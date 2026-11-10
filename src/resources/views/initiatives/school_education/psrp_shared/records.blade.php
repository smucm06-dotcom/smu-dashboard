{{-- The part of the page that is refreshed on its own when a filter or table control changes. --}}
@php
    $plural = static fn ($word, $count) => \Illuminate\Support\Str::plural($word, (int) $count);
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);
    $percent = static fn ($value) => $value === null ? '—' : number_format((float) $value, 1).'%';
    $signed = static fn ($value, $text) => ($value > 0 ? '+' : '').$text;

    $cellText = static function ($value, array $column) use ($number, $percent, $signed) {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($column['type']) {
            'date' => $value->format('j M Y'),
            'boolean' => $value ? 'Yes' : 'No',
            'number' => $number($value),
            'change' => $signed($value, $number($value)),
            'percent' => $percent($value),
            'change_percent' => $signed($value, $percent($value)),
            default => (string) $value,
        };
    };

    $isBadCell = static function ($value, array $column) {
        if (in_array($column['type'], ['change', 'change_percent'], true)) {
            return $value !== null && $value < 0;
        }

        return array_key_exists('bad', $column) && $value === $column['bad'];
    };

    $numericTypes = ['number', 'change', 'percent', 'change_percent'];

    // Pages whose name column is not "school_name" say which one it is.
    $nameColumn ??= 'school_name';

    // Everything recorded for a visit, shown when its row is opened.
    $visitDetail = static function ($visit) use ($detailFields, $detailDateFormat, $nameColumn) {
        $fields = [];

        foreach ($detailFields as $column => $label) {
            $value = $visit->{$column};

            $fields[] = [
                'label' => $label,
                'value' => match (true) {
                    $value === null || $value === '' => '—',
                    is_bool($value) => $value ? 'Yes' : 'No',
                    $column === 'visited_at' => $value->format($detailDateFormat),
                    default => (string) $value,
                },
            ];
        }

        return [
            'title' => $visit->{$nameColumn} ?: 'EMIS '.$visit->emis_code,
            'subtitle' => trim(($visit->district ?? '').' · '.$visit->visited_at->format('j M Y'), ' ·'),
            'fields' => $fields,
        ];
    };

    $showingDistricts = $table['tab'] === 'districts';
    $rows = $showingDistricts ? $districtRows : $records;
    $rowCount = $showingDistricts ? $rows->count() : $rows->total();
@endphp

@if ($hasDistrictTab)
<nav class="psrp-tabs" aria-label="Record views">
    <a
        @class(['is-active' => ! $showingDistricts])
        href="{{ request()->fullUrlWithQuery(['tab' => null, 'sort' => null, 'dir' => null, 'page' => null]) }}#psrp-records"
        @if (! $showingDistricts) aria-current="page" @endif
    >School visits</a>
    <a
        @class(['is-active' => $showingDistricts])
        href="{{ request()->fullUrlWithQuery(['tab' => 'districts', 'sort' => null, 'dir' => null, 'page' => null]) }}#psrp-records"
        @if ($showingDistricts) aria-current="page" @endif
    >District summary</a>
</nav>
@endif

<form id="psrpRecordsForm" method="GET" action="{{ route('initiatives.show', $initiative) }}#psrp-records" hidden>
    @foreach ($filters as $filter => $value)
        @if ($value !== '')
            <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
        @endif
    @endforeach

    @if ($showingDistricts)
        <input type="hidden" name="tab" value="districts">
    @endif

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
            placeholder="{{ $showingDistricts ? 'District' : $searchHint }}"
        >
    </div>

    @unless ($showingDistricts || $dataChecks === [])
        <div class="psrp-field psrp-field--check">
            <label for="psrpCheck">Data check</label>
            <select class="psrp-control" id="psrpCheck" name="check" form="psrpRecordsForm">
                <option value="">All visits</option>
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

<div
    class="psrp-table-wrapper"
    role="region"
    aria-label="{{ $showingDistricts ? 'District summary table' : 'School visits table' }}"
    tabindex="0"
>
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
            @forelse ($rows as $row)
                <tr @unless ($showingDistricts) class="psrp-visit-row" data-visit="{{ json_encode($visitDetail($row)) }}" @endunless>
                    @foreach ($table['columns'] as $key => $column)
                        @php $value = data_get($row, $key); @endphp
                        <td @class([
                            'is-numeric' => in_array($column['type'], $numericTypes, true),
                            'is-code' => $column['type'] === 'code',
                            'is-bad' => $isBadCell($value, $column),
                        ])>
                            @if ($loop->first && ! $showingDistricts)
                                <button type="button" class="psrp-visit-open" aria-label="Open visit details for EMIS {{ $row->emis_code }}">{{ $cellText($value, $column) }}</button>
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
                            ? 'No visits have been imported for this initiative yet.'
                            : 'No records match these filters.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="psrp-table-footer">
    @if ($showingDistricts)
        <p>{{ $number($rowCount) }} {{ $plural('district', $rowCount) }}</p>
    @else
        <p>
            @if ($rowCount > 0)
                Rows {{ $number($rows->firstItem()) }} to {{ $number($rows->lastItem()) }}
                of {{ $number($rowCount) }} {{ $plural('visit', $rowCount) }}
            @else
                0 visits
            @endif
        </p>

        <div class="psrp-pagination">
            <select class="psrp-control psrp-control--small" name="per_page" form="psrpRecordsForm" aria-label="Rows per page">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($table['per_page'] === $option)>{{ $option }} rows</option>
                @endforeach
            </select>

            @if ($rows->onFirstPage())
                <span class="psrp-btn is-disabled" aria-disabled="true">Previous</span>
            @else
                <a class="psrp-btn" href="{{ $rows->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            <span class="psrp-page-count">Page {{ $number($rows->currentPage()) }} of {{ $number($rows->lastPage()) }}</span>

            @if ($rows->hasMorePages())
                <a class="psrp-btn" href="{{ $rows->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="psrp-btn is-disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    @endif
</div>
