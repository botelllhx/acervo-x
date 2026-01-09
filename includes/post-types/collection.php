<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_type('acervox_collection', [
        'label' => 'Coleções',
        'labels' => [
            'name' => 'Coleções',
            'singular_name' => 'Coleção',
            'add_new' => 'Adicionar Nova',
            'add_new_item' => 'Adicionar Nova Coleção',
            'edit_item' => 'Editar Coleção',
            'new_item' => 'Nova Coleção',
            'view_item' => 'Ver Coleção',
            'search_items' => 'Buscar Coleções',
            'not_found' => 'Nenhuma coleção encontrada',
            'not_found_in_trash' => 'Nenhuma coleção encontrada na lixeira',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-archive',
        'supports' => ['title', 'editor'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
});
