<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

class CachedUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return Cache::remember('auth_user_' . $identifier, 86400, function () use ($identifier) {
            return parent::retrieveById($identifier);
        });
    }
}
