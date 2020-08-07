<?php

namespace WpAdroit\Wac_Coupon\Frontend\Helpers;

/**
 * Coupon Apply class
 */
class Apply
{
    /**
     * Apply Discount
     * $coupon is wac_coupon
     **/
    public function apply_discount($coupon)
    {
        $cart = WC()->cart;
        $wac_main = get_post_meta($coupon, "wac_coupon_main", true);
        $wac_discounts = get_post_meta($coupon, "wac_coupon_discounts", true);
        $wac_coupon_type = $wac_main["type"];
        $discount_amount = 0;
        $discount_label = get_option("wac_first_time_purchase_coupon_label");
        if ($wac_coupon_type == "cart") {
            switch ($wac_discounts["type"]) {
                case 'percentage':
                    $discount_total = ($wac_discounts["value"] / 100) * $cart->subtotal;
                    break;
                case 'fixed':
                    $discount_total = $wac_discounts["value"];
                    break;
            }
            $discount_amount += $discount_total;
            if (empty($wac_discounts["label"] || $wac_discounts["label"] == '')) {
                $discount_label = $wac_discounts["label"];
            }
        } else if ($wac_coupon_type == "product") {
            WC()->session->set("wac_product_coupon", "yes");
            return false;
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
                    $discount_amount += $discount_total;
                }
            }
        }
        return [
            "label" => $discount_label,
            "amount" => $discount_amount
        ];
    }
}
