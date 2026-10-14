<?php

namespace Tests\Unit;

use App\Services\Svg\SvgOptimizationService;
use PHPUnit\Framework\TestCase;

class SvgOptimizationServiceTest extends TestCase
{
    private SvgOptimizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SvgOptimizationService;
    }

    public function test_it_strips_comments_and_metadata(): void
    {
        $svg = '<?xml version="1.0"?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <!-- diagnostic comment -->
                <metadata><dc:date>2026-01-01</dc:date></metadata><title>Test</title>
                <path d="M0 0L100 0" fill="#000"/>
            </svg>';

        $out = $this->service->optimize($svg);

        $this->assertStringNotContainsString('comment', $out);
        $this->assertStringNotContainsString('metadata', $out);
        $this->assertStringNotContainsString('Test', $out);
        $this->assertStringContainsString('viewBox="0 0 100 100"', $out);
        $this->assertStringContainsString('M0 0L100 0', $out);
    }

    public function test_it_removes_superfluous_attributes(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            .'<path d="M0 0" style=" " shape-rendering="optimizeSpeed" data-color="#ffffff" fill="#fff"/>'
            .'</svg>';

        $out = $this->service->optimize($svg);

        $this->assertStringNotContainsString('shape-rendering', $out);
        $this->assertStringNotContainsString('data-color', $out);
        $this->assertStringNotContainsString('style', $out);
        $this->assertStringContainsString('fill="#fff"', $out);
    }

    public function test_it_removes_empty_groups(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            .'<g></g>'
            .'<path d="M0 0L10 10"/>'
            .'</svg>';

        $out = $this->service->optimize($svg);

        $this->assertStringNotContainsString('<g/>', $out);
        $this->assertStringContainsString('M0 0L10 10', $out);
    }

    public function test_it_returns_input_unchanged_for_invalid_xml(): void
    {
        $svg = '<svg><path></svg>';

        $this->assertSame($svg, $this->service->optimize($svg));
    }

    public function test_optimized_output_is_still_valid_svg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">'
            .'<path d="M0 0H200V200H0Z" fill="#123456"/>'
            .'</svg>';

        $out = $this->service->optimize($svg);

        $this->assertStringContainsString('<svg', $out);
        $this->assertStringContainsString('viewBox="0 0 200 200"', $out);
        $this->assertStringContainsString('M0 0H200V200H0Z', $out);
    }
}