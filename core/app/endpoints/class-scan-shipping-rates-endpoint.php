<?php

namespace RuleHook\Core\App\Endpoints;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Api\Exception;
use RuleHook\Core\Constants;
use RuleHook\Core\Scan\Rate_Scanner;

class Scan_Shipping_Rates_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {
        if (empty(get_option(Constants::API_KEY_KEY))) {
            $this->abort('Connect your store to RuleHook first.');
        }

        $country = strtoupper($data['restOfWorldCountry'] ?? '');

        if (! array_key_exists($country, WC()->countries->get_countries())) {
            $this->abort('Choose a country for the "rest of the world" zone.');
        }

        if (function_exists('wc_set_time_limit')) {
            wc_set_time_limit(300);
        }

        $scanner = new Rate_Scanner($country);
        $result = $scanner->scan();

        if (! empty($result['rates'])) {
            try {
                $scanner->send($result['rates']);
            } catch (Exception $e) {
                $this->abort('The scan found rates, but sending them to RuleHook failed: '.$e->getMessage());
            }
        }

        $services = [];
        foreach ($result['rates'] as $rate) {
            $services[$rate['method_id'].'|'.$rate['id']] = [
                'method_id' => $rate['method_id'],
                'id' => $rate['id'],
                'label' => $rate['label'],
            ];
        }

        $this->output([
            'ok' => true,
            'zones' => $result['zones'],
            'packages' => $result['packages'],
            'failedPackages' => $result['failed_packages'],
            'servicesFound' => $result['services'],
            'services' => array_values($services),
        ]);
    }

    public function action()
    {
        return 'rulehook_scan_shipping_rates';
    }

    protected function get_required_fields()
    {
        return ['restOfWorldCountry'];
    }
}
