@extends('layouts.app')

@section('title', 'Shop Items - Admin Panel')

@section('additional_styles')
<style>
    .page-container {
        max-width: 1400px;
        margin: 100px auto;
        padding: 40px 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        border-bottom: 2px solid rgba(255, 137, 28, 0.3);
        padding-bottom: 20px;
    }

    .page-header h1 {
        color: #fff;
        font-size: 28px;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .btn-create {
        background: #FF891C;
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .btn-create:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(255, 137, 28, 0.5);
    }

    .filter-section {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        color: #ddd;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 14px;
    }

    .form-control:focus {
        outline: none;
        border-color: #FF891C;
        box-shadow: 0 0 10px rgba(255, 137, 28, 0.3);
    }

    .btn-filter {
        background: #FF891C;
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
        align-self: flex-end;
    }

    .btn-filter:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(255, 137, 28, 0.5);
    }

    .table-container {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th {
        color: #FF891C;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid rgba(255, 137, 28, 0.2);
        background: rgba(255, 137, 28, 0.05);
    }

    table td {
        color: #ddd;
        padding: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    table tbody tr {
        transition: background 0.3s ease;
    }

    table tbody tr:hover {
        background: rgba(255, 137, 28, 0.05);
    }

    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-active {
        background: rgba(76, 175, 80, 0.2);
        color: #4CAF50;
    }

    .badge-inactive {
        background: rgba(244, 67, 54, 0.2);
        color: #F44336;
    }

    .action-btn {
        background: #FF891C;
        border: none;
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        margin-right: 5px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .action-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 0 10px rgba(255, 137, 28, 0.5);
    }

    .action-btn.danger {
        background: #F44336;
    }

    .action-btn.danger:hover {
        box-shadow: 0 0 10px rgba(244, 67, 54, 0.5);
    }

    .empty-state {
        color: #999;
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state p {
        font-size: 16px;
        margin: 10px 0;
    }

    .pagination {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        color: #ddd;
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        text-decoration: none;
    }

    .pagination a:hover {
        border-color: #FF891C;
        color: #FF891C;
    }

    .pagination .active span {
        background: #FF891C;
        color: white;
        border-color: #FF891C;
    }
</style>
@endsection

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Shop Items</h1>
        <div class="header-actions">
            <a href="{{ route('admin.shop.items.create') }}" class="btn-create">+ Add New Item</a>
        </div>
    </div>

    @if ($message = session('success'))
    <div style="background: rgba(76, 175, 80, 0.2); border: 1px solid #4CAF50; color: #4CAF50; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        {{ $message }}
    </div>
    @endif

    @if ($message = session('error'))
    <div style="background: rgba(244, 67, 54, 0.2); border: 1px solid #F44336; color: #F44336; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        {{ $message }}
    </div>
    @endif

    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" action="{{ route('admin.shop.items.index') }}" class="filter-form">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-filter">Filter</button>
        </form>
    </div>

    <!-- Items Table -->
    <div class="table-container">
        @if($items->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (GEL)</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Sales</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>#{{ $item->id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>
                        <span style="color: #FF891C;">{{ $item->category->name ?? '-' }}</span>
                    </td>
                    <td>₾{{ number_format($item->price, 2) }}</td>
                    <td>{{ $item->stock ?? 'Unlimited' }}</td>
                    <td>
                        @if($item->is_active)
                        <span class="badge badge-active">Active</span>
                        @else
                        <span class="badge badge-inactive">Inactive</span>
                        @endif
                    </td>
                    <td style="color: #4CAF50; font-weight: 600;">{{ $item->completed_count ?? 0 }}</td>
                    <td style="white-space: nowrap;">
                        <a href="{{ route('admin.shop.items.edit', $item->id) }}" class="action-btn">Edit</a>
                        <form style="display: inline;" method="POST" action="{{ route('admin.shop.items.destroy', $item->id) }}" onsubmit="return confirm('Delete this item?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="action-btn danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $items->links() }}
        @else
        <div class="empty-state">
            <p>No shop items found</p>
            <p style="color: #666; font-size: 14px;">Create your first item to get started</p>
            <a href="{{ route('admin.shop.items.create') }}" class="btn-create" style="margin-top: 20px;">+ Add New Item</a>
        </div>
        @endif
    </div>
</div>
@endsection
