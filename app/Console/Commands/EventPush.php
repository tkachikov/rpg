<?php

namespace App\Console\Commands;

use App\Events\TestEvent;
use App\Models\User;
use Illuminate\Console\Command;

class EventPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:event-push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        event(new TestEvent(User::first()));
    }
}
