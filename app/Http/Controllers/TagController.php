<?php

namespace App\Http\Controllers;

use App\Models\Tag;

class TagController extends Controller
{
    public function show(Tag $tag)
    {
        return view('tags.show', [
            'tag' => $tag,
            'films' => $tag->films()->paginate(24),
            'diaries' => $tag->diaries()
                ->with('user', 'film')
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }
}
