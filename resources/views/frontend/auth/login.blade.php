@extends('frontend.main.layout-main')
@section('content')
<section class="customer-page section-ptb" style="min-height: 80vh; display: flex; align-items: center; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <!-- Login Form -->
                <div class="log-acc-page" id="CustomerLoginForm">
                    <div class="card shadow-lg border-0 rounded-4" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3 d-inline-block mb-3">
                                    <img src="{{ asset('frontend/img/logo/bank-sampah-removebg.png') }}" alt="Bank Sampah" style="width: 80px; height: 80px; object-fit: contain;">
                                </div>
                                <h2 class="h4 fw-bold mb-1" style="color: #2d3436;">Selamat Datang!</h2>
                                <p class="text-muted small">Masuk ke akun Anda untuk mengakses dasbor</p>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    {{ $errors->first() }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login.submit') }}" class="needs-validation" novalidate>
                                @csrf
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-secondary">
                                        <i class="bi bi-envelope-fill me-1"></i> Email Address
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-envelope text-primary"></i>
                                        </span>
                                        <input type="email" name="email" class="form-control border-start-0 ps-0" 
                                               placeholder="Masukkan email Anda" autocomplete="off" 
                                               value="{{ old('email') }}" required>
                                    </div>
                                    <div class="invalid-feedback">Email wajib diisi</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-secondary">
                                        <i class="bi bi-lock-fill me-1"></i> Password
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-key text-primary"></i>
                                        </span>
                                        <input type="password" name="password" class="form-control border-start-0 ps-0" 
                                               placeholder="Masukkan password Anda" required>
                                        <button class="btn btn-outline-secondary border-start-0" type="button" 
                                                onclick="togglePassword(this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Password wajib diisi</div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill py-2 fw-semibold" 
                                            style="background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); border: none; box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);">
                                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                                    </button>
                                </div>
                            </form>

                            <hr class="my-4" style="border-color: #dfe6e9;">
                        </div>
                    </div>
                </div>

                <!-- Recover Password Form -->
                <div class="log-acc-page" id="RecoverPasswordForm" style="display: none;">
                    <div class="card shadow-lg border-0 rounded-4" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <div class="rounded-circle bg-warning bg-opacity-10 p-3 d-inline-block mb-3">
                                    <i class="bi bi-envelope-paper-fill fs-1" style="color: #fdcb6e;"></i>
                                </div>
                                <h2 class="h4 fw-bold mb-1" style="color: #2d3436;">Reset Password</h2>
                                <p class="text-muted small">Kami akan mengirimkan email untuk mereset password Anda</p>
                            </div>

                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-secondary">
                                        <i class="bi bi-envelope-fill me-1"></i> Email Address
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-envelope text-warning"></i>
                                        </span>
                                        <input type="email" name="q" class="form-control border-start-0 ps-0" 
                                               placeholder="Masukkan email Anda" autocomplete="off" required>
                                    </div>
                                    <div class="invalid-feedback">Email wajib diisi</div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-warning btn-lg rounded-pill py-2 fw-semibold" 
                                            style="box-shadow: 0 4px 15px rgba(253, 203, 110, 0.3);">
                                        <i class="bi bi-send me-2"></i> Kirim Reset Link
                                    </button>
                                    <button type="button" onclick="myFunction()" 
                                            class="btn btn-outline-secondary rounded-pill py-2">
                                        <i class="bi bi-arrow-left me-2"></i> Kembali ke Login
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .form-control:focus {
        border-color: #6c5ce7;
        box-shadow: 0 0 0 0.2rem rgba(108, 92, 231, 0.15);
    }
    
    .form-check-input:checked {
        background-color: #6c5ce7;
        border-color: #6c5ce7;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 92, 231, 0.4) !important;
        transition: all 0.3s ease;
    }
    
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(253, 203, 110, 0.4) !important;
        transition: all 0.3s ease;
    }
    
    .input-group-text {
        background: white;
        border-right: none;
    }
    
    .form-control {
        border-left: none;
    }
    
    .form-control:focus + .input-group-text {
        border-color: #6c5ce7;
    }
    
    .card {
        transition: transform 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
</style>

<script>
function myFunction() {
    var x = document.getElementById("RecoverPasswordForm");
    var y = document.getElementById("CustomerLoginForm");
    if (x.style.display === "none") {
        x.style.display = "block";
        y.style.display = "none";
    } else {
        x.style.display = "none";
        y.style.display = "block";
    }
}

function togglePassword(btn) {
    var input = btn.parentElement.querySelector('input[type="password"]');
    var icon = btn.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.className = "bi bi-eye-slash";
    } else {
        input.type = "password";
        icon.className = "bi bi-eye";
    }
}

// Bootstrap validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<!-- Bootstrap Icons (tambahkan di layout utama jika belum ada) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
@endsection