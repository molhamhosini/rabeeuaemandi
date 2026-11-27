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

    public function test_banquet_needs_rice_and_serving_and_rejects_foreign_addons(): void
    {
        $banquet = Item::where('name_en', 'Mandi')->first();
        $lamb = Item::where('name_en', 'Mandi Lamb')->first();
        $groups = $banquet->addonGroups()->with('addons')->get();
        $rice = $groups->firstWhere('name_en', 'Rice type')->addons;
        $serving = $groups->firstWhere('name_en', 'Serving')->addons;
        $order = fn (array $ids) => $this->postJson('/api/v1/orders', [
            'branch_id' => Branch::first()->id, 'customer_name' => 'T', 'customer_phone' => '0501234567',
            'fulfillment' => 'pickup', 'payment_method' => 'whatsapp',
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
