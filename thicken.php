<?php
/**
 * Plugin Name: Thicken
 * Description: Provides a rotating random-post RSS feed.
 * Version: 1.0.0
 * Author: Thicken Team
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: thicken
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THICKEN_VERSION', '1.0.0');
define('THICKEN_PLUGIN_FILE', __FILE__);
define('THICKEN_PLUGIN_DIR', __DIR__);
define('THICKEN_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once THICKEN_PLUGIN_DIR . '/includes/class-thicken.php';
require_once THICKEN_PLUGIN_DIR . '/includes/class-thicken-feed.php';
require_once THICKEN_PLUGIN_DIR . '/admin/class-thicken-admin.php';

register_activation_hook(THICKEN_PLUGIN_FILE, array('Thicken', 'activate'));
register_deactivation_hook(THICKEN_PLUGIN_FILE, array('Thicken', 'deactivate'));

Thicken::instance();
