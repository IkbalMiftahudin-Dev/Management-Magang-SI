<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root path requires SSO authentication and redirects guests to /sso/login.
     */
    public function test_root_redirects_guests_to_sso_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
