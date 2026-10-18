<?php

namespace RuleHook\Core\App\Endpoints;

if ( ! defined( 'ABSPATH' ) ) exit;


use RuleHook\Core\Outcomes\Order_Backfill;

/**
 * "Import past orders" on the admin page (margin spec §4.6).
 */
class Start_Order_Backfill_Endpoint extends Abstract_Endpoint
{
    public function callback($data)
    {
        if (! current_user_can('manage_woocommerce')) {
            $this->abort('You are not allowed to do this.');
        }

        try {
            $state = Order_Backfill::start();
        } catch (\RuntimeException $e) {
            $this->abort($e->getMessage());
        }

        $this->output(['ok' => true, 'orderBackfill' => $state]);
    }

    public function action()
    {
        return 'rulehook_start_order_backfill';
    }
}
