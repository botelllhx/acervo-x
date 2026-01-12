<?php
if (!defined('ABSPATH')) exit;

class AcervoX_CSV_Importer {
    
    private $collection_id;
    private $file_path;
    private $delimiter = ',';
    private $enclosure = '"';
    private $headers = [];
    private $total_rows = 0;
    private $processed = 0;
    private $errors = [];
    private $log = [];
    
    public function __construct($file_path, $collection_id) {
        $this->file_path = $file_path;
        $this->collection_id = absint($collection_id);
    }
    
    public function set_delimiter($delimiter) {
        $this->delimiter = $delimiter;
    }
    
    public function set_enclosure($enclosure) {
        $this->enclosure = $enclosure;
    }
    
    public function parse_file() {
        if (!file_exists($this->file_path)) {
            return new WP_Error('file_not_found', 'Arquivo não encontrado');
        }
        
        $handle = fopen($this->file_path, 'r');
        if (!$handle) {
            return new WP_Error('file_open_error', 'Erro ao abrir arquivo');
        }
        
        // Ler cabeçalhos
        $this->headers = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);
        
        if (empty($this->headers)) {
            fclose($handle);
            return new WP_Error('invalid_csv', 'CSV inválido: cabeçalhos não encontrados');
        }
        
        // Contar linhas
        $row_count = 0;
        while (fgetcsv($handle, 0, $this->delimiter, $this->enclosure) !== false) {
            $row_count++;
        }
        $this->total_rows = $row_count;
        
        fclose($handle);
        
