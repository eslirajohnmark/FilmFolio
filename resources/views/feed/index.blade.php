@extends('layouts.app')

@section('title', 'Activity · FilmFolio')

@section('content')
<div class="container" style="max-width:820px;">
    <h1 class="section-title">Activity</h1>
    @livewire('activity-feed')
</div>
@endsection
