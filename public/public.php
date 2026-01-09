<?php
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {

    wp_register_style(
        'acervox-front',
        ACERVOX_URL . 'public/assets/css/acervo.css',
        [],
        '0.1.0'
    );

    wp_register_script(
        'acervox-front',
        ACERVOX_URL . 'public/assets/js/acervo.js',
        [],
        '0.1.0',
        true
    );

    wp_localize_script('acervox-front', 'AcervoXFront', [
        'api' => rest_url('acervox/v1'),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
});
