<?php

namespace WpAdroit\Wac_Coupon\Frontend;

use WC_Coupon;
use WpAdroit\Wac_Coupon\Frontend\Helpers\Validator;

/**
 * Class Wac_front
 * control front coupon system when user manuelly apply coupon
 */
class Wac_front
{
    public $discount_amount;
    public function __construct()
    {
        // check coupon is valid 
        add_filter("woocommerce_coupon_is_valid", [$this, "wac_woocommerce_coupon_is_valid"], 10, 2);
        // OverWrite Coupon Amount
        add_action('woocommerce_cart_calculate_fees', [$this, "wac_coupon_amount_overwrite"]);
        // display products price with regular price
        add_filter('woocommerce_cart_product_price', [$this, "wac_filter_cart_product_pricing"], 10, 2);
        // woocommerce set product price as product adjustment
        add_filter("woocommerce_product_get_price", [$this, "wac_update_product_price"], 10, 2);
    }

    /**
     * WooCommerce display cart price with <del>#...</del>
     *
     **/
    public function wac_filter_cart_product_pricing($formatted_price, $product)
    {
        $_product = wc_get_product($product->get_id());
        if ($formatted_price != wc_price($_product->get_regular_price())) {
            return $formatted_price . '<br /><del>' . wc_price($_product->get_regular_price()) . '</del>';
        } else {
            return $formatted_price;
        }
    }

    /**
     * WooCommerce update cart subtotal
     *
     **/
    public function wac_cart_subtotal($subtotal, $compound, $cart)
    {
        $store_credit = $this->discount_amount;
        if ($store_credit > 0) {
            $cart->set_discount_total($store_credit);
            $cart->set_total($cart->get_subtotal() - $store_credit);
        }
        return $subtotal;
    }

    /**
     * WooCommerce coupon overwrite
     *
     **/
    public function wac_coupon_amount_overwrite()
    {
        $coupons = WC()->cart->get_applied_coupons();
        $cart = WC()->cart;
        $cartProducts = $cart->get_cart();
        $store_coupons = [];
        $discount_amount = 0;
        foreach ($coupons as $coupon) {
            $couponData = new WC_Coupon($coupon);
            $post_id = $couponData->get_id();
            $post_meta = get_post_meta($post_id, "wac_coupon_panel", true);
            $wac_id = $post_meta["list_id"];
            $wac_main = get_post_meta($wac_id, "wac_coupon_main", true);
            $wac_discounts = get_post_meta($wac_id, "wac_coupon_discounts", true);

            $wac_filters = get_post_meta($post_meta["list_id"], "wac_filters", true);

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
                $discount_total = $couponData->get_amount();
                $store_coupons[$coupon] = $discount_total;
                $discount_amount += $discount_total;
            } else if ($wac_coupon_type == "bulk") {
                foreach ($wac_discounts as $wac_discount) {
                    if ($wac_discount["min"] <= $cart->subtotal && $wac_discount["max"] >= $cart->subtotal) {
                        switch ($wac_discount["type"]) {
                            case 'percentage':
                                $discount_total = ($wac_discount["value"] / 100) * $cart->subtotal;
                                break;
                            case 'fixed':
                                $discount_total = $wac_discount["value"];
                                break;
                        }
                        if ($post_meta["overwrite_discount"] === null) {
                            $store_coupons[$coupon] = $couponData->get_amount();
                            $cart->add_fee("Bulk Discount", -$discount_total);
                            $discount_amount += $couponData->get_amount();
                        } else {
                            $store_coupons[$coupon] = $discount_total;
                        }
                        $discount_amount += $discount_total;
                    }
                }
            }
        }

        $store_keys = [];
        foreach ($store_coupons as $key => $value) {
            array_push($store_keys, $key);
        }
        $cart->applied_coupons = $store_keys;
        $cart->coupon_discount_totals = $store_coupons;
        $this->discount_amount = $discount_amount;
        add_filter('woocommerce_cart_subtotal', [$this, "wac_cart_subtotal"], 10, 3);
    }

    /**
     * WooCommerce update product price if wac_coupon is product adjustment
     *
     **/
    public function wac_update_product_price($price, $product)
    {
        if (is_admin()) {
            return $price;
        }
        $coupons = WC()->cart->applied_coupons;
        if (empty($coupons)) {
            return $price;
        }
        $cartProducts = WC()->cart->get_cart();
        foreach ($coupons as $coupon) {
            $couponData = new WC_Coupon($coupon);
            $post_id = $couponData->get_id();
            $post_meta = get_post_meta($post_id, "wac_coupon_panel", true);
            $wac_id = $post_meta["list_id"];
            $wac_main = get_post_meta($wac_id, "wac_coupon_main", true);
            $wac_discounts = get_post_meta($wac_id, "wac_coupon_discounts", true);
            $wac_filters = get_post_meta($post_meta["list_id"], "wac_filters", true);
            if ($wac_main["type"] != "product") {
                return $price;
            }
            foreach ($wac_filters as $wac_filter) {
                if ($wac_filter["type"] == "products") {
                    foreach ($cartProducts as $cartProduct) {
                        if ($cartProduct["data"]->get_id() == $product->get_id()) {
                            switch ($wac_discounts["type"]) {
                                case 'percentage':
                                    $discount = ($wac_discounts["value"] / 100) * $price;
                                    break;
                                case 'fixed':
                                    $discount = $wac_discounts["value"];
                                    break;
                            }
                            $amount = (int)($price - $discount);
                            $product->set_sale_price($amount);
                            return $amount;
                        }
                    }
                } else if ($wac_filter["type"] == "all_products") {
                    foreach ($cartProducts as $cartProduct) {
                        $discount = 0;
                        switch ($wac_discounts["type"]) {
                            case 'percentage':
                                $discount = ($wac_discounts["value"] / 100) * $price;
                                break;
                            case 'fixed':
                                $discount = $wac_discounts["value"];
                                break;
                        }
                        $amount = (int)($price - $discount);
                        $product->set_sale_price($amount);
                        return $amount;
                    }
                }
            }
        }
        return $price;
    }

    /**
     * WooCommerce Custom Validator
     *
     **/
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
}
