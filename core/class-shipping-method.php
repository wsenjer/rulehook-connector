<?php

namespace RuleHook\Core;


if ( ! defined( 'ABSPATH' ) ) exit;


class Shipping_Method extends \WC_Shipping_Method
{

    public function __construct()
    {
        $this->id = 'rulehook';
        $this->method_title = __('RuleHook', 'rulehook-connector');
        $this->method_description = __('Dynamic shipping rates based on rules from rulehook.com', 'rulehook-connector');
        $this->enabled = $this->get_option('enabled', 'yes');
        $this->title = $this->get_option('title', __('Shipping', 'rulehook-connector'));

        $this->init();
    }

    private function init()
    {
        $this->init_form_fields();
        $this->init_settings();

        // Save settings
        add_action('woocommerce_update_options_shipping_'.$this->id, [$this, 'process_admin_options']);

        add_filter('woocommerce_package_rates', [$this, 'maybe_override_shipping_rates'], 100, 2);

    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable', 'rulehook-connector'),
                'type' => 'checkbox',
                'label' => __('Enable this shipping method', 'rulehook-connector'),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __('Method Title', 'rulehook-connector'),
                'type' => 'text',
                'description' => __('This controls the title seen by customers at checkout.', 'rulehook-connector'),
                'default' => __('Shipping', 'rulehook-connector'),
                'desc_tip' => true,
            ],
        ];
    }

    public function calculate_shipping($package = [])
    {
        $calculator = new Calculator($this->title);
        $rates = $calculator->calculate($package);

        foreach ($rates as $rate) {
            $this->add_rate($rate->getWoocommerceRate());
        }
    }

    public function admin_options()
    {
        require_once dirname(__FILE__).'/app/frontend/views/app.php';
    }


    /**
     * Filter shipping rates to override other methods with matching codes
     *
     * @param array $rates Array of shipping rates
     * @param array $package The shipping package
     * @return array Modified array of shipping rates
     */
    public function maybe_override_shipping_rates($rates, $package = [])
    {
        if (empty($rates)) {
            return $rates;
        }

        $rulehook_rates = [];
        $other_rates = [];
        $removed_rate_ids = [];

        // Separate RuleHook rates from other rates
        foreach ($rates as $rate_id => $rate) {
            if (str_starts_with($rate_id, 'rulehook:')) {
                $rulehook_rates[$rate_id] = $rate;
            } else {
                $other_rates[$rate_id] = $rate;
            }
        }

        // If we don't have any RuleHook rates, return all rates unchanged
        if (empty($rulehook_rates)) {
            return $rates;
        }
        // Check if any RuleHook rates should override other rates
        foreach ($rulehook_rates as $rulehook_id => $rulehook_rate) {
            list($before, $rate_code) = explode(':', $rulehook_rate->get_id(), 2);
            if (!empty($other_rates[$rate_code])) {
                unset($other_rates[$rate_code]);
            }
        }

        // Combine the remaining rates
        return array_merge($rulehook_rates, $other_rates);

    }



}
