<style>
    .psrp-page a:focus-visible,
    .psrp-page button:focus-visible,
    .psrp-page select:focus-visible,
    .psrp-page input:focus-visible,
    .psrp-page .psrp-table-wrapper:focus-visible {
        outline: 2px solid var(--gold);
        outline-offset: 2px;
    }

    .psrp-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 0;
    }

    .psrp-field label {
        color: var(--muted);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .psrp-field--search { width: 220px; }
    .psrp-field--check { width: 300px; }

    .psrp-control {
        width: 100%;
        height: 36px;
        padding: 0 30px 0 11px;
        color: var(--text);
        font-size: 12px;
        background-color: var(--white);
        border: 1px solid var(--border);
        border-radius: 8px;
    }

    select.psrp-control {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23687D73' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        text-overflow: ellipsis;
    }

    .psrp-control--text {
        padding-right: 11px;
    }

    .psrp-control--small {
        width: 92px;
    }

    .psrp-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        padding: 0 14px;
        color: var(--text);
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 8px;
        transition: background .2s, border-color .2s;
    }

    a.psrp-btn:hover,
    button.psrp-btn:hover {
        background: var(--primary-light);
        border-color: var(--primary);
    }

    .psrp-btn--primary {
        color: #fff;
        font-weight: 600;
        background: var(--primary);
        border-color: var(--primary);
    }

    a.psrp-btn--primary:hover,
    button.psrp-btn--primary:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    button.psrp-btn:disabled {
        cursor: wait;
        opacity: .7;
    }

    .psrp-btn.is-disabled {
        color: #84958C;
        background: var(--bg);
        cursor: default;
    }

    /* EXPLORE THE RECORDS */

    .psrp-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--border);
    }

    .psrp-tabs a {
        margin-bottom: -1px;
        padding: 8px 12px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 500;
        border-bottom: 2px solid transparent;
    }

    .psrp-tabs a:hover {
        color: var(--text);
    }

    .psrp-tabs a.is-active {
        color: var(--primary);
        font-weight: 600;
        border-bottom-color: var(--primary);
    }

    .psrp-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
        margin-bottom: 12px;
    }

    .psrp-toolbar-actions {
        display: flex;
        gap: 8px;
        margin-left: auto;
    }

    .psrp-table-wrapper {
        width: 100%;
        overflow-x: auto;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 10px;
    }

    .psrp-table {
        width: 100%;
        font-size: 11px;
        text-align: left;
        border-collapse: collapse;
    }

    .psrp-table th {
        padding: 0;
        font-weight: 600;
        white-space: nowrap;
        background: var(--primary-light);
    }

    .psrp-table th a {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 11px 10px;
        color: var(--text);
    }

    .psrp-table th.is-numeric a {
        flex-direction: row-reverse;
    }

    .psrp-table th a:hover {
        color: var(--primary);
    }

    .psrp-sort-mark {
        color: var(--muted);
        font-size: 9px;
    }

    .psrp-sort-mark.is-sorted {
        color: var(--primary);
    }

    .psrp-table td {
        padding: 9px 10px;
        white-space: nowrap;
        border-top: 1px solid var(--border);
    }

    .psrp-table .is-numeric {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .psrp-table .is-code {
        font-family: Consolas, Menlo, monospace;
        font-size: 11px;
    }

    .psrp-table td.is-bad {
        color: var(--psrp-red);
        font-weight: 600;
    }

    .psrp-visit-row {
        cursor: pointer;
    }

    .psrp-visit-row:hover {
        background: var(--bg);
    }

    .psrp-visit-open {
        padding: 0;
        color: var(--primary);
        font-weight: 600;
        background: none;
        border: none;
    }

    .psrp-visit-open:hover {
        text-decoration: underline;
    }

    .psrp-table td.psrp-table-empty {
        padding: 34px;
        color: var(--muted);
        font-size: 12px;
        text-align: center;
        white-space: normal;
    }

    .psrp-table-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 12px;
    }

    .psrp-table-footer p {
        margin: 0;
        color: var(--muted);
        font-size: 11px;
    }

    .psrp-pagination {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    .psrp-page-count {
        font-size: 12px;
        white-space: nowrap;
    }

    /* UPLOAD RESULT */

    .psrp-import-result {
        margin-bottom: 18px;
        padding: 16px;
        scroll-margin-top: var(--psrp-anchor-offset);
        background: #EEF8F1;
        border: 1px solid #A9D8B8;
        border-radius: 12px;
    }

    .psrp-import-result.is-failed {
        background: #FDF0F0;
        border-color: #EDB9B9;
    }

    .psrp-import-result h4 {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        margin: 0 0 10px;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }

    .psrp-import-result h4 svg {
        width: 17px;
        height: 17px;
    }

    .psrp-import-result p {
        margin: 0 0 8px;
        font-size: 12px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .psrp-import-result p:last-child {
        margin-bottom: 0;
    }

    .psrp-import-counts {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin: 0 0 12px;
    }

    .psrp-import-counts div {
        padding: 9px 12px;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 9px;
    }

    .psrp-import-counts dt {
        color: var(--muted);
        font-size: 10px;
    }

    .psrp-import-counts dd {
        margin: 0;
        font-size: 19px;
        font-weight: 700;
        line-height: 1.3;
    }

    .psrp-import-table-wrapper {
        max-height: 340px;
        overflow: auto;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 9px;
    }

    .psrp-import-table {
        width: 100%;
        min-width: 520px;
        font-size: 11px;
        text-align: left;
        border-collapse: collapse;
    }

    .psrp-import-table th {
        position: sticky;
        top: 0;
        padding: 9px 10px;
        font-weight: 600;
        white-space: nowrap;
        background: var(--primary-light);
    }

    .psrp-import-table td {
        padding: 8px 10px;
        vertical-align: top;
        border-top: 1px solid var(--border);
        overflow-wrap: anywhere;
    }

    /* VISIT DETAILS */

    .psrp-dialog {
        width: calc(100% - 32px);
        max-width: 720px;
        max-height: calc(100vh - 48px);
        max-height: calc(100dvh - 32px);
        padding: 0;
        color: var(--text);
        border: none;
        border-radius: 14px;
    }

    .psrp-dialog::backdrop {
        background: rgba(11, 69, 52, .45);
    }

    .psrp-dialog-header {
        position: sticky;
        top: 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        background: var(--white);
        border-bottom: 1px solid var(--border);
    }

    .psrp-dialog-header h4 {
        margin: 0 0 3px;
        font-size: 15px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .psrp-dialog-header p {
        margin: 0;
        color: var(--muted);
        font-size: 11px;
    }

    .psrp-dialog-close {
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

    .psrp-dialog-close svg {
        width: 16px;
        height: 16px;
    }

    .psrp-dialog-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 20px;
        margin: 0;
        padding: 18px 20px 22px;
    }

    .psrp-dialog-fields dt {
        margin-bottom: 2px;
        color: var(--muted);
        font-size: 10px;
    }

    .psrp-dialog-fields dd {
        margin: 0;
        font-size: 12px;
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    /* A section's room is what matters here, as on the rest of the page. */

    .psrp-records {
        padding-top: 42px;
        padding-bottom: 36px;
        scroll-margin-top: var(--psrp-anchor-offset);
    }

    #psrpRecordsBody[aria-busy="true"] {
        opacity: .55;
        transition: opacity .15s;
    }

    /* RESPONSIVE */

    @media (max-width: 768px) {
        .psrp-control--small {
            width: 120px;
        }

        .psrp-field--search {
            width: 290px;
        }
    }

    @media (max-width: 650px) {
        .psrp-toolbar .psrp-field {
            width: 100%;
        }

        .psrp-toolbar-actions {
            flex-wrap: wrap;
            width: 100%;
            margin-left: 0;
        }

        .psrp-toolbar-actions .psrp-btn {
            flex: 1;
        }

        .psrp-import-counts {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .psrp-dialog-fields {
            grid-template-columns: 1fr;
        }
    }
</style>
