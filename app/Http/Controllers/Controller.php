<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

abstract class Controller
{
    protected array $permissions = [];

    public function __construct()
    {
        if (!Auth::check()) {
            return;
        }
        $this->setUserPermissions();
    }

    protected function setUserPermissions()
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $this->permissions = Cache::remember('user_permissions_' . $user->id, 86400, function () use ($user) {
            $menus = $user->roles()
                ->with(['menus' => function ($query) {
                    $query->select('menus.id', 'menus.name')
                          ->withPivot(['allow_show' ,'allow_create', 'allow_update', 'allow_destroy']);
                }])
                ->get()
                ->pluck('menus')
                ->flatten()
                ->unique('id');

            $permissions = [];
            foreach ($menus as $menu) {
                $permissions[$menu->id] = [
                    'allow_show' => $menu->pivot->allow_show ?? 0,
                    'allow_create' => $menu->pivot->allow_create ?? 0,
                    'allow_update' => $menu->pivot->allow_update ?? 0,
                    'allow_destroy' => $menu->pivot->allow_destroy ?? 0,
                ];
            }
            return $permissions;
        });

        // Share permissions globally in views
        view()->share('permissions', $this->permissions);
    }

    public function getPermissions()
    {
        return $this->permissions;
    }
}
