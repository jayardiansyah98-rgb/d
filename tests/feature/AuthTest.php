<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthTest extends CIUnitTestCase
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

    public function testLoginPageIsAccessibleForGuests(): void
    {
        $result = $this->withSession([])->get('login');

        $result->assertOK();
        $result->assertSee('Masuk');
    }

    public function testProtectedPageRedirectsGuestsToLogin(): void
    {
        $result = $this->withSession([])->get('dashboard');

        $result->assertRedirectTo(site_url('login'));
    }

    public function testLoginWithoutCsrfTokenIsRejected(): void
    {
        $this->expectException(SecurityException::class);

        $this->withSession([])->post('login', [
            'login'    => 'admin',
            'password' => $this->demoPassword('seed.adminPassword'),
        ]);
    }

    public function testLoginWithValidCredentialsRedirectsToDashboard(): void
    {
        $result = $this->withSession([])->post('login', [
            'csrf_token' => $this->csrfToken(),
            'login'      => 'admin',
            'password'   => $this->demoPassword('seed.adminPassword'),
        ]);

        $result->assertRedirectTo(site_url('dashboard'));
        $result->assertSessionHas('success');
        $result->assertSessionHas('auth_user_id');
    }

    public function testLoginAcceptsEmailAddress(): void
    {
        $result = $this->withSession([])->post('login', [
            'csrf_token' => $this->csrfToken(),
            'login'      => 'admin@yayasan.test',
            'password'   => $this->demoPassword('seed.adminPassword'),
        ]);

        $result->assertRedirectTo(site_url('dashboard'));
    }

    public function testLoginWithWrongPasswordShowsGenericError(): void
    {
        $result = $this->withSession([])->post('login', [
            'csrf_token' => $this->csrfToken(),
            'login'      => 'admin',
            'password'   => 'salah-sekali',
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');
    }

    public function testInactiveUserCannotLogin(): void
    {
        $this->db->table('users')
            ->where('username', 'petugas')
            ->update(['status' => 'inactive']);

        $result = $this->withSession([])->post('login', [
            'csrf_token' => $this->csrfToken(),
            'login'      => 'petugas',
            'password'   => $this->demoPassword('seed.petugasPassword'),
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');
    }

    public function testLoginIsThrottledAfterRepeatedFailures(): void
    {
        $login = 'ratelimit-' . bin2hex(random_bytes(4)) . '@yayasan.test';

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $result = $this->withSession([])->post('login', [
                'csrf_token' => $this->csrfToken(),
                'login'      => $login,
                'password'   => 'salah-sekali',
            ]);

            $result->assertRedirectTo(site_url('login'));
        }

        $blocked = $this->withSession([])->post('login', [
            'csrf_token' => $this->csrfToken(),
            'login'      => $login,
            'password'   => 'salah-sekali',
        ]);

        $blocked->assertRedirectTo(site_url('login'));
        $blocked->assertSessionHas('error', 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.');
    }

    public function testLogoutRequiresAuthentication(): void
    {
        $result = $this->withSession([])->post('logout', [
            'csrf_token' => $this->csrfToken(),
        ]);

        $result->assertRedirectTo(site_url('login'));
    }

    public function testLogoutClearsSessionAndRedirectsToLogin(): void
    {
        $result = $this->withSession(['auth_user_id' => $this->userId('pengurus')])
            ->post('logout', [
                'csrf_token' => $this->csrfToken(),
            ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('success');
        $result->assertSessionMissing('auth_user_id');
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->select('id')->where('username', $username)->get()->getRow();

        $this->assertNotNull($row, 'Pengguna uji tidak ditemukan: ' . $username);

        return (int) $row->id;
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
}
