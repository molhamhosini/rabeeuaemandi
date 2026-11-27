<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        // a fixed Saturday, 2 PM in Abu Dhabi: inside ordering hours (12:00 to 23:30)
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-28 14:00', 'Asia/Dubai'));
    }

    public function test_public_pages_render_in_both_languages(): void
    {
        foreach (['/', '/menu', '/branches', '/checkout'] as $url) {
            $this->get($url.'?lang=ar')->assertOk()->assertSee('ربيع الإمارات');
            $this->get($url.'?lang=en')->assertOk()->assertSee('Rabee Al Emarat');
        }
    }

    public function test_menu_api_returns_categories_items_and_options(): void
    {
        $res = $this->getJson('/api/v1/menu')->assertOk()->json();
        $this->assertCount(9, $res);
        $banquets = collect($res)->firstWhere('slug', 'banquets');
        $this->assertEquals([1570, 1570, 1225], array_column($banquets['items'][0]['options'], 'price'));
        $this->getJson('/api/v1/branches')->assertOk()->assertJsonCount(2);
    }

    public function test_whatsapp_order_uses_database_prices(): void
    {
        $lamb = Item::where('name_en', 'Mandi Lamb')->first();
        $banquet = Item::where('name_en', 'Mandi')->first();
        $goat = $banquet->options()->where('name_en', 'Goat (Tais)')->first();
        $addon = fn (Item $i, string $name) => $i->addonGroups()->with('addons')->get()->flatMap->addons->firstWhere('name_en', $name)->id;

        $res = $this->postJson('/api/v1/orders', [
            'branch_id' => Branch::first()->id,
            'customer_name' => 'Test',
            'customer_phone' => '0501234567',
            'fulfillment' => 'delivery',
            'address' => 'Villa 1',
            'payment_method' => 'whatsapp',
            'scheduled_at' => now('Asia/Dubai')->addHours(4)->format('Y-m-d\TH:i'),
            'items' => [
                ['item_id' => $lamb->id, 'quantity' => 2, 'price' => 1], // client price must be ignored
                ['item_id' => $banquet->id, 'option_id' => $goat->id, 'quantity' => 1, 'addon_ids' => [
                    $addon($banquet, 'Bukhari'), $addon($banquet, 'Zurbian'), $addon($banquet, 'Split over several plates'),
                    $addon($banquet, 'Extra yogurt'), $addon($banquet, 'Extra chutney'), $addon($banquet, 'Extra chutney'),
                ]],
            ],
        ])->assertCreated()->json();

        // two rice types (mixed); 1 yogurt + 2 chutney at 2 AED each are added to the banquet price
        $this->assertEquals(73 * 2 + 1225 + 2 + 2 * 2, $res['order']['total']);
        $this->assertStringContainsString(rawurlencode('نوع الرز: بخاري، زربيان'), $res['redirect']);
        $this->assertStringContainsString(rawurlencode('إضافات: شتني إضافي ×2، روب إضافي'), $res['redirect']);
        $this->assertStringStartsWith('https://wa.me/971581404800?text=', $res['redirect']);
        $this->assertStringContainsString(rawurlencode('ذبيحة مندي - تيس'), $res['redirect']);
        $this->assertStringContainsString(rawurlencode('الموعد: '), $res['redirect']);
        $this->assertNotNull($res['order']['scheduled_at']);
    }

    public function test_item_with_options_requires_an_option(): void
    {
        $banquet = Item::where('name_en', 'Mandi')->first();
        $this->postJson('/api/v1/orders', [
            'branch_id' => Branch::first()->id, 'customer_name' => 'T', 'customer_phone' => '0501234567',
            'fulfillment' => 'pickup', 'payment_method' => 'whatsapp',
            'items' => [['item_id' => $banquet->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_banquet_must_be_booked_three_hours_ahead(): void
    {
        // many orders in a row from one client would hit the order rate limit
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $banquet = Item::where('name_en', 'Mandi')->first();
        $groups = $banquet->addonGroups()->with('addons')->get();
        $ids = [$groups->firstWhere('name_en', 'Rice type')->addons[0]->id, $groups->firstWhere('name_en', 'Serving')->addons[0]->id];
        $order = fn (?string $at, ?Item $item = null) => $this->postJson('/api/v1/orders', array_filter([
            'branch_id' => Branch::first()->id, 'customer_name' => 'T', 'customer_phone' => '0501234567',
            'fulfillment' => 'pickup', 'payment_method' => 'whatsapp', 'scheduled_at' => $at,
            'items' => [$item
                ? ['item_id' => $item->id, 'quantity' => 1]
                : ['item_id' => $banquet->id, 'option_id' => $banquet->options()->value('id'), 'quantity' => 1, 'addon_ids' => $ids]],
        ]));
        // now is Saturday 28 November, 14:00 in Abu Dhabi
        $order(null)->assertStatus(422)->assertJsonValidationErrors('scheduled_at');            // banquet "as soon as possible"
        $order('2026-11-28T16:00')->assertStatus(422)->assertJsonValidationErrors('scheduled_at'); // only 2 hours ahead
        $order('2026-11-28T17:15')->assertStatus(422);                                          // not one of the choices
        $order('2026-11-29T09:00')->assertStatus(422);                                          // before opening
        $order('2027-01-20T13:00')->assertStatus(422);                                          // too far ahead
        $res = $order('2026-11-28T17:00')->assertCreated()->json();
        $this->assertSame('2026-11-28T13:00:00+00:00', $res['order']['scheduled_at']);
        $order('2026-12-02T20:30')->assertCreated();                                            // banquets can be booked days ahead
        $cook = Item::whereHas('category', fn ($q) => $q->where('slug', 'cooking-service'))->first();
        $order(null, $cook)->assertStatus(422);                                                 // cooking service needs notice too

        // ordinary meals: right away, or later the same day with at least 30 minutes
        $plate = Item::where('name_en', 'Mandi Lamb')->first();
        $this->assertNull($order(null, $plate)->assertCreated()->json('order.scheduled_at'));
        $order('2026-11-28T14:30', $plate)->assertCreated();
        $order('2026-11-28T13:30', $plate)->assertStatus(422);                                 // in the past
        $order('2026-11-29T14:00', $plate)->assertStatus(422);                                 // another day
        $order('2026-11-28T23:30', $plate)->assertCreated();                                   // last choice of the day
        $order('2026-11-28T23:45', $plate)->assertStatus(422);                                 // after hours

        // outside ordering hours there is no "as soon as possible"
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-28 23:50', 'Asia/Dubai'));
        $order(null, $plate)->assertStatus(422);
    }

    public function test_banquet_needs_rice_and_serving_and_rejects_foreign_addons(): void
    {
        $banquet = Item::where('name_en', 'Mandi')->first();
        $lamb = Item::where('name_en', 'Mandi Lamb')->first();
        $groups = $banquet->addonGroups()->with('addons')->get();
        $rice = $groups->firstWhere('name_en', 'Rice type')->addons;
        $serving = $groups->firstWhere('name_en', 'Serving')->addons;
        $order = fn (array $ids) => $this->postJson('/api/v1/orders', [
            'branch_id' => Branch::first()->id, 'customer_name' => 'T', 'customer_phone' => '0501234567',
            'fulfillment' => 'pickup', 'payment_method' => 'whatsapp', 'scheduled_at' => now('Asia/Dubai')->addHours(5)->format('Y-m-d\TH:i'),
            'items' => [['item_id' => $banquet->id, 'option_id' => $banquet->options()->value('id'), 'quantity' => 1, 'addon_ids' => $ids]],
        ]);

        $order([$serving[0]->id])->assertStatus(422);                                  // no rice type
        $order([$rice[0]->id, $serving[0]->id, $serving[1]->id])->assertStatus(422);   // two servings in a pick-one group
        $order([$rice[0]->id, $rice[0]->id, $serving[0]->id])->assertStatus(422);      // a free choice can't be counted twice
        $foreign = $lamb->addonGroups()->with('addons')->first()->addons[0]->id;
        $order([$rice[0]->id, $serving[0]->id, $foreign])->assertStatus(422);         // choice from another dish
        $order([$rice[0]->id, $serving[1]->id])->assertCreated();

        // rice type is optional on a lamb plate
        $this->postJson('/api/v1/orders', [
            'branch_id' => Branch::first()->id, 'customer_name' => 'T', 'customer_phone' => '0501234567',
            'fulfillment' => 'pickup', 'payment_method' => 'whatsapp',
            'items' => [['item_id' => $lamb->id, 'quantity' => 1]],
        ])->assertCreated();
    }

    public function test_card_order_creates_stripe_checkout_and_webhook_marks_paid(): void
    {
        config(['services.stripe.secret' => 'sk_test_x', 'services.stripe.webhook_secret' => 'whsec_x']);
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_123'])]);

        $res = $this->postJson('/checkout', [
            'branch_id' => Branch::first()->id, 'customer_name' => 'T', 'customer_phone' => '0501234567',
            'fulfillment' => 'pickup', 'payment_method' => 'card',
            'items' => [['item_id' => Item::where('name_en', 'Mandi Chicken')->value('id'), 'quantity' => 3]],
        ])->assertOk()->json();

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_123', $res['redirect']);
        Http::assertSent(fn ($r) => $r['line_items[0][price_data][unit_amount]'] == 3000 && $r['line_items[0][price_data][currency]'] === 'aed' && $r['line_items[0][quantity]'] == 3);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_test_123', 'payment_status' => 'paid']]]);
        $t = time();
        $sig = 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$payload, 'whsec_x');

        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 'bad', 'CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();

        $order = Order::where('code', $res['order'])->first();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);
    }

    public function test_card_payment_is_refused_when_stripe_is_not_configured(): void
    {
        config(['services.stripe.secret' => null]);
        $this->postJson('/api/v1/orders', [
            'branch_id' => Branch::first()->id, 'customer_name' => 'T', 'customer_phone' => '0501234567',
            'fulfillment' => 'pickup', 'payment_method' => 'card',
            'items' => [['item_id' => Item::first()->id, 'option_id' => Item::first()->options()->value('id'), 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_admin_area_requires_admin_and_can_edit_items(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $admin = User::where('is_admin', true)->first();
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/menu')->assertOk()->assertSee('لحم مندي');

        $item = Item::where('name_en', 'Mandi Lamb')->first();
        $this->actingAs($admin)->put("/admin/items/{$item->id}", [
            'category_id' => $item->category_id, 'name_ar' => 'لحم مندي', 'name_en' => 'Mandi Lamb', 'price' => 75, 'is_active' => 1,
        ])->assertRedirect('/admin/menu');
        $this->assertEquals(75, $item->fresh()->price);

        $normal = User::factory()->create();
        $this->actingAs($normal)->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_money_formatting(): void
    {
        $this->assertSame('1,570', OrderService::money(1570));
        $this->assertSame('7.50', OrderService::money(7.5));
    }
}
