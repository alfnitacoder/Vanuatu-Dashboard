<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_shop_can_log_in_and_see_end_of_day(): void
    {
        $user = User::factory()->create([
            'email' => 'shop@stua.vu',
            'password' => 'stua-demo',
        ]);

        $this->post('/login', [
            'email' => 'shop@stua.vu',
            'password' => 'stua-demo',
        ])->assertRedirect('/');

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('End of day / En blong dei')
            ->assertSee('Summary / Sumari')
            ->assertSee('Gross VAT Inclusive')
            ->assertSee('Tender summary / Samari blong pemen')
            ->assertSee('Today’s log / Log blong tude')
            ->assertSee('Close day / Klosem dei');
    }

    public function test_web_records_sale_with_inclusive_vat(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/sales', [
            'lines' => [
                ['name' => 'Rice', 'qty' => 1, 'unit_price_vuv' => 2000],
                ['qty' => 1, 'unit_price_vuv' => 800],
                ['qty' => 1, 'unit_price_vuv' => 650],
            ],
            'tender' => 'cash',
            'tendered_vuv' => 4000,
        ])->assertRedirect();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('INV-0001')
            ->assertSee('3,450 VUV')
            ->assertSee('450');
    }
}
