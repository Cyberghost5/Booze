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

        // Calculate Bestseller Drink Today
        $todayItemCounts = [];
        foreach ($activeOrders as $ord) {
            foreach ($ord['items'] ?? [] as $it) {
                $itemName = $it['item_name'] ?? 'Unknown Item';
                $todayItemCounts[$itemName] = ($todayItemCounts[$itemName] ?? 0) + $it['quantity'];
            }
        }
        arsort($todayItemCounts);
        $bestsellerName = ! empty($todayItemCounts) ? array_key_first($todayItemCounts) : 'None';
        $bestsellerQty = ! empty($todayItemCounts) ? reset($todayItemCounts) : 0;

        return Inertia::render('Vendor/Orders/Index', [
            'orders' => $formattedOrders,
            'dateFilter' => $dateFilter,
            'ledger' => [
                'totalSales' => (float) $totalSales,
                'totalCogs' => (float) $totalCogs,
                'netProfit' => (float) $netProfit,
                'profitMargin' => round($profitMargin, 1),
                'bestsellerToday' => $bestsellerName !== 'None' ? "{$bestsellerName} ({$bestsellerQty} sold)" : 'No sales yet',
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

    public function exportCsv(Request $request)
    {
        $vendor = $request->user();
        $dateFilter = $request->query('date', 'today');

        $ordersQuery = Order::where(function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id)
                ->orWhereNull('vendor_id');
        })
            ->with(['items.product', 'user'])
            ->orderByDesc('created_at');

        if ($dateFilter === 'today') {
            $ordersQuery->whereDate('created_at', now()->toDateString());
        } elseif ($dateFilter === '7days') {
            $ordersQuery->where('created_at', '>=', now()->subDays(7));
        }

        $orders = $ordersQuery->get();

        $fileName = 'booze_sales_report_'.$dateFilter.'_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, [
                'Order #',
                'Date & Time',
                'Customer Name',
                'Customer Phone',
                'Delivery Address',
                'Items Summary',
                'Subtotal (NGN)',
                'Delivery Fee (NGN)',
                'Total Amount (NGN)',
                'Status',
            ]);

            foreach ($orders as $order) {
                $itemsSummary = $order->items->map(function ($item) {
                    return "{$item->item_name} x{$item->quantity} (@ NGN ".number_format($item->unit_price, 2).')';
                })->implode(' | ');

                fputcsv($file, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->customer_name,
                    $order->customer_phone,
                    $order->delivery_address,
                    $itemsSummary,
                    number_format($order->subtotal, 2, '.', ''),
                    number_format($order->delivery_fee, 2, '.', ''),
                    number_format($order->total_amount, 2, '.', ''),
                    strtoupper($order->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

    public function riderIndex(Request $request): Response
    {
        $vendor = $request->user();

        $activeOrders = Order::where(function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id)
                ->orWhereNull('vendor_id');
        })
            ->whereIn('status', ['packed', 'out_for_delivery', 'pending'])
            ->with(['items.product', 'user'])
            ->orderByRaw("CASE WHEN status = 'out_for_delivery' THEN 1 WHEN status = 'packed' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        $staffPhone = $vendor->phone ?? '';

        $formattedOrders = $activeOrders->map(function ($order) use ($staffPhone) {
            $mapQuery = urlencode($order->delivery_address.', Gwallameji, Bauchi, Nigeria');
            if ($order->delivery_latitude && $order->delivery_longitude) {
                $mapUrl = "https://www.google.com/maps/dir/?api=1&destination={$order->delivery_latitude},{$order->delivery_longitude}";
            } else {
                $mapUrl = "https://www.google.com/maps/search/?api=1&query={$mapQuery}";
            }

            $arrivalMessage = rawurlencode("Hello {$order->customer_name}! Your Booze App delivery rider has arrived at {$order->delivery_address}. Please step outside to collect Order #{$order->order_number}. Thank you!");
            $cleanPhone = preg_replace('/[^0-9]/', '', $order->customer_phone);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '234'.substr($cleanPhone, 1);
            }
            $whatsappArrivalUrl = "https://wa.me/{$cleanPhone}?text={$arrivalMessage}";

            return array_merge($order->toArray(), [
                'google_maps_url' => $mapUrl,
                'whatsapp_arrival_url' => $whatsappArrivalUrl,
                'whatsapp_dispatch_url' => $this->dispatchService->generateDispatchLink($order, $staffPhone),
            ]);
        });

        return Inertia::render('Vendor/Orders/RiderDispatch', [
            'orders' => $formattedOrders,
            'vendorStoreName' => $vendor->store_name ?? 'Gwallameji Liquor Store',
        ]);
    }
}
