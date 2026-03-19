@extends('layouts.app')

@section('title', $success ? 'გადახდა დასრულდა - GENUINE-RP.GE' : 'გადახდა ვერ მოხერხდა - GENUINE-RP.GE')

@section('additional_styles')
<style>
    .payment-status-container {
        max-width: 600px;
        margin: 100px auto;
        padding: 40px 20px;
    }

    .status-card {
        background: rgba(26, 26, 26, 0.95);
        border: 2px solid;
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        backdrop-filter: blur(10px);
    }

    .status-card.success {
        border-color: #4CAF50;
    }

    .status-card.error {
        border-color: #F44336;
    }

    .status-icon {
        font-size: 64px;
        margin-bottom: 20px;
        display: block;
    }

    .status-icon.success {
        color: #4CAF50;
        animation: scaleIn 0.6s ease;
    }

    .status-icon.error {
        color: #F44336;
        animation: shake 0.5s;
    }

    .status-icon.pending {
        color: #FF9800;
        animation: spin 2s linear infinite;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0);
        }
        to {
            transform: scale(1);
        }
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .status-title {
        color: #fff;
        font-size: 28px;
        font-weight: 600;
        margin: 20px 0;
    }

    .status-message {
        color: #bbb;
        font-size: 16px;
        margin: 15px 0;
        line-height: 1.6;
    }

    .status-details {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 20px;
        margin: 30px 0;
        text-align: left;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        color: #ddd;
        font-size: 14px;
        margin: 10px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .detail-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .detail-label {
        color: #999;
        font-weight: 500;
    }

    .detail-value {
        color: #4CAF50;
        font-weight: 500;
        word-break: break-all;
    }

    .status-details.error .detail-value {
        color: #F44336;
    }

    .status-details.pending .detail-value {
        color: #FF9800;
    }

    .action-buttons {
        margin-top: 30px;
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: linear-gradient(135deg, #FF891C, #FF6B1C);
        color: white;
        font-weight: 600;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(255, 137, 28, 0.4);
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: #ddd;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .refresh-info {
        background: rgba(255, 152, 0, 0.1);
        border: 1px solid rgba(255, 152, 0, 0.3);
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        color: #FF9800;
        font-size: 14px;
    }

    .warning-info {
        background: rgba(244, 67, 54, 0.1);
        border: 1px solid rgba(244, 67, 54, 0.3);
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        color: #F44336;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .payment-status-container {
            margin-top: 80px;
            padding: 20px 15px;
        }

        .status-card {
            padding: 25px;
        }

        .status-icon {
            font-size: 48px;
        }

        .status-title {
            font-size: 22px;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
@php
    // Prevent back navigation and caching
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
@endphp

<script>
    // Prevent back navigation
    window.onload = function() {
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, null, window.location.href);
        };
    };
</script>

<div class="payment-status-container">
    <div class="status-card {{ 
        $success ? 'success' : 
        (isset($transaction) && $transaction->status === 'pending' ? 'pending' : 'error') 
    }}">
        <i class="fas {{ 
            $success ? 'fa-check-circle status-icon success' : 
            (isset($transaction) && $transaction->status === 'pending' ? 'fa-spinner status-icon pending' : 'fa-times-circle status-icon error') 
        }}"></i>

        <h1 class="status-title">
            @if ($success)
                გადახდა წარმატებით დასრულდა!
            @else
                გადახდა ვერ მოხერხდა
            @endif
        </h1>

        @if (isset($transaction))
            <div class="status-details {{ 
                $success ? 'success' : 
                (isset($transaction) && $transaction->status === 'pending' ? 'pending' : 'error') 
            }}">
                {{-- <div class="detail-row">
                    <span class="detail-label">შეკვეთის ID:</span>
                    <span class="detail-value">{{ $transaction->bog_order_id }}</span>
                </div> --}}
                <div class="detail-row">
                    <span class="detail-label">გადასახდელი თანხა:</span>
                    <span class="detail-value">{{ number_format($transaction->amount, 2) }} ₾</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">სტატუსი:</span>
                    <span class="detail-value">
                        @if ($transaction->status === 'completed')
                            ✅ წარმატებული
                        @elseif ($transaction->status === 'pending')
                            ⏳ მოლოდინე
                        @elseif ($transaction->status === 'failed')
                            ❌ ვერ მოხერხდა
                        @else
                            🔄 {{ ucfirst($transaction->status) }}
                        @endif
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">გადახდის თარიღი:</span>
                    <span class="detail-value">{{ $transaction->created_at->format('d.m.Y H:i:s') }}</span>
                </div>
            </div>
        @endif

        @if(isset($transaction) && $transaction->status === 'pending')
            <div class="refresh-info">
                <strong>⏳ გადახდა დამუშავდება</strong>
                <p>BOG თქვენივე თქვენი გადახდის მდგომარეობას მოგვაცნობებს. ამ გვერდს ავტომატურად განახლდება.</p>
            </div>

            <div class="action-buttons">
                <button onclick="manualCheck()" class="btn btn-primary" id="check-btn">🔄 სტატუსის შემოწმება</button>
                <a href="{{ route('home') }}" class="btn btn-secondary">მთავარ გვერდზე</a>
            </div>

            <div id="auto-status" style="margin-top: 20px; text-align: center; color: #FF9800; font-size: 14px;"></div>
        @else
            <div class="action-buttons">
                <a href="{{ route('home') }}" class="btn btn-primary">მთავარ გვერდზე</a>
                <a href="{{ route('shop') }}" class="btn btn-secondary">მაღაზიაში დაბრუნება</a>
            </div>
        @endif
    </div>
</div>

@if(isset($transaction) && $transaction->status === 'pending')
<script>
let checkCount = 0;
const maxChecks = 36;
let isChecking = false;

function manualCheck() {
    if (isChecking) return;
    
    const btn = document.getElementById('check-btn');
    btn.disabled = true;
    btn.textContent = '⏳ დამუშავდება...';
    isChecking = true;
    
    fetch(`/payment/check/{{ $transaction->id }}`)
        .then(response => response.json())
        .then(data => {
            console.log('Status check result:', data);
            
            if (data.status && data.status !== 'pending') {
                document.getElementById('auto-status').textContent = 'სტატუსი განახლდა!';
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                document.getElementById('auto-status').textContent = '' + (data.message || 'ველოდებით პასუხს პასუხის...');
                btn.disabled = false;
                btn.textContent = 'სტატუსის შემოწმება';
                isChecking = false;
            }
        })
        .catch(error => {
            console.error('Error checking status:', error);
            document.getElementById('auto-status').textContent = 'შეცდომა სტატუსის შემოწმებაში';
            btn.disabled = false;
            btn.textContent = '🔄 სტატუსის შემოწმება';
            isChecking = false;
        });
}

function autocheck() {
    if (checkCount >= maxChecks) {
        document.getElementById('auto-status').textContent = 'ავტომატური შემოწმება დასრულდა. გთხოვთ ხელით შემოწმება.';
        return;
    }

    checkCount++;
    document.getElementById('auto-status').textContent = `სტატუსისავტომატური შემოწმება... (${checkCount}/${maxChecks})`;

    fetch(`/payment/check/{{ $transaction->id }}`)
        .then(response => response.json())
        .then(data => {
            console.log('Auto-check result:', data);
            
            if (data.status && data.status !== 'pending') {
                document.getElementById('auto-status').textContent = 'სტატუსი განახლდა!';
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        })
        .catch(error => console.error('Auto-check error:', error));
}

setTimeout(() => {
    autocheck();
    setInterval(autocheck, 10000);
}, 5000);
</script>
@endif
@endsection
