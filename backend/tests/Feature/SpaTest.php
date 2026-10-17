<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaTest extends TestCase
{
    public function test_unknown_api_routes_stay_json_404s(): void
    {
        $this->getJson('/api/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_health_check_is_not_swallowed_by_the_spa(): void
    {
        $this->get('/up')->assertOk();
    }
}
