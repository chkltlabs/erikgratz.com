<?php

namespace App\Livewire\Page;

use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.components.layouts.app', ['pageTitle' => self::TITLE])]
class Experience extends Component
{
    public const TITLE = 'Experience';

    #[Computed]
    public function experience() :array
    {
        return [
            [
                'company' => 'Pocketnest',
                'location' => 'Detroit, MI -> Fully Remote',
                'title' => 'Senior Software Engineer, Backend',
                'timeframe' => '2022-' . now()->year,
                'bullets' => [
                    'Led server development from MVP to acquisition by new parent company, managing server and database load to engage with 40x user count, at acquisition resulting in a 36.6x stock evaluation increase.',
                    'Implemented dashboard for application configuration, including snapshots of database state, analytics and configuration transfer between staging and prod, resulting in faster iteration of content, version control of app configuration, and enabling non-technical staff to meet stakeholder demands more often.',
                    'Rewrote application core to solve redundancy and database bloat, while easing maintenance.',
                    'Implemented separate API for client-facing analytics, including key management, permissions, email invitation system',
                    'Traced existing application routes and optimized 20x speed in first month.',
                    'Migrated entire platform to Google Cloud from AWS.',
                ],
                'technologies' => [
                    'PHP', 'MySQL', 'FilamentPHP', 'Livewire', 'Docker',
                    'Laravel (Flare, Forge, Horizon, Pint, Passport, Sanctum, Sail, Telescope)'
                ]
            ],
            [
                'company' => 'Sonic Boom Wellness',
                'location' => 'San Diego, CA -> Fully Remote',
                'title' => 'Developer',
                'timeframe' => '2020-2022',
                'bullets' => [
                    'Self directed initiative with staff hacker to identify and close vulnerabilities across the stack, front and backend, including XSS and data validation.',
                    'Migrated platform features to new Lumen API from previous Zend App.',
                    'Maintenance & Expansion of API with focus on scalability, efficiency and good UX.',
                    'Extensive use of queued jobs, cron jobs, MongoDB, MySQL and .csv manipulation.',
                ],
                'technologies' => [
                    'PHP', 'Lumen', 'Laravel', 'Eloquent', 'MongoDB', 'MySQL', 'Vue 3', 'JavaScript', 'Rundeck', 'RabbitMQ',
                    'ElasticSearch/Kibana', 'VirtualBox Vagrant'
                ]
            ],
            [
                'company' => 'Internet Things',
                'location' => 'San Diego, CA',
                'title' => 'PHP Developer',
                'timeframe' => '2019-2020',
                'bullets' => [
                    'Managed automated API call based lead auctions system, with transparent reporting and UI for updates to connection details and debugging new connections',
                    'Stepped into the lead role after the previous lead left the company unexpectedly, a temporary post turned into a permanent one.',
                    'Spearheaded continued development and improvement of SimplySweeps.com, a lead generation platform.',
                    'CTO Mike Everhart praised work ethic.',
                ],
                'technologies' => [
                    'PHP', 'JavaScript', 'jQuery', 'Laravel', 'Eloquent', 'MySQL', 'Atlassian JIRA', 'Docker'
                ]
            ],
        ];
    }
    public function render()
    {
        return view('livewire.page.experience');
    }
}
