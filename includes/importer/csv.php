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
        
        // Ler primeira linha de dados para análise
        $first_row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);
        $sample_data = null;
        if ($first_row && count($first_row) === count($this->headers)) {
            $sample_data = array_combine($this->headers, $first_row);
        }
        
        // Contar linhas (já lemos a primeira, então começamos de 1)
        $row_count = $first_row ? 1 : 0;
        while (fgetcsv($handle, 0, $this->delimiter, $this->enclosure) !== false) {
            $row_count++;
        }
        $this->total_rows = $row_count;
        
        fclose($handle);
        
        // Criar campos automaticamente se não existirem
        if ($this->collection_id && class_exists('AcervoX_Meta_Registry')) {
            $existing_fields = AcervoX_Meta_Registry::get_fields($this->collection_id);
            if (empty($existing_fields) && !empty($this->headers) && $sample_data) {
                $this->auto_create_fields_from_csv($sample_data);
            }
        }
        
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
            
            // Campos a ignorar (já processados ou especiais)
            $ignore_fields = [
                'title', 'titulo', 'nome', 'name', 'description', 'descricao', 
                'excerpt', 'resumo', 'content', 'conteudo',
                'image', 'imagem', 'thumbnail', 'special_thumbnail', 'url',
                'special_item_id', 'special_item_status', 'special_document',
                'special_attachments', 'special_comment_status', 'special_item_author',
                'special_item_slug', 'creation_date', 'user_last_modified',
                'modification_date', 'public_url', 'coleção', 'collection'
            ];
            
            foreach ($fields as $field) {
                $key = $field['key'] ?? '';
                $label = strtolower($field['label'] ?? '');
                
                // Pular campos ignorados
                $key_lower = strtolower($key);
                if (in_array($key_lower, array_map('strtolower', $ignore_fields))) {
                    continue;
                }
                
                // Tentar encontrar o valor no CSV
                $value = null;
                foreach ($data as $csv_key => $csv_value) {
                    $csv_key_lower = strtolower(trim($csv_key));
                    
                    // Remover caracteres especiais para comparação
                    $csv_key_clean = str_replace(['|', ':', '/', '\\'], '', $csv_key_lower);
                    $key_clean = str_replace(['|', ':', '/', '\\'], '', $key_lower);
                    
                    // Comparação flexível
                    if ($csv_key_lower === $key_lower || 
                        $csv_key_lower === $label ||
                        $csv_key_clean === $key_clean ||
                        str_replace([' ', '_', '-'], '', $csv_key_clean) === str_replace([' ', '_', '-'], '', $key_clean)) {
                        $value = $csv_value;
                        break;
                    }
                }
                
                // Se não encontrou por chave exata, tentar por similaridade no label
                if ($value === null || $value === '') {
                    foreach ($data as $csv_key => $csv_value) {
                        $csv_key_lower = strtolower(trim($csv_key));
                        // Remover informações de tipo do CSV (ex: "Título|core_title|required")
                        $csv_key_base = explode('|', $csv_key_lower)[0];
                        $csv_key_base = trim($csv_key_base);
                        
                        if ($csv_key_base === $label || 
                            str_replace([' ', '_', '-'], '', $csv_key_base) === str_replace([' ', '_', '-'], '', $label)) {
                            $value = $csv_value;
                            break;
                        }
                    }
                }
                
                if ($value !== null && $value !== '') {
                    $field_type = $field['type'] ?? 'text';
                    
                    // Sanitizar baseado no tipo
                    switch ($field_type) {
                        case 'textarea':
                            $sanitized_value = sanitize_textarea_field($value);
                            break;
                        case 'number':
                            $sanitized_value = is_numeric($value) ? $value : '';
                            break;
                        default:
                            $sanitized_value = sanitize_text_field($value);
                    }
                    
                    if (!empty($sanitized_value)) {
                        update_post_meta($post_id, '_acervox_' . $key, $sanitized_value);
                    }
                }
            }
        }
        
        // Processar imagem (URL ou caminho) - procurar em várias colunas possíveis
        $image_url = null;
        $image_columns = [
            'special_thumbnail', 'thumbnail', 'imagem', 'image', 'url', 
            'featured_image', 'featured-image', 'imagem_destaque', 'imagem_destaque'
        ];
        
        foreach ($image_columns as $col) {
            if (!empty($data[$col])) {
                $image_url = trim($data[$col]);
                // Remover prefixo "file:" se existir
                if (strpos($image_url, 'file:') === 0) {
                    $image_url = substr($image_url, 5);
                }
                break;
            }
        }
        
        // Se não encontrou nas colunas conhecidas, procurar por similaridade
        if (empty($image_url)) {
            foreach ($data as $key => $value) {
                $key_lower = strtolower(trim($key));
                if (strpos($key_lower, 'thumbnail') !== false || 
                    strpos($key_lower, 'imagem') !== false || 
                    strpos($key_lower, 'image') !== false ||
                    strpos($key_lower, 'foto') !== false ||
                    strpos($key_lower, 'photo') !== false) {
                    $image_url = trim($value);
                    if (strpos($image_url, 'file:') === 0) {
                        $image_url = substr($image_url, 5);
                    }
                    if (!empty($image_url)) {
                        break;
                    }
                }
            }
        }
        
        if (!empty($image_url)) {
            $this->import_image($post_id, $image_url);
        }
        
        return $post_id;
    }
    
    private function import_image($post_id, $image_url) {
        if (empty($image_url)) {
            return false;
        }
        
        // Limpar URL de espaços e caracteres especiais
        $image_url = trim($image_url);
        
        // Se for URL externa (http:// ou https://), fazer download
        if (filter_var($image_url, FILTER_VALIDATE_URL) && (strpos($image_url, 'http://') === 0 || strpos($image_url, 'https://') === 0)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            // Verificar se a imagem já existe na biblioteca de mídia
            $attachment_id = $this->get_attachment_by_url($image_url);
            
            if (!$attachment_id) {
                // Fazer download da imagem
                $tmp = download_url($image_url);
                
                if (is_wp_error($tmp)) {
                    $this->log[] = "Erro ao baixar imagem: " . $tmp->get_error_message();
                    return false;
                }
                
                // Obter extensão do arquivo
                $file_ext = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
                $file_name = sanitize_file_name(basename(parse_url($image_url, PHP_URL_PATH)));
                
                if (empty($file_ext)) {
                    // Tentar detectar tipo pela URL ou conteúdo
                    $file_ext = 'jpg'; // padrão
                }
                
                $file_array = [
                    'name' => $file_name ?: 'image.' . $file_ext,
                    'tmp_name' => $tmp
                ];
                
                $attachment_id = media_handle_sideload($file_array, $post_id);
                
                if (is_wp_error($attachment_id)) {
                    $this->log[] = "Erro ao fazer upload da imagem: " . $attachment_id->get_error_message();
                    @unlink($tmp);
                    return false;
                }
                
                @unlink($tmp);
            }
            
            // Definir como featured image
            if ($attachment_id && !is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
                return true;
            }
        } elseif (file_exists($image_url)) {
            // Se for caminho local absoluto
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
                return true;
            } else {
                $this->log[] = "Erro ao fazer upload da imagem local: " . $attachment_id->get_error_message();
            }
        } else {
            // Tentar como URL relativa ou caminho do WordPress
            $upload_dir = wp_upload_dir();
            $possible_paths = [
                $image_url,
                $upload_dir['basedir'] . '/' . ltrim($image_url, '/'),
                ABSPATH . ltrim($image_url, '/'),
            ];
            
            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    
                    $file_array = [
                        'name' => basename($path),
                        'tmp_name' => $path
                    ];
                    
                    $attachment_id = media_handle_sideload($file_array, $post_id);
                    
                    if (!is_wp_error($attachment_id)) {
                        set_post_thumbnail($post_id, $attachment_id);
                        return true;
                    }
                    break;
                }
            }
            
            $this->log[] = "Imagem não encontrada: " . $image_url;
        }
        
        return false;
    }
    
    private function get_attachment_by_url($url) {
        global $wpdb;
        
        // Extrair nome do arquivo da URL
        $filename = basename(parse_url($url, PHP_URL_PATH));
        
        if (empty($filename)) {
            return false;
        }
        
        // Buscar attachment pelo nome do arquivo
        $attachment = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wp_attached_file' 
            AND meta_value LIKE %s 
            LIMIT 1",
            '%' . $wpdb->esc_like($filename) . '%'
        ));
        
        if (!empty($attachment)) {
            return (int) $attachment[0];
        }
        
        return false;
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
    
    /**
     * Cria campos de metadados automaticamente baseado nas colunas do CSV
     */
    private function auto_create_fields_from_csv($sample_data) {
        if (!class_exists('AcervoX_Meta_Registry')) {
            return;
        }
        
        // Buscar headers do arquivo
        if (empty($this->headers)) {
            $handle = fopen($this->file_path, 'r');
            if ($handle) {
                $this->headers = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);
                fclose($handle);
            }
        }
        
        if (empty($this->headers)) {
            return;
        }
        
        $fields = [];
        $ignore_headers = [
            'special_item_id', 'special_item_status', 'special_document',
            'special_thumbnail', 'special_attachments', 'special_comment_status',
            'special_item_author', 'special_item_slug', 'creation_date',
            'user_last_modified', 'modification_date', 'public_url'
        ];
        
        foreach ($this->headers as $header) {
            $header_clean = trim($header);
            
            // Extrair nome do campo (remover informações de tipo, ex: "Título|core_title|required")
            $header_parts = explode('|', $header_clean);
            $field_name = trim($header_parts[0]);
            
            // Ignorar campos especiais e vazios
            if (empty($field_name) || in_array(strtolower($field_name), array_map('strtolower', $ignore_headers))) {
                continue;
            }
            
            // Ignorar campos de imagem (já processados)
            $field_name_lower = strtolower($field_name);
            if (strpos($field_name_lower, 'thumbnail') !== false || 
                strpos($field_name_lower, 'imagem') !== false ||
                strpos($field_name_lower, 'image') !== false) {
                continue;
            }
            
            // Ignorar campos de título e descrição (já processados)
            if (in_array($field_name_lower, ['title', 'titulo', 'nome', 'name', 'description', 'descricao', 'excerpt', 'resumo'])) {
                continue;
            }
            
            // Determinar tipo do campo baseado no nome ou conteúdo
            $field_type = 'text';
            if (isset($header_parts[1])) {
                $type_hint = strtolower(trim($header_parts[1]));
                if (strpos($type_hint, 'textarea') !== false || strpos($type_hint, 'text_area') !== false) {
                    $field_type = 'textarea';
                } elseif (strpos($type_hint, 'number') !== false || strpos($type_hint, 'numeric') !== false) {
                    $field_type = 'number';
                } elseif (strpos($type_hint, 'select') !== false || strpos($type_hint, 'selectbox') !== false) {
                    $field_type = 'select';
                }
            }
            
            // Verificar se é textarea pelo nome
            if (strpos($field_name_lower, 'descrição') !== false || 
                strpos($field_name_lower, 'description') !== false ||
                strpos($field_name_lower, 'observação') !== false ||
                strpos($field_name_lower, 'observacao') !== false ||
                strpos($field_name_lower, 'histórico') !== false ||
                strpos($field_name_lower, 'historico') !== false ||
                strpos($field_name_lower, 'dados') !== false ||
                strpos($field_name_lower, 'referência') !== false ||
                strpos($field_name_lower, 'referencia') !== false) {
                $field_type = 'textarea';
            }
            
            // Criar chave única baseada no nome
            $key = sanitize_title($field_name);
            $key = str_replace(['-', '_'], '', $key);
            
            // Verificar se o campo já existe
            $exists = false;
            foreach ($fields as $existing_field) {
                if ($existing_field['key'] === $key) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $fields[] = [
                    'key' => $key,
                    'label' => $field_name,
                    'type' => $field_type
                ];
            }
        }
        
        // Salvar campos na coleção
        if (!empty($fields)) {
            AcervoX_Meta_Registry::save_fields($this->collection_id, $fields);
            $this->log[] = count($fields) . ' campos de metadados criados automaticamente a partir do CSV';
        }
    }
}
