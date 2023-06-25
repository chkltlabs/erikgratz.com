<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class InertiaDashboardController extends Controller
{
    public function getDashboard()
    {
        // return Inertia::render('Dashboard');
        // 2023-06-24 : Filament replaces Breeze Dash
        return redirect(route('filament.pages.dashboard'));
    }

    public function getBlogListing()
    {
        return Inertia::render('BlogList', [
            'BlogPosts' => \App\Models\BlogPost::with('author')->get(),
        ]);
    }

    public function getBlogEdit($blog_post_id)
    {
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
            'multiOptions' => $tags,
        ]);
    }

    public function postBlogEdit(Request $request, $blog_post_id)
    {
        if ($blog_post_id == $request->get('id')) {
            $post = \App\Models\BlogPost::find($blog_post_id)->update($request->all());

            return redirect('/blog/listing')->with('success', 'BlogPost saved!');
        }

        return redirect()->back()->with('error', 'Post not saved! ID Mismatch!');
    }

    public function getBlogNew()
    {
        return Inertia::render('BlogEdit', [
            'BlogPost' => new \App\Models\BlogPost(),
            'mode' => 'new',
        ]);
    }

    public function postBlogNew(Request $request)
    {
        \App\Models\BlogPost::create($request->all());

        return redirect('/blog/listing')->with('success', 'BlogPost saved!');
    }
}
