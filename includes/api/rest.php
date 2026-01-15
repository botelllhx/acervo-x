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

    // Endpoint para salvar campos de metadados de uma coleção
    register_rest_route('acervox/v1', '/collections/(?P<id>\d+)/fields', [
        'methods' => 'POST',
        'callback' => 'acervox_api_save_collection_fields',
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

    // Endpoints de Galeria
    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/gallery', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_item_gallery',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);

    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/gallery', [
        'methods' => 'POST',
        'callback' => 'acervox_api_add_to_gallery',
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

    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/gallery/(?P<attachment_id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'acervox_api_remove_from_gallery',
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
            'attachment_id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);

    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/gallery/order', [
        'methods' => 'POST',
        'callback' => 'acervox_api_update_gallery_order',
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

    // Endpoints de Tags e Categorias
    register_rest_route('acervox/v1', '/tags', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_tags',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('acervox/v1', '/categories', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_categories',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/tags', [
        'methods' => 'POST',
        'callback' => 'acervox_api_update_item_tags',
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

    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/categories', [
        'methods' => 'POST',
        'callback' => 'acervox_api_update_item_categories',
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

    // Endpoints de Edição em Massa
    register_rest_route('acervox/v1', '/items/bulk-update', [
        'methods' => 'POST',
        'callback' => 'acervox_api_bulk_update_items',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        }
    ]);

    register_rest_route('acervox/v1', '/items/(?P<id>\d+)/duplicate', [
        'methods' => 'POST',
        'callback' => 'acervox_api_duplicate_item',
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

    // Endpoints de exportação
    register_rest_route('acervox/v1', '/export/(?P<format>csv|json|xml)', [
        'methods' => 'POST',
        'callback' => 'acervox_api_export',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'args' => [
            'format' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return in_array($param, ['csv', 'json', 'xml']);
                }
            ]
        ]
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

function acervox_api_save_collection_fields($request) {
    $collection_id = absint($request->get_param('id'));
    $body = $request->get_json_params();
    $fields = $body['fields'] ?? [];
    
    // Verificar se a coleção existe
    $collection = get_post($collection_id);
    if (!$collection || $collection->post_type !== 'acervox_collection') {
        return new WP_Error('collection_not_found', 'Coleção não encontrada', ['status' => 404]);
    }
    
    // Validar campos
    if (!is_array($fields)) {
        return new WP_Error('invalid_fields', 'Campos devem ser um array', ['status' => 400]);
    }
    
    // Sanitizar campos
    $sanitized_fields = [];
    foreach ($fields as $field) {
        // Pular campos sem label
        if (empty($field['label'])) {
            continue;
        }
        
        // Gerar key se não existir
        $key = !empty($field['key']) ? $field['key'] : sanitize_key(sanitize_title($field['label']));
        $key = str_replace(['-', '_'], '', $key); // Remover hífens e underscores para consistência
        
        $sanitized_fields[] = [
            'label' => sanitize_text_field($field['label']),
            'key' => sanitize_key($key),
            'type' => in_array($field['type'] ?? 'text', ['text', 'textarea', 'number', 'date', 'select']) 
                ? $field['type'] 
                : 'text',
            'required' => !empty($field['required']),
            'options' => isset($field['options']) && is_array($field['options']) 
                ? array_map('sanitize_text_field', $field['options']) 
                : []
        ];
    }
    
    // Salvar usando o registry
    if (class_exists('AcervoX_Meta_Registry')) {
        $saved = AcervoX_Meta_Registry::save_fields($collection_id, $sanitized_fields);
        
        if ($saved) {
            // Aguardar um pouco para garantir que o banco processou
            usleep(100000); // 0.1 segundo
            
            // Limpar cache do WordPress
            clean_post_cache($collection_id);
            wp_cache_delete($collection_id, 'post_meta');
            
            // Verificar se foi salvo corretamente - ler de volta do banco
            $saved_fields = AcervoX_Meta_Registry::get_fields($collection_id);
            
            return [
                'success' => true,
                'message' => 'Campos salvos com sucesso',
                'fields' => $saved_fields,
                'saved_count' => count($saved_fields)
            ];
        } else {
            // Tentar diagnosticar o problema
            $raw_meta = get_post_meta($collection_id, '_acervox_fields', true);
            return new WP_Error('save_failed', 'Falha ao salvar campos no banco de dados', ['status' => 500, 'debug' => [
                'collection_id' => $collection_id,
                'meta_exists' => !empty($raw_meta)
            ]]);
        }
    }
    
    return new WP_Error('registry_not_found', 'Sistema de metadados não encontrado', ['status' => 500]);
}

function acervox_api_get_external_collections($request) {
    if (!class_exists('AcervoX_External_Importer')) {
        return new WP_Error('class_not_found', 'Classe não encontrada', ['status' => 500]);
    }

    if (!AcervoX_External_Importer::is_external_active()) {
        return [
            'collections' => [],
            'active' => false,
            'message' => 'Sistema externo não está ativo'
        ];
    }

    $collections = AcervoX_External_Importer::get_collections();
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
    if (!class_exists('AcervoX_External_Importer')) {
        return new WP_Error('class_not_found', 'Classe AcervoX_External_Importer não encontrada', ['status' => 500]);
    }

    if (!AcervoX_External_Importer::is_external_active()) {
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

    if (!class_exists('AcervoX_External_Mapper') || !class_exists('AcervoX_Meta_Registry')) {
        return new WP_Error('classes_not_found', 'Classes necessárias não encontradas', ['status' => 500]);
    }

    $fields = AcervoX_External_Mapper::map_fields($collection_id);
    if (!empty($fields)) {
        AcervoX_Meta_Registry::save_fields($acervox_collection, $fields);
    }

    $start_time = current_time('mysql');
    
    $import_result = AcervoX_External_Importer::import_items($collection_id, $acervox_collection);

    $log = [];
    if (class_exists('AcervoX_Import_Logger')) {
        $log = AcervoX_Import_Logger::get();
    }
    
    // Contar itens importados
    $imported_count = 0;
    $failed_count = 0;
    foreach ($log as $log_entry) {
        if (strpos($log_entry, 'importado') !== false || strpos($log_entry, 'criado') !== false) {
            $imported_count++;
        } elseif (strpos($log_entry, 'Erro') !== false) {
            $failed_count++;
        }
    }
    
    // Salvar no histórico
    if (class_exists('AcervoX_Import_History')) {
        AcervoX_Import_History::add([
            'import_type' => 'external',
            'collection_id' => $acervox_collection,
            'total_items' => $imported_count + $failed_count,
            'imported_items' => $imported_count,
            'failed_items' => $failed_count,
            'status' => $import_result ? 'completed' : 'failed',
            'started_at' => $start_time,
            'completed_at' => current_time('mysql'),
            'error_message' => !$import_result ? 'Falha na importação' : null,
            'log_data' => $log
        ]);
    }

    return [
        'status' => 'ok',
        'collection_id' => $acervox_collection,
        'log' => $log,
        'imported_count' => $imported_count,
        'failed_count' => $failed_count
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
    
    // Garantir que processed seja o total processado até agora
    if (!isset($result['processed']) || $result['processed'] < ($offset + $result['imported'])) {
        $result['processed'] = $offset + $result['imported'];
    }
    
    // Se terminou, limpar arquivo temporário e salvar no histórico
    if ($result['processed'] >= $session_data['total_rows']) {
        @unlink($session_data['file']);
        delete_transient($session_key);
        $result['completed'] = true;
        
        // Salvar no histórico
        if (class_exists('AcervoX_Import_History')) {
            // Calcular corretamente os itens importados e falhados
            $total_imported = $result['processed'];
            $total_errors = isset($result['errors']) ? $result['errors'] : 0;
            
            AcervoX_Import_History::add([
                'import_type' => 'csv',
                'collection_id' => $session_data['collection_id'],
                'total_items' => $session_data['total_rows'],
                'imported_items' => max(0, $total_imported - $total_errors),
                'failed_items' => $total_errors,
                'status' => ($total_errors > 0 && $total_errors >= $total_imported) ? 'failed' : 'completed',
                'started_at' => current_time('mysql'), // Usar started_at também
                'completed_at' => current_time('mysql'),
                'log_data' => array_merge($result['log'] ?? [], $result['errors_list'] ?? [])
            ]);
        }
    }
    
    return $result;
}

function acervox_api_export($request) {
    if (!class_exists('AcervoX_Exporter')) {
        return new WP_Error('exporter_not_found', 'Sistema de exportação não encontrado', ['status' => 500]);
    }
    
    $format = $request->get_param('format');
    $body = $request->get_json_params();
    $collection_id = isset($body['collection_id']) ? absint($body['collection_id']) : 0;
    $item_ids = isset($body['item_ids']) && is_array($body['item_ids']) 
        ? array_map('absint', $body['item_ids']) 
        : [];
    
    if (!$collection_id && empty($item_ids)) {
        return new WP_Error('missing_collection', 'ID da coleção ou IDs de itens são obrigatórios', ['status' => 400]);
    }
    
    // Se não tem collection_id mas tem item_ids, buscar collection_id do primeiro item
    if (!$collection_id && !empty($item_ids)) {
        $first_item = get_post($item_ids[0]);
        if ($first_item) {
            $collection_id = get_post_meta($first_item->ID, '_acervox_collection', true);
        }
    }
    
    if (!$collection_id) {
        return new WP_Error('invalid_collection', 'Coleção inválida', ['status' => 400]);
    }
    
    // Exportar baseado no formato
    switch ($format) {
        case 'csv':
            $result = AcervoX_Exporter::export_csv($collection_id, $item_ids);
            break;
        case 'json':
            $result = AcervoX_Exporter::export_json($collection_id, $item_ids);
            break;
        case 'xml':
            $result = AcervoX_Exporter::export_xml($collection_id, $item_ids);
            break;
        default:
            return new WP_Error('invalid_format', 'Formato inválido. Use: csv, json ou xml', ['status' => 400]);
    }
    
    if (is_wp_error($result)) {
        return $result;
    }
    
    return [
        'success' => true,
        'file_url' => $result['file_url'],
        'file_path' => $result['file_path'],
        'total_items' => $result['total_items'],
        'format' => $result['format']
    ];
}

function acervox_api_get_import_history($request) {
    if (!class_exists('AcervoX_Import_History')) {
        // Tentar inicializar se não estiver inicializado
        AcervoX_Import_History::init();
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
    
    // Remover valores vazios
    $args = array_filter($args, function($value) {
        return $value !== '' && $value !== null;
    });
    
    // Remover valores vazios/null dos args
    $clean_args = [];
    foreach ($args as $key => $value) {
        if ($value !== '' && $value !== null) {
            $clean_args[$key] = $value;
        }
    }
    
    $result = AcervoX_Import_History::get_all($clean_args);
    
    // Garantir que items existe
    if (!isset($result['items'])) {
        $result['items'] = [];
    }
    
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

// Endpoints de Galeria
function acervox_api_get_item_gallery($request) {
    if (!class_exists('AcervoX_Gallery')) {
        return new WP_Error('gallery_not_found', 'Sistema de galeria não encontrado', ['status' => 500]);
    }
    
    $item_id = absint($request->get_param('id'));
    $item = get_post($item_id);
    
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    $gallery = AcervoX_Gallery::get_formatted_gallery($item_id);
    
    return [
        'success' => true,
        'gallery' => $gallery,
        'total' => count($gallery)
    ];
}

function acervox_api_add_to_gallery($request) {
    if (!class_exists('AcervoX_Gallery')) {
        return new WP_Error('gallery_not_found', 'Sistema de galeria não encontrado', ['status' => 500]);
    }
    
    $item_id = absint($request->get_param('id'));
    $body = $request->get_json_params();
    $attachment_id = isset($body['attachment_id']) ? absint($body['attachment_id']) : 0;
    $type = isset($body['type']) ? sanitize_text_field($body['type']) : 'image';
    
    if (!$attachment_id) {
        return new WP_Error('missing_attachment', 'ID do anexo é obrigatório', ['status' => 400]);
    }
    
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    $attachment = get_post($attachment_id);
    if (!$attachment) {
        return new WP_Error('attachment_not_found', 'Anexo não encontrado', ['status' => 404]);
    }
    
    $result = AcervoX_Gallery::add_media($item_id, $attachment_id, $type);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Mídia adicionada à galeria com sucesso',
            'gallery' => AcervoX_Gallery::get_formatted_gallery($item_id)
        ];
    } else {
        return new WP_Error('add_failed', 'Falha ao adicionar mídia à galeria', ['status' => 500]);
    }
}

function acervox_api_remove_from_gallery($request) {
    if (!class_exists('AcervoX_Gallery')) {
        return new WP_Error('gallery_not_found', 'Sistema de galeria não encontrado', ['status' => 500]);
    }
    
    $item_id = absint($request->get_param('id'));
    $attachment_id = absint($request->get_param('attachment_id'));
    
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    $result = AcervoX_Gallery::remove_media($item_id, $attachment_id);
    
    if ($result !== false) {
        return [
            'success' => true,
            'message' => 'Mídia removida da galeria com sucesso',
            'gallery' => AcervoX_Gallery::get_formatted_gallery($item_id)
        ];
    } else {
        return new WP_Error('remove_failed', 'Falha ao remover mídia da galeria', ['status' => 500]);
    }
}

function acervox_api_update_gallery_order($request) {
    if (!class_exists('AcervoX_Gallery')) {
        return new WP_Error('gallery_not_found', 'Sistema de galeria não encontrado', ['status' => 500]);
    }
    
    $item_id = absint($request->get_param('id'));
    $body = $request->get_json_params();
    $attachment_ids = isset($body['attachment_ids']) && is_array($body['attachment_ids'])
        ? array_map('absint', $body['attachment_ids'])
        : [];
    
    if (empty($attachment_ids)) {
        return new WP_Error('missing_ids', 'IDs dos anexos são obrigatórios', ['status' => 400]);
    }
    
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    $result = AcervoX_Gallery::update_order($item_id, $attachment_ids);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Ordem da galeria atualizada com sucesso',
            'gallery' => AcervoX_Gallery::get_formatted_gallery($item_id)
        ];
    } else {
        return new WP_Error('update_failed', 'Falha ao atualizar ordem da galeria', ['status' => 500]);
    }
}

// Endpoints de Tags e Categorias
function acervox_api_get_tags($request) {
    $tags = get_terms([
        'taxonomy' => 'acervox_tag',
        'hide_empty' => false,
        'number' => $request->get_param('per_page') ?: 100
    ]);
    
    if (is_wp_error($tags)) {
        return [];
    }
    
    return array_map(function($tag) {
        return [
            'id' => $tag->term_id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'count' => $tag->count
        ];
    }, $tags);
}

function acervox_api_get_categories($request) {
    $categories = get_terms([
        'taxonomy' => 'acervox_category',
        'hide_empty' => false,
        'number' => $request->get_param('per_page') ?: 100
    ]);
    
    if (is_wp_error($categories)) {
        return [];
    }
    
    return array_map(function($cat) {
        return [
            'id' => $cat->term_id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'count' => $cat->count,
            'parent' => $cat->parent
        ];
    }, $categories);
}

function acervox_api_update_item_tags($request) {
    $item_id = absint($request->get_param('id'));
    $body = $request->get_json_params();
    $tags = isset($body['tags']) ? $body['tags'] : [];
    
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    // Converter nomes de tags para IDs
    $tag_ids = [];
    foreach ($tags as $tag) {
        if (is_numeric($tag)) {
            $tag_ids[] = absint($tag);
        } else {
            // Criar ou buscar tag pelo nome
            $term = wp_insert_term($tag, 'acervox_tag');
            if (!is_wp_error($term)) {
                $tag_ids[] = is_array($term) ? $term['term_id'] : $term;
            } elseif (isset($term->error_data['term_exists'])) {
                $tag_ids[] = $term->error_data['term_exists'];
            }
        }
    }
    
    $result = wp_set_object_terms($item_id, $tag_ids, 'acervox_tag');
    
    if (is_wp_error($result)) {
        return new WP_Error('update_failed', $result->get_error_message(), ['status' => 500]);
    }
    
    return [
        'success' => true,
        'message' => 'Tags atualizadas com sucesso',
        'tags' => $tag_ids
    ];
}

function acervox_api_update_item_categories($request) {
    $item_id = absint($request->get_param('id'));
    $body = $request->get_json_params();
    $categories = isset($body['categories']) ? $body['categories'] : [];
    
    $item = get_post($item_id);
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    // Converter nomes de categorias para IDs
    $cat_ids = [];
    foreach ($categories as $cat) {
        if (is_numeric($cat)) {
            $cat_ids[] = absint($cat);
        } else {
            // Criar ou buscar categoria pelo nome
            $term = wp_insert_term($cat, 'acervox_category');
            if (!is_wp_error($term)) {
                $cat_ids[] = is_array($term) ? $term['term_id'] : $term;
            } elseif (isset($term->error_data['term_exists'])) {
                $cat_ids[] = $term->error_data['term_exists'];
            }
        }
    }
    
    $result = wp_set_object_terms($item_id, $cat_ids, 'acervox_category');
    
    if (is_wp_error($result)) {
        return new WP_Error('update_failed', $result->get_error_message(), ['status' => 500]);
    }
    
    return [
        'success' => true,
        'message' => 'Categorias atualizadas com sucesso',
        'categories' => $cat_ids
    ];
}

// Edição em Massa
function acervox_api_bulk_update_items($request) {
    $body = $request->get_json_params();
    $item_ids = isset($body['item_ids']) && is_array($body['item_ids'])
        ? array_map('absint', $body['item_ids'])
        : [];
    $updates = isset($body['updates']) ? $body['updates'] : [];
    
    if (empty($item_ids)) {
        return new WP_Error('missing_ids', 'IDs dos itens são obrigatórios', ['status' => 400]);
    }
    
    $updated = 0;
    $errors = [];
    
    foreach ($item_ids as $item_id) {
        $item = get_post($item_id);
        if (!$item || $item->post_type !== 'acervox_item') {
            $errors[] = "Item {$item_id} não encontrado";
            continue;
        }
        
        $post_data = [];
        
        // Atualizar título
        if (isset($updates['title'])) {
            $post_data['post_title'] = sanitize_text_field($updates['title']);
        }
        
        // Atualizar conteúdo
        if (isset($updates['content'])) {
            $post_data['post_content'] = wp_kses_post($updates['content']);
        }
        
        // Atualizar excerpt
        if (isset($updates['excerpt'])) {
            $post_data['post_excerpt'] = sanitize_textarea_field($updates['excerpt']);
        }
        
        // Atualizar coleção
        if (isset($updates['collection_id'])) {
            $collection_id = absint($updates['collection_id']);
            if ($collection_id > 0) {
                update_post_meta($item_id, '_acervox_collection', $collection_id);
            } else {
                delete_post_meta($item_id, '_acervox_collection');
            }
        }
        
        // Atualizar tags
        if (isset($updates['tags']) && is_array($updates['tags'])) {
            $tag_ids = array_map('absint', $updates['tags']);
            wp_set_object_terms($item_id, $tag_ids, 'acervox_tag');
        }
        
        // Atualizar categorias
        if (isset($updates['categories']) && is_array($updates['categories'])) {
            $cat_ids = array_map('absint', $updates['categories']);
            wp_set_object_terms($item_id, $cat_ids, 'acervox_category');
        }
        
        // Atualizar metadados
        if (isset($updates['meta']) && is_array($updates['meta'])) {
            foreach ($updates['meta'] as $key => $value) {
                update_post_meta($item_id, '_acervox_' . sanitize_key($key), sanitize_text_field($value));
            }
        }
        
        // Atualizar post se houver mudanças
        if (!empty($post_data)) {
            $post_data['ID'] = $item_id;
            $result = wp_update_post($post_data);
            if (is_wp_error($result)) {
                $errors[] = "Erro ao atualizar item {$item_id}: " . $result->get_error_message();
                continue;
            }
        }
        
        $updated++;
    }
    
    return [
        'success' => true,
        'updated' => $updated,
        'total' => count($item_ids),
        'errors' => $errors
    ];
}

function acervox_api_duplicate_item($request) {
    $item_id = absint($request->get_param('id'));
    $item = get_post($item_id);
    
    if (!$item || $item->post_type !== 'acervox_item') {
        return new WP_Error('item_not_found', 'Item não encontrado', ['status' => 404]);
    }
    
    // Criar novo post
    $new_post = [
        'post_title' => $item->post_title . ' (Cópia)',
        'post_content' => $item->post_content,
        'post_excerpt' => $item->post_excerpt,
        'post_status' => 'publish',
        'post_type' => 'acervox_item'
    ];
    
    $new_id = wp_insert_post($new_post);
    
    if (is_wp_error($new_id)) {
        return new WP_Error('duplicate_failed', 'Falha ao duplicar item', ['status' => 500]);
    }
    
    // Copiar metadados
    $collection_id = get_post_meta($item_id, '_acervox_collection', true);
    if ($collection_id) {
        update_post_meta($new_id, '_acervox_collection', $collection_id);
    }
    
    // Copiar metadados personalizados
    $meta_keys = get_post_meta($item_id);
    foreach ($meta_keys as $key => $values) {
        if (strpos($key, '_acervox_') === 0) {
            foreach ($values as $value) {
                add_post_meta($new_id, $key, $value);
            }
        }
    }
    
    // Copiar featured image
    $thumbnail_id = get_post_thumbnail_id($item_id);
    if ($thumbnail_id) {
        set_post_thumbnail($new_id, $thumbnail_id);
    }
    
    // Copiar galeria
    if (class_exists('AcervoX_Gallery')) {
        $gallery = AcervoX_Gallery::get_gallery($item_id);
        foreach ($gallery as $media) {
            AcervoX_Gallery::add_media($new_id, $media['id'], $media['type']);
        }
    }
    
    // Copiar taxonomias
    $taxonomies = get_object_taxonomies('acervox_item');
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($item_id, $taxonomy, ['fields' => 'ids']);
        if (!empty($terms) && !is_wp_error($terms)) {
            wp_set_object_terms($new_id, $terms, $taxonomy);
        }
    }
    
    return [
        'success' => true,
        'message' => 'Item duplicado com sucesso',
        'new_id' => $new_id,
        'permalink' => get_permalink($new_id)
    ];
}
