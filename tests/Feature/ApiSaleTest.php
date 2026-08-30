<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use App\Support\Vat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_shop_device_token(): void
    {
        $user = User::factory()->create([
            'email' => 'shop@stua.vu',
            'password' => 'stua-demo',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'shop@stua.vu',
            'password' => 'stua-demo',
            'device' => 'till-1',
        ])->assertOk()
            ->assertJsonPath('token_type', 'shop_device')
            ->assertJsonPath('shop.id', $user->shop_id)
            ->assertJsonPath('shop.name', $user->shop->name)
            ->assertJsonStructure(['token', 'token_type', 'shop' => ['id', 'name', 'location']]);
    }

    public function test_records_inclusive_sale_and_sms_receipt(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
            'client_sale_id' => 'dev03-sale-142',
            'lines' => [
                ['qty' => 1, 'unit_price_vuv' => 2000],
                ['qty' => 1, 'unit_price_vuv' => 800],
                ['qty' => 1, 'unit_price_vuv' => 650],
            ],
            'tender' => 'cash',
            'tendered_vuv' => 4000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('client_sale_id', 'dev03-sale-142')
            ->assertJsonPath('total', 3450)
            ->assertJsonPath('vat_vuv', 450)
            ->assertJsonPath('vat', 450)
            ->assertJsonPath('subtotal', 3000)
            ->assertJsonPath('net', 3000)
            ->assertJsonPath('change_vuv', 550)
            ->assertJsonPath('receipt_no', 'INV-0001');

        $text = $response->json('receipt_text');
        $this->assertStringContainsString("TOTAL 3450 VUV\nVAT included 450", $text);
        $this->assertStringContainsString('Kas 4000', $text);
        $this->assertStringContainsString('Senis 550', $text);
        $this->assertMatchesRegularExpression('/^STUA #1\n2000\n800\n650\n/', $text);
    }

    public function test_inclusive_demo_2400_150_680_is_not_exclusive_3715(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
            'client_sale_id' => 'demo-3230',
            'lines' => [
                ['qty' => 1, 'unit_price_vuv' => 2400],
                ['qty' => 1, 'unit_price_vuv' => 150],
                ['qty' => 1, 'unit_price_vuv' => 680],
            ],
            'tender' => 'cash',
            'tendered_vuv' => 4000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('total', 3230)
            ->assertJsonPath('vat_vuv', 421)
            ->assertJsonPath('net', 2809)
            ->assertJsonPath('change_vuv', 770);

        $text = $response->json('receipt_text');
        $this->assertStringContainsString("2400\n150\n680\nTOTAL 3230 VUV\nVAT included 421", $text);
        $this->assertStringNotContainsString('3715', $text);
        $this->assertStringNotContainsString('485', $text);
    }

    public function test_cash_requires_tendered_amount(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
            'client_sale_id' => 'cash-no-tendered',
            'lines' => [['qty' => 1, 'unit_price_vuv' => 1000]],
            'tender' => 'cash',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['tendered_vuv']);
    }

    public function test_client_sale_id_is_idempotent(): void
    {
        $user = User::factory()->create();
        $payload = [
            'client_sale_id' => 'offline-1',
            'lines' => [['qty' => 1, 'unit_price_vuv' => 3230]],
            'tender' => 'card',
        ];

        $first = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', $payload);
        $first->assertCreated()->assertJsonPath('total', 3230)->assertJsonPath('vat_vuv', 421);

        $replay = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', $payload);
        $replay->assertOk()
            ->assertJsonPath('id', $first->json('id'))
            ->assertJsonPath('receipt_no', $first->json('receipt_no'));

        $this->assertSame(1, Sale::query()->count());
    }

    public function test_daily_list_includes_day_totals_and_tender_split(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
            'client_sale_id' => 'card-186400',
            'lines' => [['qty' => 1, 'unit_price_vuv' => 186400]],
            'tender' => 'card',
        ])->assertCreated();

        $vat = Vat::extract(186400);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonPath('vat_summary.count', 1)
            ->assertJsonPath('vat_summary.total', 186400)
            ->assertJsonPath('vat_summary.vat', $vat)
            ->assertJsonPath('vat_summary.subtotal', 186400 - $vat)
            ->assertJsonPath('day.gross', 186400)
            ->assertJsonPath('day.vat', $vat)
            ->assertJsonPath('day.net', 186400 - $vat)
            ->assertJsonPath('day.tenders.card', 186400)
            ->assertJsonPath('day.tenders.cash', 0)
            ->assertJsonPath('day.tenders.mvatu', 0)
            ->assertJsonPath('day.tenders.mycash', 0);
    }

    public function test_sms_stub_returns_body_and_does_not_send(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $created = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
            'client_sale_id' => 'sms-1',
            'lines' => [['qty' => 1, 'unit_price_vuv' => 115]],
            'tender' => 'mvatu',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sales/'.$created->json('id').'/sms-stub', ['phone' => '+678123456'])
            ->assertOk()
            ->assertExactJson([
                'to' => '+678123456',
                'body' => $created->json('receipt_text'),
            ]);

        Http::assertNothingSent();
    }

    public function test_receipt_pdf_is_available(): void
    {
        $user = User::factory()->create();
        $saleId = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
            'client_sale_id' => 'pdf-1',
            'lines' => [['qty' => 1, 'unit_price_vuv' => 115]],
            'tender' => 'mycash',
        ])->json('id');

        $this->actingAs($user, 'sanctum')
            ->get('/api/v1/sales/'.$saleId.'/receipt.pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
