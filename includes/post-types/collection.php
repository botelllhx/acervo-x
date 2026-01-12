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

/**
 * Deletar todos os itens quando uma coleção é deletada
 */
add_action('before_delete_post', function($post_id) {
    $post = get_post($post_id);
    
    // Verificar se é uma coleção
    if ($post && $post->post_type === 'acervox_collection') {
        // Buscar todos os itens vinculados a esta coleção
        $items = get_posts([
            'post_type' => 'acervox_item',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_acervox_collection',
                    'value' => $post_id,
                    'compare' => '='
                ]
            ],
            'post_status' => 'any'
        ]);
        
        // Deletar cada item
        foreach ($items as $item) {
            // Deletar também os anexos (imagens) do item
            $attachments = get_attached_media('', $item->ID);
            foreach ($attachments as $attachment) {
                wp_delete_attachment($attachment->ID, true);
            }
            
            // Deletar o item permanentemente
            wp_delete_post($item->ID, true);
        }
    }
});
