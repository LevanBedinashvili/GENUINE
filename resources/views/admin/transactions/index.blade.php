@extends('layouts.app')

@section('title', 'Transactions - Admin Panel')

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

    .btn-primary {
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

    .btn-primary:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(255, 137, 28, 0.5);
    }

    .search-player {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .search-form {
        display: flex;
        gap: 10px;
    }

    .search-form input {
        flex: 1;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 10px 12px;
        border-radius: 6px;
    }

    .search-form input::placeholder {
        color: #999;
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

    .badge-completed {
        background: rgba(76, 175, 80, 0.2);
        color: #4CAF50;
    }

    .badge-pending {
        background: rgba(255, 193, 7, 0.2);
        color: #FFC107;
    }

    .badge-failed {
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
    }

    .action-btn:hover {
        transform: scale(1.05);
    }

    .action-btn.danger {
        background: #F44336;
    }

    .pagination {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 30px;
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
        <h1>All Transactions</h1>
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

    <!-- Search by Player -->
    <div class="search-player">
        <h3 style="color: #fff; margin-top: 0; margin-bottom: 15px;">Search by Player Name</h3>
        <form method="POST" action="{{ route('admin.transactions.search') }}" class="search-form">
            @csrf
            <input type="text" name="player_name" placeholder="Enter player name..." required>
            <button type="submit" class="btn-primary">Search</button>
        </form>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="filter-form">
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>

            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>

            <div class="form-group">
                <label>Amount From</label>
                <input type="number" name="amount_from" class="form-control" step="0.01" value="{{ request('amount_from') }}">
            </div>

            <div class="form-group">
                <label>Amount To</label>
                <input type="number" name="amount_to" class="form-control" step="0.01" value="{{ request('amount_to') }}">
            </div>

            <button type="submit" class="btn-primary">Filter</button>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Account ID</th>
                    <th>Status</th>
                    <th>Amount</th>
                    {{-- <th>Currency</th> --}}
                    <th>Item</th>
                    <th>Payment Method</th>
                    <th>External TX ID</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td>#{{ $tx->id }}</td>
                    <td>{{ $tx->account_id }}</td>
                    <td><span class="badge badge-{{ $tx->status }}">{{ ucfirst($tx->status) }}</span></td>
                    <td>{{ number_format($tx->amount, 2) }} ₾</td>
                    {{-- <td>{{ $tx->getCurrencyLabel() }}</td> --}}
                    <td>{{ $tx->shopItem->name ?? '-' }}</td>
                    <td>{{ $tx->payment_method ?? 'Credit Card' }}</td>
                    <td><code style="color: #FF891C; font-size: 11px;">{{ substr($tx->external_tx_id ?? 'N/A', 0, 16) }}...</code></td>
                    <td>{{ $tx->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        @if($tx->status === 'pending')
                            <form style="display: inline;" method="POST" action="{{ route('admin.transactions.approve', $tx->id) }}">
                                @csrf
                                <button type="submit" class="action-btn">Approve</button>
                            </form>
                            <form style="display: inline;" method="POST" action="{{ route('admin.transactions.fail', $tx->id) }}">
                                @csrf
                                <button type="submit" class="action-btn danger">Reject</button>
                            </form>
                        @else
                            <span style="color: #999; font-size: 12px;">
                                {{ $tx->status === 'completed' ? '✅ Completed' : '❌ Failed' }}
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align: center; color: #999; padding: 30px;">No transactions found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $transactions->links() }}
</div>
@endsection
