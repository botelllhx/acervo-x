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

    // Endpoint para buscar coleções do Tainacan
    register_rest_route('acervox/v1', '/tainacan/collections', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_tainacan_collections',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    register_rest_route('acervox/v1', '/import/tainacan', [
        'methods' => 'POST',
        'callback' => 'acervox_import_tainacan',
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
        ];
    }

    wp_reset_postdata();

    return [
        'collections' => $collections,
        'total' => $query->found_posts,
    ];
}

function acervox_api_get_tainacan_collections($request) {
    if (!class_exists('AcervoX_Tainacan_Importer')) {
        return new WP_Error('class_not_found', 'Classe não encontrada', ['status' => 500]);
    }

    if (!AcervoX_Tainacan_Importer::is_tainacan_active()) {
        return [
            'collections' => [],
            'active' => false,
            'message' => 'Tainacan não está ativo'
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

function acervox_import_tainacan($request) {
    if (!class_exists('AcervoX_Tainacan_Importer')) {
        return new WP_Error('class_not_found', 'Classe AcervoX_Tainacan_Importer não encontrada', ['status' => 500]);
    }

    if (!AcervoX_Tainacan_Importer::is_tainacan_active()) {
        return new WP_Error('tainacan_not_active', 'Tainacan não está ativo', ['status' => 400]);
    }

    if (!isset($request['tainacan_collection']) || empty($request['tainacan_collection'])) {
        return new WP_Error('missing_collection', 'ID da coleção Tainacan é obrigatório', ['status' => 400]);
    }

    $collection_id = absint($request['tainacan_collection']);
    
    // Buscar título da coleção Tainacan
    $tainacan_collection = get_post($collection_id);
    $collection_title = $tainacan_collection ? $tainacan_collection->post_title : 'Importado do Tainacan';
    
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
