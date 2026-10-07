@extends('layouts.app')

@section('title', 'Members · FilmFolio')

@section('content')
<div class="container members">
    @livewire('member-search')

    @if($featured->isNotEmpty())
        <h2 class="section-heading">Featured Members</h2>
        <div class="member-grid">
            @foreach($featured as $user)
                @include('partials.member-card', ['user' => $user, 'followingIds' => $followingIds, 'keyPrefix' => 'featured'])
            @endforeach
        </div>
    @endif

    <h2 class="section-heading">Popular Members</h2>
    <div class="member-grid">
        @forelse($popular as $user)
            @include('partials.member-card', ['user' => $user, 'followingIds' => $followingIds, 'keyPrefix' => 'popular'])
        @empty
            <p class="empty-state">No members yet.</p>
        @endforelse
    </div>
</div>
@endsection
