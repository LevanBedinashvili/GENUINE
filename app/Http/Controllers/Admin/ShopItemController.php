<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopItem;
use App\Models\ShopCategory;
use Illuminate\Http\Request;

class ShopItemController extends Controller
{
    public function index(Request $request)
    {
        $query = ShopItem::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        $items = $query->orderBy('sort_order')->paginate(15);
        $categories = ShopCategory::all();

        return view('admin.shop.items.index', [
            'items' => $items,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $categories = ShopCategory::active()->get();
        return view('admin.shop.items.create', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:shop_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0.01',
            'ingame_item_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:1',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'max_quantity_per_purchase' => 'integer|min:1',
        ]);

        ShopItem::create($validated);

        return redirect()->route('admin.shop.items.index')
            ->with('success', 'Shop item created successfully');
    }

    public function edit(ShopItem $item)
    {
        $categories = ShopCategory::active()->get();
        return view('admin.shop.items.edit', [
            'item' => $item,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, ShopItem $item)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:shop_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0.01',
            'ingame_item_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:1',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'max_quantity_per_purchase' => 'integer|min:1',
        ]);

        $item->update($validated);

        return redirect()->route('admin.shop.items.index')
            ->with('success', 'Shop item updated successfully');
    }

    public function destroy(ShopItem $item)
    {
        // Check if item has completed transactions
        if ($item->transactions()->where('status', 'completed')->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete item with completed transactions');
        }

        $item->delete();

        return redirect()->route('admin.shop.items.index')
            ->with('success', 'Shop item deleted successfully');
    }

    public function updateSortOrder(Request $request)
    {
        $items = $request->get('items', []);

        foreach ($items as $index => $itemId) {
            ShopItem::find($itemId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}

