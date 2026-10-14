<?php

namespace Tests\Unit;

use App\Services\Svg\SvgValidationService;
use PHPUnit\Framework\TestCase;

class SvgValidationServiceTest extends TestCase
{
    private SvgValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SvgValidationService;
    }

    public function test_it_accepts_a_valid_svg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0 0H100V100H0Z" fill="#fff"/></svg>';

        $result = $this->service->validate($svg);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    public function test_it_accepts_width_height_svgs_without_viewbox(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><path d="M0 0L100 100"/></svg>';

        $this->assertTrue($this->service->validate($svg)['valid']);
    }

    public function test_it_rejects_empty_svg(): void
    {
        $result = $this->service->validate('   ');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('empty', $result['errors'][0]);
    }

    public function test_it_rejects_malformed_xml(): void
    {
        $result = $this->service->validate('<svg><path></svg>');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('not well-formed', $result['errors'][0]);
    }

    public function test_it_rejects_script_elements(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            .'<script>alert(1)</script><path d="M0 0"/></svg>';

        $result = $this->service->validate($svg);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('scripting', implode(', ', $result['errors']));
    }

    public function test_it_rejects_root_elements_that_are_not_svg(): void
    {
        $result = $this->service->validate('<div xmlns="http://www.w3.org/1999/xhtml"/>');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('root element', implode(', ', $result['errors']));
    }

    public function test_contains_scripts_detects_onload_handlers(): void
    {
        $svg = '<svg onload="alert(1)"></svg>';

        $this->assertTrue($this->service->containsScripts($svg));
    }
}