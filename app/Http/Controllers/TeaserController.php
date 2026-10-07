<?php

namespace App\Http\Controllers;

use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeaserController extends Controller
{
    public function index(Request $request): View
    {
        $teasers = Film::where('is_teaser', true)
            ->orderByRaw('(trailer_key IS NULL AND trailer_path IS NULL AND teaser_path IS NULL)')
            ->orderBy('release_date')
            ->take(12)
            ->get();

        $featured = $teasers->firstWhere('id', (int) $request->query('film')) ?? $teasers->first();

        return view('teasers.index', [
            'featured' => $featured,
            'upcoming' => $teasers->reject(fn (Film $film) => $film->is($featured)),
        ]);
    }
}
