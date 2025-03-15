<?php
/**
 * My Account Shortcuts tab
 *
 * This template can be overridden by copying it to yourtheme/shortcuts-hub/myaccount/shortcuts.php.
 *
 * @package ShortcutsHub
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Get user's downloaded shortcuts
global $wpdb;
$user_id = get_current_user_id();
$table_name = $wpdb->prefix . 'shortcutshub_downloads';

$downloads = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        shortcut_id,
        shortcut_name, 
        MAX(version) as latest_downloaded_version,
        MAX(download_date) as last_download_date,
        post_url,
        post_id
     FROM {$table_name} 
     WHERE user_id = %d 
     GROUP BY shortcut_id
     ORDER BY last_download_date DESC",
    $user_id
));
?>
<div class="woocommerce-MyAccount-content-wrapper shortcuts-content">
    <h2><?php echo esc_html__('My Shortcuts', 'shortcuts-hub'); ?></h2>
    
    <?php if (empty($downloads)) : ?>
        <div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
            <?php echo esc_html__('You haven\'t downloaded any shortcuts yet.', 'shortcuts-hub'); ?>
        </div>
    <?php else : ?>
        <div class="shortcuts-table-container">
            <table class="shortcuts-table woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
                <thead>
                    <tr>
                        <th class="shortcut-name-column"><?php echo esc_html__('Shortcut', 'shortcuts-hub'); ?></th>
                        <th class="shortcut-version-column"><?php echo esc_html__('Version', 'shortcuts-hub'); ?></th>
                        <th class="shortcut-date-column"><?php echo esc_html__('Date', 'shortcuts-hub'); ?></th>
                        <th class="shortcut-actions-column"><?php echo esc_html__('Actions', 'shortcuts-hub'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($downloads as $download) : ?>
                        <tr>
                            <td class="shortcut-name-cell" data-title="<?php echo esc_attr__('Shortcut', 'shortcuts-hub'); ?>">
                                <div class="shortcut-name-container">
                                    <?php if (!empty($download->post_url)) : ?>
                                        <span class="shortcut-name-text"><a href="<?php echo esc_url($download->post_url); ?>"><?php echo esc_html($download->shortcut_name); ?></a></span>
                                    <?php else : ?>
                                        <span class="shortcut-name-text"><?php echo esc_html($download->shortcut_name); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="shortcut-version-cell" data-title="<?php echo esc_attr__('Version', 'shortcuts-hub'); ?>">
                                <?php echo esc_html($download->latest_downloaded_version); ?>
                            </td>
                            <td class="shortcut-date-cell" data-title="<?php echo esc_attr__('Date', 'shortcuts-hub'); ?>">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($download->last_download_date))); ?>
                            </td>
                            <td class="shortcut-actions-cell" data-title="<?php echo esc_attr__('Actions', 'shortcuts-hub'); ?>">
                                <?php if (!empty($download->post_url)) : ?>
                                    <a href="<?php echo esc_url($download->post_url); ?>" class="shortcut-action-button view-shortcut button">
                                        <?php echo esc_html__('View', 'shortcuts-hub'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
