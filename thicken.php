<?php
/**
 * Plugin Name: Thicken
 * Description: Provides a rotating random-post RSS feed.
 * Version: 0.1.0
 * Author: Thicken Team
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: thicken
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THICKEN_VERSION', '0.1.0');
define('THICKEN_PLUGIN_FILE', __FILE__);

define('THICKEN_PLUGIN_DIR', __DIR__);

define('THICKEN_PLUGIN_BASENAME', plugin_basename(__FILE__));

final class Thicken_Plugin
{
    private static $instance = null;

    private function __construct()
    {
        $this->setup_hooks();
    }

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function setup_hooks()
    {
        register_activation_hook(THICKEN_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(THICKEN_PLUGIN_FILE, array($this, 'deactivate'));
    }

    public function activate()
    {
        // Activation logic will be added in later issues.
    }

    public function deactivate()
    {
        // Deactivation logic will be added in later issues.
    }
}

Thicken_Plugin::instance();
