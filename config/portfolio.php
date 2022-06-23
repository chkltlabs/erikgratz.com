<?php

return [
    [
        'imgUrl' => '/storage/images/webp/bingo-cli.webp',
        'text' => 'Recently, I "played" bingo on a trip home, and realized that after choosing your bingo card, the act of "playing" is complete. Your fate is sealed. So, why not subject a computer to all the tedium of actually playing bingo? This program creates bingo boards, assigns them to players, and calls numbers until a player wins. It is as pointless as actual bingo, yet somehow less fun.',
        'title' => 'Auto Bingo!',
        'link' => 'https://github.com/chkltlabs/Auto-Bingo',
        'linkText' => 'clone it on Github',
    ],
    [
        'imgUrl' => '/storage/images/webp/droidku.webp',
        'text' => 'I created a simple android app that reads a backend api endpoint to show the contact requests I get through my site. Other functionality includes downloading contact info and requesting its deletion from the main site. Its made with the Ionic framework using Vue 3 components and the composition API.',
        'title' => 'Companion Android App',
    ],
    [
        'imgUrl' => '/storage/images/webp/toast.webp',
        'text' => 'Hopefully you\'ve noticed the toasts that appear in the corner of your screen. The system driving these is a websocket server running through a proxy address, backed by the excellent laravel-websockets package, and picked up by the equally great Laravel Echo on the front side. There should be a toast on each full page load when the websocket connection is established, as well as a toast each time anyone uses my /contact page to send me a note.',
        'title' => 'Websocket driven toasts!',
    ],
    [
        'imgUrl' => '/storage/images/webp/blogEditScreenshot.webp',
        'text' => 'Not much to this one, just a tiptap editor instance customized to work with tailwind.css styling. Not pictured is the blog post listing page in the admin area which allows editing of old posts, and saving drafts.',
        'title' => 'Integrated editing and storage for blog posts!',
    ],
    [
        'imgUrl' => '/storage/images/webp/siteFiletree.webp',
        'text' => 'My website (welcome, by the way) is made with MySql, Laravel, Inertia.js, Vue 3 and TailwindCSS. Its hosted on DigitalOcean servers with Laravel Forge for management and deployment. I originally started messing around with this site to get myself some reps doing frontend work, It\'s certainly not perfect, but I am proud of it nonetheless. I\'d love to do more frontend work, so any constructive criticism would be appreciated.',
        'title' => 'This website and its backend!',
    ],
];
