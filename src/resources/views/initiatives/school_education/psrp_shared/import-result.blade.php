    @if ($importResult)
        <div
            @class([
                'psrp-import-result',
                'is-failed' => isset($importResult['failure']),
            ])
            id="psrp-import-result"
            role="status"
            tabindex="-1"
        >
            @isset($importResult['failure'])
                <h4>
                    <i data-lucide="circle-x" aria-hidden="true"></i>
                    Upload failed. Nothing was saved.
                </h4>
                <p>{{ $importResult['failure'] }}</p>

                @if (! empty($importResult['ignoredColumns']))
                    <p class="psrp-import-note">
                        <strong>Headings found in the file:</strong>
                        {{ implode(', ', $importResult['ignoredColumns']) }}
                    </p>
                @endif

                @if (! empty($importResult['problems']))
                    @if ($importResult['problemCount'] > count($importResult['problems']))
                        <p class="psrp-import-note">
                            Showing the first {{ $number(count($importResult['problems'])) }}
                            of {{ $number($importResult['problemCount']) }} problems.
                        </p>
                    @endif

                    <div class="psrp-import-table-wrapper" role="region" aria-label="Upload problems" tabindex="0">
                        <table class="psrp-import-table">
                            <thead>
                                <tr>
                                    <th scope="col">Row</th>
                                    <th scope="col">Column</th>
                                    <th scope="col">Value in file</th>
                                    <th scope="col">Problem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($importResult['problems'] as $problem)
                                    <tr>
                                        <td>{{ $number($problem['row']) }}</td>
                                        <td>{{ $problem['column'] }}</td>
                                        <td>{{ $problem['value'] === '' ? '(empty)' : $problem['value'] }}</td>
                                        <td>{{ $problem['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <h4>
                    <i data-lucide="circle-check" aria-hidden="true"></i>
                    {{ $importResult['fileName'] }} uploaded
                </h4>

                <dl class="psrp-import-counts">
                    <div>
                        <dt>Added</dt>
                        <dd>{{ $number($importResult['added']) }}</dd>
                    </div>
                    <div>
                        <dt>Updated</dt>
                        <dd>{{ $number($importResult['updated']) }}</dd>
                    </div>
                    <div>
                        <dt>Already up to date</dt>
                        <dd>{{ $number($importResult['unchanged']) }}</dd>
                    </div>
                </dl>

                @if ($importResult['ignoredColumns'] !== [])
                    <p class="psrp-import-note">
                        <strong>Columns not recognised, so not saved:</strong>
                        {{ implode(', ', $importResult['ignoredColumns']) }}
                    </p>
                @endif

                @if ($importResult['missingColumns'] !== [])
                    <p class="psrp-import-note">
                        <strong>Columns not in this file, so left as they were:</strong>
                        {{ implode(', ', $importResult['missingColumns']) }}
                    </p>
                @endif

                @if (($importResult['datesCorrected'] ?? 0) > 0)
                    <p class="psrp-import-note">
                        <strong>{{ $number($importResult['datesCorrected']) }} visit dates were in the future,</strong>
                        so their day and month were swapped.
                    </p>
                @endif

                @if (($importResult['yearsCorrected'] ?? 0) > 0)
                    <p class="psrp-import-note">
                        <strong>{{ $number($importResult['yearsCorrected']) }} visit dates were before this phase began,</strong>
                        so their year was corrected.
                    </p>
                @endif

                @if (($importResult['coordinatesIgnored'] ?? 0) > 0)
                    <p class="psrp-import-note">
                        <strong>{{ $number($importResult['coordinatesIgnored']) }} coordinates could not be used,</strong>
                        so they were left blank.
                    </p>
                @endif

                @if (($importResult['fileKept'] ?? true) === false)
                    <p class="psrp-import-note">
                        <strong>The rows were saved, but the file could not be kept,</strong>
                        so it will not appear in the previous files list.
                    </p>
                @endif
            @endisset
        </div>
    @endif
