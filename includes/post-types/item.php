<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_type('acervox_item', [
        'label' => 'Itens do Acervo',
        'labels' => [
            'name' => 'Itens do Acervo',
            'singular_name' => 'Item do Acervo',
            'add_new' => 'Adicionar Novo',
            'add_new_item' => 'Adicionar Novo Item',
            'edit_item' => 'Editar Item',
            'new_item' => 'Novo Item',
            'view_item' => 'Ver Item',
            'search_items' => 'Buscar Itens',
            'not_found' => 'Nenhum item encontrado',
            'not_found_in_trash' => 'Nenhum item encontrado na lixeira',
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'acervo'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
});
