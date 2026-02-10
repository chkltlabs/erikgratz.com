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
            \Illuminate\Support\Facades\Cache::forget('photos.all');
        });

        static::updated(function ($model) {
            \Illuminate\Support\Facades\Cache::forget('photos.all');
        });

        static::deleted(function ($model) {
            \Illuminate\Support\Facades\Cache::forget('photos.all');
        });
    }
}
