@extends('errors.layout')

@section('code', '419')
@section('title', 'Page expired')
@section('message', 'Your session timed out for security. Refresh the page and try again.')
@section('action')
    <a href="javascript:location.reload()" class="btn btn-primary">Refresh page</a>
@endsection
