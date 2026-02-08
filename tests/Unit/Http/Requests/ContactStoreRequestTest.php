<?php

namespace Tests\Unit\Http\Requests;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\ContactStoreRequest
 */
class ContactStoreRequestTest extends TestCase
{
    /** @var \App\Http\Requests\ContactStoreRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \App\Http\Requests\ContactStoreRequest;
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
            'name' => 'required|string',
            'message' => 'required|string',
        ], $actual);
    }

    // test cases...
}
