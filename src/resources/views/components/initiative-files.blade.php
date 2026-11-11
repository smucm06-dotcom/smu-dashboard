@props(['initiative', 'files', 'visitsUploadUrl', 'visitMatch' => 'visit date and time'])

@php
    $visitsType = \App\Models\InitiativeFile::TYPE_OUR_VISITS;
    $listType = \App\Models\InitiativeFile::TYPE_DEPARTMENT_LIST;
    $reportType = \App\Models\InitiativeFile::TYPE_OUR_REPORT;
    $responseType = \App\Models\InitiativeFile::TYPE_DEPARTMENT_RESPONSE;

    $currentList = $files->get($listType)?->first();

    $types = [
        $visitsType => [
            'button' => 'Upload CSV',
            'icon' => 'upload',
            'title' => 'Upload visit records',
            'hint' => 'CSV or Excel (.xlsx), up to 20 MB',
            'accept' => '.csv,.xlsx',
            'listTitle' => 'Uploaded visit files',
            'empty' => 'No visit files have been uploaded yet.',
            'showEntries' => true,
            'notes' => [
                'You can upload the same file again after adding rows. Visits are matched by EMIS code and '.$visitMatch.', so nothing is duplicated.',
                'New visits are added and changed ones are updated. Visits not in the file are kept.',
                'Every row is checked first. If any row has a problem, nothing is saved and the problems are listed.',
            ],
            'deleteWarning' => 'Only the file is removed. Visit records already saved from it stay in the table.',
        ],
        $listType => [
            'button' => 'Department list',
            'icon' => 'file-spreadsheet',
            'title' => 'Upload department list',
            'hint' => 'CSV or Excel (.xlsx), up to 20 MB',
            'accept' => '.csv,.xlsx',
            'listTitle' => 'Current department list',
            'empty' => 'No department list has been uploaded yet.',
            'showEntries' => true,
            'deleteWarning' => 'The schools saved from it are removed from this page too.',
        ],
        $reportType => [
            'button' => 'Upload report',
            'icon' => 'file-text',
            'title' => 'Upload report',
            'hint' => 'PDF only, up to 100 MB',
            'accept' => '.pdf',
            'listTitle' => 'Uploaded reports',
            'empty' => 'No reports have been uploaded yet.',
        ],
        $responseType => [
            'button' => 'Department response',
            'icon' => 'file-check',
            'title' => 'Upload department response',
            'hint' => 'PDF, CSV or Excel (.xlsx), up to 100 MB',
            'accept' => '.pdf,.csv,.xlsx',
            'listTitle' => 'Uploaded department responses',
            'empty' => 'No department responses have been uploaded yet.',
            'showEntries' => true,
            'notes' => [
                'A CSV or Excel file replaces the earlier one, so the page always compares against the latest file. The earlier file and its replies are deleted.',
                'PDF letters from the department are kept beside it and replace nothing.',
                'If the new file cannot be read, nothing changes.',
            ],
            'deleteWarning' => 'Replies saved from it are removed from this page too.',
        ],
    ];

    // The response file whose replies the page uses now. A new CSV or Excel file replaces it.
    $currentResponse = $files->get($responseType, collect())
        ->first(fn ($file) => strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION)) !== 'pdf');

    $failedUpload = session('initiativeFileError');
    $failedType = $failedUpload['type'] ?? null;
    $deletedFile = session('initiativeFileDeleted');
@endphp

<div class="ifile-section" id="initiative-files">
    @if (session('initiativeFileSaved'))
        <p class="ifile-saved" role="status">
            <i data-lucide="circle-check" aria-hidden="true"></i>
            {{ session('initiativeFileSaved') }}
        </p>
    @endif

    <div class="ifile-buttons">
        @foreach ($types as $type => $config)
            @php $count = $files->get($type, collect())->count(); @endphp
            <button
                type="button"
                class="ifile-btn"
                data-file-dialog="initiativeFileDialog-{{ $type }}"
                aria-label="{{ $config['button'] }}, {{ number_format($count) }} uploaded"
            >
                <i data-lucide="{{ $config['icon'] }}" aria-hidden="true"></i>
                {{ $config['button'] }}
                <span class="ifile-count" aria-hidden="true">{{ number_format($count) }}</span>
            </button>
        @endforeach
    </div>
