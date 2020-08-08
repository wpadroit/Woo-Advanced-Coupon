<?php

namespace WpAdroit\Wac_Coupon\Admin;

/**
 * Wac_setting class
 * Woocommerce Settings Tabs
 */
class Wac_Setting
{
    public function __construct()
    {
        add_filter('woocommerce_settings_tabs_array', [$this, 'wac_add_settings_tab'], 50);
        add_filter('woocommerce_settings_tabs_wac_woo_setting', [$this, 'wac_settings_tab_content']);
        add_filter('woocommerce_update_options_wac_woo_setting', [$this, 'wac_update_tab_settings']);
    }

    public function wac_add_settings_tab($sections)
    {
        $sections['wac_woo_setting'] = __('Wac Settings', 'wac');
        return $sections;
    }

    public function wac_settings_tab_content()
    {
        woocommerce_admin_fields($this->get_wac_settings());
    }

    public function get_wac_settings()
    {
        $args = [
            "post_type" => "woocoupon",
            'post_status' => 'publish'
        ];
        $wac_data = get_posts($args);
        $wac_coupons = ["0" => "Select Discount"];
        foreach ($wac_data as $data) {
            $wac_coupons[$data->ID] = $data->post_title;
        }
        $settings = array(
            'section_title' => array(
                'name'     => __('Woo Coupon Settings', 'wac'),
                'type'     => 'title',
                'desc'     => 'These settings can effect both coupons',
                'id'       => 'wac_woo_settings_section_title'
            ),
            'wac_first_time_purchase_coupon' => array(
                'name' => __('Coupon for first Purchase', 'wac'),
                'type' => 'select',
                'options' => $wac_coupons,
                'desc' => __('Select a discount from here which you want to enable for new customers', 'wac'),
                'id'   => 'wac_first_time_purchase_coupon'
            ),
            'wac_first_time_purchase_coupon_label' => array(
                'name' => __('First Purchase coupon label', 'wac'),
                'type' => 'text',
                'default' => 'Discounted Amount',
                'desc' => __('Display Label on cart', 'wac'),
                'id'   => 'wac_first_time_purchase_coupon_label'
            ),
            'wac_woo_setting_show_product_discount' => array(
                'name' => __('Show Product Discount', 'wac'),
                'type' => 'select',
                'options' => [
                    "yes" => "Yes",
                    "no" => "No",
                ],
                'desc' => __('Set "no" , if you want to hide product discount', 'wac'),
                'id'   => 'wac_woo_setting_show_product_discount'
            ),
            'wac_woo_setting_multi' => array(
                'name' => __('Multi Coupon', 'wac'),
                'type' => 'select',
                'options' => [
                    "yes" => "Yes",
                    "no" => "No",
                ],
                'desc' => __('Set "no" , if you never want to apply Multi coupon in cart', 'wac'),
                'id'   => 'wac_woo_setting_multi'
            ),
            'wac_woo_setting_url' => array(
                'name' => __('Coupon Url slug Name', 'wac'),
                'type' => 'text',
                'default' => 'coupon',
                'desc' => get_home_url() . '/?<b>' . get_option('wac_woo_setting_url') . '</b>=coupon_code',
                'id'   => 'wac_woo_setting_url'
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wac_woo_settings_section_end'
            )
        );

        return apply_filters('wac_woo_settings', $settings);
    }

    public function wac_update_tab_settings()
    {
        woocommerce_update_options($this->get_wac_settings());
    }
}
