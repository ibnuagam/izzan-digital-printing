<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email'))), 'name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\s-]{8,30}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['email.unique' => 'Email sudah terdaftar. Silakan masuk.', 'phone.regex' => 'Nomor telepon harus berisi 8–30 karakter angka atau tanda telepon.', 'password.confirmed' => 'Konfirmasi kata sandi tidak sama.'], ['name' => 'nama lengkap', 'phone' => 'nomor telepon', 'password' => 'kata sandi']);
        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        $user->password = $data['password'];
        // Peran ditentukan server; input role dari form tidak pernah digunakan.
        $user->role = 'pelanggan';
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('pelanggan.dashboard')->with('status', 'Pendaftaran berhasil. Selamat datang di Izzan Digital Printing!');
    }
}
