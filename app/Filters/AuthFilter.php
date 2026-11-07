<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Auth as AuthConfig;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = service('auth');

        if (! $auth->check()) {
            $session = session();
            $userId  = $session->get(config(AuthConfig::class)->sessionKey);

            // Sesi masih menyimpan ID, tetapi pengguna sudah tidak aktif
            // atau diarsipkan: bersihkan sesi agar tidak mencoba berulang.
            if (is_numeric($userId)) {
                $auth->logout();

                return redirect()->to(url_to('login'))
                    ->with('error', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
            }

            $session->set('redirect_url', current_url());

            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                    ->setJSON(['message' => 'Anda harus masuk terlebih dahulu.']);
            }

            return redirect()->to(url_to('login'))
                ->with('error', 'Silakan masuk terlebih dahulu.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
