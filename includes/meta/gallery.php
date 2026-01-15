<?php
if (!defined('ABSPATH')) exit;

/**
 * Sistema de Galeria de Mídia Avançada
 * Permite múltiplas imagens, vídeos e documentos por item
 */
class AcervoX_Gallery {
    
    const META_KEY = '_acervox_gallery';
    
    /**
     * Adicionar mídia à galeria do item
     */
    public static function add_media($item_id, $attachment_id, $type = 'image') {
        $gallery = self::get_gallery($item_id);
        
        $media = [
            'id' => absint($attachment_id),
            'type' => sanitize_text_field($type),
            'order' => count($gallery),
            'added_at' => current_time('mysql')
        ];
        
        $gallery[] = $media;
        
        return update_post_meta($item_id, self::META_KEY, $gallery);
    }
    
    /**
     * Remover mídia da galeria
     */
    public static function remove_media($item_id, $attachment_id) {
        $gallery = self::get_gallery($item_id);
        
        $gallery = array_filter($gallery, function($media) use ($attachment_id) {
            return $media['id'] != $attachment_id;
        });
        
        // Reordenar
        $gallery = array_values($gallery);
        foreach ($gallery as $index => $media) {
            $gallery[$index]['order'] = $index;
        }
        
        return update_post_meta($item_id, self::META_KEY, $gallery);
    }
    
    /**
     * Obter galeria do item
     */
    public static function get_gallery($item_id) {
        $gallery = get_post_meta($item_id, self::META_KEY, true);
        
        if (!is_array($gallery)) {
            return [];
        }
        
        // Ordenar por ordem
        usort($gallery, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        return $gallery;
    }
    
    /**
     * Atualizar ordem da galeria
     */
    public static function update_order($item_id, $attachment_ids) {
        $gallery = [];
        
        foreach ($attachment_ids as $index => $attachment_id) {
            $existing = get_post($attachment_id);
            if ($existing) {
                $gallery[] = [
                    'id' => absint($attachment_id),
                    'type' => wp_attachment_is_image($attachment_id) ? 'image' : 
                             (wp_attachment_is('video', $attachment_id) ? 'video' : 'document'),
                    'order' => $index,
                    'added_at' => get_post_meta($item_id, self::META_KEY, true)[$index]['added_at'] ?? current_time('mysql')
                ];
            }
        }
        
        return update_post_meta($item_id, self::META_KEY, $gallery);
    }
    
    /**
     * Obter URLs das mídias formatadas
     */
    public static function get_formatted_gallery($item_id) {
        $gallery = self::get_gallery($item_id);
        $formatted = [];
        
        foreach ($gallery as $media) {
            $attachment_id = $media['id'];
            $attachment = get_post($attachment_id);
            
            if (!$attachment) {
                continue;
            }
            
            $file_url = wp_get_attachment_url($attachment_id);
            $file_type = wp_check_filetype($file_url);
            
            $formatted_media = [
                'id' => $attachment_id,
                'type' => $media['type'] ?? 'image',
                'url' => $file_url,
                'title' => get_the_title($attachment_id),
                'mime_type' => $attachment->post_mime_type,
                'order' => $media['order'] ?? 0,
                'thumbnails' => []
            ];
            
            // Se for imagem, adicionar thumbnails
            if (wp_attachment_is_image($attachment_id)) {
                $formatted_media['thumbnails'] = [
                    'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                    'medium' => wp_get_attachment_image_url($attachment_id, 'medium'),
                    'large' => wp_get_attachment_image_url($attachment_id, 'large'),
                    'full' => wp_get_attachment_image_url($attachment_id, 'full'),
                ];
            }
            
            $formatted[] = $formatted_media;
        }
        
        return $formatted;
    }
}
