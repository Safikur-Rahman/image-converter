<?php

namespace App\Services\Svg;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

/**
 * Lightweight SVG optimizer that safely reduces markup weight without breaking
 * the document structure, the viewBox or path geometry.
 */
class SvgOptimizationService
{
    public function optimize(string $svg): string
    {
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $svg;
        }

        $this->stripComments($doc);
        $this->stripMetadata($doc);
        $this->stripEmptyContainers($doc);
        $this->simplifyAttributes($doc);
        $this->stripSuperfluousAttributes($doc);

        $doc->formatOutput = false;
        $doc->preserveWhiteSpace = false;

        $out = trim($doc->saveXML());

        if ($out === '' || $out === false) {
            return $svg;
        }

        // DOMDocument writes the XML declaration; keep it minimal but present.
        return $out;
    }

    protected function stripComments(DOMDocument $doc): void
    {
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//comment()') as $comment) {
            $comment->parentNode->removeChild($comment);
        }
    }

    protected function stripMetadata(DOMDocument $doc): void
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        foreach ($xpath->query('//svg:metadata|//svg:title|//svg:desc') as $node) {
            if ($node->parentNode !== null) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    protected function stripEmptyContainers(DOMDocument $doc): void
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $groups = $xpath->query('//svg:g');

        $list = [];
        foreach ($groups as $group) {
            $list[] = $group;
        }

        foreach ($list as $group) {
            if ($group instanceof DOMElement && !$group->hasChildNodes() && count($group->attributes) === 0) {
                $group->parentNode->removeChild($group);
            }
        }
    }

    protected function simplifyAttributes(DOMDocument $doc): void
    {
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//*') as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            // Drop inline style blocks; the tracer never emits meaningful ones.
            if ($node->hasAttribute('style')) {
                $node->removeAttribute('style');
            }

            // `data-color` is a diagnostic hint, not part of the visual output.
            if ($node->hasAttribute('data-color')) {
                $node->removeAttribute('data-color');
            }

            // `shape-rendering` hints are irrelevant at the resolution we target.
            if ($node->hasAttribute('shape-rendering')) {
                $node->removeAttribute('shape-rendering');
            }
        }
    }

    protected function stripSuperfluousAttributes(DOMDocument $doc): void
    {
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//*') as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            // Remove empty style attributes left behind by cleanup passes.
            if ($node->hasAttribute('style') && trim($node->getAttribute('style')) === '') {
                $node->removeAttribute('style');
            }
        }
    }
}