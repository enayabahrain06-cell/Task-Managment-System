<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
        .card { background: #fff; border-radius: 20px; border: 1px solid #E5E7EB; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 48px 40px; max-width: 440px; width: 100%; text-align: center; }
        .icon-wrap { width: 72px; height: 72px; background: #FEF3C7; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 32px; }
        h1 { font-size: 22px; font-weight: 700; color: #111827; margin-bottom: 10px; }
        p  { font-size: 14px; color: #6B7280; line-height: 1.6; }
        .badge { display: inline-flex; align-items: center; gap: 6px; margin-top: 28px; padding: 8px 18px; background: #FEF3C7; color: #92400E; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .dot { width: 7px; height: 7px; background: #F59E0B; border-radius: 50%; animation: pulse 1.4s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{ opacity:1; transform:scale(1); } 50%{ opacity:.5; transform:scale(.85); } }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">🔧</div>
        <h1>Under Maintenance</h1>
        <p>We're performing scheduled maintenance to improve your experience. The system will be back online shortly.</p>
        <div class="badge">
            <span class="dot"></span>
            In progress — please check back soon
        </div>
    </div>
</body>
</html>
