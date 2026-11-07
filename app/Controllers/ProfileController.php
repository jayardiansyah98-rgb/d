<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Validation\ProfileRules;
use DomainException;
use RuntimeException;

class ProfileController extends BaseController
{
    public function edit()
    {
        return view('profile/edit', [
            'title' => 'Profil Saya',
            'user'  => $this->currentUserOrFail(),
        ]);
    }

    public function update()
    {
        $user = $this->currentUserOrFail();

        $rules = (new ProfileRules())->update((int) $user->id);

        if (! $this->validate($rules)) {
            return redirect()->to(url_to('profile.edit'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            service('userService')->updateProfile((int) $user->id, [
                'full_name' => trim((string) $this->request->getPost('full_name')),
                'email'     => trim((string) $this->request->getPost('email')),
                'phone'     => trim((string) $this->request->getPost('phone')),
            ]);
        } catch (DomainException $exception) {
            return redirect()->to(url_to('profile.edit'))->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal memperbarui profil: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('profile.edit'))->with('error', 'Profil gagal diperbarui.');
        }

        return redirect()->to(url_to('profile.edit'))->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword()
    {
        $user = $this->currentUserOrFail();

        if (! $this->validate('password')) {
            return redirect()->to(url_to('profile.edit'))
                ->with('errors', $this->validator->getErrors());
        }

        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword     = (string) $this->request->getPost('new_password');

        $freshUser = model(UserModel::class)->find((int) $user->id);

        if ($freshUser === null || ! password_verify($currentPassword, $freshUser->password_hash)) {
            return redirect()->to(url_to('profile.edit'))
                ->with('error', 'Kata sandi saat ini tidak sesuai.');
        }

        if (password_verify($newPassword, $freshUser->password_hash)) {
            return redirect()->to(url_to('profile.edit'))
                ->with('error', 'Kata sandi baru harus berbeda dari kata sandi saat ini.');
        }

        try {
            service('userService')->updatePassword((int) $user->id, $newPassword);
        } catch (RuntimeException $exception) {
            log_message('error', 'Gagal mengubah kata sandi: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(url_to('profile.edit'))->with('error', 'Kata sandi gagal diperbarui.');
        }

        return redirect()->to(url_to('profile.edit'))->with('success', 'Kata sandi berhasil diperbarui.');
    }

    private function currentUserOrFail(): object
    {
        $user = current_user();

        if ($user === null) {
            throw new \RuntimeException('Sesi pengguna tidak valid.');
        }

        return $user;
    }
}
