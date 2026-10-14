<?php

namespace App\Services\Svg;

use DOMDocument;

/**
 * Validates that a produced SVG is a well-formed, safe, vector document.
 */
class SvgValidationService
{
    /**
     * @return array{valid: bool, errors: array<int, string>}
     */
    public function validate(string $svg): array
    {
        $errors = [];

        if (trim($svg) === '') {
            return ['valid' => false, 'errors' => ['The SVG is empty.']];
        }

        if (!$this->isWellFormed($svg)) {
            return ['valid' => false, 'errors' => ['The SVG is not well-formed XML.']];
        }

        $doc = $this->load($svg);
        $root = $doc->documentElement;

        if ($root === null || $root->nodeName !== 'svg') {
            $errors[] = 'The root element is not <svg>.';
        }

        if ($root !== null && !$root->hasAttribute('viewBox') && (!$root->hasAttribute('width') || !$root->hasAttribute('height'))) {
            $errors[] = 'The SVG has no viewBox or width/height.';
        }

        if ($this->containsScripts($svg)) {
            $errors[] = 'The SVG contains scripting elements.';
        }

        if ($errors === []) {
            return ['valid' => true, 'errors' => []];
        }

        return ['valid' => false, 'errors' => $errors];
    }

    public function isWellFormed(string $svg): bool
    {
        return $this->load($svg) !== null;
    }

    public function hasViewBox(string $svg): bool
    {
        $doc = $this->load($svg);
        if ($doc === null) {
            return false;
        }

        $root = $doc->documentElement;

        return $root !== null && $root->hasAttribute('viewBox');
    }

    public function containsScripts(string $svg): bool
    {
        $doc = $this->load($svg);
        if ($doc === null) {
            return false;
        }

        $lower = strtolower($svg);

        if (str_contains($lower, '<script') || str_contains($lower, 'onload')) {
            return true;
        }

        return false;
    }

    private function load(string $svg): ?DOMDocument
    {
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $doc : null;
    }
}