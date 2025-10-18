<?php

namespace RuleHook\Core\App\Endpoints;

if ( ! defined( 'ABSPATH' ) ) exit;


class Save_Shipping_Settings_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {
        if (! isset($data['shippingMethodEnabled'])) {
            $this->abort('Missing required fields');
        }

        $enabled = filter_var($data['shippingMethodEnabled'], FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no';

        // Get the existing settings
        $rulehook_settings = get_option('woocommerce_rulehook_settings', []);

        // Update with new values
        $rulehook_settings['enabled'] = $enabled;

        // Save to WooCommerce options
        update_option('woocommerce_rulehook_settings', $rulehook_settings);

        $this->ok();
    }

    public function action()
    {
        return 'rulehook_save_shipping_settings';
    }

    public function get_required_fields()
    {
        return [
            'shippingMethodEnabled',
        ];
    }
}
