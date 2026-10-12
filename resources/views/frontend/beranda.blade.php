@extends('frontend.main.layout-main')
@section('content')
    <!-- Login Section -->
    <section class="login-section">
        <div class="container">
            <div class="row justify-content-center align-items-center " style="min-height: 80vh;">
                <div class="col-md-6 col-lg-5">
                    <div class="login-card">
                        <div class="card shadow-lg border-0 bg-success">
                            <div class="card-body p-5">
                                <!-- Logo/Header -->
                                <div class="text-center mb-4">
                                    {{-- <img src="{{ asset('frontend/img/logo-bsj.png') }}" alt="Bank Sampah Jayapura" class="img-fluid" style="max-height: 80px;"> --}}
                                    <h3 class="mt-3 font-weight-bold" style="color: #2d3436;">BANK SAMPAH <span style="color: #00c400;">JAYAPURA</span></h3>
                                    <p class="text-muted">Silahkan Login untuk melanjutkan</p>
                                </div>

                                <!-- Alert Error -->
                                @if ($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle"></i> 
                                        @foreach ($errors->all() as $error)
                                            {{ $error }}
                                        @endforeach
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif

                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif

                                @if (session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif

                                <!-- Form Login -->
                                <form action="{{ route('login') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="email" class="font-weight-bold">Alamat Email</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-success text-white border-0">
                                                    <i class="fas fa-envelope"></i>
                                                </span>
                                            </div>
                                            <input type="email" name="email" id="email" 
                                                   class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                                   placeholder="masukkan@email.com" 
                                                   value="{{ old('email') }}" 
                                                   required autofocus>
                                        </div>
                                        @error('email')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="password" class="font-weight-bold">Password</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-success text-white border-0">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            </div>
                                            <input type="password" name="password" id="password" 
                                                   class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                                   placeholder="Masukkan password" 
                                                   required>
                                            {{-- <div class="input-group-append">
                                                <span class="input-group-text bg-white" style="cursor: pointer;" onclick="togglePassword()">
                                                    <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                                </span>
                                            </div> --}}
                                        </div>
                                        @error('password')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                   

                                    <div class="form-group m-auto">
                                        <button type="submit" class="btn btn-success btn-lg btn-block font-weight-bold">
                                            <i class="fas fa-sign-in-alt mr-2"></i> LOGIN
                                        </button>
                                    </div>
                                </form>

                                <hr class="my-4">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('styles')
<style>
    /* Background Gradient */
    .login-section {
        background: linear-gradient(135deg, #00b894 0%, #00c400 50%, #2d3436 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        padding: 20px 0;
        position: relative;
    }

    /* Decorative Elements */
    .login-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        pointer-events: none;
    }

    .login-section::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 50%;
        pointer-events: none;
    }

    /* Login Card */
    .login-card {
        animation: fadeInUp 0.6s ease;
        position: relative;
        z-index: 1;
    }

    .login-card .card {
        border-radius: 20px;
        overflow: hidden;
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.98);
        transition: transform 0.3s ease;
    }

    .login-card .card:hover {
        transform: translateY(-5px);
    }

    /* Form Input */
    .form-control {
        border-radius: 10px;
        border: 2px solid #e8f0fe;
        padding: 12px 15px;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #00c400;
        box-shadow: 0 0 0 0.2rem rgba(0, 196, 0, 0.15);
    }

    .input-group-text {
        border-radius: 10px 0 0 10px;
        min-width: 45px;
        justify-content: center;
    }

    .input-group .form-control {
        border-radius: 0 10px 10px 0;
        border-left: none;
    }

    .input-group .form-control:focus {
        border-left: none;
    }

    .input-group-append .input-group-text {
        border-radius: 0 10px 10px 0;
        border-left: none;
        cursor: pointer;
    }

    /* Button */
    .btn-success {
        background: linear-gradient(135deg, #00b894, #00c400);
        border: none;
        border-radius: 10px;
        padding: 14px;
        font-size: 16px;
        transition: all 0.3s ease;
        letter-spacing: 1px;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 196, 0, 0.3);
        background: linear-gradient(135deg, #00c400, #00b894);
    }

    .btn-outline-success {
        border-radius: 10px;
        padding: 12px;
        border-width: 2px;
        transition: all 0.3s ease;
    }

    .btn-outline-success:hover {
        background: linear-gradient(135deg, #00b894, #00c400);
        border-color: #00b894;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 196, 0, 0.2);
    }

    /* Custom Checkbox */
    .custom-control-label {
        cursor: pointer;
        font-size: 14px;
    }

    .custom-control-label::before {
        border-radius: 4px;
        border: 2px solid #00c400;
        background-color: white;
    }

    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #00c400;
        border-color: #00c400;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Alert */
    .alert {
        border-radius: 10px;
        border: none;
    }

    .alert-danger {
        background-color: #fee;
        color: #dc3545;
    }

    .alert-success {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .login-card .card-body {
            padding: 30px 20px !important;
        }

        .login-section {
            padding: 15px;
            min-height: 100vh;
        }

        .login-section .row {
            min-height: auto !important;
        }

        .form-control-lg {
            font-size: 14px;
            padding: 10px 12px;
        }

        .btn-lg {
            font-size: 14px;
            padding: 12px;
        }
    }

    @media (max-width: 576px) {
        .login-card .card-body {
            padding: 25px 15px !important;
        }

        h3 {
            font-size: 20px !important;
        }

        .login-card .card {
            border-radius: 15px;
        }
    }
</style>
@endsection

@section('scripts')
<script>
    // Toggle Password Visibility
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert) {
                    const closeButton = alert.querySelector('.close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }
            });
        }, 5000);
    });
</script>
@endsection