<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleMenu extends Model
{
    protected $table = 'role_menu';
    protected $primary = ['role_id', 'menu_id'];
    public $incrementing = false;
    protected $fillable = [
        'role_id',
        'menu_id',
        'allow_show',
        'allow_create',
        'allow_update',
        'allow_destroy',
    ];

    protected $casts = [
        'allow_show' => 'boolean',
        'allow_create' => 'boolean',
        'allow_update' => 'boolean',
        'allow_destroy' => 'boolean',
    ];

    public $timestamps = true;

    public function role(){
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function menu(){
        return $this->belongsTo(Menu::class,'menu_id');
    }
}
