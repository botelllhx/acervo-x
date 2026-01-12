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
    
    // Metadados padrão
    $default_description = get_post_meta($post->ID, '_acervox_default_description', true);
    
    ?>
    <div style="margin-bottom: 24px; padding: 16px; background: #f0f6fc; border: 1px solid #c3d4e6; border-radius: 6px;">
        <h3 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #1d2327; text-transform: uppercase; letter-spacing: 0.5px;">
            📌 Metadados Padrão (Obrigatórios)
        </h3>
        
        <div style="margin-bottom: 16px;">
            <label for="acervox_collection" style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327;">
                Coleção <span style="color: #d63638;">*</span>
            </label>
            <select name="acervox_collection" id="acervox_collection" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; background: white;">
                <option value="">-- Selecione uma coleção --</option>
                <?php foreach ($collections as $collection) : ?>
                    <option value="<?php echo esc_attr($collection->ID); ?>" <?php selected($collection_id, $collection->ID); ?>>
                        <?php echo esc_html($collection->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description" style="margin-top: 8px; font-size: 13px; color: #646970;">
                A coleção à qual este item pertence. Este é um campo obrigatório e não pode ser removido.
            </p>
            <?php if ($collection_id) : ?>
                <div style="margin-top: 12px; padding: 10px; background: #d1f4e0; border-left: 4px solid #00a32a; border-radius: 4px;">
                    <strong style="color: #00a32a;">✓ Item vinculado à coleção:</strong>
                    <span style="color: #1e4620;"><?php echo esc_html(get_the_title($collection_id)); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327;">
                Título <span style="color: #d63638;">*</span>
            </label>
            <input 
                type="text" 
                value="<?php echo esc_attr($post->post_title); ?>" 
                readonly 
                style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; background: #f6f7f7; color: #646970; cursor: not-allowed;"
            />
            <p class="description" style="margin-top: 8px; font-size: 13px; color: #646970;">
                O título do item (puxado automaticamente do título do post). Este campo é obrigatório e não pode ser removido.
            </p>
        </div>
        
        <div>
            <label for="acervox_default_description" style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327;">
                Descrição <span style="color: #d63638;">*</span>
            </label>
            <textarea 
                name="acervox_default_description" 
                id="acervox_default_description" 
                rows="4" 
                style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; font-family: inherit; resize: vertical;"
            ><?php echo esc_textarea($default_description); ?></textarea>
            <p class="description" style="margin-top: 8px; font-size: 13px; color: #646970;">
                Descrição do item. Este campo é obrigatório e não pode ser removido.
            </p>
        </div>
    </div>
    
    <?php
    
    // Renderiza campos dinâmicos se houver coleção associada
    if ($collection_id) {
        ?>
        <div style="margin-top: 24px; padding: 16px; background: #fff; border: 1px solid #c3d4e6; border-radius: 6px;">
            <h3 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #1d2327; text-transform: uppercase; letter-spacing: 0.5px;">
                ⚙️ Metadados Personalizados
            </h3>
            <?php acervox_render_dynamic_fields($post); ?>
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

    // Salvar/atualizar coleção vinculada (metadado padrão)
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

    // Salvar descrição padrão (metadado padrão)
    if (isset($_POST['acervox_default_description'])) {
        update_post_meta($post_id, '_acervox_default_description', sanitize_textarea_field($_POST['acervox_default_description']));
    }

    // Obter a coleção atual (após atualização)
    $collection_id = get_post_meta($post_id, '_acervox_collection', true);

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
