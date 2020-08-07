<?php

namespace WpAdroit\Wac_Coupon\Frontend;

use WpAdroit\Wac_Coupon\Frontend\Helpers\Apply;
use WpAdroit\Wac_Coupon\Frontend\Helpers\Validator;

/**
 * Class Wac_auto
 * control auto coupon system
 */
class Wac_auto
{

    public function __construct()
    {
        if (is_admin()) {
            return;
        }
        // add_action("woocommerce_before_cart", [$this, "wac_do_before_cart"]);
        add_action('woocommerce_cart_calculate_fees', [$this, "wac_auto_coupon_on_cart"]);
        add_filter("woocommerce_product_get_price", [$this, "wac_change_price"], 10, 2);
    }

    /**
     * woocommerce auto coupon function
     **/
    public function wac_auto_coupon_on_cart()
    {
        $this->wac_first_order();
    }

    /**
     * control first purchase features
     **/
    public function wac_first_order()
    {
        if (!is_user_logged_in())
            return;

        $user = wp_get_current_user();
        $user_id = $user->ID;
        $coupon = get_option("wac_first_time_purchase_coupon");
        if ($coupon == 0) {
            return;
        }
        $args = array(
            'customer_id' => $user_id
        );
        $orders = wc_get_orders($args);
        if (count($orders) == 0) {
            $validate = Validator::check(null, null, $coupon);
            if ($validate) {
                $apply = new Apply;
                $res_data = $apply->apply_discount($coupon);
                if ($res_data) {
                    $cart = WC()->cart;
                    $cart->add_fee($res_data["label"], -$res_data["amount"]);
                }
            }
        }
    }

    /**
     * woocommerce product change price
     **/
    public function wac_change_price($price, $product)
    {
        $data = WC()->session->get("wac_product_coupon");
        if ($data) {
            $coupon = get_option("wac_first_time_purchase_coupon");
            $wac_main = get_post_meta($coupon, "wac_coupon_main", true);
            $wac_coupon_type = $wac_main["type"];
            $wac_discounts = get_post_meta($coupon, "wac_coupon_discounts", true);
            $wac_filters = get_post_meta($coupon, "wac_filters", true);
            if ($wac_coupon_type == "product") {
                foreach ($wac_filters as $wac_filter) {
                    if ($wac_filter["type"] == "products") {
                        foreach ($wac_filter["items"] as $wacproducts) {
                            if ($wacproducts["value"] == $product->get_id()) {
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
                    } elseif ($wac_filter["type"] == "all_products") {
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
}
