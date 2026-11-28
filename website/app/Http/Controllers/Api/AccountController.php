<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** The signed-in customer: profile, saved address, past orders, deleting the account. */
class AccountController extends Controller
{
    public function show(Request $request)
    {
        return $request->attributes->get('customer')->toApi();
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|nullable|string|max:100',
            'address' => 'sometimes|nullable|string|max:300',
            'branch_id' => 'sometimes|nullable|exists:branches,id',
            'area_id' => 'sometimes|nullable|exists:areas,id',
            'lat' => 'sometimes|nullable|numeric|between:22,26.5|required_with:lng',
            'lng' => 'sometimes|nullable|numeric|between:51,56.5|required_with:lat',
        ]);
        $customer = $request->attributes->get('customer');
        $customer->update($data);
        return $customer->toApi();
    }

    public function orders(Request $request)
    {
        return $request->attributes->get('customer')->orders()->with('items', 'branch')->limit(50)->get()->map->toApi();
    }

    /**
     * App Store and Google Play rules: people must be able to delete their account in the app.
     * Their past orders stay for the restaurant's records but lose the link to the account.
     */
    public function destroy(Request $request)
    {
        $request->attributes->get('customer')->delete();
        return response()->json(['ok' => true]);
    }
}
