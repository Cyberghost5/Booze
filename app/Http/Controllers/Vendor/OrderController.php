<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\WhatsAppDispatchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        protected WhatsAppDispatchService $dispatchService
    ) {}

    public function index(Request $request): Response
    {
        $vendor = $request->user();
        $dateFilter = $request->query('date', 'today'); // 'today', 'all', '7days'

        $ordersQuery = Order::where(function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id)
                  ->orWhereNull('vendor_id'); // Orders in system
        })
        ->with(['items.product', 'user'])
        ->orderByDesc('created_at');

        if ($dateFilter === 'today') {
            $ordersQuery->whereDate('created_at', now()->toDateString());
        } elseif ($dateFilter === '7days') {
            $ordersQuery->where('created_at', '>=', now()->subDays(7));
        }

        $orders = $ordersQuery->get();

        // Calculate Daily Bookkeeping Ledger Analytics
        $staffPhone = $vendor->phone ?? '';

        $formattedOrders = $orders->map(function ($order) use ($staffPhone) {
            // Calculate COGS for this order
            $orderCogs = $order->items->sum(function ($item) {
                $costPrice = $item->product?->cost_price ?? ($item->unit_price * 0.75); // Fallback to 75% if product deleted
                return $costPrice * $item->quantity;
            });

            $orderProfit = $order->status === 'cancelled' ? 0 : ($order->subtotal - $orderCogs);

            return array_merge($order->toArray(), [
                'cogs' => (float) $orderCogs,
                'net_profit' => (float) $orderProfit,
                'whatsapp_dispatch_url' => $this->dispatchService->generateDispatchLink($order, $staffPhone),
            ]);
        });

        // Summary Ledger Numbers (Exclude cancelled orders from active sales/COGS totals)
        $activeOrders = $formattedOrders->where('status', '!=', 'cancelled');
        $totalSales = $activeOrders->sum('subtotal');
        $totalCogs = $activeOrders->sum('cogs');
        $netProfit = $totalSales - $totalCogs;
        $profitMargin = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;

        return Inertia::render('Vendor/Orders/Index', [
            'orders' => $formattedOrders,
            'dateFilter' => $dateFilter,
            'ledger' => [
                'totalSales' => (float) $totalSales,
                'totalCogs' => (float) $totalCogs,
                'netProfit' => (float) $netProfit,
                'profitMargin' => round($profitMargin, 1),
                'totalOrdersCount' => $formattedOrders->count(),
                'pendingCount' => $formattedOrders->where('status', 'pending')->count(),
                'packedCount' => $formattedOrders->where('status', 'packed')->count(),
                'outForDeliveryCount' => $formattedOrders->where('status', 'out_for_delivery')->count(),
                'deliveredCount' => $formattedOrders->where('status', 'delivered')->count(),
                'cancelledCount' => $formattedOrders->where('status', 'cancelled')->count(),
            ],
            'staffPhone' => $staffPhone,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $vendor = $request->user();

        // Multi-Vendor Conflict Guard: Prevent Vendor B from claiming or modifying Vendor A's order
        if ($order->vendor_id && $order->vendor_id !== $vendor->id) {
            return redirect()->back()->withErrors([
                'order' => "Order #{$order->order_number} has already been accepted and claimed by another vendor store.",
            ]);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,packed,out_for_delivery,delivered,cancelled',
        ]);

        $newStatus = $validated['status'];
        $previousStatus = $order->status;

        // Auto Stock Restoration on Order Cancellation
        if ($newStatus === 'cancelled' && $previousStatus !== 'cancelled') {
            $order->loadMissing('items.product');
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_level', $item->quantity);
                }
            }
        }

        $order->update([
            'status' => $newStatus,
            'vendor_id' => $vendor->id,
        ]);

        $statusLabels = [
            'pending' => 'Pending',
            'packed' => 'Packed & Ready',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled & Stock Restored',
        ];

        return redirect()->back()->with('success', "Order #{$order->order_number} status updated to '{$statusLabels[$newStatus]}'.");
    }
}
