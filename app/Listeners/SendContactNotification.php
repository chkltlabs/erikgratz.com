<?php

namespace App\Listeners;

use App\Events\ContactReqCreated;

class SendContactNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(ContactReqCreated $event)
    {
        //
    }
}
