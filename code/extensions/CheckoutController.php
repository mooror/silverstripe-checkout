<?php

/**
 * Extension for Content Controller that provide methods such as cart link and category list
 * to templates
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class CheckoutController extends Extension {
    
    /**
     * Get the current shoppingcart
     * 
     * @return ShoppingCart
     */
    public function getShoppingCart() {
        return ShoppingCart::get();
    }
    
    public function onBeforeInit() {        
        // Set the default currency symbol for this site
        Currency::config()->currency_symbol = Checkout::config()->currency_symbol;
    }
}