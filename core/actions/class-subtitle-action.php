<?php

namespace RuleHook\Core\Actions;

if ( ! defined( 'ABSPATH' ) ) exit;


class Subtitle_Action extends Abstract_Action
{
    const META_KEY = '_rulehook_subtitle';

    /**
     * The subtitle is stored in each rate's own meta by the Calculator and
     * rendered by render_subtitle(), which Core registers once.
     */
    public function execute()
    {
    }

    public static function render_subtitle($method)
    {
        $meta_data = $method->get_meta_data();

        if (empty($meta_data[self::META_KEY])) {
            return;
        }

        echo '<br><small style="color: #9f9f9f;">'.esc_html($meta_data[self::META_KEY]).'</small>';
    }
}
