<?php

namespace App\Providers;

use App\Models\Menu;
use App\Services\ServiceGoogleSheet;
use App\Services\ServicePbgTask;
use App\Services\ServiceTabPbgTask;
use App\Services\ServiceTokenSIMBG;
use App\View\Components\Circle;
use App\Auth\CachedUserProvider;
use Auth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
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
        Auth::provider('cached-eloquent', function ($app, array $config) {
            return new CachedUserProvider($app['hash'], $config['model']);
        });

        Blade::component('circle', Circle::class);

        // Vector-tile proxy rate limit (Phase 8). 120 tiles/min/user covers
        // a vigorous pan-zoom session; over the limit returns 429.
        RateLimiter::for('tiles', function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
        });

        View::composer('layouts.partials.sidebar', function ($view) {
            $user = Auth::user();

            if ($user) {
                $menus = Cache::remember('sidebar_menus_' . $user->id, 86400, function () use ($user) {
                    $result = Menu::whereHas('roles', function ($query) use ($user) {
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
                        ->whereNull('parent_id')
                        ->orderBy('sort_order', 'asc')
                        ->get();
                    $result->each(fn ($m) => $m->setRelation('children', $m->children));
                    return $result;
                });
            } else {
                $menus = collect();
            }

            $view->with('menus', $menus);
        });
    }
}
