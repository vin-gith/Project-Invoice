<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** Tanpa Content-Length, halaman yang dibuka lewat link port forwarding VS Code terus "loading". */
    public function test_responses_carry_content_length(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Content-Length', (string) strlen($response->getContent()));
    }
}
