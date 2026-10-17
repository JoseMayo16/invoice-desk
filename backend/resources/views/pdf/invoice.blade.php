@php
    $money = fn (int $cents) => $invoice->currency.' '.number_format($cents / 100, 2);
    $company = $invoice->company;
    $client = $invoice->client;
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoice->number }}</title>
<style>
    @page { margin: 36px 42px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1d1f24; }
    h1 { font-size: 22px; margin: 0; letter-spacing: 1px; color: #0f5d4a; }
    .muted { color: #6b6f78; }
    table { width: 100%; border-collapse: collapse; }
    .head td { vertical-align: top; }
    .meta td { padding: 2px 0; }
    .meta td:first-child { color: #6b6f78; padding-right: 14px; }
    .items { margin-top: 26px; }
    .items th { text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: .5px;
                color: #6b6f78; border-bottom: 1.5px solid #1d1f24; padding: 6px 4px; }
    .items td { padding: 7px 4px; border-bottom: 1px solid #e3e4e8; }
    .num, .items th.num { text-align: right; white-space: nowrap; }
    .totals { width: 46%; margin-left: 54%; margin-top: 14px; }
    .totals td { padding: 4px; }
    .totals .grand td { border-top: 1.5px solid #1d1f24; font-size: 13px; font-weight: bold; padding-top: 8px; }
    .status { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 9.5px;
              background: #e7f4ef; color: #0f5d4a; text-transform: uppercase; letter-spacing: .5px; }
    .notes { margin-top: 28px; padding-top: 10px; border-top: 1px solid #e3e4e8; white-space: pre-line; }
</style>
</head>
<body>
<table class="head">
    <tr>
        <td style="width: 55%">
            <strong style="font-size: 14px">{{ $company->name }}</strong><br>
            @if ($company->address)<span class="muted">{!! nl2br(e($company->address)) !!}</span><br>@endif
            @if ($company->email)<span class="muted">{{ $company->email }}</span><br>@endif
            @if ($company->tax_id)<span class="muted">Tax ID: {{ $company->tax_id }}</span>@endif
        </td>
        <td style="width: 45%; text-align: right">
            <h1>INVOICE</h1>
            <table class="meta" style="width: auto; margin-left: auto; margin-top: 8px">
                <tr><td>Number</td><td class="num">{{ $invoice->number }}</td></tr>
                <tr><td>Issued</td><td class="num">{{ $invoice->issue_date->format('M j, Y') }}</td></tr>
                <tr><td>Due</td><td class="num">{{ $invoice->due_date->format('M j, Y') }}</td></tr>
            </table>
            @if ($invoice->displayStatus() === 'paid')<div class="status" style="margin-top: 8px">Paid</div>@endif
        </td>
    </tr>
</table>

<div style="margin-top: 26px">
    <span class="muted" style="font-size: 9.5px; text-transform: uppercase; letter-spacing: .5px">Bill to</span><br>
    <strong>{{ $client->name }}</strong><br>
    @if ($client->address)<span class="muted">{!! nl2br(e($client->address)) !!}</span><br>@endif
    @if ($client->tax_id)<span class="muted">Tax ID: {{ $client->tax_id }}</span>@endif
</div>

<table class="items">
    <thead>
        <tr>
            <th style="width: 46%">Description</th>
            <th class="num">Qty</th>
            <th class="num">Unit price</th>
            <th class="num">Tax</th>
            <th class="num">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="num">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                <td class="num">{{ number_format($item->unit_price / 100, 2) }}</td>
                <td class="num">{{ rtrim(rtrim($item->tax_rate, '0'), '.') }}%</td>
                <td class="num">{{ number_format($item->line_subtotal / 100, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Subtotal</td><td class="num">{{ $money($invoice->subtotal) }}</td></tr>
    <tr><td>Tax</td><td class="num">{{ $money($invoice->tax_total) }}</td></tr>
    <tr class="grand"><td>Total</td><td class="num">{{ $money($invoice->total) }}</td></tr>
    @if ($invoice->amount_paid > 0)
        <tr><td class="muted">Paid</td><td class="num muted">{{ $money($invoice->amount_paid) }}</td></tr>
        <tr><td><strong>Balance due</strong></td><td class="num"><strong>{{ $money($invoice->balance()) }}</strong></td></tr>
    @endif
</table>

@if ($invoice->notes)
    <div class="notes">{{ $invoice->notes }}</div>
@endif
</body>
</html>
