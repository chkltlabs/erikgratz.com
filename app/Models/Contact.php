<?php

namespace App\Models;

use App\Events\ContactReqCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['contact', 'name', 'message'];

    protected $appends = ['ab_message'];

    protected static function booted()
    {
        parent::booted();
        static::created(function ($model) {
            ContactReqCreated::dispatch($model);
        });
    }

    public function getAbMessageAttribute()
    {
        return strlen($this->attributes['message']) > 25
            ? substr($this->attributes['message'], 0, 22).'...'
            : $this->attributes['message'];
    }
}
