@extends('layouts.app')

@section('title', $initiative->name)

@section('content')
@php
    // Both are used by the shared upload result box (psrp_shared/import-result).
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);
    $importResult = session('psrpImport');
@endphp

<div class="psrp-page" id="psrpPage" data-url="{{ route('initiatives.school-meal.data', $initiative) }}">

    <nav class="psrp-breadcrumb psrp-wrap" aria-label="Breadcrumb">
        <a href="{{ route('departments.index') }}">Departments</a>
        <i data-lucide="chevron-right" aria-hidden="true"></i>
        <a href="{{ route('departments.show', $initiative->department_id) }}">
            {{ $initiative->department?->name ?? 'Department' }}
        </a>
        <i data-lucide="chevron-right" aria-hidden="true"></i>
        <span aria-current="page">{{ $initiative->name }}</span>
        {{-- The page as it is on the screen, as a PDF download (see psrp_shared/pdf-export) --}}
        <button type="button" class="psrp-pdf-btn" id="pdfExport" data-pdf-skip data-pdf-name="{{ $initiative->name }}">
            <i data-lucide="file-down" aria-hidden="true"></i>
            <span>Export to PDF</span>
        </button>
    </nav>

    {{-- Filters: these apply to the whole page. The dates choose which visits count. --}}
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
                <input id="f-from" class="ctl" type="date" name="from" value="{{ $filters['from'] }}" title="Visits on or after this date">
            </span>
            <span class="fg">
                <label for="f-to">To</label>
                <input id="f-to" class="ctl" type="date" name="to" value="{{ $filters['to'] }}" title="Visits on or before this date">
            </span>
            <noscript><button type="submit" class="ctl">Apply</button></noscript>
            <button type="button" class="link" id="f-clear" hidden>Clear filters</button>
            <div class="fb-meta">
                <nav class="nav" aria-label="Sections">
                    <a href="#issues">Issues</a><a href="#supply">Milk supply</a><a href="#changed">Impact</a><a href="#revisit">Checked again</a><a href="#mapsec">Map</a><a href="#districts">Districts</a><a href="#psrp-records">Records</a>
                </nav>
            </div>
        </form>
    </div>

    <div class="psrp-findings psrp-wrap" id="psrpFindings">
        <p class="note" id="load-note" aria-live="polite">Loading the figures…</p>

        <section id="issues" aria-labelledby="h-issues">
            <div class="sec-h"><h2 id="h-issues">The issues that stand out</h2><p>What field teams found at each school they visited, counted once per school from its latest visit. Click any issue to see those schools on the map.</p></div>
            <div class="tiles" id="tiles"></div>
            <div class="card pad">
                <h3>Other things the field teams found</h3>
                <div class="rows" id="other-rows"></div>
            </div>
        </section>

        <section id="supply" aria-labelledby="h-supply">
            <div class="sec-h"><h2 id="h-supply">How the milk reaches students</h2><p>From the stock register and the staff at each school, on the day of the latest visit.</p></div>
            <div class="card pad progress" id="prog"></div>
            <div class="g2 eq">
                <div class="card pad">
                    <h3>How often milk arrives</h3>
                    <p class="sub">Days between deliveries, as told by the school.</p>
                    <div class="rows" id="lag-rows"></div>
                </div>
                <div class="card pad">
                    <h3>Milk left in stock on the visit day</h3>
                    <p class="sub" id="dos-sub"></p>
                    <div class="rows" id="dos-rows"></div>
                </div>
            </div>
            <div class="g2 eq">
                <div class="card ans-card">
                    <h3>Field team's overall rating</h3>
                    <p class="sub" id="rt-sub"></p>
                    <div class="segbar" id="rt-bar" role="img"></div>
                    <div class="outs three" id="rt-outs"></div>
                </div>
                <div class="card pad">
                    <h3>Students on the visit day</h3>
                    <p class="sub">At the schools that were open.</p>
                    <div class="mini" id="stu"></div>
                </div>
            </div>
        </section>

        <section id="changed" aria-labelledby="h-changed">
            <div class="sec-h"><h2 id="h-changed">What changed after the SMU visits</h2></div>
            {{-- The design's three Impact cards --}}
            <div class="impact" aria-label="Impact of SMU monitoring">
                <div class="imp-card" id="imp-1"><span class="imp-tag">Impact</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
                <div class="imp-card" id="imp-2"><span class="imp-tag">Impact</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
                <div class="imp-card" id="imp-3"><span class="imp-tag">Key finding</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
            </div>
            <div class="card ans-card">
                <h3>Department's response on the issues SMU reported</h3>
                <p class="sub" id="ans-sub"></p>
                <div class="segbar" id="segbar" role="img"></div>
                <div class="outs six" id="outs"></div>
            </div>
            <div class="card tablewrap" style="margin-top:14px">
                <div class="pad" style="padding-bottom:4px"><h3>What SMU reported, and how the department answered</h3><p class="sub">Issues grouped by topic. Click a row to see those schools on the map.</p></div>
                <table class="vt iss-t" id="iss-t"></table>
                <div class="legend-row" id="iss-key"></div>
            </div>

            <div class="out-h"><h3>Checked again after the department responded</h3><p id="fu-sub"></p></div>
            <div class="card tablewrap"><table class="vt fu-t" id="fu-t"></table></div>
            <button type="button" class="link showall" id="fu-more" hidden></button>
            <p class="note" id="fu-note"></p>

            <div class="out-h"><h3>What the department says it did</h3><p>Each SMU letter, with the issues raised and the department's response. Click a response to read all of it.</p></div>
            <div class="acts" id="acts"></div>
            <button type="button" class="link showall" id="showall" hidden></button>
            <div class="callout">
                <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1 .5 15h15L8 1Zm-.8 5h1.6v5H7.2V6Zm0 6h1.6v1.6H7.2V12Z"/></svg>
                <div id="caveat"></div>
            </div>
        </section>

        <section id="revisit" aria-labelledby="h-rev">
            <div class="sec-h"><h2 id="h-rev">Back at the same schools</h2><p id="rev-sub"></p></div>
            <div class="card tablewrap"><table class="vt" id="rev-t"></table>
                <div class="key"><span><i class="s-more"></i>Better at the latest visit</span><span><i class="s-same"></i>No change</span><span><i class="s-less"></i>Worse at the latest visit</span></div>
            </div>
            <p class="note" id="rev-note"></p>
            <div class="card pad" style="margin-top:14px">
                <h3 id="rv-h">Schools visited again</h3>
                <p class="sub">Green is the number of checks that got better, red the number that got worse. Click one to open its report.</p>
                <div class="top rows two rv-list" id="rv-list"></div>
            </div>
        </section>

        <section id="mapsec" aria-labelledby="h-map">
            <div class="sec-h"><h2 id="h-map">Find any school on the map</h2><p>Click a pin to open that school's visit report and the department's response.</p></div>
            <div class="tools">
                <div class="seg" role="group" aria-label="Which schools to show">
                    <button type="button" data-show="all" aria-pressed="true">All</button>
                    <button type="button" data-show="v" aria-pressed="false">Visited</button>
                    <button type="button" data-show="a" aria-pressed="false">Visited again</button>
                    <button type="button" data-show="r" aria-pressed="false">Reported</button>
                    <button type="button" data-show="n" aria-pressed="false">Not visited</button>
                </div>
                <select id="f-find" class="ctl" aria-label="Show only schools with this finding or response"></select>
                <div class="search">
                    <input id="q" class="ctl" type="search" placeholder="Search school name or EMIS code" aria-label="Search school name or EMIS code" autocomplete="off">
                    <div class="results" id="results" hidden></div>
                </div>
                {{-- Shown by the script while a finding or response is chosen, as on the other pages --}}
                <form id="map-download" method="POST" action="{{ route('initiatives.school-meal.map-download', $initiative) }}" hidden>
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
                    <div id="map" role="application" aria-label="Map of schools in the meal programme"></div>
                    <div class="legend" id="legend"></div>
                </div>
                <aside class="card panel" id="panel" aria-live="polite"></aside>
            </div>
            <p class="map-note" id="map-note"></p>
        </section>

        <section id="districts" aria-labelledby="h-districts">
            <div class="sec-h">
                <div class="sec-h-row">
                    <h2 id="h-districts">How districts compare</h2>
                    {{-- This section only, as a PDF download (see psrp_shared/pdf-export) --}}
                    <button type="button" class="psrp-pdf-btn" data-pdf-section="districts" data-pdf-skip title="Export only this section to PDF">
                        <i data-lucide="file-down" aria-hidden="true"></i>
                        <span>Export to PDF</span>
                    </button>
                </div>
                <p>Click a district to focus the whole page on it. Click a column heading to sort.</p>
            </div>
            <div class="insights four" id="insights"></div>
            <div class="card tablewrap"><table class="dt" id="dt"></table></div>
        </section>
    </div>

    {{-- In place of the design's "About the data": the uploads and every visit on file. --}}
    <div class="psrp-wrap">
        <section class="psrp-records" id="psrp-records" aria-labelledby="h-records">
            <div class="psrp-findings">
                <div class="sec-h">
                    <h2 id="h-records">Explore the records</h2>
                    <p>Every visit in the selection. Sort by any column, search by EMIS code, school or district, and click a visit to see everything recorded for it.</p>
                </div>
            </div>

            {{-- Result of the last upload --}}
            @include('initiatives.school_education.psrp_shared.import-result')

            <x-initiative-files
                :initiative="$initiative"
                :files="$files"
                :visits-upload-url="route('initiatives.school-meal.import', $initiative)"
            />

            <div id="psrpRecordsBody">
                @include('initiatives.school_education.psrp_shared.records')
            </div>
        </section>

        <p class="psrp-findings meal-sources">Sources: department file (total schools), SMU monitoring data (school visits), and the department's response on the issues SMU reported.</p>
    </div>

    {{-- Filled from the clicked row by the script --}}
    @include('initiatives.school_education.psrp_shared.visit-dialog')
</div>

{{-- Outside the page's container, which would otherwise change where it can be placed --}}
<div class="psrp-findings psrp-tip" id="tip" hidden></div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..800&display=swap">
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@include('initiatives.school_education.psrp_shared.findings-styles')
@include('initiatives.school_education.school_meal.partials.findings-styles')
@include('initiatives.school_education.psrp_shared.records-styles')
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
@include('initiatives.school_education.school_meal.partials.findings-script')
@include('initiatives.school_education.psrp_shared.records-script')
@include('initiatives.school_education.psrp_shared.pdf-export')
@endpush
