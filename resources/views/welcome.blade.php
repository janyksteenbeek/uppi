@php
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Number;

    $dashboardUrl = \App\Filament\Pages\Dashboard::getUrl();

    // ─── Real stats (cached 1h) — counts from start of yesterday for "today" feel ───
    $statsTtl = now()->addHour();
    $checksSinceYesterday = Cache::remember(
        'marketing.checks_since_yesterday',
        $statsTtl,
        fn () => \App\Models\Check::where('created_at', '>=', now()->subDay()->startOfDay())->count()
    );
    $totalChecks = Cache::remember(
        'marketing.total_checks',
        $statsTtl,
        fn () => \App\Models\Check::count()
    );
    $totalMonitors = Cache::remember(
        'marketing.total_monitors',
        $statsTtl,
        fn () => \App\Models\Monitor::count()
    );
    $totalAlerts = Cache::remember(
        'marketing.total_alerts',
        $statsTtl,
        fn () => \App\Models\Alert::count()
    );
    $abbr = fn (int $n) => Number::abbreviate($n, maxPrecision: 1);

    // ─── Hero response-time graph (precomputed so the curve is stable) ───
    $graphW = 1336; $graphH = 200; $graphN = 120;
    $baseline = $graphH * 0.55;
    $graphPts = [];
    for ($i = 0; $i < $graphN; $i++) {
        $x = $i / ($graphN - 1) * $graphW;
        $seed = sin($i * 0.3) * 14 + sin($i * 0.7) * 6 + cos($i * 0.15) * 9;
        $spike = match ($i) { 78 => -42, 79 => -30, default => 0 };
        $y = $baseline - 16 + $seed + $spike;
        $graphPts[] = [$x, $y];
    }
    // exception markers along bottom rail (linked to the response curve)
    $excMarkers = [
        ['idx' =>   8, 'count' =>  4, 'sev' => 'warn'],
        ['idx' =>  22, 'count' =>  9, 'sev' => 'err'],
        ['idx' =>  35, 'count' =>  2, 'sev' => 'err'],
        ['idx' =>  52, 'count' => 14, 'sev' => 'err'],
        ['idx' =>  64, 'count' =>  3, 'sev' => 'warn'],
        ['idx' =>  78, 'count' => 38, 'sev' => 'err'],
        ['idx' =>  92, 'count' =>  6, 'sev' => 'err'],
        ['idx' => 104, 'count' =>  2, 'sev' => 'warn'],
        ['idx' => 113, 'count' => 11, 'sev' => 'err'],
    ];
    $excRailY = $graphH - 14;
    $graphPath = '';
    foreach ($graphPts as $idx => $p) {
        $graphPath .= ($idx === 0 ? 'M' : 'L') . number_format($p[0], 1, '.', '') . ',' . number_format($p[1], 1, '.', '') . ' ';
    }
    $graphPath = trim($graphPath);
    $graphFill = $graphPath . " L{$graphW},{$graphH} L0,{$graphH} Z";
    $spikePt = $graphPts[78];
    $cursorPt = $graphPts[40];

    // ─── Sparklines for the server card ───
    $sparkline = function (string $trend) {
        $points = [];
        for ($i = 0; $i < 30; $i++) {
            $noise = sin($i * 0.6) * 4 + cos($i * 1.2) * 2;
            $drift = $trend === 'up' ? -$i * 0.3 : $i * 0.2;
            $points[] = [$i * 4, 20 + $noise + $drift];
        }
        $path = '';
        foreach ($points as $idx => $p) {
            $path .= ($idx === 0 ? 'M' : 'L') . number_format($p[0], 1, '.', '') . ',' . number_format($p[1], 1, '.', '') . ' ';
        }
        return trim($path);
    };

    $tickerItems = [
        ['n' => 'api.acme.io',          't' => '142ms',     's' => 'OK'],
        ['n' => 'checkout.shop.dev',    't' => '89ms',      's' => 'OK'],
        ['n' => 'docs.platform.io',     't' => '210ms',     's' => 'OK'],
        ['n' => 'auth.uppi.dev',        't' => '76ms',      's' => 'OK'],
        ['n' => 'cdn.assets.co',        't' => 'timeout',   's' => 'FAIL'],
        ['n' => 'webhook.relay.io',     't' => '54ms',      's' => 'OK'],
        ['n' => 'db-east-1.internal',   't' => '12ms',      's' => 'OK'],
        ['n' => 'status.uptime.org',    't' => '188ms',     's' => 'OK'],
        ['n' => 'media.bucket.s3',      't' => '301ms',     's' => 'OK'],
        ['n' => 'jobs.cron.run',        't' => '4ms',       's' => 'OK'],
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Uppi — quiet, until something breaks.</title>

    <meta name="description"
          content="Open-source uptime monitoring for websites and APIs. Uppi watches every minute and tells you the moment things drift. HTTP, TCP, cron, browser flows and servers on one calm dashboard.">
    <meta name="keywords" content="uptime monitoring, website monitoring, api monitoring, cron monitoring, browser tests, server monitoring, open-source">
    <meta name="author" content="Janyk Steenbeek">

    <meta property="og:title" content="Uppi — quiet, until something breaks.">
    <meta property="og:description"
          content="Open-source uptime monitoring. HTTP, TCP, cron heartbeats, server metrics and browser flows on one calm dashboard.">
    <meta property="og:image" content="{{ asset('static/iPad.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@janyksteenbeek">
    <meta name="twitter:creator" content="@janyksteenbeek">
    <meta name="twitter:title" content="Uppi — quiet, until something breaks.">
    <meta name="twitter:description"
          content="Open-source uptime monitoring. HTTP, TCP, cron heartbeats, server metrics and browser flows on one calm dashboard.">
    <meta name="twitter:image" content="{{ asset('static/iPad.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}"/>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,300..700;1,6..72,300..700&family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">

    <script defer src="https://statisfyer.nl/script.js" data-website-id="5e2d6b2a-67a0-4965-ace2-8677b879fbdf"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --red: #E5392E;
            --red-soft: #FFE9E6;
            --ink: #0E0E10;
            --ink-2: #3A3A40;
            --muted: #8A8A93;
            --line: #E8E6E1;
            --bg: #F6F4EF;
            --paper: #FBFAF7;
            --green: #1F8A5B;
            --display: 'Newsreader', ui-serif, Georgia, serif;
            --body: 'Geist', 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            --mono: 'Geist Mono', ui-monospace, 'JetBrains Mono', monospace;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            background: var(--bg);
            color: var(--ink);
            font-family: var(--body);
            font-feature-settings: 'ss01', 'cv11';
            letter-spacing: -0.01em;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        a { color: inherit; }
        img, svg { display: block; max-width: 100%; }
        .pulse-mono { font-family: var(--mono); letter-spacing: 0; }
        .pulse-display { font-family: var(--display); letter-spacing: -0.03em; font-weight: 400; }
        [x-cloak] { display: none !important; }

        .pulse-shell { max-width: 1440px; margin: 0 auto; position: relative; overflow: hidden; }

        /* ===== NAV ===== */
        .pulse-nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 22px 48px;
            border-bottom: 1px solid var(--line);
            background: var(--bg);
            position: sticky; top: 0; z-index: 50;
        }
        .pulse-logo { display: inline-flex; align-items: center; text-decoration: none; color: var(--ink); }
        .pulse-logo img { height: 22px; width: auto; display: block; }
        .pulse-nav-links { display: flex; gap: 32px; font-size: 14px; color: var(--ink-2); }
        .pulse-nav-links a { text-decoration: none; transition: color .15s; }
        .pulse-nav-links a:hover { color: var(--ink); }
        .pulse-nav-cta { display: flex; align-items: center; gap: 12px; }
        .pulse-nav-mobile { display: none; }

        .pulse-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 14px; border-radius: 999px;
            border: 1px solid var(--line); background: var(--paper);
            font-size: 13px; color: var(--ink-2);
        }
        .pulse-eu {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px 6px 8px; border-radius: 999px;
            border: 1px solid var(--line); background: var(--paper);
            font-family: var(--mono); font-size: 12px; color: var(--ink); font-weight: 500;
        }
        .pulse-eu-flag { width: 16px; height: 16px; flex-shrink: 0; }
        .pulse-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); position: relative; flex-shrink: 0; }
        .pulse-dot::after {
            content: ''; position: absolute; inset: -4px; border-radius: 50%;
            border: 1.5px solid var(--green); opacity: 0.4;
            animation: pulse-ring 2s ease-out infinite;
        }
        @keyframes pulse-ring { 0% { transform: scale(0.6); opacity: 0.6; } 100% { transform: scale(1.6); opacity: 0; } }

        .pulse-btn {
            font-size: 14px; padding: 10px 18px; border-radius: 999px;
            background: var(--ink); color: white; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px; font-family: inherit; font-weight: 500;
            text-decoration: none; transition: background .15s;
        }
        .pulse-btn:hover { background: var(--red); }
        .pulse-btn-ghost {
            background: transparent; color: var(--ink); border: 1px solid var(--line);
            padding: 9px 17px;
        }
        .pulse-btn-ghost:hover { background: transparent; border-color: var(--ink); color: var(--ink); }

        .pulse-icon-btn { background: transparent; border: none; padding: 8px; cursor: pointer; color: var(--ink); }

        /* ===== HERO ===== */
        .pulse-hero { padding: 72px 48px 32px; position: relative; }
        .pulse-eyebrow {
            display: inline-flex; align-items: center; gap: 10px;
            font-family: var(--mono); font-size: 12px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 28px;
        }
        .pulse-eyebrow-tick { width: 16px; height: 1px; background: var(--ink); }
        .pulse-h1 {
            font-family: var(--display); font-weight: 400;
            font-size: clamp(48px, 8vw, 96px); line-height: 0.95; letter-spacing: -0.04em;
            margin: 0; max-width: 1100px;
        }
        .pulse-h1 em { font-style: italic; color: var(--red); font-weight: 300; }
        .pulse-lede {
            font-size: 19px; line-height: 1.5; color: var(--ink-2);
            max-width: 540px; margin: 36px 0 0;
        }
        .pulse-cta-row { display: flex; align-items: center; gap: 16px; margin-top: 36px; flex-wrap: wrap; }
        .pulse-meta-row {
            display: flex; align-items: center; gap: 28px; margin-top: 28px; flex-wrap: wrap;
            font-size: 13px; color: var(--ink-2);
        }
        .pulse-meta-item { display: inline-flex; align-items: center; gap: 8px; }
        .pulse-meta-item svg { width: 14px; height: 14px; color: var(--muted); flex-shrink: 0; }
        .pulse-meta-item b { color: var(--ink); font-weight: 500; }

        /* ===== HERO INSTRUMENT ===== */
        .pulse-instrument {
            margin-top: 64px; background: var(--paper);
            border: 1px solid var(--line); border-radius: 18px;
            position: relative; overflow: hidden;
        }
        .pulse-inst-meter {
            display: grid; grid-template-columns: 1fr 1fr auto;
            align-items: stretch;
            border-bottom: 1px solid var(--line);
        }
        .pulse-inst-vital {
            padding: 22px 28px;
            border-right: 1px solid var(--line);
            display: flex; flex-direction: column; gap: 6px;
            position: relative;
        }
        .pulse-inst-vital::before {
            content: ''; position: absolute; left: 0; top: 22px; bottom: 22px; width: 2px;
        }
        .pulse-inst-vital.up::before { background: var(--green); }
        .pulse-inst-vital.err::before { background: var(--red); }
        .pulse-inst-vital-lbl {
            font-family: var(--mono); font-size: 10px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.14em;
        }
        .pulse-inst-vital-val {
            font-family: var(--display); font-size: 40px; letter-spacing: -0.03em;
            font-weight: 400; line-height: 1; display: flex; align-items: baseline; gap: 10px;
        }
        .pulse-inst-vital-val small {
            font-family: var(--mono); font-size: 12px; color: var(--muted); letter-spacing: 0;
        }
        .pulse-inst-vital.up .pulse-inst-vital-val { color: var(--ink); }
        .pulse-inst-vital.err .pulse-inst-vital-val { color: var(--red); }
        .pulse-inst-vital-sub {
            font-family: var(--mono); font-size: 11px; color: var(--ink-2);
            margin-top: 2px;
        }
        .pulse-inst-status {
            padding: 0 28px; display: flex; align-items: center; gap: 10px;
            font-family: var(--mono); font-size: 11px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.12em;
        }

        .pulse-inst-body { padding: 24px 28px 28px; }
        .pulse-inst-head {
            display: flex; justify-content: space-between; align-items: center; gap: 18px;
            margin-bottom: 8px; flex-wrap: wrap;
        }
        .pulse-inst-title {
            font-family: var(--mono); font-size: 11px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.14em;
        }
        .pulse-inst-legend {
            display: flex; gap: 20px; font-size: 11px; color: var(--ink-2);
            font-family: var(--mono); flex-wrap: wrap;
        }
        .pulse-inst-legend span { display: inline-flex; align-items: center; gap: 6px; }
        .pulse-inst-legend i { display: inline-block; width: 10px; height: 2px; border-radius: 2px; }

        .pulse-graph { width: 100%; height: 220px; display: block; overflow: visible; }

        .pulse-inst-issues { margin-top: 18px; border-top: 1px solid var(--line); }
        .pulse-issue {
            display: grid; grid-template-columns: 14px 1fr auto auto;
            gap: 16px; align-items: center;
            padding: 14px 4px; border-bottom: 1px solid var(--line);
            font-family: var(--mono); font-size: 12px;
        }
        .pulse-issue:last-child { border-bottom: none; }
        .pulse-issue .sev { width: 8px; height: 8px; border-radius: 50%; background: var(--red); }
        .pulse-issue .sev.warn { background: #f59e0b; }
        .pulse-issue .label { color: var(--ink); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pulse-issue .label b { font-weight: 500; color: var(--red); margin-right: 6px; }
        .pulse-issue .label .where { color: var(--muted); margin-left: 8px; font-size: 11px; }
        .pulse-issue .ts { color: var(--muted); font-size: 11px; min-width: 64px; text-align: right; }
        .pulse-issue .ct {
            font-family: var(--display); font-size: 16px; color: var(--ink);
            letter-spacing: -0.01em; min-width: 48px; text-align: right;
        }


        /* ===== TICKER ===== */
        .pulse-ticker {
            margin-top: 32px; padding: 14px 20px; background: var(--ink); color: #d4d4d4;
            border-radius: 12px; font-family: var(--mono); font-size: 12px;
            display: flex; gap: 32px; overflow: hidden; position: relative;
            -webkit-mask: linear-gradient(90deg, transparent, black 4%, black 96%, transparent);
                    mask: linear-gradient(90deg, transparent, black 4%, black 96%, transparent);
        }
        .pulse-ticker-track { display: flex; gap: 32px; animation: ticker 40s linear infinite; white-space: nowrap; }
        @keyframes ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .pulse-ticker-item { display: inline-flex; align-items: center; gap: 8px; }
        .pulse-ticker-ok { color: #4ade80; }
        .pulse-ticker-fail { color: #ff6b6b; }

        /* ===== SECTIONS ===== */
        .pulse-section { padding: 96px 48px; border-top: 1px solid var(--line); }
        .pulse-section-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 32px; margin-bottom: 64px; flex-wrap: wrap; }
        .pulse-section-eyebrow {
            font-family: var(--mono); font-size: 12px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 14px;
        }
        .pulse-section-title {
            font-family: var(--display); font-weight: 400;
            font-size: clamp(36px, 5vw, 56px); line-height: 1.0; letter-spacing: -0.03em;
            max-width: 720px; margin: 0;
        }
        .pulse-section-title em { font-style: italic; color: var(--red); }
        .pulse-section-sub { max-width: 340px; font-size: 14px; color: var(--ink-2); margin: 0; line-height: 1.5; }

        /* ===== COVERAGE GRID (whole-stack) ===== */
        .pulse-cov {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px;
            background: var(--line); border: 1px solid var(--line); border-radius: 16px; overflow: hidden;
        }
        .pulse-cov-cell {
            background: var(--paper); padding: 32px; min-height: 220px;
            display: flex; flex-direction: column; gap: 14px;
        }
        .pulse-cov-cell.feat { background: var(--ink); color: white; }
        .pulse-cov-num { font-family: var(--mono); font-size: 10px; color: var(--muted); letter-spacing: 0.14em; text-transform: uppercase; }
        .pulse-cov-cell.feat .pulse-cov-num { color: #a1a1aa; }
        .pulse-cov-name { font-family: var(--display); font-size: 24px; letter-spacing: -0.02em; font-weight: 400; margin: 0; }
        .pulse-cov-cell.feat .pulse-cov-name em { font-style: italic; color: var(--red); }
        .pulse-cov-desc { font-size: 14px; color: var(--ink-2); line-height: 1.55; margin: 0; max-width: 320px; }
        .pulse-cov-cell.feat .pulse-cov-desc { color: #d4d4d8; }
        .pulse-cov-foot { margin-top: auto; font-family: var(--mono); font-size: 11px; color: var(--muted); display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .pulse-cov-foot b { color: var(--ink); font-weight: 500; }
        .pulse-cov-cell.feat .pulse-cov-foot { color: #a1a1aa; }
        .pulse-cov-cell.feat .pulse-cov-foot b { color: white; }

        /* ===== MONITOR TYPES (legacy, retained for fallback) ===== */
        .pulse-types {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px;
            background: var(--line); border: 1px solid var(--line); border-radius: 16px; overflow: hidden;
        }
        .pulse-type { background: var(--paper); padding: 36px; min-height: 320px; display: flex; flex-direction: column; }
        .pulse-type-num { font-family: var(--mono); font-size: 11px; color: var(--muted); letter-spacing: 0.1em; }
        .pulse-type-icon { margin-top: 32px; margin-bottom: 28px; }
        .pulse-type-name {
            font-family: var(--display); font-size: 28px; letter-spacing: -0.02em;
            margin-bottom: 8px; font-weight: 400;
        }
        .pulse-type-desc { font-size: 14px; color: var(--ink-2); line-height: 1.5; margin-bottom: 20px; }
        .pulse-type-list { list-style: none; padding: 0; margin: auto 0 0; line-height: 1.9; }
        .pulse-type-list li {
            padding-left: 18px; position: relative;
            font-family: var(--mono); font-size: 12px; color: var(--ink-2);
        }
        .pulse-type-list li::before { content: '+'; position: absolute; left: 0; color: var(--red); }

        /* ===== SERVERS (light, normal block) ===== */
        .pulse-servers-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 48px; align-items: start; }
        .pulse-server-card {
            background: var(--paper); border: 1px solid var(--line); border-radius: 16px; padding: 28px;
        }
        .pulse-server-head {
            display: flex; justify-content: space-between; align-items: center; gap: 16px;
            margin-bottom: 24px; flex-wrap: wrap;
        }
        .pulse-server-name {
            font-family: var(--mono); font-size: 11px; color: var(--muted);
            letter-spacing: 0.1em; text-transform: uppercase;
        }
        .pulse-server-state {
            font-family: var(--display); font-size: 24px; margin-top: 4px; letter-spacing: -0.02em; color: var(--ink);
        }
        .pulse-server-tag {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px;
            background: rgba(31,138,91,0.12); color: var(--green);
            font-size: 12px; font-family: var(--mono);
        }
        .pulse-server-tag .d { width: 6px; height: 6px; border-radius: 50%; background: var(--green); }
        .pulse-server-metrics {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1px;
            background: var(--line); border: 1px solid var(--line); border-radius: 8px; overflow: hidden;
        }
        .pulse-metric { background: var(--paper); padding: 20px; }
        .pulse-metric-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .pulse-metric-label { font-family: var(--mono); font-size: 10px; color: var(--muted); letter-spacing: 0.1em; }
        .pulse-metric-value { font-family: var(--display); font-size: 24px; letter-spacing: -0.02em; color: var(--ink); }
        .pulse-metric-sub { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-top: 2px; }
        .pulse-install {
            margin-top: 20px; padding: 14px 16px; background: var(--ink); border-radius: 8px;
            font-family: var(--mono); font-size: 12px; color: #d4d4d8; overflow-x: auto;
        }
        .pulse-install .pr { color: #71717a; }

        .pulse-server-bullets { display: grid; gap: 20px; }
        .pulse-bullet { padding-left: 24px; border-left: 1px solid var(--line); position: relative; }
        .pulse-bullet::before { content: ''; position: absolute; left: -1px; top: 0; width: 1px; height: 32px; background: var(--red); }
        .pulse-bullet h4 { margin: 0; font-family: var(--display); font-size: 18px; font-weight: 400; letter-spacing: -0.02em; color: var(--ink); }
        .pulse-bullet p { margin: 8px 0 0; font-size: 14px; color: var(--ink-2); line-height: 1.5; }

        /* ===== INCIDENT RESPONSE (dark) ===== */
        .pulse-incident { background: var(--ink); color: white; border-top: none; }
        .pulse-incident .pulse-section-eyebrow { color: #a1a1aa; }
        .pulse-incident .pulse-section-title { color: white; }
        .pulse-incident .pulse-section-sub { color: #a1a1aa; }
        .pulse-incident-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 48px; align-items: start; }
        .pulse-incident-card {
            background: #1a1a1d; border: 1px solid #2a2a2e; border-radius: 16px; overflow: hidden;
        }
        .pulse-incident-head {
            display: flex; justify-content: space-between; align-items: center; gap: 16px;
            padding: 18px 24px; border-bottom: 1px solid #2a2a2e; flex-wrap: wrap;
        }
        .pulse-incident-id {
            font-family: var(--mono); font-size: 11px; color: #71717a;
            letter-spacing: 0.1em; text-transform: uppercase;
        }
        .pulse-incident-title {
            font-family: var(--display); font-size: 22px; margin-top: 4px; letter-spacing: -0.02em; color: white;
        }
        .pulse-incident-tag {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px;
            background: rgba(31,138,91,0.15); color: #4ade80;
            font-size: 12px; font-family: var(--mono);
        }
        .pulse-incident-tag .d { width: 6px; height: 6px; border-radius: 50%; background: #4ade80; }
        .pulse-incident-row {
            display: grid; grid-template-columns: 84px 64px 1fr; gap: 16px; align-items: center;
            padding: 14px 24px; border-bottom: 1px solid #232326;
            font-family: var(--mono); font-size: 12px;
        }
        .pulse-incident-row:last-child { border-bottom: none; }
        .pulse-incident-row .t { color: #71717a; }
        .pulse-incident-row .tag { padding: 2px 8px; border-radius: 4px; font-size: 10px; letter-spacing: 0.06em; text-align: center; }
        .pulse-incident-row .tag.detect { background: rgba(229,57,46,0.18); color: #ff8a82; }
        .pulse-incident-row .tag.neutral { background: rgba(255,255,255,0.08); color: #a1a1aa; }
        .pulse-incident-row .tag.ok { background: rgba(31,138,91,0.18); color: #4ade80; }
        .pulse-incident-row .body { color: #e4e4e7; }
        .pulse-incident-row .body b { font-weight: 500; }
        .pulse-incident-row .body .det { color: #71717a; margin-left: 10px; }
        .pulse-incident .pulse-bullet { border-left-color: #2a2a2e; }
        .pulse-incident .pulse-bullet h4 { color: white; }
        .pulse-incident .pulse-bullet p { color: #a1a1aa; }

        /* ===== ALERTS ===== */
        .pulse-alerts { display: grid; grid-template-columns: 1.4fr 1fr; gap: 32px; align-items: start; }
        .pulse-alerts-channels { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .pulse-channel {
            background: var(--paper); border: 1px solid var(--line); border-radius: 14px;
            padding: 20px; display: flex; flex-direction: column; gap: 12px;
            transition: transform .2s, border-color .2s;
        }
        .pulse-channel:hover { border-color: var(--ink); transform: translateY(-2px); }
        .pulse-channel-name { font-size: 14px; font-weight: 500; }
        .pulse-channel-meta { font-size: 11px; color: var(--muted); font-family: var(--mono); }
        .pulse-channel-icon { width: 32px; height: 32px; display: grid; place-items: center; border-radius: 8px; background: var(--bg); font-family: var(--mono); font-size: 14px; }
        .pulse-channel-icon svg { width: 18px; height: 18px; display: block; }

        .pulse-alert-demo {
            background: var(--ink); color: white; border-radius: 16px;
            padding: 24px; font-family: var(--mono); font-size: 12px; line-height: 1.7;
        }
        .pulse-alert-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #2a2a2e;
        }
        .pulse-alert-head .lab { color: #71717a; font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; }
        .pulse-alert-line { display: flex; gap: 12px; padding: 8px 0; border-bottom: 1px solid #2a2a2e; align-items: center; }
        .pulse-alert-line:last-child { border-bottom: none; }
        .pulse-alert-time { color: #71717a; min-width: 60px; flex-shrink: 0; }
        .pulse-alert-tag { padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .pulse-alert-tag.fail { background: rgba(229,57,46,0.15); color: #ff8a82; }
        .pulse-alert-tag.ok { background: rgba(31,138,91,0.15); color: #4ade80; }
        .pulse-alert-tag.send { background: rgba(255,255,255,0.08); color: #a1a1aa; }
        .pulse-alert-line .body { color: #e4e4e7; }

        /* ===== BROWSER TESTS ===== */
        .pulse-tests { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; }
        .pulse-test-window {
            background: var(--paper); border: 1px solid var(--line); border-radius: 16px;
            overflow: hidden; box-shadow: 0 24px 60px -30px rgba(0,0,0,0.15);
        }
        .pulse-test-bar { background: var(--bg); padding: 12px 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--line); }
        .pulse-test-bar .dot { width: 10px; height: 10px; border-radius: 50%; background: #d4d4d4; }
        .pulse-test-url {
            margin-left: 8px; padding: 4px 12px; background: white; border: 1px solid var(--line);
            border-radius: 6px; font-family: var(--mono); font-size: 11px;
            color: var(--muted); flex: 1;
        }
        .pulse-test-steps { padding: 20px; }
        .pulse-test-step {
            display: grid; grid-template-columns: 28px 1fr auto; gap: 12px; align-items: center;
            padding: 12px 0; border-bottom: 1px dashed var(--line); font-size: 13px;
        }
        .pulse-test-step:last-child { border-bottom: none; }
        .pulse-test-step-num {
            width: 22px; height: 22px; border-radius: 50%; background: var(--bg);
            display: grid; place-items: center; font-family: var(--mono); font-size: 10px;
            color: var(--ink-2);
        }
        .pulse-test-step-label { color: var(--ink); font-weight: 500; }
        .pulse-test-step-detail { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-left: 8px; }
        .pulse-test-step-time { font-family: var(--mono); font-size: 11px; color: var(--green); }
        .pulse-test-step.active .pulse-test-step-num { background: var(--red); color: white; }
        .pulse-test-step.pending .pulse-test-step-num { background: transparent; border: 1px dashed var(--line); }
        .pulse-test-step.pending .pulse-test-step-time { color: var(--muted); }
        .pulse-tests-bullets { display: grid; gap: 24px; }
        .pulse-tests-bullets .pulse-bullet { border-left-color: var(--line); }
        .pulse-tests-bullets .pulse-bullet h4 { font-size: 20px; color: var(--ink); }
        .pulse-tests-bullets .pulse-bullet p { color: var(--ink-2); }

        /* ===== TEST USES (chips below the test window) ===== */
        .pulse-test-uses {
            display: flex; gap: 8px; padding: 14px 20px; flex-wrap: wrap;
            border-top: 1px solid var(--line); background: var(--bg);
        }
        .pulse-test-uses .lab {
            font-family: var(--mono); font-size: 11px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.1em; align-self: center; margin-right: 4px;
        }
        .pulse-test-uses .chip {
            font-family: var(--mono); font-size: 11px; padding: 4px 10px;
            background: var(--paper); border: 1px solid var(--line); border-radius: 6px;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .pulse-test-uses .chip .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); }

        /* ===== ERRORS card (Sentry-style) ===== */
        .pulse-err-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 32px; align-items: start; }
        .pulse-err-card {
            background: var(--ink); color: white; border-radius: 16px; overflow: hidden;
            font-family: var(--body);
        }
        .pulse-err-head {
            padding: 24px; display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: start;
            border-bottom: 1px solid #2a2a2e;
        }
        .pulse-err-tag {
            display: inline-flex; align-items: center;
            font-family: var(--mono); font-size: 11px; color: #ff8a82;
            letter-spacing: 0.1em; text-transform: uppercase;
        }
        .pulse-err-title {
            font-family: var(--display); font-size: 22px; line-height: 1.4;
            font-weight: 400; letter-spacing: -0.01em; margin: 8px 0 4px; color: white;
        }
        .pulse-err-title b { font-weight: 500; }
        .pulse-err-msg { font-family: var(--mono); font-size: 12px; color: #a1a1aa; margin: 0; }
        .pulse-err-spark { text-align: right; }
        .pulse-err-spark svg { display: block; margin-left: auto; }
        .pulse-err-spark-num { font-family: var(--display); font-size: 26px; letter-spacing: -0.02em; line-height: 1; margin-top: 4px; }
        .pulse-err-spark-lbl { font-family: var(--mono); font-size: 10px; color: #71717a; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 2px; }

        .pulse-err-meta {
            display: grid; grid-template-columns: repeat(4, 1fr);
            border-bottom: 1px solid #2a2a2e;
        }
        .pulse-err-meta-cell { padding: 16px 24px; border-right: 1px solid #2a2a2e; }
        .pulse-err-meta-cell:last-child { border-right: none; }
        .pulse-err-meta-lbl { font-family: var(--mono); font-size: 10px; color: #71717a; text-transform: uppercase; letter-spacing: 0.1em; }
        .pulse-err-meta-val { font-family: var(--mono); font-size: 13px; color: white; margin-top: 4px; }
        .pulse-err-meta-val.red { color: #ff8a82; }

        .pulse-err-stack {
            padding: 16px 0;
            font-family: var(--mono); font-size: 12px; line-height: 1.7;
            background: #0e0e10; border-bottom: 1px solid #2a2a2e;
            overflow-x: auto;
        }
        .pulse-err-stack-line { display: grid; grid-template-columns: 48px 1fr; gap: 16px; padding: 2px 24px 2px 0; color: #d4d4d8; white-space: nowrap; }
        .pulse-err-stack-line .ln { color: #525258; text-align: right; user-select: none; }
        .pulse-err-stack-line.frame { background: rgba(229,57,46,0.12); }
        .pulse-err-stack-line.frame b { color: #ff8a82; font-weight: 500; }
        .pulse-err-stack-line b { color: #ff8a82; font-weight: 400; }
        .pulse-err-stack-line .kw { color: #a1a1aa; }
        .pulse-err-stack-line .kw2 { color: #ffffff; }
        .pulse-err-stack-line .fn { color: #93c5fd; }
        .pulse-err-stack-line .cm { color: #71717a; font-style: italic; }

        .pulse-err-bread { padding: 18px 24px; }
        .pulse-err-bread h5 {
            font-family: var(--mono); font-size: 11px; color: #71717a;
            text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 12px; font-weight: 500;
        }
        .pulse-err-bread-row {
            display: grid; grid-template-columns: 64px 56px 1fr; gap: 12px; align-items: center;
            padding: 6px 0; font-family: var(--mono); font-size: 12px; color: #d4d4d8;
        }
        .pulse-err-bread-row .t { color: #71717a; }
        .pulse-err-bread-row .tag { text-align: center; padding: 2px 6px; border-radius: 4px; font-size: 10px; letter-spacing: 0.04em; }
        .pulse-err-bread-row .tag.nav,
        .pulse-err-bread-row .tag.click { background: rgba(255,255,255,0.08); color: #d4d4d8; }
        .pulse-err-bread-row .tag.http { background: rgba(31,138,91,0.16); color: #4ade80; }
        .pulse-err-bread-row .tag.err { background: rgba(229,57,46,0.18); color: #ff8a82; }
        .pulse-err-bread-row.err { color: #ff8a82; }

        /* ===== STATS ===== */
        .pulse-stats-bar {
            display: grid; grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);
        }
        .pulse-stat-cell { padding: 48px; border-right: 1px solid var(--line); }
        .pulse-stat-cell:last-child { border-right: none; }
        .pulse-stat-cell-num {
            font-family: var(--display); font-size: clamp(56px, 7vw, 80px); line-height: 1;
            letter-spacing: -0.04em; font-weight: 400; white-space: nowrap;
        }
        .pulse-stat-cell-label {
            font-family: var(--mono); font-size: 12px;
            color: var(--muted); margin-top: 12px; letter-spacing: 0.05em;
        }

        /* ===== PRICING ===== */
        .pulse-pricing { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
        .pulse-plan {
            background: var(--paper); border: 1px solid var(--line); border-radius: 20px;
            padding: 40px; display: flex; flex-direction: column;
        }
        .pulse-plan.featured { background: var(--ink); color: var(--paper); border-color: var(--ink); }
        .pulse-plan-name {
            font-family: var(--mono); font-size: 12px;
            text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;
            color: var(--muted);
        }
        .pulse-plan.featured .pulse-plan-name { color: #a1a1aa; }
        .pulse-plan-price {
            font-family: var(--display); font-size: clamp(56px, 6.5vw, 72px); letter-spacing: -0.04em;
            line-height: 1; font-weight: 400; display: flex; align-items: baseline; gap: 8px;
        }
        .pulse-plan-price small { font-size: 16px; color: var(--muted); font-weight: 400; font-family: var(--body); letter-spacing: 0; }
        .pulse-plan.featured .pulse-plan-price small { color: #a1a1aa; }
        .pulse-plan-tag { font-size: 14px; margin: 16px 0 0; color: var(--ink-2); max-width: 360px; line-height: 1.5; }
        .pulse-plan.featured .pulse-plan-tag { color: #d4d4d4; }
        .pulse-plan-features { list-style: none; padding: 0; margin: 32px 0; flex: 1; }
        .pulse-plan-features li {
            font-size: 14px; padding: 10px 0; border-top: 1px solid var(--line);
            display: flex; align-items: center; gap: 10px;
        }
        .pulse-plan.featured .pulse-plan-features li { border-color: #2a2a2e; }
        .pulse-plan-features li::before {
            content: ''; width: 14px; height: 14px; border-radius: 50%;
            border: 1px solid var(--ink); flex-shrink: 0;
            background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 14 14'><path d='M3.5 7.5l2 2 5-5' stroke='%230E0E10' stroke-width='1.5' fill='none' stroke-linecap='round'/></svg>") center no-repeat;
        }
        .pulse-plan.featured .pulse-plan-features li::before {
            border-color: var(--paper);
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 14 14'><path d='M3.5 7.5l2 2 5-5' stroke='%23FBFAF7' stroke-width='1.5' fill='none' stroke-linecap='round'/></svg>");
        }
        .pulse-plan-cta {
            padding: 14px 20px; border-radius: 999px; text-align: center;
            font-size: 14px; font-weight: 500; cursor: pointer; border: none;
            font-family: inherit; text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            transition: opacity .15s, background .15s;
        }
        .pulse-plan-cta.dark { background: var(--ink); color: white; }
        .pulse-plan-cta.dark:hover { background: var(--red); }
        .pulse-plan-cta.light { background: var(--red); color: white; }
        .pulse-plan-cta.light:hover { opacity: 0.9; }

        /* ===== FOOTER ===== */
        .pulse-foot { padding: 80px 48px 40px; border-top: 1px solid var(--line); background: var(--bg); }
        .pulse-foot-head {
            display: flex; justify-content: space-between; align-items: center; gap: 24px;
            padding-bottom: 32px; margin-bottom: 48px; border-bottom: 1px solid var(--line);
            flex-wrap: wrap;
        }
        .pulse-foot-mark img { height: 28px; width: auto; display: block; }
        .pulse-foot-status {
            display: inline-flex; align-items: center; gap: 14px; flex-wrap: wrap;
            font-family: var(--mono); font-size: 12px; color: var(--ink-2);
        }
        .pulse-foot-status .sep { color: var(--line); }
        .pulse-foot-cols {
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px;
        }
        .pulse-foot-col h4 {
            font-family: var(--mono); font-size: 11px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.12em; margin: 0 0 16px; font-weight: 400;
        }
        .pulse-foot-col a { display: block; font-size: 14px; color: var(--ink); text-decoration: none; padding: 6px 0; transition: color .15s; }
        .pulse-foot-col a:hover { color: var(--red); }
        .pulse-foot-col p { font-size: 14px; color: var(--ink-2); line-height: 1.6; max-width: 320px; margin: 0; }
        .pulse-foot-end {
            margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--line);
            display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap;
            font-size: 12px; color: var(--muted); font-family: var(--mono);
        }

        /* ===== Mobile menu ===== */
        .pulse-mobile-menu {
            position: fixed; inset: 0; z-index: 100;
            background: var(--bg); padding: 28px;
            display: flex; flex-direction: column; gap: 6px;
        }
        .pulse-mobile-menu-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .pulse-mobile-menu a { font-family: var(--display); font-size: 36px; color: var(--ink); text-decoration: none; padding: 12px 0; border-bottom: 1px solid var(--line); }
        .pulse-mobile-menu a:last-of-type { border-bottom: none; }

        /* ===== Responsive ===== */
        @media (max-width: 1080px) {
            .pulse-section-head { flex-direction: column; align-items: flex-start; margin-bottom: 48px; }
            .pulse-inst-meter { grid-template-columns: 1fr; }
            .pulse-inst-vital { border-right: none; border-bottom: 1px solid var(--line); }
            .pulse-inst-status { padding: 14px 28px; }
            .pulse-types { grid-template-columns: 1fr; }
            .pulse-cov { grid-template-columns: repeat(2, 1fr); }
            .pulse-err-grid { grid-template-columns: 1fr; }
            .pulse-err-meta { grid-template-columns: repeat(2, 1fr); }
            .pulse-err-meta-cell:nth-child(2n) { border-right: none; }
            .pulse-err-meta-cell:nth-child(-n+2) { border-bottom: 1px solid #2a2a2e; }
            .pulse-servers-grid { grid-template-columns: 1fr; }
            .pulse-alerts { grid-template-columns: 1fr; }
            .pulse-tests { grid-template-columns: 1fr; }
            .pulse-stats-bar { grid-template-columns: repeat(2, 1fr); }
            .pulse-stat-cell:nth-child(2n) { border-right: none; }
            .pulse-stat-cell:nth-child(-n+2) { border-bottom: 1px solid var(--line); }
            .pulse-pricing { grid-template-columns: 1fr; }
            .pulse-foot-cols { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .pulse-nav { padding: 18px 24px; }
            .pulse-nav-links, .pulse-nav-cta .pulse-pill, .pulse-nav-cta .pulse-btn-ghost { display: none; }
            .pulse-nav-mobile { display: inline-flex; }
            .pulse-hero { padding: 48px 24px 32px; }
            .pulse-instrument { padding: 20px; }
            .pulse-section { padding: 72px 24px; }
            .pulse-stat-cell { padding: 32px 24px; }
            .pulse-foot { padding: 64px 24px 32px; }
            .pulse-foot-head { flex-direction: column; align-items: flex-start; gap: 16px; padding-bottom: 32px; margin-bottom: 32px; }
            .pulse-foot-cols { grid-template-columns: 1fr; gap: 32px; }
            .pulse-alerts-channels { grid-template-columns: repeat(2, 1fr); }
            .pulse-server-metrics { grid-template-columns: 1fr; }
        }
        @media (max-width: 520px) {
            .pulse-alerts-channels { grid-template-columns: 1fr; }
            .pulse-stats-bar { grid-template-columns: 1fr; }
            .pulse-stat-cell { border-right: none; border-bottom: 1px solid var(--line); }
            .pulse-stat-cell:last-child { border-bottom: none; }
            .pulse-incident-grid { grid-template-columns: 1fr; }
            .pulse-incident-row { grid-template-columns: 72px 1fr; gap: 12px; }
            .pulse-incident-row .tag { grid-column: 1 / -1; justify-self: start; }
            .pulse-cov { grid-template-columns: 1fr; }
            .pulse-err-meta { grid-template-columns: 1fr; }
            .pulse-err-meta-cell { border-right: none; border-bottom: 1px solid #2a2a2e; }
            .pulse-err-meta-cell:last-child { border-bottom: none; }
        }
    </style>
</head>
<body x-data="{ open: false }">
<div class="pulse-shell">

    {{-- NAV --}}
    <nav class="pulse-nav">
        <a href="/" class="pulse-logo" aria-label="Uppi">
            <img src="{{ asset('logo.svg') }}" alt="Uppi">
        </a>
        <div class="pulse-nav-links">
            <a href="#coverage">Monitors</a>
            <a href="#tests">Tests</a>
            <a href="#errors">Errors</a>
            <a href="#response">Incidents</a>
            <a href="#pricing">Pricing</a>
            <a href="https://github.com/janyksteenbeek/uppi/blob/main/README.md">Docs</a>
        </div>
        <div class="pulse-nav-cta">
            @php
                $euStars = '';
                for ($i = 0; $i < 12; $i++) {
                    $a = ($i / 12) * M_PI * 2 - M_PI / 2;
                    $sx = number_format(12 + cos($a) * 7, 2, '.', '');
                    $sy = number_format(12 + sin($a) * 7, 2, '.', '');
                    $euStars .= "<circle cx=\"{$sx}\" cy=\"{$sy}\" r=\"1.1\" fill=\"#FFCC00\"/>";
                }
            @endphp
            <span class="pulse-eu">
                <svg class="pulse-eu-flag" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="12" fill="#003399"/>
                    {!! $euStars !!}
                </svg>
                EU hosted
            </span>
            <a class="pulse-btn pulse-btn-ghost" href="{{ $dashboardUrl }}">Sign in</a>
            <a class="pulse-btn" href="{{ $dashboardUrl }}">Start free →</a>
            <button type="button" class="pulse-icon-btn pulse-nav-mobile" x-on:click="open = true" aria-label="Open menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                    <path d="M3 7h18M3 12h18M3 17h18"/>
                </svg>
            </button>
        </div>
    </nav>

    {{-- Mobile menu --}}
    <div class="pulse-mobile-menu" x-show="open" x-cloak x-transition>
        <div class="pulse-mobile-menu-head">
            <span class="pulse-mono" style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.14em;">menu</span>
            <button type="button" class="pulse-icon-btn" x-on:click="open = false" aria-label="Close menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                    <path d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>
        <a href="#coverage" x-on:click="open = false">Monitors</a>
        <a href="#tests" x-on:click="open = false">Tests</a>
        <a href="#errors" x-on:click="open = false">Errors</a>
        <a href="#servers" x-on:click="open = false">Servers</a>
        <a href="#pricing" x-on:click="open = false">Pricing</a>
        <a href="https://github.com/janyksteenbeek/uppi/blob/main/README.md">Docs</a>
        <a href="https://github.com/janyksteenbeek/uppi">GitHub</a>
        <a href="{{ $dashboardUrl }}">Sign in</a>
        <a href="{{ $dashboardUrl }}" style="color:var(--red);">Start free →</a>
    </div>

    {{-- HERO --}}
    <section class="pulse-hero">
        <div class="pulse-eyebrow">
            <span class="pulse-eyebrow-tick"></span>
            UPTIME · TESTS · ERRORS · CRON · OPEN SOURCE
        </div>
        <h1 class="pulse-h1">
            It will break.<br>
            You&rsquo;ll know <em>first.</em>
        </h1>
        <p class="pulse-lede">
            Uptime, browser tests, and exception tracking — on one calm dashboard, hosted in the EU.
        </p>
        <div class="pulse-cta-row">
            <a class="pulse-btn" href="{{ $dashboardUrl }}">Start monitoring — free <span style="opacity:.6;">→</span></a>
            <a class="pulse-btn pulse-btn-ghost" href="https://github.com/janyksteenbeek/uppi">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
                </svg>
                Star on GitHub
            </a>
        </div>
        <div class="pulse-meta-row">
            <span class="pulse-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12h4l3-9 4 18 3-9h4"/>
                </svg>
                <b>{{ Number::format($checksSinceYesterday) }}</b>&nbsp;checks since yesterday
            </span>
            <span class="pulse-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                </svg>
                <b>1-minute</b>&nbsp;interval
            </span>
            <span class="pulse-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/>
                </svg>
                <b>EU hosted</b>,&nbsp;GDPR by default
            </span>
        </div>

        {{-- INSTRUMENT --}}
        <div class="pulse-instrument">
            <div class="pulse-inst-meter">
                <div class="pulse-inst-vital up">
                    <span class="pulse-inst-vital-lbl">Uptime · 24h</span>
                    <span class="pulse-inst-vital-val">99.984<small>%</small></span>
                    <span class="pulse-inst-vital-sub">14 monitors · 187ms avg · 1 incident resolved</span>
                </div>
                <div class="pulse-inst-vital err">
                    <span class="pulse-inst-vital-lbl">Exceptions · 24h</span>
                    <span class="pulse-inst-vital-val">214<small>events</small></span>
                    <span class="pulse-inst-vital-sub">4 issues · 38 users · release v2.4.1-rc3</span>
                </div>
                <div class="pulse-inst-status">
                    <span class="pulse-dot"></span><span>live</span>
                </div>
            </div>
            <div class="pulse-inst-body">
            <div class="pulse-inst-head">
                <span class="pulse-inst-title">Response time &amp; exceptions · last 24 hours</span>
                <div class="pulse-inst-legend">
                    <span><i style="background:var(--red);"></i> response time</span>
                    <span><i style="background:#f59e0b;"></i> warnings</span>
                    <span><i style="background:var(--red); height:6px; width:2px; border-radius:0;"></i> exceptions</span>
                </div>
            </div>
            <svg class="pulse-graph" viewBox="0 0 {{ $graphW }} {{ $graphH }}" preserveAspectRatio="none" overflow="visible" aria-label="24-hour response time and exceptions">
                <defs>
                    <linearGradient id="pulseFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#E5392E" stop-opacity="0.10"/>
                        <stop offset="100%" stop-color="#E5392E" stop-opacity="0"/>
                    </linearGradient>
                    <pattern id="pulseGrid" x="0" y="0" width="80" height="40" patternUnits="userSpaceOnUse">
                        <path d="M80 0H0V40" fill="none" stroke="#E8E6E1" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="{{ $graphW }}" height="{{ $graphH }}" fill="url(#pulseGrid)"/>
                <line x1="0" y1="{{ $baseline }}" x2="{{ $graphW }}" y2="{{ $baseline }}" stroke="#E8E6E1" stroke-dasharray="4 4"/>
                <text x="8" y="{{ $baseline - 6 }}" font-size="10" fill="#8A8A93" font-family="Geist Mono">200ms baseline</text>

                <path id="pulse-fill" d="{{ $graphFill }}" fill="url(#pulseFill)"/>
                <path id="pulse-line" d="{{ $graphPath }}" fill="none" stroke="#E5392E" stroke-width="1.5" stroke-linejoin="round"/>

                {{-- exception rail label + ticks (static; tied to response curve) --}}
                <text x="8" y="{{ $excRailY - 6 }}" font-size="9" fill="#8A8A93" font-family="Geist Mono" letter-spacing="1">EXCEPTIONS</text>
                @foreach($excMarkers as $m)
                    @php
                        $tickX = $graphPts[$m['idx']][0];
                        $tickH = min(10, 2 + log($m['count'] + 1, 2) * 2);
                        $tickColor = $m['sev'] === 'warn' ? '#f59e0b' : '#E5392E';
                    @endphp
                    <line x1="{{ $tickX }}" y1="{{ $excRailY }}" x2="{{ $tickX }}" y2="{{ $excRailY + $tickH }}" stroke="{{ $tickColor }}" stroke-width="2" stroke-linecap="round"/>
                @endforeach

                {{-- big-spike annotation: links incident on curve to exception count below --}}
                <g id="pulse-spike" transform="translate({{ $spikePt[0] }}, {{ $spikePt[1] }})">
                    <line x1="0" y1="6" x2="0" y2="{{ $excRailY - $spikePt[1] - 4 }}" stroke="#E5392E" stroke-opacity="0.3" stroke-dasharray="2 3" stroke-width="1"/>
                    <circle r="4" fill="#E5392E"/>
                    <circle r="4" fill="none" stroke="#E5392E" opacity="0.4">
                        <animate attributeName="r" from="4" to="14" dur="2s" repeatCount="indefinite"/>
                        <animate attributeName="opacity" from="0.6" to="0" dur="2s" repeatCount="indefinite"/>
                    </circle>
                    <line x1="0" y1="0" x2="0" y2="-26" stroke="#3A3A40" stroke-width="1"/>
                    <rect x="-90" y="-46" width="180" height="22" rx="4" fill="#0E0E10"/>
                    <text x="0" y="-31" font-size="11" fill="white" text-anchor="middle" font-family="Geist Mono">14:02 · 38 exceptions · 4m32s</text>
                </g>

                {{-- live cursor --}}
                <line id="pulse-cursor-line" x1="{{ $cursorPt[0] }}" y1="0" x2="{{ $cursorPt[0] }}" y2="{{ $graphH }}" stroke="#0E0E10" stroke-opacity="0.15" stroke-dasharray="2 3"/>
                <circle id="pulse-cursor-outer" cx="{{ $cursorPt[0] }}" cy="{{ $cursorPt[1] }}" r="4" fill="#0E0E10"/>
                <circle id="pulse-cursor-inner" cx="{{ $cursorPt[0] }}" cy="{{ $cursorPt[1] }}" r="3" fill="white"/>
            </svg>

            <script>
                (function () {
                    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                    var line = document.getElementById('pulse-line');
                    var fill = document.getElementById('pulse-fill');
                    var spike = document.getElementById('pulse-spike');
                    var curLine = document.getElementById('pulse-cursor-line');
                    var curOuter = document.getElementById('pulse-cursor-outer');
                    var curInner = document.getElementById('pulse-cursor-inner');
                    if (!line || !fill || !curLine) return;

                    var W = {{ $graphW }}, H = {{ $graphH }}, N = {{ $graphN }}, baseline = {{ $baseline }};
                    var t = 0;
                    var paused = document.hidden;
                    document.addEventListener('visibilitychange', function () { paused = document.hidden; });

                    var railY = {{ $excRailY }};
                    var spikeDrop = spike ? spike.querySelector('line[stroke-dasharray]') : null;
                    function frame() {
                        if (!paused) {
                            t++;
                            var d = '';
                            var spikeX, spikeY;
                            var cx, cy;
                            for (var i = 0; i < N; i++) {
                                var x = i / (N - 1) * W;
                                var seed = Math.sin(i * 0.3) * 14 + Math.sin(i * 0.7) * 6 + Math.cos(i * 0.15) * 9;
                                var wob = Math.sin(t * 0.02 + i * 0.3) * 2.5;
                                var sp = i === 78 ? -42 : i === 79 ? -30 : 0;
                                var y = baseline - 16 + seed + wob + sp;
                                d += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ',' + y.toFixed(1) + ' ';
                                if (i === 78) { spikeX = x; spikeY = y; }
                                if (i === Math.floor((t * 0.5) % N)) { cx = x; cy = y; }
                            }
                            line.setAttribute('d', d);
                            fill.setAttribute('d', d + 'L' + W + ',' + H + ' L0,' + H + ' Z');
                            if (spike && spikeY !== undefined) {
                                spike.setAttribute('transform', 'translate(' + spikeX.toFixed(1) + ',' + spikeY.toFixed(1) + ')');
                                if (spikeDrop) spikeDrop.setAttribute('y2', (railY - spikeY - 4).toFixed(1));
                            }
                            if (cx !== undefined) {
                                curLine.setAttribute('x1', cx.toFixed(1));
                                curLine.setAttribute('x2', cx.toFixed(1));
                                curOuter.setAttribute('cx', cx.toFixed(1));
                                curOuter.setAttribute('cy', cy.toFixed(1));
                                curInner.setAttribute('cx', cx.toFixed(1));
                                curInner.setAttribute('cy', cy.toFixed(1));
                            }
                        }
                        requestAnimationFrame(frame);
                    }
                    requestAnimationFrame(frame);
                })();
            </script>
            <div class="pulse-inst-issues">
                @foreach([
                    ['sev'=>'err','type'=>'TypeError','msg'=>"Cannot read 'price' of undefined",'where'=>'checkout/cart.tsx:84','count'=>142,'when'=>'2s ago'],
                    ['sev'=>'err','type'=>'ReferenceError','msg'=>'paymentSession is not defined','where'=>'api/stripe.ts:212','count'=>38,'when'=>'1m ago'],
                    ['sev'=>'warn','type'=>'TimeoutWarn','msg'=>'/v1/inventory > 800ms budget','where'=>'lib/http.ts:47','count'=>21,'when'=>'4m ago'],
                    ['sev'=>'err','type'=>'NetworkError','msg'=>'ECONNRESET on retry','where'=>'webhook-relay','count'=>13,'when'=>'12m ago'],
                ] as $iss)
                    <div class="pulse-issue">
                        <span class="sev {{ $iss['sev'] === 'warn' ? 'warn' : '' }}"></span>
                        <span class="label"><b>{{ $iss['type'] }}</b>{{ $iss['msg'] }}<span class="where">· {{ $iss['where'] }}</span></span>
                        <span class="ts">{{ $iss['when'] }}</span>
                        <span class="ct">{{ $iss['count'] }}</span>
                    </div>
                @endforeach
            </div>
            </div>{{-- /inst-body --}}
        </div>

        {{-- TICKER --}}
        <div class="pulse-ticker">
            <div class="pulse-ticker-track">
                @for($i = 0; $i < 2; $i++)
                    @foreach($tickerItems as $it)
                        <span class="pulse-ticker-item">
                            <span class="{{ $it['s'] === 'OK' ? 'pulse-ticker-ok' : 'pulse-ticker-fail' }}">● {{ $it['s'] }}</span>
                            <span style="color:#71717a;">{{ $it['n'] }}</span>
                            <span>{{ $it['t'] }}</span>
                        </span>
                    @endforeach
                @endfor
            </div>
        </div>
    </section>

    {{-- COVERAGE — full application surface --}}
    <section class="pulse-section" id="coverage">
        <div class="pulse-section-head">
            <div>
                <div class="pulse-section-eyebrow">01 · Coverage</div>
                <h2 class="pulse-section-title">One platform. <em>The whole stack.</em></h2>
            </div>
            <p class="pulse-section-sub">
                From the edge request to the running process to the line that threw — Uppi watches every layer your app actually has.
            </p>
        </div>
        <div class="pulse-cov">
            <div class="pulse-cov-cell">
                <span class="pulse-cov-num">01 / Endpoint</span>
                <h3 class="pulse-cov-name">HTTP / HTTPS</h3>
                <p class="pulse-cov-desc">Status codes, response bodies, headers, redirects, TLS expiry.</p>
                <div class="pulse-cov-foot">●&nbsp; <b>1-min</b> interval&nbsp;·&nbsp;global probes</div>
            </div>
            <div class="pulse-cov-cell">
                <span class="pulse-cov-num">02 / Network</span>
                <h3 class="pulse-cov-name">TCP &amp; ports</h3>
                <p class="pulse-cov-desc">Postgres, Redis, mail relays, SSH — anything that holds a socket.</p>
                <div class="pulse-cov-foot">●&nbsp; <b>any</b> port&nbsp;·&nbsp;reachability + latency</div>
            </div>
            <div class="pulse-cov-cell">
                <span class="pulse-cov-num">03 / Jobs</span>
                <h3 class="pulse-cov-name">Cron heartbeats</h3>
                <p class="pulse-cov-desc">A unique URL per job. Notice the silence before the report is missing.</p>
                <div class="pulse-cov-foot">●&nbsp; <b>grace</b> windows&nbsp;·&nbsp;dead-job alerts</div>
            </div>
            <div class="pulse-cov-cell">
                <span class="pulse-cov-num">04 / Browser</span>
                <h3 class="pulse-cov-name">Synthetic tests</h3>
                <p class="pulse-cov-desc">Click together a real user flow. Replay it on schedule, headless.</p>
                <div class="pulse-cov-foot">●&nbsp; <b>visual</b> step builder&nbsp;·&nbsp;screenshots on fail</div>
            </div>
            <div class="pulse-cov-cell">
                <span class="pulse-cov-num">05 / Server</span>
                <h3 class="pulse-cov-name">Host metrics</h3>
                <p class="pulse-cov-desc">A small Go agent streams CPU, memory, disk, network — threshold alerts.</p>
                <div class="pulse-cov-foot">●&nbsp; <b>one-line</b> install&nbsp;·&nbsp;open source</div>
            </div>
            <div class="pulse-cov-cell feat">
                <span class="pulse-cov-num">06 / Runtime</span>
                <h3 class="pulse-cov-name">Exception <em>tracking</em></h3>
                <p class="pulse-cov-desc">Stack traces, breadcrumbs, release tags, user context. Group identical errors. Spot regressions the second they ship.</p>
                <div class="pulse-cov-foot">●&nbsp; <b>SDK</b> in 6 langs&nbsp;·&nbsp;same alerts as the rest</div>
            </div>
        </div>
    </section>

    {{-- INCIDENT RESPONSE (dark) --}}
    <section class="pulse-section pulse-incident" id="response">
        <div class="pulse-section-head">
            <div>
                <div class="pulse-section-eyebrow">02 · Response</div>
                <h2 class="pulse-section-title">From red to <em>resolved.</em></h2>
            </div>
            <p class="pulse-section-sub">
                One incident model across every check. Routed, acknowledged, broadcast, closed — all on one timeline you can hand to a regulator.
            </p>
        </div>

        <div class="pulse-incident-grid">
            <div class="pulse-incident-card">
                <div class="pulse-incident-head">
                    <div>
                        <div class="pulse-incident-id">incident #2841 · checkout.shop.dev</div>
                        <div class="pulse-incident-title">500 on POST /cart/checkout</div>
                    </div>
                    <span class="pulse-incident-tag"><span class="d"></span> resolved · 4m 32s</span>
                </div>
                @foreach([
                    ['t'=>'14:02:11','tag'=>'DETECT','cls'=>'detect','label'=>'Monitor failed','det'=>'3 consecutive 500s · EU-west probe'],
                    ['t'=>'14:02:11','tag'=>'ROUTE','cls'=>'neutral','label'=>'Paged on-call','det'=>'#ops-alerts · Maya R. (sms + push)'],
                    ['t'=>'14:02:38','tag'=>'ACK','cls'=>'neutral','label'=>'Acknowledged','det'=>'Maya R. · "looking — db pool exhausted?"'],
                    ['t'=>'14:03:02','tag'=>'PUBLIC','cls'=>'neutral','label'=>'Status page updated','det'=>'status.shop.dev · "Investigating checkout"'],
                    ['t'=>'14:04:51','tag'=>'NOTE','cls'=>'neutral','label'=>'Linked deploy','det'=>'release v2.4.1-rc3 · 2 min before first failure'],
                    ['t'=>'14:06:43','tag'=>'OK','cls'=>'ok','label'=>'Recovered','det'=>'rolled back · 12 consecutive 200s'],
                    ['t'=>'14:07:15','tag'=>'CLOSE','cls'=>'ok','label'=>'Post-mortem drafted','det'=>'auto-attached: graphs · alerts · deploy diff'],
                ] as $row)
                    <div class="pulse-incident-row">
                        <span class="t">{{ $row['t'] }}</span>
                        <span class="tag {{ $row['cls'] }}">{{ $row['tag'] }}</span>
                        <span class="body"><b>{{ $row['label'] }}</b><span class="det">· {{ $row['det'] }}</span></span>
                    </div>
                @endforeach
            </div>

            <div class="pulse-server-bullets">
                <div class="pulse-bullet">
                    <h4>One incident model</h4>
                    <p>HTTP, cron, browser test, exception — all roll up into the same timeline. One ack, one resolve, one record.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Status page in the loop</h4>
                    <p>Auto-publish to a hosted or self-hosted status page. Customers know before they ask.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Deploy correlation</h4>
                    <p>Every incident lists the last deploy. Rollback is one click and re-checks the failing monitor.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Post-mortems, written for you</h4>
                    <p>On close, Uppi attaches the graphs, the alert chain and the deploy diff. You write the human part.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ALERTS --}}
    <section class="pulse-section">
        <div class="pulse-section-head">
            <div>
                <div class="pulse-section-eyebrow">03 · Alerts</div>
                <h2 class="pulse-section-title">Reach the right person, <em>once.</em></h2>
            </div>
            <p class="pulse-section-sub">
                One incident, one page. Routing rules pick the channel and stop — no fan-out spam, no five Slacks repeating the same thing.
            </p>
        </div>

        <div class="pulse-alerts">
            <div class="pulse-alerts-channels">
                @php
                    $channels = [
                        ['name'=>'Email','meta'=>'detailed alert emails','svg'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m3 6 9 7 9-7"/></svg>'],
                        ['name'=>'Slack','meta'=>'channel notifications','svg'=>'<svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#E01E5A" d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313z"/><path fill="#36C5F0" d="M8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312z"/><path fill="#2EB67D" d="M18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312z"/><path fill="#ECB22E" d="M15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/></svg>'],
                        ['name'=>'Telegram','meta'=>'bot messages','svg'=>'<svg fill="#26A5E4" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>'],
                        ['name'=>'Pushover','meta'=>'push notifications','svg'=>'<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="20" height="20" rx="4" fill="#249DF1"/><text x="12" y="16.6" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-weight="700" font-size="13" fill="#fff">P</text></svg>'],
                        ['name'=>'SMS','meta'=>'via Bird','svg'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M21 11.5a8.38 8.38 0 0 1-9 8.32c-.86 0-1.7-.13-2.49-.38L3 21l1.56-4.68A8.38 8.38 0 0 1 21 11.5z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg>'],
                        ['name'=>'Webhook','meta'=>'custom integrations','svg'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>'],
                        ['name'=>'PagerDuty','meta'=>'on-call escalation','svg'=>'<svg fill="#06AC38" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M16.965 1.18C15.085.164 13.769 0 10.683 0H3.73v14.55h6.926c2.743 0 4.8-.164 6.61-1.37 1.975-1.303 3.004-3.484 3.004-6.007 0-2.716-1.262-4.896-3.305-5.994zm-5.5 10.326h-4.21V3.113l3.977-.027c3.62-.028 5.43 1.234 5.43 4.128 0 3.113-2.248 4.292-5.197 4.292zM3.73 17.61h3.525V24H3.73Z"/></svg>'],
                        ['name'=>'Discord','meta'=>'server messages','svg'=>'<svg fill="#5865F2" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z"/></svg>'],
                        ['name'=>'Mobile app','meta'=>'iOS · Android','svg'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg>'],
                    ];
                @endphp
                @foreach($channels as $c)
                    <div class="pulse-channel">
                        <div class="pulse-channel-icon">{!! $c['svg'] !!}</div>
                        <div>
                            <div class="pulse-channel-name">{{ $c['name'] }}</div>
                            <div class="pulse-channel-meta">{{ $c['meta'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pulse-alert-demo">
                <div class="pulse-alert-head">
                    <span class="lab">incident · INC-4821</span>
                    <span style="color:#ff8a82;">● firing</span>
                </div>
                <div class="pulse-alert-line"><span class="pulse-alert-time">14:02:11</span><span class="pulse-alert-tag fail">FAIL</span><span class="body">checkout.shop.dev → 503 Service Unavailable</span></div>
                <div class="pulse-alert-line"><span class="pulse-alert-time">14:02:11</span><span class="pulse-alert-tag send">SEND</span><span>→ #ops-alerts (slack)</span></div>
                <div class="pulse-alert-line"><span class="pulse-alert-time">14:02:11</span><span class="pulse-alert-tag send">SEND</span><span>→ on-call · Maya R. (sms)</span></div>
                <div class="pulse-alert-line"><span class="pulse-alert-time">14:02:42</span><span class="pulse-alert-tag send">ACK</span><span class="body">acknowledged · Maya R.</span></div>
                <div class="pulse-alert-line"><span class="pulse-alert-time">14:06:48</span><span class="pulse-alert-tag ok">OK</span><span class="body">recovered · 4m 37s · 200 OK</span></div>
                <div class="pulse-alert-line"><span class="pulse-alert-time">14:06:48</span><span class="pulse-alert-tag send">SEND</span><span>→ resolution to all subscribers</span></div>
            </div>
        </div>
    </section>

    {{-- TESTS — write once, run as a monitor --}}
    <section class="pulse-section" id="tests">
        <div class="pulse-section-head">
            <div>
                <div class="pulse-section-eyebrow">04 · Tests</div>
                <h2 class="pulse-section-title">Write the test once. <em>Run it as a monitor.</em></h2>
            </div>
            <p class="pulse-section-sub">
                Build a real user flow on a visual canvas — no code. Then drop it into a monitor and let headless Chromium replay it on schedule.
            </p>
        </div>
        <div class="pulse-tests">
            <div class="pulse-test-window">
                <div class="pulse-test-bar">
                    <span class="dot" style="background:#ff5f57;"></span>
                    <span class="dot" style="background:#febc2e;"></span>
                    <span class="dot" style="background:#28c840;"></span>
                    <span class="pulse-test-url">test · checkout-flow · used by 3 monitors · ✓ passed 2m ago</span>
                </div>
                <div class="pulse-test-steps">
                    @foreach([
                        ['n'=>'01','label'=>'Visit','detail'=>'https://shop.example.com','time'=>'128ms','state'=>''],
                        ['n'=>'02','label'=>'Type','detail'=>'email · alex@example.com','time'=>'85ms','state'=>''],
                        ['n'=>'03','label'=>'Press','detail'=>'Sign in','time'=>'372ms','state'=>''],
                        ['n'=>'04','label'=>'Wait for text','detail'=>'"Welcome back"','time'=>'1.2s','state'=>''],
                        ['n'=>'05','label'=>'Click','detail'=>'a[href="/cart"]','time'=>'94ms','state'=>'active'],
                        ['n'=>'06','label'=>'Assert','detail'=>'2 items in cart','time'=>'—','state'=>'pending'],
                        ['n'=>'07','label'=>'Screenshot','detail'=>'cart-state.png','time'=>'—','state'=>'pending'],
                    ] as $s)
                        <div class="pulse-test-step {{ $s['state'] }}">
                            <span class="pulse-test-step-num">{{ $s['n'] }}</span>
                            <span><span class="pulse-test-step-label">{{ $s['label'] }}</span><span class="pulse-test-step-detail">{{ $s['detail'] }}</span></span>
                            <span class="pulse-test-step-time">{{ $s['time'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="pulse-test-uses">
                    <span class="lab">used by</span>
                    @foreach(['mon · checkout-eu', 'mon · checkout-us', 'mon · checkout-ap'] as $m)
                        <span class="chip"><span class="dot"></span>{{ $m }}</span>
                    @endforeach
                </div>
            </div>
            <div class="pulse-tests-bullets">
                <div class="pulse-bullet">
                    <h4>Visual step builder</h4>
                    <p>Click together a real user flow on an intuitive canvas. Visit, type, click, wait, assert. No code.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Reuse across monitors</h4>
                    <p>One test, many monitors. Run checkout-flow from EU, US and AP — three monitors, one definition. Edit once, all three update.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Screenshots on failure</h4>
                    <p>When a step fails, Uppi captures the page state, HTML snapshot and console log. You see exactly what the user saw.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Same alerts as the rest</h4>
                    <p>Test failures route through the same channels as your HTTP and cron checks. One incident model. One on-call.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- SERVERS (light, normal block) --}}
    <section class="pulse-section" id="servers">
        <div class="pulse-section-head">
            <div>
                <div class="pulse-section-eyebrow">05 · Servers</div>
                <h2 class="pulse-section-title">One agent. <em>Full visibility.</em></h2>
            </div>
            <p class="pulse-section-sub">
                A small Go binary. Drops onto any Linux box and streams CPU, memory, disk and network — with the same threshold alerts as everything else.
            </p>
        </div>

        <div class="pulse-servers-grid">
            <div class="pulse-server-card">
                <div class="pulse-server-head">
                    <div>
                        <div class="pulse-server-name">web-prod-01 · eu-west</div>
                        <div class="pulse-server-state">nominal</div>
                    </div>
                    <span class="pulse-server-tag"><span class="d"></span> healthy · 47d</span>
                </div>

                <div class="pulse-server-metrics">
                    @php
                        $metrics = [
                            ['label' => 'CPU',     'value' => '27%',         'sub' => '8 cores',         'color' => '#E5392E', 'trend' => 'up'],
                            ['label' => 'MEMORY',  'value' => '6.2 / 8 GB',  'sub' => '77.5%',           'color' => '#f59e0b', 'trend' => 'down'],
                            ['label' => 'DISK',    'value' => '78%',         'sub' => '/ — 312 GB',      'color' => '#f59e0b', 'trend' => 'down'],
                            ['label' => 'NETWORK', 'value' => 'eth0',        'sub' => '12.4 MB/s ↑ 3.1 ↓','color' => '#1F8A5B', 'trend' => 'up'],
                        ];
                    @endphp
                    @foreach($metrics as $m)
                        <div class="pulse-metric">
                            <div class="pulse-metric-head">
                                <span class="pulse-metric-label">{{ $m['label'] }}</span>
                                <svg width="120" height="40" viewBox="0 0 120 40" aria-hidden="true">
                                    <path d="{{ $sparkline($m['trend']) }}" fill="none" stroke="{{ $m['color'] }}" stroke-width="1.5"/>
                                </svg>
                            </div>
                            <div class="pulse-metric-value">{{ $m['value'] }}</div>
                            <div class="pulse-metric-sub">{{ $m['sub'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="pulse-install"><span class="pr">$ </span>curl -sSL get.uppi.dev | sh</div>
            </div>

            <div class="pulse-server-bullets">
                <div class="pulse-bullet">
                    <h4>One-line install</h4>
                    <p>curl-piped Go binary. No Docker, no Python, no agent fleet.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Threshold alerts</h4>
                    <p>Set custom levels per metric. Get notified the moment something crosses your line.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Audit the source</h4>
                    <p>The whole agent is on GitHub. Read it, fork it, ship it. No black box on your servers.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ERRORS — exception tracking --}}
    <section class="pulse-section" id="errors">
        <div class="pulse-section-head">
            <div>
                <div class="pulse-section-eyebrow">06 · Errors</div>{{-- order: 01 Coverage · 02 Response · 03 Alerts · 04 Tests · 05 Servers · 06 Errors · 07 Pricing --}}
                <h2 class="pulse-section-title">When something throws, <em>you read the stack.</em></h2>
            </div>
            <p class="pulse-section-sub">
                Drop the SDK in. Catch every uncaught exception with full stack traces, breadcrumbs, release tags and user context.
            </p>
        </div>

        <div class="pulse-err-grid">
            <div class="pulse-err-card">
                <div class="pulse-err-head">
                    <div>
                        <span class="pulse-err-tag">● unresolved · prod</span>
                        <h3 class="pulse-err-title">TypeError: <b>Cannot read properties of undefined (reading &lsquo;price&rsquo;)</b></h3>
                        <p class="pulse-err-msg">at <span class="pulse-mono">CartSummary.computeTotal</span> · checkout/cart.tsx:84</p>
                    </div>
                    <div class="pulse-err-spark">
                        <svg width="120" height="40" viewBox="0 0 120 40" aria-hidden="true">
                            <path d="{{ $sparkline('down') }}" fill="none" stroke="#E5392E" stroke-width="1.5"/>
                        </svg>
                        <div class="pulse-err-spark-num">214</div>
                        <div class="pulse-err-spark-lbl">events · 24h</div>
                    </div>
                </div>
                <div class="pulse-err-meta">
                    <div class="pulse-err-meta-cell">
                        <div class="pulse-err-meta-lbl">first seen</div>
                        <div class="pulse-err-meta-val">2h 14m ago</div>
                    </div>
                    <div class="pulse-err-meta-cell">
                        <div class="pulse-err-meta-lbl">last seen</div>
                        <div class="pulse-err-meta-val">just now</div>
                    </div>
                    <div class="pulse-err-meta-cell">
                        <div class="pulse-err-meta-lbl">users affected</div>
                        <div class="pulse-err-meta-val">38</div>
                    </div>
                    <div class="pulse-err-meta-cell">
                        <div class="pulse-err-meta-lbl">release</div>
                        <div class="pulse-err-meta-val red">v2.4.1-rc3</div>
                    </div>
                </div>
                <div class="pulse-err-stack">
                    <div class="pulse-err-stack-line"><span class="ln">82</span><span><span class="kw">const</span> <span class="fn">computeTotal</span> = (items) =&gt; {</span></div>
                    <div class="pulse-err-stack-line"><span class="ln">83</span><span>&nbsp;&nbsp;<span class="cm">// sum line items + tax</span></span></div>
                    <div class="pulse-err-stack-line frame"><span class="ln">84</span><span>&nbsp;&nbsp;<span class="kw2">return</span> items.reduce((a, i) =&gt; a + <b>i.price</b> * i.qty, 0);</span></div>
                    <div class="pulse-err-stack-line"><span class="ln">85</span><span>};</span></div>
                    <div class="pulse-err-stack-line"><span class="ln">86</span><span></span></div>
                    <div class="pulse-err-stack-line"><span class="ln">87</span><span><span class="kw">function</span> <span class="fn">CartSummary</span>({ cartId }) {</span></div>
                    <div class="pulse-err-stack-line"><span class="ln">88</span><span>&nbsp;&nbsp;<span class="kw">const</span> items = useCart(cartId)<b>?.items</b>;</span></div>
                </div>
                <div class="pulse-err-bread">
                    <h5>Breadcrumbs · last 6 events before crash</h5>
                    <div class="pulse-err-bread-row"><span class="t">14:02:08</span><span class="tag nav">nav</span><span>GET /checkout/cart</span></div>
                    <div class="pulse-err-bread-row"><span class="t">14:02:09</span><span class="tag click">click</span><span>button[data-id=&quot;apply-promo&quot;]</span></div>
                    <div class="pulse-err-bread-row"><span class="t">14:02:09</span><span class="tag http">http</span><span>POST /api/promo · 200 OK</span></div>
                    <div class="pulse-err-bread-row"><span class="t">14:02:10</span><span class="tag http">http</span><span>GET /api/cart/8821 · 200 OK</span></div>
                    <div class="pulse-err-bread-row"><span class="t">14:02:11</span><span class="tag click">click</span><span>a[href=&quot;/checkout/review&quot;]</span></div>
                    <div class="pulse-err-bread-row err"><span class="t">14:02:11</span><span class="tag err">err</span><span>TypeError · cart.tsx:84</span></div>
                </div>
            </div>

            <div class="pulse-tests-bullets">
                <div class="pulse-bullet">
                    <h4>Full stack traces</h4>
                    <p>Sourcemapped to your original code. Click any frame to jump to the line. Local variables and breadcrumbs included.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Smart grouping</h4>
                    <p>Identical exceptions collapse into one issue with a frequency curve. New errors stand out. Resolved ones stay resolved — until they regress.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Release tagging</h4>
                    <p>Every event tagged with the release that produced it. See the spike start at v2.4.1, find the commit, ship the fix.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Web · server · mobile</h4>
                    <p>JavaScript, Node, Python, Ruby, PHP, Go. One SDK per stack, one inbox for everything. Same routing, same on-call.</p>
                </div>
                <div class="pulse-bullet">
                    <h4>Privacy aware</h4>
                    <p>Scrub PII before send. Configurable allowlist for headers and request bodies. Hosted in the EU, GDPR by default.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- STATS BAR --}}
    <div class="pulse-stats-bar">
        <div class="pulse-stat-cell">
            <div class="pulse-stat-cell-num">{{ $abbr($totalChecks) }}</div>
            <div class="pulse-stat-cell-label">CHECKS RUN</div>
        </div>
        <div class="pulse-stat-cell">
            <div class="pulse-stat-cell-num">{{ $abbr($totalMonitors) }}</div>
            <div class="pulse-stat-cell-label">MONITORS WATCHED</div>
        </div>
        <div class="pulse-stat-cell">
            <div class="pulse-stat-cell-num">{{ $abbr($totalAlerts) }}</div>
            <div class="pulse-stat-cell-label">ALERTS DELIVERED</div>
        </div>
        <div class="pulse-stat-cell">
            <div class="pulse-stat-cell-num">60<span style="color:var(--red);">s</span></div>
            <div class="pulse-stat-cell-label">MIN INTERVAL</div>
        </div>
    </div>

    {{-- PRICING --}}
    <section class="pulse-section" id="pricing">
        <div class="pulse-section-head">
            <div>
                <div class="pulse-section-eyebrow">07 · Pricing</div>
                <h2 class="pulse-section-title">Free forever. <em>Or yours forever.</em></h2>
            </div>
            <p class="pulse-section-sub">
                Hosted is free up to a fair limit, no card and no clock. Or self-host the whole thing — same software, your perimeter, no telemetry.
            </p>
        </div>
        <div class="pulse-pricing">
            <div class="pulse-plan">
                <span class="pulse-plan-name">Hosted · free</span>
                <div class="pulse-plan-price">€0<small>/forever</small></div>
                <p class="pulse-plan-tag">Run by us. No card. No clock. No surprise migration to a paid tier.</p>
                <ul class="pulse-plan-features">
                    <li>Up to 25 monitors</li>
                    <li>1-minute interval</li>
                    <li>All monitor types</li>
                    <li>All alert channels</li>
                    <li>Public status pages</li>
                    <li>30-day history</li>
                </ul>
                <a class="pulse-plan-cta dark" href="{{ $dashboardUrl }}">Start free →</a>
            </div>
            <div class="pulse-plan featured">
                <span class="pulse-plan-name">Self-host · open source</span>
                <div class="pulse-plan-price">€0<small>/your servers</small></div>
                <p class="pulse-plan-tag">Your servers, your data, your runtime. Same software, just inside your perimeter — forever.</p>
                <ul class="pulse-plan-features">
                    <li>Unlimited monitors</li>
                    <li>1-minute interval</li>
                    <li>Full source on GitHub</li>
                    <li>Docker · Kubernetes · bare metal</li>
                    <li>Community support</li>
                    <li>No telemetry, no phone-home</li>
                </ul>
                <a class="pulse-plan-cta light" href="https://github.com/janyksteenbeek/uppi/blob/main/README.md">Read the docs →</a>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="pulse-foot">
        <div class="pulse-foot-head">
            <a href="/" class="pulse-foot-mark" aria-label="Uppi"><img src="{{ asset('logo.svg') }}" alt="Uppi"></a>
            <div class="pulse-foot-status">
                <span class="pulse-pill"><span class="pulse-dot"></span> All systems normal</span>
                <span class="sep">·</span>
                <span>1-minute checks</span>
                <span class="sep">·</span>
                <span>EU hosted</span>
            </div>
        </div>
        <div class="pulse-foot-cols">
            <div class="pulse-foot-col">
                <h4>About</h4>
                <p>Open-source uptime, browser tests and exception tracking — built to watch your services so you don&rsquo;t have to.</p>
            </div>
            <div class="pulse-foot-col">
                <h4>Product</h4>
                <a href="#coverage">Monitors</a>
                <a href="#tests">Tests</a>
                <a href="#errors">Errors</a>
                <a href="#servers">Servers</a>
                <a href="#pricing">Pricing</a>
                <a href="https://apps.apple.com/app/uppi/id6739699410">iOS app</a>
                <a href="https://play.google.com/store/apps/details?id=dev.uppi.app">Android app</a>
            </div>
            <div class="pulse-foot-col">
                <h4>Resources</h4>
                <a href="https://github.com/janyksteenbeek/uppi/blob/main/README.md">Docs</a>
                <a href="https://github.com/janyksteenbeek/uppi">GitHub</a>
                <a href="https://github.com/sponsors/janyksteenbeek">Sponsor</a>
                <a href="https://github.com/janyksteenbeek/uppi/issues">Roadmap</a>
                <a href="https://github.com/janyksteenbeek/uppi#contributing">Contribute</a>
            </div>
            <div class="pulse-foot-col">
                <h4>Company</h4>
                <a href="{{ url('privacy') }}">Privacy</a>
                <a href="https://www.webmethod.nl/juridisch/algemene-voorwaarden">Terms</a>
                <a href="https://www.webmethod.nl/juridisch/coordinated-vulnerability-disclosure">VDP</a>
                <a href="https://x.com/janyksteenbeek">𝕏</a>
            </div>
        </div>
        <div class="pulse-foot-end">
            <span>© {{ date('Y') }} Webmethod · KVK 63314061 · BTW NL002401656B67</span>
            <span>source available · CC-BY-NC</span>
        </div>
    </footer>
</div>
</body>
</html>
