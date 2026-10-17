<x-mail::message>
# Invoice {{ $invoice->number }}

Hello {{ $invoice->client->name }},

{{ $invoice->company->name }} sent you an invoice for **{{ $invoice->currency }} {{ number_format($invoice->total / 100, 2) }}**, due on **{{ $invoice->due_date->format('F j, Y') }}**. The PDF is attached.

<x-mail::button :url="$payUrl">
View and pay online
</x-mail::button>

Thank you,<br>
{{ $invoice->company->name }}
</x-mail::message>
