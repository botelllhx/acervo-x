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
    // Campo de seleção de coleção (sempre visível)
    $collection_id = get_post_meta($post->ID, '_acervox_collection', true);
    $collections = get_posts([
        'post_type' => 'acervox_collection',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    ?>
    <div style="margin-bottom: 20px;">
        <label for="acervox_collection" style="display: block; margin-bottom: 8px; font-weight: 600;">
            Coleção <span style="color: #d63638;">*</span>
        </label>
        <select name="acervox_collection" id="acervox_collection" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">-- Selecione uma coleção --</option>
            <?php foreach ($collections as $collection) : ?>
                <option value="<?php echo esc_attr($collection->ID); ?>" <?php selected($collection_id, $collection->ID); ?>>
                    <?php echo esc_html($collection->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description" style="margin-top: 8px; font-size: 13px; color: #646970;">
            Selecione a coleção à qual este item pertence. Os campos de metadados serão atualizados automaticamente.
        </p>
        <?php if ($collection_id) : ?>
            <div style="margin-top: 12px; padding: 10px; background: #d1f4e0; border-left: 4px solid #00a32a; border-radius: 4px;">
                <strong style="color: #00a32a;">✓ Item vinculado à coleção:</strong>
                <span style="color: #1e4620;"><?php echo esc_html(get_the_title($collection_id)); ?></span>
            </div>
        <?php else : ?>
            <div style="margin-top: 12px; padding: 10px; background: #fcf0f1; border-left: 4px solid #d63638; border-radius: 4px;">
                <strong style="color: #d63638;">⚠ Atenção:</strong>
                <span style="color: #721c24;">Este item não está vinculado a nenhuma coleção.</span>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    
    // Renderiza campos dinâmicos se houver coleção associada
    if ($collection_id) {
        acervox_render_dynamic_fields($post);
    } else {
        // Campo padrão se não houver coleção
        $autor = get_post_meta($post->ID, '_acervox_autor', true);
        ?>
        <div style="margin-bottom: 20px;">
            <label for="acervox_autor" style="display: block; margin-bottom: 8px; font-weight: 600;">Autor</label>
            <input type="text" id="acervox_autor" name="acervox_autor" value="<?php echo esc_attr($autor); ?>" style="width:100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <p class="description" style="margin-top: 8px; font-size: 13px; color: #646970;">
                Este campo só será usado se o item não estiver vinculado a uma coleção.
            </p>
        </div>
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

    // Salvar/atualizar coleção vinculada
    if (isset($_POST['acervox_collection'])) {
        $new_collection_id = !empty($_POST['acervox_collection']) ? absint($_POST['acervox_collection']) : '';
        
        // Verificar se a coleção existe
        if ($new_collection_id) {
            $collection_post = get_post($new_collection_id);
            if ($collection_post && $collection_post->post_type === 'acervox_collection') {
                update_post_meta($post_id, '_acervox_collection', $new_collection_id);
            }
        } else {
            // Remover vínculo se estiver vazio
            delete_post_meta($post_id, '_acervox_collection');
        }
    }

    // Obter a coleção atual (após atualização)
    $collection_id = get_post_meta($post_id, '_acervox_collection', true);

    // Salvar campo autor padrão (apenas se não houver coleção)
    if (!$collection_id && isset($_POST['acervox_autor'])) {
        update_post_meta($post_id, '_acervox_autor', sanitize_text_field($_POST['acervox_autor']));
    } elseif ($collection_id) {
        // Limpar autor se houver coleção
        delete_post_meta($post_id, '_acervox_autor');
    }

    // Salvar campos dinâmicos da coleção
    if ($collection_id && class_exists('AcervoX_Meta_Registry')) {
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
