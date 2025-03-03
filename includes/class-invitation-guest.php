<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kelas untuk mengelola data tamu yang diundang (placeholder sementara).
 * Akan diisi dengan logika manajemen data tamu (input, penyimpanan, dan tampilan) di masa depan.
 */
class Invitation_Guest {
    /**
     * Menyimpan instance tunggal dari kelas.
     * @var Invitation_Guest|null
     */
    private static $instance = null;

    /**
     * Konstruktor privat untuk mencegah instantiasi langsung.
     */
    private function __construct() {
        // Placeholder, akan diisi dengan logika manajemen tamu nanti
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Invitation_Guest initialized (placeholder)');
        }
    }

    /**
     * Mendapatkan instance tunggal dari kelas.
     * @return Invitation_Guest Instance tunggal.
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
