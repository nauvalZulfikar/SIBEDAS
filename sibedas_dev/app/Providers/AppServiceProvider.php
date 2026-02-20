<?php

namespace App\Providers;

use App\Models\Menu;
use App\Services\ServiceGoogleSheet;
use App\Services\ServicePbgTask;
use App\Services\ServiceTabPbgTask;
use App\Services\ServiceTokenSIMBG;
use App\View\Components\Circle;
use Auth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Client::class, function () {
            return new Client();
        });

        $this->app->bind(ServiceTokenSIMBG::class, function ($app) {
            return new ServiceTokenSIMBG();
        });

        $this->app->bind(ServicePbgTask::class, function ($app) {
            return new ServicePbgTask($app->make(Client::class), $app->make(ServiceTokenSIMBG::class));
        });

        $this->app->bind(ServiceTabPbgTask::class, function ($app) {
            return new ServiceTabPbgTask($app->make(Client::class), $app->make(ServiceTokenSIMBG::class));
        });

        $this->app->bind(ServiceGoogleSheet::class, function ($app) {
            return new ServiceGoogleSheet();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('circle', Circle::class);

        View::composer('layouts.partials.sidebar', function ($view) {
            $user = Auth::user();

            if ($user) {
                $menus = Menu::whereHas('roles', function ($query) use ($user) {
                        $query->whereIn('roles.id', $user->roles->pluck('id'))
                            ->where('role_menu.allow_show', 1);
                    })
                    ->with(['children' => function ($query) use ($user) {
                        $query->whereHas('roles', function ($subQuery) use ($user) {
                            $subQuery->whereIn('roles.id', $user->roles->pluck('id'))
                                    ->where('role_menu.allow_show', 1);
                        })
                        ->orderBy('sort_order', 'asc');
                    }])
                    ->whereNull('parent_id') // Ambil hanya menu utama
                    ->orderBy('sort_order', 'asc')
                    ->get();
            } else {
                $menus = collect();
            }

            $view->with('menus', $menus);
        });
    }
}
