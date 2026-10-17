<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use App\Services\InvoiceWriter;
use App\Services\PaymentRecorder;
use Illuminate\Database\Seeder;

/**
 * A believable demo account: one studio, a dozen clients and a year of
 * invoices in every state. Every name and address is invented.
 *
 *   owner@demo.test / password   (can delete)
 *   member@demo.test / password  (cannot delete)
 */
class DatabaseSeeder extends Seeder
{
    private const SERVICES = [
        ['Website design', 2400, 4800],
        ['Landing page', 900, 1600],
        ['Brand identity package', 1800, 3500],
        ['Monthly maintenance', 350, 600],
        ['SEO audit', 450, 900],
        ['Copywriting (per page)', 120, 220],
        ['Development hours', 65, 95],
        ['Hosting (annual)', 180, 360],
        ['Photography session', 400, 850],
        ['Email campaign setup', 300, 700],
    ];

    public function run(InvoiceWriter $writer, PaymentRecorder $recorder): void
    {
        mt_srand(2026);
        fake()->seed(2026);

        $company = Company::create([
            'name' => 'Lumen & Pine Studio',
            'email' => 'billing@lumenpine.test',
            'address' => "Carrera 43A #7-50, Oficina 1204\nMedellín, Colombia",
            'tax_id' => '901.555.210-4',
            'currency' => 'USD',
            'invoice_prefix' => 'LP',
        ]);
        User::factory()->for($company)->create(['name' => 'Demo Owner', 'email' => 'owner@demo.test', 'role' => Role::Owner]);
        User::factory()->for($company)->create(['name' => 'Demo Member', 'email' => 'member@demo.test', 'role' => Role::Member]);

        $clients = Client::factory()->count(12)->for($company)->create();

        // 70 invoices over the last year, 18 of them from the last month so the
        // demo shows drafts, open and overdue invoices, not only paid ones.
        for ($i = 0; $i < 70; $i++) {
            $issued = today()->subDays($i < 18 ? mt_rand(0, 30) : mt_rand(31, 360));
            $lines = [];
            foreach ((array) array_rand(self::SERVICES, mt_rand(1, 4)) as $key) {
                [$name, $min, $max] = self::SERVICES[$key];
                $lines[] = [
                    'description' => $name,
                    'quantity' => str_contains($name, 'hours') ? mt_rand(8, 60) : mt_rand(1, 3),
                    'unit_price' => mt_rand($min, $max),
                    'tax_rate' => [0, 0, 8.5, 19][mt_rand(0, 3)],
                ];
            }

            $invoice = $writer->create($company, [
                'client_id' => $clients->random()->id,
                'issue_date' => $issued,
                'due_date' => $issued->copy()->addDays([15, 30, 30, 45][mt_rand(0, 3)]),
                'notes' => 'Payment by bank transfer or card. Thank you for your business.',
            ], $lines);
            $invoice->forceFill(['created_at' => $issued, 'updated_at' => $issued]);

            $age = $issued->diffInDays(today());
            if ($age < 10 && mt_rand(1, 100) <= 60) {
                $invoice->save(); // recent drafts

                continue;
            }

            // Mid-morning, so the timestamps fall on the issue day in any American or European timezone.
            $invoice->forceFill(['status' => InvoiceStatus::Sent, 'sent_at' => $issued->copy()->setTime(15, 0)])->save();

            // Older invoices are mostly paid; some recent ones are still open or overdue.
            $paidChance = $age > 90 ? 90 : ($age > 45 ? 65 : 20);
            if (mt_rand(1, 100) <= $paidChance) {
                $paidAt = $issued->copy()->addDays(mt_rand(3, 40))->setTime(mt_rand(13, 22), mt_rand(0, 59))->min(now());
                $recorder->record($invoice, $invoice->total, [PaymentMethod::BankTransfer, PaymentMethod::Card][mt_rand(0, 1)], $paidAt);
            }
        }
    }
}
