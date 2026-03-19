@extends('layouts.app')

@section('title', 'Reset Password - GENUINE-RP.GE')

@section('additional_styles')
<style>
    .reset-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
    }

    .reset-card {
        background: rgba(26, 26, 26, 0.8);
        border: 2px solid rgba(255, 137, 28, 0.3);
        border-radius: 16px;
        padding: 50px;
        width: 100%;
        max-width: 400px;
        backdrop-filter: blur(10px);
    }

    .reset-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .reset-header h1 {
        color: #FF891C;
        font-size: 28px;
        margin: 0 0 10px 0;
    }

    .reset-header p {
        color: #bbb;
        font-size: 14px;
        margin: 0;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        color: #ddd;
        font-size: 14px;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .form-control {
        width: 100%;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 12px 15px;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #FF891C;
        background: rgba(255, 255, 255, 0.15);
        box-shadow: 0 0 12px rgba(255, 137, 28, 0.3);
    }

    .form-error {
        color: #F44336;
        font-size: 12px;
        margin-top: 5px;
        display: block;
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, #FF891C, #FF6B1C);
        border: none;
        color: white;
        padding: 12px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 25px;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 137, 28, 0.4);
    }

    .reset-footer {
        text-align: center;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .reset-footer a {
        color: #FF891C;
        text-decoration: none;
    }

    .reset-footer a:hover {
        text-decoration: underline;
    }

    .alert {
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.2);
        border: 1px solid #4CAF50;
        color: #4CAF50;
    }

    .alert-danger {
        background: rgba(244, 67, 54, 0.2);
        border: 1px solid #F44336;
        color: #F44336;
    }
</style>
@endsection

@section('content')
<div class="reset-container">
    <div class="reset-card">
        <div class="reset-header">
            <h1>Reset Password</h1>
            <p>Enter your email address</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="admin@genuine-rp.ge"
                    required
                    autofocus
                >
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn-submit">Send Reset Link</button>
        </form>

        <div class="reset-footer">
            <p>
                <a href="{{ route('login') }}">Back to login</a>
            </p>
        </div>
    </div>
</div>
@endsection
