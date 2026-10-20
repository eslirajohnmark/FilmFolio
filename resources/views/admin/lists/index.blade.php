@extends('layouts.admin')

@section('title', 'Lists')

@section('content')
<div class="admin-toolbar">
    <div>
        <h1 class="section-title" style="margin:0;">Lists</h1>
        <p class="panel-sub" style="margin:4px 0 0;">Create Crew Picks and feature great lists. Members' own lists can be viewed and featured, never edited or deleted.</p>
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
                    <td>
                        @if($list->user)
                            <a href="{{ route('admin.accounts.show', $list->user) }}">&#64;{{ $list->ownerName() }}</a>
                        @else
                            {{ $list->ownerName() }}
                        @endif
                    </td>
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
                            <a href="{{ route('lists.show', $list) }}" class="icon-btn icon-view" title="View list" aria-label="View {{ $list->name }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            @if($list->isCrewPick())
                                <form method="POST" action="{{ route('admin.lists.destroy', $list) }}"
                                      onsubmit="return confirm(@js('Delete the list “'.$list->name.'”?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-btn icon-delete" title="Delete">🗑</button>
                                </form>
                            @else
                                <span class="text-muted view-only" title="This list belongs to a member: it can be featured, but not edited or deleted">Member's list</span>
                            @endif
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
