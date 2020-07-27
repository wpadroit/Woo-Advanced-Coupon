<?php

namespace WpAdroit\Wac_Coupon;

use WpAdroit\Wac_Coupon\Admin\Wac_Coupon;
use WpAdroit\Wac_Coupon\Admin\Wac_Panels;

/**
 * The admin class
 */
class Admin
{

    /**
     * Initialize the class
     */
    public function __construct()
    {
        $this->dispatch_actions();
        new Wac_Coupon;
        new Wac_Panels;
    }

    /**
     * Dispatch and bind actions
     *
     * @return void
     */
    public function dispatch_actions()
    {
    }
}
