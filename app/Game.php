<?php
declare(strict_types=1);

namespace App;

class Game
{
    public array $player;

    public function __construct()
    {
        $this->getPlayer();
    }

    public function __destruct()
    {
        cache()->set('player', $this->player);
    }

    /**
     * @throws mixed
     *
     * @return array
     */
    public function getPlayer(): array
    {
        if (!isset($this->player)) {
            $this->player = cache()->get('player') ?? [
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

        return $this->player;
    }
}
