<?php

return [
    [
        'imgUrl' => '/images/webp/openbrain.webp',
        'title' => 'OpenBrain SaaS',
        'text' => 'Agents forget the chat the moment it ends. OpenBrain is a semantic memory service I rebuilt in Laravel so any MCP client — or a plain REST API — can ingest, search, and update durable thoughts in Postgres with pgvector. The first version was a personal Node stack; this greenfield SaaS adds organizations, Sanctum API keys, a Filament dashboard, and local Ollama embeddings so remembering decisions does not require a cloud LLM bill. Billing is still on the roadmap. I already use it as shared memory across Cursor and other MCP tools: capture a fact once, retrieve it from the next session.',
        'link' => 'https://openbrain.erikgratz.com',
        'linkText' => 'Visit OpenBrain',
    ],
    [
        'imgUrl' => '/images/webp/card-benefits.webp',
        'title' => 'Household card wallet',
        'text' => 'Spreadsheets know a card exists. They do not know what is due next month after interest-saving vs interest-free buckets, or whether the $300 travel credit is still unused. The Filament admin tracks household cards, statement windows, and benefits with real tracking modes: manual, auto-assumed statement credits, or ignored (hidden by default, greyed at the bottom when I ask to see them). Annual-fee ROI is captured value against the fee. A Points widget rolls up card and loyalty balances — Avios displays as one pool even when the programs stay separate for booking.',
    ],
    [
        'imgUrl' => '/images/webp/book-travel.webp',
        'title' => 'Book Travel',
        'text' => 'The question is never "do we have a credit." It is which card, portal, and perk actually apply to this vendor, cabin, and channel. Book Travel ranks household wallet combos against a trip, using per-person loyalty memberships, transfer routes, and earning rates as first-class data. Unused credits show up as Benefits Due vs Assumed captured. Activities are the trip timeline — map, dates, spends, redemptions — so a booking decision and a trip plan live in the same system.',
    ],
    [
        'imgUrl' => '/images/webp/net-worth.webp',
        'title' => 'Net worth over time',
        'text' => 'Accounts, spends, periodic bills, and loans against savings are the ledger. A daily state dump snapshots balances so a Past Stats chart can plot net worth, cash, card debt, and points — including foreign-currency accounts converted to USD. SimpleFIN pulls bank transactions; categorization rules keep me from typing every coffee. The dashboard is the unsentimental version of "are we actually saving."',
    ],
    [
        'imgUrl' => '/images/webp/CCCOutput.webp',
        'text' => 'A Github Action to analyze a cobertura.xml code coverage file and add a comment to the pull request summarizing covered files. This is a fork of work by Irongut, whose original always posted and analyzed the entire codebase. My fork extends this by allowing targeted analysis of only files present in the changes for the PR. (This was also my first time working with C#, and it was a lot of fun!)',
        'title' => 'Code Coverage Summary Github Action',
        'link' => 'https://github.com/chkltlabs/CodeCoverageSummary',
        'linkText' => 'Check it out on Github',
    ],
    [
        'imgUrl' => '/images/webp/WixReadme.webp',
        'text' => 'Inspired by the TomorrowIdeas PHP SDK, this performs similar integration to the Wix website API. It is far from comprehensive, and made to fit a specific project need, but completion and expansion would be straightforward. (Though this is not a fork, and technically all my own work, I need to give credit to TomorrowIdeas for the structure)',
        'title' => 'Wix API PHP Client',
        'link' => 'https://github.com/chkltlabs/wix-client',
        'linkText' => 'Check it out on Github',
    ],
    [
        'imgUrl' => '/images/webp/PlaidReadme.webp',
        'text' => 'The original repo by TomorrowIdeas has been abandoned since 2022. When Plaid made updates to their API, adding new features, parameters and functionality, the original repo no longer fully supported our use case. This fork maintains the SDK for ongoing compatibility, emphasizing ease of use and integration.',
        'title' => 'Plaid API PHP Client',
        'link' => 'https://github.com/chkltlabs/plaid-sdk-php',
        'linkText' => 'Check it out on Github',
    ],
    [
        'imgUrl' => '/images/webp/bingo-cli.webp',
        'text' => 'Recently, I "played" bingo on a trip home, and realized that after choosing your bingo card, the act of "playing" is complete. Your fate is sealed. So, why not subject a computer to all the tedium of actually playing bingo? This program creates bingo boards, assigns them to players, and calls numbers until a player wins. It is as pointless as actual bingo, yet somehow less fun.',
        'title' => 'Auto Bingo!',
        'link' => 'https://github.com/chkltlabs/Auto-Bingo',
        'linkText' => 'Check it out on Github',
    ],
    [
        'imgUrl' => '/images/webp/siteFiletree.webp',
        'text' => 'My website (welcome, by the way) is made with MySql, Laravel, Livewire and TailwindCSS. Its hosted on DigitalOcean servers with Laravel Forge for management and deployment. I originally started messing around with this site to get myself some reps doing frontend work, It\'s certainly not perfect, but I am proud of it nonetheless. I\'d love to do more frontend work, so any constructive criticism would be appreciated.',
        'title' => 'This website and its backend!',
    ],
];
