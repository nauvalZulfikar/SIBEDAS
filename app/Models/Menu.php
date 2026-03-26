<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';
    protected $fillable = [
        'name',
        'url',
        'icon',
        'parent_id',
        'sort_order'
    ];

    public function roles(){
        return $this->belongsToMany(Role::class, 'role_menu')->withTimestamps();
    }

    public function children(){
        return $this->hasMany(Menu::class,'parent_id');
    }
    public function parent(){
        return $this->belongsTo(Menu::class,'parent_id');
    }
}
