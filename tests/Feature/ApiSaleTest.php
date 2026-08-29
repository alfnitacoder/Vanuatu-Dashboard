<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Vat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_shop(): void
    {
        $user = User::factory()->create([
            'email' => 'shop@stua.vu',
            'password' => 'stua-demo',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'shop@stua.vu',
            'password' => 'stua-demo',
        ])->assertOk()
            ->assertJsonPath('shop.id', $user->shop_id)
            ->assertJsonPath('shop.name', $user->shop->name)
            ->assertJsonStructure(['token', 'shop' => ['id', 'name', 'location']]);
    }

    public function test_records_inclusive_sale_and_sms_receipt(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
            'lines' => [
                ['qty' => 1, 'unit_price_vuv' => 2000],
                ['qty' => 1, 'unit_price_vuv' => 800],
                ['qty' => 1, 'unit_price_vuv' => 650],
            ],
            'tender' => 'cash',
            'tendered_vuv' => 4000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('total', 3450)
            ->assertJsonPath('vat', 450)
            ->assertJsonPath('subtotal', 3000)
            ->assertJsonPath('receipt_no', 'INV-0001');

        $text = $response->json('receipt_text');
        $this->assertStringContainsString("TOTAL 3450 VUV\nVAT incl. 450", $text);
        $this->assertStringContainsString('Kas 4000', $text);
        $this->assertStringContainsString('Senis 550', $text);
        $this->assertMatchesRegularExpression('/^STUA #1\n2000\n800\n650\n/', $text);
    }

    public function test_daily_list_uses_extract_from_gross(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
            'lines' => [['qty' => 1, 'unit_price_vuv' => 186400]],
            'tender' => 'card',
        ])->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonPath('vat_summary.count', 1)
            ->assertJsonPath('vat_summary.total', 186400)
            ->assertJsonPath('vat_summary.vat', Vat::extract(186400))
            ->assertJsonPath('vat_summary.subtotal', 186400 - Vat::extract(186400));
    }

    public function test_sms_stub_returns_body_and_does_not_send(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $created = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sales', [
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
            'lines' => [['qty' => 1, 'unit_price_vuv' => 115]],
            'tender' => 'mycash',
        ])->json('id');

        $this->actingAs($user, 'sanctum')
            ->get('/api/v1/sales/'.$saleId.'/receipt.pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
