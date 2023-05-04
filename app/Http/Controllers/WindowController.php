<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Action;
use App\Game;
use App\Players;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class WindowController extends Controller
{
    public function __construct(
        //public Game $game,
    ) {
    }

    /**
     * @throws mixed
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $players = Players::get();
        dd('test', $players, Players::$users);
        $this->game->user($request->user());

        return Inertia::render('Window/Index', [
            'test' => cache()->get('test'),
            'player' => $this->game->player,
            'map' => $this->game->getMap(),
            'battleStatus' => $this->game->battleStatus,
            'targetFight' => $this->game->getTargetOnFocus(),
            'rand' => rand(1, 9),
            'render' => false,
            'img' => null,
            'user_id' => $request->user()->id,
        ]);
    }

    /**
     * @param Request $request
     */
    public function move(Request $request)
    {
        app(Action::class)
            ->user($request->user())
            ->save('movePlayer', $request->only(['position', 'step']));
    }

    public function render(Request $request)
    {
        app(Game::class)->user($request->user())->render();

        return view('render', ['userId' => $request->user()->id]);
    }

    public function click(Request $request)
    {
        app(Action::class)
            ->user($request->user())
            ->save('event', $request->only(['x', 'y']));
    }

    public function event(Request $request)
    {
        app(Action::class)
            ->user($request->user())
            ->save('keyEvent', $request->only(['code']));
    }
}
