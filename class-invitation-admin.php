<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kelas untuk mengelola tampilan dan pengaturan admin.
 * Menangani halaman pengaturan dan opsi seperti slug CPT.
 */
class Invitation_Admin {
    /**
     * Menyimpan instance tunggal dari kelas.
     * @var Invitation_Admin|null
     */
    private static $instance = null;

    /**
     * Konstruktor privat untuk mencegah instantiasi langsung.
     */
    private function __construct() {
        // Registrasi hook
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Mendapatkan instance tunggal dari kelas.
     * @return Invitation_Admin Instance tunggal.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Menambahkan halaman pengaturan ke menu admin.
     */
    public function add_admin_menu() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Attempting to add admin menu: Invitation Settings');
        }

        // Tambahkan menu utama
        $menu = add_menu_page(
            'WP Online Invitation Settings',
            'Invitation Settings',
            'manage_options',
            'wpoi-settings',
            [$this, 'settings_page'],
            'dashicons-email',
            20
        );

        if ($menu && (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log('Admin menu added successfully with slug: wpoi-settings');
        }

        // Tambahkan submenu RSVP
        add_submenu_page(
            'wpoi-settings',
            'RSVP Settings',
            'RSVP Settings',
            'manage_options',
            'rsvp-settings',
            [$this, 'render_rsvp_settings']
        );
    }

    /**
     * Mendaftarkan pengaturan untuk opsi admin.
     */
    public function register_settings() {
        register_setting('wpoi_settings_group', 'wpoi_cpt_slug', [
            'default' => 'wedding_invitation',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_section('wpoi_cpt_section', 'Custom Post Type Settings', null, 'wpoi-settings');

        add_settings_field('wpoi_cpt_slug', 'CPT Slug', [$this, 'cpt_slug_field'], 'wpoi-settings', 'wpoi_cpt_section');
    }

    /**
     * Menampilkan field input untuk slug CPT di halaman pengaturan.
     */
    public function cpt_slug_field() {
        $slug = get_option('wpoi_cpt_slug', 'wedding_invitation');
        echo '<input type="text" name="wpoi_cpt_slug" value="' . esc_attr($slug) . '" class="regular-text" />';
        echo '<p class="description">Masukkan slug untuk Custom Post Type (misalnya wedding_invitation). Perubahan akan diterapkan setelah menyimpan dan flush permalink.</p>';
    }

    /**
     * Menampilkan halaman pengaturan admin.
     */
    public function settings_page() {
    error_log('Rendering settings page');
         ?>
         <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wpoi_settings_group');
                do_settings_sections('wpoi-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Menampilkan halaman pengaturan RSVP.
     */
    public function render_rsvp_settings() {
        if (isset($_POST['wpoi_rsvp_submit']) && check_admin_referer('wpoi_rsvp_settings', 'wpoi_rsvp_nonce')) {
            $shortcode_name = sanitize_text_field($_POST['shortcode_name']);
            $fields = array_map('sanitize_text_field', explode(',', $_POST['fields']));
            $rsvp_settings = get_option('wpoi_rsvp_settings', []);
            $rsvp_settings[$shortcode_name] = array_filter($fields); // Hapus field kosong
            update_option('wpoi_rsvp_settings', $rsvp_settings);
            add_settings_error('wpoi_rsvp_messages', 'wpoi_rsvp_success', 'Shortcode berhasil dibuat!', 'success');
        }

        $rsvp_settings = get_option('wpoi_rsvp_settings', []);
        ?>
        <div class="wrap">
            <h1>RSVP Settings</h1>
            <?php settings_errors('wpoi_rsvp_messages'); ?>
            <form method="post">
                <?php wp_nonce_field('wpoi_rsvp_settings', 'wpoi_rsvp_nonce'); ?>
                <label>Shortcode Name: <input type="text" name="shortcode_name" required></label><br>
                <label>Fields (comma-separated, e.g., guest_email,rsvp_status): <input type="text" name="fields" placeholder="guest_email,rsvp_status" required></label><br>
                <button type="submit" name="wpoi_rsvp_submit">Generate Shortcode</button>
            </form>
            <h3>Shortcodes Terdaftar</h3>
            <ul>
                <?php foreach ($rsvp_settings as $name => $fields): ?>
                    <li>[<?php echo esc_html($name); ?> cpt_id="CPT_ID"] - Fields: <?php echo esc_html(implode(', ', $fields)); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Gunakan shortcode seperti <code>[nama_shortcode cpt_id="123"]</code> di Elementor atau halaman.</p>
        </div>
        <?php
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
Invitation_Admin::get_instance();