@extends('documents.print.layout')

@section('document-body')
@php
    $resident  = $document->resident;
    $household = $resident?->household;
    $issuedAt  = $document->issued_at ?? now();
@endphp

<div class="office-title">OFFICE OF THE BARANGAY CAPTAIN</div>
<div class="doc-title">BUSINESS CLEARANCE</div>

<p class="body-text"><strong>TO WHOM IT MAY CONCERN:</strong></p>

<p class="body-text">
    <span class="indent"></span>
    This is to certify that
    <span class="underline-field">&nbsp;{{ strtoupper($resident?->full_name ?? $document->business_name) }}&nbsp;</span>,
    of legal age, {{ $resident?->civil_status ?? '' }}, Filipino citizen, and a
    resident of
    @if($household)
        {{ $household->full_address }},
    @endif
    Barangay Caranas, Motiong, Samar, is personally known to this office.
</p>

<p class="body-text">
    <span class="indent"></span>
    That said person is the owner/operator of
    <span class="underline-field">&nbsp;{{ strtoupper($document->business_name ?? '') }}&nbsp;</span>,
    a
    <span class="underline-med">&nbsp;{{ $document->business_type ?? '' }}&nbsp;</span>
    type of business located at
    <span class="underline-field">&nbsp;{{ $document->business_address ?? 'Barangay Caranas, Motiong, Samar' }}&nbsp;</span>.
</p>

<p class="body-text">
    <span class="indent"></span>
    This clearance is issued upon request for
    <strong>{{ $document->purpose }}</strong>
    and for whatever legal purpose it may serve.
</p>

<p class="body-text">
    <span class="indent"></span>
    ISSUED this
    <span class="underline-short">&nbsp;{{ $issuedAt->format('j') }}&nbsp;</span>
    day of
    <span class="underline-med">&nbsp;{{ $issuedAt->format('F') }}&nbsp;</span>,
    <span class="underline-short">&nbsp;{{ $issuedAt->format('Y') }}&nbsp;</span>
    at Barangay Caranas, Motiong, Samar.
</p>

<div class="sig-section">
    <div class="sig-block">
        <div class="sig-name">HON. PUNONG BARANGAY</div>
        <div class="sig-title">Barangay Captain</div>
    </div>
</div>

<div class="footer-fields">
    <table>
        <tr><td>O.R. No.</td><td><span class="field-line">&nbsp;{{ $document->or_number ?? '' }}&nbsp;</span></td></tr>
        <tr><td>Date Issued:</td><td><span class="field-line">&nbsp;{{ $issuedAt->format('F j, Y') }}&nbsp;</span></td></tr>
        <tr><td>Doc. Stamp:</td><td><span class="field-line">&nbsp;&nbsp;</span></td></tr>
    </table>
</div>

@endsection
