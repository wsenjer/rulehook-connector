<?php

namespace RuleHook\Core\Scan;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Api\Client;
use RuleHook\Core\Calculator;
use RuleHook\Core\Constants;

/**
 * "Scan shipping rates": finds the services the store's shipping plugins
 * offer before it has any checkouts, and sends them to RuleHook so they can
 * be picked as rule targets.
 *
 * For each zone it builds a sample destination, calculates rates for the
 * lightest and the heaviest shippable product, and records every rate that
 * isn't RuleHook's own. Live-rate plugins vary their services by weight and
 * destination, so this speeds up the cold start but won't find everything.
 */
class Rate_Scanner
{
    const DISCOVER_BATCH_SIZE = 100;

    /**
     * Postcode lengths used to fill wildcards like `30*`. Other countries
     * get the wildcard's prefix plus one zero.
     */
    const POSTCODE_LENGTHS = [
        'AU' => 4, 'NZ' => 4, 'AT' => 4, 'BE' => 4, 'CH' => 4, 'DK' => 4, 'NO' => 4, 'ZA' => 4,
        'US' => 5, 'DE' => 5, 'FR' => 5, 'IT' => 5, 'ES' => 5, 'FI' => 5, 'MX' => 5, 'MY' => 5,
        'IN' => 6, 'SG' => 6,
    ];

    /**
     * @var string Country for the "rest of the world" zone
     */
    private $rest_of_world_country;

    public function __construct(string $rest_of_world_country)
    {
        $this->rest_of_world_country = strtoupper($rest_of_world_country);
    }

    /**
     * @return array{zones: int, packages: int, failed_packages: int, services: int, rates: array<int, array>}
     */
    public function scan(): array
    {
        $products = $this->sample_products();
        $summary = ['zones' => 0, 'packages' => 0, 'failed_packages' => 0, 'services' => 0, 'rates' => []];

        if (empty($products)) {
            return $summary;
        }

        if (function_exists('wc_load_cart') && WC()->cart === null) {
            // Some carrier plugins read the cart or customer; give them one.
            wc_load_cart();
        }

        $found = [];
        Calculator::suspend(true);

        try {
            foreach ($this->zones() as $zone) {
                $destination = $this->sample_destination($zone);

                if ($destination === null) {
                    continue;
                }

                $summary['zones']++;

                foreach ($products as $product) {
                    $summary['packages']++;

                    try {
                        $rates = $this->rates_for($product, $destination);
                    } catch (\Throwable $e) {
                        $summary['failed_packages']++;
                        continue;
                    }

                    foreach ($rates as $rate) {
                        $external_rate = $this->external_rate($rate, $zone->get_zone_name());

                        if ($external_rate !== null) {
                            $key = $external_rate['method_id'].'|'.$external_rate['instance_id'].'|'.$external_rate['id'];
                            $found[$key] = $external_rate;
                        }
                    }
                }
            }
        } finally {
            Calculator::suspend(false);

            if (WC()->session) {
                WC()->session->set('shipping_for_package_rulehook_scan', null);
            }
        }

        $summary['rates'] = array_values($found);
        $summary['services'] = count(array_unique(array_map(function ($rate) {
            return $rate['method_id'].'|'.$rate['id'];
        }, $summary['rates'])));

        return $summary;
    }

    /**
     * Sends the scan's results to RuleHook, which records them like rates
     * seen at checkout.
     *
     * @throws \RuleHook\Core\Api\Exception
     */
    public function send(array $rates): void
    {
        $client = new Client(Constants::api_url(), (string) get_option(Constants::API_KEY_KEY));

        foreach (array_chunk($rates, self::DISCOVER_BATCH_SIZE) as $batch) {
            $client->request('POST', 'v1/external-rates/discover', [
                'store_domain' => wp_parse_url(home_url(), PHP_URL_HOST),
                'external_rates' => $batch,
            ]);
        }
    }

    /**
     * @return array<int, \WC_Shipping_Zone>
     */
    private function zones(): array
    {
        $zones = [];

        foreach (\WC_Shipping_Zones::get_zones() as $zone_data) {
            $zone = \WC_Shipping_Zones::get_zone((int) $zone_data['id']);

            if ($zone) {
                $zones[] = $zone;
            }
        }

        $zones[] = new \WC_Shipping_Zone(0);

        return $zones;
    }

