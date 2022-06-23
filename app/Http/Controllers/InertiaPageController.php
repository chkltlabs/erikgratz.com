<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Foundation\Application;

class InertiaPageController extends Controller
{
    public function getIndex(Request $request)
    {
        return Inertia::render('Home', [
            'useRealHomepage' => config('app.useRealHomepage'),
            'canLogin' => $request->has('login'),
            'canRegister' => $request->has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }

    public function getPlay()
    {
        return Inertia::render('Playground', []);
    }

    public function getMock($page)
    {
        return Inertia::render('Mocks', [
            'page' => $page,
        ]);
    }

    public function getContact()
    {
        return Inertia::render('Contact', [
            'phone' => config('contact.phone'),
            'email' => config('contact.email'),
            'resumeUrl' => config('contact.resumeUrl'),
            'submitButtonText' => \Illuminate\Support\Collection::make(['Validate me...', 'I\'m hungry, feed me words.', 'I love you.', 'You matter.'])->random(),
        ]);
    }

    public function getWedding()
    {
        $files = \Illuminate\Support\Facades\Storage::allFiles('public/images/engagement/');
        $imageUrls = array_map(function ($i) {
            return \Illuminate\Support\Facades\Storage::url($i);
        }, $files);
        return Inertia::render('Wedding', [
            'images' => $imageUrls
        ]);
    }

    public function getBlog()
    {
        return Inertia::render('Blog', [
            'posts' => \App\Models\BlogPost::public()->with('author')->get(),
            'inspire' => \Illuminate\Support\Collection::make(config('inspire'))->random(),
        ]);
    }

    public function getPortfolio()
    {
        return Inertia::render('Portfolio', [
            'portfolioThings' => config('portfolio')
        ]);
    }

    public function getDonate()
    {
        return Inertia::render('Donate', [
            'walletCodes' => config('crypto.walletPublicCodes'),
            'iconSvgPaths' => config('crypto.coinIcons'),
            'socials' => config('socials'),
            'charities' => config('charity'),
        ]);
    }
}
