<?php

namespace RuleHook\Core\Actions;

use RuleHook\Core\Helper;

if ( ! defined( 'ABSPATH' ) ) exit;


class Hide_Other_Methods_Action extends Abstract_Action
{
    public function execute()
    {
        add_filter('woocommerce_package_rates', [$this, 'hide_shipping_when_rulehook_is_available'], 10, 2);
    }

    /**
     * Hide shipping rates when free shipping is available
     *
     * @param  array  $rates  Array of rates found for the package
     * @param  array  $package  The package array/object being shipped
     * @return array of modified rates
     */
    public function hide_shipping_when_rulehook_is_available($rates, $package)
    {
        $new_rates = Helper::filter_table_rates($rates);

        if (count($new_rates) > 0) {
            return $new_rates;
        }

        return $rates;
    }
}
