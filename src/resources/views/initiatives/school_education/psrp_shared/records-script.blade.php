<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.getElementById('psrpRecordsBody');
        let running = null;

        // Refreshes only the table, so the map and the figures above it stay as they are.
        async function loadRecords(url) {
            if (running) running.abort();

            const request = running = new AbortController();
            const focusedId = document.activeElement && body.contains(document.activeElement)
                ? document.activeElement.id
                : '';

            body.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(url, {
                    signal: request.signal,
                    headers: { 'X-Psrp-Records': '1', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) throw new Error('Records failed to load');

                body.innerHTML = await response.text();
                history.replaceState(null, '', url);
                body.removeAttribute('aria-busy');

                if (focusedId && document.getElementById(focusedId)) {
                    document.getElementById(focusedId).focus();
                }
            } catch (error) {
                // A normal page load shows the login page if the session has ended.
                if (error.name !== 'AbortError') location.assign(url);
            }
        }

        function recordsFormUrl() {
            const form = document.getElementById('psrpRecordsForm');
            const query = new URLSearchParams();

            // Keep links short by leaving out empty filters.
            for (const [name, value] of new FormData(form)) {
                if (value !== '') query.set(name, value);
            }

            const text = query.toString();

            return form.getAttribute('action').split('#')[0] + (text ? '?' + text : '') + '#psrp-records';
        }

        // The district and date filters at the top of the page.
        document.addEventListener('psrp:filters', function (event) {
            const url = new URL(location.href);

            url.hash = '';
            url.searchParams.delete('page');

            Object.entries(event.detail).forEach(function ([name, value]) {
                if (value) url.searchParams.set(name, value);
                else url.searchParams.delete(name);
            });

            loadRecords(url.href);
        });

        body.addEventListener('change', function (event) {
            if (event.target.matches('[form="psrpRecordsForm"]')) loadRecords(recordsFormUrl());
        });

        body.addEventListener('submit', function (event) {
            event.preventDefault();
            loadRecords(recordsFormUrl());
        });

        // Visit details
        const dialog = document.getElementById('psrpVisitDialog');
        const fields = document.getElementById('psrpVisitFields');

        function openVisit(visit) {
            document.getElementById('psrpVisitTitle').textContent = visit.title;
            document.getElementById('psrpVisitSubtitle').textContent = visit.subtitle;
            fields.replaceChildren();

            visit.fields.forEach(function (field) {
                const item = document.createElement('div');
                const label = document.createElement('dt');
                const value = document.createElement('dd');

                label.textContent = field.label;
                value.textContent = field.value;
                item.append(label, value);
                fields.append(item);
            });

            dialog.showModal();
            dialog.scrollTop = 0;
        }

        document.getElementById('psrpVisitClose').addEventListener('click', () => dialog.close());

        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) dialog.close();
        });

        // Copy CSV
        async function writeToClipboard(text) {
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(text);
                return;
            }

            // The clipboard API needs HTTPS or localhost.
            const area = document.createElement('textarea');
            area.value = text;
            document.body.append(area);
            area.select();
            const copied = document.execCommand('copy');
            area.remove();

            if (!copied) throw new Error('Copy failed');
        }

        async function copyCsv(copyButton) {
            copyButton.disabled = true;
            copyButton.textContent = 'Copying…';

            try {
                const response = await fetch(copyButton.dataset.url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) throw new Error('Export failed');

                await writeToClipboard(await response.text());
                copyButton.textContent = 'Copied';
            } catch (error) {
                copyButton.textContent = 'Copy failed';
            }

            setTimeout(function () {
                copyButton.textContent = 'Copy CSV';
                copyButton.disabled = false;
            }, 2000);
        }

        // The table's contents are replaced on every refresh, so listen on what stays.
        body.addEventListener('click', function (event) {
            const link = event.target.closest('a[href]');

            if (link) {
                // Sort, tab and page links. The CSV download is a different address and goes ahead.
                if (link.pathname === location.pathname && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
                    event.preventDefault();
                    loadRecords(link.href);
                }

                return;
            }

            const copyButton = event.target.closest('#psrpCopyCsv');

            if (copyButton) {
                copyCsv(copyButton);
                return;
            }

            const row = event.target.closest('tr[data-visit]');

            // Selecting text in a row should not open it.
            if (row && String(window.getSelection()) === '') {
                openVisit(JSON.parse(row.dataset.visit));
            }
        });
    });
</script>
