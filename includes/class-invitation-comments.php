<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kelas untuk mengelola komentar tamu di halaman undangan (placeholder sementara).
 * Akan diisi dengan logika manajemen komentar di masa depan.
 */
class Invitation_Comments {
    /**
     * Menyimpan instance tunggal dari kelas.
     * @var Invitation_Comments|null
     */
    private static $instance = null;

    /**
     * Konstruktor privat untuk mencegah instantiasi langsung.
     */
    private function __construct() {
        // Placeholder, akan diisi dengan logika manajemen komentar nanti
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Invitation_Comments initialized (placeholder)');
        }
    }

    /**
     * Mendapatkan instance tunggal dari kelas.
     * @return Invitation_Comments Instance tunggal.
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
