@extends('layouts.app')

@section('title', $initiative->name)

@section('content')
@php
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);

    $importResult = session('psrpImport');
@endphp

<div class="psrp-page" id="psrpPage" data-url="{{ route('initiatives.psrp-phase-1.data', $initiative) }}">

    <nav class="psrp-breadcrumb psrp-wrap" aria-label="Breadcrumb">
        <a href="{{ route('departments.index') }}">Departments</a>
        <i data-lucide="chevron-right" aria-hidden="true"></i>
        <a href="{{ route('departments.show', $initiative->department_id) }}">
            {{ $initiative->department?->name ?? 'Department' }}
        </a>
        <i data-lucide="chevron-right" aria-hidden="true"></i>
        <span aria-current="page">{{ $initiative->name }}</span>
    </nav>

    {{-- Filters: these apply to the whole page --}}
    <div class="psrp-findings filterbar">
        <form class="psrp-wrap" id="psrpFilters" method="GET" action="{{ route('initiatives.show', $initiative) }}">
            <span class="fg">
                <label for="f-district">District</label>
                <select id="f-district" class="ctl" name="district">
                    <option value="">All districts</option>
                    @if ($filters['district'] !== '')
                        <option selected>{{ $filters['district'] }}</option>
                    @endif
                </select>
            </span>
            <span class="fg">
                <label for="f-from">From</label>
                <input id="f-from" class="ctl" type="date" name="from" value="{{ $filters['from'] }}">
            </span>
            <span class="fg">
                <label for="f-to">To</label>
                <input id="f-to" class="ctl" type="date" name="to" value="{{ $filters['to'] }}">
            </span>
            <noscript><button type="submit" class="ctl">Apply</button></noscript>
            <button type="button" class="link" id="f-clear" hidden>Clear filters</button>
            <div class="fb-meta">
                <span class="scope" id="scope"></span>
                <nav class="nav" aria-label="Sections">
                    <a href="#changed">What changed</a><a href="#mapsec">Map</a><a href="#issues">Issues</a><a href="#districts">Districts</a><a href="#psrp-records">Records</a>
                </nav>
            </div>
        </form>
    </div>

    <div class="psrp-findings psrp-wrap" id="psrpFindings">
        <section id="changed" aria-labelledby="h-changed">
            <div class="sec-h"><h2 id="h-changed">What changed after the SMU visits</h2></div>
            <div class="card verdict" id="verdict" aria-live="polite">Loading the figures…</div>
            <div class="stats" id="stats"></div>
            <div class="card progress" id="progress"></div>
            <div class="out-h"><h3>How the department responded</h3><p id="out-sub"></p></div>
            <div class="outs" id="outs"></div>
            <div class="callout">
                <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1 .5 15h15L8 1Zm-.8 5h1.6v5H7.2V6Zm0 6h1.6v1.6H7.2V12Z"/></svg>
                <div id="caveat"></div>
            </div>
            <div class="since" id="since"></div>
        </section>

        <section id="mapsec" aria-labelledby="h-map">
            <div class="sec-h"><h2 id="h-map">Find any school on the map</h2><p>Click a pin to open that school's visit report and the department's reply.</p></div>
            <div class="tools">
                <div class="seg" role="group" aria-label="Which schools to show">
                    <button type="button" data-show="all" aria-pressed="true">All schools</button>
                    <button type="button" data-show="v" aria-pressed="false">Visited</button>
                    <button type="button" data-show="n" aria-pressed="false">Not visited</button>
                </div>
                <select id="f-issue" class="ctl" aria-label="Show only schools with this issue"></select>
                <div class="search">
                    <input id="q" class="ctl" type="search" placeholder="Search school name or EMIS code" aria-label="Search school name or EMIS code" autocomplete="off">
                    <div class="results" id="results" hidden></div>
                </div>
                {{-- Shown by the script while an issue is selected --}}
                <form id="map-download" method="POST" action="{{ route('initiatives.psrp-phase-1.map-download', $initiative) }}" hidden>
                    @csrf
                    <input type="hidden" name="ids" id="dl-ids">
                    <input type="hidden" name="file_name" id="dl-issue">
                    <button type="submit" class="ctl dl-btn">
                        <i data-lucide="download" aria-hidden="true"></i>
                        <span id="dl-label">Download</span>
                    </button>
                </form>
            </div>
            <div class="mapgrid">
                <div class="card mapcard">
                    <div id="map" role="application" aria-label="Map of Phase 1 schools"></div>
                    <div class="legend" id="legend"></div>
                </div>
                <aside class="card panel" id="panel" aria-live="polite"></aside>
            </div>
            <p class="map-note" id="map-note"></p>
        </section>

        <section id="issues" aria-labelledby="h-issues">
            <div class="sec-h"><h2 id="h-issues">The issues that stand out</h2><p>Share of visited schools with each issue. Click any issue to see those schools on the map.</p></div>
            <div class="tiles" id="tiles"></div>
            <div class="issgrid">
                <div class="card pad">
                    <h3>All issues found, most common first</h3>
                    <div class="rows" id="irows"></div>
                    <div id="inotes"></div>
                </div>
                <div class="card pad">
                    <h3>Schools with the most issues</h3>
                    <p class="sub">Click a school to open its report.</p>
                    <div class="top rows" id="top"></div>
                </div>
            </div>
        </section>

        <section id="districts" aria-labelledby="h-districts">
            <div class="sec-h"><h2 id="h-districts">How districts compare</h2><p>Click a district to focus the whole page on it. Click a column heading to sort. The * marks ratings explained in the note under the issues list.</p></div>
            <div class="insights" id="insights"></div>
            <div class="card tablewrap"><table class="dt" id="dt"></table></div>
        </section>
    </div>

    <div class="psrp-wrap">
        <section class="psrp-records" id="psrp-records" aria-labelledby="h-records">
            <div class="psrp-findings">
                <div class="sec-h">
                    <h2 id="h-records">Explore the records</h2>
                    <p>Every visit in the selection, plus a summary row per district. Sort by any column, search by EMIS code or district, and click a visit to see everything recorded for it.</p>
                </div>
            </div>

            {{-- Result of the last upload --}}
    @include('initiatives.school_education.psrp_shared.import-result')
            <x-initiative-files
                :initiative="$initiative"
                :files="$files"
                :visits-upload-url="route('initiatives.psrp-phase-1.import', $initiative)"
            />

            <div id="psrpRecordsBody">
                @include('initiatives.school_education.psrp_shared.records')
            </div>
        </section>
    </div>

    {{-- Filled from the clicked row by the script --}}
    @include('initiatives.school_education.psrp_shared.visit-dialog')
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400..800&display=swap">
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@include('initiatives.school_education.psrp_shared.findings-styles')
@include('initiatives.school_education.psrp_shared.records-styles')
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
@include('initiatives.school_education.psrp_phase_1.partials.findings-script')
@include('initiatives.school_education.psrp_shared.records-script')
@endpush
