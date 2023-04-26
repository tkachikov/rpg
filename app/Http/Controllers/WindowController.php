<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\TestEvent;
use App\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WindowController extends Controller
{
    private array $coords = [];

    public function __construct(
        public Game $game,
    ) {}

    /**
     * @return Response
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Window/Index', [
            'test' => cache()->get('test'),
            'player' => $this->game->player,
            'map' => $this->game->getMap(),
            'battleStatus' => $this->game->battleStatus,
            'targetFight' => $this->game->getTargetOnFocus(),
        ]);
    }

    public function start()
    {
        cache()->set('test', cache()->get('test', 0)  + 1);
    }

    public function move(Request $request)
    {
        $this->game->movePlayer(...$request->only(['position', 'step']));
    }

    public function battle(Request $request)
    {
        $this->game->battle();
    }

    public function leaveBattle()
    {
        $this->game->leaveBattle();
    }

    public function fight()
    {
        $damage = rand($this->game->player['damage']['min'], $this->game->player['damage']['max']);

        $target = $this->getTarget();
        $this->game->targets[$target['y']][$target['x']]['health'] -= $damage;
        $this->event("wood -$damage ({$target['health']})");

        if ($target['attack']) {
            $damageTarget = rand($target['damage']['min'], $target['damage']['max']);
            $this->game->player['health'] -= $damageTarget;
            $this->event("you -$damageTarget ({$this->game->player['health']})");
        }

        if ($target['health'] < 1) {
            unset($this->game->targets[$target['y']][$target['x']]);
            cache()->set('battle-status', false);
            $colors = cache()->get('colors-map');
            unset($colors[$target['y']][$target['x']]);
            cache()->set('colors-map', $colors);
        }
    }
}
