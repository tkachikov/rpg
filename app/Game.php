<?php
declare(strict_types=1);

namespace App;

use App\Events\TestEvent;
use App\Models\User;

class Game
{
    public array $player;

    public array $targets;

    public array $map;

    public bool $battleStatus;

    public function __construct()
    {
        $this->initPlayer();
        $this->initTargets();
        $this->initMap();
        $this->battleStatus = cache()->get('battle-status') ?? false;
    }

    public function __destruct()
    {
        cache()->set('player', $this->player);
        cache()->set('targets', $this->targets);
        cache()->set('map', $this->map);
        cache()->set('battle-status', $this->battleStatus);
    }

    /**
     * @return void
     * @throws mixed
     *
     */
    public function initPlayer(): void
    {
        if (cache()->has('player')) {
            $this->player = cache()->get('player');
            return;
        }
        $this->player = [
            'x' => 5,
            'y' => 5,
            'health' => 50,
            'fullHealth' => 50,
            'moveName' => null,
            'damage' => [
                'min' => $min = rand(2, 4),
                'max' => rand($min + 1, $min + 3),
            ],
            'color' => 'bg-slate-200',
        ];
    }

    /**
     * @return void
     * @throws mixed
     *
     */
    public function initTargets(): void
    {
        if (cache()->has('targets')) {
            $this->targets = cache()->get('targets');
            return;
        }
        for ($i = 0; $i < rand(4, 15); $i++) {
            while (true) {
                $x = rand(0, 9);
                $y = rand(0, 9);
                if (
                    !isset($this->targets[$y][$x])
                    && !($this->player['y'] === $y && $this->player['x'] === $x)
                ) {
                    break;
                }
            }
            $this->targets[$y][$x] = [
                'x' => $x,
                'y' => $y,
                'health' => $health = rand(15, 30),
                'fullHealth' => $health,
                'attack' => (bool)rand(0, 1),
                'damage' => [
                    'min' => $min = rand(1, 3),
                    'max' => rand($min + 1, $min + 3),
                ],
            ];
        }
    }

    /**
     * @return void
     * @throws mixed
     *
     */
    public function initMap(): void
    {
        if (cache()->has('map')) {
            $this->map = cache()->get('map');
            return;
        }
        for ($y = $this->player['y'] - 5; $y < $this->player['y'] + 5; $y++) {
            for ($x = $this->player['x'] - 5; $x < $this->player['x'] + 5; $x++) {
                $this->map[$y][$x] = [
                    'x' => $x,
                    'y' => $y,
                    'player' => false,
                    'target' => false,
                    'color' => 'bg-amber-' . rand(6, 8) . '00',
                ];
                if ($this->player['x'] === $x && $this->player['y'] === $y) {
                    $this->map[$y][$x]['player'] = true;
                } elseif (isset($this->targets[$y][$x])) {
                    $this->map[$y][$x]['target'] = true;
                    $this->map[$y][$x]['color'] = $this->targets[$y][$x]['attack']
                        ? 'bg-red-400'
                        : 'bg-green-400';
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getMap(): array
    {
        $map = [];
        for ($y = $this->player['y'] - 5; $y < $this->player['y'] + 5; $y++) {
            $row = [];
            for ($x = $this->player['x'] - 5; $x < $this->player['x'] + 5; $x++) {
                if (!isset($this->map[$y][$x])) {
                    $this->map[$y][$x] = $this->newCell($y, $x);
                }
                $row[] = $this->map[$y][$x];
            }
            $map[] = $row;
        }

        return $map;
    }

    /**
     * @param int $y
     * @param int $x
     *
     * @return array
     */
    public function newCell(int $y, int $x): array
    {
        return [
            'x' => $x,
            'y' => $y,
            'player' => false,
            'target' => false,
            'color' => 'bg-amber-' . rand(6,8) . '00',
        ];
    }

    /**
     * @param string $position
     * @param int    $step
     *
     * @return void
     */
    public function movePlayer(string $position, int $step): void
    {
        $this->player['moveName'] = $this->getMoveName($position, $step);
        $nextCell = $this->getNextCell($this->player, $position, $step);
        if ($this->playerCanMove($nextCell)) {
            $this->log("Player move: {$this->player['moveName']}");
            $this->map[$this->player['y']][$this->player['x']]['player'] = false;
            $this->map[$nextCell['y']][$nextCell['x']]['player'] = true;
            $this->player[$position] = $nextCell[$position];
        } else {
            $this->log("Player not move (here target): {$this->player['moveName']}");
        }
    }

    /**
     * @param string $position
     * @param int    $step
     *
     * @return string
     */
    public function getMoveName(string $position, int $step): string
    {
        return match (true) {
            $position === 'x' && $step > 0 => 'right',
            $position === 'x' && $step < 0 => 'left',
            $position === 'y' && $step > 0 => 'down',
            $position === 'y' && $step < 0 => 'up',
        };
    }

    /**
     * @param array  $item
     * @param string $position
     * @param int    $step
     *
     * @return array
     */
    public function getNextCell(array $item, string $position, int $step): array
    {
        return [
            'x' => $item['x'] + ($position == 'x' ? $step : 0),
            'y' => $item['y'] + ($position == 'y' ? $step : 0),
        ];
    }

    /**
     * @param array $nextCell
     *
     * @return bool
     */
    public function playerCanMove(array $nextCell): bool
    {
        return !$this->map[$nextCell['y']][$nextCell['x']]['target'];
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message): void
    {
        event(new TestEvent(User::first(), $message));
    }
}
