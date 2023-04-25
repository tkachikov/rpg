<?php
declare(strict_types=1);

namespace App;

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
     * @throws mixed
     *
     * @return void
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
        ];
    }

    /**
     * @throws mixed
     *
     * @return void
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
                'attack' => (bool) rand(0, 1),
                'damage' => [
                    'min' => $min = rand(1, 3),
                    'max' => rand($min + 1, $min + 3),
                ],
            ];
        }
    }

    /**
     * @throws mixed
     *
     * @return void
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
                    'color' => 'bg-amber-'.rand(6, 8).'00',
                ];
                if ($this->player['x'] === $x && $this->player['y'] === $y) {
                    $this->map[$y][$x]['player'] = true;
                    $this->map[$y][$x]['color'] = 'bg-slate-200';
                } elseif (isset($this->targets[$y][$x])) {
                    $this->map[$y][$x]['target'] = true;
                    $this->map[$y][$x]['color'] = $this->targets[$y][$x]['attack']
                        ? 'bg-red-400'
                        : 'bg-green-400';
                }
            }
        }
    }
}
