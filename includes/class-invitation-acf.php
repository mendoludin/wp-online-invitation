<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kelas untuk mengelola field ACF dalam plugin WP Online Invitation.
 */
class Invitation_ACF {
    /**
     * Menyimpan instance tunggal dari kelas.
     * @var Invitation_ACF|null
     */
    private static $instance = null;

    /**
     * Daftar slug CPT yang mungkin berdasarkan tema.
     * @var array
     */
    private $cpt_slugs = array();

    /**
     * Konstruktor privat untuk mencegah instantiasi langsung.
     */
    private function __construct() {
        // Inisialisasi daftar slug CPT (contoh sementara, bisa diambil dari order)
        $this->cpt_slugs = $this->get_dynamic_cpt_slugs();

        // Inisialisasi hooks ACF
        add_action('acf/init', array($this, 'register_client_fields'));
        add_action('acf/init', array($this, 'register_admin_fields'));
        
        // Tambahkan ACF form head untuk frontend
        add_action('wp_head', array($this, 'add_acf_form_head'));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Invitation_ACF initialized with slugs: ' . implode(', ', $this->cpt_slugs));
        }
    }

    /**
     * Mendapatkan instance tunggal dari kelas.
     * @return Invitation_ACF Instance tunggal.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Mencegah kloning instance.
     */
    private function __clone() {}

    /**
     * Mencegah unserialize instance.
     */
    public function __wakeup() {}

    /**
     * Ambil daftar slug CPT dinamis (contoh sementara).
     * @return array Daftar slug CPT.
     */
    private function get_dynamic_cpt_slugs() {
        // Contoh: slug diambil dari tema yang tersedia
        // Dalam praktiknya, ini bisa diambil dari data order atau konfigurasi
        return apply_filters('invitation_cpt_slugs', array(
            'wedding_invitation_classic',
            'wedding_invitation_modern',
            'wedding_invitation_floral',
            'wedding_invitation_minimalist',
        ));
    }

    /**
     * Tambahkan acf_form_head untuk frontend.
     */
    public function add_acf_form_head() {
        if (function_exists('acf_form_head') && is_page('my-account')) {
            acf_form_head();
        }
    }

    /**
     * Register field ACF untuk klien di My Account (terkait CPT dinamis).
     */
    public function register_client_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        // Buat lokasi dinamis berdasarkan slug CPT
        $locations = array();
        foreach ($this->cpt_slugs as $slug) {
            $locations[] = array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => $slug,
                ),
            );
        }

        acf_add_local_field_group(array(
            'key' => 'group_wedding_invitation_client',
            'title' => 'Wedding Invitation Details (Client)',
            'fields' => array(
                array(
                    'key' => 'field_groom_name',
                    'label' => 'Groom Name',
                    'name' => 'groom_name',
                    'type' => 'text',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_bride_name',
                    'label' => 'Bride Name',
                    'name' => 'bride_name',
                    'type' => 'text',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_wedding_date',
                    'label' => 'Wedding Date',
                    'name' => 'wedding_date',
                    'type' => 'date_picker',
                    'display_format' => 'd/m/Y',
                    'return_format' => 'd/m/Y',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_wedding_location',
                    'label' => 'Wedding Location',
                    'name' => 'wedding_location',
                    'type' => 'text',
                    'required' => 1,
                ),
            ),
            'location' => $locations, // Lokasi dinamis berdasarkan slug CPT
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => array('the_content'),
            'active' => true,
            'description' => 'Fields for clients to manage their wedding invitation.',
        ));
    }

    /**
     * Register field ACF untuk pengaturan admin.
     */
    public function register_admin_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_wedding_invitation_admin',
            'title' => 'Wedding Invitation Admin Settings',
            'fields' => array(
                array(
                    'key' => 'field_default_theme',
                    'label' => 'Default Invitation Theme',
                    'name' => 'default_theme',
                    'type' => 'select',
                    'choices' => array(
                        'classic' => 'Classic',
                        'modern' => 'Modern',
                        'floral' => 'Floral',
                        'minimalist' => 'Minimalist',
                    ),
                    'default_value' => 'classic',
                ),
                array(
                    'key' => 'field_rsvp_limit',
                    'label' => 'RSVP Limit',
                    'name' => 'rsvp_limit',
                    'type' => 'number',
                    'default_value' => 100,
                    'min' => 1,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'invitation-settings',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => 'Admin settings for WP Online Invitation plugin.',
        ));
    }

    /**
     * Render form ACF di My Account untuk klien.
     * @param int $post_id ID dari post CPT yang akan diedit.
     */
    public function render_client_form($post_id) {
        if (!function_exists('acf_form') || !is_user_logged_in()) {
            return;
        }

        // Pastikan hanya pemilik post yang bisa mengedit
        if (get_post_field('post_author', $post_id) != get_current_user_id()) {
            return '<p>You do not have permission to edit this invitation.</p>';
        }

        // Pastikan post_type valid
        $post_type = get_post_type($post_id);
        if (!in_array($post_type, $this->cpt_slugs)) {
            return '<p>Invalid invitation type.</p>';
        }

        acf_form(array(
            'post_id' => $post_id,
            'form' => true,
            'field_groups' => array('group_wedding_invitation_client'),
            'submit_value' => 'Save Invitation',
            'return' => add_query_arg('updated', 'true', get_permalink()),
        ));
    }

    /**
     * Getter untuk daftar slug CPT.
     * @return array Daftar slug CPT.
     */
    public function get_cpt_slugs() {
        return $this->cpt_slugs;
    }
}

// Inisialisasi di file utama plugin
// require_once plugin_dir_path(__FILE__) . 'includes/class-invitation-acf.php';
// Invitation_ACF::get_instance();
