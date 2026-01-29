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
            return (int) $cached;
        }

        $post_id = $this->get_random_post_id($post_types);
        if ($post_id) {
            set_transient(Thicken::TRANSIENT_KEY, $post_id, $interval);
        }

        return $post_id;
    }

    private function get_random_post_id($post_types)
    {
        $post_types = array_values(array_filter($post_types, 'post_type_exists'));
        if (empty($post_types)) {
            return 0;
        }

        $count_query = new WP_Query(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ));

        $total = (int) $count_query->found_posts;
        if ($total < 1) {
            return 0;
        }

        $offset = $total > 1 ? wp_rand(0, $total - 1) : 0;

        $random_query = new WP_Query(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'offset' => $offset,
        ));

        if (empty($random_query->posts)) {
            return 0;
        }

        return (int) $random_query->posts[0];
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
