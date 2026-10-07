{{-- Self-contained (no Vite, no database) so it renders even when the app itself is broken. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · FilmFolio</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: radial-gradient(ellipse 70% 520px at 50% 0%, rgba(88, 28, 135, 0.35), transparent 75%), #0e0a14;
            color: #c9bfdc;
            font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif;
            text-align: center;
        }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; margin-bottom: 36px; }
        .mark { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-weight: 800; background: linear-gradient(135deg, #d946ef, #8b5cf6); }
        .name { font-family: 'Playfair Display', Georgia, serif; font-weight: 700; font-size: 24px; }
        .code { font-family: 'Playfair Display', Georgia, serif; font-size: 96px; line-height: 1; color: #fff; text-shadow: 0 0 40px rgba(168, 85, 247, 0.6); }
        h1 { font-family: 'Playfair Display', Georgia, serif; font-weight: 400; font-size: 28px; color: #fff; margin: 14px 0 10px; }
        p { max-width: 440px; margin: 0 auto; line-height: 1.6; font-size: 15px; }
        .actions { margin-top: 30px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 24px; border-radius: 999px; font-weight: 600; font-size: 14px; text-decoration: none; cursor: pointer; border: 0; }
        .btn-primary { background: linear-gradient(135deg, #a855f7, #9333ea); color: #fff; box-shadow: 0 6px 22px rgba(168, 85, 247, 0.35); }
        .btn-ghost { background: transparent; color: #e9d5ff; border: 1px solid rgba(168, 85, 247, 0.4); }
    </style>
</head>
<body>
    <main>
        <a href="{{ url('/') }}" class="brand"><span class="mark">F</span><span class="name">FilmFolio</span></a>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            @hasSection('action')
                @yield('action')
            @else
                <a href="{{ url('/') }}" class="btn btn-primary">Back to home</a>
            @endif
            <a href="javascript:history.back()" class="btn btn-ghost">Go back</a>
        </div>
    </main>
</body>
</html>
