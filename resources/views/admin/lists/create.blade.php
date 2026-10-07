@extends('layouts.admin')

@section('title', 'New official list')

@section('content')
<a href="{{ route('admin.lists.index') }}" class="link" style="font-size:13px;">← All lists</a>
<h1 class="section-title" style="margin-top:8px;">New official list</h1>
<p class="section-subtitle">Official lists appear as <b>Crew Picks</b> on the Lists page. Build one around a genre or a theme.</p>

<form method="POST" action="{{ route('admin.lists.store') }}" class="panel panel-form" style="max-width:680px;">
    @csrf

    <div class="field">
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="e.g. Essential Sci-Fi, Rainy Day Comfort Films" required>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="description">Theme / description</label>
        <textarea id="description" name="description" style="min-height:80px;" placeholder="What ties these films together?">{{ old('description') }}</textarea>
        @error('description') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-grid-2">
        <div class="field">
            <label for="genre">Genre <span class="text-muted">(optional)</span></label>
            <select id="genre" name="genre">
                <option value="">— Theme list (no genre) —</option>
                @foreach($genres as $genre)
                    <option value="{{ $genre }}" @selected(old('genre') === $genre)>{{ $genre }}</option>
                @endforeach
            </select>
            @error('genre') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="auto_fill">Auto-fill with top-rated films</label>
            <input id="auto_fill" name="auto_fill" type="number" min="0" max="50" value="{{ old('auto_fill', 10) }}">
            <div class="hint">Only used with a genre. Set 0 to start empty and add films yourself.</div>
            @error('auto_fill') <div class="field-error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="field">
        <input type="hidden" name="is_featured" value="0">
        <label class="checkbox"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> Also feature this list</label>
    </div>

    <button type="submit" class="btn btn-primary">Create list</button>
</form>
@endsection
