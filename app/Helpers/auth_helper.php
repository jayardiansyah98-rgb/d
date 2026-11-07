<?php

if (! function_exists('current_user')) {
    /**
     * Pengguna aktif yang sedang login, atau null.
     */
    function current_user(): ?object
    {
        return service('auth')->user();
    }
}

if (! function_exists('can')) {
    /**
     * Periksa hak akses pengguna yang sedang login.
     */
    function can(string $permission): bool
    {
        return service('auth')->can($permission);
    }
}

if (! function_exists('user_initials')) {
    /**
     * Inisial nama untuk avatar.
     */
    function user_initials(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '?';
        }

        $words    = preg_split('/\s+/u', $name) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials !== '' ? $initials : '?';
    }
}

if (! function_exists('role_badge_class')) {
    /**
     * Kelas badge Bootstrap untuk sebuah slug role.
     */
    function role_badge_class(?string $slug): string
    {
        return match ($slug) {
            'admin'      => 'text-bg-danger',
            'keuangan'   => 'text-bg-primary',
            'distribusi' => 'text-bg-success',
            'pimpinan'   => 'text-bg-warning',
            default      => 'text-bg-secondary',
        };
    }
}
