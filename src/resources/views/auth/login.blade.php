<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SMU Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #0B6046;
            --dark-green: #0B4534;
            --light-green: #EAF5EE;
            --gold: #CAA24A;
            --border: #D9E7DE;
            --text: #173F32;
            --muted: #687D73;
            font-family: 'Poppins', sans-serif;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; width: 100%; min-height: 100%; font-family: 'Poppins', sans-serif; color: var(--text); }
        body { background: var(--dark-green); }

        /* Full-viewport collage. The card sits on top, not beside it. */
        .login-layout {
            position: relative;
            isolation: isolate;
            display: grid;
            place-items: center;
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 24px;
            overflow: hidden;
        }
        .photo-panel {
            position: absolute;
            inset: 0;
            z-index: -2;
            overflow: hidden;
            background: var(--dark-green);
        }
        .collage-image {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        .login-layout::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background: rgba(5, 36, 30, .52);
            pointer-events: none;
        }

        /* Centered card */
        .login-panel {
            position: relative;
            width: min(100%, 560px);
            min-width: 0;
            margin: auto;
            background: #fff;
            border: 1px solid rgba(217,231,222,.95);
            border-radius: 22px;
            box-shadow: 0 22px 70px rgba(0,31,24,.28);
            padding: clamp(24px, 3.2vh, 36px) clamp(24px, 3.1vw, 43px);
        }
        .form-container { width: 100%; min-width: 0; }

        /* Logo starts at exactly the same left edge as the heading/form. */
        .branding { display: block; width: 100%; margin: 0 0 14px; padding: 0; text-align: left; }
        .logo {
            display: block;
            width: min(100%, 345px);
            max-width: 100%;
            height: auto;
            margin: 0;
            padding: 0;
            object-fit: contain;
            object-position: left center;
        }
        h1 { margin: 0 0 6px; font-size: clamp(25px, 2.2vw, 33px); font-weight: 700; letter-spacing: -.8px; color: var(--dark-green); line-height: 1.25; }
        .description { font-size: 12px; color: var(--muted); line-height: 1.55; margin: 0 0 13px; }
        .field-label { display: block; font-size: 12px; font-weight: 600; margin: 12px 0 6px; }
        .input-field {
            display: block;
            width: 100%;
            height: 46px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 0 14px;
            outline: none;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s;
        }
        .input-field:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(11,96,70,.13); }
        .password-wrapper { position: relative; }
        .password-wrapper .input-field { padding-right: 80px; }
        .show-password {
            position: absolute; right: 8px; top: 6px; height: 34px;
            border: 0; border-radius: 7px; padding: 0 12px;
            background: var(--light-green); color: var(--primary-green);
            cursor: pointer; font: inherit; font-size: 12px;
        }
        .captcha-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .math-question {
            flex: 1; min-width: 0; height: 46px; border: 1px solid #D5E4D8;
            background: var(--light-green); border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 700; color: var(--primary-green); letter-spacing: 1px;
        }
        .refresh-captcha {
            display: flex; align-items: center; justify-content: center;
            width: 46px; height: 46px; flex-shrink: 0;
            border: 1px solid var(--border); border-radius: 10px;
            color: var(--primary-green); text-decoration: none; font-size: 25px;
        }
        .refresh-captcha:hover { background: var(--light-green); }
        .signin-btn {
            width: 100%; height: 48px; background: var(--primary-green);
            border: none; border-radius: 28px; color: #fff; font: inherit;
            font-size: 14px; font-weight: 700; cursor: pointer;
            transition: background .2s;
            margin-top: 15px;
        }
        .signin-btn:hover { background: var(--dark-green); }
        .error { color: #be2638; font-size: 12px; margin-top: 6px; }
        .error-box { background: #fff1f1; border: 1px solid #f6c5c5; color: #ab2634; padding: 12px; border-radius: 9px; font-size: 12px; margin-bottom: 14px; }

        /* Keep the background full-screen. Scroll only on genuinely short screens
           so the login controls cannot become inaccessible. */
        @media (max-height: 730px) {
            .login-layout { overflow: visible; padding-block: 18px; }
            .photo-panel { position: fixed; }
            .login-panel { padding-top: 22px; padding-bottom: 22px; }
            .branding { margin-bottom: 9px; }
            .logo { width: min(100%, 290px); }
            .description { margin-bottom: 9px; }
            .field-label { margin-top: 9px; }
        }
        @media (max-width: 700px) {
            /* iPhones zoom the whole page when a focused field's text is under 16px. */
            .input-field { font-size: 16px; }
            .login-layout { padding: 16px; }
            .login-panel { width: min(100%, 480px); border-radius: 17px; padding: 24px; }
            .logo { width: min(100%, 300px); }
        }
        @media (max-width: 380px) {
            .login-layout { padding: 10px; }
            .login-panel { padding: 20px 17px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="login-layout">
        <aside class="photo-panel" aria-label="Chief Minister Punjab photo collage">
            <img src="{{ asset('images/cm-collage.png') }}"
                 alt="Chief Minister Punjab initiatives and official visits collage"
                 class="collage-image">
        </aside>

        <section class="login-panel">
            <div class="form-container">
                <div class="branding">
                    <img src="{{ asset('images/logo.png') }}"
                         alt="Chief Minister's Special Monitoring Unit Logo"
                         class="logo">
                </div>

                <h1>SMU Monitoring Portal</h1>
                <p class="description">
                    Monitor government initiatives, track departmental performance, and review field observations to strengthen public service delivery across Punjab
                </p>

                @if ($errors->any())
                    <div class="error-box" role="alert">Please correct the errors below.</div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf
                    <label class="field-label" for="email">Email address</label>
                    <input class="input-field" type="email" id="email" name="email"
                           value="{{ old('email') }}" autocomplete="username" required>
                    @error('email')
                        <div class="error" role="alert">{{ $message }}</div>
                    @enderror

                    <label class="field-label" for="password">Password</label>
                    <div class="password-wrapper">
                        <input class="input-field" type="password" id="password" name="password"
                               autocomplete="current-password" required>
                        <button class="show-password" type="button" onclick="togglePassword()">Show</button>
                    </div>
                    @error('password')
                        <div class="error" role="alert">{{ $message }}</div>
                    @enderror

                    <label class="field-label" for="captcha">Security check</label>
                    <div class="captcha-row">
                        <div class="math-question">{{ $first }} + {{ $second }} = ?</div>
                        <a class="refresh-captcha" href="{{ route('login') }}"
                           title="Generate a new question" aria-label="Refresh CAPTCHA question">&#8635;</a>
                    </div>
                    <input class="input-field" type="number" id="captcha" name="captcha"
                           placeholder="Enter the answer" min="0" required>
                    @error('captcha')
                        <div class="error" role="alert">{{ $message }}</div>
                    @enderror
                    <button class="signin-btn" type="submit">Sign in</button>
                </form>
            </div>
        </section>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const button = document.querySelector('.show-password');
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }
    </script>
</body>
</html>
