<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    #[Test]
    public function application_redirects_unauthenticated_visitor_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }
}
