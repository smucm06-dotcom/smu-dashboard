{{-- Shared by the Users pages and the Settings page. Layouts follow the space this page
     really has, so they stay right with the sidebar open, collapsed or hidden. --}}
<style>
    .um-page {
        container: um-page / inline-size;
        width: 100%;
    }

    .um-narrow {
        max-width: 560px;
    }

    .um-sr {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip-path: inset(50%);
        white-space: nowrap;
    }

    .um-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .um-head > div {
        min-width: 0;
    }

    .um-head h3 {
        margin: 0 0 4px;
        color: var(--primary-dark);
        font-size: 21px;
        font-weight: 700;
        line-height: 1.4;
    }

    .um-head p {
        margin: 0;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.6;
    }

    .um-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 36px;
        margin-bottom: 8px;
        color: var(--primary);
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
    }

    .um-back svg {
        width: 16px;
        height: 16px;
    }

    .um-back:hover {
        color: var(--primary-dark);
    }

    /* Buttons */
    .um-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 0 16px;
        color: var(--text);
        font: inherit;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 9px;
        transition: background .2s, border-color .2s, color .2s;
    }

    .um-btn svg {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
    }

    .um-btn:hover {
        background: var(--primary-light);
        border-color: var(--primary);
    }

    .um-btn--primary {
        color: #fff;
        background: var(--primary);
        border-color: var(--primary);
    }

    .um-btn--primary:hover {
        color: #fff;
        background: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .um-btn--small {
        min-height: 34px;
        padding: 0 11px;
        font-weight: 500;
    }

    .um-btn--danger {
        color: #A61B1B;
    }

    .um-btn--danger:hover {
        background: #FDF0F0;
        border-color: #EDB9B9;
    }

    .um-page a:focus-visible,
    .um-page button:focus-visible,
    .um-page input:focus-visible,
    .um-page select:focus-visible {
        outline: 2px solid var(--gold);
        outline-offset: 2px;
    }

    /* Messages */
    .um-message {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin: 0 0 16px;
        padding: 11px 13px;
        font-size: 12px;
        line-height: 1.5;
        border: 1px solid;
        border-radius: 9px;
        overflow-wrap: anywhere;
    }

    .um-message svg {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
        margin-top: 1px;
    }

    .um-message--ok {
        background: #EEF8F1;
        border-color: #A9D8B8;
    }

    .um-message--ok svg {
        color: #146C2E;
    }

    .um-message--error {
        color: #A61B1B;
        background: #FDF0F0;
        border-color: #EDB9B9;
    }

    .um-message--notice {
        color: #6B4E0E;
        background: #FBF5E6;
        border-color: #E7D4A3;
    }

    /* Cards and fields */
    .um-card {
        margin-bottom: 16px;
        padding: 20px;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 14px;
    }

    .um-card > h3 {
        margin: 0 0 4px;
        color: var(--primary-dark);
        font-size: 15px;
        font-weight: 700;
    }

    .um-card > p {
        margin: 0 0 16px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.6;
    }

    .um-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 16px;
    }

    .um-fields--single {
        grid-template-columns: minmax(0, 1fr);
    }

    .um-field {
        min-width: 0;
    }

    .um-field label {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        font-weight: 600;
    }

    .um-control {
        display: block;
        width: 100%;
        min-width: 0;
        height: 42px;
        padding: 0 12px;
        color: var(--text);
        font: inherit;
        font-size: 12px;
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 9px;
        transition: border-color .2s, box-shadow .2s;
    }

    .um-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 96, 70, .08);
    }

    .um-control:disabled {
        color: var(--muted);
        background: var(--bg);
        cursor: not-allowed;
    }

    .um-control.is-invalid {
        border-color: #C24141;
    }

    .um-hint,
    .um-error {
        display: block;
        margin: 5px 0 0;
        font-size: 11px;
        line-height: 1.5;
    }

    .um-hint {
        color: var(--muted);
    }

    .um-error {
        color: #A61B1B;
    }

    .um-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .um-card .um-form-actions {
        margin-top: 18px;
    }

    /* Filters */
    .um-filters {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1.4fr) minmax(0, 1fr) auto;
        gap: 10px;
        align-items: end;
        margin-bottom: 16px;
    }

    .um-filters label {
        display: block;
        margin-bottom: 4px;
        color: var(--muted);
        font-size: 11px;
        font-weight: 600;
    }

    .um-filter-actions {
        display: flex;
        gap: 8px;
    }

    /* Users table */
    .um-table-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
    }

    /* Fixed widths, so one long email address cannot squeeze the other columns. */
    .um-table {
        width: 100%;
        font-size: 12px;
        text-align: left;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .um-table th:nth-child(1) { width: 32%; }
    .um-table th:nth-child(2) { width: 18%; }
    .um-table th:nth-child(3) { width: 24%; }
    .um-table th:nth-child(4) { width: 12%; }
    .um-table th:nth-child(5) { width: 14%; }

    .um-table th {
        padding: 11px 16px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        background: var(--primary-light);
    }

    .um-table td {
        padding: 13px 16px;
        vertical-align: middle;
        border-top: 1px solid var(--border);
        overflow-wrap: anywhere;
    }

    .um-user {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
    }

    .um-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        background: #28754F;
        border-radius: 50%;
    }

    .um-user-text {
        min-width: 0;
    }

    .um-user-text strong,
    .um-user-text span {
        display: block;
    }

    .um-user-text strong {
        font-size: 13px;
        font-weight: 600;
    }

    .um-user-text span {
        margin-top: 1px;
        color: var(--muted);
        font-size: 11px;
    }

    .um-you {
        color: var(--muted);
        font-size: 11px;
        font-style: normal;
        font-weight: 500;
    }

    .um-badge {
        display: inline-block;
        padding: 3px 9px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.5;
        color: var(--primary);
        background: var(--primary-light);
        border-radius: 999px;
    }

    .um-badge--admin {
        color: #6B4E0E;
        background: #FBF5E6;
    }

    .um-badge--muted {
        font-weight: 500;
        color: var(--muted);
        background: var(--bg);
        border: 1px solid var(--border);
    }

    .um-access {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .um-muted {
        color: var(--muted);
    }

    .um-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
        white-space: nowrap;
    }

    .um-status-dot {
        width: 8px;
        height: 8px;
        flex-shrink: 0;
        background: #35A66B;
        border-radius: 50%;
    }

    .um-status-dot.is-off {
        background: #8A9690;
    }

    .um-status-note {
        display: block;
        margin-top: 2px;
        color: var(--muted);
        font-size: 10px;
    }

    .um-row-actions {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 6px;
    }

    .um-row-actions form {
        margin: 0;
    }

    .um-empty {
        padding: 40px 20px;
        color: var(--muted);
        font-size: 12px;
        text-align: center;
    }

    .um-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 14px;
        color: var(--muted);
        font-size: 11px;
    }

    .um-pagination p {
        margin: 0;
    }

    .um-pagination-links {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .um-btn.is-disabled {
        color: var(--muted);
        cursor: not-allowed;
        background: var(--bg);
        border-color: var(--border);
    }

    /* Choosing initiatives */
    .um-picker {
        margin-top: 16px;
    }

    .um-picker-note {
        margin: 0;
        padding: 12px 14px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.6;
        background: var(--bg);
        border: 1px dashed var(--border);
        border-radius: 9px;
    }

    .um-message[hidden],
    .um-picker-note[hidden],
    .um-picker-panel[hidden],
    .um-picker-tools[hidden],
    .um-group[hidden],
    .um-option[hidden],
    .um-chips[hidden] {
        display: none;
    }

    .um-picker-panel {
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: 10px;
    }

    .um-picker-tools {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 14px;
        padding: 10px 12px;
        background: var(--bg);
        border-bottom: 1px solid var(--border);
    }

    .um-picker-search {
        flex: 1 1 220px;
        min-width: 0;
        height: 38px;
    }

    .um-select-all {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 38px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .um-select-all input,
    .um-option input {
        flex-shrink: 0;
        width: 17px;
        height: 17px;
        margin: 0;
        accent-color: var(--primary);
        cursor: pointer;
    }

    .um-picker-count {
        margin-left: auto;
        color: var(--muted);
        font-size: 11px;
        white-space: nowrap;
    }

    .um-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        max-height: 104px;
        padding: 10px 12px;
        overflow-y: auto;
        border-bottom: 1px solid var(--border);
    }

    .um-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-height: 28px;
        max-width: 100%;
        padding: 2px 4px 2px 10px;
        color: var(--primary);
        font: inherit;
        font-size: 11px;
        font-weight: 600;
        text-align: left;
        cursor: pointer;
        background: var(--primary-light);
        border: 1px solid transparent;
        border-radius: 999px;
    }

    .um-chip:hover {
        border-color: var(--primary);
    }

    .um-chip-x {
        flex-shrink: 0;
        width: 18px;
        font-size: 14px;
        line-height: 1;
        text-align: center;
    }

    .um-group {
        min-width: 0;
        max-height: clamp(240px, 46vh, 420px);
        margin: 0;
        padding: 0;
        overflow-y: auto;
        border: 0;
    }

    .um-group > legend {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip-path: inset(50%);
        white-space: nowrap;
    }

    .um-option {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        min-height: 46px;
        padding: 10px 14px;
        cursor: pointer;
        border-top: 1px solid var(--border);
        transition: background .15s;
    }

    .um-group .um-option:first-of-type {
        border-top: 0;
    }

    .um-option:hover {
        background: var(--bg);
    }

    .um-option:has(input:checked) {
        background: var(--primary-light);
    }

    .um-option input {
        margin-top: 1px;
    }

    .um-option-text {
        flex: 1;
        min-width: 0;
    }

    .um-option-text strong,
    .um-option-text small {
        display: block;
        overflow-wrap: anywhere;
    }

    .um-option-text strong {
        font-size: 12px;
        font-weight: 600;
    }

    .um-option-text small {
        margin-top: 1px;
        color: var(--muted);
        font-size: 11px;
        line-height: 1.45;
    }

    .um-picker-empty {
        margin: 0;
        padding: 20px 14px;
        color: var(--muted);
        font-size: 12px;
        text-align: center;
    }

    .um-picker-empty[hidden] {
        display: none;
    }

    /* Tablets, or a desktop with little room beside the sidebar */
    @container um-page (max-width: 900px) {
        .um-filters {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .um-filter-search {
            grid-column: 1 / -1;
        }
    }

    /* Each user becomes a card, so the page never scrolls sideways. */
    @container um-page (max-width: 720px) {
        .um-table thead {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip-path: inset(50%);
        }

        .um-table,
        .um-table tbody,
        .um-table tr,
        .um-table td {
            display: block;
            width: 100%;
        }

        .um-table tr {
            padding: 14px 16px;
            border-top: 1px solid var(--border);
        }

        .um-table tr:first-child {
            border-top: 0;
        }

        .um-table td {
            display: grid;
            grid-template-columns: 78px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
            padding: 5px 0;
            border-top: 0;
        }

        .um-table td::before {
            content: attr(data-label);
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            line-height: 1.8;
        }

        .um-table td.um-cell-user,
        .um-table td.um-cell-actions {
            grid-template-columns: minmax(0, 1fr);
        }

        .um-table td.um-cell-user::before,
        .um-table td.um-cell-actions::before {
            content: none;
        }

        .um-table td.um-cell-user {
            padding-bottom: 9px;
        }

        .um-table td.um-cell-actions {
            padding-top: 10px;
        }

        .um-row-actions {
            justify-content: stretch;
        }

        .um-row-actions > * {
            flex: 1 1 0;
        }

        .um-row-actions .um-btn {
            width: 100%;
            min-height: 40px;
        }
    }

    @container um-page (max-width: 560px) {
        .um-head {
            align-items: stretch;
            flex-direction: column;
        }

        .um-fields,
        .um-filters {
            grid-template-columns: minmax(0, 1fr);
        }

        .um-card {
            padding: 16px;
        }

        .um-filter-actions > .um-btn {
            flex: 1 1 0;
        }

        .um-form-actions {
            flex-direction: column-reverse;
        }

        .um-form-actions .um-btn {
            width: 100%;
        }

        .um-picker-count {
            margin-left: 0;
        }

        .um-pagination {
            justify-content: center;
            text-align: center;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .um-btn,
        .um-control,
        .um-option {
            transition: none;
        }
    }
</style>
