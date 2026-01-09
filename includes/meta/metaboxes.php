<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
    add_meta_box(
        'acervox_meta',
        'Metadados do Item',
        'acervox_render_metabox',
        'acervox_item'
    );
});

function acervox_render_metabox($post) {
    // Renderiza campos dinâmicos se houver coleção associada
    $collection_id = get_post_meta($post->ID, '_acervox_collection', true);
    
    if ($collection_id) {
        acervox_render_dynamic_fields($post);
    } else {
        // Campo padrão se não houver coleção
        $autor = get_post_meta($post->ID, '_acervox_autor', true);
        ?>
        <label>Autor</label>
        <input type="text" name="acervox_autor" value="<?php echo esc_attr($autor); ?>" style="width:100%">
        <?php
    }
    
    // Nonce para segurança
    wp_nonce_field('acervox_meta_box', 'acervox_meta_box_nonce');
}

add_action('save_post', function ($post_id) {
    // Verificar se é o post type correto
    if (get_post_type($post_id) !== 'acervox_item') {
        return;
    }

    // Verificar nonce
    if (!isset($_POST['acervox_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['acervox_meta_box_nonce'], 'acervox_meta_box')) {
        return;
    }

    // Verificar autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verificar permissões
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Salvar campo autor padrão
    if (isset($_POST['acervox_autor'])) {
        update_post_meta($post_id, '_acervox_autor', sanitize_text_field($_POST['acervox_autor']));
    }

    // Salvar campos dinâmicos
    $collection_id = get_post_meta($post_id, '_acervox_collection', true);
    if ($collection_id) {
        $fields = AcervoX_Meta_Registry::get_fields($collection_id);
        if (!empty($fields) && is_array($fields)) {
            foreach ($fields as $field) {
                $key = 'acervox_' . $field['key'];
                if (isset($_POST[$key])) {
                    $value = sanitize_text_field($_POST[$key]);
                    update_post_meta($post_id, '_acervox_' . $field['key'], $value);
                }
            }
        }
    }
});
