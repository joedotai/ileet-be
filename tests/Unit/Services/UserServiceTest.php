<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = new UserService();

        // Create a mock user for the test database context
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);
    }

    /** @test */
    public function it_logs_in_a_user_with_correct_credentials(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ];

        $result = $this->userService->login($credentials);

        $this->assertTrue($result);
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->user->id, Auth::user()->id);
    }

    /** @test */
    public function it_throws_a_validation_exception_with_incorrect_credentials(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->userService->login($credentials);
        } finally {
            $this->assertFalse(Auth::check());
        }
    }

    /** @test */
    public function it_logs_out_an_authenticated_user(): void
    {
        Auth::login($this->user);
        $this->assertTrue(Auth::check());

        $this->userService->logout();

        $this->assertFalse(Auth::check());
    }
}