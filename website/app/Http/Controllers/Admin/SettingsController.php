<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;

/** Owner settings: ordering hours, notice per section, payment methods, receipt printer. */
class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings', [
            'hours' => [
                'open' => config('restaurant.open'), 'last' => config('restaurant.last'),
                'meal_minutes' => (int) config('restaurant.meal_minutes'), 'book_days' => (int) config('restaurant.book_days'),
            ],
            'categories' => Category::orderBy('sort')->get(),
            'pay' => [
                'cash' => (bool) Setting::get('pay.cash', false),
                'whatsapp' => (bool) Setting::get('pay.whatsapp', true),
                'card' => (bool) Setting::get('pay.card', true),
            ],
            'stripe' => filled(config('services.stripe.secret')),
            'receipt' => self::receipt(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'open' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'last' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'meal_minutes' => 'required|integer|min:0|max:240',
            'book_days' => 'required|integer|min:0|max:90',
            'lead' => 'array',
            'lead.*' => 'nullable|integer|min:0|max:72',
            'pay' => 'array',
            'paper' => 'required|in:80,58',
            'receipt_footer' => 'nullable|string|max:200',
            'auto_print' => 'nullable|boolean',
        ]);
        // choices go every 30 minutes, so the times must be on the hour or half hour
        foreach (['open', 'last'] as $k) {
            if ((int) substr($data[$k], 3) % 30 !== 0) {
                return back()->withErrors(['open' => __('admin.set.half_hour')])->withInput();
            }
        }
        if ($data['last'] <= $data['open']) {
            return back()->withErrors(['open' => __('admin.set.last_after_open')])->withInput();
        }
        $pay = collect(Setting::PAYMENTS)->mapWithKeys(fn ($m) => [$m => (bool) ($data['pay'][$m] ?? false)]);
        if (! $pay->contains(true)) {
            return back()->withErrors(['pay' => __('admin.set.one_payment')])->withInput();
        }

        foreach (Setting::HOURS as $k) Setting::put('hours.'.$k, $data[$k]);
        foreach ($pay as $m => $on) Setting::put('pay.'.$m, $on);
        foreach ($data['lead'] ?? [] as $id => $h) {
            Category::whereKey($id)->update(['lead_hours' => (int) $h]);
        }
        Setting::put('receipt.paper', (int) $data['paper']);
        Setting::put('receipt.footer', (string) ($data['receipt_footer'] ?? ''));
        Setting::put('receipt.auto_print', $request->boolean('auto_print'));

        return back()->with('ok', __('site.admin.saved'));
    }

    /** Receipt printer settings: paper width in mm, a line at the bottom, print when accepting. */
    public static function receipt(): array
    {
        return [
            'paper' => (int) Setting::get('receipt.paper', 80),
            'footer' => (string) Setting::get('receipt.footer', ''),
            'auto_print' => (bool) Setting::get('receipt.auto_print', false),
        ];
    }
}
