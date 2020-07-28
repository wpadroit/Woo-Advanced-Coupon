<?php

namespace WpAdroit\Wac_Coupon;

/**
 * Class Installer
 * @package WPGenerator
 */
class Installer {
    /**
     * Run the installer
     *
     * @return void
     */
    public function run() {
        $this->add_version();
        $this->create_tables();
    }

    /**
     * Add time and version on DB
     */
    public function add_version() {
        $installed = get_option( 'Woo Advance Coupon _installed' );

        if ( ! $installed ) {
            update_option( 'Woo Advance Coupon _installed', time() );
        }

        update_option( 'Woo Advance Coupon _version', WAC_ASSETS_VERSION );

    }

    /**
     * Create necessary database tables
     *
     * @return void
     */
    public function create_tables() {
        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        
    }

    
}
