<?php
declare(strict_types=1);

namespace App;

use App\Events\TestEvent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Game
{
    public User $user;

    public array $imageColors = [];

    public array $colors = [
        'bg-amber-600' => [217, 119, 6],
        'bg-amber-700' => [180, 83, 9],
        'bg-amber-800' => [146, 64, 14],
        'bg-red-400' => [248, 113, 113],
        'bg-green-400' => [74, 222, 128],
        'bg-slate-200' => [226, 232, 240],
    ];

    public array $player;

    public array $targets;

    public array $map;

    public bool $battleStatus;

    public function __destruct()
    {
        $this->destroy();
    }

    /**
     * @throws mixed
     *
     * @return void
     */
    public function init(): void
    {
        $this->initPlayer();
        $this->initTargets();
        $this->initMap();
        $this->battleStatus = cache()->get($this->getKeyCache('battle-status')) ?? false;
    }

    /**
     * @return void
     * @throws mixed
     */
    public function destroy(): void
    {
        if (isset($this->user)) {
            cache()->set($this->getKeyCache('player'), $this->player);
            cache()->set($this->getKeyCache('targets'), $this->targets);
            cache()->set($this->getKeyCache('map'), $this->map);
            cache()->set($this->getKeyCache('battle-status'), $this->battleStatus);
        }
    }

    public function user(?User $user = null)
    {
        if ($user) {
            $this->user = $user;
            $this->init();
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getKeyCache(string $key): string
    {
        return Auth::id().'-'.$key;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        //$this->moveTargets();
        $this->log('Frame', $this->base64());
    }

    /**
     * @return void
     * @throws mixed
     *
     */
    public function initPlayer(): void
    {
        $key = $this->getKeyCache('player');
        if (cache()->has($key)) {
            $this->player = cache()->get($key);
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
            'rgb' => $this->colors['bg-slate-200'],
            //'rgb' => [rand(0, 255), rand(0, 255), rand(0, 255)],
        ];
    }

    /**
     * @return void
     * @throws mixed
     *
     */
    public function initTargets(): void
    {
        $key = $this->getKeyCache('targets');
        if (cache()->has($key)) {
            $this->targets = cache()->get($key);
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
                'attack' => $attack = (bool) rand(0, 1),
                'color' => $color = $attack ? 'bg-red-400' : 'bg-green-400',
                'rgb' => $this->colors[$color],
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
        $key = $this->getKeyCache('map');
        if (cache()->has($key)) {
            $this->map = cache()->get($key);
            return;
        }
        for ($y = $this->player['y'] - 5; $y < $this->player['y'] + 5; $y++) {
            for ($x = $this->player['x'] - 5; $x < $this->player['x'] + 5; $x++) {
                $this->map[$y][$x] = $this->newCell($y, $x);
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
                $cell = $this->map[$y][$x];
                if ($this->herePlayer($y, $x)) {
                    $cell['player'] = true;
                    $cell['playerItem'] = $this->player;
                    $cell['color'] = $cell['playerItem']['color'];
                    $cell['rgb'] = $cell['playerItem']['rgb'];
                }
                if ($this->hereTarget($y, $x)) {
                    $cell['target'] = true;
                    $cell['targetItem'] = $this->targets[$y][$x];
                    $cell['color'] = $cell['targetItem']['color'];
                    $cell['rgb'] = $cell['targetItem']['rgb'];
                }
                $row[] = $cell;
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
            'color' => ($color = 'bg-amber-' . rand(6,8) . '00'),
            'rgb' => $this->colors[$color],
        ];
    }

    /**
     * @param int $y
     * @param int $x
     *
     * @return bool
     */
    public function herePlayer(int $y, int $x): bool
    {
        return $this->player['x'] === $x && $this->player['y'] === $y;
    }

    /**
     * @param int $y
     * @param int $x
     *
     * @return bool
     */
    public function hereTarget(int $y, int $x): bool
    {
        return isset($this->targets[$y][$x]);
    }

    /**
     * @param string $position
     * @param int    $step
     *
     * @return void
     */
    public function movePlayer(string $position, int $step): void
    {
        if ($this->battleStatus) {
            return;
        }
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
        return !$this->hereTarget($nextCell['y'], $nextCell['x']);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message, ?string $img = null): void
    {
        event(new TestEvent(User::first(), $message, $img));
    }

    /**
     * @return array
     */
    public function nearTargets(): array
    {
        $keys = [
            [0, 1],
            [0, -1],
            [1, 0],
            [-1, 0],
        ];
        foreach ($keys as $key) {
            $y = $this->player['y'] + $key[0];
            $x = $this->player['x'] + $key[1];
            if (isset($this->targets[$y][$x])) {
                $targets[] = $this->targets[$y][$x];
            }
        }

        return $targets ?? [];
    }

    /**
     * @return void
     */
    public function battle(): void
    {
        $targetOnFocus = $this->getTargetOnFocus();
        $this->battleStatus = (bool) $targetOnFocus;
        $this->log("Battle: " . (int) $this->battleStatus);
    }

    /**
     * @return void
     */
    public function leaveBattle(): void
    {
        $this->battleStatus = false;
    }

    /**
     * @return array|null
     */
    public function getTargetOnFocus(): ?array
    {
        $nearTargets = $this->nearTargets();
        $cellTarget = [
            'x' => $this->player['x'] + ($this->player['moveName'] === 'left'
                    ? -1
                    : ($this->player['moveName'] === 'right' ? 1 : 0)
                ),
            'y' => $this->player['y'] + ($this->player['moveName'] === 'up'
                    ? -1
                    : ($this->player['moveName'] === 'down' ? 1 : 0)
                ),
        ];
        foreach ($nearTargets as $target) {
            if (
                $target['y'] === $cellTarget['y']
                && $target['x'] === $cellTarget['x']
            ) {
                return $target;
            }
        }

        return null;
    }

    /**
     * @return void
     */
    public function fight(): void
    {
        $target = $this->getTargetOnFocus();

        $playerDamage = $this->getDamage($this->player);
        $target['health'] -= $playerDamage;
        $this->targets[$target['y']][$target['x']] = $target;

        if ($target['health'] < 1) {
            unset($this->targets[$target['y']][$target['x']]);
            $this->battleStatus = false;
            $this->log('target die...');
        } elseif ($target['attack']) {
            $targetDamage = $this->getDamage($target);
            $this->player['health'] -= $targetDamage;
        }
    }

    /**
     * @param array $who
     * @param bool  $player
     *
     * @return int
     */
    public function getDamage(array $who, bool $player = true): int
    {
        $damage = rand($who['damage']['min'], $who['damage']['max']);
        if (rand(0, 100) < 5) {
            $this->log(($player ? 'Player' : 'Target') . ' CRITICAL damage!');
            $damage *= 2;
        }

        return $damage;
    }

    /**
     * @return void
     */
    public function moveTargets(): void
    {
        $keys = [
            [0, 1],
            [0, -1],
            [1, 0],
            [-1, 0],
        ];
        $removed = $moveTargets = [];
        foreach ($this->targets as $y => $line) {
            foreach ($line as $x => $target) {
                if (rand(0, 10) < 5) {
                    $coords = $keys[rand(0, count($keys) - 1)];
                    $y = $target['y'] + $coords[0];
                    $x = $target['x'] + $coords[1];
                    if (!$this->hereTarget($y, $x)) {
                        $removed[] = [$target['y'], $target['x']];
                        $target['y'] = $y;
                        $target['x'] = $x;
                        $moveTargets[] = $target;
                    }
                }
            }
        }
        foreach ($removed as $remove) {
            unset($this->targets[$remove[0]][$remove[1]]);
        }
        foreach ($moveTargets as $target) {
            $this->targets[$target['y']][$target['x']] = $target;
        }
        cache()->set('targets', $this->targets);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $im = imagecreate(1000, 1000);

        $this->initColors($im);
        $this->renderMap($im);
        $this->renderBattle($im);

        ob_start();
        imagepng($im);
        $image = ob_get_contents();
        ob_clean();

        return $image;
    }

    public function initColors($im)
    {
        $this->imageColors['background'] = $this->imageColors['white'] = imagecolorallocate($im, 255, 255, 255);
        $this->imageColors['black'] = imagecolorallocate($im, 0, 0, 0);
        $this->imageColors['grey'] = imagecolorallocate($im, 200, 200, 200);
    }

    /**
     * @param $im
     *
     * @return void
     */
    public function renderMap($im): void
    {
        if ($this->battleStatus) {
            return;
        }
        $map = $this->getMap();
        $path = Storage::disk('public')->path('Arial.ttf');
        foreach ($map as $y => $row) {
            $startY = $y * 100;
            foreach ($row as $x => $cell) {
                $startX = $x * 100;
                $color = imagecolorallocate($im, ...$cell['rgb']);
                imagefilledrectangle($im, $startX, $startY, $startX + 100, $startY + 100, $color);
                if ($cell['player']) {
                    // left
                    imagettftext($im, 14, 0, $startX + 10, $startY + 55, $this->getArrowColorFor('left'), $path, '<');
                    // right
                    imagettftext($im, 14, 0, $startX + 80, $startY + 55, $this->getArrowColorFor('right'), $path, '>');
                    // up
                    imagettftext($im, 14, 90, $startX + 58, $startY + 24, $this->getArrowColorFor('up'), $path, '>');
                    // down
                    imagettftext($im, 14, -90, $startX + 45, $startY + 80, $this->getArrowColorFor('down'), $path, '>');
                } elseif ($cell['target']) {

                } else {
                    imagettftext($im, 14, 0, $startX + 35, $startY + 55, $this->imageColors['black'], $path, $cell['y'].'x'.$cell['x']);
                }
            }
        }
    }

    public function getArrowColorFor(string $position)
    {
        return $this->player['moveName'] === $position
            ? $this->imageColors['black']
            : $this->imageColors['grey'];
    }

    /**
     * @param $im
     * @return void
     */
    public function renderBattle($im): void
    {
        if ($this->battleStatus) {
            $this->renderPLayerFight($im);
            $this->renderAreaFight($im);
            $this->renderTargetFight($im);
        }
    }

    public function renderPlayerFight($im)
    {
        $grey = imagecolorallocate($im, 125, 125, 125);
        imagerectangle($im, 20, 20, 320, 400, $grey);
        $this->renderStats($im, $this->player, 40);
    }

    public function renderAreaFight($im)
    {
        $grey = imagecolorallocate($im, 125, 125, 125);
        imagerectangle($im, 350, 20, 650, 400, $grey);
        imagefilledrectangle($im, 460, 200, 540, 240, $grey);
        $path = Storage::disk('public')->path('Arial.ttf');
        imagettftext($im, 14, 0, 470, 225, $this->imageColors['white'], $path, 'FIGHT');
    }

    public function renderTargetFight($im)
    {
        $grey = imagecolorallocate($im, 125, 125, 125);
        imagerectangle($im, 680, 20, 980, 400, $grey);
        $this->renderStats($im, $this->getTargetOnFocus(), 700);
    }

    public function renderStats($im, $item, $start)
    {
        $length = 260;
        $fullMana = $start + $length;
        $blue = imagecolorallocatealpha($im, 0, 0, 150, 50);
        imagefilledrectangle($im, $start, 370, $fullMana, 390, $blue);

        $fullHp = $start + (int) ($length * ($item['health'] / $item['fullHealth']));
        $red = imagecolorallocatealpha($im, 150, 0, 0, 50);
        imagefilledrectangle($im, $start, 340, $fullHp, 360, $red);
    }

    /**
     * @return string
     */
    public function base64(): string
    {
        return 'data:image/png;base64, ' . base64_encode($this->render());
    }

    public function event(int $x, int $y)
    {
        $this->log('Get event: '.$x.'x'.$y);
        if ($this->isFightButton($x, $y)) {
            $this->log('Event fight');
            $this->fight();
        }
    }

    public function isFightButton($x, $y)
    {
        return $this->battleStatus && $x >= 460 && $x <= 540 && $y >= 200 && $y <= 240;
    }
}
