<?php
if (!defined('ABSPATH')) {
    exit;
}

class Invitation_MyAccount {
    private static $instance = null;

    private function __construct() {
        add_filter('woocommerce_account_menu_items', [$this, 'add_invitation_menu_item']);
        add_action('woocommerce_account_invitation_endpoint', [$this, 'invitation_endpoint_content']);
        add_action('init', [$this, 'register_endpoint']);
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_endpoint() {
        add_rewrite_endpoint('invitation', EP_ROOT | EP_PAGES);
        // Paksa flush rewrite rules saat pertama kali endpoint didaftarkan
        $flush_needed = get_option('wpoi_flush_rewrite_needed', true);
        if ($flush_needed) {
            flush_rewrite_rules();
            update_option('wpoi_flush_rewrite_needed', false);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Rewrite rules flushed for Invitation endpoint');
            }
        }
    }

    public function add_invitation_menu_item($items) {
        $items['invitation'] = __('Undangan Saya', 'wp-online-invitation');
        return $items;
    }

    public function invitation_endpoint_content() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>Silakan login untuk melihat undangan Anda.</p>';
            return;
        }

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => ['completed'],
            'limit' => -1,
        ]);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Rendering Invitation endpoint for user ' . $user_id);
            error_log('Orders found for user ' . $user_id . ': ' . count($orders));
            foreach ($orders as $order) {
                error_log('Order ID: ' . $order->get_id() . ', Status: ' . $order->get_status());
            }
        }

        if (empty($orders)) {
            echo '<p>Belum ada pesanan undangan yang selesai.</p>';
            return;
        }

        echo '<h2>Undangan Saya</h2>';

        $acf_instance = class_exists('Invitation_ACF') ? Invitation_ACF::get_instance() : null;

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $invitation_id = get_post_meta($order_id, '_invitation_cpt_id', true);
            $invitation_link = $invitation_id ? get_permalink($invitation_id) : '#';

            echo '<div class="invitation-item">';
            echo '<h3>Order #' . $order_id . '</h3>';

            if ($invitation_id) {
                echo '<p><a href="' . esc_url($invitation_link) . '">Lihat Undangan</a></p>';
                if ($acf_instance) {
                    echo '<div class="invitation-edit-form">';
                    $acf_instance->render_client_form($invitation_id);
                    echo '</div>';
                } else {
                    echo '<p>Form pengeditan undangan tidak tersedia. Pastikan plugin ACF aktif.</p>';
                }
            } else {
                echo '<p>Undangan belum dibuat untuk pesanan ini.</p>';
            }

            echo '</div>';
        }
    }

    private function __clone() {}
    public function __wakeup() {}
}

Invitation_MyAccount::get_instance();

// Tandai flush rewrite saat aktivasi plugin
register_activation_hook(WPOI_PLUGIN_DIR . 'wp-online-invitation.php', function() {
    update_option('wpoi_flush_rewrite_needed', true);
});