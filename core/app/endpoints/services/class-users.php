<?php

namespace RuleHook\Core\App\Endpoints\Services;

class Users
{
    public function get_roles()
    {
        global $wp_roles;
        $all_roles = apply_filters('editable_roles', $wp_roles->roles);

        $roles = [];
        foreach ($all_roles as $role_key => $role) {
            $roles[] = [
                'id' => $role_key,
                'name' => $role['name'],
            ];
        }

        return $roles;
    }
}
