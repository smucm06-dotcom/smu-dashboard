{{--
    The progress bar for a call center page's background uploads (the upload windows marked
    data-background-upload, see x-initiative-files). The file is sent with a bar showing how much has gone, then
    the page asks the server every second how far the reading has got (CallCenterUploadController::show) and
    shows the rows done and left. When it ends the page reloads to show the result. An upload still running
    when the page is opened (someone left and came back) is followed the same way.
--}}
<div
    class="ccu-panel"
    id="ccUploadPanel"
    role="status"
    aria-live="polite"
    @if ($uploads['running'] === null) hidden @endif
    @if ($uploads['running'] !== null) data-upload="{{ json_encode($uploads['running']) }}" @endif
>
    <div class="ccu-head">
        <span class="ccu-spinner" aria-hidden="true"></span>
        <div class="ccu-text">
            <b id="ccUploadTitle">Uploading</b>
            <span id="ccUploadStep">Starting…</span>
        </div>
    </div>
    <div class="ccu-bar" id="ccUploadBar" role="progressbar" aria-labelledby="ccUploadTitle" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
        <i id="ccUploadFill"></i>
    </div>
    <p class="ccu-note" id="ccUploadNote">You can leave this page while the file is read. Come back to see the result.</p>
</div>

@push('styles')
<style>
    .ccu-panel { margin: 0 0 12px; padding: 12px 14px; background: #F4F9F5; border: 1px solid #A9D8B8; border-radius: 10px; font-size: 12px; }
    .ccu-panel[hidden] { display: none; }
    .ccu-head { display: flex; align-items: center; gap: 10px; }
    .ccu-text { display: grid; gap: 1px; min-width: 0; }
    .ccu-text b { font-size: 13px; overflow-wrap: anywhere; }
    .ccu-text span { color: var(--text); overflow-wrap: anywhere; }
    .ccu-spinner { flex: none; width: 18px; height: 18px; border-radius: 50%; border: 3px solid #CFE7D7; border-top-color: #0B6046; animation: ccu-spin .9s linear infinite; }
    .ccu-panel.is-failed { background: #FDF0F0; border-color: #EDB9B9; }
    .ccu-panel.is-failed .ccu-spinner { animation: none; border-color: #A61B1B; }
    .ccu-bar { height: 10px; margin-top: 10px; background: #E2EEE6; border-radius: 99px; overflow: hidden; }
    .ccu-bar i { display: block; height: 100%; width: 0; background: linear-gradient(90deg, #0b6b45, #2fb57f); border-radius: 99px; transition: width .4s ease; }
    .ccu-bar.is-unknown i { width: 30%; animation: ccu-slide 1.4s ease-in-out infinite; }
    .ccu-note { margin: 8px 0 0; color: var(--muted); font-size: 11px; line-height: 1.5; }
    .ccu-panel.is-failed .ccu-note { color: #A61B1B; }
    @keyframes ccu-spin { to { transform: rotate(360deg); } }
    @keyframes ccu-slide { 0% { margin-left: -30%; } 100% { margin-left: 100%; } }
    @media (prefers-reduced-motion: reduce) {
        .ccu-spinner, .ccu-bar.is-unknown i { animation: none; }
        .ccu-bar i { transition: none; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.getElementById('ccUploadPanel');

        if (! panel) return;

        const title = document.getElementById('ccUploadTitle');
        const step = document.getElementById('ccUploadStep');
        const bar = document.getElementById('ccUploadBar');
        const fill = document.getElementById('ccUploadFill');
        const note = document.getElementById('ccUploadNote');
        const number = (value) => Number(value).toLocaleString('en-US');
        let samples = [];

        function show(name) {
            panel.hidden = false;
            panel.classList.remove('is-failed');
            title.textContent = 'Uploading ' + name;
            note.textContent = 'You can leave this page while the file is read. Come back to see the result.';
        }

        function setBar(percent) {
            bar.classList.toggle('is-unknown', percent === null);
            fill.style.width = percent === null ? '' : Math.max(1, Math.min(100, percent)) + '%';
            bar.setAttribute('aria-valuenow', percent === null ? 0 : Math.round(percent));
        }

        function failed(message) {
            panel.hidden = false;
            panel.classList.add('is-failed');
            setBar(0);
            step.textContent = 'The file was not uploaded.';
            note.textContent = message;
        }

        // About how long is left, from how fast the last minute's rows went.
        function timeLeft(done, total) {
            const now = Date.now();
            samples.push([now, done]);
            samples = samples.filter((sample) => now - sample[0] < 60000);

            if (samples.length < 3 || ! total || done >= total) return '';

            const [startedAt, startDone] = samples[0];
            const rate = (done - startDone) / ((now - startedAt) / 1000);

            if (rate <= 0) return '';

            const seconds = (total - done) / rate;

            return seconds < 60 ? ' · less than a minute left' : ' · about ' + Math.ceil(seconds / 60) + ' min left';
        }

        function render(upload) {
            const total = upload.rowsTotal;
            const done = Math.min(upload.rowsDone, total || upload.rowsDone);
            const percent = total ? done / total * 100 : null;

            title.textContent = 'Uploading ' + upload.fileName;

            if (upload.status === 'queued') {
                setBar(null);
                step.textContent = upload.workerLate
                    ? 'Waiting for the upload worker. It does not seem to be running: ask IT to start it (docker compose up -d). The file is kept and will be read once it starts.'
                    : 'Waiting to start…';
                return;
            }

            if (upload.phase === 'saving') {
                setBar(percent);
                step.textContent = total
                    ? 'Checking and saving rows: ' + number(done) + ' of ' + number(total) + ' (' + Math.floor(percent) + '%)' + timeLeft(done, total)
                    : 'Checking and saving rows: ' + number(done) + ' so far';
            } else if (upload.phase === 'linking') {
                setBar(percent);
                step.textContent = 'Linking every call to the department list: ' + number(done) + ' of ' + number(total || 0) + (percent === null ? '' : ' (' + Math.floor(percent) + '%)');
            } else {
                setBar(null);
                step.textContent = 'Opening the file…';
            }
        }

        // Shows the result: the page reloads at the upload's result box.
        function finished() {
            history.replaceState(null, '', location.pathname + location.search + '#psrp-import-result');
            location.reload();
        }

        async function follow(url) {
            samples = [];

            while (true) {
                let upload;

                try {
                    const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });

                    if (response.status === 401 || response.status === 419) {
                        failed('Your session has ended. Sign in again to see the result.');
                        return;
                    }

                    upload = (await response.json()).upload;
                } catch (error) {
                    // A dropped connection: try again shortly.
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    continue;
                }

                if (upload.status === 'done' || upload.status === 'failed') {
                    finished();
                    return;
                }

                render(upload);
                await new Promise((resolve) => setTimeout(resolve, 1000));
            }
        }

        // Sends the file with a bar for how much of it has gone, then follows the reading.
        function send(form) {
            const input = form.querySelector('input[type="file"]');
            const file = input && input.files[0];

            if (! file) return;

            form.closest('dialog')?.close();
            show(file.name);
            setBar(0);
            step.textContent = 'Sending the file: 0%';
            // After the window has closed and given the focus back to its button, which also scrolls.
            requestAnimationFrame(() => panel.scrollIntoView({ block: 'nearest' }));

            const request = new XMLHttpRequest();
            request.open('POST', form.action);
            request.setRequestHeader('Accept', 'application/json');
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            request.upload.addEventListener('progress', function (event) {
                if (! event.lengthComputable) return;

                const percent = event.loaded / event.total * 100;
                setBar(percent);
                step.textContent = 'Sending the file: ' + Math.floor(percent) + '% (' + (event.loaded / 1048576).toFixed(1) + ' of ' + (event.total / 1048576).toFixed(1) + ' MB)';
            });

            request.addEventListener('load', function () {
                let body = {};

                try { body = JSON.parse(request.responseText); } catch (error) { /* not JSON */ }

                if (request.status === 202 && body.upload) {
                    render(body.upload);
                    follow(body.upload.url);
                } else if (request.status === 413) {
                    failed('The file is too big to upload.');
                } else if (request.status === 419) {
                    failed('The page has been open too long. Reload it and upload again.');
                } else {
                    failed(body.message || 'The file could not be uploaded. Please try again.');
                }

                const submit = form.querySelector('button[type="submit"]');
                if (submit) {
                    submit.disabled = false;
                    submit.removeAttribute('data-uploading');
                    submit.textContent = 'Upload';
                }

                form.reset();
            });

            request.addEventListener('error', function () {
                failed('The connection was lost while sending the file. Please try again.');
            });

            request.send(new FormData(form));
        }

        // The upload window's own script asks before replacing a file; a "no" stops the upload here too.
        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-background-upload]');

            if (! form || event.defaultPrevented) return;

            event.preventDefault();
            send(form);
        });

        if (panel.dataset.upload) {
            const upload = JSON.parse(panel.dataset.upload);
            render(upload);
            follow(upload.url);
        }
    });
</script>
@endpush
