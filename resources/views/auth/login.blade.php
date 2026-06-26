<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SPBU Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --spbu-red:    #c0392b;
            --spbu-red-dk: #a93226;
            --spbu-dark:   #1a1a2e;
            --spbu-gray:   #f4f6f9;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--spbu-dark) 0%, #16213e 50%, #0f3460 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.35);
            overflow: hidden;
        }

        .login-header {
            background: var(--spbu-red);
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            color: #fff;
        }

        .login-header .logo-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
        }

        .login-header h1 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .login-header p {
            font-size: 0.82rem;
            opacity: 0.85;
            margin: 0;
        }

        .login-body {
            padding: 2rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 0.4rem;
        }

        .form-control {
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--spbu-red);
            box-shadow: 0 0 0 3px rgba(192,57,43,0.12);
        }

        .input-group .form-control {
            border-right: none;
        }

        .input-group .btn-outline-secondary {
            border: 1.5px solid #e0e0e0;
            border-left: none;
            border-radius: 0 8px 8px 0;
            background: #fafafa;
            color: #888;
        }

        .input-group .btn-outline-secondary:hover {
            background: #f0f0f0;
            color: #555;
        }

        .btn-login {
            background: var(--spbu-red);
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            width: 100%;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-login:hover {
            background: var(--spbu-red-dk);
            color: #fff;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login:disabled {
            background: #e0e0e0;
            cursor: not-allowed;
        }

        .form-check-input:checked {
            background-color: var(--spbu-red);
            border-color: var(--spbu-red);
        }

        .alert-danger {
            border-radius: 8px;
            font-size: 0.85rem;
            border: none;
            background: #fdf0ef;
            color: #c0392b;
        }

        .login-footer {
            text-align: center;
            padding: 1rem 2rem 1.5rem;
            font-size: 0.78rem;
            color: #aaa;
            border-top: 1px solid #f0f0f0;
        }

        /* Decorative circles */
        .bg-circle {
            position: fixed;
            border-radius: 50%;
            opacity: 0.05;
            background: #fff;
            pointer-events: none;
        }
        .bg-circle-1 { width: 300px; height: 300px; top: -80px; right: -80px; }
        .bg-circle-2 { width: 200px; height: 200px; bottom: -60px; left: -60px; }
    </style>
</head>
<body>
    <div class="bg-circle bg-circle-1"></div>
    <div class="bg-circle bg-circle-2"></div>

    <div class="login-wrapper">
        <div class="login-card">

            {{-- Header --}}
            <div class="login-header">
                <div class="logo-icon">
                    <i class="bi bi-fuel-pump-fill"></i>
                </div>
                <h1>SPBU Management</h1>
                <p>Sistem Manajemen Operasional SPBU</p>
            </div>

            {{-- Body --}}
            <div class="login-body">

                {{-- Error message --}}
                @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="border: 1.5px solid #e0e0e0; border-right: none; border-radius: 8px 0 0 8px;">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>
                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="email@spbu.com"
                                required
                                autofocus
                                style="border-left: none;"
                            >
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="border: 1.5px solid #e0e0e0; border-right: none; border-radius: 8px 0 0 8px;">
                                <i class="bi bi-lock text-muted"></i>
                            </span>
                            <input
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                                style="border-left: none; border-right: none; border-radius: 0;"
                            >
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Remember me --}}
                    <div class="mb-4 d-flex align-items-center justify-content-between">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label text-muted" for="remember" style="font-size:0.85rem;">
                                Ingat saya
                            </label>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn btn-login" id="btnLogin">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Masuk
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <div class="login-footer">
                &copy; {{ date('Y') }} SPBU Management System. All rights reserved.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon  = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });

        // Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
        });
    </script>
</body>
</html>
