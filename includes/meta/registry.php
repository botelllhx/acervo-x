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
        // Validar collection_id
        if (empty($collection_id) || !is_numeric($collection_id)) {
            return false;
        }
        
        // Sempre salvar como JSON string para compatibilidade
        if (is_array($fields)) {
            // Garantir que cada campo tem uma chave válida
            $processed_fields = [];
            foreach ($fields as $field) {
                if (empty($field['label'])) {
                    continue; // Pular campos sem label
                }
                
                // Gerar chave se não existir ou se estiver vazia
                if (empty($field['key'])) {
                    $field['key'] = sanitize_key(sanitize_title($field['label']));
                    // Remover hífens e underscores para manter consistência
                    $field['key'] = str_replace(['-', '_'], '', $field['key']);
                }
                
                $processed_fields[] = [
                    'label' => sanitize_text_field($field['label']),
                    'key' => sanitize_key($field['key']),
                    'type' => isset($field['type']) ? sanitize_text_field($field['type']) : 'text',
                    'required' => !empty($field['required']),
                    'options' => isset($field['options']) && is_array($field['options']) 
                        ? array_map('sanitize_text_field', $field['options']) 
                        : []
                ];
            }
            
            $json_data = json_encode($processed_fields, JSON_UNESCAPED_UNICODE);
            
            // Sempre atualizar (não verificar se mudou, pois pode haver diferenças de formatação JSON)
            // Primeiro, deletar o meta existente para garantir atualização limpa
            delete_post_meta($collection_id, '_acervox_fields');
            
            // Adicionar novo valor
            $result = add_post_meta($collection_id, '_acervox_fields', $json_data, true);
            
            // Se add_post_meta falhou (já existe), usar update
            if ($result === false) {
                $result = update_post_meta($collection_id, '_acervox_fields', $json_data);
            }
            
            // Limpar todos os caches relacionados
            clean_post_cache($collection_id);
            wp_cache_delete($collection_id, 'post_meta');
            wp_cache_delete($collection_id . '_acervox_fields', 'post_meta');
            
            // Verificar se foi salvo corretamente
            $verify = get_post_meta($collection_id, '_acervox_fields', true);
            
            // Comparação mais flexível (normalizar JSON)
            $verify_json = is_string($verify) ? json_decode($verify, true) : $verify;
            $expected_json = json_decode($json_data, true);
            
            // Comparar arrays normalizados
            return ($verify_json === $expected_json || $verify === $json_data);
        } else {
            $result = update_post_meta($collection_id, '_acervox_fields', $fields);
            clean_post_cache($collection_id);
            return $result !== false;
        }
    }
}
