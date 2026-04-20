<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'label', 'color', 'description', 'is_system', 'permissions'];

    protected $casts = ['is_system' => 'boolean', 'permissions' => 'array'];

    // Users that currently carry this role
    public function users()
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    // Convenience: custom (non-system) roles only
    public static function custom()
    {
        return static::where('is_system', false)->orderBy('label');
    }

    // All roles ordered: system first, then custom alphabetically
    public static function ordered()
    {
        return static::orderByDesc('is_system')->orderBy('label')->get();
    }

    // Tailwind-safe badge style built from stored hex color
    public function badgeStyle(): string
    {
        return "background:{$this->color}18;color:{$this->color};";
    }
}
