<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->document_type }} — {{ $document->resident?->full_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Times+New+Roman:ital,wght@0,400;0,700;1,400;1,700&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13pt;
            background: #e5e7eb;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 20px;
            min-height: 100vh;
        }

        .no-print {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 13px;
            font-family: sans-serif;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-print  { background: #1a4731; color: #fff; }
        .btn-print:hover { background: #2d6a4f; }
        .btn-back   { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .btn-back:hover { background: #f9fafb; }

        /* ── PAPER ── */
        .paper {
            width: 8.5in;
            min-height: 11in;
            background: #fff;
            border: 3px double #111;
            padding: 0.75in 0.85in;
            position: relative;
            box-shadow: 0 4px 24px rgba(0,0,0,.18);
        }

        /* ── HEADER ── */
        .doc-header {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 10px;
        }
        .logo-wrap img {
            width: 90px;
            height: 90px;
            object-fit: contain;
        }
        .header-text {
            flex: 1;
            text-align: center;
        }
        .header-text .republic {
            font-size: 11pt;
            line-height: 1.5;
            margin-bottom: 6px;
        }
        .header-text .brgy-name {
            font-family: 'Libre Baskerville', 'Times New Roman', serif;
            font-size: 28pt;
            font-style: italic;
            font-weight: 700;
            color: #1a3a6b;
            line-height: 1.1;
        }

        .header-divider {
            border: none;
            border-top: 2.5px solid #111;
            margin: 10px 0 20px;
        }

        /* ── BODY ── */
        .office-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .doc-title {
            text-align: center;
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        .body-text {
            font-size: 13pt;
            line-height: 2;
            text-align: justify;
            margin-bottom: 14px;
        }
        .body-text .indent { display: inline-block; width: 48px; }

        .underline-field {
            display: inline-block;
            border-bottom: 1.5px solid #111;
            min-width: 280px;
            line-height: 1.2;
            font-weight: bold;
            text-align: center;
        }
        .underline-short {
            display: inline-block;
            border-bottom: 1.5px solid #111;
            min-width: 40px;
            text-align: center;
        }
        .underline-med {
            display: inline-block;
            border-bottom: 1.5px solid #111;
            min-width: 160px;
            text-align: center;
        }

        /* ── SIGNATURE ── */
        .sig-section {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
        }
        .sig-block {
            text-align: center;
            min-width: 220px;
        }
        .sig-name {
            font-weight: bold;
            font-size: 13pt;
            letter-spacing: 1px;
            border-top: 1.5px solid #111;
            padding-top: 4px;
            margin-top: 40px;
        }
        .sig-title { font-size: 11pt; }

        /* ── FOOTER FIELDS ── */
        .footer-fields {
            margin-top: 36px;
            font-size: 11pt;
            line-height: 2.2;
        }
        .footer-fields table td:first-child { padding-right: 8px; }
        .footer-fields .field-line {
            display: inline-block;
            border-bottom: 1.5px solid #111;
            min-width: 180px;
        }

        /* ── PRINT ── */
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            .paper {
                width: 100%;
                min-height: auto;
                border: 3px double #111;
                box-shadow: none;
                padding: 0.6in 0.75in;
            }
            @page { size: letter; margin: 0.3in; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="{{ url()->previous() }}" class="btn btn-back">
        ← Back
    </a>
    <button onclick="window.print()" class="btn btn-print">
        🖨 Print Document
    </button>
</div>

<div class="paper">

    {{-- ── HEADER ── --}}
    <div class="doc-header">
        <div class="logo-wrap">
            <img src="{{ asset('images/logo.png') }}" alt="Brgy. Caranas Seal">
        </div>
        <div class="header-text">
            <div class="republic">
                Republic of the Philippines<br>
                Province of Samar<br>
                Municipality of Motiong
            </div>
            <div class="brgy-name">Barangay Caranas</div>
        </div>
        {{-- spacer to balance logo --}}
        <div style="width:90px;"></div>
    </div>

    <hr class="header-divider">

    @yield('document-body')

</div>


<script>
    window.onafterprint = function () {
        if (window.opener && !window.opener.closed) {
            window.opener.location.reload();
        }
        window.close();
    };
</script>
</body>
</html>
