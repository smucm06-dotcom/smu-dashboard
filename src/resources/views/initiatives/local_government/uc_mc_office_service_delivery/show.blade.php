@extends('layouts.app')

@section('title', $initiative->name)

@section('content')
@php
    // Both are used by the shared upload result box (psrp_shared/import-result).
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);
    $importResult = session('psrpImport');
@endphp

<div class="psrp-page" id="psrpPage" data-url="{{ route('initiatives.uc-mc-office-service-delivery.data', $initiative) }}">

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
                    <a href="#impact">Impact</a><a href="#issues">Issues</a><a href="#trend">Over time</a><a href="#changed">Department response</a><a href="#recheck">Checked again</a><a href="#mapsec">Map</a><a href="#districts">Districts</a><a href="#psrp-records">Records</a>
                </nav>
            </div>
        </form>
    </div>

    <div class="psrp-findings psrp-wrap" id="psrpFindings">
        <p class="note" id="load-note" aria-live="polite">Loading the figures…</p>

        <section id="impact" aria-labelledby="h-impact">
            <div class="sec-h"><h2 id="h-impact">What SMU's monitoring has changed</h2><p>SMU's visits and reports make the department look into each office. These cards show what the department says it did, and what SMU found when it went back to the offices.</p></div>
            <div class="impact two" aria-label="Impact of SMU monitoring">
                <div class="imp-card" id="imp-1"><span class="imp-tag">Impact</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
                <div class="imp-card" id="imp-2"><span class="imp-tag">Impact</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
            </div>
        </section>

        <section id="issues" aria-labelledby="h-issues">
            <div class="sec-h"><h2 id="h-issues">What field teams found at the offices</h2><p>Counted once per office, from its latest visit. Click any issue to see those offices on the map.</p></div>
            <div class="tiles nine" id="tiles"></div>
        </section>

        <section id="trend" aria-labelledby="h-trend">
            <div class="sec-h"><h2 id="h-trend">Service delivery, quarter by quarter</h2><p>Every SMU visit, grouped by the quarter it was made in.</p></div>
            <div class="card pad key-chart">
                <span class="imp-tag">Key finding</span>
                <h3 class="kf" id="kf"></h3>
                <div class="ckey" id="ckey"></div>
                <div id="chart"></div>
                <p class="note" id="chart-note"></p>
            </div>
        </section>

        <section id="changed" aria-labelledby="h-changed">
            <div class="sec-h"><h2 id="h-changed">What the department did with SMU's reports</h2><p id="changed-sub"></p></div>
            <div class="card tablewrap">
                <div class="pad" style="padding-bottom:4px"><h3>Weekly SMU reports, <span id="w-range"></span>: what SMU reported and how the department answered</h3><p class="sub" id="w-sub"></p></div>
                <table class="vt iss-t" id="iss-t"></table>
                <div class="lgd2" id="iss-key"></div>
            </div>
            <div class="g2 eq">
                <div class="card ans-card">
                    <h3>Earlier SMU letters, <span id="l-range"></span></h3>
                    <p class="sub" id="l-sub"></p>
                    <div class="segbar" id="l-bar" role="img"></div>
                    <div class="outs six" id="l-outs"></div>
                </div>
                <div class="card pad why">
                    <h3>Why secretaries were away, as the department explains</h3>
                    <p class="sub" id="why-sub"></p>
                    <div class="rows" id="why-rows"></div>
                </div>
            </div>
            <div class="callout">
                <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1 .5 15h15L8 1Zm-.8 5h1.6v5H7.2V6Zm0 6h1.6v1.6H7.2V12Z"/></svg>
                <div id="caveat"></div>
            </div>
        </section>

        <section id="recheck" aria-labelledby="h-recheck">
            <div class="sec-h"><h2 id="h-recheck">Checked again after the report</h2><p id="rc-sub"></p></div>
            <div class="card pad">
                <h3>Was the problem still there at SMU's next visit?</h3>
                <div class="rows" id="rc-rows"></div>
                <div class="ckey" style="margin-top:10px"><span><i style="background:var(--good)"></i>Gone at the next visit</span><span><i style="background:var(--crit)"></i>Still there</span></div>
                <p class="note" id="fu-note"></p>
            </div>
        </section>

        <section id="mapsec" aria-labelledby="h-map">
            <div class="sec-h"><h2 id="h-map">Find any office on the map</h2><p>Each pin is an office SMU visited, coloured by what the field team found at the latest visit. Click a pin to open its visits and the department's response.</p></div>
            <div class="tools">
                <div class="seg" role="group" aria-label="Which offices to show">
                    <button type="button" data-show="all" aria-pressed="true">All</button>
                    <button type="button" data-show="S" aria-pressed="false">Serving</button>
                    <button type="button" data-show="P" aria-pressed="false">Open, not serving</button>
                    <button type="button" data-show="C" aria-pressed="false">Closed</button>
                    <button type="button" data-show="r" aria-pressed="false">Reported</button>
                </div>
                <select id="f-find" class="ctl" aria-label="Show only offices with this finding or response"></select>
                <div class="search">
                    <input id="q" class="ctl" type="search" placeholder="Search UC number, MC or place name" aria-label="Search UC number, MC or place name" autocomplete="off">
                    <div class="results" id="results" hidden></div>
                </div>
                {{-- Shown by the script while a finding or response is chosen, as on the other pages --}}
                <form id="map-download" method="POST" action="{{ route('initiatives.uc-mc-office-service-delivery.map-download', $initiative) }}" hidden>
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
                    <div id="map" role="application" aria-label="Map of UC and MC offices visited by SMU"></div>
                    <div class="legend" id="legend"></div>
                </div>
                <aside class="card panel" id="panel" aria-live="polite"></aside>
            </div>
            <p class="map-note" id="map-note"></p>
        </section>

        <section id="districts" aria-labelledby="h-districts">
            <div class="sec-h"><h2 id="h-districts">How districts compare</h2><p><span id="dt-sub"></span>Shares are of the offices visited in each district, from the latest visit. Click a district to focus the whole page on it. Click a column heading to sort.</p></div>
            <div class="card tablewrap"><table class="dt" id="dt"></table></div>
        </section>
    </div>

    {{-- In place of the design's "About the data": the uploads and every visit on file. --}}
    <div class="psrp-wrap">
        <section class="psrp-records" id="psrp-records" aria-labelledby="h-records">
            <div class="psrp-findings">
                <div class="sec-h">
                    <h2 id="h-records">Explore the records</h2>
                    <p>Every visit in the selection. Sort by any column, search by name, UC, MC, district or tehsil, and click a visit to see everything recorded for it.</p>
                </div>
            </div>

            {{-- Result of the last upload --}}
            @include('initiatives.school_education.psrp_shared.import-result')

            <x-initiative-files
                :initiative="$initiative"
                :files="$files"
                :visits-upload-url="route('initiatives.uc-mc-office-service-delivery.import', $initiative)"
                match-note="Visits are matched by their Timestamp and Email, so nothing is duplicated. Rows that share both, such as one form for two UCs, are all kept."
                list-items="UCs"
            />

            <div id="psrpRecordsBody">
                @include('initiatives.school_education.psrp_shared.records')
            </div>
        </section>

        <p class="psrp-findings ucmc-sources">Sources: total UC and MC offices in Punjab (department list), SMU monitoring data (office visits), and the department's response on the problems SMU reported.</p>
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
@include('initiatives.local_government.uc_mc_office_service_delivery.partials.findings-styles')
@include('initiatives.school_education.psrp_shared.records-styles')
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
@include('initiatives.local_government.uc_mc_office_service_delivery.partials.findings-script')
@include('initiatives.school_education.psrp_shared.records-script')
@include('initiatives.school_education.psrp_shared.pdf-export')
@endpush
