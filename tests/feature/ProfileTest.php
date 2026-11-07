<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ProfileTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';
    protected $DBGroup   = 'tests';
    protected $seed      = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetServices();
        $this->mockSession();
    }

    public function testUserCanUpdateOwnProfile(): void
    {
        $result = $this->asUser('petugas')->post('profile', [
            'csrf_token' => $this->csrfToken(),
            'full_name'  => 'Petugas Ubah Profil',
            'email'      => 'petugas.ubah@yayasan.test',
            'phone'      => '08123456789',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('success');

        $user = model(UserModel::class)->find($this->userId('petugas'));

        $this->assertSame('Petugas Ubah Profil', $user->full_name);
        $this->assertSame('petugas.ubah@yayasan.test', $user->email);
    }

    public function testProfileUpdateRejectsEmailOfAnotherUser(): void
    {
        $result = $this->asUser('petugas')->post('profile', [
            'csrf_token' => $this->csrfToken(),
            'full_name'  => 'Petugas Donasi',
            'email'      => 'admin@yayasan.test',
            'phone'      => '',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('errors');
        $this->assertSame('petugas@yayasan.test', model(UserModel::class)->find($this->userId('petugas'))->email);
    }

    public function testPasswordChangeRejectsWrongCurrentPassword(): void
    {
        $result = $this->asUser('petugas')->post('profile/password', [
            'csrf_token'           => $this->csrfToken(),
            'current_password'     => 'KataSandiSalah#1',
            'new_password'         => 'Baru#12345',
            'new_password_confirm' => 'Baru#12345',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('error', 'Kata sandi saat ini tidak sesuai.');

        $user = model(UserModel::class)->find($this->userId('petugas'));
        $this->assertTrue(password_verify($this->demoPassword('seed.petugasPassword'), $user->password_hash));
    }

    public function testUserCanChangePassword(): void
    {
        $currentPassword = $this->demoPassword('seed.petugasPassword');

        $result = $this->asUser('petugas')->post('profile/password', [
            'csrf_token'           => $this->csrfToken(),
            'current_password'     => $currentPassword,
            'new_password'         => 'Baru#12345',
            'new_password_confirm' => 'Baru#12345',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('success');

        $user = model(UserModel::class)->find($this->userId('petugas'));

        $this->assertTrue(password_verify('Baru#12345', $user->password_hash));
        $this->assertFalse(password_verify($currentPassword, $user->password_hash));

        $audit = $this->db->table('audit_logs')
            ->where('action', 'profile.password_changed')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();

        $this->assertNotNull($audit);
        $this->assertStringNotContainsString('Baru#12345', (string) $audit->new_values);
    }

    public function testPasswordChangeRejectsSamePassword(): void
    {
        $currentPassword = $this->demoPassword('seed.petugasPassword');

        $result = $this->asUser('petugas')->post('profile/password', [
            'csrf_token'           => $this->csrfToken(),
            'current_password'     => $currentPassword,
            'new_password'         => $currentPassword,
            'new_password_confirm' => $currentPassword,
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('error', 'Kata sandi baru harus berbeda dari kata sandi saat ini.');
    }

    public function testGuestCannotUpdateProfile(): void
    {
        $result = $this->withSession([])->post('profile', [
            'csrf_token' => $this->csrfToken(),
            'full_name'  => 'Penyusup',
            'email'      => 'penyusup@yayasan.test',
            'phone'      => '',
        ]);

        $result->assertRedirectTo(site_url('login'));
    }

    private function asUser(string $username): self
    {
        $this->resetServices();
        $this->mockSession();

        return $this->withSession(['auth_user_id' => $this->userId($username)]);
    }

    private function demoPassword(string $envKey): string
    {
        $password = (string) env($envKey, '');

        $this->assertNotSame('', $password, 'Variabel environment ' . $envKey . ' belum diatur.');

        return $password;
    }

    private function csrfToken(): string
    {
        return (string) service('security')->getHash();
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

        return (int) $row->id;
    }
}
