@extends('layouts.admin')

@section('title', 'Lists')

@section('content')
<div class="admin-toolbar">
    <div>
        <h1 class="section-title" style="margin:0;">Lists</h1>
        <p class="panel-sub" style="margin:4px 0 0;">Create official lists, feature great ones, remove inappropriate ones.</p>
    </div>
    <a href="{{ route('admin.lists.create') }}" class="btn btn-primary">+ New official list</a>
</div>

<form method="GET" action="{{ route('admin.lists.index') }}" class="search-bar" style="max-width:520px;margin-bottom:12px;">
    <input type="search" name="q" value="{{ $q }}" placeholder="Search lists…" aria-label="Search lists">
    <input type="hidden" name="filter" value="{{ $filter }}">
    <button class="btn btn-secondary">Search</button>
</form>

<div class="filter-tabs" style="margin-bottom:14px;">
    @foreach(['all' => 'All', 'official' => 'Crew Picks', 'featured' => 'Featured', 'private' => 'Private'] as $key => $label)
        <a href="{{ route('admin.lists.index', array_filter(['filter' => $key, 'q' => $q])) }}" class="{{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>List</th>
                <th>Owner</th>
                <th style="text-align:right;">Films</th>
                <th style="text-align:right;">Likes</th>
                <th>Visibility</th>
                <th>Featured</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lists as $list)
                <tr>
                    <td>
                        <a href="{{ route('lists.show', $list) }}" style="color:#fff;font-weight:600;">{{ $list->name }}</a>
                        @if($list->is_official) <span class="badge">Crew Pick</span> @endif
                        @if($list->genre) <span class="text-muted" style="font-size:12px;">· {{ $list->genre }}</span> @endif
                    </td>
                    <td><a href="{{ route('admin.accounts.show', $list->user) }}">&#64;{{ $list->user->username ?? $list->user->name }}</a></td>
                    <td style="text-align:right;">{{ $list->films_count }}</td>
                    <td style="text-align:right;">{{ $list->likes_count }}</td>
                    <td>{{ ucfirst($list->visibility) }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.lists.feature', $list) }}">
                            @csrf
                            @method('PATCH')
                            <button class="status {{ $list->is_featured ? 'status-active' : 'status-suspended' }}" style="border:none;cursor:pointer;"
                                    @disabled($list->visibility !== 'public' && ! $list->is_featured)
                                    title="{{ $list->is_featured ? 'Unfeature' : 'Feature on the Lists page' }}">
                                {{ $list->is_featured ? 'Featured' : 'No' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('lists.show', $list) }}#edit" class="icon-btn icon-edit" title="Edit">✎</a>
                            <form method="POST" action="{{ route('admin.lists.destroy', $list) }}"
                                  onsubmit="return confirm(@js('Delete the list “'.$list->name.'”?'))">
                                @csrf
                                @method('DELETE')
                                <button class="icon-btn icon-delete" title="Delete">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-state">No lists found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $lists->links() }}
@endsection
