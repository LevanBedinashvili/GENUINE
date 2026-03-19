@extends('layouts.app')

@section('title', 'Edit Shop Item - Admin Panel')

@section('additional_styles')
<style>
    .page-container {
        max-width: 800px;
        margin: 100px auto;
        padding: 40px 20px;
    }

    .page-header {
        margin-bottom: 30px;
        border-bottom: 2px solid rgba(255, 137, 28, 0.3);
        padding-bottom: 20px;
    }

    .page-header h1 {
        color: #fff;
        font-size: 28px;
        margin: 0;
    }

    .form-container {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        color: #ddd;
        font-size: 14px;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .form-group small {
        display: block;
        color: #999;
        font-size: 12px;
        margin-top: 5px;
    }

    .form-control {
        width: 100%;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #FF891C;
        box-shadow: 0 0 10px rgba(255, 137, 28, 0.3);
    }

    .form-control::placeholder {
        color: #666;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-row.full {
        grid-template-columns: 1fr;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #FF891C;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #FF891C;
        color: white;
    }

    .btn-primary:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(255, 137, 28, 0.5);
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: #ddd;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .btn-danger {
        background: #F44336;
        color: white;
        margin-left: auto;
    }

    .btn-danger:hover {
        box-shadow: 0 0 15px rgba(244, 67, 54, 0.5);
    }

    .error-message {
        color: #F44336;
        font-size: 12px;
        margin-top: 5px;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-danger {
        background: rgba(244, 67, 54, 0.2);
        border: 1px solid #F44336;
        color: #F44336;
    }

    .item-info {
        background: rgba(255, 137, 28, 0.05);
        border: 1px solid rgba(255, 137, 28, 0.3);
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #ddd;
    }

    .item-info strong {
        color: #FF891C;
    }
</style>
@endsection

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Edit Shop Item</h1>
    </div>

    <div class="item-info">
        <strong>Item ID:</strong> #{{ $item->id }} | 
        <strong>Created:</strong> {{ $item->created_at->format('M d, Y') }}
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <strong>Validation errors:</strong>
        <ul style="margin: 10px 0 0 20px;">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="form-container">
        <form action="{{ route('admin.shop.items.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="category_id">Category *</label>
                <select name="category_id" id="category_id" class="form-control" required>
                    <option value="">Select a category</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $item->category_id == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
                @error('category_id')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="name">Item Name *</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter item name" value="{{ old('name', $item->name) }}" required>
                @error('name')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" placeholder="Item description (max 1000 characters)">{{ old('description', $item->description) }}</textarea>
                <small>Maximum 1000 characters</small>
                @error('description')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price (GEL) *</label>
                    <input type="number" name="price" id="price" class="form-control" placeholder="0.00" step="0.01" min="0.01" value="{{ old('price', $item->price) }}" required>
                    <small>Minimum price: 0.01</small>
                    @error('price')
                    <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="ingame_item_id">In-Game Item ID</label>
                    <input type="number" name="ingame_item_id" id="ingame_item_id" class="form-control" placeholder="Optional" value="{{ old('ingame_item_id', $item->ingame_item_id) }}">
                    @error('ingame_item_id')
                    <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" placeholder="Item quantity" min="1" value="{{ old('quantity', $item->quantity) }}" required>
                    @error('quantity')
                    <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="max_quantity_per_purchase">Max Quantity Per Purchase</label>
                    <input type="number" name="max_quantity_per_purchase" id="max_quantity_per_purchase" class="form-control" placeholder="No limit if empty" min="1" value="{{ old('max_quantity_per_purchase', $item->max_quantity_per_purchase) }}">
                    @error('max_quantity_per_purchase')
                    <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="image_url">Image URL</label>
                <input type="url" name="image_url" id="image_url" class="form-control" placeholder="https://example.com/image.png" value="{{ old('image_url', $item->image_url) }}">
                @error('image_url')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" placeholder="0" min="0" value="{{ old('sort_order', $item->sort_order) }}">
                    <small>Used for ordering items (lower numbers appear first)</small>
                    @error('sort_order')
                    <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="checkbox-group">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                        <label for="is_active" style="margin: 0; cursor: pointer;">Active</label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Item</button>
                <a href="{{ route('admin.shop.items.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <!-- Delete Form - Outside main form to prevent nesting -->
        <form action="{{ route('admin.shop.items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');" style="margin-top: 20px;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete Item</button>
        </form>
    </div>
</div>
@endsection
