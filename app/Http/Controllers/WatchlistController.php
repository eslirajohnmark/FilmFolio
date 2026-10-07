<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class WatchlistController extends Controller
{
    public function index(Request $request): View
    {
        return view('watchlist.index', [
            'films' => $request->user()->watchlist()->orderByDesc('watchlists.id')->paginate(24),
        ]);
    }
}
