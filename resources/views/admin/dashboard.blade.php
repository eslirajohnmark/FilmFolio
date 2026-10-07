@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<h1 class="section-title">Dashboard</h1>
<p class="section-subtitle">Platform analytics and statistics.</p>

{{-- Headline numbers --}}
<div class="stats-row">
    <div class="stat-box"><b>{{ number_format($members['total']) }}</b><span>Members</span></div>
    <div class="stat-box"><b>{{ number_format($members['newThisWeek']) }}</b><span>New this week</span></div>
    <div class="stat-box"><b>{{ number_format($catalogue['films']) }}</b><span>Films</span></div>
    <div class="stat-box"><b>{{ number_format($catalogue['teasers']) }}</b><span>Teasers</span></div>
    <div class="stat-box"><b>{{ number_format($catalogue['lists']) }}</b><span>Lists ({{ $catalogue['officialLists'] }} official)</span></div>
    <div class="stat-box">
        <b>{{ number_format($members['suspended'] + $members['banned']) }}</b>
        <span>Suspended {{ $members['suspended'] }} · Banned {{ $members['banned'] }}</span>
    </div>
</div>

{{-- Weekly / monthly / yearly --}}
<div class="section-label">Weekly, monthly &amp; yearly statistics</div>
<div class="table-wrap">
    <table class="admin-table">
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
                    @foreach($values as $value)
                        <td style="text-align:right;color:#fff;font-weight:600;">{{ number_format($value) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Activity trend: one series, columns with a hover tooltip per week --}}
<div class="section-label">Activity per week <span class="chart-note">reviews + diary logs, last 12 weeks</span></div>
@php $weeklyMax = max(1, $weekly->max('total')); @endphp
<figure class="col-chart" role="img"
        aria-label="Reviews and diary logs per week: {{ $weekly->map(fn ($w) => $w['label'].' '.$w['total'])->join(', ') }}">
    @foreach($weekly as $week)
        <div class="col" tabindex="0">
            <span class="col-bar" style="height:{{ round($week['total'] / $weeklyMax * 100) }}%;"></span>
            <span class="col-label">{{ $week['label'] }}</span>
            <span class="chart-tip">Week of {{ $week['label'] }}: <b>{{ number_format($week['total']) }}</b></span>
        </div>
    @endforeach
</figure>

<div class="admin-grid-2">
    @foreach(['Popular genres' => $genres, 'Popular decades' => $decades] as $heading => $data)
        <section>
            <div class="section-label">{{ $heading }} <span class="chart-note">by reviews + diary logs</span></div>
            @php $max = max(1, $data->max() ?? 1); @endphp
            @forelse($data as $label => $total)
                <div class="hbar" tabindex="0">
                    <span class="hbar-label">{{ $label }}</span>
                    <span class="hbar-track"><span class="hbar-fill" style="width:{{ max(1, round($total / $max * 100)) }}%;"></span></span>
                    <span class="hbar-value">{{ number_format($total) }}</span>
                    <span class="chart-tip">{{ $label }}: <b>{{ number_format($total) }}</b></span>
                </div>
            @empty
                <p class="empty-state">No activity yet.</p>
            @endforelse
        </section>
    @endforeach
</div>
@endsection
