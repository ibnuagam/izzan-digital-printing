@extends('layouts.app')
@section('title','Daftar pelanggan | Izzan Digital Printing')
@section('content')
<div class="login-shell registration-shell"><section class="login-story"><a class="brand" href="{{ route('login') }}"><span class="brand-mark"><x-icon name="print"/></span><span>IZZAN<span class="brand-caption">DIGITAL PRINTING</span></span></a>
<div class="story-body"><span class="eyebrow">UNTUK KEBUTUHAN CETAK ANDA</span><h1>Ide Anda.<br>Mulai di sini.</h1><p>Buat akun pelanggan untuk mengenal layanan percetakan Izzan dan mempersiapkan kebutuhan cetak Anda.</p><div class="registration-benefits"><div><x-icon name="check"/>Jelajahi katalog layanan</div><div><x-icon name="check"/>Ruang kerja khusus pelanggan</div><div><x-icon name="lock"/>Akun dengan kata sandi pribadi</div></div></div><p class="story-footer">Izzan Digital Printing · Akun pelanggan</p></section>
<section class="login-form-panel"><div class="login-form-wrap"><span class="section-kicker">AKUN PELANGGAN BARU</span><h2>Daftar akun</h2><p class="intro">Sudah memiliki akun? <a class="text-link" href="{{ route('login') }}">Masuk di sini</a></p>
<form method="post" action="{{ route('register.store') }}">@csrf
@foreach(['name'=>['Nama lengkap','text','name','Nama lengkap Anda'],'email'=>['Alamat email','email','email','nama@contoh.com'],'phone'=>['Nomor telepon / WhatsApp','tel','tel','Contoh: 081234567890']] as $field => [$label,$type,$autocomplete,$placeholder])
<label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" value="{{ old($field) }}" placeholder="{{ $placeholder }}" maxlength="{{ $field==='phone' ? 30 : ($field==='name' ? 150 : 255) }}" required @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
@error($field)<p class="error" id="{{ $field }}-error" role="alert">{{ $message }}</p>@enderror
@endforeach
<label for="password">Kata sandi</label><div class="password-field"><input id="password" type="password" name="password" autocomplete="new-password" minlength="8" placeholder="Buat kata sandi" required aria-describedby="password-hint @error('password') password-error @enderror"><button type="button" id="password-toggle" aria-controls="password" aria-pressed="false">Tampilkan</button></div><p class="field-hint" id="password-hint">Minimal 8 karakter, mengandung huruf dan angka.</p>
@error('password')<p class="error" id="password-error" role="alert">{{ $message }}</p>@enderror
<label for="password_confirmation">Konfirmasi kata sandi</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" placeholder="Ulangi kata sandi" required>
<button class="button primary login-submit" type="submit">Buat akun pelanggan <x-icon name="arrow"/></button>
</form><div class="login-help"><x-icon name="lock"/><p>Pendaftaran ini khusus pelanggan. Akun admin dan manajer tidak dibuat melalui formulir ini.</p></div></div></section></div>
@endsection
