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
    protected $signature = 'app:game-loop {--user=}';

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
        $service = app(Game::class)->user(User::find($this->option('user')));
        while (true) {
            $start = microtime(true);
            $service->run();
            dump(microtime(true) - $start);
            usleep(10000);
        }
    }
}
