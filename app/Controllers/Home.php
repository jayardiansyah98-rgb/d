<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if (service('auth')->check()) {
            return redirect()->to(url_to('dashboard'));
        }

        return redirect()->to(url_to('login'));
    }
}
