<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<meta name="theme-color" content="#2A42F7">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, #2a42f7 0%, #1a2d99 100%);
        min-height: 100vh; padding: 40px 20px; color: #212529; line-height: 1.6;
    }
    .wrap { max-width: 760px; margin: 0 auto; }
    .card {
        background: #fff; border-radius: 16px; padding: 36px 34px;
        box-shadow: 0 20px 40px rgba(0,0,0,.12); margin-bottom: 20px;
    }
    h1 { font-size: 1.7rem; margin-bottom: 8px; letter-spacing: -.02em; }
    h2 { font-size: 1.05rem; margin-bottom: 14px; }
    .sub { color: #6c757d; margin-bottom: 26px; }
    label { display: block; font-weight: 600; font-size: .9rem; margin-bottom: 6px; }
    input[type=url], input[type=email] {
        width: 100%; padding: 12px 14px; border: 2px solid #e6e8eb; border-radius: 8px;
        font-size: .95rem; font-family: inherit; margin-bottom: 16px;
    }
    input:focus { outline: none; border-color: #4285f4; }
    .hint { font-size: .82rem; color: #6c757d; margin: -10px 0 18px; }
    .btn {
        display: inline-block; background: #2a42f7; color: #fff; border: none;
        padding: 13px 26px; border-radius: 8px; font-size: .95rem; font-weight: 600;
        cursor: pointer; text-decoration: none; font-family: inherit;
    }
    .btn:hover { background: #1a2d99; }
    .btn[disabled] { background: #adb5bd; cursor: not-allowed; }
    .btn-quiet { background: #fff; color: #dc3545; border: 1px solid #f1aeb5; padding: 7px 14px; font-size: .84rem; }
    .btn-quiet:hover { background: #fff5f5; }
    .alert { padding: 13px 16px; border-radius: 8px; margin-bottom: 20px; font-size: .92rem; }
    .alert-success { background: #d4edda; color: #155724; }
    .alert-error { background: #f8d7da; color: #721c24; }
    .alert-warning { background: #fff3cd; color: #856404; }
    .share { border: 1px solid #e9ecef; border-radius: 10px; padding: 16px 18px; margin-bottom: 12px; }
    .share-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap; }
    .share strong { font-size: .97rem; }
    .meta { font-size: .84rem; color: #6c757d; }
    .pill { display: inline-block; font-size: .74rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; text-transform: uppercase; letter-spacing: .04em; }
    .pill-live { background: #e7f5ec; color: #1a7f37; }
    .pill-used { background: #eef1f4; color: #57606a; }
    .pill-dead { background: #fbe9e7; color: #b3261e; }
    .copy { display: flex; gap: 8px; margin-top: 12px; }
    .copy input { flex: 1; margin: 0; font-size: .8rem; background: #f8f9fa; padding: 9px 11px; border-radius: 6px; border: 1px solid #e9ecef; font-family: ui-monospace, Menlo, monospace; }
    .copy button { padding: 9px 16px; font-size: .84rem; }
    .allowance { background: #f8f9fa; border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; font-size: .92rem; }
    .back { color: #fff; opacity: .9; text-decoration: none; font-size: .9rem; display: inline-block; margin-bottom: 18px; }
    .back:hover { opacity: 1; }
    .facts { list-style: none; margin: 20px 0 26px; }
    .facts li { padding: 9px 0; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; gap: 16px; font-size: .94rem; }
    .facts li:last-child { border-bottom: none; }
    .facts span:first-child { color: #6c757d; }
    .facts span:last-child { font-weight: 600; text-align: right; }
</style>
