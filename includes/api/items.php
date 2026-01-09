<?php
if (!defined('ABSPATH')) exit;

/**
 * API REST completa para Itens do AcervoX
 * FASE 1 - Coração do sistema
 */
add_action('rest_api_init', function () {
    register_rest_route('acervox/v1', '/items', [
        'methods' => 'GET',
        'callback' => 'acervox_api_get_items_advanced',
        'permission_callback' => '__return_true',
        'args' => [
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'default' => 12,
                'sanitize_callback' => 'absint',
            ],
            'collection' => [
                'sanitize_callback' => 'absint',
            ],
            'taxonomy' => [
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'term' => [
                'sanitize_callback' => 'absint',
            ],
            'search' => [
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'meta_key' => [
                'sanitize_callback' => 'sanitize_key',
            ],
            'meta_value' => [
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'orderby' => [
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order' => [
                'default' => 'DESC',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]);
});

function acervox_api_get_items_advanced($request) {
    $args = [
        'post_type' => 'acervox_item',
        'post_status' => 'publish',
        'posts_per_page' => $request->get_param('per_page'),
        'paged' => $request->get_param('page'),
        'orderby' => $request->get_param('orderby'),
        'order' => $request->get_param('order'),
    ];

    // Filtro por coleção
    if ($request->get_param('collection')) {
        $args['meta_query'][] = [
            'key' => '_acervox_collection',
            'value' => $request->get_param('collection'),
            'compare' => '='
        ];
    }

    // Filtro por taxonomia
    if ($request->get_param('taxonomy') && $request->get_param('term')) {
        $args['tax_query'][] = [
            'taxonomy' => sanitize_key($request->get_param('taxonomy')),
            'field' => 'term_id',
            'terms' => $request->get_param('term'),
        ];
    }

    // Busca por texto
    if ($request->get_param('search')) {
        $args['s'] = $request->get_param('search');
    }

    // Filtro por metadado
    if ($request->get_param('meta_key') && $request->get_param('meta_value')) {
        $args['meta_query'][] = [
            'key' => '_acervox_' . $request->get_param('meta_key'),
            'value' => $request->get_param('meta_value'),
            'compare' => 'LIKE'
        ];
    }

    // Relacionar meta_query
    if (!empty($args['meta_query']) && count($args['meta_query']) > 1) {
        $args['meta_query']['relation'] = 'AND';
    }

    $query = new WP_Query($args);
    $items = [];

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $collection_id = get_post_meta($post_id, '_acervox_collection', true);
        
        // Thumbnail em múltiplos tamanhos
        $thumbnails = [
            'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
            'medium' => get_the_post_thumbnail_url($post_id, 'medium'),
            'large' => get_the_post_thumbnail_url($post_id, 'large'),
            'full' => get_the_post_thumbnail_url($post_id, 'full'),
        ];

        $item = [
            'id' => $post_id,
            'title' => get_the_title(),
            'excerpt' => get_the_excerpt(),
            'content' => get_the_content(),
            'permalink' => get_permalink(),
            'thumbnails' => $thumbnails,
            'collection_id' => $collection_id ? absint($collection_id) : null,
            'date' => get_the_date('c'),
            'modified' => get_the_modified_date('c'),
        ];

        // Taxonomias
        $taxonomies = get_object_taxonomies('acervox_item');
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post_id, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                $item['taxonomies'][$taxonomy] = array_map(function($term) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }, $terms);
            }
        }

        // Metadados da coleção
        if ($collection_id && class_exists('AcervoX_Meta_Registry')) {
            $fields = AcervoX_Meta_Registry::get_fields($collection_id);
            if (!empty($fields) && is_array($fields)) {
                $meta = [];
                foreach ($fields as $field) {
                    if (isset($field['key'])) {
                        $value = get_post_meta($post_id, '_acervox_' . $field['key'], true);
                        if ($value) {
                            $meta[$field['key']] = [
                                'label' => $field['label'] ?? $field['key'],
                                'type' => $field['type'] ?? 'text',
                                'value' => $value,
                            ];
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
        'current_page' => $request->get_param('page'),
        'per_page' => $request->get_param('per_page'),
    ];
}
