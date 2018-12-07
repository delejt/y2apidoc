<?php namespace Delejt\Y2apidoc\Renderer;

use Highlight\Highlighter;

/**
 * Class JsonRenderer
 *
 * @package Delejt\Y2apidoc\Renderer
 */
class LanguageRenderer
{
    /**
     * @param $body
     * @return mixed
     * @throws \Exception
     */
    public static function prettyPrint($body)
        {
            return self::colorize($body);
        }

    /**
     * @param $body
     * @return mixed
     * @throws \Exception
     */
    private static function colorize($body)
        {
            $hl = new Highlighter();
            $highlighted = $hl->highlight('php', $body);

            $result = preg_replace('/&amp;nbsp;/', '&nbsp;', $highlighted->value);
            $result = preg_replace('/&amp;quot;/', '&quot;', $result);
            $result = preg_replace('/\b(CURLOPT_\w*)\b/', '<strong>$1</strong>;', $result);
            return $result;
        }

    }