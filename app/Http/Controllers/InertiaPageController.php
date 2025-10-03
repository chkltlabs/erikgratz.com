<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use App\Models\BlogPost;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
            'submitButtonText' => Collection::make(['Validate me...', 'I\'m hungry, feed me words.', 'I love you.', 'You matter.'])->random(),
        ]);
    }

    public function getWedding()
    {
        $files = Storage::allFiles('public/images/engagement/');
        $imageUrls = array_map(function ($i) {
            return Storage::url($i);
        }, $files);

        return Inertia::render('Wedding', [
            'images' => $imageUrls,
        ]);
    }

    public function getBlog()
    {
        return Inertia::render('Blog', [
            'posts' => BlogPost::public()->with('author')->get(),
            'inspire' => Collection::make(config('inspire'))->random(),
        ]);
    }

    public function getPortfolio()
    {
        $portfolio = config('portfolio');
        array_walk($portfolio,
            fn ($arr) => $arr['imgUrl'] = asset(Storage::url($arr['imgUrl'])));

        //        var_dump($portfolio);
        return Inertia::render('Portfolio', [
            'portfolioThings' => $portfolio,
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
