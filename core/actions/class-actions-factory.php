<?php

namespace RuleHook\Core\Actions;

class Actions_Factory
{
    public static function make(string $action, array $payload): ?Abstract_Action
    {
        $actions = [
            'show_notice' => Show_Notice_Action::class,
            'rename_method' => Rename_Method_Action::class,
            'set_subtitle' => Subtitle_Action::class,
            'hide_other_methods' => Hide_Other_Methods_Action::class,
        ];

        if (! array_key_exists($action, $actions)) {
            return null;
        }

        return new $actions[$action]($payload);
    }
}
