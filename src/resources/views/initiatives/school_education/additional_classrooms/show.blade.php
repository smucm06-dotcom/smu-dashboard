@extends('layouts.app')

@section('title', $initiative->name)

@section('content')
@php
    // Both are used by the shared upload result box (psrp_shared/import-result).
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);
    $importResult = session('psrpImport');
@endphp

<div class="psrp-page" id="psrpPage" data-url="{{ route('initiatives.additional-classrooms.data', $initiative) }}">

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
                <label for="f-zone">Zone</label>
                <select id="f-zone" class="ctl" name="zone">
                    <option value="">All zones</option>
                    @if ($filters['zone'] !== '')
                        <option selected>{{ $filters['zone'] }}</option>
                    @endif
                </select>
            </span>
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
                <span class="scope" id="scope"></span>
                <nav class="nav" aria-label="Sections">
                    <a href="#issues">Issues</a><a href="#construction">Construction</a><a href="#changed">Impact</a><a href="#mapsec">Map</a><a href="#districts">Districts</a><a href="#psrp-records">Records</a>
                </nav>
            </div>
        </form>
    </div>

    <div class="psrp-findings psrp-wrap" id="psrpFindings">
        <p class="note" id="load-note" aria-live="polite">Loading the figures…</p>

        <section id="issues" aria-labelledby="h-issues">
            <div class="sec-h"><h2 id="h-issues">The issues that stand out</h2><p>What field teams found at each school they visited. Click any issue to see those schools on the map.</p></div>
            <div class="tiles" id="tiles"></div>
            <p class="note" id="inotes"></p>
        </section>

        <section id="construction" aria-labelledby="h-con">
            <div class="sec-h"><h2 id="h-con">Where construction stands</h2><p>Progress on the approved classrooms at the schools SMU visited. Click a box or a row to see those schools on the map.</p></div>
            <div class="card progress" id="prog"></div>
            <div class="g2 eq">
                <div class="card ans-card">
                    <h3>Is work going on?</h3>
                    <p class="sub">Each school counted once, by what the field team saw on the visit day.</p>
                    <div class="segbar" id="st-bar" role="img"></div>
                    <div class="outs three" id="st-outs"></div>
                </div>
                <div class="card pad">
                    <h3>Stage reached</h3>
                    <p class="sub">How far the building work had got on the visit day.</p>
                    <div class="rows" id="sg-rows"></div>
                </div>
            </div>
            <div class="g2 eq">
                <div class="card tablewrap">
                    <div class="pad" style="padding-bottom:4px"><h3>What is holding work back</h3><p class="sub">The reason given where work has stopped, and the problem noted where work goes on but is slowed.</p></div>
                    <table class="vt cause-t" id="cause-t"></table>
                </div>
                <div class="card pad">
                    <h3>How often officials inspect the site</h3>
                    <p class="sub">As told to the field team at each school.</p>
                    <div class="rows" id="ins-rows"></div>
                </div>
            </div>
        </section>

        <section id="changed" aria-labelledby="h-changed">
            <div class="sec-h"><h2 id="h-changed">What changed after the SMU visits</h2></div>
            {{-- The design's two Impact cards --}}
            <div class="impact" aria-label="Impact of SMU monitoring">
                <div class="imp-card" id="imp-1"><span class="imp-tag">Impact</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
                <div class="imp-card" id="imp-2"><span class="imp-tag">Key finding</span><div class="imp-num">&ndash;</div><div class="imp-txt"><b>Loading the figures…</b></div></div>
            </div>
            <div class="card ans-card">
                <h3>Department's response on the issues SMU reported</h3>
                <p class="sub" id="ans-sub"></p>
                <div class="segbar" id="segbar" role="img"></div>
                <div class="outs four" id="outs"></div>
            </div>

            <div class="out-h"><h3>What the department says it did</h3><p>Each SMU report, with the issues raised and the department's response. Click a response to read all of it.</p></div>
            <div class="acts" id="acts"></div>
            <div class="callout">
                <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1 .5 15h15L8 1Zm-.8 5h1.6v5H7.2V6Zm0 6h1.6v1.6H7.2V12Z"/></svg>
                <div id="caveat"></div>
            </div>
        </section>
        <section id="mapsec" aria-labelledby="h-map">
            <div class="sec-h"><h2 id="h-map">Find any school on the map</h2><p>Click a pin to open that school's visit report and the department's response.</p></div>
            <div class="tools">
                <div class="seg" role="group" aria-label="Which schools to show">
                    <button type="button" data-show="all" aria-pressed="true">All</button>
                    <button type="button" data-show="v" aria-pressed="false">Visited</button>
                    <button type="button" data-show="n" aria-pressed="false">Not visited</button>
                </div>
                <select id="f-find" class="ctl" aria-label="Show only schools with this finding, status or response"></select>
                <div class="search">
                    <input id="q" class="ctl" type="search" placeholder="Search school name or EMIS code" aria-label="Search school name or EMIS code" autocomplete="off">
                    <div class="results" id="results" hidden></div>
                </div>
                {{-- Shown by the script while a finding, status or response is chosen, as on the other pages --}}
                <form id="map-download" method="POST" action="{{ route('initiatives.additional-classrooms.map-download', $initiative) }}" hidden>
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
                    <div id="map" role="application" aria-label="Map of schools with approved additional classrooms"></div>
                    <div class="legend" id="legend"></div>
                </div>
                <aside class="card panel" id="panel" aria-live="polite"></aside>
            </div>
            <p class="map-note" id="map-note"></p>
        </section>

        <section id="districts" aria-labelledby="h-districts">
            <div class="sec-h"><h2 id="h-districts">How districts compare</h2><p>Click a district to focus the whole page on it. Click a column heading to sort.</p></div>
            <div class="insights" id="insights"></div>
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
                :visits-upload-url="route('initiatives.additional-classrooms.import', $initiative)"
                visit-match="visit date"
            />

            <div id="psrpRecordsBody">
                @include('initiatives.school_education.psrp_shared.records')
            </div>
        </section>

        <p class="psrp-findings ac-sources">Sources: department file (total schools), SMU monitoring data (school visits), and the department's response on the issues SMU reported.</p>
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
@include('initiatives.school_education.additional_classrooms.partials.findings-styles')
@include('initiatives.school_education.psrp_shared.records-styles')
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
@include('initiatives.school_education.additional_classrooms.partials.findings-script')
@include('initiatives.school_education.psrp_shared.records-script')
@include('initiatives.school_education.psrp_shared.pdf-export')
@endpush
