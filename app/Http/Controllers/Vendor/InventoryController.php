<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $vendor = $request->user();

        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $tab = $request->query('tab', 'my_stock');

        // Vendor active items query
        $myStockQuery = Product::where('vendor_id', $vendor->id)
            ->with('category')
            ->orderBy('name');

        if ($search) {
            $myStockQuery->where('name', 'like', "%{$search}%");
        }
        if ($categoryId) {
            $myStockQuery->where('category_id', $categoryId);
        }

        $myStock = $myStockQuery->get();

        // Global Catalog Query
        $globalCatalogQuery = Product::global()
            ->with('category')
            ->orderBy('name');

        if ($search) {
            $globalCatalogQuery->where('name', 'like', "%{$search}%");
        }
        if ($categoryId) {
            $globalCatalogQuery->where('category_id', $categoryId);
        }

        $globalCatalog = $globalCatalogQuery->get();

        // Calculate summary metrics
        $allVendorItems = Product::where('vendor_id', $vendor->id)->get();
        $totalItemsCount = $allVendorItems->count();
        $lowStockCount = $allVendorItems->filter(fn ($item) => $item->stock_level > 0 && $item->stock_level <= 10)->count();
        $outOfStockCount = $allVendorItems->filter(fn ($item) => $item->stock_level === 0)->count();
        $totalInventoryValue = $allVendorItems->sum(fn ($item) => $item->stock_level * $item->cost_price);
        $totalPotentialRevenue = $allVendorItems->sum(fn ($item) => $item->stock_level * $item->selling_price);

        return Inertia::render('Vendor/Inventory/Index', [
            'myStock' => $myStock,
            'globalCatalog' => $globalCatalog,
            'categories' => Category::orderBy('name')->get(),
            'filters' => [
                'search' => $search ?? '',
                'category_id' => $categoryId ?? '',
                'tab' => $tab,
            ],
            'metrics' => [
                'totalItemsCount' => $totalItemsCount,
                'lowStockCount' => $lowStockCount,
                'outOfStockCount' => $outOfStockCount,
                'totalInventoryValue' => (float) $totalInventoryValue,
                'totalPotentialRevenue' => (float) $totalPotentialRevenue,
                'totalEstimatedProfit' => (float) ($totalPotentialRevenue - $totalInventoryValue),
            ],
        ]);
    }

    public function storeGlobal(Request $request)
    {
        $vendor = $request->user();

        $validated = $request->validate([
            'global_product_id' => 'required|exists:products,id',
            'stock_level' => 'required|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $globalProduct = Product::findOrFail($validated['global_product_id']);

        // Check if vendor already has a product with this name
        $existing = Product::where('vendor_id', $vendor->id)
            ->where('name', $globalProduct->name)
            ->first();

        if ($existing) {
            $existing->update([
                'stock_level' => $existing->stock_level + $validated['stock_level'],
                'cost_price' => $validated['cost_price'],
                'selling_price' => $validated['selling_price'],
                'is_active' => true,
            ]);
        } else {
            Product::create([
                'category_id' => $globalProduct->category_id,
                'vendor_id' => $vendor->id,
                'name' => $globalProduct->name,
                'slug' => Str::slug($globalProduct->name.'-'.$vendor->id),
                'description' => $globalProduct->description,
                'image_url' => $globalProduct->image_url,
                'unit' => $globalProduct->unit,
                'cost_price' => $validated['cost_price'],
                'selling_price' => $validated['selling_price'],
                'stock_level' => $validated['stock_level'],
                'is_global' => false,
                'is_active' => true,
                'is_chilled' => $globalProduct->is_chilled ?? true,
            ]);
        }

        return redirect()->back()->with('success', "'{$globalProduct->name}' added to your store inventory successfully!");
    }

    public function storeCustom(Request $request)
    {
        $vendor = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:50',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_level' => 'required|integer|min:0',
            'is_chilled' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp,svg,gif|max:10240',
            'image_url' => 'nullable|string|max:1000',
        ]);

        $imageUrl = $validated['image_url'] ?? 'https://images.unsplash.com/photo-1527281400683-1aae777175f8?auto=format&fit=crop&w=600&q=80';

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $imageUrl = Storage::url($path);
        }

        Product::create([
            'category_id' => $validated['category_id'],
            'vendor_id' => $vendor->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name'].'-'.$vendor->id),
            'description' => $validated['description'] ?? null,
            'image_url' => $imageUrl,
            'unit' => $validated['unit'],
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'stock_level' => $validated['stock_level'],
            'is_global' => false,
            'is_active' => true,
            'is_chilled' => $request->boolean('is_chilled', true),
        ]);

        return redirect()->back()->with('success', "Custom product '{$validated['name']}' created and stocked successfully!");
    }

    public function updateStock(Request $request, Product $product)
    {
        if ($product->vendor_id !== $request->user()->id) {
            abort(403, 'Unauthorized product access.');
        }

        $validated = $request->validate([
            'stock_level' => 'required|integer|min:0',
        ]);

        $product->update([
            'stock_level' => $validated['stock_level'],
        ]);

        return redirect()->back()->with('success', "Stock level for '{$product->name}' updated to {$validated['stock_level']}.");
    }

    public function toggleChilled(Request $request, Product $product)
    {
        if ($product->vendor_id !== $request->user()->id) {
            abort(403, 'Unauthorized product access.');
        }

        $product->update([
            'is_chilled' => ! $product->is_chilled,
        ]);

        $status = $product->is_chilled ? 'Ice-Cold ❄️' : 'Room Temp 🌡️';

        return redirect()->back()->with('success', "Temperature status for '{$product->name}' updated to {$status}.");
    }

    public function update(Request $request, Product $product)
    {
        if ($product->vendor_id !== $request->user()->id) {
            abort(403, 'Unauthorized product access.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:50',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_level' => 'required|integer|min:0',
            'is_chilled' => 'nullable|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp,svg,gif|max:10240',
            'image_url' => 'nullable|string|max:1000',
        ]);

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'stock_level' => $validated['stock_level'],
            'is_chilled' => $request->boolean('is_chilled', $product->is_chilled),
            'description' => $validated['description'] ?? $product->description,
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = Storage::url($path);
        } elseif (! empty($validated['image_url'])) {
            $data['image_url'] = $validated['image_url'];
        }

        $product->update($data);

        return redirect()->back()->with('success', "Product '{$product->name}' updated successfully.");
    }

    public function destroy(Request $request, Product $product)
    {
        if ($product->vendor_id !== $request->user()->id) {
            abort(403, 'Unauthorized product access.');
        }

        $name = $product->name;
        $product->delete();

        return redirect()->back()->with('success', "Item '{$name}' removed from your inventory.");
    }
}
