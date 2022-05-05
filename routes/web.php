<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
$exitCode = \Illuminate\Support\Facades\Artisan::call('storage:link', []);
//echo $exitCode; // 0 exit code for no errors.

Route::get('/', function () {
    //\Illuminate\Support\Facades\Log::error('FUCKING LOGGED');
    return Inertia::render('Home', [
        'useRealHomepage' => config('app.useRealHomepage'),
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::get('/play', function () {
    return Inertia::render('Playground', [
        //...
    ]);
});

Route::get('/mock/{page}', function ($page) {
    return Inertia::render('Mocks', [
        'page' => $page,
    ]);
});

Route::get('/contact', function () {
    return Inertia::render('Contact', [
        'phone' => config('contact.phone'),
        'email' => config('contact.email'),
        'resumeUrl' => config('contact.resumeUrl'),
        'submitButtonText' => \Illuminate\Support\Collection::make(['Validate me...', 'I\'m hungry, feed me words.', 'I love you.', 'You matter.'])->random(),
    ]);
})->name('contact');

Route::get('/wedding', function () {
    $files = \Illuminate\Support\Facades\Storage::allFiles('public/images/engagement/');
    $imageUrls = array_map(function ($i) {
        return \Illuminate\Support\Facades\Storage::url($i);
    }, $files);
    return Inertia::render('Wedding', [
        'images' => $imageUrls
    ]);
});

//Route::post('/contact', function(\Illuminate\Http\Request $request){
//    $to_name = 'erikgratz.com contact message';
//    $to_email = 'erikgratz110@gmail.com';
//    $form = $request->all();
//    $data = array(
//        'name' => (string)$form['name'],
//        "contact" => (string)$form['contact'],
//        "messages" => (string)$form['message']);
//\Illuminate\Support\Facades\Mail::send('emails.mail', $data, function($message) use ($to_name, $to_email) {
//    $message->to($to_email, $to_name)
//        ->subject('Message Received!');
//    $message->from('chkltlabs@gmail.com','Message Messenger');
//});
//return response()->redirectTo('/donate');
//});

Route::resource('contacts', \App\Http\Controllers\ContactController::class)->except('update', 'delete');

Route::get('/blog', function () {
    return Inertia::render('Blog', [
        'posts' => \App\Models\BlogPost::public()->with('author')->get(),
        'inspire' => \Illuminate\Support\Collection::make(config('inspire'))->random(),
    ]);
});

Route::get('/portfolio', function () {
    return Inertia::render('Portfolio', [
        'portfolioThings' => config('portfolio')
    ]);
});

Route::get('/donate', function () {
    return Inertia::render('Donate', [
        'walletCodes' => config('crypto.walletPublicCodes'),
        'iconSvgPaths' => config('crypto.coinIcons'),
        'socials' => config('socials'),
        'charities' => config('charity'),
    ]);
});

Route::get('/crypto', function () {
    return redirect()->route('crypto', ['ticker' => 'BTC-USDT', 'timescale' => '1hour']);
});

Route::get('/crypto/{ticker}/{timescale}', function ($ticker, $timescale) {
    //todo: validate ticker and timeframe

    $response = \Illuminate\Support\Facades\Http::post('https://api.kucoin.com/api/v1/bullet-public');
    switch ($timescale) {
        case '1min':
            $seconds = Carbon\Carbon::now()->subMinutes(30)->timestamp;
            break;
        case '3min':
            $seconds = Carbon\Carbon::now()->subMinutes(30 * 3)->timestamp;
            break;
        case '15min':
            $seconds = Carbon\Carbon::now()->subMinutes(30 * 15)->timestamp;
            break;
        case '30min':
            $seconds = Carbon\Carbon::now()->subMinutes(30 * 30)->timestamp;
            break;
        case '1hour':
            $seconds = Carbon\Carbon::now()->subHours(30)->timestamp;
            break;
        case '2hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 2)->timestamp;
            break;
        case '4hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 4)->timestamp;
            break;
        case '6hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 6)->timestamp;
            break;
        case '8hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 8)->timestamp;
            break;
        case '12hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 12)->timestamp;
            break;
        case '1day':
            $seconds = Carbon\Carbon::now()->subDays(30)->timestamp;
            break;
        case '1week':
            $seconds = Carbon\Carbon::now()->subWeeks(30)->timestamp;
            break;
    }
    $klines = json_decode(
        \Illuminate\Support\Facades\Http::get("https://api.kucoin.com/api/v1/market/candles?type=$timescale&symbol=$ticker&startAt=$seconds")
            ->body(), true);
    return Inertia::render('Crypto', ['res' => $response->body(), 'ticker' => $ticker, 'timescale' => $timescale, 'klines' => $klines]);
})->name('crypto');

Route::get('/crypto/klines/{timescale}/{symbol}', function (\Illuminate\Http\Request $request, $timescale, $symbol) {
    //todo - perfect use-case for a match operator in php 8, ensure it is installed locally and on production before use
    switch ($timescale) {
        case '1min':
            $seconds = Carbon\Carbon::now()->subMinutes(30)->timestamp;
            break;
        case '3min':
            $seconds = Carbon\Carbon::now()->subMinutes(30 * 3)->timestamp;
            break;
        case '15min':
            $seconds = Carbon\Carbon::now()->subMinutes(30 * 15)->timestamp;
            break;
        case '30min':
            $seconds = Carbon\Carbon::now()->subMinutes(30 * 30)->timestamp;
            break;
        case '1hour':
            $seconds = Carbon\Carbon::now()->subHours(30)->timestamp;
            break;
        case '2hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 2)->timestamp;
            break;
        case '4hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 4)->timestamp;
            break;
        case '6hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 6)->timestamp;
            break;
        case '8hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 8)->timestamp;
            break;
        case '12hour':
            $seconds = Carbon\Carbon::now()->subHours(30 * 12)->timestamp;
            break;
        case '1day':
            $seconds = Carbon\Carbon::now()->subDays(30)->timestamp;
            break;
        case '1week':
            $seconds = Carbon\Carbon::now()->subWeeks(30)->timestamp;
            break;
    }
    $klines = \Illuminate\Support\Facades\Http::get("https://api.kucoin.com/api/v1/market/candles?type=$timescale&symbol=$symbol&startAt=$seconds");
    return response($klines);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/blog/listing', function () {
        return Inertia::render('BlogList', [
            'BlogPosts' => \App\Models\BlogPost::with('author')->get(),
        ]);
    })->name('posts');

    Route::resource('contacts', \App\Http\Controllers\ContactController::class)->only('update', 'delete');

    Route::get('/blog/edit/{blog_post_id}', function ($blog_post_id) {
        //getting existing tags
        $posts = \App\Models\BlogPost::all('tags')->toArray();
        $posts = array_column($posts, 'tags');
        $tags = [];
        foreach ($posts as $tagArr) {
            if ($tagArr) {
                $tags = array_merge($tags, $tagArr);
            }
        }
        $tags = array_unique($tags);
        $tags = array_combine(array_values($tags), array_values($tags));
        return Inertia::render('BlogEdit', [
            'BlogPost' => \App\Models\BlogPost::with('author')->find($blog_post_id),
            'mode' => 'edit',
            'multiOptions' => $tags
        ]);
    });

    Route::post('/blog/edit/{blog_post_id}', function (\Illuminate\Http\Request $request, $blog_post_id) {
        if ($blog_post_id == $request->get('id')) {
            $post = \App\Models\BlogPost::find($blog_post_id)->update($request->all());
            return redirect('/blog/listing')->with('success', 'BlogPost saved!');
        }
        return redirect()->back()->with('error', 'Post not saved! ID Mismatch!');
    });

    Route::get('/blog/new', function () {
        return Inertia::render('BlogEdit', [
            'BlogPost' => new \App\Models\BlogPost(),
            'mode' => 'new',
        ]);
    });

    Route::post('/blog/new', function (\Illuminate\Http\Request $request) {
        \App\Models\BlogPost::create($request->all());
        return redirect('/blog/listing')->with('success', 'BlogPost saved!');
    });

});


require __DIR__ . '/auth.php';
