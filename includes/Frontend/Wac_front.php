<?php

namespace WpAdroit\Wac_Coupon\Frontend;

use WC_Coupon;
use WpAdroit\Wac_Coupon\Frontend\Helpers\Validator;

/**
 * Class Wac_front
 */
class Wac_front
{
    public $discount_amount;
    public function __construct()
    {
        // add_action("woocommerce_add_to_cart", [$this, "wac_apply_coupon"]);
        // check coupon is valid 
        add_filter("woocommerce_coupon_is_valid", [$this, "wac_woocommerce_coupon_is_valid"], 10, 2);
        // OverWrite Coupon Amount
        add_action('woocommerce_cart_calculate_fees', [$this, "wac_coupon_amount_overwrite"]);
        // display products price with regular price
        add_filter('woocommerce_cart_product_price', [$this, "wac_filter_cart_product_pricing"], 10, 2);
        add_action('init', function () {
            if (isset($_GET['coupon_code'])) {
                $coupon_code = esc_attr($_GET['coupon_code']);
                WC()->session->set('coupon_code', $coupon_code);
            }
        });
        add_action("woocommerce_before_cart", function () {
            $coupons = WC()->cart->get_applied_coupons();
            $code = WC()->session->get('coupon_code');
            if ($code) {
                if (in_array($code, $coupons)) {
                    WC()->session->__unset('coupon_code');
                } else {
                    WC()->cart->apply_coupon($code);
                    WC()->session->__unset('coupon_code');
                }
            }
        });
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
                        $cart_product_id = $cartProduct["data"]->get_id();
                        if ((int)$item["value"] == $cart_product_id) {
                            $price = $cartProduct["data"]->get_price();
                            if ($wac_discount["type"] == "percentage") {
                                $amount = ($wac_discount["value"] / 100) * $price;
                            } else {
                                $amount = $wac_discount["value"];
                            }
                            $cartProduct["data"]->set_price((int)($price - $amount));
                        }
                    }
                }
            }
        }
    }

    public function wac_cart_subtotal($subtotal, $compound, $cart)
    {
        // $store_credit = $this->wac_coupon_amount_overwrite();
        $store_credit = $this->discount_amount;
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
                $product_adj_amount = 0;
                foreach ($wac_filters as $wac_filter) {
                    foreach ($cartProducts as $cartProduct) {
                        if ($wac_filter["type"] == "all_products") {
                            $price = $cartProduct["data"]->get_price();
                            if ($wac_discounts["type"] == "percentage") {
                                $amount = ($wac_discounts["value"] / 100) * $price;
                            } else {
                                $amount = $wac_discounts["value"];
                            }
                            $cartProduct["data"]->set_price((int)($price - $amount));
                            $product_adj_amount += $amount;
                        } else {
                            foreach ($wac_filter["items"] as $item) {
                                if ($item["value"] == $cartProduct["data"]->get_id()) {
                                    $price = $cartProduct["data"]->get_price();
                                    if ($wac_discounts["type"] == "percentage") {
                                        $amount = ($wac_discounts["value"] / 100) * $price;
                                    } else {
                                        $amount = $wac_discounts["value"];
                                    }
                                    $cartProduct["data"]->set_price((int)($price - $amount));
                                    $product_adj_amount += $amount;
                                }
                            }
                        }
                    }
                }
                if ($post_meta["overwrite_discount"] == "yes") {
                    $store_coupons[$coupon] = $product_adj_amount;
                    $discount_amount += $product_adj_amount;
                } else {
                    $store_coupons[$coupon] = $couponData->get_amount();
                    $cart->add_fee("product adjustment", -$product_adj_amount);
                    $discount_amount += $couponData->get_amount();
                    $discount_amount += $product_adj_amount;
                }
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
