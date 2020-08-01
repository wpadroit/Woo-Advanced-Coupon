<?php

namespace WpAdroit\Wac_Coupon;

use WpAdroit\Wac_Coupon\Frontend\Wac_front;

/**
 * Frontend handler class
 */
class Frontend
{
    /**
     * Frontend constructor.
     */
    public function __construct()
    {
        new Wac_front;
    }
}
