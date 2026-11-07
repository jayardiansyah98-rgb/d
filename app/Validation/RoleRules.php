<?php

namespace App\Validation;

class RoleRules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function store(): array
    {
        return [
            'name' => [
                'label' => 'Nama role',
                'rules' => 'required|min_length[3]|max_length[80]',
            ],
            'slug' => [
                'label' => 'Slug',
                'rules' => 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[roles.slug]',
            ],
            'description' => [
                'label' => 'Deskripsi',
                'rules' => 'permit_empty|max_length[255]',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function update(int $id): array
    {
        $rules = $this->store();

        $rules['slug']['rules'] = 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[roles.slug,id,' . $id . ']';

        return $rules;
    }
}
