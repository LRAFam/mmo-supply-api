<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;

class ItemController extends Controller
{
    public function index(): Collection
    {
        return Item::all();
    }
}
