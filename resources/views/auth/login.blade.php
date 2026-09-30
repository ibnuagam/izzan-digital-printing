@extends('layouts.app')
@section('title', 'Masuk | Izzan Digital Printing')
@section('content')
<div class="login-shell"><section class="login-story"><a class="brand" href="{{ route('login') }}"><span class="brand-mark"><x-icon name="print"/></span><span>IZZAN<span class="brand-caption">DIGITAL PRINTING</span></span></a>
<div class="story-body"><span class="eyebrow">RUANG KERJA PERCETAKAN</span><h1>Setiap pesanan.<br>Lebih terarah.</h1><p>Satu tempat untuk mengelola pemesanan dan memahami pola kebutuhan percetakan.</p>
<div class="print-art" aria-hidden="true"><div class="paper paper-back"></div><div class="paper paper-front"><span class="paper-word">MAKE<br>IT<br>PRINT.</span><div class="color-swatches"><i></i><i></i><i></i><i></i></div><span class="paper-line"></span></div><span class="art-label">IDE → CETAK → HASIL</span></div></div>
<p class="story-footer">Izzan Digital Printing · Sistem informasi percetakan</p></section>
<section class="login-form-panel"><div class="login-form-wrap"><span class="section-kicker">SELAMAT DATANG KEMBALI</span><h2>Masuk ke ruang kerja</h2><p class="intro">Gunakan akun Anda untuk melanjutkan.</p>
<form method="post" action="{{ route('login.store') }}">@csrf
<label for="email">Alamat email</label><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@contoh.com" required autocomplete="username" autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
@error('email')<p class="error" id="email-error" role="alert">{{ $message }}</p>@enderror
<label for="password">Kata sandi</label><div class="password-field"><input id="password" type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button type="button" id="password-toggle" aria-controls="password" aria-pressed="false">Tampilkan</button></div>
@error('password')<p class="error" id="password-error" role="alert">{{ $message }}</p>@enderror
<button class="button primary login-submit" type="submit">Masuk <x-icon name="arrow"/></button>
</form><p class="auth-switch">Belum memiliki akun? <a class="text-link" href="{{ route('register') }}">Daftar sebagai pelanggan</a></p><div class="login-help"><x-icon name="lock"/><p>Akses diberikan sesuai peran akun Anda. Pelanggan baru dapat mendaftar melalui tautan di atas.</p></div>
</div><div class="login-bottom">Kelola dengan rapi. Cetak dengan percaya diri.</div></section></div>
@endsection
