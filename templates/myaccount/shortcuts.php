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

?>
<div class="woocommerce-MyAccount-content-wrapper shortcuts-content">
    <h2><?php echo esc_html__('My Shortcuts', 'shortcuts-hub'); ?></h2>
    
    <?php
    // Display the download log table
    if (isset($download_log) && is_object($download_log)) {
        $download_log->render();
    } else {
        echo '<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">';
        echo esc_html__('No shortcuts available yet.', 'shortcuts-hub');
        echo '</div>';
    }
    ?>
</div>
