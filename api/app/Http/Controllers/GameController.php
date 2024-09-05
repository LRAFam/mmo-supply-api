<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Database\Eloquent\Collection;

class GameController extends Controller
{
    public function index(): Collection
    {
        return Game::all();
    }
}
