<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase {
    use RefreshDatabase;

    public function test_classic_swagger_ui_endpoint_returns_successful_response(): void {
        $response = $this->get('/api/documentation');

        $response->assertStatus(200);
        $response->assertSee('SwaggerUIBundle');
        $response->assertSee('/docs/api.json');
    }

    public function test_swagger_ui_endpoint_returns_successful_response(): void {
        $response = $this->get('/docs/api');

        $response->assertStatus(200);
    }

    public function test_openapi_json_specification_returns_valid_json_with_endpoints(): void {
        $response = $this->get('/docs/api.json');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('openapi', $json);
        $this->assertArrayHasKey('paths', $json);
        $this->assertNotEmpty($json['paths']);

        $pathKeys = array_keys($json['paths']);
        $hasContractsEndpoint = false;
        foreach ($pathKeys as $path) {
            if (str_contains($path, 'contracts')) {
                $hasContractsEndpoint = true;
                break;
            }
        }
        $this->assertTrue($hasContractsEndpoint);
    }
}
