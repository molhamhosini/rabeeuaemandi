<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-28 14:00', 'Asia/Dubai'));
        config(['otp.driver' => 'log', 'otp.test_code' => '123456']);
    }

    private function signIn(string $phone = '050 123 4567'): array
    {
        $this->postJson('/api/v1/auth/otp', ['phone' => $phone])->assertOk()->assertJson(['sent' => true, 'phone' => '+971501234567']);
        return $this->postJson('/api/v1/auth/verify', ['phone' => $phone, 'code' => '123456'])->assertOk()->json();
    }

    public function test_phone_numbers_are_normalized_to_uae_mobiles(): void
    {
        foreach (['0501234567', '050 123 4567', '+971 50 123 4567', '00971501234567', '501234567'] as $p) {
            $this->assertSame('971501234567', Customer::normalizePhone($p), $p);
        }
        foreach (['026410888', '12345', '+966501234567', ''] as $p) {
            $this->assertNull(Customer::normalizePhone($p), $p);
        }
        $this->postJson('/api/v1/auth/otp', ['phone' => '02 641 0888'])->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_sign_in_with_code_creates_account_and_token(): void
    {
        $res = $this->signIn();
        $this->assertTrue($res['is_new']);
        $this->assertSame('050 123 4567', $res['customer']['phone']);
        $this->assertSame(1, Customer::count());

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$res['token']])->assertOk()->assertJson(['phone' => '050 123 4567']);
        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer wrong'])->assertStatus(401);

        // signing in again later finds the same account
        $this->travel(2)->minutes();
        $again = $this->signIn('+971501234567');
        $this->assertFalse($again['is_new']);
        $this->assertSame(1, Customer::count());
    }

    public function test_wrong_code_is_refused_and_attempts_are_limited(): void
    {
        $this->postJson('/api/v1/auth/otp', ['phone' => '0501234567'])->assertOk();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/verify', ['phone' => '0501234567', 'code' => '000000'])->assertStatus(422)->assertJsonValidationErrors('code');
        }
        // even the right code is refused after too many wrong tries
        $this->postJson('/api/v1/auth/verify', ['phone' => '0501234567', 'code' => '123456'])->assertStatus(422);
        $this->assertSame(0, Customer::count());
    }

    public function test_code_cannot_be_requested_again_within_a_minute(): void
    {
        $this->postJson('/api/v1/auth/otp', ['phone' => '0501234567'])->assertOk();
        $this->postJson('/api/v1/auth/otp', ['phone' => '0501234567'])->assertStatus(422)->assertJsonValidationErrors('phone');
        $this->travel(61)->seconds();
        $this->postJson('/api/v1/auth/otp', ['phone' => '0501234567'])->assertOk();
    }

    public function test_code_is_used_once_and_expires(): void
    {
        $this->postJson('/api/v1/auth/otp', ['phone' => '0501234567'])->assertOk();
        $this->travel(6)->minutes();
        $this->postJson('/api/v1/auth/verify', ['phone' => '0501234567', 'code' => '123456'])->assertStatus(422);

        $this->signIn();
        $this->postJson('/api/v1/auth/verify', ['phone' => '0501234567', 'code' => '123456'])->assertStatus(422);
    }

    public function test_signed_in_order_goes_to_account_and_saves_details(): void
    {
        $token = $this->signIn()['token'];
        $h = ['Authorization' => 'Bearer '.$token];
        $lamb = Item::where('name_en', 'Mandi Lamb')->first();
        $branch = Branch::first();

        $this->postJson('/api/v1/orders', [
            'branch_id' => $branch->id, 'customer_name' => 'Ahmad', 'customer_phone' => '0501234567',
            'fulfillment' => 'delivery', 'address' => 'Villa 7, Al Shamkha', 'payment_method' => 'whatsapp',
            'items' => [['item_id' => $lamb->id, 'quantity' => 1]],
        ], $h)->assertCreated();

        $me = $this->getJson('/api/v1/me', $h)->assertOk()->json();
        $this->assertSame('Ahmad', $me['name']);
        $this->assertSame('Villa 7, Al Shamkha', $me['address']);
        $this->assertSame($branch->id, $me['branch_id']);
        $this->assertSame(1, $me['orders_count']);
        $this->getJson('/api/v1/me/orders', $h)->assertOk()->assertJsonCount(1);

        // a guest order still works and is not linked to anyone
        $this->postJson('/api/v1/orders', [
            'branch_id' => $branch->id, 'customer_name' => 'Guest', 'customer_phone' => '0509999999',
            'fulfillment' => 'pickup', 'payment_method' => 'whatsapp',
            'items' => [['item_id' => $lamb->id, 'quantity' => 1]],
        ])->assertCreated();
        $this->getJson('/api/v1/me/orders', $h)->assertJsonCount(1);
    }

    public function test_profile_update_logout_and_delete(): void
    {
        $token = $this->signIn()['token'];
        $h = ['Authorization' => 'Bearer '.$token];

        $this->patchJson('/api/v1/me', ['name' => 'Sara', 'address' => 'Al Danah'], $h)->assertOk()->assertJson(['name' => 'Sara', 'address' => 'Al Danah']);

        $this->postJson('/api/v1/auth/logout', [], $h)->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', $h)->assertStatus(401);

        $this->travel(2)->minutes();
        $h = ['Authorization' => 'Bearer '.$this->signIn()['token']];
        $this->deleteJson('/api/v1/me', [], $h)->assertOk();
        $this->assertSame(0, Customer::count());
        $this->getJson('/api/v1/me', $h)->assertStatus(401);
    }

    public function test_msg91_driver_sends_and_verifies_through_their_api(): void
    {
        config(['otp.driver' => 'msg91', 'otp.msg91.auth_key' => 'k', 'otp.msg91.template_id' => 't']);
        Http::fake([
            'control.msg91.com/api/v5/otp/verify*' => Http::response(['type' => 'success', 'message' => 'OTP verified success']),
            'control.msg91.com/api/v5/otp*' => Http::response(['type' => 'success', 'request_id' => 'x']),
        ]);

        $this->postJson('/api/v1/auth/otp', ['phone' => '0501234567'])->assertOk();
        $this->postJson('/api/v1/auth/verify', ['phone' => '0501234567', 'code' => '4321'])->assertOk()->assertJsonStructure(['token']);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'template_id=t') && str_contains($r->url(), 'mobile=971501234567') && $r->hasHeader('authkey', 'k'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/otp/verify') && str_contains($r->url(), 'otp=4321'));
    }

    public function test_msg91_failure_shows_a_friendly_error(): void
    {
        config(['otp.driver' => 'msg91']);
        Http::fake(['control.msg91.com/*' => Http::response(['type' => 'error', 'message' => 'invalid authkey'], 401)]);
        $this->postJson('/api/v1/auth/otp', ['phone' => '0501234567'])->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_map_pin_goes_with_the_order_and_is_saved_on_the_account(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->signIn()['token']];
        $lamb = Item::where('name_en', 'Mandi Lamb')->first();
        $body = [
            'branch_id' => Branch::first()->id, 'customer_name' => 'Ahmad', 'customer_phone' => '0501234567',
            'fulfillment' => 'delivery', 'address' => 'Villa 7', 'payment_method' => 'whatsapp',
            'lat' => 24.401234, 'lng' => 54.712345,
            'items' => [['item_id' => $lamb->id, 'quantity' => 1]],
        ];
        $res = $this->postJson('/api/v1/orders', $body, $h)->assertCreated()->json();
        $this->assertSame(['lat' => 24.401234, 'lng' => 54.712345], $res['order']['location']);
        $this->assertStringContainsString(rawurlencode('https://maps.google.com/?q=24.401234,54.712345'), $res['redirect']);
        $this->assertSame(['lat' => 24.401234, 'lng' => 54.712345], $this->getJson('/api/v1/me', $h)->json('location'));

        // a pin outside the UAE is refused
        $this->postJson('/api/v1/orders', ['lat' => 40.0, 'lng' => 54.7] + $body, $h)->assertStatus(422)->assertJsonValidationErrors('lat');
    }

    public function test_areas_belong_to_branches_and_pick_the_branch(): void
    {
        $branches = collect($this->getJson('/api/v1/branches')->json());
        $danah = $branches->firstWhere('slug', 'al-danah');
        $shahamah = $branches->firstWhere('slug', 'new-shahamah');
        $this->assertContains('الخالدية', array_column(array_column($danah['areas'], 'name'), 'ar'));
        $this->assertContains('الشهامة', array_column(array_column($shahamah['areas'], 'name'), 'ar'));
        $this->assertNotNull($danah['location']);

        $khalidiyah = \App\Models\Area::where('name_ar', 'الخالدية')->first();
        $lamb = Item::where('name_en', 'Mandi Lamb')->first();
        $body = [
            'customer_name' => 'A', 'customer_phone' => '0501234567', 'fulfillment' => 'delivery', 'address' => 'Villa 1',
            'payment_method' => 'whatsapp', 'area_id' => $khalidiyah->id, 'items' => [['item_id' => $lamb->id, 'quantity' => 1]],
        ];
        // Al Khalidiyah is in the city, so New Shahamah cannot take it
        $this->postJson('/api/v1/orders', $body + ['branch_id' => $shahamah['id']])->assertStatus(422)->assertJsonValidationErrors('area_id');
        $res = $this->postJson('/api/v1/orders', $body + ['branch_id' => $danah['id']])->assertCreated()->json();
        $this->assertSame('الخالدية', $res['order']['area']['name']['ar']);
        $this->assertStringContainsString(rawurlencode('توصيل إلى: الخالدية - Villa 1'), $res['redirect']);
    }

    public function test_admin_edits_delivery_areas(): void
    {
        $admin = \App\Models\User::where('is_admin', true)->first() ?? \App\Models\User::factory()->create(['is_admin' => true]);
        $branch = Branch::where('slug', 'new-shahamah')->first();
        $old = $branch->areas()->where('name_ar', 'الوثبة')->first();
        $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
            'name_ar' => $branch->name_ar, 'name_en' => $branch->name_en, 'whatsapp' => $branch->whatsapp,
            'delivery_fee' => 0, 'min_order' => 0, 'is_active' => 1, 'lat' => '24.53', 'lng' => '54.67',
            'areas' => "الشهامة | Al Shahamah\nالرحبة\nمدينة الرياض | Riyadh City",
        ])->assertRedirect();
        $active = $branch->areas()->where('is_active', true)->pluck('name_en', 'name_ar')->all();
        $this->assertSame(['الشهامة' => 'Al Shahamah', 'الرحبة' => 'الرحبة', 'مدينة الرياض' => 'Riyadh City'], $active);
        $this->assertFalse($old->fresh()->is_active);
        $this->get(route('admin.branches'))->assertOk()->assertSee('مدينة الرياض');
    }
}
