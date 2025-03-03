<?php
/**
 * Core class for WP Online Invitation plugin.
 *
 * @package WP_Online_Invitation
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class WP_Online_Invitation_Core
 *
 * Main class to initialize all plugin modules using Singleton pattern.
 */
class WP_Online_Invitation_Core {
    /**
     * Singleton instance of the class.
     *
     * @var WP_Online_Invitation_Core|null
     */
    private static $instance = null;

    /**
     * Instances of module classes.
     *
     * @var array
     */
    private $modules = [];

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        $this->setup_modules();
        $this->initialize();
    }

    /**
     * Get the singleton instance of the class.
     *
     * @return WP_Online_Invitation_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Setup all module instances.
     */
    private function setup_modules() {
        $this->modules['cpt'] = WP_Online_Invitation_CPT::get_instance();
        $this->modules['admin'] = WP_Online_Invitation_Admin::get_instance();
        $this->modules['myaccount'] = WP_Online_Invitation_MyAccount::get_instance();
        $this->modules['comment'] = WP_Online_Invitation_Comment::get_instance();
        $this->modules['email'] = WP_Online_Invitation_Email::get_instance();
        $this->modules['guest'] = WP_Online_Invitation_Guest::get_instance();
        $this->modules['rsvp'] = WP_Online_Invitation_RSVP::get_instance();
        $this->modules['acf'] = WP_Online_Invitation_ACF::get_instance();
    }

    /**
     * Initialize all modules.
     */
    private function initialize() {
        foreach ($this->modules as $module) {
            if (method_exists($module, 'init')) {
                $module->init();
            }
        }
    }

    /**
     * Get a specific module instance.
     *
     * @param string $module_name The name of the module.
     * @return object|null
     */
    public function get_module($module_name) {
        return isset($this->modules[$module_name]) ? $this->modules[$module_name] : null;
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}
}
