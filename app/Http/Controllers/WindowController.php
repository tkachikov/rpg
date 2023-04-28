<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Action;
use App\Game;
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
        /*
        $this->game->user($request->user());
        $this->game->movePlayer(...$request->only(['position', 'step']));
        */
        app(Action::class)
            ->user($request->user())
            ->save('movePlayer', $request->only(['position', 'step']));
        if ($request->has('render')) {
            return response($this->game->base64());
        }
    }

    public function battle(Request $request)
    {
        $this->game->user($request->user());
        $this->game->battle();
        if ($request->has('render')) {
            return response($this->game->base64());
        }
    }

    public function leaveBattle(Request $request)
    {
        $this->game->user($request->user());
        $this->game->leaveBattle();
        if ($request->has('render')) {
            return response($this->game->base64());
        }
    }

    public function fight(Request $request)
    {
        $this->game->user($request->user());
        $this->game->fight();
        if ($request->has('render')) {
            return response($this->game->base64());
        }
    }

    public function render(Request $request)
    {
        app(Game::class)->user($request->user())->render();

        return view('render', ['userId' => $request->user()->id]);
    }

    public function click(Request $request)
    {
        $this->game->user($request->user());
        $this->game->event(...$request->only(['x', 'y']));
        if ($request->has('render')) {
            return response($this->game->base64());
        }
    }

    public function event(Request $request)
    {
        $this->game->user($request->user());
        $this->game->keyEvent($request->get('code'));
        if ($request->has('render')) {
            return response($this->game->base64());
        }
    }
}
