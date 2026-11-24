@extends('layouts.app')

@section('title', $initiative->name)

{{--
    CM Punjab Honhaar Scholarship, a call center campaign: the "Honhaar Scholarship Call Center Feedback" design,
    worked out on the server from the uploads (see HonhaarDashboard), with the four uploads and every call below
    it in place of the design's "About the data".
--}}

@section('content')
@php
    // Used by the upload result boxes (call_center/shared/upload-result and psrp_shared/import-result).
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);
    $importResult = null;
    $listType = \App\Models\InitiativeFile::TYPE_DEPARTMENT_LIST;
    $callsType = \App\Models\InitiativeFile::TYPE_OUR_VISITS;
    $responseType = \App\Models\InitiativeFile::TYPE_DEPARTMENT_RESPONSE;
    $backgroundUrl = route('initiatives.call-center.uploads.store', $initiative);
    $maxMb = (int) config('call_center.max_upload_mb', 100);
    $backgroundNote = 'The file is read in the background, with a bar showing how far it has got. You can leave the page meanwhile.';
@endphp

<div class="psrp-page" id="psrpPage" data-url="{{ route('initiatives.honhaar-scholarship.data', $initiative) }}">

    <nav class="psrp-breadcrumb psrp-wrap" aria-label="Breadcrumb">
        <a href="{{ route('call-center.index') }}">Call Center</a>
        <i data-lucide="chevron-right" aria-hidden="true"></i>
        <span aria-current="page">{{ $initiative->name }}</span>
        {{-- The page as it is on the screen, as a PDF download (see psrp_shared/pdf-export) --}}
        <button type="button" class="psrp-pdf-btn" id="pdfExport" data-pdf-skip data-pdf-name="{{ $initiative->name }}">
            <i data-lucide="file-down" aria-hidden="true"></i>
            <span>Export to PDF</span>
        </button>
    </nav>

    {{-- Filters: these apply to the whole page. The dates choose which calls count. --}}
    <div class="psrp-findings filterbar">
        <form class="psrp-wrap" id="psrpFilters" method="GET" action="{{ route('initiatives.show', $initiative) }}" style="--nav-pads: 16">
            <span class="fg">
                <label for="f-district">District</label>
                <select id="f-district" class="ctl" name="district">
                    <option value="">All Punjab</option>
                    @if ($filters['district'] !== '')
                        <option selected>{{ $filters['district'] }}</option>
                    @endif
                </select>
            </span>
            <span class="fg">
                <label for="f-from">From</label>
                <input id="f-from" class="ctl" type="date" name="from" value="{{ $filters['from'] }}" title="Calls on or after this date">
            </span>
            <span class="fg">
                <label for="f-to">To</label>
                <input id="f-to" class="ctl" type="date" name="to" value="{{ $filters['to'] }}" title="Calls on or before this date">
            </span>
            <noscript><button type="submit" class="ctl">Apply</button></noscript>
            <button type="button" class="link" id="f-clear" hidden>Clear filters</button>
            <div class="fb-meta">
                <nav class="nav" aria-label="Sections">
                    <a href="#satisfaction">Satisfaction</a><a href="#impact">Impact</a><a href="#issues">Issues</a><a href="#answers">Other answers</a><a href="#unis">Universities</a><a href="#districts">Districts</a><a href="#calls">Repeat calls</a><a href="#psrp-records">Records</a>
                </nav>
                {{-- The section links that do not fit beside the filters, so the bar keeps one row (see the script) --}}
                <details class="nav-more" id="nav-more" hidden>
                    <summary>More <i data-lucide="chevron-down" aria-hidden="true"></i></summary>
                    <div class="nav-more-menu" id="nav-more-list"></div>
                </details>
            </div>
        </form>
    </div>

    <div class="psrp-findings psrp-wrap" id="psrpFindings">
        <p class="note" id="load-note" aria-live="polite">Loading the figures…</p>
        <p class="note" id="scope-note" hidden></p>

        <section id="satisfaction" aria-labelledby="h-sat">
            <div class="sec-h"><h2 id="h-sat">Calls and satisfaction</h2><p>Every student who reported a problem on the call is counted as unsatisfied: money not received, a bribe, the university not cooperating, and so on. This holds whatever they answered when asked "Are you satisfied with the programme?"</p></div>
            <div class="g2">
                <div class="card pad">
                    <h3>Did the call reach the student?</h3>
                    <p class="sub">All calls made, including repeat calls to the same student.</p>
                    <div id="out-rows"></div>
                </div>
                <div class="card pad">
                    <h3>Satisfaction of the students who gave feedback</h3>
                    <div class="sbar" id="s-bar" role="img"></div>
                    <div class="skey">
                        <div><span><i class="sw g"></i>No issue reported: satisfied</span><b id="s-ok"></b></div>
                        <div><span><i class="sw c"></i>Reported an issue: unsatisfied</span><b id="s-iss"></b></div>
                    </div>
                    <p class="note">Students reached more than once are counted from their latest call, so a problem that was solved by a later call is not counted. A bribe counts if it was reported on any call.</p>
                </div>
            </div>
        </section>

        <section id="impact" aria-labelledby="h-impact">
            <div class="sec-h"><h2 id="h-impact">What the follow-up calls show</h2><p>The call center called many students again. A repeat call checks whether a problem the student reported earlier has been fixed.</p></div>
            <div class="impact two" aria-label="Impact of the monitoring calls">
                <div class="imp-card" id="imp-1"></div>
                <div class="imp-card" id="imp-2"></div>
            </div>
            <p class="imp-note" id="imp-note"></p>
        </section>

        <section id="issues" aria-labelledby="h-iss">
            <div class="sec-h"><h2 id="h-iss">Issues students reported</h2><p>Students, counted once each, from their latest call. Shares are of the students who gave feedback (for the bribe question, of the students who were asked it). A few students reported more than one issue.</p></div>
            <div class="card pad" id="iss-rows"></div>
        </section>

        <section id="answers" aria-labelledby="h-ans">
            <div class="sec-h"><h2 id="h-ans">Answers to the other call questions</h2><p>Each call asked up to six questions. The bribe question, any other problem, and "Are you satisfied?" are covered above.</p></div>
            <div class="g2">
                <div class="card pad">
                    <h3>Has the scholarship reached you?</h3>
                    <span class="asked">Dec 2024 &ndash; Feb 2025: "first cheque received?" &middot; from Jul 2025: "receiving the scholarship?"</span>
                    <div class="yn" id="rv-bar" role="img"></div>
                    <div class="ynk" id="rv-key"></div>
                    <p class="note">Students who said no are in the issues above, under the reason they gave: 2nd cheque not received, not eligible, name issue and so on.</p>
                </div>
                <div class="card pad">
                    <h3>Which year of the degree are you in?</h3>
                    <span class="asked" id="yr-asked">Asked mainly Dec 2024 &ndash; Feb 2025</span>
                    <div id="yr-rows" style="margin-top:10px"></div>
                </div>
            </div>
        </section>

        <section id="unis" aria-labelledby="h-unis">
            <div class="sec-h"><h2 id="h-unis">Universities and colleges</h2><p>Scholarship students are counted from the department's list. Calls, feedback and issues are counted for the students the call center reached, under their university or college. One spelling is used for each institution (the lists spell the same university up to four ways).</p></div>
            <div class="types" id="types"></div>
            <div class="card tablewrap">
                <div class="tbl-tools">
                    <div class="seg" role="group" aria-label="Sort universities">
                        <button type="button" data-u="iss" aria-pressed="true">Most issues</button>
                        <button type="button" data-u="list" aria-pressed="false">Most scholarship students</button>
                        <button type="button" data-u="low" aria-pressed="false">Lowest satisfaction</button>
                    </div>
                    <span class="note" id="u-note" style="margin-top:0"></span>
                </div>
                <table class="dt ut" id="ut"></table>
                <div class="more-row"><button type="button" class="link" id="u-more"></button></div>
            </div>
        </section>

        <section id="districts" aria-labelledby="h-dist">
            <div class="sec-h">
                <div class="sec-h-row">
                    <h2 id="h-dist">District-wise analysis</h2>
                    {{-- This section only, as a PDF download (see psrp_shared/pdf-export) --}}
                    <button type="button" class="psrp-pdf-btn" data-pdf-section="districts" data-pdf-skip title="Export only this section to PDF">
                        <i data-lucide="file-down" aria-hidden="true"></i>
                        <span>Export to PDF</span>
                    </button>
                </div>
                <p>Each student's home district, taken from the department's scholarship list. Lowest satisfaction first. Click a district to focus the whole page on it. Click a column heading to sort.</p>
            </div>
            <div class="card tablewrap"><table class="dt" id="dt"></table></div>
        </section>

        <section id="calls" aria-labelledby="h-calls">
            <div class="sec-h"><h2 id="h-calls">Repeat calls: how often, and what changed</h2><p>Many students were called more than once: some because the first call did not connect, some for a follow-up.</p></div>
            <div class="g2">
                <div class="card pad">
                    <h3>How many times each student was called</h3>
                    <p class="sub" id="nc-sub"></p>
                    <div id="nc-rows"></div>
                </div>
                <div class="card pad">
                    <h3>Students who gave feedback more than once: did their answers change?</h3>
                    <p class="sub" id="ch-sub"></p>
                    <div id="ch-rows"></div>
                    <p class="note">One student can be in more than one of the change rows. The changes shown in "What the follow-up calls show" are not repeated here. The page uses each student's latest call. Each student's calls are in the records below.</p>
                </div>
            </div>
        </section>
    </div>

    {{-- In place of the design's "About the data": the uploads and every call on file. --}}
    <div class="psrp-wrap">
        <section class="psrp-records" id="psrp-records" aria-labelledby="h-records">
            <div class="psrp-findings">
                <div class="sec-h">
                    <h2 id="h-records">Explore the records</h2>
                    <p>Every call in the selection, as in the call files, with whether the page counts it. Sort by any column, search by name, CNIC, phone, institute or district, and click a call to see everything kept for it. CNIC and phone numbers are shown partly hidden.</p>
                </div>
            </div>

            {{-- A file being read in the background, then the result of the last upload --}}
            @include('call_center.shared.upload-progress')
            @include('call_center.shared.upload-result')

            <x-initiative-files
                :initiative="$initiative"
                :files="$files"
                :visits-upload-url="$backgroundUrl"
                list-items="students"
                :background-uploads="[$callsType => $backgroundUrl, $listType => $backgroundUrl]"
                :type-overrides="[
                    $callsType => [
                        'viewButton' => 'Call files',
                        'title' => 'Upload call records',
                        'hint' => 'CSV or Excel (.xlsx), up to '.$maxMb.' MB',
                        'listTitle' => 'Uploaded call files',
                        'empty' => 'No call files have been uploaded yet.',
                        'notes' => [
                            'Each file adds its calls to the earlier files. Its Sr. No may start again at 1.',
                            'The same file cannot be added twice. Rows that repeat earlier rows one after another, in the same order (a file pasted in twice), are kept but not counted.',
                            'When the file has a Universities sheet, its single name for each university replaces the earlier one.',
                            'Every row is checked first. If any row has a problem, nothing is saved and the problems are listed.',
                            $backgroundNote,
                        ],
                        'deleteWarning' => 'The calls saved from it are removed from this page too.',
                        'listNote' => 'Deleting a file here removes it and the calls saved from it.',
                    ],
                    $listType => [
                        'hint' => 'CSV or Excel (.xlsx), up to '.$maxMb.' MB',
                        'notes' => [
                            'A new list replaces the earlier one, and every call is linked to the new list.',
                            'Every row is checked first. If any row has a problem, nothing is saved and the problems are listed.',
                            $backgroundNote,
                        ],
                        'deleteWarning' => 'The students saved from it are removed from this page too, so no call counts until a new list is uploaded.',
                    ],
                    $responseType => [
                        'hint' => 'PDF only, up to 100 MB',
                        'accept' => '.pdf',
                        'showEntries' => false,
                        'notes' => [
                            'Letters from the department are kept here and replace nothing.',
                            'A response file in CSV or Excel can be added once the department sends one and its columns are set up.',
                        ],
                        'deleteWarning' => '',
                    ],
                ]"
            />

            <div id="psrpRecordsBody">
                @include('call_center.shared.records')
            </div>
        </section>

        <p class="psrp-findings honhaar-sources">Sources: call center feedback calls to Honhaar Scholarship students (the call files), and the department's scholarship list (CM Honhaar Phase-II 20K and Honhaar Active Students 28287) for each student's district and university.</p>
    </div>

    {{-- Filled from the clicked row by the script --}}
    @include('initiatives.school_education.psrp_shared.visit-dialog')
</div>

{{-- Outside the page's container, which would otherwise change where it can be placed --}}
<div class="psrp-findings psrp-tip" id="tip" hidden></div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..800&display=swap">
@include('initiatives.school_education.psrp_shared.findings-styles')
@include('initiatives.higher_education.honhaar_scholarship.partials.findings-styles')
@include('initiatives.school_education.psrp_shared.records-styles')
<style>
    /* Long comments are cut short in the table; the call's window shows them in full. */
    #psrpRecordsBody .psrp-table td { max-width: 320px; overflow: hidden; text-overflow: ellipsis; }
</style>
@endpush

@push('scripts')
@include('initiatives.higher_education.honhaar_scholarship.partials.findings-script')
@include('initiatives.school_education.psrp_shared.records-script')
@include('initiatives.school_education.psrp_shared.pdf-export')
@endpush
