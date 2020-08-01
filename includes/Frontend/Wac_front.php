<?php

namespace WpAdroit\Wac_Coupon\Frontend;

use WC_Cart;
use WC_Coupon;
use WC_Discounts;
use WpAdroit\Wac_Coupon\Frontend\Helpers\Validator;

/**
 * Class Wac_front
 */
class Wac_front
{
    public function __construct()
    {
        // add_action("woocommerce_add_to_cart", [$this, "wac_apply_coupon"]);
        // add_action("woocommerce_before_calculate_totals", [$this, "wac_coupon_amount_overwrite"]);
        // add_action("wp", [$this, "wac_apply_coupon"]);

        // check coupon is valid 👇👇👇
        add_filter("woocommerce_coupon_is_valid", [$this, "wac_woocommerce_coupon_is_valid"], 10, 2);
        // OverWrite Coupon Amount 👇👇👇
        add_filter('woocommerce_cart_subtotal', [$this, "wac_coupon_amount_overwrite"], 10, 3);
    }

    public function wac_coupon_amount_overwrite($subtotal, $component, $cart)
    {
        $coupons = WC()->cart->get_applied_coupons();
        foreach ($coupons as $coupon) {
            $couponData = new WC_Coupon($coupon);
            $post_id = $couponData->get_id();
            $post_meta = get_post_meta($post_id, "wac_coupon_panel", true);
            $store_coupons = [];
            $wac_id = $post_meta["list_id"];
            $wac_main = get_post_meta($wac_id, "wac_coupon_main", true);
            $wac_discounts = get_post_meta($wac_id, "wac_coupon_discounts", true);
            $wac_coupon_type = $wac_main["type"];
            if ($post_meta["overwrite_discount"] === null) {
                if ($wac_coupon_type == "cart") {
                    switch ($wac_discounts["type"]) {
                        case 'percentage':
                            $discount_total = ($wac_discounts["value"] / 100) * $cart->subtotal;
                            break;
                        case 'fixed':
                            $discount_total = $wac_discounts["value"];
                            break;
                    }
                    $cart->add_fee($wac_discounts["label"], -$discount_total);
                }
            }
            // $store_credit = 50;
            // $coupon_name = 'test';
            // $coupon = array($coupon_name => $store_credit, "hello" => 16);
            // $cart->applied_coupons = array($coupon_name, "hello");
            // $cart->set_discount_total($store_credit);
            // $cart->set_total($cart->get_subtotal() - $store_credit);
            // $cart->coupon_discount_totals = $coupon;
        }
        return $subtotal;
    }

    public function wac_woocommerce_coupon_is_valid($valid, $coupon)
    {
        if (!$valid)
            return false;

        $validator = Validator::check($coupon->get_code(), $coupon->get_id());

        if ($validator)
            return true;
        else
            return false;
    }

    public function wac_apply_coupon()
    {
        $cart = WC()->cart;
        $coupons = new \WP_Query([
            'post_type' => 'shop_coupon'
        ]);
        $validator = true;
        if ($coupons->have_posts()) {
            while ($coupons->have_posts()) {
                $coupons->the_post();
                $validator = Validator::check(get_the_title(), get_the_ID());
                if ($validator) {
                    // echo get_the_title();
                }
            }
        }
        $products = $cart->get_cart();
        wp_reset_postdata();
    }

    public function display_code($code = null)
    {
        echo "<pre>";
        print_r($code);
        echo "</pre>";
    }
}
