<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Validation\DonaturRules;
use App\Validation\ProfileRules;
use App\Validation\RoleRules;
use App\Validation\UserRules;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ValidationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';
    protected $DBGroup   = 'tests';
    protected $seed      = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetServices();
    }

    public function testValidUserPayloadPasses(): void
    {
        $result = $this->validateUser($this->validUserPayload());

        $this->assertSame([], service('validation')->getErrors());
        $this->assertTrue($result);
    }

    public function testDuplicateUsernameIsRejected(): void
    {
        $payload = $this->validUserPayload(['username' => 'admin']);

        $this->assertFalse($this->validateUser($payload));
        $this->assertArrayHasKey('username', service('validation')->getErrors());
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $payload = $this->validUserPayload(['email' => 'admin@yayasan.test']);

        $this->assertFalse($this->validateUser($payload));
        $this->assertArrayHasKey('email', service('validation')->getErrors());
    }

    public function testWeakPasswordIsRejected(): void
    {
        $payload = $this->validUserPayload([
            'password'         => 'password',
            'password_confirm' => 'password',
        ]);

        $this->assertFalse($this->validateUser($payload));
        $this->assertArrayHasKey('password', service('validation')->getErrors());
    }

    public function testPasswordConfirmationMustMatch(): void
    {
        $payload = $this->validUserPayload([
            'password'         => 'Rahasia#123',
            'password_confirm' => 'Rahasia#124',
        ]);

        $this->assertFalse($this->validateUser($payload));
        $this->assertArrayHasKey('password_confirm', service('validation')->getErrors());
    }

    public function testInvalidPhoneIsRejected(): void
    {
        $payload = $this->validUserPayload(['phone' => 'telepon-saya']);

        $this->assertFalse($this->validateUser($payload));
        $this->assertArrayHasKey('phone', service('validation')->getErrors());
    }

    public function testUnknownRoleIsRejected(): void
    {
        $payload = $this->validUserPayload(['role_id' => 999999]);

        $this->assertFalse($this->validateUser($payload));
        $this->assertArrayHasKey('role_id', service('validation')->getErrors());
    }

    public function testUpdateRulesAllowKeepingOwnUsernameAndEmail(): void
    {
        $adminId = $this->userId('admin');
        $payload = $this->validUserPayload([
            'password'         => '',
            'password_confirm' => '',
        ]);

        $this->assertTrue($this->validateUser($payload, $adminId));
    }

    public function testDuplicateRoleSlugIsRejected(): void
    {
        $validation = service('validation');
        $validation->setRules((new RoleRules())->store());

        $this->assertFalse($validation->run([
            'name'        => 'Admin Baru',
            'slug'        => 'admin',
            'description' => '',
        ]));
        $this->assertArrayHasKey('slug', $validation->getErrors());
    }

    public function testProfileRulesAllowOwnEmailButRejectAnotherUsersEmail(): void
    {
        $adminId = $this->userId('admin');
        $rules   = (new ProfileRules())->update($adminId);
        $validation = service('validation');

        $validation->setRules($rules);
        $this->assertTrue($validation->run([
            'full_name' => 'Administrator Yayasan',
            'email'     => 'admin@yayasan.test',
            'phone'     => '',
        ]));

        $validation->setRules($rules);
        $this->assertFalse($validation->run([
            'full_name' => 'Administrator Yayasan',
            'email'     => 'petugas@yayasan.test',
            'phone'     => '',
        ]));
        $this->assertArrayHasKey('email', $validation->getErrors());
    }

    public function testLoginValidationRequiresBothFields(): void
    {
        $validation = service('validation');
        $validation->setRuleGroup('login');

        $this->assertFalse($validation->run([]));
        $this->assertArrayHasKey('login', $validation->getErrors());
        $this->assertArrayHasKey('password', $validation->getErrors());
    }

    public function testDonorRulesAllowEmptyEmailAndPhone(): void
    {
        $validation = service('validation');
        $validation->setRules((new DonaturRules())->store());

        $this->assertTrue($validation->run($this->validDonorPayload([
            'email' => '',
            'phone' => '',
        ])));
        $this->assertSame([], $validation->getErrors());
    }

    public function testDonorRulesRejectInvalidEmailPhoneAndOverlongFields(): void
    {
        $validation = service('validation');
        $validation->setRules((new DonaturRules())->store());

        $this->assertFalse($validation->run($this->validDonorPayload([
            'email'   => 'bukan-email',
            'phone'   => 'telepon-saya',
            'name'    => str_repeat('A', 151),
            'address' => str_repeat('B', 1001),
            'notes'   => str_repeat('C', 2001),
        ])));

        $errors = $validation->getErrors();

        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('address', $errors);
        $this->assertArrayHasKey('notes', $errors);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateUser(array $payload, ?int $ignoreId = null): bool
    {
        $rules = $ignoreId === null
            ? (new UserRules())->store()
            : (new UserRules())->update($ignoreId);

        $validation = service('validation');
        $validation->setRules($rules);

        return $validation->run($payload);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validDonorPayload(array $overrides = []): array
    {
        return array_merge([
            'name'         => 'Donatur Uji',
            'type'         => 'individual',
            'email'        => 'donatur.uji@example.test',
            'phone'        => '081234567890',
            'address'      => 'Jl. Uji No. 1',
            'notes'        => '',
            'is_anonymous' => '0',
            'status'       => 'active',
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validUserPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name'        => 'Pengguna Baru',
            'username'         => 'pengguna_baru',
            'email'            => 'pengguna.baru@yayasan.test',
            'phone'            => '081234567890',
            'role_id'          => $this->roleId('distribusi'),
            'status'           => 'active',
            'password'         => 'Rahasia#123',
            'password_confirm' => 'Rahasia#123',
        ], $overrides);
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

        return (int) $row->id;
    }

    private function roleId(string $slug): int
    {
        $row = $this->db->table('roles')->select('id')->where('slug', $slug)->get()->getRow();

        $this->assertNotNull($row, 'Role uji tidak ditemukan: ' . $slug);

        return (int) $row->id;
    }
}
