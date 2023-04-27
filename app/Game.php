<?php
declare(strict_types=1);

namespace App;

use GdImage;
use App\Models\User;
use App\Events\TestEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Game
{
    public User $user;

    public array $imageColors = [];

    public array $fonts = [];

    public GdImage $image;

    public array $colors = [
        'bg-amber-600' => [217, 119, 6],
        'bg-amber-700' => [180, 83, 9],
        'bg-amber-800' => [146, 64, 14],
        'bg-red-400' => [248, 113, 113],
        'bg-green-400' => [74, 222, 128],
        'bg-slate-200' => [226, 232, 240],
        'bg-red-300' => [252, 165, 165],
        'bg-blue-300' => [147, 197, 253],
    ];

    public array $player;

    public array $targets;

    public array $map;

    public bool $battleStatus;

    public int $mapSize;

    public int $targetsSize;

    public float $maxTargets = 0.2;

    public array $keysCache = [
        'battle-status',
        'targets-size',
        'map-size',
        'player',
        'targets',
        'map',
    ];

    public array $locks = [];

    public function __destruct()
    {
        if (isset($this->user)) {
            foreach ($this->keysCache as $key) {
                $keyValue = str($key)->camel()->toString();
                if (isset($this->$keyValue)) {
                    cache()->set($this->getKeyCache($key), $this->$keyValue);
                    $this->locks[$key]->release();
                }
            }
        }
    }

    /**
     * @throws mixed
     *
     * @return void
     */
    public function init(): void
    {
        foreach ($this->keysCache as $key) {
            $this->locks[$key] = Cache::lock($this->getKeyCache($key).'-lock', 1);
            try {
                $this->locks[$key]->block(1);
                $method = 'init'.str($key)->studly()->toString();
                $this->{$method}();
            } catch (\Throwable) {}
        }
    }

    public function refresh()
    {
        if (isset($this->user)) {
            foreach ($this->keysCache as $key) {
                cache()->delete($this->getKeyCache($key));
            }
        }
    }

    /**
     * @param User|null $user
     *
     * @return $this
     */
    public function user(?User $user = null): self
    {
        if ($user) {
            $this->user = $user;
            $this->init();
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getKeyCache(string $key): string
    {
        return $this->user->id.'-'.$key;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $this->moveTargets();
        $this->bornTargets();
    }

    public function sendFrame()
    {
        $this->log('new frame', $this->base64());
    }

    public function initBattleStatus()
    {
        $this->battleStatus = cache()->get($this->getKeyCache('battle-status')) ?? false;
    }

    public function initTargetsSize()
    {
        $this->targetsSize = cache()->get($this->getKeyCache('targets-size')) ?? 0;
    }

    public function initMapSize()
    {
        $this->mapSize = cache()->get($this->getKeyCache('map-size')) ?? 0;
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
            'name' => $this->user->name ?? 'Player',
            'inBattle' => false,
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
            $this->targets[$y][$x] = $this->newTarget($x, $y);
        }
    }

    public function newTarget(?int $x = null, ?int $y = null)
    {
        if (!$x || !$y) {
            $keysY = array_keys($this->map);
            do {
                $y = rand(min($keysY), max($keysY));
                $keysX = array_keys($this->map[$y]);
                $x = rand(min($keysX), max($keysX));
            } while ($this->herePlayer($y, $x) || $this->hereTarget($y, $x));
        }
        $this->targetsSize++;
        $this->log("Born target: {$x}x$y");

        return [
            'x' => $x,
            'y' => $y,
            'health' => $health = rand(15, 30),
            'fullHealth' => $health,
            'attack' => $attack = (bool) rand(0, 1),
            'color' => $color = $attack ? 'bg-red-400' : 'bg-green-400',
            'rgb' => $this->colors[$color],
            'name' => 'Wood',
            'damage' => [
                'min' => $min = rand(1, 3),
                'max' => rand($min + 1, $min + 3),
            ],
            'inBattle' => false,
            'canMove' => (bool) rand(0, 1),
        ];
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
        $this->mapSize++;

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
        return !$this->player['inBattle'] && !$this->hereTarget($nextCell['y'], $nextCell['x']);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message, ?string $img = null): void
    {
        event(new TestEvent($this->user ?? User::first(), $message, $img));
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
        if ($this->battleStatus) {
            $this->targets[$targetOnFocus['y']][$targetOnFocus['x']]['inBattle'] = true;
            $this->player['inBattle'] = true;
        }
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
            $this->player['inBattle'] = false;
            $this->log('target die...');
        } elseif ($target['attack']) {
            $targetDamage = $this->getDamage($target);
            $this->player['health'] -= $targetDamage;
            if ($this->player['health'] < 1) {
                $this->refresh();
                $this->init();
            }
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
                if (!$target['canMove'] || $target['inBattle'] || rand(0, 10) < 5) {
                    continue;
                }
                $coords = $keys[rand(0, count($keys) - 1)];
                $y = $target['y'] + $coords[0];
                $x = $target['x'] + $coords[1];
                if (!$this->hereTarget($y, $x) && !$this->herePlayer($y, $x)) {
                    $removed[] = [$target['y'], $target['x']];
                    $target['y'] = $y;
                    $target['x'] = $x;
                    $moveTargets[] = $target;
                }
            }
        }
        foreach ($removed as $remove) {
            unset($this->targets[$remove[0]][$remove[1]]);
        }
        foreach ($moveTargets as $target) {
            $this->targets[$target['y']][$target['x']] = $target;
        }
    }

    public function bornTargets()
    {
        $targetsPercent = $this->targetsSize / $this->mapSize;
        $this->log("Count map: " . $this->mapSize . ", count targets: " . $this->targetsSize . ', targets miss percent: ' . $targetsPercent);
        if ($targetsPercent < $this->maxTargets) {
            $diff = (int) (($this->maxTargets - $targetsPercent) * $this->mapSize * 0.5);
            $this->log('Diff: ' . $diff);
            $count = rand(1, $diff);
            $this->log('Creating new targets: ' . $count);
            for ($i = 0; $i < $count; $i++) {
                $newTarget = $this->newTarget();
                $this->targets[$newTarget['y']][$newTarget['x']] = $newTarget;
            }
        }
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $this->image = imagecreate(1000, 1000);

        $this->initColors();
        $this->initFonts();
        $this->renderMap();
        $this->renderBattle();

        ob_start();
        imagepng($this->image);
        $image = ob_get_contents();
        ob_clean();

        return $image;
    }

    public function initColors()
    {
        $this->imageColors['background'] = $this->imageColors['white'] = imagecolorallocate($this->image, 255, 255, 255);
        $this->imageColors['black'] = imagecolorallocate($this->image, 0, 0, 0);
        $this->imageColors['grey'] = imagecolorallocate($this->image, 200, 200, 200);
        $this->imageColors['hp'] = imagecolorallocate($this->image, ...$this->colors['bg-red-300']);
        $this->imageColors['mana'] = imagecolorallocate($this->image, ...$this->colors['bg-blue-300']);
    }

    public function initFonts()
    {
        $this->fonts['arial'] = Storage::disk('public')->path('Arial.ttf');
    }

    /**
     * @param $im
     *
     * @return void
     */
    public function renderMap(): void
    {
        if ($this->battleStatus) {
            return;
        }
        $map = $this->getMap();
        foreach ($map as $y => $row) {
            $startY = $y * 100;
            foreach ($row as $x => $cell) {
                $startX = $x * 100;
                $color = imagecolorallocate($this->image, ...$cell['rgb']);
                imagefilledrectangle($this->image, $startX, $startY, $startX + 100, $startY + 100, $color);
                if ($cell['player']) {
                    // left
                    imagettftext($this->image, 14, 0, $startX + 10, $startY + 55, $this->getArrowColorFor('left'), $this->fonts['arial'], '<');
                    // right
                    imagettftext($this->image, 14, 0, $startX + 80, $startY + 55, $this->getArrowColorFor('right'), $this->fonts['arial'], '>');
                    // up
                    imagettftext($this->image, 14, 90, $startX + 58, $startY + 24, $this->getArrowColorFor('up'), $this->fonts['arial'], '>');
                    // down
                    imagettftext($this->image, 14, -90, $startX + 45, $startY + 80, $this->getArrowColorFor('down'), $this->fonts['arial'], '>');
                } elseif ($cell['target']) {
                    if (!$cell['targetItem']['canMove']) {
                        imagettftext($this->image, 14, 0, $startX + 35, $startY + 50, $this->imageColors['black'], $this->fonts['arial'], 'zzZ');
                    }
                } else {
                    imagettftext($this->image, 14, 0, $startX + 35, $startY + 55, $this->imageColors['black'], $this->fonts['arial'], $cell['y'].'x'.$cell['x']);
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

    public function renderBattle(): void
    {
        if ($this->battleStatus) {
            $this->renderPLayerFight();
            $this->renderAreaFight();
            $this->renderTargetFight();
        }
    }

    public function renderPlayerFight()
    {
        $grey = imagecolorallocate($this->image, 125, 125, 125);
        imagerectangle($this->image, 20, 20, 320, 400, $grey);
        $this->renderStats($this->player, 40);
    }

    public function renderAreaFight()
    {
        $grey = imagecolorallocate($this->image, 125, 125, 125);
        imagerectangle($this->image, 350, 20, 650, 400, $grey);
        imagefilledrectangle($this->image, 460, 200, 540, 240, $grey);
        imagettftext($this->image, 14, 0, 470, 225, $this->imageColors['white'], $this->fonts['arial'], 'FIGHT');
    }

    public function renderTargetFight()
    {
        $grey = imagecolorallocate($this->image, 125, 125, 125);
        imagerectangle($this->image, 680, 20, 980, 400, $grey);
        $this->renderStats($this->getTargetOnFocus(), 700);
    }

    public function renderStats($item, $start)
    {
        imagettftext($this->image, 14, 0, $start, 50, $this->imageColors['grey'], $this->fonts['arial'], $item['name']);

        $length = 260;
        $fullMana = $start + $length;
        imagefilledrectangle($this->image, $start, 370, $fullMana, 390, $this->imageColors['mana']);

        $fullHp = $start + (int) ($length * ($item['health'] / $item['fullHealth']));
        imagefilledrectangle($this->image, $start, 340, $fullHp, 360, $this->imageColors['hp']);
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

    public function keyEvent(string $code)
    {
        if ($code === 'Space') {
            if ($this->battleStatus) {
                $this->event(460, 200);
            } else {
                $this->battle();
            }
        } elseif ($code === 'Backspace') {
            if ($this->battleStatus) {
                $this->leaveBattle();
            }
        }
    }
}
