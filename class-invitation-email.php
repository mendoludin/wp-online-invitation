<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kelas untuk mengelola pengiriman email otomatis.
 * Menghasilkan dan menyimpan kredensial login, serta menambahkannya ke email order complete WooCommerce.
 */
class Invitation_Email {
    /**
     * Menyimpan instance tunggal dari kelas.
     * @var Invitation_Email|null
     */
    private static $instance = null;

    /**
     * Konstruktor privat untuk mencegah instantiasi langsung.
     */
    private function __construct() {
        add_action('woocommerce_order_status_completed', [$this, 'generate_and_store_credentials'], 10, 1);
        add_action('woocommerce_email_order_meta', [$this, 'add_credentials_to_email'], 10, 3);
    }

    /**
     * Mendapatkan instance tunggal dari kelas.
     * @return Invitation_Email Instance tunggal.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Menghasilkan dan menyimpan kredensial login untuk order.
     * @param int $order_id ID order WooCommerce.
     */
    public function generate_and_store_credentials($order_id) {
        if (!get_post_meta($order_id, '_billing_username', true)) {
            $username = 'user_' . $order_id;
            $password = wp_generate_password(12, true, true); // Generate password kuat
            $user_id = get_post_meta($order_id, '_customer_user', true);

            // Simpan ke order meta
            update_post_meta($order_id, '_billing_username', $username);
            update_post_meta($order_id, '_billing_password', $password);

            // Jika user baru, set password (opsional)
            if ($user_id && $user_id > 0) {
                wp_set_password($password, $user_id);
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Generated credentials for Order #' . $order_id . ': Username=' . $username . ', Password=' . $password);
            }
        }
    }

    /**
     * Menyisipkan kredensial login ke email order complete untuk pelanggan.
     * @param WC_Order $order Objek order WooCommerce.
     * @param bool $sent_to_admin Apakah email dikirim ke admin.
     * @param bool $plain_text Apakah format email adalah teks polos.
     */
    public function add_credentials_to_email($order, $sent_to_admin, $plain_text) {
        if ($sent_to_admin) {
            return; // Hanya untuk pelanggan, bukan admin
        }

        $username = get_post_meta($order->get_id(), '_billing_username', true);
        $password = get_post_meta($order->get_id(), '_billing_password', true);

        if ($username && $password) {
            echo '<h2>Login Details</h2>';
            echo '<p><strong>Username:</strong> ' . esc_html($username) . '</p>';
            echo '<p><strong>Password:</strong> ' . esc_html($password) . '</p>';
            echo '<p>Please use these credentials to log in to your account and customize your invitation at <a href="' . esc_url(home_url('/my-account/')) . '">My Account</a>.</p>';
        }
    }

    /**
     * Mencegah kloning instance.
     */
    private function __clone() {
        // Mencegah kloning instance
    }

    /**
     * Mencegah unserialize instance.
     */
    public function __wakeup() {
        // Mencegah unserialize instance
    }
}

// Inisialisasi kelas melalui pola Singleton
Invitation_Email::get_instance();