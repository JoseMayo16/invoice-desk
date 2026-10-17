<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $company = $this->invoice->company;

        return new Envelope(
            replyTo: $company->email ? [new Address($company->email, $company->name)] : [],
            subject: "Invoice {$this->invoice->number} from {$company->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-sent',
            with: ['payUrl' => rtrim(config('app.frontend_url'), '/')."/pay/{$this->invoice->public_token}"],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => InvoicePdf::render($this->invoice), InvoicePdf::filename($this->invoice))
                ->withMime('application/pdf'),
        ];
    }
}
