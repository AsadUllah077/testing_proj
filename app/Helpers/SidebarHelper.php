<?php

// app/Helpers/SidebarHelper.php

use Illuminate\Http\Request;

if (! function_exists('getSubModules')) {
    function getSubModules($module)
    {
        $user = auth('web')->user();
        $sub_modules = [];

        if (! $user) {
            return $sub_modules;
        }

        $moduleConfigs = [
            'users' => [
                'dashboard' => [
                    'name' => 'Dashboard',
                    'url' => route('users.dashboard'),
                    'permission' => 'users.dashboard.view',
                    'active' => Request::routeIs('users.dashboard'),
                ],
                'permission' => [
                    'name' => 'Permission',
                    'url' => route('users.permission.index'),
                    'permission' => 'users.permission.view',
                    'active' => Request::routeIs('users.permission.*'),
                ],
                'role' => [
                    'name' => 'Role',
                    'url' => route('users.role.index'),
                    'permission' => 'users.role.view',
                    'active' => Request::routeIs('users.role.*'),
                ],
                'user' => [
                    'name' => 'User',
                    'url' => route('users.user.index'),
                    'permission' => 'users.user.view',
                    'active' => Request::routeIs('users.user.*'),
                ],
            ],
            'home' => [
                'dashboard' => [
                    'name' => 'Dashboard',
                    'url' => route('home.dashboard'),
                    'permission' => 'home.dashboard.view',
                    'active' => Request::routeIs('home.dashboard'),
                ],
                'company' => [
                    'name' => 'Company',
                    'url' => route('home.companies.index'),
                    'permission' => 'home.companies.view',
                    'active' => Request::routeIs('home.companies.*'),
                ],
            ],
            // Add other modules similarly
        ];

        if (! isset($moduleConfigs[$module])) {
            return $sub_modules;
        }

        foreach ($moduleConfigs[$module] as $item) {
            if ($user->hasPermission($item['permission'])) {
                $sub_modules[] = [
                    'name' => $item['name'],
                    'url' => $item['url'],
                    'is_active' => $item['active']
                        ? 'text-blue-600 border-r-4 border-blue-600'
                        : 'hover:bg-blue-50',
                    'icon' => '', // Add icons as needed
                ];
            }
        }

        return $sub_modules;
    }
}
