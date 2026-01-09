<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Tainacan_Importer {

    public static function is_tainacan_active() {
        return class_exists('\Tainacan\Repositories\Collections');
    }

    public static function get_collections() {
        if (!self::is_tainacan_active()) return [];

        $repo = \Tainacan\Repositories\Collections::get_instance();
        return $repo->fetch(['posts_per_page' => -1]);
    }
    
    public static function import_items($collection_id, $acervox_collection_id) {
        if (!self::is_tainacan_active()) {
            AcervoX_Import_Logger::add("Erro: Tainacan não está ativo");
            return false;
        }

        if (empty($collection_id) || empty($acervox_collection_id)) {
            AcervoX_Import_Logger::add("Erro: IDs de coleção inválidos");
            return false;
        }

        try {
            $items_repo = \Tainacan\Repositories\Items::get_instance();
            $items = $items_repo->fetch(['collection_id' => $collection_id, 'posts_per_page' => -1]);

            if (empty($items)) {
                AcervoX_Import_Logger::add("Nenhum item encontrado na coleção Tainacan");
                return true;
            }

            $imported = 0;
            $errors = 0;

            foreach ($items as $item) {
                $post_id = wp_insert_post([
                    'post_type' => 'acervox_item',
                    'post_title' => sanitize_text_field($item->get_title()),
                    'post_content' => wp_kses_post($item->get_description()),
                    'post_status' => 'publish'
                ]);

                if (is_wp_error($post_id)) {
                    AcervoX_Import_Logger::add("Erro ao criar item: " . $post_id->get_error_message());
                    $errors++;
                    continue;
                }

                update_post_meta($post_id, '_acervox_collection', absint($acervox_collection_id));

                // Importar metadados
                $metadata = $item->get_metadata();
                if (!empty($metadata)) {
                    foreach ($metadata as $meta) {
                        $meta_name = sanitize_key($meta->get_name());
                        $meta_value = $meta->get_value();
                        
                        // Sanitizar valor baseado no tipo
                        if (is_array($meta_value)) {
                            $meta_value = array_map('sanitize_text_field', $meta_value);
                        } else {
                            $meta_value = sanitize_text_field($meta_value);
                        }
                        
                        update_post_meta(
                            $post_id,
                            '_acervox_' . $meta_name,
                            $meta_value
                        );
                    }
                }

                // Importar thumbnail
                $thumbnail_id = $item->get_thumbnail_id();
                if ($thumbnail_id) {
                    set_post_thumbnail($post_id, absint($thumbnail_id));
                }

                $imported++;
                AcervoX_Import_Logger::add("Item importado: {$post_id} - " . $item->get_title());
            }

            AcervoX_Import_Logger::add("Importação concluída: {$imported} itens importados, {$errors} erros");
            return true;

        } catch (Exception $e) {
            AcervoX_Import_Logger::add("Erro na importação: " . $e->getMessage());
            return false;
        }
    }

}

