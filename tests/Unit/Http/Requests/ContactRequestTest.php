<?php

namespace Tests\Unit\Http\Requests;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\ContactRequest
 */
class ContactRequestTest extends TestCase
{
    /** @var \App\Http\Requests\ContactRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \App\Http\Requests\ContactRequest;
    }

    #[Test]
    public function authorize()
    {
        $actual = $this->subject->authorize();

        $this->assertFalse($actual);
    }

    #[Test]
    public function rules()
    {
        $actual = $this->subject->rules();

        $this->assertEquals([], $actual);
    }

    // test cases...
}
