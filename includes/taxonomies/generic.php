<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {

    register_taxonomy(
        'acervox_category',
        'acervox_item',
        [
            'label' => 'Categorias do Acervo',
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ]
    );

    register_taxonomy(
        'acervox_tag',
        'acervox_item',
        [
            'label' => 'Tags do Acervo',
            'public' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ]
    );
});
