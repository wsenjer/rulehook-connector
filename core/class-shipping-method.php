<?php

namespace RuleHook\Core;

if (! defined('ABSPATH')) {
    exit;
}

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
}
