<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Thicken_Feed
{
    /** @var Thicken */
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function render_feed()
    {
        $settings = $this->plugin->get_settings();
        $post_types = isset($settings['post_types']) ? (array) $settings['post_types'] : array('post');
        $interval = isset($settings['interval']) ? (int) $settings['interval'] : 600;
        if ($interval <= 0) {
            $interval = 600;
        }

        $post_id = $this->get_cached_post_id($post_types, $interval);
        $post = $post_id ? get_post($post_id) : null;
        if ($post && $post->post_status !== 'publish') {
            delete_transient(Thicken::TRANSIENT_KEY);
            $post = null;
        }

        $charset = get_option('blog_charset');
        header('Content-Type: application/rss+xml; charset=' . $charset, true);

        echo '<?xml version="1.0" encoding="' . esc_attr($charset) . '"?>';
        echo "\n";
        echo '<rss version="2.0">';
        echo "\n";
        echo '<channel>';
        echo "\n";
        echo '<title>' . esc_html(get_bloginfo('name')) . '</title>';
        echo "\n";
        echo '<link>' . esc_url(get_bloginfo('url')) . '</link>';
        echo "\n";
        echo '<description>' . esc_html(get_bloginfo('description')) . '</description>';
        echo "\n";
        echo '<lastBuildDate>' . esc_html(gmdate(DATE_RSS)) . '</lastBuildDate>';
        echo "\n";

        if ($post) {
            $title = get_the_title($post);
            $link = get_permalink($post);
            $description = $this->get_post_description($post);
            $pub_date = get_post_time(DATE_RSS, true, $post);

            echo '<item>';
            echo "\n";
            echo '<title>' . esc_html($title) . '</title>';
            echo "\n";
            echo '<link>' . esc_url($link) . '</link>';
            echo "\n";
            echo '<pubDate>' . esc_html($pub_date) . '</pubDate>';
            echo "\n";
            echo '<guid isPermaLink="true">' . esc_url($link) . '</guid>';
            echo "\n";
            echo '<description>' . esc_html($description) . '</description>';
            echo "\n";
            echo '</item>';
            echo "\n";
        }

        echo '</channel>';
        echo "\n";
        echo '</rss>';
        exit;
    }

    private function get_cached_post_id($post_types, $interval)
    {
        $cached = get_transient(Thicken::TRANSIENT_KEY);
        if ($cached !== false) {
            $cached_id = (int) $cached;
            if ($this->is_valid_cached_post($cached_id, $post_types)) {
                return $cached_id;
            }

            delete_transient(Thicken::TRANSIENT_KEY);
        }

        $post_id = $this->get_random_post_id($post_types);
        if ($post_id) {
            set_transient(Thicken::TRANSIENT_KEY, $post_id, $interval);
        }

        return $post_id;
    }

    private function is_valid_cached_post($post_id, $post_types)
    {
        if (!$post_id) {
            return false;
        }

        $post_types = array_values(array_filter($post_types, 'post_type_exists'));
        if (empty($post_types)) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        if ($post->post_status !== 'publish') {
            return false;
        }

        if (!in_array($post->post_type, $post_types, true)) {
            return false;
        }

        return true;
    }

    private function get_random_post_id($post_types)
    {
        global $wpdb;

        $post_types = array_values(array_filter($post_types, 'post_type_exists'));
        if (empty($post_types)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $min_max_sql = "SELECT MIN(ID) as min_id, MAX(ID) as max_id FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($placeholders)";
        $min_max = $wpdb->get_row($wpdb->prepare($min_max_sql, $post_types));

        if (empty($min_max) || !$min_max->min_id || !$min_max->max_id) {
            return 0;
        }

        $rand_id = wp_rand((int) $min_max->min_id, (int) $min_max->max_id);
        $select_sql = "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($placeholders) AND ID >= %d ORDER BY ID ASC LIMIT 1";
        $post_id = (int) $wpdb->get_var($wpdb->prepare($select_sql, array_merge($post_types, array($rand_id))));

        if ($post_id > 0) {
            return $post_id;
        }

        $fallback_sql = "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($placeholders) AND ID <= %d ORDER BY ID DESC LIMIT 1";
        $post_id = (int) $wpdb->get_var($wpdb->prepare($fallback_sql, array_merge($post_types, array($rand_id))));

        return $post_id > 0 ? $post_id : 0;
    }

    private function get_post_description($post)
    {
        if (has_excerpt($post->ID)) {
            $excerpt = get_the_excerpt($post);
            return wp_trim_words(wp_strip_all_tags($excerpt), 55);
        }

        $content = $post->post_content ? $post->post_content : '';
        $content = wp_strip_all_tags($content);

        return wp_trim_words($content, 55);
    }
}