        return [
            'headers' => $this->headers,
            'total_rows' => $this->total_rows,
            'collection_id' => $this->collection_id
        ];
    }
    
    public function import($offset = 0, $limit = 10) {
        if (!file_exists($this->file_path)) {
            return new WP_Error('file_not_found', 'Arquivo não encontrado');
        }
        
        $handle = fopen($this->file_path, 'r');
        if (!$handle) {
            return new WP_Error('file_open_error', 'Erro ao abrir arquivo');
        }
        
        // Ler cabeçalhos
        $headers = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);
        
        if (empty($headers)) {
            fclose($handle);
            return new WP_Error('invalid_csv', 'CSV inválido');
        }
        
        // Pular linhas até o offset
        for ($i = 0; $i < $offset; $i++) {
            if (fgetcsv($handle, 0, $this->delimiter, $this->enclosure) === false) {
                break;
            }
        }
        
        $imported = 0;
        $errors = 0;
        $current_row = $offset + 1;
        
        // Processar linhas
        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure)) !== false && $imported < $limit) {
            if (count($row) !== count($headers)) {
                $this->errors[] = "Linha {$current_row}: Número de colunas não corresponde aos cabeçalhos";
                $errors++;
                $current_row++;
                continue;
            }
            
            $data = array_combine($headers, $row);
            
            // Criar item
            $result = $this->create_item($data, $current_row);
            
            if (is_wp_error($result)) {
                $this->errors[] = "Linha {$current_row}: " . $result->get_error_message();
                $errors++;
            } else {
                $imported++;
                $this->log[] = "Linha {$current_row}: Item criado com sucesso (ID: {$result})";
            }
            
            $current_row++;
        }
        
        fclose($handle);
        
        $this->processed += $imported;
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'processed' => $this->processed,
            'total' => $this->total_rows,
            'log' => array_slice($this->log, -10), // Últimas 10 entradas
            'errors_list' => array_slice($this->errors, -10) // Últimos 10 erros
        ];
    }
    
    private function create_item($data, $row_number) {
        // Buscar título de forma flexível (case-insensitive e com variações)
        $title = null;
        $title_variations = ['title', 'titulo', 'Title', 'Titulo', 'TITLE', 'TITULO', 'nome', 'Nome', 'NOME', 'name', 'Name', 'NAME'];
        
        foreach ($title_variations as $variation) {
            if (!empty($data[$variation])) {
                $title = trim($data[$variation]);
                break;
            }
        }
        
        // Se não encontrou, tentar buscar por similaridade (case-insensitive)
        if (empty($title)) {
            foreach ($data as $key => $value) {
                $key_lower = strtolower(trim($key));
                if (in_array($key_lower, array_map('strtolower', $title_variations)) || 
                    strpos($key_lower, 'titulo') !== false || 
                    strpos($key_lower, 'title') !== false ||
                    strpos($key_lower, 'nome') !== false) {
                    $title = trim($value);
                    break;
                }
            }
        }
        
        // Se ainda não encontrou, usar o primeiro campo não vazio
        if (empty($title)) {
            foreach ($data as $key => $value) {
                $value_trimmed = trim($value);
                if (!empty($value_trimmed)) {
                    $title = $value_trimmed;
                    break;
                }
            }
        }
        
        // Se ainda não tem título, retornar erro
        if (empty($title)) {
            return new WP_Error('missing_title', 'Título não encontrado. Verifique se o CSV possui uma coluna de título (title, titulo, nome, etc.)');
        }
        
        // Criar post
        $post_id = wp_insert_post([
            'post_type' => 'acervox_item',
            'post_title' => sanitize_text_field($title),
            'post_content' => !empty($data['description']) || !empty($data['descricao']) 
                ? wp_kses_post($data['description'] ?? $data['descricao']) 
                : '',
            'post_excerpt' => !empty($data['excerpt']) || !empty($data['resumo'])
                ? sanitize_text_field($data['excerpt'] ?? $data['resumo'])
                : '',
            'post_status' => 'publish'
        ]);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Vincular à coleção
        update_post_meta($post_id, '_acervox_collection', $this->collection_id);
        
        // Salvar descrição padrão
        if (!empty($data['description']) || !empty($data['descricao'])) {
            update_post_meta($post_id, '_acervox_default_description', sanitize_textarea_field($data['description'] ?? $data['descricao']));
        }
        
        // Processar metadados personalizados
        if ($this->collection_id && class_exists('AcervoX_Meta_Registry')) {
            $fields = AcervoX_Meta_Registry::get_fields($this->collection_id);
            
            foreach ($fields as $field) {
                $key = $field['key'] ?? '';
                $label = strtolower($field['label'] ?? '');
                
                // Tentar encontrar o valor no CSV
                $value = null;
                foreach ($data as $csv_key => $csv_value) {
                    $csv_key_lower = strtolower($csv_key);
                    if ($csv_key_lower === $key || $csv_key_lower === $label || 
                        str_replace([' ', '_', '-'], '', $csv_key_lower) === str_replace([' ', '_', '-'], '', $key)) {
                        $value = $csv_value;
                        break;
                    }
                }
                
                if ($value !== null && $value !== '') {
                    update_post_meta($post_id, '_acervox_' . $key, sanitize_text_field($value));
                }
            }
        }
        
        // Processar imagem (URL ou caminho)
        if (!empty($data['image']) || !empty($data['imagem']) || !empty($data['thumbnail']) || !empty($data['url'])) {
            $image_url = $data['image'] ?? $data['imagem'] ?? $data['thumbnail'] ?? $data['url'];
            $this->import_image($post_id, $image_url);
        }
        
        return $post_id;
    }
    
    private function import_image($post_id, $image_url) {
        if (empty($image_url)) {
            return false;
        }
        
        // Se for URL externa, fazer download
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $tmp = download_url($image_url);
            
            if (is_wp_error($tmp)) {
                return false;
            }
            
            $file_array = [
                'name' => basename($image_url),
                'tmp_name' => $tmp
            ];
            
            $attachment_id = media_handle_sideload($file_array, $post_id);
            
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
            
            @unlink($tmp);
        } elseif (file_exists($image_url)) {
            // Se for caminho local
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $file_array = [
                'name' => basename($image_url),
                'tmp_name' => $image_url
            ];
            
            $attachment_id = media_handle_sideload($file_array, $post_id);
            
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
        
        return true;
    }
    
    public function get_progress() {
        return [
            'processed' => $this->processed,
            'total' => $this->total_rows,
            'percentage' => $this->total_rows > 0 ? round(($this->processed / $this->total_rows) * 100, 2) : 0
        ];
    }
    
    public function get_errors() {
        return $this->errors;
    }
    
    public function get_log() {
        return $this->log;
    }
}
