<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'url', 'path', 'description', 'tags'];

    protected $casts = ['tags' => 'array'];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $model->url = parse_url(Storage::disk('public')->url($model->path))['path'];
            $model->save();
        });
    }
}
