<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $fillable = [
        'name',
        'description'
    ];

    public function users(){
        return $this->belongsToMany(User::class,'user_role')->withTimestamps();
    }

    public function menus(){
        return $this->belongsToMany(Menu::class,'role_menu')->withTimestamps();
    }
}
