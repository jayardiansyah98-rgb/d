<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $permission = $arguments[0] ?? null;

        if ($permission === null || $permission === '') {
            return $request->isAJAX()
                ? service('response')->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)->setJSON(['message' => 'Akses ditolak.'])
                : redirect()->to(url_to('profile.edit'))->with('error', 'Akses ditolak.');
        }

        if (service('auth')->can($permission)) {
            return null;
        }

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON(['message' => 'Anda tidak memiliki hak akses untuk tindakan ini.']);
        }

        return redirect()->to(url_to('profile.edit'))
            ->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
