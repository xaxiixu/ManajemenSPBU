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

        .stat-card-link {
            text-decoration: none;
            border: 2px solid transparent;
            transition: border-color .15s, transform .15s;
        }

        .stat-card-link:hover {
            border-color: var(--spbu-red-lt);
        }

        .stat-card-active {
            border-color: var(--spbu-red);
        }

        /* Badge role */
        .badge-manager  { background: #1a1a2e; color: #fff; }
        .badge-pengawas { background: var(--spbu-red); color: #fff; }
        .badge-petugas  { background: #16a085; color: #fff; }

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
        @php $role = auth()->user()->role; @endphp

        {{-- UMUM: semua kecuali petugas --}}
        @if(in_array($role, ['manager', 'pengawas']))
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
        @endif

        {{-- OPERASIONAL: petugas --}}
        @if($role === 'petugas')
        <div class="nav-section-label mt-2">Operasional</div>
        <div class="nav-item">
            <a href="{{ route('presensi.index') }}"
                class="{{ request()->routeIs('presensi.*') ? 'active' : '' }}">
                <i class="bi bi-person-check-fill"></i> Presensi
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('pengajuan-shift.index') }}"
                class="{{ request()->routeIs('pengajuan-shift.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-repeat"></i> Pengajuan Shift
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('lembur.index') }}"
                class="{{ request()->routeIs('lembur.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Pengajuan Lembur
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('gaji-saya.index') }}"
                class="{{ request()->routeIs('gaji-saya.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Gaji Saya
            </a>
        </div>
        @endif

        {{-- OPERASIONAL: pengawas/manager --}}
        @if(in_array($role, ['manager', 'pengawas']))
        <div class="nav-section-label mt-2">Operasional</div>
        <div class="nav-item">
            <a href="{{ route('penjualan-bbm.index') }}"
                class="{{ request()->routeIs('penjualan-bbm.*') ? 'active' : '' }}">
                <i class="bi bi-fuel-pump"></i> Penjualan BBM
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('pembelian-bbm.index') }}"
                class="{{ request()->routeIs('pembelian-bbm.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Pembelian BBM
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('tangki-bbm.index') }}"
                class="{{ request()->routeIs('tangki-bbm.*') ? 'active' : '' }}">
                <i class="bi bi-droplet-half"></i> Stok Tangki
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('pengeluaran.index') }}"
                class="{{ request()->routeIs('pengeluaran.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Pengeluaran Operasional
            </a>
        </div>
        @endif

        {{-- MASTER DATA: Manager --}}
        @if($role === 'manager')
        <div class="nav-section-label mt-2">Master Data</div>
        <div class="nav-item">
            <a href="{{ route('coa.index') }}"
                class="{{ request()->routeIs('coa.*') ? 'active' : '' }}">
                <i class="bi bi-list-columns-reverse"></i> COA
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('master-bbm.index') }}"
                class="{{ request()->routeIs('master-bbm.*') ? 'active' : '' }}">
                <i class="bi bi-fuel-pump-fill"></i> Master BBM
            </a>
        </div>
        @endif

        {{-- TRANSAKSI: Manager --}}
        @if($role === 'manager')
        <div class="nav-section-label mt-2">Transaksi</div>
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

        {{-- MANAJEMEN: Pengawas + Manager --}}
        @if(in_array($role, ['manager', 'pengawas']))
        <div class="nav-section-label mt-2">Manajemen</div>
        <div class="nav-item">
            <a href="{{ route('petugas.index') }}"
                class="{{ request()->routeIs('petugas.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge-fill"></i> Data Petugas
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('jadwal-shift.index') }}"
                class="{{ request()->routeIs('jadwal-shift.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-week"></i> Jadwal Shift
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('approval.index') }}"
                class="{{ request()->routeIs('approval.*') ? 'active' : '' }}">
                <i class="bi bi-check2-square"></i> Approval
            </a>
        </div>
        @endif

        {{-- PAYROLL: Manager --}}
        @if($role === 'manager')
        <div class="nav-section-label mt-2">Payroll</div>
        <div class="nav-item">
            <a href="{{ route('penggajian.index') }}"
                class="{{ request()->routeIs('penggajian.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> Penggajian
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('pengaturan-gaji.edit') }}"
                class="{{ request()->routeIs('pengaturan-gaji.*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> Pengaturan Gaji
            </a>
        </div>
        @endif

        {{-- PENGATURAN: Manager --}}
        @if($role === 'manager')
        <div class="nav-section-label mt-2">Pengaturan</div>
        <div class="nav-item">
            <a href="{{ route('users.index') }}"
                class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Manajemen User
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('saldo-awal.index') }}"
                class="{{ request()->routeIs('saldo-awal.*') ? 'active' : '' }}">
                <i class="bi bi-piggy-bank-fill"></i> Saldo Awal
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
        <div class="dropdown">
            <button class="btn btn-link text-dark position-relative p-0 me-1" type="button"
                id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:1.2rem; line-height:1;">
                <i class="bi bi-bell-fill"></i>
                <span id="notifBadge" class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"
                    style="font-size:.6rem; display:none;">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0" style="width:320px; max-height:400px; overflow-y:auto;" aria-labelledby="notifDropdown">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <strong class="small">Notifikasi</strong>
                    <form id="formBacaSemua" action="{{ route('notifikasi.baca-semua') }}" method="POST" class="mb-0">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">Tandai semua dibaca</button>
                    </form>
                </div>
                <div id="notifList">
                    <div class="text-center text-muted small py-3">Memuat...</div>
                </div>
            </div>
        </div>
        <span class="topbar-date">
            <i class="bi bi-calendar3 me-1"></i>
            <span id="currentDate"></span>
        </span>
        @php
            $roleBadge = match(auth()->user()->role) {
                'manager'  => 'badge-manager',
                'petugas'  => 'badge-petugas',
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

    // Notifikasi
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function muatNotifikasi() {
        fetch('{{ route('notifikasi.data') }}')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('notifBadge');
                const list = document.getElementById('notifList');

                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }

                if (data.items.length === 0) {
                    list.innerHTML = '<div class="text-center text-muted small py-3">Belum ada notifikasi</div>';
                    return;
                }

                list.innerHTML = data.items.map(item => `
                    <a href="/notifikasi/${item.id}/buka" class="d-block text-decoration-none text-dark px-3 py-2 border-bottom ${item.dibaca ? '' : 'bg-light'}">
                        <div class="small ${item.dibaca ? '' : 'fw-semibold'}">${escapeHtml(item.pesan)}</div>
                        <div class="text-muted" style="font-size:.75rem;">${escapeHtml(item.waktu)}</div>
                    </a>
                `).join('');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        muatNotifikasi();
        setInterval(muatNotifikasi, 30000);
    });
</script>

@stack('scripts')
</body>
</html>