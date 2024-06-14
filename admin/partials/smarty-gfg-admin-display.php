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
	<h2 class="nav-tab-wrapper">
	<?php foreach ($tabs as $tab_key => $tab_caption) : ?>
            <?php $active = ($current_tab == $tab_key) ? 'nav-tab-active' : ''; ?>
            <a class="nav-tab <?php echo $active; ?>" href="<?php echo admin_url('options-general.php?page=smarty-gfg-settings&tab=' . $tab_key); ?>">
                <?php echo $tab_caption; ?>
            </a>
        <?php endforeach; ?>
	</h2>
	<form action="options.php" method="post">
		<?php if ($current_tab == 'general') : ?>
			<?php settings_fields('smarty_gfg_options_general'); ?>
			<?php do_settings_sections('smarty_gfg_options_general'); ?>
		<?php elseif ($current_tab == 'mapping') : ?>
			<?php settings_fields('smarty_gfg_options_mapping'); ?>
			<?php do_settings_sections('smarty_gfg_options_mapping'); ?>
		<?php elseif ($current_tab == 'compatibility') : ?>
			<?php $compatibility = call_user_func('Smarty_Gfg_Admin::section_compatibility_cb'); ?>
			<div class="flex-container">
				<!-- WordPress Version -->
				<div class="flex-item">
					<strong><?php echo esc_html__('WordPress Version', 'smarty-google-feed-generator'); ?></strong>
					<div><?php echo esc_html($compatibility['wp_version']); ?></div>
				</div>

				<!-- WooCommerce Version -->
				<div class="flex-item">
					<strong><?php echo esc_html__('WooCommerce Version', 'smarty-google-feed-generator'); ?></strong>
					<div><?php echo esc_html($compatibility['wc_version']); ?></div>
				</div>

				<!-- PHP Version -->
				<div class="flex-item">
					<strong><?php echo esc_html__('PHP Version', 'smarty-google-feed-generator'); ?></strong>
					<div><?php echo esc_html($compatibility['php_version']); ?></div>
				</div>

				<!-- Status -->
				<div class="flex-item">
					<strong><?php echo esc_html__('Status', 'smarty-google-feed-generator'); ?></strong>
					<div>
						<span class="smarty-compatibility-status <?php echo ($compatibility['wp_compatible'] && $compatibility['php_compatible'] && $compatibility['wc_compatible']) ? 'smarty-compatibility-yes' : 'smarty-compatibility-no'; ?>">
							<small><?php echo ($compatibility['wp_compatible'] && $compatibility['php_compatible'] && $compatibility['wc_compatible']) ? esc_html__('Compatible', 'smarty-google-feed-generator') : esc_html__('Incompatible', 'smarty-google-feed-generator'); ?></small>
						</span>
					</div>
				</div>
			</div>
		<?php elseif ($current_tab == 'license') : ?>
			<?php settings_fields('smarty_gfg_options_license'); ?>
			<?php do_settings_sections('smarty_gfg_options_license'); ?>
		<?php endif; ?>
		<?php submit_button(__('Save Settings', 'smarty-google-feed-generator')); ?>
	</form>
</div>