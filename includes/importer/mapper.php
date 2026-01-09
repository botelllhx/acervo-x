<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Tainacan_Mapper {

    public static function map_fields($collection_id) {

        $meta_repo = \Tainacan\Repositories\Metadata::get_instance();
        $metadata = $meta_repo->fetch_by_collection($collection_id);

        $mapped = [];

        foreach ($metadata as $meta) {
            $mapped[] = [
                'label' => $meta->get_name(),
                'key' => sanitize_title($meta->get_name()),
                'type' => self::map_type($meta->get_metadata_type())
            ];
        }

        return $mapped;
    }

    private static function map_type($type) {
        switch ($type) {
            case 'text':
                return 'text';
            case 'numeric':
                return 'number';
            case 'selectbox':
                return 'select';
            default:
                return 'text';
        }
    }
}
