<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Meta_Registry {

    public static function get_fields($collection_id) {
        $fields = get_post_meta($collection_id, '_acervox_fields', true);
        
        // Se for string JSON, decodificar
        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded ?: [];
            }
        }
        
        // Se for array, retornar direto
        if (is_array($fields)) {
            return $fields;
        }
        
        return [];
    }

    public static function save_fields($collection_id, $fields) {
        // Sempre salvar como JSON string para compatibilidade
        if (is_array($fields)) {
            update_post_meta($collection_id, '_acervox_fields', json_encode($fields));
        } else {
            update_post_meta($collection_id, '_acervox_fields', $fields);
        }
    }
}
