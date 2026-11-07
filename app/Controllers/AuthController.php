<?php

namespace App\Controllers;

use Config\Auth as AuthConfig;

class AuthController extends BaseController
{
    public function login()
    {
        return view('auth/login', [
            'title' => 'Masuk',
        ]);
    }

    public function attemptLogin()
    {
        if (! $this->validate('login')) {
            return redirect()->to(url_to('login'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $login    = trim((string) $this->request->getPost('login'));
        $password = (string) $this->request->getPost('password');
        $config   = config(AuthConfig::class);
        $throttler = service('throttler');
        $throttleKey = 'login_' . md5(strtolower($login) . '|' . $this->request->getIPAddress());

        if (! $throttler->check($throttleKey, $config->maxAttempts, $config->lockoutSeconds)) {
            service('auditLogger')->log(
                'auth.locked',
                'Percobaan login diblokir sementara untuk identitas: ' . $login,
            );

            return redirect()->to(url_to('login'))
                ->withInput()
                ->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.');
        }

        $auth = service('auth');

        if (! $auth->attempt($login, $password)) {
            service('auditLogger')->log(
                'auth.login_failed',
                'Percobaan login gagal untuk identitas: ' . $login,
            );

            $message = $auth->getFailureReason() === 'inactive'
                ? 'Akun Anda tidak aktif. Hubungi administrator.'
                : 'Username/email atau kata sandi salah.';

            return redirect()->to(url_to('login'))
                ->withInput()
                ->with('error', $message);
        }

        $throttler->remove($throttleKey);

        $user = $auth->user();

        service('auditLogger')->log(
            'auth.login',
            'Berhasil masuk ke aplikasi.',
            'user',
            $auth->id(),
        );

        $session  = session();
        $intended = $session->get('redirect_url');
        $session->remove('redirect_url');

        $target = is_string($intended) && $intended !== '' && str_starts_with($intended, base_url())
            ? $intended
            : url_to('dashboard');

        return redirect()->to($target)
            ->with('success', 'Selamat datang, ' . ($user->full_name ?? $user->username) . '.');
    }

    public function logout()
    {
        $auth = service('auth');
        $user = $auth->user();

        if ($user !== null) {
            service('auditLogger')->log(
                'auth.logout',
                'Keluar dari aplikasi.',
                'user',
                (int) $user->id,
            );
        }

        $auth->logout();

        return redirect()->to(url_to('login'))
            ->with('success', 'Anda telah keluar dari aplikasi.');
    }
}
