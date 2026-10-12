@extends('layouts.app')

@section('title', 'Admin Login | Mini ERP')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Admin Login</h4>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="remember" id="remember" value="1" class="form-check-input">
                            <label for="remember" class="form-check-label">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Sign In
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection