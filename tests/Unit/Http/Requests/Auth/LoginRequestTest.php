<?php

namespace Tests\Unit\Http\Requests\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\Auth\LoginRequest
 */
class LoginRequestTest extends TestCase
{
    /** @var \App\Http\Requests\Auth\LoginRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \App\Http\Requests\Auth\LoginRequest;
    }

    #[Test]
    public function authorize()
    {
        $actual = $this->subject->authorize();

        $this->assertTrue($actual);
    }

    #[Test]
    public function rules()
    {
        $actual = $this->subject->rules();

        $this->assertValidationRules([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], $actual);
    }

    // test cases...
}
