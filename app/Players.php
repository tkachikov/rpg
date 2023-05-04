<?php
declare(strict_types=1);

namespace App;

use App\Models\User;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

class Players
{
    public static array $users;

    /**
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function get(): array
    {
        $players = [];
        if (!isset(self::$users)) {
            self::$users = User::pluck('id')->toArray();
        }
        foreach (self::$users as $id) {
            $player = json_decode(cache()->get($id.'-player') ?? '[]', true);
            if ($player) {
                $players[$player['y']][$player['x']][$player['id']] = $player;
            }
        }

        return $players;
    }
}
