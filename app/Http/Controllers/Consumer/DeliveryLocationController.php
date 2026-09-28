<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryLocationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'landmark' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $isDefault = $validated['is_default'] ?? false;

        if ($isDefault || $user->deliveryLocations()->count() === 0) {
            $user->deliveryLocations()->update(['is_default' => false]);
            $isDefault = true;
        }

        $location = $user->deliveryLocations()->create([
            'label' => $validated['label'],
            'address' => $validated['address'],
            'landmark' => $validated['landmark'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_default' => $isDefault,
        ]);

        if ($isDefault) {
            $user->update([
                'default_address' => $location->address,
                'default_latitude' => $location->latitude,
                'default_longitude' => $location->longitude,
            ]);
        }

        return redirect()->back()->with('success', "Saved location '{$location->label}' successfully!");
    }

    public function destroy(Request $request, DeliveryLocation $deliveryLocation): RedirectResponse
    {
        if ($deliveryLocation->user_id !== $request->user()->id) {
            abort(403);
        }

        $label = $deliveryLocation->label;
        $wasDefault = $deliveryLocation->is_default;
        $deliveryLocation->delete();

        $user = $request->user();

        if ($wasDefault && $user->deliveryLocations()->count() > 0) {
            $newDefault = $user->deliveryLocations()->first();
            $newDefault->update(['is_default' => true]);
            $user->update([
                'default_address' => $newDefault->address,
                'default_latitude' => $newDefault->latitude,
                'default_longitude' => $newDefault->longitude,
            ]);
        }

        return redirect()->back()->with('success', "Location '{$label}' removed.");
    }

    public function setDefault(Request $request, DeliveryLocation $deliveryLocation): RedirectResponse
    {
        if ($deliveryLocation->user_id !== $request->user()->id) {
            abort(403);
        }

        $user = $request->user();
        $user->deliveryLocations()->update(['is_default' => false]);
        $deliveryLocation->update(['is_default' => true]);

        $user->update([
            'default_address' => $deliveryLocation->address,
            'default_latitude' => $deliveryLocation->latitude,
            'default_longitude' => $deliveryLocation->longitude,
        ]);

        return redirect()->back()->with('success', "'{$deliveryLocation->label}' set as default delivery address.");
    }
}
