<?php


add_filter('woocommerce_cart_product_price', 'dcwd_cart_product_price', 10, 2);
function dcwd_cart_product_price($formatted_price, $product)
{
    if (!class_exists('WooCommerceWholeSalePrices')) {
        return $formatted_price;
    }
    if (!is_user_logged_in()) {
        return $formatted_price;
    }

    // Check whether the current user has a wholesale role that may have a different price.
    $wwp_class = WooCommerceWholeSalePrices::instance();
    $user_wholesale_role = $wwp_class->wwp_wholesale_roles->getUserWholesaleRole();
    if (empty($user_wholesale_role)) {
        return $formatted_price;
    }
    //error_log( 'Price: Product ID: '. $product->get_id() );
    //error_log( 'Price: Formatted price: ' . $formatted_price );

    $_product = wc_get_product($product->get_id());
    //error_log( 'Price: Product price: '. $_product->get_price() );
    //error_log( 'Price: Original price, formatted: ' . wc_price( $_product->get_price() ) );

    return $formatted_price . '<br /><del>' . wc_price($_product->get_price()) . '</del>';
}


add_filter('woocommerce_cart_item_subtotal', 'dcwd_cart_item_subtotal', 10, 3);
function dcwd_cart_item_subtotal($product_subtotal, $cart_item, $cart_item_key)
{
    if (!class_exists('WooCommerceWholeSalePrices')) {
        return $product_subtotal;
    }
    if (!is_user_logged_in()) {
        return $product_subtotal;
    }

    // Check whether the current user has a wholesale role that may have a different price.
    $wwp_class = WooCommerceWholeSalePrices::instance();
    $user_wholesale_role = $wwp_class->wwp_wholesale_roles->getUserWholesaleRole();
    if (empty($user_wholesale_role)) {
        return $product_subtotal;
    }
    //error_log( 'Subtotal: Product subtotal: ' . $product_subtotal );
    //error_log( 'Subtotal: Product ID: '. $cart_item[ 'product_id' ] );

    $_product = wc_get_product($cart_item['product_id']);
    // If it's a variable product get the info about this variation.
    if ($_product->is_type('variable')) {
        $_product = wc_get_product($cart_item['variation_id']);
    }

    //error_log( 'Subtotal: Original price, formatted: ' . wc_price( $_product->get_price() * $cart_item['quantity'] ) );

    return $product_subtotal . '<br /><del>' . wc_price($_product->get_price() * $cart_item['quantity']) . '</del>';
}
