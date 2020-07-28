<?php

namespace WpAdroit\Wac_Coupon\Admin;

/**
 * Admin Pages Handler
 *
 * Class Menu
 * @package WPGenerator\Admin
 */
class Menu {
    /**
     * Menu constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    }

    /**
     * Register our menu page
     *
     * @return void
     */
    public function admin_menu() {
        
    }

    /**
     * Initialize our hooks for the admin page
     *
     * @return void
     */
    public function init_hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Load scripts and styles for the app
     *
     * @return void
     */
    public function enqueue_scripts() {
        // wp_enqueue_style( 'admin' );
        // wp_enqueue_script( 'admin' );
    }

    /**
     * Handles the main page
     *
     * @return void
     */
    public function plugin_page() {
        echo '<div class="wrap">WP Generator is a plugin generator tool for developers.</div>';
    }

    
}
