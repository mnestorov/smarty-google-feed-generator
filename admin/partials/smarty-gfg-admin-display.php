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

<?php $license_options = get_option('smarty_gfg_settings_license'); ?>
<?php $api_key = $license_options['api_key'] ?? ''; ?>

<div class="wrap">
	<h1><?php echo esc_html('Google Feed Generator | Settings', 'smarty-google-feed-generator'); ?></h1>
	<h2 class="nav-tab-wrapper">
	<?php foreach ($tabs as $tab_key => $tab_caption) : ?>
            <?php $active = ($current_tab == $tab_key) ? 'nav-tab-active' : ''; ?>
            <a class="nav-tab <?php echo $active; ?>" href="<?php echo admin_url('options-general.php?page=smarty-gfg-settings&tab=' . $tab_key); ?>">
                <?php echo $tab_caption; ?>
            </a>
        <?php endforeach; ?>
	</h2>

	<?php if ($this->is_valid_api_key($api_key)) : ?>
		<form action="options.php" method="post">
			<?php if ($current_tab == 'general') : ?>
				<?php settings_fields('smarty_gfg_options_general'); ?>
				<?php do_settings_sections('smarty_gfg_options_general'); ?>
			<?php elseif ($current_tab == 'google-feed') : ?>
				<?php settings_fields('smarty_gfg_options_google_feed'); ?>
				<?php do_settings_sections('smarty_gfg_options_google_feed'); ?>
			<?php elseif ($current_tab == 'activity-logging') : ?>
				<?php settings_fields('smarty_gfg_options_activity_logging'); ?>
				<?php do_settings_sections('smarty_gfg_options_activity_logging'); ?>
			<?php elseif ($current_tab == 'license') : ?>
				<?php settings_fields('smarty_gfg_options_license'); ?>
				<?php do_settings_sections('smarty_gfg_options_license'); ?>
			<?php endif; ?>
			<?php submit_button(__('Save Settings', 'smarty-google-feed-generator')); ?>
		</form>
	<?php else: ?>
		<form action="options.php" method="post">
			<?php if ($current_tab == 'license') : ?>
				<?php settings_fields('smarty_gfg_options_license'); ?>
				<?php do_settings_sections('smarty_gfg_options_license'); ?>
				<?php submit_button(__('Save Settings', 'smarty-google-feed-generator')); ?>
			<?php else: ?>
				<p class="description smarty-error" style="margin: 30px 0;"><?php echo esc_html__('Please enter a valid API key in the License tab to access this setting.', 'smarty-google-feed-generator'); ?></p>
			<?php endif; ?>
		</form>
	<?php endif; ?>
</div>