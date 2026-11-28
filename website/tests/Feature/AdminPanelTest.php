<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-28 14:00', 'Asia/Dubai'));
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function admin(): User
    {
        return User::where('is_admin', true)->first();
    }

    private function order(array $extra = []): array
    {
        return array_merge([
            'branch_id' => Branch::first()->id, 'customer_name' => 'Test', 'customer_phone' => '0501234567',
            'fulfillment' => 'pickup', 'payment_method' => 'whatsapp',
            'items' => [['item_id' => Item::where('name_en', 'Mandi Lamb')->first()->id, 'quantity' => 1]],
        ], $extra);
    }

    public function test_every_admin_page_renders_with_orders(): void
    {
        $this->postJson('/api/v1/orders', $this->order())->assertCreated();
        $o = Order::first();
        $this->actingAs($this->admin());
        foreach (['/admin', '/admin/board', '/admin/board?partial=1', '/admin/orders', '/admin/orders?tab=new&q=050', '/admin/settings', '/admin/menu', '/admin/branches',
            "/admin/orders/{$o->code}", "/admin/orders/{$o->code}/ticket", "/admin/orders/{$o->code}/ticket?copy=kitchen"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get("/admin/orders/{$o->code}/ticket")->assertSee('#'.$o->number);
        $poll = $this->getJson('/admin/poll')->assertOk()->json();
        $this->assertSame(1, $poll['new']);
        $this->assertSame($o->number, $poll['orders'][0]['number']);
    }

    public function test_accept_from_the_window_and_pause_orders(): void
    {
        $this->postJson('/api/v1/orders', $this->order())->assertCreated();
        $o = Order::first();
        $this->actingAs($this->admin())->patchJson("/admin/orders/{$o->code}", ['status' => 'confirmed'])->assertOk()->assertJson(['status' => 'confirmed']);

        $this->post('/admin/pause', ['paused' => 1])->assertRedirect();
        $this->assertFalse($this->getJson('/api/v1/config')->json('taking_orders'));
        $this->postJson('/api/v1/orders', $this->order())->assertStatus(422);
        $this->post('/admin/pause', ['paused' => 0]);
        $this->postJson('/api/v1/orders', $this->order())->assertCreated();
    }

    public function test_settings_change_hours_notice_and_payments(): void
    {
        $banquets = Category::where('slug', 'banquets')->first();
        $this->actingAs($this->admin())->put('/admin/settings', [
            'open' => '13:00', 'last' => '22:00', 'meal_minutes' => 45, 'book_days' => 10,
            'lead' => [$banquets->id => 5], 'pay' => ['cash' => 1], 'paper' => 58, 'receipt_footer' => 'أهلًا',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cfg = $this->getJson('/api/v1/config')->json();
        $this->assertSame('13:00', $cfg['hours']['open']);
        $this->assertSame(['cash'], $cfg['payment_methods']);
        $this->assertSame(5, (int) $banquets->fresh()->lead_hours);

        // whatsapp is now off; cash goes straight to the restaurant with no link to open
        $this->postJson('/api/v1/orders', $this->order())->assertStatus(422);
        $res = $this->postJson('/api/v1/orders', $this->order(['payment_method' => 'cash']))->assertCreated()->json();
        $this->assertNull($res['redirect']);

        // 23:00 is past the new last time
        $this->postJson('/api/v1/orders', $this->order(['payment_method' => 'cash', 'scheduled_at' => '2026-11-28T22:30']))->assertStatus(422);

        // at least one payment method must stay on
        $this->put('/admin/settings', ['open' => '12:00', 'last' => '23:30', 'meal_minutes' => 30, 'book_days' => 30, 'paper' => 80, 'pay' => []])
            ->assertSessionHasErrors('pay');
    }

    public function test_dish_only_at_one_branch(): void
    {
        [$danah, $shamkha] = Branch::orderBy('sort')->get()->all();
        $item = Item::where('name_en', 'Mandi Lamb')->first();
        $this->actingAs($this->admin())->put("/admin/items/{$item->id}", [
            'category_id' => $item->category_id, 'name_ar' => $item->name_ar, 'name_en' => $item->name_en,
            'price' => 73, 'is_active' => 1, 'branches' => [$danah->id],
        ])->assertRedirect();
        $this->assertSame([$danah->id], $item->fresh()->branchList());

        $this->postJson('/api/v1/orders', $this->order(['branch_id' => $shamkha->id]))->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson('/api/v1/orders', $this->order(['branch_id' => $danah->id]))->assertCreated();
        $api = collect($this->getJson('/api/v1/menu')->json())->flatMap(fn ($c) => $c['items'])->firstWhere('id', $item->id);
        $this->assertSame([$danah->id], $api['branch_ids']);
        $this->get('/menu')->assertSee('data-branches="'.$danah->id.'"', false);

        // ticking every branch means everywhere again
        $this->put("/admin/items/{$item->id}", [
            'category_id' => $item->category_id, 'name_ar' => $item->name_ar, 'name_en' => $item->name_en,
            'price' => 73, 'is_active' => 1, 'branches' => [$danah->id, $shamkha->id],
        ]);
        $this->assertNull($item->fresh()->branch_ids);
    }
}