    /**
     * The first country and state of a zone; for a postcode range its start,
     * and for a wildcard like `30*` the `*` replaced with zeros.
     *
     * @return array|null Null when the zone has no methods to scan
     */
    public function sample_destination(\WC_Shipping_Zone $zone): ?array
    {
        if (empty($zone->get_shipping_methods(true))) {
            return null;
        }

        $country = '';
        $state = '';
        $postcode = '';
        $continent = '';

        foreach ($zone->get_zone_locations() as $location) {
            $code = (string) $location->code;

            switch ($location->type) {
                case 'country':
                    $country = $country ?: $code;
                    break;
                case 'state':
                    if ($state === '') {
                        $parts = explode(':', $code, 2);
                        $country = $country ?: $parts[0];
                        $state = $parts[1] ?? '';
                    }
                    break;
                case 'postcode':
                    $postcode = $postcode ?: $code;
                    break;
                case 'continent':
                    $continent = $continent ?: $code;
                    break;
            }
        }

        if ($country === '' && $continent !== '') {
            $continents = WC()->countries->get_continents();
            $country = $continents[$continent]['countries'][0] ?? '';
        }

        if ($country === '') {
            $country = $zone->get_id() === 0 ? $this->rest_of_world_country : WC()->countries->get_base_country();
        }

        return [
            'country' => $country,
            'state' => $state,
            'postcode' => $this->sample_postcode($postcode, $country),
            'city' => '',
            'address' => '',
            'address_1' => '',
            'address_2' => '',
        ];
    }

    public function sample_postcode(string $postcode, string $country): string
    {
        if ($postcode === '') {
            return '';
        }

        if (strpos($postcode, '...') !== false) {
            return trim(explode('...', $postcode, 2)[0]);
        }

        if (strpos($postcode, '*') !== false) {
            $prefix = str_replace('*', '', $postcode);
            $length = self::POSTCODE_LENGTHS[$country] ?? strlen($prefix) + 1;

            return str_pad($prefix, max($length, strlen($prefix) + 1), '0');
        }

        return $postcode;
    }

    /**
     * The lightest and the heaviest shippable product with a weight.
     *
     * @return array<int, \WC_Product>
     */
    private function sample_products(): array
    {
        $products = [];

        foreach (['ASC', 'DESC'] as $order) {
            $ids = get_posts([
                'post_type' => ['product', 'product_variation'],
                'post_status' => 'publish',
                'posts_per_page' => 20,
                'fields' => 'ids',
                'meta_key' => '_weight',
                'orderby' => 'meta_value_num',
                'order' => $order,
                'meta_query' => [['key' => '_weight', 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL(10,4)']],
                'no_found_rows' => true,
            ]);

            foreach ($ids as $id) {
                $product = wc_get_product($id);

                if ($product && $product->needs_shipping() && ! $product->is_type(['variable', 'grouped', 'external'])) {
                    $products[$product->get_id()] = $product;
                    break;
                }
            }
        }

        return array_values($products);
    }

    /**
     * Calculates rates for one product, quantity 1, without WooCommerce's
     * rate cache. RuleHook itself is suspended, so these are the store's own
     * rates.
     */
    private function rates_for(\WC_Product $product, array $destination): array
    {
        $price = (float) $product->get_price();
        $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        $variation_id = $product->is_type('variation') ? $product->get_id() : 0;
        $key = md5('rulehook_scan_'.$product->get_id());

        $package = [
            'contents' => [
                $key => [
                    'key' => $key,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'variation' => [],
                    'quantity' => 1,
                    'data' => $product,
                    'data_hash' => '',
                    'line_tax_data' => ['subtotal' => [], 'total' => []],
                    'line_subtotal' => $price,
                    'line_subtotal_tax' => 0,
                    'line_total' => $price,
                    'line_tax' => 0,
                ],
            ],
            'contents_cost' => $price,
            'applied_coupons' => [],
            'user' => ['ID' => get_current_user_id()],
            'destination' => $destination,
            'cart_subtotal' => $price,
            // Makes the package hash unique, so WooCommerce's cache is bypassed.
            'rulehook_scan' => wp_generate_uuid4(),
        ];

        $result = WC()->shipping()->calculate_shipping_for_package($package, 'rulehook_scan');

        return is_array($result['rates'] ?? null) ? $result['rates'] : [];
    }

    private function external_rate($rate, string $zone_name): ?array
    {
        if (! $rate instanceof \WC_Shipping_Rate || $rate->get_method_id() === 'rulehook') {
            return null;
        }

        $id = (string) $rate->get_id();
        $method_id = (string) $rate->get_method_id();

        if ($id === '' || $method_id === '' || mb_strlen($id) > 255 || mb_strlen($method_id) > 255) {
            return null;
        }

        return [
            'id' => $id,
            'method_id' => $method_id,
            'instance_id' => (string) $rate->get_instance_id(),
            'label' => mb_substr(wp_strip_all_tags((string) $rate->get_label()), 0, 255),
            'cost' => max(0, (float) $rate->get_cost()),
            'meta' => ['zone' => $zone_name],
        ];
    }
}
