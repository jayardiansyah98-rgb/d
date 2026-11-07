<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * Validasi formulir login.
     *
     * @var array<string, array<string, string>>
     */
    public array $login = [
        'login' => [
            'label' => 'Username atau email',
            'rules' => 'required|max_length[254]',
        ],
        'password' => [
            'label' => 'Kata sandi',
            'rules' => 'required|max_length[72]',
        ],
    ];

    /**
     * Validasi perubahan kata sandi dari halaman profil.
     *
     * @var array<string, array<string, string>>
     */
    public array $password = [
        'current_password' => [
            'label' => 'Kata sandi saat ini',
            'rules' => 'required|max_length[72]',
        ],
        'new_password' => [
            'label' => 'Kata sandi baru',
            'rules' => 'required|min_length[8]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
            'errors' => [
                'regex_match' => 'Kata sandi baru harus mengandung huruf besar, huruf kecil, dan angka.',
            ],
        ],
        'new_password_confirm' => [
            'label' => 'Konfirmasi kata sandi baru',
            'rules' => 'required|matches[new_password]',
        ],
    ];
}
