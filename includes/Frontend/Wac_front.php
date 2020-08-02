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
        // coupon set cart product pricing 👇👇👇
        // add_action("woocommerce_before_calculate_totals", [$this, "wac_product_discount"], 20, 1);
        // check coupon is valid 👇👇👇
        add_filter("woocommerce_coupon_is_valid", [$this, "wac_woocommerce_coupon_is_valid"], 10, 2);
        // OverWrite Coupon Amount 👇👇👇
        add_action('woocommerce_cart_calculate_fees', [$this, "wac_coupon_amount_overwrite"]);
        // Set Cart Total 👇👇👇
        add_filter('woocommerce_cart_subtotal', [$this, "wac_cart_subtotal"], 10, 3);
        // display products price with regular price 👇👇👇
        add_filter('woocommerce_cart_product_price', [$this, "wac_filter_cart_product_pricing"], 10, 2);
    }

    public function wac_filter_cart_product_pricing($formatted_price, $product)
    {
        $_product = wc_get_product($product->get_id());
        if ($formatted_price != wc_price($_product->get_price())) {
            return $formatted_price . '<br /><del>' . wc_price($_product->get_price()) . '</del>';
        } else {
            return $formatted_price;
        }
    }

    public function wac_product_discount($cart, $coupon = null)
    {
        $cartProducts = $cart->get_cart();
        $wac_panel = get_post_meta($coupon->get_id(), "wac_coupon_panel", true);
        $wac_discount = get_post_meta($wac_panel["list_id"], "wac_coupon_discounts", true);
        $wac_filters = get_post_meta($wac_panel["list_id"], "wac_filters", true);
        foreach ($wac_filters as $wac_filter) {
            foreach ($cartProducts as $cartProduct) {
                if ($wac_filter["type"] == "all_products") {
                    $price = $cartProduct["data"]->get_price();
                    if ($wac_discount["type"] == "percentage") {
                        $amount = ($wac_discount["value"] / 100) * $price;
                    } else {
                        $amount = $wac_discount["value"];
                    }
                    $cartProduct["data"]->set_price((int)($price - $amount));
                } else {
                    foreach ($wac_filter["items"] as $item) {
                        var_dump($item);
                    }
                }
            }
        }
    }

    public function wac_cart_subtotal($subtotal, $compound, $cart)
    {
        $store_credit = $this->wac_coupon_amount_overwrite();
        if ($store_credit > 0) {
            $cart->set_discount_total($store_credit);
            $cart->set_total($cart->get_subtotal() - $store_credit);
        }
        return $subtotal;
    }

    public function wac_coupon_amount_overwrite()
    {
        $coupons = WC()->cart->get_applied_coupons();
        $cart = WC()->cart;
        $store_coupons = [];
        $discount_amount = 0;
        foreach ($coupons as $coupon) {
            $couponData = new WC_Coupon($coupon);
            $post_id = $couponData->get_id();
            $post_meta = get_post_meta($post_id, "wac_coupon_panel", true);
            $wac_id = $post_meta["list_id"];
            $wac_main = get_post_meta($wac_id, "wac_coupon_main", true);
            $wac_discounts = get_post_meta($wac_id, "wac_coupon_discounts", true);
            $wac_coupon_type = $wac_main["type"];
            if ($wac_coupon_type == "cart") {
                switch ($wac_discounts["type"]) {
                    case 'percentage':
                        $discount_total = ($wac_discounts["value"] / 100) * $cart->subtotal;
                        break;
                    case 'fixed':
                        $discount_total = $wac_discounts["value"];
                        break;
                }
                if ($post_meta["overwrite_discount"] === null) {
                    $store_coupons[$coupon] = $couponData->get_amount();
                    $cart->add_fee($wac_discounts["label"], -$discount_total);
                    $discount_amount += $couponData->get_amount();
                } else {
                    $store_coupons[$coupon] = $discount_total;
                }
                $discount_amount += $discount_total;
            } else if ($wac_coupon_type == "product") {
                $this->wac_product_discount(WC()->cart, $couponData);
                $store_coupons[$coupon] = $couponData->get_amount();
                $discount_amount += $couponData->get_amount();
            }
        }

        $store_keys = [];
        foreach ($store_coupons as $key => $value) {
            array_push($store_keys, $key);
        }
        $cart->applied_coupons = $store_keys;
        $cart->coupon_discount_totals = $store_coupons;
        return $discount_amount;
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
