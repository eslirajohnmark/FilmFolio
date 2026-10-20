@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    // Every number links to the page that lists it, filtered the same way it was counted.
    $periodKeys = ['This week' => 'week', 'This month' => 'month', 'This year' => 'year'];
    $metricLink = fn (string $metric, string $period) => match ($metric) {
        'New members' => route('admin.accounts.index', ['joined' => $period]),
        'Reviews & ratings' => route('admin.activity.index', ['type' => 'reviews', 'period' => $period]),
        'Diary logs' => route('admin.activity.index', ['type' => 'logs', 'period' => $period]),
        'Lists created' => route('admin.activity.index', ['type' => 'lists', 'period' => $period]),
        'Comments' => route('admin.activity.index', ['type' => 'comments', 'period' => $period]),
    };
@endphp
{{-- Keeps every number current while the dashboard is open (see partials/scripts) --}}
<div data-refresh-every="15" hidden></div>

<p class="welcome-back admin-welcome">Welcome back, <span class="welcome-name">{{ auth('admin')->user()->name }}</span>!</p>
<h1 class="section-title">Dashboard</h1>
<p class="section-subtitle">Platform analytics and statistics. Click any number to see what's behind it.</p>

{{-- Headline numbers --}}
<div class="stats-row">
    <a href="{{ route('admin.accounts.index') }}" class="stat-box stat-link"><b>{{ number_format($members['total']) }}</b><span>Members</span></a>
    <a href="{{ route('admin.accounts.index', ['joined' => 'week']) }}" class="stat-box stat-link"><b>{{ number_format($members['newThisWeek']) }}</b><span>New this week</span></a>
    <a href="{{ route('admin.films.index') }}" class="stat-box stat-link"><b>{{ number_format($catalogue['films']) }}</b><span>Films</span></a>
    <a href="{{ route('admin.films.index', ['filter' => 'teasers']) }}" class="stat-box stat-link"><b>{{ number_format($catalogue['teasers']) }}</b><span>Teasers</span></a>
    <div class="stat-box stat-split">
        <a href="{{ route('admin.lists.index') }}" class="stat-split-main"><b>{{ number_format($catalogue['lists']) }}</b><span>Lists</span></a>
        <a href="{{ route('admin.lists.index', ['filter' => 'official']) }}" class="stat-split-sub">{{ $catalogue['officialLists'] }} official →</a>
    </div>
    <div class="stat-box stat-split">
        <a href="{{ route('admin.accounts.index', ['status' => 'suspended']) }}" class="stat-split-main">
            <b>{{ number_format($members['suspended']) }}</b><span>Suspended</span>
        </a>
        <a href="{{ route('admin.accounts.index', ['status' => 'banned']) }}" class="stat-split-sub">Banned {{ $members['banned'] }} →</a>
    </div>
</div>

{{-- Weekly / monthly / yearly --}}
<div class="section-label">Weekly, monthly &amp; yearly statistics <a href="{{ route('admin.activity.index') }}">All activity →</a></div>
<div class="table-wrap">
    <table class="admin-table stats-table">
        <thead>
            <tr>
                <th>Metric</th>
                @foreach(array_keys(reset($periods)) as $period)
                    <th style="text-align:right;">{{ $period }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($periods as $metric => $values)
                <tr>
                    <td>{{ $metric }}</td>
                    @foreach($values as $period => $value)
                        <td style="text-align:right;">
                            <a href="{{ $metricLink($metric, $periodKeys[$period]) }}" class="stat-cell" title="See {{ strtolower($metric) }}: {{ strtolower($period) }}">{{ number_format($value) }}</a>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Activity trend: one series, a column per week; click one to see that week --}}
<div class="section-label">Activity per week <span class="chart-note">reviews + diary logs, last 12 weeks · click a week to see it</span></div>
@php $weeklyMax = max(1, $weekly->max('total')); @endphp
<figure class="col-chart" role="img"
        aria-label="Reviews and diary logs per week: {{ $weekly->map(fn ($w) => $w['label'].' '.$w['total'])->join(', ') }}">
    @foreach($weekly as $week)
        <a href="{{ route('admin.activity.index', ['type' => 'reviews', 'week' => $week['start']]) }}" class="col" aria-label="Week of {{ $week['label'] }}: {{ $week['total'] }}">
            <span class="col-bar" style="height:{{ round($week['total'] / $weeklyMax * 100) }}%;"></span>
            <span class="col-label">{{ $week['label'] }}</span>
            <span class="chart-tip">Week of {{ $week['label'] }}: <b>{{ number_format($week['total']) }}</b></span>
        </a>
    @endforeach
</figure>

<div class="admin-grid-2">
    @foreach(['Popular genres' => ['data' => $genres, 'param' => 'genre'], 'Popular decades' => ['data' => $decades, 'param' => 'decade']] as $heading => $chart)
        <section>
            <div class="section-label">{{ $heading }} <span class="chart-note">by reviews + diary logs · click to see the films</span></div>
            @php $max = max(1, $chart['data']->max() ?? 1); @endphp
            @forelse($chart['data'] as $bucket => $total)
                @php $label = $chart['param'] === 'decade' ? $bucket.'s' : $bucket; @endphp
                <a href="{{ route('admin.films.index', [$chart['param'] => $bucket]) }}" class="hbar">
                    <span class="hbar-label">{{ $label }}</span>
                    <span class="hbar-track"><span class="hbar-fill" style="width:{{ max(1, round($total / $max * 100)) }}%;"></span></span>
                    <span class="hbar-value">{{ number_format($total) }}</span>
                    <span class="chart-tip">{{ $label }}: <b>{{ number_format($total) }}</b></span>
                </a>
            @empty
                <p class="empty-state">No activity yet.</p>
            @endforelse
        </section>
    @endforeach
</div>
@endsection
