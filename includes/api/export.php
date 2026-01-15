<?php
if (!defined('ABSPATH')) exit;

/**
 * Sistema de Exportação de Dados
 * Suporta CSV, JSON e XML
 */
class AcervoX_Exporter {
    
    /**
     * Exportar coleção para CSV
     */
    public static function export_csv($collection_id, $item_ids = []) {
        $items = self::get_items($collection_id, $item_ids);
        
        if (empty($items)) {
            return new WP_Error('no_items', 'Nenhum item encontrado', ['status' => 404]);
        }
        
        // Buscar campos de metadados
        $fields = [];
        if (class_exists('AcervoX_Meta_Registry')) {
            $fields = AcervoX_Meta_Registry::get_fields($collection_id);
        }
        
        // Preparar cabeçalhos
        $headers = ['ID', 'Título', 'Descrição', 'Resumo', 'Data', 'URL'];
        foreach ($fields as $field) {
            $headers[] = $field['label'] ?? $field['key'];
        }
        $headers[] = 'Imagem URL';
        $headers[] = 'Tags';
        $headers[] = 'Categorias';
        
        // Criar arquivo CSV temporário
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/acervox-export-' . time() . '.csv';
        $handle = fopen($temp_file, 'w');
        
        // Escrever BOM para UTF-8 (Excel)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escrever cabeçalhos
        fputcsv($handle, $headers);
        
        // Escrever dados
        foreach ($items as $item) {
            $row = [
                $item->ID,
                $item->post_title,
                $item->post_content,
                $item->post_excerpt,
                get_the_date('Y-m-d H:i:s', $item->ID),
                get_permalink($item->ID)
            ];
            
            // Metadados personalizados
            foreach ($fields as $field) {
                $key = $field['key'] ?? '';
                $value = get_post_meta($item->ID, '_acervox_' . $key, true);
                $row[] = is_array($value) ? implode('; ', $value) : ($value ?: '');
            }
            
            // URL da imagem
            $thumbnail_url = get_the_post_thumbnail_url($item->ID, 'full');
            $row[] = $thumbnail_url ?: '';
            
            // Tags
            $tags = get_the_terms($item->ID, 'acervox_tag');
            $tag_names = $tags && !is_wp_error($tags) 
                ? implode(', ', wp_list_pluck($tags, 'name'))
                : '';
            $row[] = $tag_names;
            
            // Categorias
            $categories = get_the_terms($item->ID, 'acervox_category');
            $cat_names = $categories && !is_wp_error($categories)
                ? implode(', ', wp_list_pluck($categories, 'name'))
                : '';
            $row[] = $cat_names;
            
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        return [
            'file_path' => $temp_file,
            'file_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $temp_file),
            'total_items' => count($items),
            'format' => 'csv'
        ];
    }
    
    /**
     * Exportar coleção para JSON
     */
    public static function export_json($collection_id, $item_ids = []) {
        $items = self::get_items($collection_id, $item_ids);
        
        if (empty($items)) {
            return new WP_Error('no_items', 'Nenhum item encontrado', ['status' => 404]);
        }
        
        $collection = get_post($collection_id);
        $fields = [];
        if (class_exists('AcervoX_Meta_Registry')) {
            $fields = AcervoX_Meta_Registry::get_fields($collection_id);
        }
        
        $data = [
            'collection' => [
                'id' => $collection_id,
                'title' => $collection ? $collection->post_title : '',
                'description' => $collection ? $collection->post_content : '',
            ],
            'fields' => $fields,
            'items' => []
        ];
        
        foreach ($items as $item) {
            $item_data = [
                'id' => $item->ID,
                'title' => $item->post_title,
                'description' => $item->post_content,
                'excerpt' => $item->post_excerpt,
                'date' => get_the_date('c', $item->ID),
                'modified' => get_the_modified_date('c', $item->ID),
                'permalink' => get_permalink($item->ID),
                'thumbnail' => get_the_post_thumbnail_url($item->ID, 'full'),
                'meta' => [],
                'tags' => [],
                'categories' => []
            ];
            
            // Metadados
            foreach ($fields as $field) {
                $key = $field['key'] ?? '';
                $value = get_post_meta($item->ID, '_acervox_' . $key, true);
                if ($value) {
                    $item_data['meta'][$key] = [
                        'label' => $field['label'] ?? $key,
                        'type' => $field['type'] ?? 'text',
                        'value' => $value
                    ];
                }
            }
            
            // Tags
            $tags = get_the_terms($item->ID, 'acervox_tag');
            if ($tags && !is_wp_error($tags)) {
                $item_data['tags'] = array_map(function($tag) {
                    return [
                        'id' => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug
                    ];
                }, $tags);
            }
            
            // Categorias
            $categories = get_the_terms($item->ID, 'acervox_category');
            if ($categories && !is_wp_error($categories)) {
                $item_data['categories'] = array_map(function($cat) {
                    return [
                        'id' => $cat->term_id,
                        'name' => $cat->name,
                        'slug' => $cat->slug
                    ];
                }, $categories);
            }
            
            $data['items'][] = $item_data;
        }
        
        // Criar arquivo JSON
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/acervox-export-' . time() . '.json';
        file_put_contents($temp_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return [
            'file_path' => $temp_file,
            'file_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $temp_file),
            'total_items' => count($items),
            'format' => 'json'
        ];
    }
    
    /**
     * Exportar coleção para XML
     */
    public static function export_xml($collection_id, $item_ids = []) {
        $items = self::get_items($collection_id, $item_ids);
        
        if (empty($items)) {
            return new WP_Error('no_items', 'Nenhum item encontrado', ['status' => 404]);
        }
        
        $collection = get_post($collection_id);
        $fields = [];
        if (class_exists('AcervoX_Meta_Registry')) {
            $fields = AcervoX_Meta_Registry::get_fields($collection_id);
        }
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement('acervox_export');
        $xml->appendChild($root);
        
        // Informações da coleção
        $collection_node = $xml->createElement('collection');
        $collection_node->setAttribute('id', $collection_id);
        $collection_node->appendChild($xml->createElement('title', $collection ? $collection->post_title : ''));
        $collection_node->appendChild($xml->createElement('description', $collection ? $collection->post_content : ''));
        $root->appendChild($collection_node);
        
        // Itens
        $items_node = $xml->createElement('items');
        foreach ($items as $item) {
            $item_node = $xml->createElement('item');
            $item_node->setAttribute('id', $item->ID);
            
            $item_node->appendChild($xml->createElement('title', htmlspecialchars($item->post_title, ENT_XML1, 'UTF-8')));
            $item_node->appendChild($xml->createElement('description', htmlspecialchars($item->post_content, ENT_XML1, 'UTF-8')));
            $item_node->appendChild($xml->createElement('excerpt', htmlspecialchars($item->post_excerpt, ENT_XML1, 'UTF-8')));
            $item_node->appendChild($xml->createElement('date', get_the_date('c', $item->ID)));
            $item_node->appendChild($xml->createElement('permalink', get_permalink($item->ID)));
            
            $thumbnail_url = get_the_post_thumbnail_url($item->ID, 'full');
            if ($thumbnail_url) {
                $item_node->appendChild($xml->createElement('thumbnail', $thumbnail_url));
            }
            
            // Metadados
            if (!empty($fields)) {
                $meta_node = $xml->createElement('meta');
                foreach ($fields as $field) {
                    $key = $field['key'] ?? '';
                    $value = get_post_meta($item->ID, '_acervox_' . $key, true);
                    if ($value) {
                        $field_node = $xml->createElement('field');
                        $field_node->setAttribute('key', $key);
                        $field_node->setAttribute('label', $field['label'] ?? $key);
                        $field_node->setAttribute('type', $field['type'] ?? 'text');
                        $field_node->appendChild($xml->createTextNode(is_array($value) ? implode(', ', $value) : $value));
                        $meta_node->appendChild($field_node);
                    }
                }
                $item_node->appendChild($meta_node);
            }
            
            // Tags
            $tags = get_the_terms($item->ID, 'acervox_tag');
            if ($tags && !is_wp_error($tags)) {
                $tags_node = $xml->createElement('tags');
                foreach ($tags as $tag) {
                    $tag_node = $xml->createElement('tag', htmlspecialchars($tag->name, ENT_XML1, 'UTF-8'));
                    $tag_node->setAttribute('id', $tag->term_id);
                    $tag_node->setAttribute('slug', $tag->slug);
                    $tags_node->appendChild($tag_node);
                }
                $item_node->appendChild($tags_node);
            }
            
            // Categorias
            $categories = get_the_terms($item->ID, 'acervox_category');
            if ($categories && !is_wp_error($categories)) {
                $cats_node = $xml->createElement('categories');
                foreach ($categories as $cat) {
                    $cat_node = $xml->createElement('category', htmlspecialchars($cat->name, ENT_XML1, 'UTF-8'));
                    $cat_node->setAttribute('id', $cat->term_id);
                    $cat_node->setAttribute('slug', $cat->slug);
                    $cats_node->appendChild($cat_node);
                }
                $item_node->appendChild($cats_node);
            }
            
            $items_node->appendChild($item_node);
        }
        $root->appendChild($items_node);
        
        // Salvar arquivo
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/acervox-export-' . time() . '.xml';
        $xml->save($temp_file);
        
        return [
            'file_path' => $temp_file,
            'file_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $temp_file),
            'total_items' => count($items),
            'format' => 'xml'
        ];
    }
    
    /**
     * Buscar itens para exportação
     */
    private static function get_items($collection_id, $item_ids = []) {
        $args = [
            'post_type' => 'acervox_item',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];
        
        if (!empty($item_ids)) {
            $args['post__in'] = array_map('absint', $item_ids);
        } else {
            $args['meta_query'] = [
                [
                    'key' => '_acervox_collection',
                    'value' => absint($collection_id),
                    'compare' => '='
                ]
            ];
        }
        
        return get_posts($args);
    }
}
