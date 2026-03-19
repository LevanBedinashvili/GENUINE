@extends('layouts.app')

@section('title', 'Admin Dashboard - GENUINE-RP.GE Shop')

@section('additional_styles')
<style>
    .admin-container {
        max-width: 1400px;
        margin: 100px auto;
        padding: 40px 20px;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        border-bottom: 2px solid rgba(255, 137, 28, 0.3);
        padding-bottom: 20px;
    }

    .dashboard-header h1 {
        color: #fff;
        font-size: 32px;
        margin: 0;
    }

    .dashboard-welcome {
        color: #bbb;
        font-size: 14px;
    }

    .admin-nav {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .admin-nav a {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #ddd;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .admin-nav a:hover,
    .admin-nav a.active {
        background: rgba(255, 137, 28, 0.1);
        border-color: #FF891C;
        color: #FF891C;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        border-color: rgba(255, 137, 28, 0.5);
        box-shadow: 0 0 20px rgba(255, 137, 28, 0.2);
    }

    .stat-card h3 {
        color: #bbb;
        font-size: 14px;
        margin: 0 0 15px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .stat-value {
        color: #FF891C;
        font-size: 32px;
        font-weight: bold;
        margin: 0;
    }

    .stat-label {
        color: #999;
        font-size: 12px;
        margin-top: 10px;
    }

    .section {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .section h2 {
        color: #fff;
        font-size: 20px;
        margin: 0 0 20px 0;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table thead {
        background: rgba(255, 137, 28, 0.05);
    }

    table th {
        color: #FF891C;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid rgba(255, 137, 28, 0.2);
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
        padding: 8px 15px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 0 10px rgba(255, 137, 28, 0.5);
    }

    .action-btn.danger {
        background: #F44336;
    }

    .top-items-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
    }

    .item-card {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 137, 28, 0.3);
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .item-card:hover {
        border-color: #FF891C;
        box-shadow: 0 0 15px rgba(255, 137, 28, 0.3);
    }

    .item-card h4 {
        color: #fff;
        margin: 0 0 10px 0;
        font-size: 14px;
    }

    .item-sales {
        color: #FF891C;
        font-size: 24px;
        font-weight: bold;
        margin: 0;
    }

    .item-label {
        color: #999;
        font-size: 12px;
        margin: 5px 0 0 0;
    }

    .empty-state {
        color: #999;
        text-align: center;
        padding: 30px;
    }
</style>
@endsection

@section('content')
<div class="admin-container">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <div class="dashboard-welcome">
            Welcome, {{ auth()->user()->name }}
        </div>
    </div>

    <div class="admin-nav">
        <a href="{{ route('admin.dashboard') }}" class="active">Dashboard</a>
        <a href="{{ route('admin.transactions.index') }}">Transactions</a>
        <a href="{{ route('admin.shop.items.index') }}">Shop Items</a>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Revenue</h3>
            <p class="stat-value">${{ number_format($stats['total_revenue'], 2) }}</p>
            <p class="stat-label">From {{ $stats['completed_transactions'] }} transactions</p>
        </div>

        <div class="stat-card">
            <h3>Total Transactions</h3>
            <p class="stat-value">{{ $stats['total_transactions'] }}</p>
            <p class="stat-label">All time</p>
        </div>

        <div class="stat-card">
            <h3>Completed</h3>
            <p class="stat-value">{{ $stats['completed_transactions'] }}</p>
            <p class="stat-label" style="color: #4CAF50;">Successful</p>
        </div>

        <div class="stat-card">
            <h3>Pending</h3>
            <p class="stat-value">{{ $stats['pending_transactions'] }}</p>
            <p class="stat-label" style="color: #FFC107;">Awaiting approval</p>
        </div>

        <div class="stat-card">
            <h3>Failed</h3>
            <p class="stat-value">{{ $stats['failed_transactions'] }}</p>
            <p class="stat-label" style="color: #F44336;">Rejected</p>
        </div>

        <div class="stat-card">
            <h3>Unique Players</h3>
            <p class="stat-value">{{ $stats['total_users_purchased'] }}</p>
            <p class="stat-label">Made purchases</p>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="section">
        <h2>Recent Transactions (Last 10)</h2>
        @if($recentTransactions->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Amount</th>
                    {{-- <th>Currency</th> --}}
                    <th>Item</th>
                    <th>Payment Method</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentTransactions as $tx)
                <tr>
                    <td>#{{ $tx->id }}</td>
                    <td><span class="badge badge-{{ $tx->status }}">{{ ucfirst($tx->status) }}</span></td>
                    <td>${{ number_format($tx->amount, 2) }}</td>
                    {{-- <td>{{ $tx->getCurrencyLabel() }}</td> --}}
                    <td>{{ $tx->shopItem->name ?? 'Direct Purchase' }}</td>
                    <td>{{ $tx->payment_method ?? 'N/A' }}</td>
                    <td>{{ $tx->created_at->format('M d, Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="empty-state">No transactions yet</p>
        @endif
    </div>

    <!-- Top Selling Items -->
    <div class="section">
        <h2>Top Selling Items</h2>
        @if($topItems->count() > 0)
        <div class="top-items-grid">
            @foreach($topItems as $item)
            <div class="item-card">
                <h4>{{ $item->name }}</h4>
                <p class="item-sales">{{ $item->completed_count }}</p>
                <p class="item-label">Sales</p>
            </div>
            @endforeach
        </div>
        @else
        <p class="empty-state">No items sold yet</p>
        @endif
    </div>
</div>
@endsection
