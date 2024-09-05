<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index(): Collection
    {
        return Game::all();
    }
}
