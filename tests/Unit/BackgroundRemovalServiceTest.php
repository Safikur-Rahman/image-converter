<?php

namespace Tests\Unit;

use App\Services\BackgroundRemoval\BackgroundRemovalService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BackgroundRemovalServiceTest extends TestCase
{
    public function test_is_unavailable_when_no_provider_configured(): void
    {
        $service = new BackgroundRemovalService(['provider' => '']);

        $this->assertFalse($service->isAvailable());
        $this->assertNull($service->providerClass());
        $this->assertNull($service->configuredProviderName());
    }

    public function test_http_provider_requires_api_url_and_key(): void
    {
        $withoutKey = new BackgroundRemovalService([
            'provider' => 'http',
            'api_url' => 'https://example.com/rm',
            'api_key' => '',
        ]);
        $this->assertFalse($withoutKey->isAvailable());

        $configured = new BackgroundRemovalService([
            'provider' => 'http',
            'api_url' => 'https://example.com/rm',
            'api_key' => 'secret',
        ]);
        $this->assertTrue($configured->isAvailable());
        $this->assertSame('http', $configured->configuredProviderName());
    }

    public function test_local_provider_is_available_when_configured(): void
    {
        $service = new BackgroundRemovalService(['provider' => 'local']);

        $this->assertTrue($service->isAvailable());
        $this->assertSame('local', $service->configuredProviderName());
    }

    public function test_remove_background_throws_when_not_configured(): void
    {
        $service = new BackgroundRemovalService(['provider' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not configured/i');

        $service->removeBackground('/tmp/in.png', '/tmp/out.png');
    }

    public function test_unknown_provider_name_is_not_available(): void
    {
        $service = new BackgroundRemovalService(['provider' => 'xenomorph']);

        $this->assertNull($service->providerClass());
        $this->assertFalse($service->isAvailable());
    }
}