<?php

namespace App\Services\BackgroundRemoval;

use RuntimeException;

/**
 * Resolves and delegates to whichever background-removal provider is
 * configured. When none is configured, availability is reported as false and
 * calls throw a descriptive, catchable exception — never a silent no-op.
 */
class BackgroundRemovalService
{
    public const PROVIDERS = [
        'http' => ApiProvider::class,
        'local' => LocalProvider::class,
    ];

    private ?ProviderInterface $provider = null;

    public function __construct(private array $config = [])
    {
        $this->config = $config ?: config('imageconverter.background_removal');
    }

    /**
     * The configured provider class name, or null when unavailable.
     */
    public function providerClass(): ?string
    {
        $name = $this->configuredProviderName();

        return $name === null ? null : (self::PROVIDERS[$name] ?? null);
    }

    public function provider(): ?ProviderInterface
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $class = $this->providerClass();
        if ($class === null) {
            return null;
        }

        return $this->provider = new $class($this->config);
    }

    /**
     * Whether background removal can run right now (external provider
     * configured and reachable by configuration checks we can do locally).
     */
    public function isAvailable(): bool
    {
        if ($this->provider() === null) {
            return false;
        }

        $name = $this->configuredProviderName();

        if ($name === 'http') {
            return (string) ($this->config['api_url'] ?? '') !== ''
                && (string) ($this->config['api_key'] ?? '') !== '';
        }

        return true;
    }

    /**
     * @return string Absolute path to the transparent PNG.
     */
    public function removeBackground(string $inputPath, string $outputPath): string
    {
        $provider = $this->provider();

        if ($provider === null) {
            throw new RuntimeException(
                'Background removal is not configured. Set BACKGROUND_REMOVAL_PROVIDER and BACKGROUND_REMOVAL_API_URL in .env.'
            );
        }

        return $provider->removeBackground($inputPath, $outputPath);
    }

    public function configuredProviderName(): ?string
    {
        $name = strtolower(trim((string) ($this->config['provider'] ?? '')));

        return $name === '' ? null : $name;
    }
}