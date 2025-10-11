<?php

namespace RuleHook\Core;

if ( ! defined( 'ABSPATH' ) ) exit;


class Helper
{
    public static function filter_table_rates($rates): array
    {
        $new_rates = [];
        /**
         * @var string $key
         * @var WC_Shipping_Rate $rate
         */
        foreach ($rates as $key => $rate) {
            if ($rate->get_method_id() !== 'rulehook') {
                continue;
            }
            // To unset all methods except for free_shipping, do the following
            $table_rate = $rate;
            $new_rates[$key] = $table_rate;
        }

        return $new_rates;
    }
}
