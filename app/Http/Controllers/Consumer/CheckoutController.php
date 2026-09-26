<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->back()->withErrors([
                'auth' => 'Please log in or register before placing your order.',
            ]);
        }

        // Single Active Order Guard: Prevent users from placing multiple orders at the same time
        $existingActiveOrder = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'packed', 'out_for_delivery'])
            ->latest()
            ->first();

        if ($existingActiveOrder) {
            return redirect()->back()->withErrors([
                'active_order' => "You already have an active order (#{$existingActiveOrder->order_number}) in progress. You cannot place another order until your current order is delivered or cancelled.",
                'active_order_id' => $existingActiveOrder->id,
            ]);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string|max:500',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'dob' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'save_as_default_address' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Age Verification check (18+)
        $dob = Carbon::parse($validated['dob']);
        if ($dob->age < 18) {
            return redirect()->back()->withErrors([
                'dob' => 'Underage access denied. You must be at least 18 years old to order alcoholic beverages.',
            ]);
        }

        // Validate stock levels & calculate subtotal
        $subtotal = 0;
        $orderItemsData = [];
        $vendorId = null;

        foreach ($validated['items'] as $cartItem) {
            $product = Product::findOrFail($cartItem['product_id']);

            if ($product->stock_level < $cartItem['quantity']) {
                return redirect()->back()->withErrors([
                    'cart' => "Sorry, '{$product->name}' only has {$product->stock_level} unit(s) remaining in stock.",
                ]);
            }

            if (! $vendorId && $product->vendor_id) {
                $vendorId = $product->vendor_id;
            }

            $itemSubtotal = $product->selling_price * $cartItem['quantity'];
            $subtotal += $itemSubtotal;

            $orderItemsData[] = [
                'product' => $product,
                'quantity' => $cartItem['quantity'],
                'unit_price' => $product->selling_price,
                'subtotal' => $itemSubtotal,
            ];
        }

        $deliveryFee = 500.00; // Fixed community delivery fee
        $total = $subtotal + $deliveryFee;

        $order = Order::create([
            'user_id' => $user->id,
            'vendor_id' => $vendorId,
            'order_number' => 'BZ-' . strtoupper(Str::random(8)),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'delivery_address' => $validated['delivery_address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Save default address to user profile if requested or if user has no default address yet
        if (!empty($validated['save_as_default_address']) || empty($user->default_address)) {
            $user->update([
                'default_address' => $validated['delivery_address'],
                'default_latitude' => $validated['latitude'],
                'default_longitude' => $validated['longitude'],
            ]);
        }

        // Create Order Items & Decrement Stock
        foreach ($orderItemsData as $itemData) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $itemData['product']->id,
                'product_name' => $itemData['product']->name,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'subtotal' => $itemData['subtotal'],
            ]);

            // Decrement Stock
            $itemData['product']->decrement('stock_level', $itemData['quantity']);
        }

        // Send Email & BulkSMS Nigeria Notifications to Customer and Vendor
        app(\App\Services\OrderNotificationService::class)->sendOrderPlacedNotifications($order);

        return redirect()->route('consumer.orders.show', $order->id)
            ->with('success', "Order #{$order->order_number} placed successfully! Notification alerts sent to you and vendor.");
    }

    public function showOrder(Order $order): Response
    {
        $order->load(['items.product', 'vendor']);

        return Inertia::render('Consumer/OrderShow', [
            'order' => $order,
        ]);
    }

    public function cancelOrder(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $user || ($order->user_id !== $user->id && ! $user->isVendor())) {
            abort(403, 'Unauthorized order cancellation action.');
        }

        if ($order->status !== 'pending') {
            return redirect()->back()->withErrors([
                'order' => 'Only pending orders that have not yet been packed or dispatched can be cancelled.',
            ]);
        }

        $order->loadMissing('items.product');

        // Restore stock levels
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock_level', $item->quantity);
            }
        }

        $order->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', "Order #{$order->order_number} cancelled successfully and stock level restored.");
    }
}
