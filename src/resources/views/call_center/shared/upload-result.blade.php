{{--
    The result of the last background upload, shown once to whoever uploaded it (see the campaign controller's
    uploads()). It looks like the other pages' upload result (psrp_shared/import-result), which shows a failure
    the same way.
--}}
@php
    $upload = $uploads['result'] ?? null;
    $result = $upload?->result ?? [];
    $isList = $upload?->type === \App\Models\InitiativeFile::TYPE_DEPARTMENT_LIST;
    $links = $result['links'] ?? null;

    // A list uploaded before any call file has no calls to count yet.
    if ($links !== null && $links['counted'] + $links['copies'] + $links['notListed'] === 0) {
        $links = null;
    }
@endphp

@if ($upload !== null)
    @if (isset($result['failure']))
        @php $importResult = $result; @endphp
        @include('initiatives.school_education.psrp_shared.import-result')
    @else
        <div class="psrp-import-result" id="psrp-import-result" role="status" tabindex="-1">
            <h4>
                <i data-lucide="circle-check" aria-hidden="true"></i>
                {{ $result['fileName'] ?? $upload->original_name }} uploaded
            </h4>

            <dl class="psrp-import-counts">
                <div>
                    <dt>{{ $isList ? 'Students saved' : 'Rows added' }}</dt>
                    <dd>{{ $number($result['added'] ?? null) }}</dd>
                </div>
                @if ($links !== null)
                    <div>
                        <dt>Calls counted on the page</dt>
                        <dd>{{ $number($links['counted']) }}</dd>
                    </div>
                    <div>
                        <dt>Rows repeating earlier rows</dt>
                        <dd>{{ $number($links['copies']) }}</dd>
                    </div>
                    <div>
                        <dt>Calls to people not in the list</dt>
                        <dd>{{ $number($links['notListed']) }}</dd>
                    </div>
                @endif
            </dl>

            @if ($links !== null)
                <p class="psrp-import-note">
                    <strong>The last three are for every call file on the page.</strong>
                    Calls were matched to the department's list by CNIC ({{ $number($links['cnic']) }}), by phone number ({{ $number($links['phone']) }})
                    and by alt phone number ({{ $number($links['alt_phone']) }}). Rows repeating earlier rows, one after another in the same order, are kept in the records below but not counted.
                </p>
            @endif

            @if (! empty($result['universities']))
                <p class="psrp-import-note">
                    <strong>Universities sheet read:</strong>
                    {{ $number($result['universities']['names']) }} universities and colleges, joining {{ $number($result['universities']['spellings']) }} spellings in the department's list. They replace the earlier ones.
                </p>
            @endif

            @if (! empty($result['replaced']))
                <p class="psrp-import-note">
                    <strong>It replaced</strong> {{ implode(', ', $result['replaced']) }}.
                </p>
            @endif

            @if (! empty($result['ignoredColumns']))
                <p class="psrp-import-note">
                    <strong>Columns not recognised, so not saved:</strong>
                    {{ implode(', ', $result['ignoredColumns']) }}
                </p>
            @endif

            @if (! empty($result['missingColumns']))
                <p class="psrp-import-note">
                    <strong>Columns not in this file, so left blank:</strong>
                    {{ implode(', ', $result['missingColumns']) }}
                </p>
            @endif
        </div>
    @endif
@endif
