<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');

        $productsQuery = Product::where('is_active', true)
            ->where('stock_level', '>', 0)
            ->with('category')
            ->orderBy('name');

        if ($search) {
            $productsQuery->where('name', 'like', "%{$search}%");
        }

        if ($categoryId) {
            $productsQuery->where('category_id', $categoryId);
        }

        $products = $productsQuery->get();
        $categories = Category::orderBy('name')->get();

        // Check for active order for authenticated user or session
        $activeOrder = null;
        if ($user = $request->user()) {
            $activeOrder = Order::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'packed', 'out_for_delivery'])
                ->latest()
                ->first();
        }

        return Inertia::render('Consumer/Catalog', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search ?? '',
                'category_id' => $categoryId ?? '',
            ],
            'deliveryFee' => 500.00, // Fixed delivery fee
            'activeOrder' => $activeOrder,
        ]);
    }
}
