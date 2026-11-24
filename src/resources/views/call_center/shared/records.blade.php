{{--
    A call center campaign's records table: every row of its call files, a page at a time, refreshed on its own
    when a filter or table control changes. It is psrp_shared/records worded for calls, with the same element
    names, so psrp_shared/records-script and records-styles work on it unchanged.
--}}
@php
    $plural = static fn ($word, $count) => \Illuminate\Support\Str::plural($word, (int) $count);
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);

    $cellText = static function ($value, array $column) use ($number) {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($column['type']) {
            'date' => $value->format('j M Y'),
            'number' => $number($value),
            default => (string) $value,
        };
    };

    // Everything kept for a call, shown when its row is opened.
    $callDetail = static function ($call) use ($detailFields) {
        $fields = [];

        foreach ($detailFields as $column => $label) {
            $value = $call->{$column};

            $fields[] = [
                'label' => $label,
                'value' => match (true) {
                    $value === null || $value === '' => '—',
                    $value instanceof \DateTimeInterface => $value->format('j M Y'),
                    default => (string) $value,
                },
            ];
        }

        return [
            'title' => $call->applicant_name ?: 'Call, Sr. No '.number_format((int) $call->source_sr),
            'subtitle' => trim(($call->home_district ?? '').' · '.($call->call_date?->format('j M Y') ?? 'No call date'), ' ·'),
            'fields' => $fields,
        ];
    };

    $numericTypes = ['number'];
    $rowCount = $records->total();
@endphp

<form id="psrpRecordsForm" method="GET" action="{{ route('initiatives.show', $initiative) }}#psrp-records" hidden>
    @foreach ($filters as $filter => $value)
        @if ($value !== '')
            <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
        @endif
    @endforeach

    @if (request()->filled('sort'))
        <input type="hidden" name="sort" value="{{ $table['sort'] }}">
        <input type="hidden" name="dir" value="{{ $table['dir'] }}">
    @endif

    {{-- Lets Enter submit the form; JavaScript handles every other change. --}}
    <button type="submit" hidden></button>
</form>

<div class="psrp-toolbar">
    <div class="psrp-field psrp-field--search">
        <label for="psrpSearch">Search</label>
        <input
            class="psrp-control psrp-control--text"
            id="psrpSearch"
            type="search"
            name="search"
            form="psrpRecordsForm"
            value="{{ $table['search'] }}"
            maxlength="100"
            placeholder="{{ $searchHint }}"
        >
    </div>

    @unless ($dataChecks === [])
        <div class="psrp-field psrp-field--check">
            <label for="psrpCheck">Data check</label>
            <select class="psrp-control" id="psrpCheck" name="check" form="psrpRecordsForm">
                <option value="">All calls</option>
                @foreach ($dataChecks as $key => $check)
                    <option value="{{ $key }}" @selected($table['check'] === $key)>{{ $check['label'] }} ({{ $number($check['count']) }})</option>
                @endforeach
            </select>
        </div>
    @endunless

    <div class="psrp-toolbar-actions">
        <button
            type="button"
            class="psrp-btn"
            id="psrpCopyCsv"
            data-url="{{ $exportUrl }}"
            aria-live="polite"
        >Copy CSV</button>
        <a class="psrp-btn psrp-btn--primary" href="{{ $exportUrl }}">Download CSV</a>
    </div>
</div>

<div class="psrp-table-wrapper" role="region" aria-label="Calls table" tabindex="0">
    <table class="psrp-table">
        <thead>
            <tr>
                @foreach ($table['columns'] as $key => $column)
                    @php
                        $isSorted = $table['sort'] === $key;
                        $nextDir = $isSorted && $table['dir'] === 'asc' ? 'desc' : 'asc';
                    @endphp
                    <th
                        scope="col"
                        @class(['is-numeric' => in_array($column['type'], $numericTypes, true)])
                        aria-sort="{{ $isSorted ? ($table['dir'] === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                    >
                        <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir, 'page' => null]) }}#psrp-records">
                            <span>{{ $column['label'] }}</span>
                            <span @class(['psrp-sort-mark', 'is-sorted' => $isSorted]) aria-hidden="true">{{ $isSorted ? ($table['dir'] === 'asc' ? '▲' : '▼') : '↕' }}</span>
                        </a>
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($records as $row)
                <tr class="psrp-visit-row" data-visit="{{ json_encode($callDetail($row)) }}">
                    @foreach ($table['columns'] as $key => $column)
                        @php $value = data_get($row, $key); @endphp
                        <td @class([
                            'is-numeric' => in_array($column['type'], $numericTypes, true),
                            'is-bad' => $key === 'counted_text' && $value !== 'Yes',
                        ])>
                            @if ($loop->first)
                                <button type="button" class="psrp-visit-open" aria-label="Open call details for Sr. No {{ number_format((int) $row->source_sr) }}">{{ $cellText($value, $column) }}</button>
                            @else
                                {{ $cellText($value, $column) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="psrp-table-empty" colspan="{{ count($table['columns']) }}">
                        {{ $totals['visits'] === 0
                            ? 'No call files have been uploaded for this page yet.'
                            : 'No calls match these filters.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="psrp-table-footer">
    <p>
        @if ($rowCount > 0)
            Rows {{ $number($records->firstItem()) }} to {{ $number($records->lastItem()) }}
            of {{ $number($rowCount) }} {{ $plural('call', $rowCount) }}
        @else
            0 calls
        @endif
    </p>

    <div class="psrp-pagination">
        <select class="psrp-control psrp-control--small" name="per_page" form="psrpRecordsForm" aria-label="Rows per page">
            @foreach ($perPageOptions as $option)
                <option value="{{ $option }}" @selected($table['per_page'] === $option)>{{ $option }} rows</option>
            @endforeach
        </select>

        @if ($records->onFirstPage())
            <span class="psrp-btn is-disabled" aria-disabled="true">Previous</span>
        @else
            <a class="psrp-btn" href="{{ $records->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        <span class="psrp-page-count">Page {{ $number($records->currentPage()) }} of {{ $number($records->lastPage()) }}</span>

        @if ($records->hasMorePages())
            <a class="psrp-btn" href="{{ $records->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="psrp-btn is-disabled" aria-disabled="true">Next</span>
        @endif
    </div>
</div>
