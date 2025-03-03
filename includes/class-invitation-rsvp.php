<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kelas untuk mengelola fungsionalitas RSVP.
 * Menangani formulir RSVP, penyimpanan data, dan tampilan di My Account.
 */
class Invitation_RSVP {
    /**
     * Menyimpan instance tunggal dari kelas.
     * @var Invitation_RSVP|null
     */
    private static $instance = null;

    /**
     * ID Custom Post Type (CPT) yang terkait dengan RSVP.
     * @var string
     */
    private $cpt_id;

    /**
     * Konstruktor privat untuk mencegah instantiasi langsung.
     */
    private function __construct() {
        // Registrasi shortcode, enqueue assets, dan hook lainnya
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_rsvp_assets']);
        add_action('woocommerce_account_undangan_saya_endpoint', [$this, 'display_rsvp_data']);
        add_action('init', [$this, 'save_rsvp_data']); // Tangani pengiriman form
    }

    /**
     * Mendapatkan instance tunggal dari kelas.
     * @return Invitation_RSVP Instance tunggal.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registrasi shortcode dinamis berdasarkan pengaturan admin.
     */
    public function register_shortcodes() {
        $rsvp_settings = get_option('wpoi_rsvp_settings', []);
        foreach ($rsvp_settings as $shortcode_name => $fields) {
            add_shortcode($shortcode_name, function($atts) use ($fields) {
                $atts = shortcode_atts(['cpt_id' => ''], $atts);
                $this->cpt_id = sanitize_text_field($atts['cpt_id']);

                if (!is_user_logged_in() && empty($this->cpt_id)) {
                    return '<p>Silakan login atau masukkan link undangan yang valid.</p>';
                }

                ob_start();
                ?>
                <form method="post" class="wpoi-rsvp-form">
                    <?php wp_nonce_field('wpoi_rsvp_action', 'wpoi_rsvp_nonce'); ?>
                    <input type="hidden" name="cpt_id" value="<?php echo esc_attr($this->cpt_id); ?>">
                    <?php foreach ($fields as $field) {
                        $field_name = sanitize_text_field($field);
                        if ($field_name === 'rsvp_status') {
                            echo '<label>' . esc_html($field_name) . ': 
                                <select name="' . esc_attr($field_name) . '" required>
                                    <option value="hadir">Hadir</option>
                                    <option value="tidak">Tidak Hadir</option>
                                </select></label><br>';
                        } else {
                            echo '<label>' . esc_html($field_name) . ': <input type="text" name="' . esc_attr($field_name) . '" required></label><br>';
                        }
                    } ?>
                    <button type="submit">Kirim RSVP</button>
                </form>
                <?php
                return ob_get_clean();
            });
        }
    }

    /**
     * Muat asset CSS dan JS untuk RSVP.
     */
    public function enqueue_rsvp_assets() {
        $theme_color = get_theme_mod('primary_color', '#0073aa'); // Ambil warna tema utama
        $custom_css = "
            .wpoi-rsvp-form {
                max-width: 500px;
                margin: 20px auto;
                padding: 20px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .wpoi-rsvp-form label {
                display: block;
                margin-bottom: 10px;
                font-size: 16px;
                color: #333;
            }
            .wpoi-rsvp-form input, .wpoi-rsvp-form select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .wpoi-rsvp-form button {
                background-color: $theme_color;
                color: #fff;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .wpoi-rsvp-form button:hover {
                background-color: darken($theme_color, 10%);
            }
            @media (max-width: 600px) {
                .wpoi-rsvp-form {
                    margin: 10px;
                    padding: 15px;
                }
                .wpoi-rsvp-form input, .wpoi-rsvp-form select {
                    font-size: 14px;
                }
            }
        ";
        wp_enqueue_style('wpoi-rsvp-css', WPOI_PLUGIN_URL . 'assets/css/rsvp.css');
        wp_add_inline_style('wpoi-rsvp-css', $custom_css);
        wp_enqueue_script('wpoi-rsvp-js', WPOI_PLUGIN_URL . 'assets/js/rsvp.js', ['jquery'], null, true);
    }

    /**
     * Simpan data RSVP dari formulir.
     */
    public function save_rsvp_data() {
        if (isset($_POST['wpoi_rsvp_nonce']) && wp_verify_nonce($_POST['wpoi_rsvp_nonce'], 'wpoi_rsvp_action')) {
            $cpt_id = sanitize_text_field($_POST['cpt_id']);
            $rsvp_data = [];
            $rsvp_settings = get_option('wpoi_rsvp_settings', []);

            // Ambil field yang dikonfigurasi di admin
            foreach ($rsvp_settings as $shortcode => $fields) {
                foreach ($fields as $field) {
                    $rsvp_data[$field] = sanitize_text_field($_POST[$field] ?? '');
                }
            }
            $rsvp_data['timestamp'] = current_time('mysql');

            // Simpan ke post meta CPT
            $existing_data = get_post_meta($cpt_id, '_rsvp_data', true);
            $existing_data = $existing_data ? $existing_data : [];
            $existing_data[] = $rsvp_data;
            update_post_meta($cpt_id, '_rsvp_data', $existing_data);

            // Sinkronisasi ke user jika login
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $user_rsvp_data = get_user_meta($user_id, 'rsvp_' . $cpt_id, true);
                $user_rsvp_data = $user_rsvp_data ? $user_rsvp_data : [];
                $user_rsvp_data[] = $rsvp_data;
                update_user_meta($user_id, 'rsvp_' . $cpt_id, $user_rsvp_data);
            }

            // Redirect atau pesan sukses (opsional)
            wp_safe_redirect(wp_get_referer() . '?rsvp_success=1');
            exit;
        }
    }

    /**
     * Tampilkan data RSVP di tab Undangan Saya.
     */
    public function display_rsvp_data() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $cpt_ids = get_posts([
                'post_type' => get_option('wpoi_cpt_slug', 'wedding_invitation'),
                'author' => $user_id,
                'fields' => 'ids',
                'posts_per_page' => -1
            ]);

            if ($cpt_ids) {
                echo '<h2>Data RSVP Anda</h2>';
                foreach ($cpt_ids as $cpt_id) {
                    $rsvp_data = get_user_meta($user_id, 'rsvp_' . $cpt_id, true);
                    if ($rsvp_data && is_array($rsvp_data)) {
                        echo '<div class="wpoi-rsvp-entry">';
                        echo '<h3>Undangan ID: ' . esc_html($cpt_id) . '</h3>';
                        echo '<ul>';
                        foreach ($rsvp_data as $entry) {
                            echo '<li>';
                            foreach ($entry as $key => $value) {
                                echo esc_html("$key: $value") . ' | ';
                            }
                            echo '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<p>Tidak ada data RSVP untuk undangan Anda.</p>';
            }
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
Invitation_RSVP::get_instance();
