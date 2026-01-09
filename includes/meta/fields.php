<?php
if (!defined('ABSPATH')) exit;

function acervox_render_dynamic_fields($post) {

    $collection_id = get_post_meta($post->ID, '_acervox_collection', true);
    if (!$collection_id) return;

    $fields = AcervoX_Meta_Registry::get_fields($collection_id);
    if (empty($fields)) return;

    echo '<div class="acervox-dynamic-fields">';

    foreach ($fields as $field) {
        $value = get_post_meta($post->ID, '_acervox_' . $field['key'], true);
        $field_name = 'acervox_' . esc_attr($field['key']);

        echo '<div class="acervox-field" style="margin-bottom: 15px;">';
        echo '<label for="' . $field_name . '" style="display: block; margin-bottom: 5px; font-weight: 600;">' . esc_html($field['label']) . '</label>';

        switch ($field['type']) {
            case 'text':
                echo '<input type="text" id="' . $field_name . '" name="' . $field_name . '" value="' . esc_attr($value) . '" style="width: 100%; padding: 8px;">';
                break;

            case 'number':
                echo '<input type="number" id="' . $field_name . '" name="' . $field_name . '" value="' . esc_attr($value) . '" style="width: 100%; padding: 8px;">';
                break;

            case 'select':
                echo '<select id="' . $field_name . '" name="' . $field_name . '" style="width: 100%; padding: 8px;">';
                if (isset($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $opt) {
                        echo '<option value="' . esc_attr($opt) . '" ' . selected($value, $opt, false) . '>' . esc_html($opt) . '</option>';
                    }
                }
                echo '</select>';
                break;

            default:
                echo '<input type="text" id="' . $field_name . '" name="' . $field_name . '" value="' . esc_attr($value) . '" style="width: 100%; padding: 8px;">';
                break;
        }

        echo '</div>';
    }

    echo '</div>';
}
