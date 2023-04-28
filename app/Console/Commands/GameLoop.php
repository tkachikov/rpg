<?php

namespace App\Console\Commands;

use App\Game;
use App\Models\User;
use Illuminate\Console\Command;

class GameLoop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:game-loop';

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
        $services = User::get()->map(fn ($user) => app(Game::class)->user($user));
        while (true) {
            foreach ($services as $service) {
                $service->run();
            }
            usleep(10000);
        }
    }
}
