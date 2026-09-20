<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Summons — {{ $record->case_number }}</title>
    <style>
        /* Note: do NOT reset margin on body/html — dompdf derives the page's
           actual margin from body's own margin, seeded by @page below. Any
           rule that touches body's margin (even via a universal * selector)
           silently overrides @page and collapses the margin to 0. */
        p, div, table, td, tr, img { margin: 0; padding: 0; box-sizing: border-box; }

        @page { size: letter; margin: 0.5in 0.75in; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
        }

        .paper {
            width: 100%;
            position: relative;
        }
        .paper.first { page-break-after: always; }

        .copy-tag {
            position: absolute;
            top: 0.1in;
            right: 0.1in;
            font-family: Arial, sans-serif;
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 3px 10px;
            border: 1.5px solid #111;
            border-radius: 4px;
        }

        .doc-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 6px;
        }
        .logo-wrap img { width: 70px; height: 70px; object-fit: contain; }
        .header-text { flex: 1; text-align: center; }
        .header-text .republic { font-size: 10.5pt; line-height: 1.4; margin-bottom: 4px; }
        .header-text .brgy-name { font-size: 18pt; font-style: italic; font-weight: bold; }

        .header-divider { border: none; border-top: 2px solid #111; margin: 4px 0 12px; }

        .office-title { text-align: center; font-size: 11pt; font-weight: bold; letter-spacing: 1px; margin-bottom: 6px; }
        .doc-title { text-align: center; font-size: 20pt; font-weight: bold; letter-spacing: 3px; margin-bottom: 6px; }
        .case-no { text-align: center; font-size: 11pt; margin-bottom: 12px; }

        .body-text { font-size: 11.5pt; line-height: 1.5; text-align: justify; margin-bottom: 6px; }
        .body-text .indent { display: inline-block; width: 40px; }
        .underline-field {
            display: inline-block;
            border-bottom: 1.5px solid #111;
            min-width: 240px;
            font-weight: bold;
            text-align: center;
        }

        .details-table { width: 100%; margin: 10px 0; font-size: 11.5pt; border-collapse: collapse; }
        .details-table td { padding: 2px 6px; vertical-align: top; }
        .details-table td:first-child { width: 160px; font-weight: bold; }

        .narrative-box {
            border: 1px solid #999;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 11pt;
            line-height: 1.5;
            margin: 8px 0 12px;
            background: #fafafa;
        }

        .sig-section { margin-top: 14px; display: flex; justify-content: flex-end; }
        .sig-block { text-align: center; min-width: 220px; }
        .sig-img-wrap {
            height: 70px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            overflow: hidden;
        }
        .sig-img { width: 140px; height: auto; }
        .sig-name {
            font-weight: bold;
            font-size: 12pt;
            letter-spacing: 1px;
            border-top: 1.5px solid #111;
            padding-top: 4px;
            margin-top: 6px;
        }
        .sig-title { font-size: 10.5pt; }

        .footer-note {
            margin-top: 10px;
            font-size: 9.5pt;
            color: #444;
            font-style: italic;
        }
    </style>
</head>
<body>

@php
    $captainSignature = \App\Models\Setting::get('captain_signature_url');
    $captainName      = \App\Models\Setting::get('captain_name', 'HON. PUNONG BARANGAY');
    $issuedAt = now();

    $copies = [
        [
            'tag'       => 'COMPLAINANT\'S COPY',
            'name'      => $record->complainant_name,
            'address'   => $record->complainant_address,
            'body'      => "You are hereby summoned to appear before the Office of the Barangay Captain / Lupong Tagapamayapa of Barangay Caranas, Motiong, Samar, on the date and time stated below, in connection with the case you filed against " . strtoupper($record->respondent_name) . '.',
            'reason'    => 'to attend the conciliation/mediation proceedings on the complaint you filed, and to present your side of the matter',
        ],
        [
            'tag'       => "RESPONDENT'S COPY",
            'name'      => $record->respondent_name,
            'address'   => $record->respondent_address,
            'body'      => "You are hereby summoned to appear before the Office of the Barangay Captain / Lupong Tagapamayapa of Barangay Caranas, Motiong, Samar, on the date and time stated below, to answer the complaint filed against you by " . strtoupper($record->complainant_name) . '.',
            'reason'    => 'to answer the complaint filed against you and to present your side of the matter',
        ],
    ];

    $categoryLine = $record->incident_type === 'Others' && $record->incident_type_other
        ? $record->incident_type_other
        : $record->incident_type;
@endphp

@foreach($copies as $i => $copy)
<div class="paper {{ $i === 0 ? 'first' : '' }}">
    <div class="copy-tag">{{ $copy['tag'] }}</div>

    <div class="doc-header">
        <div class="logo-wrap">
            <img src="{{ public_path('images/logo.png') }}" alt="Brgy. Caranas Seal">
        </div>
        <div class="header-text">
            <div class="republic">
                Republic of the Philippines<br>
                Province of Samar<br>
                Municipality of Motiong
            </div>
            <div class="brgy-name">Barangay Caranas</div>
        </div>
        <div style="width:70px;"></div>
    </div>

    <hr class="header-divider">

    <div class="office-title">OFFICE OF THE BARANGAY CAPTAIN</div>
    <div class="doc-title">SUMMONS</div>
    <div class="case-no">Barangay Case No. <strong>{{ $record->case_number }}</strong></div>

    <p class="body-text">
        <span class="indent"></span>TO: <span class="underline-field">&nbsp;{{ strtoupper($copy['name']) }}&nbsp;</span>
    </p>
    @if($copy['address'])
    <p class="body-text"><span class="indent"></span>{{ $copy['address'] }}</p>
    @endif

    <p class="body-text" style="margin-top:14px;">
        <span class="indent"></span>{{ $copy['body'] }}
    </p>

    <table class="details-table">
        <tr>
            <td>Nature of Complaint:</td>
            <td>{{ $categoryLine }}</td>
        </tr>
        <tr>
            <td>Date of Hearing:</td>
            <td>{{ $record->hearing_date->format('F j, Y (l)') }}</td>
        </tr>
        <tr>
            <td>Time of Hearing:</td>
            <td>{{ \Carbon\Carbon::parse($record->hearing_time)->format('g:i A') }}</td>
        </tr>
        <tr>
            <td>Venue:</td>
            <td>Barangay Hall, Barangay Caranas, Motiong, Samar</td>
        </tr>
    </table>

    <p class="body-text">
        <span class="indent"></span>Please come prepared {{ $copy['reason'] }}. A summary of the complaint on file is provided below for your reference:
    </p>

    <div class="narrative-box">{{ $record->narrative }}</div>

    <p class="body-text">
        <span class="indent"></span>This Summons is issued pursuant to the Katarungang Pambarangay Law under Chapter 7, Title I, Book III of the Local Government Code of 1991 (Republic Act No. 7160).
    </p>

    <p class="body-text">
        <span class="indent"></span>You are hereby directed to appear on the date, time, and place specified above. Failure or refusal to appear without justifiable reason may result in appropriate action in accordance with applicable laws and regulations governing Katarungang Pambarangay proceedings.
    </p>

    <p class="body-text">
        <span class="indent"></span>Issued this {{ $issuedAt->format('jS') }} day of {{ $issuedAt->format('F') }} {{ $issuedAt->format('Y') }} at Barangay Caranas, Municipality of Motiong, Province of Samar, Philippines.
    </p>

    <div class="sig-section">
        <div class="sig-block">
            @if($captainSignature)
            <div class="sig-img-wrap">
                <img class="sig-img" src="{{ $captainSignature }}" alt="Signature">
            </div>
            @endif
            <div class="sig-name">{{ $captainName }}</div>
            <div class="sig-title">Barangay Captain / Lupon Chairman</div>
        </div>
    </div>

    <p class="footer-note">This is a system-generated summons based on records filed with Barangay Caranas.</p>
</div>
@endforeach

</body>
</html>
