<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#123c3e"><title>@yield('title', 'Dashboard | Izzan Digital Printing')</title>
<link rel="stylesheet" href="{{ asset('css/izzan.css') }}">
<script src="{{ asset('js/izzan.js') }}" defer></script>
</head>
<body class="@auth workspace @else auth-page @endauth">
<a class="skip-link" href="#main-content">Langsung ke konten</a>
@auth
@php
$role = auth()->user()->role;
$planned = match($role) {
    'admin' => [['orders','Pesanan'],['print','Layanan'],['box','Bahan'],['wallet','Pembayaran'],['chat','Chat pesanan'],['users','Pengguna'],['chart','Analisis K-Means']],
    'manajer' => [['chart','Analisis pemesanan'],['box','Kebutuhan bahan'],['orders','Laporan']],
    default => [['print','Katalog layanan'],['orders','Pesanan saya'],['wallet','Pembayaran'],['chat','Chat pesanan']],
};
@endphp
<aside class="sidebar" id="sidebar">
<a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark"><x-icon name="print"/></span><span>IZZAN<span class="brand-caption">DIGITAL PRINTING</span></span></a>
<div class="workspace-label">RUANG KERJA {{ strtoupper($role) }}</div>
<nav aria-label="Navigasi utama"><a class="nav-item {{ request()->routeIs('*.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('*.dashboard')) aria-current="page" @endif><x-icon/>Dashboard</a>
<div class="nav-heading">MENU APLIKASI</div>
@foreach($planned as [$icon, $label])
@php $target = match(true) { in_array($role,['admin','pelanggan']) && $label==='Chat pesanan' => $role.'.chat.index', $role==='admin' && $label==='Pembayaran' => 'admin.payments.index', $role==='pelanggan' && $label==='Pembayaran' => 'pelanggan.payments.index', $role==='admin' && $label==='Pesanan' => 'admin.orders.index', $role==='pelanggan' && $label==='Pesanan saya' => 'pelanggan.orders.index', $role==='admin' && $label==='Layanan' => 'admin.services.index', $role==='admin' && $label==='Bahan' => 'admin.materials.index', $role==='pelanggan' && $label==='Katalog layanan' => 'pelanggan.catalog', default => null }; @endphp
@if($target)<a class="nav-item {{ request()->routeIs(str_replace('.index','.*',$target)) ? 'active' : '' }}" href="{{ route($target) }}" @if(request()->routeIs(str_replace('.index','.*',$target))) aria-current="page" @endif><x-icon :name="$icon"/><span>{{ $label }}</span></a>
@else
<span class="nav-item planned" aria-disabled="true"><x-icon :name="$icon"/><span>{{ $label }}</span><span class="soon">Nanti</span></span>
@endif
@endforeach
</nav>
<div class="sidebar-note"><span class="small-dot"></span><strong>Pengembangan bertahap</strong><p>Menu berikutnya aktif setelah fiturnya selesai dibuat.</p></div>
<div class="sidebar-user"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div><strong>{{ auth()->user()->name }}</strong><span>{{ ucfirst($role) }}</span></div></div>
</aside>
<button class="sidebar-overlay" id="sidebar-overlay" aria-label="Tutup navigasi" hidden></button>
<div class="app-body"><header class="topbar"><div class="topbar-left"><button class="icon-button mobile-menu" id="menu-toggle" aria-label="Buka navigasi" aria-expanded="false" aria-controls="sidebar"><x-icon name="menu"/></button><span class="breadcrumb">Ruang kerja <span>/</span> <strong>@yield('page-title','Dashboard')</strong></span></div><div class="topbar-right"><span class="role-chip">{{ ucfirst($role) }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit"><x-icon name="logout"/><span>Keluar</span></button></form></div></header>
<main id="main-content" class="dashboard-main">@if(session('status'))<div class="flash-success" role="status"><x-icon name="check"/>{{ session('status') }}</div>@endif @yield('content')</main>
<footer class="app-footer">© {{ date('Y') }} Izzan Digital Printing<span>Sistem pemesanan & analisis</span></footer></div>
@else
<main id="main-content">@yield('content')</main>
@endauth
</body></html>
