<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('pdf')]
class PdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_downloads_the_pdf(): void
    {
        $user = $this->owner();
        $invoice = $this->invoiceFor($user);

        $response = $this->actingAs($user)->get("/api/invoices/{$invoice->id}/pdf")->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_customer_downloads_the_pdf_from_the_public_link(): void
    {
        $invoice = $this->invoiceFor($this->owner());
        $invoice->forceFill(['status' => InvoiceStatus::Sent])->save();

        $this->get("/api/public/invoices/{$invoice->public_token}/pdf")->assertOk()
            ->assertHeader('Content-Disposition', "attachment; filename=\"{$invoice->number}.pdf\"");
    }

    public function test_email_renders_with_the_pdf_attached(): void
    {
        $invoice = $this->invoiceFor($this->owner());
        $mail = new InvoiceSent($invoice);

        $mail->assertSeeInHtml($invoice->number);
        $mail->assertSeeInHtml("/pay/{$invoice->public_token}");
        // The PDF embeds its creation time, so compare what it is, not its exact bytes.
        [$attachment] = $mail->attachments();
        $this->assertSame("{$invoice->number}.pdf", $attachment->as);
        $this->assertSame('application/pdf', $attachment->mime);
        $pdf = $attachment->attachWith(fn () => null, fn ($data) => $data());
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
