<?php
if (!defined('ABSPATH')) {
    exit;
}

class Invitation_CPT {
    private static $instance = null;
    private $registered_slugs = array();
    private $default_slug = 'w_inv_default'; // 13 karakter

    private function __construct() {
    add_action('init', [$this, 'register_custom_post_types'], 20);
    add_action('woocommerce_order_status_completed', [$this, 'link_order_to_wedding_invitation'], 10, 1);
    add_filter('invitation_cpt_slugs', [$this, 'provide_cpt_slugs']);
    add_action('init', [$this, 'check_existing_completed_orders'], 30); // Tambahkan ini
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Invitation_CPT hooks registered');
    }
}

    public function check_existing_completed_orders() {
        $orders = wc_get_orders(array(
            'status' => 'completed',
            'limit' => -1,
        ));
        foreach ($orders as $order) {
            if (!get_post_meta($order->get_id(), '_invitation_cpt_id', true)) {
                $this->link_order_to_wedding_invitation($order->get_id());
            }
        }
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_custom_post_types() {
        $this->registered_slugs = $this->get_all_order_slugs();

        if (empty($this->registered_slugs)) {
            $this->registered_slugs[] = $this->default_slug;
        }

        foreach ($this->registered_slugs as $slug) {
            if (strlen($slug) > 20) {
                error_log('Skipping CPT registration for slug "' . $slug . '" - exceeds 20 characters');
                continue;
            }

            $labels = [
                'name' => __(ucwords(str_replace('_', ' ', $slug)), 'wp-online-invitation'),
                'singular_name' => __('Undangan', 'wp-online-invitation'),
                'add_new' => __('Tambah Undangan', 'wp-online-invitation'),
                'add_new_item' => __('Tambah Undangan Baru', 'wp-online-invitation'),
                'edit_item' => __('Edit Undangan', 'wp-online-invitation'),
                'view_item' => __('Lihat Undangan', 'wp-online-invitation'),
            ];

            $args = [
                'labels' => $labels,
                'public' => true,
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail'],
                'rewrite' => ['slug' => $slug],
                'show_in_rest' => true,
            ];

            register_post_type($slug, $args);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Registered CPT with slug: ' . $slug);
            }
        }
    }

    public function link_order_to_wedding_invitation($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('Failed to get order #' . $order_id);
            return;
        }

        $user_id = $order->get_user_id();
        $existing_invitation = get_post_meta($order_id, '_invitation_cpt_id', true);
        if ($existing_invitation) {
            error_log('CPT already exists for Order #' . $order_id . ': ' . $existing_invitation);
            return;
        }

        $theme = $this->get_theme_from_order($order);
        $slug = 'w_inv_' . sanitize_key($theme); // Prefix lebih pendek

        if (!in_array($slug, $this->registered_slugs)) {
            $this->registered_slugs[] = $slug;
            $this->register_single_cpt($slug);
        }

        $invitation_args = [
            'post_title' => 'Undangan untuk Order #' . $order_id,
            'post_type' => $slug,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ];

        $invitation_id = wp_insert_post($invitation_args);

        if ($invitation_id && !is_wp_error($invitation_id)) {
            update_post_meta($order_id, '_invitation_cpt_id', $invitation_id);
            update_post_meta($order_id, '_invitation_cpt_slug', $slug);
            update_post_meta($invitation_id, '_related_order_id', $order_id);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Created CPT for Order #' . $order_id . ' with ID ' . $invitation_id . ' using slug ' . $slug);
            }
        } else {
            error_log('Failed to create CPT for Order #' . $order_id . ': ' . (is_wp_error($invitation_id) ? $invitation_id->get_error_message() : 'Unknown error'));
        }
    }

    private function register_single_cpt($slug) {
        if (strlen($slug) > 20) {
            error_log('Cannot register CPT for slug "' . $slug . '" - exceeds 20 characters');
            return;
        }

        $labels = [
            'name' => __(ucwords(str_replace('_', ' ', $slug)), 'wp-online-invitation'),
            'singular_name' => __('Undangan', 'wp-online-invitation'),
            'add_new' => __('Tambah Undangan', 'wp-online-invitation'),
            'add_new_item' => __('Tambah Undangan Baru', 'wp-online-invitation'),
            'edit_item' => __('Edit Undangan', 'wp-online-invitation'),
            'view_item' => __('Lihat Undangan', 'wp-online-invitation'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => $slug],
            'show_in_rest' => true,
        ];

        register_post_type($slug, $args);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Registered single CPT with slug: ' . $slug);
        }
    }

    private function get_theme_from_order($order) {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $theme = get_post_meta($product->get_id(), '_invitation_theme', true);
            if ($theme) {
                error_log('Theme found for Order #' . $order->get_id() . ': ' . $theme);
                return $theme;
            }
        }
        error_log('No theme found for Order #' . $order->get_id() . ', using default: classic');
        return 'classic';
    }

    private function get_all_order_slugs() {
        $slugs = array();
        $orders = wc_get_orders(array(
            'status' => 'completed',
            'limit' => -1,
        ));

        if (empty($orders)) {
            error_log('No completed orders found in database');
        } else {
            foreach ($orders as $order) {
                $slug = get_post_meta($order->get_id(), '_invitation_cpt_slug', true);
                if ($slug && !in_array($slug, $slugs)) {
                    $slugs[] = $slug;
                    error_log('Found slug from order #' . $order->get_id() . ': ' . $slug);
                } else {
                    error_log('No slug found for order #' . $order->get_id());
                    $this->link_order_to_wedding_invitation($order->get_id());
                }
            }
        }

        if (empty($slugs)) {
            $slugs[] = $this->default_slug;
            error_log('No dynamic slugs found, using default: ' . $this->default_slug);
        }

        return $slugs;
    }

    public function provide_cpt_slugs($slugs) {
        return array_unique(array_merge($slugs, $this->registered_slugs));
    }

    private function __clone() {}
    public function __wakeup() {}
}

Invitation_CPT::get_instance();