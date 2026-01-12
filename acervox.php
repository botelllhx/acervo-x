<?php
/**
 * Plugin Name: AcervoX
 * Plugin URI: https://github.com/seu-usuario/acervox
 * Description: Framework profissional e moderno para gestão de acervos digitais no WordPress. Gerencie coleções, itens, metadados personalizados e exiba seu acervo com layouts elegantes. Suporta importação de dados via CSV e integração com outros sistemas de gestão de acervos.
 * Version: 0.2.0
 * Author: Mateus Botelho
 * Author URI: https://github.com/seu-usuario
 * Text Domain: acervox
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) exit;

// Constantes do plugin
define('ACERVOX_PATH', plugin_dir_path(__FILE__));
define('ACERVOX_URL', plugin_dir_url(__FILE__));
define('ACERVOX_VERSION', '0.2.0');
define('ACERVOX_DB_VERSION', '1.0');

// Carregar classes de ativação/desativação antes de registrar hooks
require_once ACERVOX_PATH . 'includes/core/activator.php';
require_once ACERVOX_PATH . 'includes/core/deactivator.php';

// Hooks de ativação / desativação
register_activation_hook(__FILE__, ['AcervoX_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['AcervoX_Deactivator', 'deactivate']);

// Loader
require_once ACERVOX_PATH . 'includes/core/loader.php';

// Inicialização segura do plugin
add_action('plugins_loaded', ['AcervoX_Loader', 'init']);
