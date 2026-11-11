<?php

namespace App\Support;

final class RichTextSanitizer
{
    public static function sanitize(string $html): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="rich-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $document->getElementById('rich-root');
        if (! $root) {
            return '';
        }

        self::sanitizeChildren($root);
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    private static function sanitizeChildren(\DOMNode $parent): void
    {
        $allowed = ['p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'span', 'a', 'font'];
        $removeContents = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template'];
        $fonts = ['Arial', 'Georgia', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'];

        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof \DOMComment || ! in_array($node->nodeType, [XML_ELEMENT_NODE, XML_TEXT_NODE], true)) {
                $parent->removeChild($node);
                continue;
            }
            if ($node instanceof \DOMText) {
                continue;
            }

            $tag = strtolower($node->nodeName);
            if (in_array($tag, $removeContents, true)) {
                $parent->removeChild($node);
                continue;
            }
            self::sanitizeChildren($node);
            if (! in_array($tag, $allowed, true)) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            $attributes = [];
            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->name);
                if (($tag === 'a' && in_array($name, ['href', 'title'], true))
                    || ($name === 'style' && in_array($tag, ['span', 'font', 'div', 'p', 'h2', 'h3', 'blockquote', 'li'], true))
                    || ($tag === 'font' && in_array($name, ['face', 'size', 'color'], true))) {
                    $attributes[$name] = $attribute->value;
                }
            }

            while ($node->attributes->length) {
                $node->removeAttributeNode($node->attributes->item(0));
            }
            foreach ($attributes as $name => $value) {
                if ($name === 'href') {
                    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                    if (! in_array($scheme, ['https', 'http', 'mailto'], true)) {
                        continue;
                    }
                } elseif ($name === 'style') {
                    $value = self::sanitizeStyle($value);
                    if ($value === '') {
                        continue;
                    }
                } elseif ($name === 'face') {
                    if (! in_array($value, $fonts, true)) {
                        continue;
                    }
                } elseif ($name === 'size') {
                    if (! in_array($value, ['1', '2', '3', '4', '5', '6', '7'], true)) {
                        continue;
                    }
                } elseif ($name === 'color' && ! preg_match('/^#[0-9a-f]{3,8}$/i', $value)) {
                    continue;
                }
                $node->setAttribute($name, $value);
            }
            if ($tag === 'a' && $node->hasAttribute('href')) {
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    private static function sanitizeStyle(string $style): string
    {
        $safe = [];
        foreach (explode(';', $style) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$property, $value] = array_map('trim', $parts);
            $property = strtolower($property);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $valid = match ($property) {
                'color', 'background-color' => self::isSafeColor($value),
                'font-size' => (bool) preg_match('/^(8|9|1[0-9]|2[0-9]|3[0-6])(px|pt)$/i', $value),
                'font-family' => (bool) preg_match('/^[a-z0-9 ,_-]{1,80}$/i', $value),
                'text-align' => in_array(strtolower($value), ['left', 'right', 'center', 'justify'], true),
                'margin-left', 'margin-bottom' => (bool) preg_match('/^(0|[1-9][0-9]?)(px|em|rem)$/i', $value),
                default => false,
            };
            if ($valid) {
                $safe[] = $property.':'.$value;
            }
        }
        return implode(';', $safe);
    }

    private static function isSafeColor(string $value): bool
    {
        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) {
            return true;
        }
        if (! preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i', $value, $channels)) {
            return false;
        }

        return (int) $channels[1] <= 255 && (int) $channels[2] <= 255 && (int) $channels[3] <= 255;
    }
}
