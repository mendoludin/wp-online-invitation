<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type LIKE 'wedding_%'");
delete_option('wp_online_invitation_settings');
