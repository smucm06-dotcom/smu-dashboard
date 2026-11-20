@extends('layouts.app')

@section('title', $initiative->name)

{{--
    Chief Minister Punjab Development Program (PDP): the Punjab Development Program design, worked out from
    the three uploaded files (see PdpDashboard), with the four uploads and every visit below it.
--}}

@section('content')
@php
    // Both are used by the shared upload result box (psrp_shared/import-result).
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);
    $importResult = session('psrpImport');
@endphp

<div class="psrp-page" id="psrpPage" data-url="{{ route('initiatives.punjab-development-program.data', $initiative) }}">

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
                    <a href="#impact">Impact</a><a href="#plan">Scope of work</a><a href="#issues">Issues</a><a href="#changed">Department response</a><a href="#revisit">Visited again</a><a href="#mapsec">Map</a><a href="#districts">Districts</a><a href="#psrp-records">Records</a>
                </nav>
            </div>
        </form>
    </div>

    <div class="psrp-findings psrp-wrap" id="psrpFindings">
        <p class="note" id="load-note" aria-live="polite">Loading the figures…</p>

        <section id="impact" aria-labelledby="h-impact">
            <div class="sec-h"><h2 id="h-impact">What SMU's monitoring has changed</h2><p>Every problem SMU reports makes the department look at that site. The first card is what the department says it did. The second is what SMU found when it went back.</p></div>
            <div class="impact two" aria-label="Impact of SMU monitoring">
                <div class="imp-card" id="imp-1"><span class="imp-tag">Impact</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
                <div class="imp-card kcard" id="imp-2"><span class="imp-tag">Impact</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
            </div>
        </section>

        <section id="plan" aria-labelledby="h-plan">
            <div class="sec-h"><h2 id="h-plan">What PDP will build</h2><p id="plan-sub"></p></div>
            <div class="plan-g" id="plan-g"></div>
            <p class="plan-note" id="plan-note"></p>
        </section>

        <section id="issues" aria-labelledby="h-issues">
            <div class="sec-h"><h2 id="h-issues">What field teams found at the work sites</h2><p>Counted once per city, from SMU's latest visit. Click a figure to see those cities on the map.</p></div>
            <div class="card pad stcard">
                <h3>How did the work sites do at the latest visit?</h3>
                <div class="segbar" id="st-bar" role="img"></div>
                <div class="outs" id="st-outs"></div>
            </div>
            <div id="tiles"></div>
        </section>

        <section id="changed" aria-labelledby="h-changed">
            <div class="sec-h"><h2 id="h-changed">What the department did with SMU's reports</h2><p>Each problem in the SMU reports the department answered counts once, sorted by what its response says about it.</p></div>
            <div class="card tablewrap">
                <table class="vt iss-t" id="iss-t"></table>
                <div class="lgd2" id="iss-key"></div>
            </div>
            <div class="letters" id="letters"></div>
            <div class="callout">
                <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1 .5 15h15L8 1Zm-.8 5h1.6v5H7.2V6Zm0 6h1.6v1.6H7.2V12Z"/></svg>
                <div id="caveat"></div>
            </div>
        </section>

        <section id="revisit" aria-labelledby="h-rev">
            <div class="sec-h"><h2 id="h-rev">Back at the same cities</h2><p id="rev-sub"></p></div>
            <div class="card pad">
                <h3>Was the problem still there at SMU's next visit?</h3>
                <p class="sub" id="rc-sub"></p>
                <div class="rows" id="rc-rows"></div>
                <div class="ckey"><span><i style="background:var(--good)"></i>Gone at the next visit</span><span><i style="background:var(--crit)"></i>Still there</span></div>
            </div>
            <div class="card tablewrap rev-card">
                <div class="pad" style="padding-bottom:4px"><h3 id="cmp-h">First visit and latest visit, at the cities visited more than once</h3><p class="sub" id="cmp-sub"></p></div>
                <table class="vt cmp-t" id="cmp-t"></table>
                <div class="lgd2"><span><i class="sw s-more"></i>Better at the latest visit</span><span><i class="sw s-same"></i>No change</span><span><i class="sw s-less"></i>Worse at the latest visit</span></div>
            </div>
            <div class="card pad rev-card" id="again-card">
                <h3>Visited again and again</h3>
                <p class="sub" id="again-sub"></p>
                <div class="again" id="again"></div>
            </div>
        </section>

        <section id="mapsec" aria-labelledby="h-map">
            <div class="sec-h"><h2 id="h-map">Find any city on the map</h2><p>Each pin is a PDP city, coloured by what the field team found at SMU's latest visit. Click a pin to open the city's visits, its scope of work and the department's response.</p></div>
            <div class="tools">
                <div class="seg" role="group" aria-label="Which cities to show">
                    <button type="button" data-show="all" aria-pressed="true">All visited</button>
                    <button type="button" data-show="G" aria-pressed="false">No problem</button>
                    <button type="button" data-show="S" aria-pressed="false">1 to 3 problems</button>
                    <button type="button" data-show="M" aria-pressed="false">4 or more</button>
                    <button type="button" data-show="r" aria-pressed="false">Answered by dept.</button>
                    <button type="button" data-show="u" aria-pressed="false">Not visited yet</button>
                </div>
                <select id="f-find" class="ctl" aria-label="Show only cities with this finding or response"></select>
                <div class="search">
                    <input id="q" class="ctl" type="search" placeholder="Search city or district" aria-label="Search city or district" autocomplete="off">
                    <div class="results" id="results" hidden></div>
                </div>
                {{-- Shown by the script while a finding or response is chosen, as on the other pages --}}
                <form id="map-download" method="POST" action="{{ route('initiatives.punjab-development-program.map-download', $initiative) }}" hidden>
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
                    <div id="map" role="application" aria-label="Map of PDP cities"></div>
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
                <p>Only the districts that have a PDP city are listed, and a district can have more than one. <span id="dt-sub"></span>Shares are of the cities visited in each district, from the latest visit. Click a district to focus the whole page on it. Click a column heading to sort.</p>
            </div>
            <div class="card tablewrap"><table class="dt" id="dt"></table></div>
        </section>
    </div>

    {{-- In place of the design's "About the data": the uploads and every visit on file. --}}
    <div class="psrp-wrap">
        <section class="psrp-records" id="psrp-records" aria-labelledby="h-records">
            <div class="psrp-findings">
                <div class="sec-h">
                    <h2 id="h-records">Explore the records</h2>
                    <p>Every visit in the selection. Sort by any column, search by city, district or category, and click a visit to see everything recorded for it.</p>
                </div>
            </div>

            {{-- Result of the last upload --}}
            @include('initiatives.school_education.psrp_shared.import-result')

            <x-initiative-files
                :initiative="$initiative"
                :files="$files"
                :visits-upload-url="route('initiatives.punjab-development-program.import', $initiative)"
                match-note="Visits are matched by their Report DATE, District, City and category, so nothing is duplicated."
                list-items="PDP cities"
            />

            <div id="psrpRecordsBody">
                @include('initiatives.school_education.psrp_shared.records')
            </div>
        </section>

        <p class="psrp-findings pdp-sources">Sources: LG&amp;CD's list of PDP cities with the scope of work (total cities), SMU monitoring data (site visits by SMU report), and SMU reports with the department's response.</p>
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
@include('initiatives.local_government.punjab_development_program.partials.findings-styles')
@include('initiatives.school_education.psrp_shared.records-styles')
@php
    // Photo and video links can run to thousands of characters. In the table they are cut short; the
    // visit's window shows them in full. The columns are found by name.
    $linkColumns = array_filter([
        array_search('images', array_keys($table['columns']), true),
        array_search('videos', array_keys($table['columns']), true),
    ], 'is_int');
@endphp
@if ($linkColumns !== [])
<style>
    @foreach ($linkColumns as $column)
    #psrpRecordsBody .psrp-table td:nth-child({{ $column + 1 }}) { max-width: 320px; overflow: hidden; text-overflow: ellipsis; }
    @endforeach
</style>
@endif
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
@include('initiatives.local_government.punjab_development_program.partials.findings-script')
@include('initiatives.school_education.psrp_shared.records-script')
@include('initiatives.school_education.psrp_shared.pdf-export')
@endpush
