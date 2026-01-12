<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {

    // Rota antiga mantida para compatibilidade, mas items.php tem a versão avançada
    register_rest_route('acervox/v1', '/items/legacy', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_items',
        'args' => [
            'collection' => ['required' => false],
            'page' => ['required' => false],
            'per_page' => ['required' => false],
            'meta' => ['required' => false],
        ]
    ]);

    // Endpoint para buscar coleções de sistemas externos
    register_rest_route('acervox/v1', '/external/collections', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_external_collections',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    register_rest_route('acervox/v1', '/import/external', [
        'methods' => 'POST',
        'callback' => 'acervox_import_external',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    register_rest_route('acervox/v1', '/collections', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_collections',
        'args' => [
            'per_page' => ['required' => false],
            'page' => ['required' => false],
        ]
    ]);

    register_rest_route('acervox/v1', '/shortcodes', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_shortcodes',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    register_rest_route('acervox/v1', '/shortcodes', [
        'methods' => 'POST',
        'callback' => 'acervox_api_save_shortcodes',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    // Endpoint para vincular item a coleção
    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/link-collection', [
        'methods' => 'POST',
        'callback' => 'acervox_api_link_item_to_collection',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'args' => [
            'id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ],
            'collection_id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) || empty($param);
                }
            ]
        ]
    ]);

    // Endpoint para desvincular item de coleção
    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/unlink-collection', [
        'methods' => 'POST',
        'callback' => 'acervox_api_unlink_item_from_collection',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'args' => [
            'id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);

    // Endpoint para obter configurações
    register_rest_route('acervox/v1', '/settings', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_settings',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    // Endpoint para salvar configurações
    register_rest_route('acervox/v1', '/settings', [
        'methods' => 'POST',
        'callback' => 'acervox_api_save_settings',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    // Endpoint para upload e parse de CSV
    register_rest_route('acervox/v1', '/import/csv/parse', [
        'methods' => 'POST',
        'callback' => 'acervox_api_parse_csv',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    // Endpoint para importar CSV (processamento em lote)
    register_rest_route('acervox/v1', '/import/csv', [
        'methods' => 'POST',
        'callback' => 'acervox_api_import_csv',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    // Endpoint para exportar coleção para CSV
    register_rest_route('acervox/v1', '/export/csv', [
        'methods' => 'GET',
        'callback' => 'acervox_api_export_csv',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    // Endpoint para obter histórico de importações
    register_rest_route('acervox/v1', '/import-history', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_import_history',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    // Endpoint para obter detalhes de uma importação
    register_rest_route('acervox/v1', '/import-history/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_import_details',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    // Endpoint para deletar histórico
    register_rest_route('acervox/v1', '/import-history/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'acervox_api_delete_import_history',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

});

function acervox_api_get_items($request)
{
    $args = [
        'post_type' => 'acervox_item',
        'posts_per_page' => isset($request['per_page']) ? absint($request['per_page']) : 12,
        'paged' => isset($request['page']) ? absint($request['page']) : 1,
        'post_status' => 'publish',
    ];

    // Filtro por coleção
    if (isset($request['collection']) && !empty($request['collection'])) {
        $args['meta_query'][] = [
            'key' => '_acervox_collection',
            'value' => absint($request['collection']),
            'compare' => '='
        ];
    }

    // Filtro por meta customizado
    if (isset($request['meta']) && is_array($request['meta'])) {
        if (isset($request['meta']['key']) && isset($request['meta']['value'])) {
            $args['meta_query'][] = [
                'key' => '_acervox_' . sanitize_key($request['meta']['key']),
                'value' => sanitize_text_field($request['meta']['value']),
                'compare' => isset($request['meta']['compare']) ? $request['meta']['compare'] : '='
            ];
        }
    }

    $query = new WP_Query($args);

    $items = [];

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $collection_id = get_post_meta($post_id, '_acervox_collection', true);
        
        $item = [
            'id' => $post_id,
            'title' => get_the_title(),
            'excerpt' => get_the_excerpt(),
            'content' => get_the_content(),
            'permalink' => get_permalink(),
            'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
            'collection_id' => $collection_id ? absint($collection_id) : null,
        ];

        // Adicionar metadados relevantes
        if ($collection_id && class_exists('AcervoX_Meta_Registry')) {
            $fields = AcervoX_Meta_Registry::get_fields($collection_id);
            if (!empty($fields) && is_array($fields)) {
                $meta = [];
                foreach ($fields as $field) {
                    if (isset($field['key'])) {
                        $value = get_post_meta($post_id, '_acervox_' . $field['key'], true);
                        if ($value) {
                            $meta[$field['key']] = $value;
                        }
                    }
                }
                if (!empty($meta)) {
                    $item['meta'] = $meta;
                }
            }
        }

        $items[] = $item;
    }

    wp_reset_postdata();

    return [
        'items' => $items,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
        'current_page' => isset($request['page']) ? absint($request['page']) : 1,
    ];
}

function acervox_api_get_collections($request) {
    $args = [
        'post_type' => 'acervox_collection',
        'posts_per_page' => isset($request['per_page']) ? absint($request['per_page']) : -1,
        'paged' => isset($request['page']) ? absint($request['page']) : 1,
        'post_status' => 'publish',
    ];

    $query = new WP_Query($args);
    $collections = [];

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $fields = [];
        
        if (class_exists('AcervoX_Meta_Registry')) {
            $fields = AcervoX_Meta_Registry::get_fields($post_id);
            if (!is_array($fields)) {
                $fields = [];
            }
        }
        
        $collections[] = [
            'id' => $post_id,
            'title' => get_the_title(),
            'description' => get_the_content(),
            'fields' => $fields,
            'fields_count' => count($fields),
            'date' => get_the_date('c'),
            'modified' => get_the_modified_date('c'),
            'author' => get_the_author_meta('display_name', get_post_field('post_author', $post_id)),
        ];
    }

    wp_reset_postdata();

    return [
        'collections' => $collections,
        'total' => $query->found_posts,
    ];
}

function acervox_api_get_external_collections($request) {
    if (!class_exists('AcervoX_Tainacan_Importer')) {
        return new WP_Error('class_not_found', 'Classe não encontrada', ['status' => 500]);
    }

    if (!AcervoX_Tainacan_Importer::is_tainacan_active()) {
        return [
            'collections' => [],
            'active' => false,
            'message' => 'Sistema externo não está ativo'
        ];
    }

    $collections = AcervoX_Tainacan_Importer::get_collections();
    $formatted = [];

    if (!empty($collections)) {
        foreach ($collections as $collection) {
            if (is_object($collection) && method_exists($collection, 'get_id')) {
                $formatted[] = [
                    'id' => $collection->get_id(),
                    'title' => $collection->get_name(),
                    'description' => $collection->get_description(),
                ];
            } elseif (isset($collection->ID)) {
                $formatted[] = [
                    'id' => $collection->ID,
                    'title' => $collection->post_title,
                    'description' => $collection->post_content,
                ];
            }
        }
    }

    return [
        'collections' => $formatted,
        'active' => true,
        'total' => count($formatted)
    ];
}

function acervox_api_get_shortcodes($request) {
    $shortcodes = get_option('acervox_shortcodes', []);
    return [
        'shortcodes' => is_array($shortcodes) ? $shortcodes : []
    ];
}

function acervox_api_save_shortcodes($request) {
    $shortcodes = $request->get_param('shortcodes');
    if (is_array($shortcodes)) {
        update_option('acervox_shortcodes', $shortcodes);
        return [
            'success' => true,
            'message' => 'Shortcodes salvos com sucesso'
        ];
    }
    return new WP_Error('invalid_data', 'Dados inválidos', ['status' => 400]);
}

function acervox_import_external($request) {
    if (!class_exists('AcervoX_Tainacan_Importer')) {
        return new WP_Error('class_not_found', 'Classe AcervoX_Tainacan_Importer não encontrada', ['status' => 500]);
    }

    if (!AcervoX_Tainacan_Importer::is_tainacan_active()) {
        return new WP_Error('external_not_active', 'Sistema externo não está ativo', ['status' => 400]);
    }

    if (!isset($request['external_collection']) || empty($request['external_collection'])) {
        return new WP_Error('missing_collection', 'ID da coleção externa é obrigatório', ['status' => 400]);
    }

    $collection_id = absint($request['external_collection']);
    
    // Buscar título da coleção externa
    $external_collection = get_post($collection_id);
    $collection_title = $external_collection ? $external_collection->post_title : 'Importado de sistema externo';
    
    $acervox_collection = wp_insert_post([
        'post_type' => 'acervox_collection',
        'post_title' => sanitize_text_field($collection_title),
        'post_status' => 'publish'
    ]);

    if (is_wp_error($acervox_collection)) {
        return $acervox_collection;
    }

    if (!class_exists('AcervoX_Tainacan_Mapper') || !class_exists('AcervoX_Meta_Registry')) {
        return new WP_Error('classes_not_found', 'Classes necessárias não encontradas', ['status' => 500]);
    }

    $fields = AcervoX_Tainacan_Mapper::map_fields($collection_id);
    if (!empty($fields)) {
        AcervoX_Meta_Registry::save_fields($acervox_collection, $fields);
    }

    AcervoX_Tainacan_Importer::import_items($collection_id, $acervox_collection);

    $log = [];
    if (class_exists('AcervoX_Import_Logger')) {
        $log = AcervoX_Import_Logger::get();
    }

    return [
        'status' => 'ok',
        'collection_id' => $acervox_collection,
        'log' => $log
    ];
}

function acervox_api_link_item_to_collection($request) {
    $item_id = absint($request->get_param('id'));
    $collection_id = $request->get_param('collection_id');
    
    // Verificar se o item existe
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    // Se collection_id vazio ou 0, desvincular
    if (empty($collection_id) || $collection_id == '0') {
        delete_post_meta($item_id, '_acervox_collection');
        return [
            'success' => true,
            'message' => 'Item desvinculado da coleção com sucesso',
            'collection_id' => null
        ];
    }
    
    $collection_id = absint($collection_id);
    
    // Verificar se a coleção existe
    $collection = get_post($collection_id);
    if (!$collection || $collection->post_type !== 'acervox_collection') {
        return new WP_Error('collection_not_found', 'Coleção não encontrada', ['status' => 404]);
    }
    
    // Vincular item à coleção
    $updated = update_post_meta($item_id, '_acervox_collection', $collection_id);
    
    if ($updated !== false) {
        return [
            'success' => true,
            'message' => 'Item vinculado à coleção com sucesso',
            'item_id' => $item_id,
            'collection_id' => $collection_id,
            'collection_title' => get_the_title($collection_id)
        ];
    } else {
        return new WP_Error('update_failed', 'Falha ao vincular item à coleção', ['status' => 500]);
    }
}

function acervox_api_unlink_item_from_collection($request) {
    $item_id = absint($request->get_param('id'));
    
    // Verificar se o item existe
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    // Desvincular item da coleção
    $deleted = delete_post_meta($item_id, '_acervox_collection');
    
    return [
        'success' => true,
        'message' => 'Item desvinculado da coleção com sucesso',
        'item_id' => $item_id,
        'collection_id' => null
    ];
}

function acervox_api_get_settings($request) {
    $defaults = [
        'items_per_page' => 12,
        'default_layout' => 'grid',
        'default_columns' => 3,
        'enable_search' => true,
        'enable_filters' => true,
        'enable_pagination' => true,
        'image_quality' => 'large',
        'lazy_load_images' => true,
    ];
    
    $settings = get_option('acervox_settings', $defaults);
    
    // Mesclar com defaults para garantir que todas as chaves existam
    $settings = wp_parse_args($settings, $defaults);
    
    return $settings;
}

function acervox_api_save_settings($request) {
    $body = $request->get_json_params();
    
    if (!is_array($body)) {
        return new WP_Error('invalid_data', 'Dados inválidos', ['status' => 400]);
    }
    
    // Sanitizar e validar dados
    $allowed_keys = [
        'items_per_page',
        'default_layout',
        'default_columns',
        'enable_search',
        'enable_filters',
        'enable_pagination',
        'image_quality',
        'lazy_load_images',
    ];
    
    $sanitized = [];
    foreach ($allowed_keys as $key) {
        if (isset($body[$key])) {
            switch ($key) {
                case 'items_per_page':
                case 'default_columns':
                    $sanitized[$key] = absint($body[$key]);
                    break;
                case 'default_layout':
                    $sanitized[$key] = in_array($body[$key], ['grid', 'masonry', 'list']) 
                        ? sanitize_text_field($body[$key]) 
                        : 'grid';
                    break;
                case 'image_quality':
                    $sanitized[$key] = in_array($body[$key], ['thumbnail', 'medium', 'large', 'full']) 
                        ? sanitize_text_field($body[$key]) 
                        : 'large';
                    break;
                case 'enable_search':
                case 'enable_filters':
                case 'enable_pagination':
                case 'lazy_load_images':
                    $sanitized[$key] = (bool) $body[$key];
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field($body[$key]);
            }
        }
    }
    
    // Salvar configurações
    $updated = update_option('acervox_settings', $sanitized);
    
    return [
        'success' => true,
        'message' => 'Configurações salvas com sucesso',
        'settings' => $sanitized
    ];
}

function acervox_api_parse_csv($request) {
    if (!isset($_FILES['file'])) {
        return new WP_Error('no_file', 'Nenhum arquivo enviado', ['status' => 400]);
    }
    
    $file = $_FILES['file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('upload_error', 'Erro no upload do arquivo', ['status' => 400]);
    }
    
    // Validar extensão
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        return new WP_Error('invalid_file', 'Apenas arquivos CSV são permitidos', ['status' => 400]);
    }
    
    // Mover arquivo para diretório temporário
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/acervox-imports';
    
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }
    
    $temp_file = $temp_dir . '/' . wp_unique_filename($temp_dir, $file['name']);
    
    if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
        return new WP_Error('move_error', 'Erro ao mover arquivo', ['status' => 500]);
    }
    
    // Parse do CSV
    $collection_id = $request->get_param('collection_id');
    if (!$collection_id) {
        @unlink($temp_file);
        return new WP_Error('missing_collection', 'ID da coleção é obrigatório', ['status' => 400]);
    }
    
    $importer = new AcervoX_CSV_Importer($temp_file, $collection_id);
    $result = $importer->parse_file();
    
    if (is_wp_error($result)) {
        @unlink($temp_file);
        return $result;
    }
    
    // Salvar referência do arquivo temporário
    $session_key = 'acervox_csv_import_' . wp_generate_password(12, false);
    set_transient($session_key, [
        'file' => $temp_file,
        'collection_id' => $collection_id,
        'headers' => $result['headers'],
        'total_rows' => $result['total_rows']
    ], HOUR_IN_SECONDS);
    
    return [
        'success' => true,
        'session_key' => $session_key,
        'headers' => $result['headers'],
        'total_rows' => $result['total_rows'],
        'collection_id' => $collection_id
    ];
}

function acervox_api_import_csv($request) {
    $body = $request->get_json_params();
    
    $session_key = $body['session_key'] ?? '';
    $offset = absint($body['offset'] ?? 0);
    $limit = absint($body['limit'] ?? 10);
    
    if (empty($session_key)) {
        return new WP_Error('missing_session', 'Chave de sessão não fornecida', ['status' => 400]);
    }
    
    $session_data = get_transient($session_key);
    
    if (!$session_data) {
        return new WP_Error('invalid_session', 'Sessão expirada ou inválida', ['status' => 400]);
    }
    
    $importer = new AcervoX_CSV_Importer($session_data['file'], $session_data['collection_id']);
    $result = $importer->import($offset, $limit);
    
    // Se terminou, limpar arquivo temporário
    if ($result['processed'] >= $session_data['total_rows']) {
        @unlink($session_data['file']);
        delete_transient($session_key);
        $result['completed'] = true;
    }
    
    return $result;
}

function acervox_api_export_csv($request) {
    $collection_id = $request->get_param('collection_id');
    
    if (!$collection_id) {
        return new WP_Error('missing_collection', 'ID da coleção é obrigatório', ['status' => 400]);
    }
    
    // Buscar itens da coleção
    $items = get_posts([
        'post_type' => 'acervox_item',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_acervox_collection',
                'value' => absint($collection_id),
                'compare' => '='
            ]
        ]
    ]);
    
    if (empty($items)) {
        return new WP_Error('no_items', 'Nenhum item encontrado na coleção', ['status' => 404]);
    }
    
    // Buscar campos de metadados
    $fields = [];
    if (class_exists('AcervoX_Meta_Registry')) {
        $fields = AcervoX_Meta_Registry::get_fields($collection_id);
    }
    
    // Preparar cabeçalhos
    $headers = ['ID', 'Título', 'Descrição', 'Resumo'];
    foreach ($fields as $field) {
        $headers[] = $field['label'] ?? $field['key'];
    }
    $headers[] = 'Imagem URL';
    
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
            $item->post_excerpt
        ];
        
        // Metadados personalizados
        foreach ($fields as $field) {
            $key = $field['key'] ?? '';
            $value = get_post_meta($item->ID, '_acervox_' . $key, true);
            $row[] = $value ?: '';
        }
        
        // URL da imagem
        $thumbnail_url = get_the_post_thumbnail_url($item->ID, 'full');
        $row[] = $thumbnail_url ?: '';
        
        fputcsv($handle, $row);
    }
    
    fclose($handle);
    
    // Retornar URL do arquivo
    $file_url = $upload_dir['baseurl'] . '/acervox-export-' . time() . '.csv';
    
    return [
        'success' => true,
        'file_url' => $file_url,
        'file_path' => $temp_file,
        'total_items' => count($items)
    ];
}

function acervox_api_get_import_history($request) {
    if (!class_exists('AcervoX_Import_History')) {
        return new WP_Error('class_not_found', 'Classe não encontrada', ['status' => 500]);
    }
    
    $args = [
        'per_page' => absint($request->get_param('per_page')) ?: 20,
        'page' => absint($request->get_param('page')) ?: 1,
        'orderby' => sanitize_text_field($request->get_param('orderby')) ?: 'started_at',
        'order' => sanitize_text_field($request->get_param('order')) ?: 'DESC',
        'import_type' => sanitize_text_field($request->get_param('import_type')),
        'collection_id' => absint($request->get_param('collection_id')),
        'status' => sanitize_text_field($request->get_param('status'))
    ];
    
    $result = AcervoX_Import_History::get_all($args);
    
    // Formatar dados
    foreach ($result['items'] as &$item) {
        if ($item['collection_id']) {
            $collection = get_post($item['collection_id']);
            $item['collection_title'] = $collection ? $collection->post_title : 'N/A';
        } else {
            $item['collection_title'] = 'N/A';
        }
        
        if ($item['user_id']) {
            $user = get_user_by('id', $item['user_id']);
            $item['user_name'] = $user ? $user->display_name : 'N/A';
        } else {
            $item['user_name'] = 'N/A';
        }
        
        $item['started_at_formatted'] = date_i18n('d/m/Y H:i', strtotime($item['started_at']));
        if ($item['completed_at']) {
            $item['completed_at_formatted'] = date_i18n('d/m/Y H:i', strtotime($item['completed_at']));
            $item['duration'] = human_time_diff(strtotime($item['started_at']), strtotime($item['completed_at']));
        }
    }
    
    return $result;
}

function acervox_api_get_import_details($request) {
    if (!class_exists('AcervoX_Import_History')) {
        return new WP_Error('class_not_found', 'Classe não encontrada', ['status' => 500]);
    }
    
    $id = absint($request->get_param('id'));
    
    if (!$id) {
        return new WP_Error('invalid_id', 'ID inválido', ['status' => 400]);
    }
    
    $item = AcervoX_Import_History::get($id);
    
    if (!$item) {
        return new WP_Error('not_found', 'Importação não encontrada', ['status' => 404]);
    }
    
    // Formatar dados
    if ($item['collection_id']) {
        $collection = get_post($item['collection_id']);
        $item['collection_title'] = $collection ? $collection->post_title : 'N/A';
    } else {
        $item['collection_title'] = 'N/A';
    }
    
    if ($item['user_id']) {
        $user = get_user_by('id', $item['user_id']);
        $item['user_name'] = $user ? $user->display_name : 'N/A';
    } else {
        $item['user_name'] = 'N/A';
    }
    
    $item['started_at_formatted'] = date_i18n('d/m/Y H:i:s', strtotime($item['started_at']));
    if ($item['completed_at']) {
        $item['completed_at_formatted'] = date_i18n('d/m/Y H:i:s', strtotime($item['completed_at']));
        $item['duration'] = human_time_diff(strtotime($item['started_at']), strtotime($item['completed_at']));
    }
    
    return $item;
}

function acervox_api_delete_import_history($request) {
    if (!class_exists('AcervoX_Import_History')) {
        return new WP_Error('class_not_found', 'Classe não encontrada', ['status' => 500]);
    }
    
    $id = absint($request->get_param('id'));
    
    if (!$id) {
        return new WP_Error('invalid_id', 'ID inválido', ['status' => 400]);
    }
    
    $deleted = AcervoX_Import_History::delete($id);
    
    if ($deleted) {
        return [
            'success' => true,
            'message' => 'Histórico deletado com sucesso'
        ];
    } else {
        return new WP_Error('delete_failed', 'Falha ao deletar histórico', ['status' => 500]);
    }
}
