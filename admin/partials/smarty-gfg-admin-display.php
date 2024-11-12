<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/mnestorov
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
?>

<div class="wrap">
	<h1><?php echo esc_html('Google Feed Generator | Settings', 'smarty-google-feed-generator'); ?></h1>
	
	<?php if ($current_tab == 'google-feed') : ?>
		<button class="btn btn-success smarty-gfg-generate-feed-button" data-feed-action="gfg_generate_google_products_feed"><?php echo __('Google Products XML Feed', 'smarty-google-feed-generator'); ?></button>
		<button class="btn btn-success smarty-gfg-generate-feed-button" data-feed-action="gfg_generate_google_csv_export"><?php echo __('Google Products CSV Feed', 'smarty-google-feed-generator'); ?></button>
	<?php elseif ($current_tab == 'google-reviews-feed') : ?>
		<button class="btn btn-success smarty-gfg-generate-feed-button" data-feed-action="gfg_generate_google_reviews_feed"><?php echo  __('Google Reviews Feed', 'smarty-google-feed-generator'); ?></button>
	<?php elseif ($current_tab == 'bing-products-feed') : ?>
		<button class="btn btn-success smarty-gfg-generate-feed-button" data-feed-action="gfg_generate_bing_products_feed"><?php echo __('Bing Products XML Feed', 'smarty-google-feed-generator'); ?></button>
		<button class="btn btn-success smarty-gfg-generate-feed-button" data-feed-action="gfg_generate_bing_txt_feed"><?php echo __('Bing Products TXT Feed', 'smarty-google-feed-generator'); ?></button>
	<?php endif; ?>

	<h2 class="nav-tab-wrapper">
		<?php foreach ($tabs as $tab_key => $tab_caption) : ?>
			<?php $active = $current_tab == $tab_key ? 'nav-tab-active' : ''; ?>
			<a class="nav-tab <?php echo $active; ?>" href="?page=smarty-gfg-settings&tab=<?php echo $tab_key; ?>">
				<?php echo $tab_caption; ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<?php if (_gfg_validate_license_key()) : ?>
		<form action="options.php" method="post">
			<?php if ($current_tab == 'general') : ?>
				<?php settings_fields('smarty_gfg_options_general'); ?>
				<?php do_settings_sections('smarty_gfg_options_general'); ?>
			<?php elseif ($current_tab == 'google-feed') : ?>
				<?php settings_fields('smarty_gfg_options_google_feed'); ?>
				<?php do_settings_sections('smarty_gfg_options_google_feed'); ?>
			<?php elseif ($current_tab == 'google-reviews-feed') : ?>
				<?php settings_fields('smarty_gfg_options_google_reviews_feed'); ?>
				<?php do_settings_sections('smarty_gfg_options_google_reviews_feed'); ?>
			<?php elseif ($current_tab == 'bing-products-feed') : ?>
				<?php settings_fields('smarty_gfg_options_bing_feed'); ?>
				<?php do_settings_sections('smarty_gfg_options_bing_feed'); ?>
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