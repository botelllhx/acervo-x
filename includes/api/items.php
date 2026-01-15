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

    // Busca avançada por texto (título, conteúdo, excerpt e metadados)
    if ($request->get_param('search')) {
        $search_term = $request->get_param('search');
        
        // Busca full-text usando WP_Query
        $args['s'] = $search_term;
        
        // Adicionar busca em metadados também
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = [];
        }
        
        // Buscar em metadados personalizados
        $args['meta_query'][] = [
            'key' => '',
            'value' => $search_term,
            'compare' => 'LIKE'
        ];
        
        // Adicionar busca em taxonomias
        $args['tax_query'] = [
            'relation' => 'OR',
            [
                'taxonomy' => 'acervox_tag',
                'field' => 'name',
                'terms' => $search_term,
                'operator' => 'LIKE'
            ],
            [
                'taxonomy' => 'acervox_category',
                'field' => 'name',
                'terms' => $search_term,
                'operator' => 'LIKE'
            ]
        ];
    }

    // Filtros múltiplos de metadados
    $meta_filters = $request->get_param('meta_filters');
    if ($meta_filters && is_array($meta_filters)) {
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = [];
        }
        
        foreach ($meta_filters as $filter) {
            if (isset($filter['key']) && isset($filter['value'])) {
                $meta_query = [
                    'key' => '_acervox_' . sanitize_key($filter['key']),
                    'value' => sanitize_text_field($filter['value']),
                    'compare' => isset($filter['compare']) ? sanitize_text_field($filter['compare']) : 'LIKE'
                ];
                
                // Suporte a comparações numéricas
                if (isset($filter['type']) && $filter['type'] === 'number') {
                    $meta_query['type'] = 'NUMERIC';
                    if (isset($filter['min'])) {
                        $meta_query['value'] = absint($filter['min']);
                        $meta_query['compare'] = '>=';
                    } elseif (isset($filter['max'])) {
                        $meta_query['value'] = absint($filter['max']);
                        $meta_query['compare'] = '<=';
                    }
                }
                
                $args['meta_query'][] = $meta_query;
            }
        }
    } elseif ($request->get_param('meta_key') && $request->get_param('meta_value')) {
        // Compatibilidade com formato antigo
        $args['meta_query'][] = [
            'key' => '_acervox_' . $request->get_param('meta_key'),
            'value' => $request->get_param('meta_value'),
            'compare' => 'LIKE'
        ];
    }
    
    // Filtro por tags
    if ($request->get_param('tags')) {
        $tags = is_array($request->get_param('tags')) 
            ? array_map('absint', $request->get_param('tags'))
            : [absint($request->get_param('tags'))];
        
        if (!isset($args['tax_query'])) {
            $args['tax_query'] = [];
        }
        
        $args['tax_query'][] = [
            'taxonomy' => 'acervox_tag',
            'field' => 'term_id',
            'terms' => $tags,
            'operator' => 'IN'
        ];
    }
    
    // Filtro por categorias
    if ($request->get_param('categories')) {
        $categories = is_array($request->get_param('categories'))
            ? array_map('absint', $request->get_param('categories'))
            : [absint($request->get_param('categories'))];
        
        if (!isset($args['tax_query'])) {
            $args['tax_query'] = [];
        }
        
        $args['tax_query'][] = [
            'taxonomy' => 'acervox_category',
            'field' => 'term_id',
            'terms' => $categories,
            'operator' => 'IN'
        ];
    }
    
    // Filtro por data (range)
    if ($request->get_param('date_from') || $request->get_param('date_to')) {
        $date_query = [];
        
        if ($request->get_param('date_from')) {
            $date_query['after'] = sanitize_text_field($request->get_param('date_from'));
        }
        
        if ($request->get_param('date_to')) {
            $date_query['before'] = sanitize_text_field($request->get_param('date_to'));
        }
        
        if (!empty($date_query)) {
            $args['date_query'] = [$date_query];
        }
    }

    // Relacionar meta_query e tax_query
    if (!empty($args['meta_query']) && count($args['meta_query']) > 1) {
        $args['meta_query']['relation'] = $request->get_param('meta_relation') === 'OR' ? 'OR' : 'AND';
    }
    
    if (!empty($args['tax_query']) && count($args['tax_query']) > 1) {
        $args['tax_query']['relation'] = $request->get_param('tax_relation') === 'OR' ? 'OR' : 'AND';
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
        
        // Galeria de mídia
        if (class_exists('AcervoX_Gallery')) {
            $item['gallery'] = AcervoX_Gallery::get_formatted_gallery($post_id);
        }

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

        // Metadados padrão (sempre presentes)
        $meta = [];
        
        // Coleção (metadado padrão)
        if ($collection_id) {
            $collection_title = get_the_title($collection_id);
            $meta['collection'] = [
                'label' => 'Coleção',
                'type' => 'collection',
                'value' => $collection_title,
                'collection_id' => $collection_id,
                'is_default' => true,
            ];
        }
        
        // Título (metadado padrão - puxado do título do post)
        $meta['title'] = [
            'label' => 'Título',
            'type' => 'text',
            'value' => get_the_title(),
            'is_default' => true,
        ];
        
        // Descrição (metadado padrão)
        $default_description = get_post_meta($post_id, '_acervox_default_description', true);
        if (!$default_description) {
            $default_description = get_the_excerpt();
        }
        $meta['description'] = [
            'label' => 'Descrição',
            'type' => 'textarea',
            'value' => $default_description,
            'is_default' => true,
        ];
        
        // Metadados personalizados da coleção
        if ($collection_id && class_exists('AcervoX_Meta_Registry')) {
            $fields = AcervoX_Meta_Registry::get_fields($collection_id);
            if (!empty($fields) && is_array($fields)) {
                foreach ($fields as $field) {
                    if (isset($field['key'])) {
                        $value = get_post_meta($post_id, '_acervox_' . $field['key'], true);
                        if ($value) {
                            $meta[$field['key']] = [
                                'label' => $field['label'] ?? $field['key'],
                                'type' => $field['type'] ?? 'text',
                                'value' => $value,
                                'is_default' => false,
                            ];
                        }
                    }
                }
            }
        }
        
        if (!empty($meta)) {
            $item['meta'] = $meta;
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
