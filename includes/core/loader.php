<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Loader {

    public static function init() {

        // Core (activator e deactivator já carregados no arquivo principal)
        
        // Post Types
        require_once ACERVOX_PATH . 'includes/post-types/collection.php';
        require_once ACERVOX_PATH . 'includes/post-types/item.php';

        // Taxonomies
        require_once ACERVOX_PATH . 'includes/taxonomies/generic.php';

        // Importer
        require_once ACERVOX_PATH . 'includes/importer/logger.php';
        require_once ACERVOX_PATH . 'includes/importer/mapper.php';
        require_once ACERVOX_PATH . 'includes/importer/tainacan.php';
        require_once ACERVOX_PATH . 'includes/importer/csv.php';
        require_once ACERVOX_PATH . 'includes/importer/history.php';

        // Metadados
        require_once ACERVOX_PATH . 'includes/meta/registry.php';
        require_once ACERVOX_PATH . 'includes/meta/fields.php';
        require_once ACERVOX_PATH . 'includes/meta/metaboxes.php';

        // API REST
        require_once ACERVOX_PATH . 'includes/api/rest.php';
        require_once ACERVOX_PATH . 'includes/api/items.php';

        // Shortcodes
        require_once ACERVOX_PATH . 'includes/shortcode/acervo.php';

        // Admin & Public
        require_once ACERVOX_PATH . 'admin/admin.php';
        require_once ACERVOX_PATH . 'public/public.php';

        // Inicialização das classes
        self::boot();
    }

    private static function boot() {
        // Post Types, Taxonomies e Metaboxes são registrados via hooks
        // API REST é registrada via hooks em rest.php
        // Public é registrado via hooks em public.php

        // Admin
        if (is_admin()) {
            new AcervoX_Admin();
        }
    }
}
