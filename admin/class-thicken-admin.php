<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Thicken_Admin
{
    /** @var Thicken */
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('update_option_' . Thicken::OPTION_NAME, array($this, 'handle_option_update'), 10, 2);
    }

    public function register_menu()
    {
        add_options_page(
            __('Thicken', 'thicken'),
            __('Thicken', 'thicken'),
            'manage_options',
            'thicken-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings()
    {
        register_setting(
            'thicken_settings_group',
            Thicken::OPTION_NAME,
            array($this, 'sanitize_settings')
        );

        add_settings_section(
            'thicken_main_section',
            __('Thicken Settings', 'thicken'),
            array($this, 'render_section_description'),
            'thicken-settings'
        );

        add_settings_field(
            'thicken_post_types',
            __('Post Types', 'thicken'),
            array($this, 'render_post_types_field'),
            'thicken-settings',
            'thicken_main_section'
        );

        add_settings_field(
            'thicken_interval',
            __('Rotation Interval', 'thicken'),
            array($this, 'render_interval_field'),
            'thicken-settings',
            'thicken_main_section'
        );

        add_settings_field(
            'thicken_feed_slug',
            __('Feed Slug', 'thicken'),
            array($this, 'render_feed_slug_field'),
            'thicken-settings',
            'thicken_main_section'
        );
    }

    public function render_section_description()
    {
        echo '<p>' . esc_html__('Configure the random-post RSS feed.', 'thicken') . '</p>';
    }

    public function render_post_types_field()
    {
        $settings = $this->plugin->get_settings();
        $selected = isset($settings['post_types']) ? (array) $settings['post_types'] : array('post');
        $post_types = get_post_types(array('public' => true), 'objects');

        foreach ($post_types as $post_type) {
            if ($post_type->name === 'attachment') {
                continue;
            }

            $checked = in_array($post_type->name, $selected, true);
            echo '<label style="display:block; margin-bottom:4px;">';
            echo '<input type="checkbox" name="' . esc_attr(Thicken::OPTION_NAME) . '[post_types][]" value="' . esc_attr($post_type->name) . '" ' . checked($checked, true, false) . ' />';
            echo ' ' . esc_html($post_type->labels->singular_name);
            echo '</label>';
        }
    }

    public function render_interval_field()
    {
        $settings = $this->plugin->get_settings();
        $selected = isset($settings['interval']) ? (int) $settings['interval'] : 600;
        $options = $this->get_interval_options();

        echo '<select name="' . esc_attr(Thicken::OPTION_NAME) . '[interval]">';
        foreach ($options as $value => $label) {
            $is_selected = ((int) $value === $selected);
            echo '<option value="' . esc_attr($value) . '" ' . selected($is_selected, true, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function render_feed_slug_field()
    {
        $settings = $this->plugin->get_settings();
        $slug = isset($settings['feed_slug']) ? $settings['feed_slug'] : 'random-post';

        echo '<input type="text" class="regular-text" name="' . esc_attr(Thicken::OPTION_NAME) . '[feed_slug]" value="' . esc_attr($slug) . '" />';
        echo '<p class="description">' . esc_html__('Default: random-post', 'thicken') . '</p>';
    }

    public function sanitize_settings($input)
    {
        if (!current_user_can('manage_options')) {
            return $this->plugin->get_settings();
        }

        $defaults = Thicken::get_default_settings();
        $output = $defaults;

        $allowed_post_types = $this->get_allowed_post_types();
        if (isset($input['post_types']) && is_array($input['post_types'])) {
            $sanitized = array();
            foreach ($input['post_types'] as $post_type) {
                $post_type = sanitize_key($post_type);
                if (in_array($post_type, $allowed_post_types, true)) {
                    $sanitized[] = $post_type;
                }
            }
            if (!empty($sanitized)) {
                $output['post_types'] = array_values(array_unique($sanitized));
            }
        }

        $interval_options = array_keys($this->get_interval_options());
        if (isset($input['interval'])) {
            $interval = (int) $input['interval'];
            if (in_array($interval, $interval_options, true)) {
                $output['interval'] = $interval;
            }
        }

        if (isset($input['feed_slug'])) {
            $slug = sanitize_title($input['feed_slug']);
            $output['feed_slug'] = $slug !== '' ? $slug : $defaults['feed_slug'];
        }

        return $output;
    }

    public function handle_option_update($old_value, $new_value)
    {
        $old_value = is_array($old_value) ? $old_value : array();
        $new_value = is_array($new_value) ? $new_value : array();

        $old_value = wp_parse_args($old_value, Thicken::get_default_settings());
        $new_value = wp_parse_args($new_value, Thicken::get_default_settings());

        if ($old_value['feed_slug'] !== $new_value['feed_slug']) {
            flush_rewrite_rules();
        }

        if ($old_value['interval'] !== $new_value['interval'] || $old_value['post_types'] !== $new_value['post_types'] || $old_value['feed_slug'] !== $new_value['feed_slug']) {
            $old_key = $this->plugin->build_transient_key($old_value, 0);
            $new_key = $this->plugin->build_transient_key($new_value, 0);
            delete_transient($old_key);
            delete_transient($new_key);
        }
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        include THICKEN_PLUGIN_DIR . '/admin/views/settings-page.php';
    }

    private function get_interval_options()
    {
        return array(
            600 => __('10 minutes', 'thicken'),
            1200 => __('20 minutes', 'thicken'),
            1800 => __('30 minutes', 'thicken'),
            3600 => __('60 minutes', 'thicken'),
            10800 => __('3 hours', 'thicken'),
            21600 => __('6 hours', 'thicken'),
            43200 => __('12 hours', 'thicken'),
            86400 => __('24 hours', 'thicken'),
        );
    }

    private function get_allowed_post_types()
    {
        $post_types = get_post_types(array('public' => true), 'objects');
        $allowed = array();

        foreach ($post_types as $post_type) {
            if ($post_type->name === 'attachment') {
                continue;
            }
            $allowed[] = $post_type->name;
        }

        return $allowed;
    }
}
