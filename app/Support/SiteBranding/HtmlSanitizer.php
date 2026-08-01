<?php

namespace App\Support\SiteBranding;

use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSanitizer
{
    /** @var array<string, true> */
    private array $allowedTags = [
        'b' => true,
        'strong' => true,
        'i' => true,
        'em' => true,
        'u' => true,
        'span' => true,
        'div' => true,
        'p' => true,
        'br' => true,
        'ul' => true,
        'ol' => true,
        'li' => true,
        'font' => true,
    ];

    /** @var array<string, true> */
    private array $allowedStyleProperties = [
        'color' => true,
        'font-size' => true,
        'font-weight' => true,
        'font-style' => true,
        'text-decoration' => true,
    ];

    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        if (! class_exists(DOMDocument::class)) {
            return $this->fallbackSanitize($html);
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="site-branding-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('site-branding-root');

        if (! $root) {
            return '';
        }

        $this->sanitizeChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        for ($child = $parent->firstChild; $child !== null;) {
            $next = $child->nextSibling;

            if ($child->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($child);
                $child = $next;

                continue;
            }

            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! isset($this->allowedTags[$tag])) {
                    $this->sanitizeChildren($child);
                    while ($child->firstChild) {
                        $parent->insertBefore($child->firstChild, $child);
                    }
                    $parent->removeChild($child);
                    $child = $next;

                    continue;
                }

                $this->sanitizeElementAttributes($child);
                $this->sanitizeChildren($child);
            }

            $child = $next;
        }
    }

    private function sanitizeElementAttributes(DOMElement $element): void
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute->name;
        }

        foreach ($attributes as $name) {
            $lowerName = strtolower($name);

            if ($lowerName === 'style') {
                $style = $this->sanitizeStyle($element->getAttribute($name));
                if ($style === '') {
                    $element->removeAttribute($name);
                } else {
                    $element->setAttribute('style', $style);
                }

                continue;
            }

            if (
                strtolower($element->tagName) === 'font'
                && $lowerName === 'color'
                && $this->isSafeColor($element->getAttribute($name))
            ) {
                continue;
            }

            $element->removeAttribute($name);
        }
    }

    private function sanitizeStyle(string $style): string
    {
        $safeDeclarations = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $value = trim($value);

            if (! isset($this->allowedStyleProperties[$property])) {
                continue;
            }

            $isSafe = match ($property) {
                'color' => $this->isSafeColor($value),
                'font-size' => (bool) preg_match('/^(12|13|14|15|16|17|18|20|24|28|32|36|40|44|48|56)px$/', $value),
                'font-weight' => in_array(strtolower($value), ['normal', 'bold', '500', '600', '700', '800', '900'], true),
                'font-style' => in_array(strtolower($value), ['normal', 'italic'], true),
                'text-decoration' => in_array(strtolower($value), ['none', 'underline'], true),
                default => false,
            };

            if ($isSafe) {
                $safeDeclarations[] = $property.': '.$value;
            }
        }

        return implode('; ', $safeDeclarations);
    }

    private function isSafeColor(string $value): bool
    {
        $value = trim($value);

        return (bool) preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $value)
            || (bool) preg_match('/^rgba?\(\s*\d{1,3}%?\s*,\s*\d{1,3}%?\s*,\s*\d{1,3}%?(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i', $value)
            || in_array(strtolower($value), [
                'black', 'white', 'gray', 'grey', 'red', 'blue', 'green',
                'navy', 'indigo', 'purple', 'orange', 'yellow', 'teal',
                'maroon', 'silver', 'transparent',
            ], true);
    }

    private function fallbackSanitize(string $html): string
    {
        $html = strip_tags(
            $html,
            '<b><strong><i><em><u><span><div><p><br><ul><ol><li><font>',
        );

        return preg_replace(
            '/\s+(?:on\w+|class|id|href|src|style)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $html,
        ) ?? '';
    }
}
