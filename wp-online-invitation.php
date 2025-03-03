<?php
/*
Plugin Name: WP Online Invitation
Description: Plugin untuk mengelola undangan online terintegrasi dengan WooCommerce, CPT UI, dan ACF.
Version: 1.0.0
Author: Muhamad Aminnudin
License: GPL-2.0+
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WPOI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPOI_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!class_exists('WP_Online_Invitation')) {
    final class WP_Online_Invitation {
        private static $instance = null;

        private function __construct() {
            if (did_action('wpoi_plugin_initialized')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('WP_Online_Invitation initialization skipped - already initialized');
                }
                return;
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WP_Online_Invitation constructed');
            }
            $this->setup();
            do_action('wpoi_plugin_initialized');
        }

        public static function get_instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function setup() {
            $this->include_files();
            $this->init_components();
            $this->register_hooks();
        }

        private function include_files() {
            require_once WPOI_PLUGIN_DIR . 'includes/class-invitation-cpt.php';
            require_once WPOI_PLUGIN_DIR . 'includes/class-invitation-acf.php';
            require_once WPOI_PLUGIN_DIR . 'includes/class-invitation-myaccount.php';
            require_once WPOI_PLUGIN_DIR . 'includes/class-invitation-email.php';
            require_once WPOI_PLUGIN_DIR . 'includes/class-invitation-rsvp.php';
            require_once WPOI_PLUGIN_DIR . 'includes/class-invitation-guest.php';
            require_once WPOI_PLUGIN_DIR . 'includes/class-invitation-comments.php';
            require_once WPOI_PLUGIN_DIR . 'includes/class-invitation-admin.php';
        }

        private function init_components() {
            Invitation_CPT::get_instance();
            Invitation_ACF::get_instance();
            Invitation_MyAccount::get_instance();
            Invitation_Email::get_instance();
            Invitation_RSVP::get_instance();
            Invitation_Guest::get_instance();
            Invitation_Comments::get_instance();
            Invitation_Admin::get_instance();
        }

        private function register_hooks() {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
            add_action('init', [$this, 'disable_heartbeat_on_settings']);
        }

        public function disable_heartbeat_on_settings() {
            global $pagenow;
            if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wpoi-settings') {
                wp_deregister_script('heartbeat');
                add_filter('heartbeat_received', '__return_false', 9999);
                add_filter('heartbeat_send', '__return_false', 9999);
                error_log('Heartbeat forcefully disabled on wpoi-settings');
            }
        }

        public function enqueue_assets() {
            if (function_exists('is_account_page') && is_account_page()) {
                wp_enqueue_style(
                    'wpoi-frontend',
                    WPOI_PLUGIN_URL . 'assets/css/frontend.css',
                    array(),
                    '1.0.0',
                    'all'
                );
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Enqueuing frontend CSS for My Account page');
                }
            }

            wp_enqueue_script(
                'wpoi-frontend-js',
                WPOI_PLUGIN_URL . 'assets/js/frontend.js',
                ['jquery'],
                '1.0.0',
                true
            );
        }

        public function enqueue_admin_scripts() {
            wp_enqueue_style('wpoi-admin-css', WPOI_PLUGIN_URL . 'assets/css/admin.css');
            wp_enqueue_script('wpoi-admin-js', WPOI_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], null, true);
        }

        public function activate() {
            update_option('wpoi_flush_rewrite_needed', true);
            flush_rewrite_rules();
        }

        public function deactivate() {
            flush_rewrite_rules();
        }

        public static function uninstall() {
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
                '_%_cpt_id'
            ));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->posts} WHERE post_type LIKE %s",
                'wedding_invitation%'
            ));
            delete_option('wpoi_settings');
            delete_transient('wpoi_transient_data');
        }
    }

    // Inisialisasi plugin hanya jika belum diinisialisasi
    if (!did_action('wpoi_plugin_initialized')) {
        WP_Online_Invitation::get_instance();
    }
}