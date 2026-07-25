<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_root_redirects(): void
    {
        // Root redirects to login (or the installer when not yet installed).
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
