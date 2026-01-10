<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode Avançado do AcervoX
 * [acervox id="1"] ou [acervox collection="1" per_page="12" layout="grid"]
 */
add_shortcode('acervox', function ($atts) {
    $atts = shortcode_atts([
        'id' => '',
        'collection' => '',
        'per_page' => '12',
        'layout' => 'grid', // grid, masonry, list
        'filters' => 'true',
        'pagination' => 'true',
        'columns' => '3',
        'show_excerpt' => 'true',
        'show_meta' => 'true',
    ], $atts, 'acervox');

    wp_enqueue_script('acervox-front');
    wp_enqueue_style('acervox-front');

    // Se tiver ID, carregar configuração salva
    if (!empty($atts['id'])) {
        $saved_configs = get_option('acervox_shortcodes', []);
        if (is_array($saved_configs)) {
            $found = array_filter($saved_configs, function($s) use ($atts) {
                return isset($s['id']) && $s['id'] == $atts['id'];
            });
            if (!empty($found)) {
                $saved = reset($found);
                $atts = array_merge($atts, $saved);
            }
        }
    }

    $container_id = 'acervox-' . uniqid();
    
    $data_atts = [
        'collection' => !empty($atts['collection']) ? absint($atts['collection']) : null,
        'per_page' => absint($atts['per_page']),
        'layout' => sanitize_text_field($atts['layout']),
        'filters' => $atts['filters'] === 'true' || $atts['filters'] === true,
        'pagination' => $atts['pagination'] === 'true' || $atts['pagination'] === true,
        'columns' => absint($atts['columns']),
        'show_excerpt' => $atts['show_excerpt'] === 'true' || $atts['show_excerpt'] === true,
        'show_meta' => $atts['show_meta'] === 'true' || $atts['show_meta'] === true,
    ];

    $json_config = json_encode($data_atts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    return sprintf(
        '<div id="%s" class="acervox-shortcode" data-config="%s"></div>',
        esc_attr($container_id),
        esc_attr($json_config)
    );
});