</div>

@foreach ($types as $type => $config)
    @php
        $isVisits = $type === $visitsType;
        $hasFailed = $failedType === $type;
        $hasDeleted = ($deletedFile['type'] ?? null) === $type;
        $showEntries = $config['showEntries'] ?? false;
        $uploadedFiles = $files->get($type, collect());
    @endphp

    <dialog
        class="ifile-dialog"
        id="initiativeFileDialog-{{ $type }}"
        aria-labelledby="initiativeFileTitle-{{ $type }}"
        @if ($hasFailed || $hasDeleted) data-open-on-load @endif
    >
        <div class="ifile-dialog-header">
            <div>
                <h4 id="initiativeFileTitle-{{ $type }}">{{ $config['title'] }}</h4>
                <p>{{ $config['hint'] }}</p>
            </div>
            <button type="button" class="ifile-dialog-close" data-file-dialog-close aria-label="Close">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </div>

        <form
            class="ifile-form"
            method="POST"
            action="{{ $isVisits ? $visitsUploadUrl : route('initiatives.files.store', $initiative) }}"
            enctype="multipart/form-data"
            @if ($type === $listType && $currentList)
                data-confirm="Replace &quot;{{ $currentList->original_name }}&quot; with the new file? The current list will be permanently deleted."
            @elseif ($type === $responseType && $currentResponse)
                data-confirm="A CSV or Excel file replaces &quot;{{ $currentResponse->original_name }}&quot;, and its replies will be permanently deleted. A PDF is only added. Continue?"
            @endif
        >
            @csrf

            @unless ($isVisits)
                <input type="hidden" name="type" value="{{ $type }}">
            @endunless

            @if ($hasFailed)
                <p class="ifile-error" role="alert">{{ $failedUpload['message'] }}</p>
            @endif

            @if ($hasDeleted)
                <p class="ifile-saved" role="status">
                    <i data-lucide="circle-check" aria-hidden="true"></i>
                    {{ $deletedFile['message'] }}
                </p>
            @endif

            <label class="ifile-label" for="initiativeFileInput-{{ $type }}">File</label>
            <input
                class="ifile-file-input"
                id="initiativeFileInput-{{ $type }}"
                type="file"
                name="file"
                accept="{{ $config['accept'] }}"
                required
            >

            @if (! empty($config['notes']))
                <ul class="ifile-notes">
                    @foreach ($config['notes'] as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="ifile-actions">
                <button type="button" class="ifile-btn" data-file-dialog-close>Cancel</button>
                <button type="submit" class="ifile-btn ifile-btn--primary">Upload</button>
            </div>
        </form>

        <section class="ifile-uploaded" aria-labelledby="initiativeFileList-{{ $type }}">
            <h5 id="initiativeFileList-{{ $type }}">{{ $config['listTitle'] }} ({{ number_format($uploadedFiles->count()) }})</h5>

            @if ($uploadedFiles->isEmpty())
                <p class="ifile-empty">{{ $config['empty'] }}</p>
            @else
                <div class="ifile-table-wrapper" role="region" aria-label="{{ $config['listTitle'] }}" tabindex="0">
                    <table class="ifile-table">
                        <thead>
                            <tr>
                                <th scope="col">File</th>
                                <th scope="col">Uploaded</th>
                                @if ($showEntries)
                                    <th scope="col" class="is-numeric">Entries</th>
                                @endif
                                <th scope="col">Uploaded by</th>
                                <th scope="col" class="ifile-action-cell" aria-label="Delete"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($uploadedFiles as $file)
                                <tr>
                                    <td>
                                        <a href="{{ route('initiatives.files.download', [$initiative, $file]) }}" title="Download {{ $file->original_name }}">
                                            <i data-lucide="download" aria-hidden="true"></i>
                                            {{ $file->original_name }}
                                        </a>
                                    </td>
                                    {{-- Stored in UTC, shown in local time so the date is not a day behind. --}}
                                    <td class="is-nowrap">{{ $file->created_at->timezone('Asia/Karachi')->format('j M Y') }}</td>
                                    @if ($showEntries)
                                        <td class="is-numeric">{{ $file->row_count === null ? '—' : number_format($file->row_count) }}</td>
                                    @endif
                                    <td>{{ $file->uploader?->name ?? '—' }}</td>
                                    <td class="ifile-action-cell">
                                        <form
                                            class="ifile-delete-form"
                                            method="POST"
                                            action="{{ route('initiatives.files.destroy', [$initiative, $file]) }}"
                                            data-confirm="Delete &quot;{{ $file->original_name }}&quot;? {{ $config['deleteWarning'] ?? '' }} This cannot be undone."
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="ifile-delete-btn"
                                                aria-label="Delete {{ $file->original_name }}"
                                                title="Delete"
                                            >
                                                <i data-lucide="trash-2" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($isVisits)
                    <p class="ifile-list-note">Deleting a file here removes only the file, not the visit records saved from it.</p>
                @endif
            @endif
        </section>
    </dialog>
@endforeach

@push('styles')
<style>
    .ifile-section {
        margin-bottom: 16px;
        scroll-margin-top: var(--psrp-anchor-offset, 16px);
    }

    .ifile-saved {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        margin: 0 0 10px;
        padding: 10px 12px;
        font-size: 12px;
        line-height: 1.5;
        background: #EEF8F1;
        border: 1px solid #A9D8B8;
        border-radius: 9px;
        overflow-wrap: anywhere;
    }

    .ifile-saved svg {
        width: 16px;
        height: 16px;
        color: #146C2E;
    }

    .ifile-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .ifile-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 36px;
        padding: 0 12px;
        color: var(--text);
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 8px;
        transition: background .2s, border-color .2s;
    }

    .ifile-btn svg {
        width: 14px;
        height: 14px;
    }

    .ifile-btn:hover:not(:disabled) {
        background: var(--primary-light);
        border-color: var(--primary);
    }

    .ifile-btn:disabled {
        cursor: not-allowed;
        opacity: .6;
    }

    .ifile-btn--primary {
        color: #fff;
        font-weight: 600;
        background: var(--primary);
        border-color: var(--primary);
    }

    .ifile-btn--primary:hover:not(:disabled) {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .ifile-count {
        min-width: 20px;
        padding: 0 6px;
        color: var(--primary);
        font-size: 10px;
        font-weight: 600;
        line-height: 18px;
        text-align: center;
        background: var(--primary-light);
        border-radius: 999px;
    }

    .ifile-dialog {
        width: calc(100% - 32px);
        max-width: 680px;
        max-height: calc(100vh - 48px);
        max-height: calc(100dvh - 32px);
        padding: 0;
        color: var(--text);
        border: none;
        border-radius: 14px;
    }

    .ifile-dialog::backdrop {
        background: rgba(11, 69, 52, .45);
    }

    .ifile-dialog-header {
        position: sticky;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        background: var(--white);
        border-bottom: 1px solid var(--border);
    }

    .ifile-dialog-header h4 {
        margin: 0 0 3px;
        font-size: 15px;
        font-weight: 700;
    }

    .ifile-dialog-header p {
        margin: 0;
        color: var(--muted);
        font-size: 11px;
    }

    .ifile-dialog-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        padding: 0;
        color: var(--text);
        background: none;
        border: 1px solid var(--border);
        border-radius: 8px;
    }

    .ifile-dialog-close svg {
        width: 16px;
        height: 16px;
    }

    .ifile-form {
        padding: 16px 20px 0;
    }

    .ifile-error {
        margin: 0 0 12px;
        padding: 9px 12px;
        color: #A61B1B;
        font-size: 12px;
        line-height: 1.5;
        background: #FDF0F0;
        border: 1px solid #EDB9B9;
        border-radius: 9px;
        overflow-wrap: anywhere;
    }

    .ifile-label {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        font-weight: 600;
    }

    .ifile-file-input {
        display: block;
        width: 100%;
        padding: 9px;
        font-size: 12px;
        background: var(--bg);
        border: 1px dashed var(--border);
        border-radius: 9px;
    }

    .ifile-notes {
        margin: 12px 0 0;
        padding-left: 18px;
        color: var(--muted);
        font-size: 11px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .ifile-notes li + li {
        margin-top: 4px;
    }

    .ifile-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding: 14px 0 16px;
    }

    .ifile-uploaded {
        padding: 14px 20px 20px;
        background: var(--bg);
        border-top: 1px solid var(--border);
    }

    .ifile-uploaded h5 {
        margin: 0 0 10px;
        font-size: 12px;
        font-weight: 600;
    }

    .ifile-empty {
        margin: 0;
        color: var(--muted);
        font-size: 12px;
    }

    .ifile-table-wrapper {
        overflow-x: auto;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 9px;
    }

    .ifile-table {
        width: 100%;
        font-size: 11px;
        text-align: left;
        border-collapse: collapse;
    }

    .ifile-table th {
        padding: 8px 10px;
        font-weight: 600;
        white-space: nowrap;
        background: var(--primary-light);
    }

    .ifile-table td {
        padding: 8px 10px;
        vertical-align: top;
        border-top: 1px solid var(--border);
        overflow-wrap: anywhere;
    }

    .ifile-table td:first-child {
        min-width: 170px;
    }

    .ifile-table a {
        display: inline-flex;
        align-items: flex-start;
        gap: 5px;
        color: var(--primary);
        font-weight: 600;
    }

    .ifile-table a:hover {
        text-decoration: underline;
    }

    .ifile-table a svg {
        width: 13px;
        height: 13px;
        margin-top: 1px;
    }

    .ifile-table .is-numeric {
        text-align: right;
    }

    .ifile-table .is-nowrap {
        white-space: nowrap;
    }

    /* Stays in view while the table scrolls sideways on small screens. */
    .ifile-table .ifile-action-cell {
        position: sticky;
        right: 0;
        width: 1%;
        padding: 4px 8px;
        vertical-align: middle;
        background: var(--white);
        box-shadow: -1px 0 0 var(--border);
    }

    .ifile-table th.ifile-action-cell {
        background: var(--primary-light);
    }

    .ifile-delete-form {
        margin: 0;
    }

    .ifile-delete-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        color: #A61B1B;
        background: none;
        border: 1px solid transparent;
        border-radius: 7px;
        transition: background .2s, border-color .2s;
    }

    .ifile-delete-btn svg {
        width: 15px;
        height: 15px;
    }

    .ifile-delete-btn:hover {
        background: #FDF0F0;
        border-color: #EDB9B9;
    }

    .ifile-list-note {
        margin: 8px 0 0;
        color: var(--muted);
        font-size: 11px;
        line-height: 1.5;
    }

    .ifile-section button:focus-visible,
    .ifile-dialog a:focus-visible,
    .ifile-dialog button:focus-visible,
    .ifile-dialog input:focus-visible,
    .ifile-table-wrapper:focus-visible {
        outline: 2px solid var(--gold);
        outline-offset: 2px;
    }

    @media (max-width: 650px) {
        .ifile-buttons {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ifile-buttons .ifile-btn {
            padding: 0 8px;
            white-space: normal;
            line-height: 1.2;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-file-dialog]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById(button.dataset.fileDialog).showModal();
            });
        });

        document.querySelectorAll('.ifile-dialog').forEach(function (dialog) {
            const uploadForm = dialog.querySelector('.ifile-form');
            const submit = uploadForm.querySelector('button[type="submit"]');

            dialog.addEventListener('click', function (event) {
                if (event.target === dialog || event.target.closest('[data-file-dialog-close]')) {
                    dialog.close();
                }
            });

            uploadForm.addEventListener('submit', function (event) {
                if (uploadForm.dataset.confirm && ! window.confirm(uploadForm.dataset.confirm)) {
                    event.preventDefault();
                    return;
                }

                submit.disabled = true;
                submit.dataset.uploading = '1';
                submit.textContent = 'Uploading…';
            });

            dialog.querySelectorAll('.ifile-delete-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (! window.confirm(form.dataset.confirm)) {
                        event.preventDefault();
                    }
                });
            });

            if (dialog.hasAttribute('data-open-on-load')) {
                dialog.showModal();
            }
        });

        // Coming back with the browser's Back button must not leave a button stuck.
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('.ifile-dialog button[data-uploading]').forEach(function (submit) {
                submit.disabled = false;
                submit.removeAttribute('data-uploading');
                submit.textContent = 'Upload';
            });
        });
    });
</script>
@endpush
