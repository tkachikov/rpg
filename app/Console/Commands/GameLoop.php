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
        $users = User::get();
        while (true) {
            foreach ($users as $user) {
                app(Game::class)->user($user)->run();
            }
            sleep(1);
        }
    }
}
