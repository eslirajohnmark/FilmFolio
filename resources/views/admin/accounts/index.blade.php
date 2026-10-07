@extends('layouts.admin')

@section('title', 'Accounts')

@section('content')
<h1 class="section-title">Accounts</h1>
<p class="section-subtitle">Review members, their activity, and suspend, ban or delete accounts.</p>

@livewire('admin.user-table')
@endsection
