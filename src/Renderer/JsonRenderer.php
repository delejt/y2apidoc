<?php namespace Delejt\Y2apidoc\Renderer;

use Highlight\Highlighter;

/**
 * Class JsonRenderer
 *
 * @package Delejt\Y2apidoc\Renderer
 */
class JsonRenderer
{
    /**
     * @author Kendall Hopkins
     * @link https://stackoverflow.com/a/9776726/1865620
     * @param $json
     * @return string
     * @throws \Exception
     */
    public static function prettyPrint($json)
    {
        $result = '';
        $level = 0;
        $in_quotes = false;
        $in_escape = false;
        $ends_line_level = NULL;
        $json_length = strlen( $json );

        for( $i = 0; $i < $json_length; $i++ ) {
            $char = $json[$i];
            $new_line_level = NULL;
            $post = "";
            if( $ends_line_level !== NULL ) {
                $new_line_level = $ends_line_level;
                $ends_line_level = NULL;
            }
            if ( $in_escape ) {
                $in_escape = false;
            } else if( $char === '"' ) {
                $in_quotes = !$in_quotes;
            } else if( ! $in_quotes ) {
                switch( $char ) {
                    case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                    case '{': case '[':
                    $level++;
                    case ',':
                        $ends_line_level = $level;
                        break;

                    case ':':
                        $post = " ";
                        break;

                    case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
                }
            } else if ( $char === '\\' ) {
                $in_escape = true;
            }
            if( $new_line_level !== NULL ) {
                $result .= "\n".str_repeat( "\t", $new_line_level );
            }
            $result .= $char.$post;
        }

        return self::colorize($result);
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
            return $highlighted->value;
        }

    }