<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

class AccountController extends Controller
{
    public function index(): Collection
    {
        return Account::all();
    }
}
