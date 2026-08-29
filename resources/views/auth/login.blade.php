@extends('layouts.app')

@section('title', 'Login — Stua')

@section('content')
    <section class="card login-card">
        <h1>Shop login / Login blong shop</h1>
        <p class="lede">Desktop till book for the day. Phone shop-floor stays on Android.</p>
        <form method="post" action="{{ route('login') }}" class="stack">
            @csrf
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            @error('email') <p class="error">{{ $message }}</p> @enderror
            <label>
                Password / Paswod
                <input type="password" name="password" required>
            </label>
            <button class="btn-pay" type="submit">Log in / Login</button>
        </form>
    </section>
@endsection
