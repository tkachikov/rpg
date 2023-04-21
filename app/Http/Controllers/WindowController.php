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

    protected function getMap()
    {
        $map = [];
        $woods = $this->getWoods();
        for ($y = $this->getPosition('y') - 5; $y < $this->getPosition('y') + 5; $y++) {
            $row = [];
            for ($x = $this->getPosition('x') - 5; $x < $this->getPosition('x') + 5; $x++) {
                $row[] = $cell = ['x' => $x, 'y' => $y, 'wood' => isset($woods[$y][$x])];
                $this->coords[$y][$x] = $cell;
            }
            $map[] = $row;
        }

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
        for ($i = 0; $i < rand(1, 5); $i++) {
            $y = rand(0, 9);
            $x = rand(0, 9);
            $woods[$y][$x] = true;
        }
        cache()->set('woods', $woods);

        return $woods;
    }

    protected function logMove(string $position, int $step)
    {
        $move = match (true) {
            $position === 'x' && $step > 0 => 'right',
            $position === 'x' && $step < 0 => 'left',
            $position === 'y' && $step > 0 => 'down',
            $position === 'y' && $step < 0 => 'up',
        };
        $this->event("move $move");
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
}
