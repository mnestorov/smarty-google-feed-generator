<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
?>

<div class="wrap">
    <h1><?php echo esc_html('Google Feed Generator | Settings', 'smarty-google-feed-generator'); ?></h1>
    <form action="options.php" method="post">
        <?php settings_fields('smarty_feed_generator_settings'); ?>
        <?php do_settings_sections('smarty_feed_generator_settings'); ?>
        <?php submit_button(__('Save Settings', 'smarty-google-feed-generator')); ?>
    </form>
</div>