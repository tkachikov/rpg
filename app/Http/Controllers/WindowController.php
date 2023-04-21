<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\TestEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WindowController extends Controller
{
    private array $coords = [];

    /**
     * @return Response
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Window/Index', [
            'test' => cache()->get('test'),
            'position' => [
                'x' => $this->getPosition('x'),
                'y' => $this->getPosition('y'),
            ],
            'map' => $this->getMap(),
            'targets' => $this->getTargets(),
            'moveName' => cache()->get('move-name'),
            'battleStatus' => cache()->get('battle-status', false),
            'targetFight' => $this->getTarget(),
        ]);
    }

    public function start()
    {
        cache()->set('test', cache()->get('test', 0)  + 1);
    }

    public function move(Request $request)
    {
        $this->getMap();
        $position = $request->string('position')->toString();
        $step = $request->integer('step');
        cache()->set('move-name', $this->moveName($position, $step));
        $nextCell = [
            'x' => $this->getPosition('x') + ($position == 'x' ? $step : 0),
            'y' => $this->getPosition('y') + ($position == 'y' ? $step : 0),
        ];
        $this->event("nextCell: {$nextCell['y']}x{$nextCell['x']}");
        if (!$this->coords[$nextCell['y']][$nextCell['x']]['wood']) {
            cache()->set('position.'.$position, $nextCell[$position]);
            $this->logMove($position, $step);
        } else {
            $this->event('This has wood!');
        }
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
        $target = $this->getTarget();
        $damagePlayer = $this->getDamage();
        $damage = rand($damagePlayer['min'], $damagePlayer['max']);
        $target['health'] -= $damage;
        $this->event("-$damage ({$target['health']})");
        $woods = $this->getWoods();
        if ($target['health'] < 1) {
            unset($woods[$target['y']][$target['x']]);
            cache()->set('battle-status', false);
            $colors = cache()->get('colors-map');
            unset($colors[$target['y']][$target['x']]);
            cache()->set('colors-map', $colors);
        } else {
            $woods[$target['y']][$target['x']] = $target;
        }
        cache()->set('woods', $woods);
    }

    protected function getTarget()
    {
        $this->getMap();
        $cell = $this->getPlayerPosition();
        $target = [
            'x' => $cell['x'] + ($cell['move'] == 'left' ? -1 : ($cell['move'] == 'right' ? 1 : 0)),
            'y' => $cell['y'] + ($cell['move'] == 'up' ? -1 : ($cell['move'] == 'down' ? 1 : 0)),
        ];

        return $this->coords[$target['y']][$target['x']]['wood']
            ? $this->getWoods()[$target['y']][$target['x']]
            : null;
    }

    protected function getDamage()
    {
        $danger = cache()->get('danger');
        if (!$danger) {
            $danger = [
                'min' => $min = rand(1, 4),
                'max' => rand($min + 1, $min + 4),
            ];
            cache()->set('danger', $danger);
        }

        return $danger;
    }

    protected function getMap()
    {
        $map = [];
        $woods = $this->getWoods();
        $colors = cache()->get('colors-map', []);
        for ($y = $this->getPosition('y') - 5; $y < $this->getPosition('y') + 5; $y++) {
            $row = [];
            for ($x = $this->getPosition('x') - 5; $x < $this->getPosition('x') + 5; $x++) {
                if (isset($woods[$y][$x])) {
                    $colors[$y][$x] = 'bg-green-400';
                } elseif (!isset($colors[$y][$x])) {
                    $colors[$y][$x] = 'bg-amber-'.rand(6, 8).'00';
                }
                $row[] = $cell = [
                    'x' => $x,
                    'y' => $y,
                    'wood' => isset($woods[$y][$x]),
                    'color' => $colors[$y][$x],
                ];
                $this->coords[$y][$x] = $cell;
            }
            $map[] = $row;
        }
        cache()->set('colors-map', $colors);

        return $map;
    }

    /**
     * @param string $coord
     *
     * @return int
     */
    protected function getPosition(string $coord): int
    {
        return cache()->get('position.'.$coord, 5);
    }

    protected function getWoods()
    {
        $woods = cache()->get('woods');
        if ($woods) {
            return $woods;
        }
        for ($i = 0; $i < rand(4, 15); $i++) {
            $y = rand(0, 9);
            $x = rand(0, 9);
            $woods[$y][$x] = [
                'x' => $x,
                'y' => $y,
                'health' => $health = rand(15, 30),
                'fullHealth' => $health,
            ];
        }
        cache()->set('woods', $woods);

        return $woods;
    }

    protected function logMove(string $position, int $step)
    {
        $this->event("move {$this->moveName($position, $step)}");
    }

    protected function moveName(string $position, int $step)
    {
        return match (true) {
            $position === 'x' && $step > 0 => 'right',
            $position === 'x' && $step < 0 => 'left',
            $position === 'y' && $step > 0 => 'down',
            $position === 'y' && $step < 0 => 'up',
        };
    }

    protected function event(string $message)
    {
        event(new TestEvent(User::first(), $message));
    }

    protected function getTargets()
    {
        $this->getMap();
        $cell = ['x' => $this->getPosition('x'), 'y' => $this->getPosition('y')];
        $targets = [];
        $keys = [
            [0, 1],
            [0, -1],
            [1, 0],
            [-1, 0],
        ];
        foreach ($keys as $key) {
            $target = ['x' => $cell['x'] + $key[0], 'y' => $cell['y'] + $key[1]];
            if ($this->coords[$target['y']][$target['x']]['wood']) {
                $targets[] = $target;
            }
        }
        if ($targets) {
            $this->event("Here targets: " . count($targets));
        }

        return $targets;
    }

    protected function getPlayerPosition()
    {
        return [
            'x' => $this->getPosition('x'),
            'y' => $this->getPosition('y'),
            'move' => cache()->get('move-name', 'down'),
        ];
    }
}
