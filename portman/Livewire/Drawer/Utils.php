<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Drawer;

use Illuminate\Http\Response;
use function Magewirephp\Magewire\response;
use Magewirephp\Magewire\Exceptions\RootTagMissingFromViewException;

class Utils extends BaseUtils
{
    static function insertAttributesIntoHtmlRoot($html, $attributes)
    {
        $attributesFormattedForHtmlElement = static::stringifyHtmlAttributes($attributes);

        preg_match('/(?:\n\s*|^\s*)<([a-zA-Z0-9\-]+)/', $html, $matches, PREG_OFFSET_CAPTURE);

        throw_unless(
            count($matches),
            new RootTagMissingFromViewException
        );

        $tagName = $matches[1][0];
        $lengthOfTagName = strlen($tagName);
        $positionOfFirstCharacterInTagName = $matches[1][1];

        return substr_replace(
            $html,
            ' ' . $attributesFormattedForHtmlElement,
            $positionOfFirstCharacterInTagName + $lengthOfTagName,
            0
        );
    }

    static function stringifyHtmlAttributes($attributes)
    {
        return collect($attributes)
            ->mapWithKeys(function ($value, $key) {
                return [$key => static::escapeStringForHtml($value)];
            })->map(function ($value, $key) {
                return sprintf('%s="%s"', $key, $value);
            })->implode(' ');
    }

    static function escapeStringForHtml($subject)
    {
        if (is_string($subject) || is_numeric($subject)) {
            return htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE);
        }

        return htmlspecialchars(json_encode($subject), ENT_QUOTES | ENT_SUBSTITUTE);
    }

    static function pretendResponseIsFile($file, $mimeType = 'application/javascript')
    {
        $expires = strtotime('+1 year');
        $lastModified = filemtime($file);
        $cacheControl = 'public, max-age=31536000';

        if (static::matchesCache($lastModified)) {
            return response('', 304, [
                'Expires' => static::httpDate($expires),
                'Cache-Control' => $cacheControl,
            ]);
        }

        $headers = [
            'Content-Type' => "$mimeType; charset=utf-8",
            'Expires' => static::httpDate($expires),
            'Cache-Control' => $cacheControl,
            'Last-Modified' => static::httpDate($lastModified),
        ];

        if (str($file)->endsWith('.br')) {
            $headers['Content-Encoding'] = 'br';
        }

        return response()->file($file, $headers);
    }

    static function matchesCache($lastModified)
    {
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        return @strtotime($ifModifiedSince) === $lastModified;
    }

    static function httpDate($timestamp)
    {
        return sprintf('%s GMT', gmdate('D, d M Y H:i:s', $timestamp));
    }

    static function containsDots($subject)
    {
        return str_contains($subject, '.');
    }

    static function dotSegments($subject)
    {
        return explode('.', $subject);
    }

    static function beforeFirstDot($subject)
    {
        return head(explode('.', $subject));
    }

    static function afterFirstDot($subject): string
    {
        return str($subject)->after('.');
    }

    static function hasProperty($target, $property)
    {
        return property_exists($target, static::beforeFirstDot($property));
    }

    static function extractAttributeDataFromHtml($html, $attribute)
    {
        $data = (string)str($html)->betweenFirst($attribute . '="', '"');

        return json_decode(
            htmlspecialchars_decode($data, ENT_QUOTES | ENT_SUBSTITUTE),
            associative: true,
        );
    }
}
