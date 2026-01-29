<?php

if (!defined('ABSPATH')) {
    exit;
}

$settings = $this->plugin->get_settings();
$feed_slug = isset($settings['feed_slug']) ? $settings['feed_slug'] : 'random-post';
$feed_url = trailingslashit(home_url()) . 'feed/' . $feed_slug;

?>
<div class="wrap">
    <h1><?php echo esc_html__('Thicken Settings', 'thicken'); ?></h1>

    <p>
        <?php echo esc_html__('Feed URL:', 'thicken'); ?>
        <code><?php echo esc_html($feed_url); ?></code>
    </p>

    <form method="post" action="options.php">
        <?php
        settings_fields('thicken_settings_group');
        do_settings_sections('thicken-settings');
        submit_button();
        ?>
    </form>
</div>
