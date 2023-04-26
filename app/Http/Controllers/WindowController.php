<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Game;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class WindowController extends Controller
{
    public function __construct(
        public Game $game,
    ) {}

    /**
     * @throws mixed
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Window/Index', [
            'test' => cache()->get('test'),
            'player' => $this->game->player,
            'map' => $this->game->getMap(),
            'battleStatus' => $this->game->battleStatus,
            'targetFight' => $this->game->getTargetOnFocus(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    public function move(Request $request): void
    {
        $this->game->movePlayer(...$request->only(['position', 'step']));
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    public function battle(Request $request): void
    {
        $this->game->battle();
    }

    /**
     * @return void
     */
    public function leaveBattle(): void
    {
        $this->game->leaveBattle();
    }

    /**
     * @return void
     */
    public function fight(): void
    {
        $this->game->fight();
    }
}
