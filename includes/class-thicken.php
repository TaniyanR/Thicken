<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Thicken
{
    const OPTION_NAME = 'thicken_settings';
    const TRANSIENT_PREFIX = 'thicken_random_post';

    private static $instance = null;

    /** @var Thicken_Admin */
    private $admin;

    /** @var Thicken_Feed */
    private $feed;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->feed = new Thicken_Feed($this);

        if (is_admin()) {
            $this->admin = new Thicken_Admin($this);
        }

        add_action('init', array($this, 'register_feed'));
    }

    public static function activate()
    {
        $defaults = self::get_default_settings();
        if (!get_option(self::OPTION_NAME)) {
            add_option(self::OPTION_NAME, $defaults);
        }

        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        flush_rewrite_rules();
    }

    public function register_feed()
    {
        $slug = $this->get_feed_slug();
        add_feed($slug, array($this->feed, 'render_feed'));
    }

    public static function get_default_settings()
    {
        return array(
            'post_types' => array('post'),
            'interval' => 600,
            'feed_slug' => 'random-post',
        );
    }

    public function get_settings()
    {
        $settings = get_option(self::OPTION_NAME, array());
        if (!is_array($settings)) {
            $settings = array();
        }

        return wp_parse_args($settings, self::get_default_settings());
    }

    public function get_feed_slug()
    {
        $settings = $this->get_settings();
        $slug = isset($settings['feed_slug']) ? $settings['feed_slug'] : 'random-post';
        $slug = sanitize_title($slug);

        return $slug !== '' ? $slug : 'random-post';
    }

    public function get_transient_key($term_id = 0)
    {
        return $this->build_transient_key($this->get_settings(), $term_id);
    }

    public function build_transient_key($settings, $term_id = 0)
    {
        $settings = is_array($settings) ? $settings : array();
        $settings = wp_parse_args($settings, self::get_default_settings());

        $post_types = isset($settings['post_types']) ? (array) $settings['post_types'] : array('post');
        $interval = isset($settings['interval']) ? (int) $settings['interval'] : 600;
        if ($interval <= 0) {
            $interval = 600;
        }

        $slug = isset($settings['feed_slug']) ? sanitize_title($settings['feed_slug']) : 'random-post';
        if ($slug === '') {
            $slug = 'random-post';
        }

        $context = $term_id ? 'cat_' . (int) $term_id : 'global';
        $hash = md5(wp_json_encode(array(
            'post_types' => $post_types,
            'interval' => $interval,
            'slug' => $slug,
        )));

        return self::TRANSIENT_PREFIX . '_' . $context . '_' . substr($hash, 0, 10);
    }
}
