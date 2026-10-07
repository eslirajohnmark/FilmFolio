@extends('layouts.app')

@section('title', 'Settings · FilmFolio')

@section('content')
<div class="container" style="max-width:720px;">
    <h1 class="section-title">Settings</h1>

    @include('profile.partials.update-profile-information-form')
    @include('profile.partials.update-password-form')
    @include('profile.partials.delete-user-form')
</div>
@endsection
