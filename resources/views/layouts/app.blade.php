<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SPBU Management</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    @stack('styles')

    <style>
        :root {
            --spbu-red:      #c0392b;
            --spbu-red-dk:   #a93226;
            --spbu-red-lt:   #f9e9e8;
            --sidebar-w:     260px;
            --sidebar-bg:    #1a1a2e;
            --sidebar-hover: rgba(255,255,255,0.07);
            --sidebar-active:#c0392b;
            --topbar-h:      60px;
            --body-bg:       #f4f6f9;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--body-bg);
            font-family: 'Segoe UI', sans-serif;
            font-size: 0.9rem;
            color: #333;
        }

        /* ── Sidebar ── */
        #sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .sidebar-brand .brand-icon {
            width: 40px; height: 40px;
            background: var(--spbu-red);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #fff;
            flex-shrink: 0;
        }

        .sidebar-brand .brand-text span {
            display: block;
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.2;
        }

        .sidebar-brand .brand-text small {
            color: rgba(255,255,255,0.45);
            font-size: 0.72rem;
        }

        .sidebar-nav {
            flex: 1;
            padding: 0.75rem 0;
        }

        .nav-section-label {
            padding: 0.6rem 1.25rem 0.3rem;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.25rem;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            position: relative;
            font-size: 0.875rem;
        }

        .nav-item a i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .nav-item a:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .nav-item a.active {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .nav-item a.active::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--spbu-red);
            border-radius: 0 2px 2px 0;
        }

        .sidebar-user {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-user .avatar {
            width: 36px; height: 36px;
            background: var(--spbu-red);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; color: #fff; font-weight: 700;
            flex-shrink: 0;
        }

        .sidebar-user .user-info span {
            display: block;
            color: #fff;
            font-size: 0.82rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .sidebar-user .user-info small {
            color: rgba(255,255,255,0.4);
            font-size: 0.72rem;
            text-transform: capitalize;
        }

        .sidebar-user .btn-logout {
            margin-left: auto;
            color: rgba(255,255,255,0.4);
            font-size: 1.1rem;
            background: none;
            border: none;
            padding: 0.25rem;
            cursor: pointer;
            transition: color 0.15s;
        }

        .sidebar-user .btn-logout:hover { color: #e74c3c; }

        /* ── Topbar ── */
        #topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            background: #fff;
            border-bottom: 1px solid #e8e8e8;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            gap: 1rem;
            z-index: 1030;
            transition: left 0.3s ease;
        }

        #sidebarToggle {
            background: none;
            border: none;
            font-size: 1.3rem;
            color: #555;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            display: none;
        }

        #sidebarToggle:hover { background: #f0f0f0; }

        .topbar-title {
            font-weight: 600;
            font-size: 1rem;
            color: #222;
        }

        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .topbar-date {
            font-size: 0.8rem;
            color: #888;
        }

        /* ── Main Content ── */
        #main-content {
            margin-left: var(--sidebar-w);
            margin-top: var(--topbar-h);
            padding: 1.75rem;
            min-height: calc(100vh - var(--topbar-h));
            transition: margin-left 0.3s ease;
        }

        /* Page header */
        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 0.25rem;
            color: #1a1a2e;
        }

        .page-header p {
            margin: 0;
            color: #888;
            font-size: 0.85rem;
        }

        /* Card */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #333;
        }

        /* Stat cards */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-card .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.1;
        }

        .stat-card .stat-label {
            font-size: 0.8rem;
            color: #888;
            margin-top: 0.15rem;
        }

        /* Badge role */
        .badge-manager  { background: #1a1a2e; color: #fff; }
        .badge-pengawas { background: var(--spbu-red); color: #fff; }
        .badge-it       { background: #2980b9; color: #fff; }

        /* Overlay mobile */
        #sidebarOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1035;
        }

        /* ── Responsive ── */
        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #sidebarOverlay.open { display: block; }
            #topbar { left: 0; }
            #main-content { margin-left: 0; }
            #sidebarToggle { display: block; }
        }

        @media (max-width: 575.98px) {
            #main-content { padding: 1rem; }
            .topbar-date { display: none; }
        }
    </style>
</head>
<body>

{{-- Overlay mobile --}}
<div id="sidebarOverlay" onclick="closeSidebar()"></div>

{{-- Sidebar --}}
<nav id="sidebar">

    {{-- Brand --}}
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-fuel-pump-fill"></i></div>
        <div class="brand-text">
            <span>SPBU Manager</span>
            <small>Management System</small>
        </div>
    </a>

    {{-- Navigation --}}
    <div class="sidebar-nav">

        {{-- UMUM: semua role --}}
        <div class="nav-section-label">Umum</div>
        <div class="nav-item">
            <a href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('laporan-laba-rugi.index') }}"
                class="{{ request()->routeIs('laporan-laba-rugi.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Laporan Laba & Rugi
            </a>
        </div>

        {{-- OPERASIONAL: semua role --}}
        <div class="nav-section-label mt-2">Operasional</div>
        <div class="nav-item">
            <a href="{{ route('presensi.index') }}"
                class="{{ request()->routeIs('presensi.*') ? 'active' : '' }}">
                <i class="bi bi-person-check-fill"></i> Presensi
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('pengeluaran.index') }}"
                class="{{ request()->routeIs('pengeluaran.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Pengeluaran Operasional
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('penjualan-bbm.index') }}"
                class="{{ request()->routeIs('penjualan-bbm.*') ? 'active' : '' }}">
                <i class="bi bi-fuel-pump"></i> Penjualan BBM
            </a>
        </div>

        {{-- AKUNTANSI: Manager + IT --}}
        @if(in_array(auth()->user()->role, ['manager', 'it']))
        <div class="nav-section-label mt-2">Akuntansi</div>
        <div class="nav-item">
            <a href="{{ route('coa.index') }}"
                class="{{ request()->routeIs('coa.*') ? 'active' : '' }}">
                <i class="bi bi-list-columns-reverse"></i> COA
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('jurnal-umum.index') }}"
                class="{{ request()->routeIs('jurnal-umum.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Jurnal Umum
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('buku-besar.index') }}"
                class="{{ request()->routeIs('buku-besar.*') ? 'active' : '' }}">
                <i class="bi bi-book-fill"></i> Buku Besar
            </a>
        </div>
        @endif

        {{-- PENGATURAN: IT full, Manager lihat petugas saja --}}
        @if(in_array(auth()->user()->role, ['manager', 'it']))
        <div class="nav-section-label mt-2">Pengaturan</div>
        @if(auth()->user()->role === 'it')
        <div class="nav-item">
            <a href="{{ route('users.index') }}"
                class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Manajemen User
            </a>
        </div>
        @endif
        <div class="nav-item">
            <a href="{{ route('petugas.index') }}"
                class="{{ request()->routeIs('petugas.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge-fill"></i> Data Petugas
            </a>
        </div>
        @endif

    </div>

    {{-- User info --}}
    <div class="sidebar-user">
        <div class="avatar">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="user-info">
            <span>{{ auth()->user()->name }}</span>
            <small>{{ ucfirst(auth()->user()->role) }}</small>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout" title="Keluar">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>

</nav>

{{-- Topbar --}}
<header id="topbar">
    <button id="sidebarToggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
    <div class="topbar-right">
        <span class="topbar-date">
            <i class="bi bi-calendar3 me-1"></i>
            <span id="currentDate"></span>
        </span>
        @php
            $roleBadge = match(auth()->user()->role) {
                'it'       => 'badge-it',
                'manager'  => 'badge-manager',
                default    => 'badge-pengawas',
            };
        @endphp
        <span class="badge rounded-pill {{ $roleBadge }} px-3 py-1">
            {{ ucfirst(auth()->user()->role) }}
        </span>
    </div>
</header>

{{-- Main Content --}}
<main id="main-content">

    {{-- Flash success --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Flash error --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        {{ session('error') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @yield('content')

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Current date
    const d = new Date();
    const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
    document.getElementById('currentDate').textContent = d.toLocaleDateString('id-ID', opts);

    // Sidebar toggle mobile
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('open');
    }
</script>

@stack('scripts')
</body>
</html>