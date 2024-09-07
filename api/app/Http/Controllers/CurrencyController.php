<?php

namespace App\Http\Controllers;


use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

class CurrencyController extends Controller
{
    public function index(): Collection
    {
        return Currency::with('user')->get();
    }
}
