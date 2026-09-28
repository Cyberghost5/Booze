<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the consumer dashboard with previous orders & profile settings.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $orders = Order::with(['items.product', 'vendor'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $deliveryLocations = $user->deliveryLocations()->latest()->get();

        return Inertia::render('Consumer/Dashboard', [
            'orders' => $orders,
            'deliveryLocations' => $deliveryLocations,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'default_address' => $user->default_address,
                'default_latitude' => $user->default_latitude,
                'default_longitude' => $user->default_longitude,
            ],
        ]);
    }

    /**
     * Update consumer profile information & default delivery address.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'default_address' => ['nullable', 'string', 'max:500'],
            'default_latitude' => ['nullable', 'numeric'],
            'default_longitude' => ['nullable', 'numeric'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'default_address' => $validated['default_address'] ?? null,
            'default_latitude' => $validated['default_latitude'] ?? null,
            'default_longitude' => $validated['default_longitude'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->back()->with('success', 'Profile and default delivery address updated successfully!');
    }
}
