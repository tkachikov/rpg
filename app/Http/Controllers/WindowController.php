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
            'nearTargets' => $this->game->nearTargets(),
            'targetFight' => null,
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
        $target = $this->getTarget();
        $status = (bool) $target;
        if ($status) {
            $this->event("Health target: {$target['health']}");
        }
        $this->event("Battle start: " . (int) $status);
        cache()->set('battle-status', $status);
    }

    public function leaveBattle()
    {
        cache()->set('battle-status', false);
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

    protected function getTarget()
    {
        $this->getMap();
        $cell = $this->game->player;
        $target = [
            'x' => $cell['x'] + ($cell['moveName'] == 'left' ? -1 : ($cell['moveName'] == 'right' ? 1 : 0)),
            'y' => $cell['y'] + ($cell['moveName'] == 'up' ? -1 : ($cell['moveName'] == 'down' ? 1 : 0)),
        ];

        return $this->coords[$target['y']][$target['x']]['wood']
            ? $this->game->targets[$target['y']][$target['x']]
            : null;
    }

    protected function event(string $message)
    {
        event(new TestEvent(User::first(), $message));
    }

    protected function moveWoods()
    {
        $woods = $this->getWoods();
        $newWoods = [];
        $player = $this->game->player;
        foreach ($woods as $y => $item) {
            foreach ($item as $x => $wood) {
                if (!$this->playerInArea($player, $wood)) {
                    $wood['y'] += rand(-1, 1);
                    $wood['x'] += rand(-1, 1);
                }
                $newWoods[$wood['y']][$wood['x']] = $wood;
            }
        }
        cache()->set('colors-map', []);
        cache()->set('woods', $newWoods);
    }

    /**
     * @param $player
     * @param $wood
     * @return bool
     */
    protected function playerInArea($player, $wood): bool
    {
        $keys = [
            [-1, -1],
            [-1, 0],
            [-1, 1],
            [0, 1],
            [0, -1],
            [1, -1],
            [1, 0],
            [1, 1]
        ];
        $coords = [];
        foreach ($keys as $key) {
            $coords[$wood['y'] + $key[0]][$wood['x'] + $key[1]] = true;
        }

        return isset($coords[$player['y']][$player['x']]);
    }
}
