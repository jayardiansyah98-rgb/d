<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi autentikasi aplikasi.
 */
class Auth extends BaseConfig
{
    /**
     * Jumlah maksimum percobaan login per kombinasi IP + identitas
     * dalam satu jendela waktu.
     */
    public int $maxAttempts = 5;

    /**
     * Lama jendela pembatasan percobaan login (detik).
     */
    public int $lockoutSeconds = 900;

    /**
     * Kunci session untuk menyimpan ID pengguna yang sedang masuk.
     */
    public string $sessionKey = 'auth_user_id';

    /**
     * Panjang minimum kata sandi.
     */
    public int $passwordMinLength = 8;

    /**
     * Panjang maksimum kata sandi (batas efektif bcrypt).
     */
    public int $passwordMaxLength = 72;
}
