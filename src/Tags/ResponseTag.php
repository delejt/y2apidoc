<?php namespace Delejt\Y2apidoc\Tags;

use Delejt\Y2apidoc\Renderer\JsonRenderer;
/**
 * Class ResponseTag
 *
 * @package Delejt\Y2apidoc\Tags
 */
class ResponseTag
{
    /**
     * @param $body
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function parse($body)
    {
        return $this->render($body);
    }

    /**
     * @param $body
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function render($body)
    {
        $template_path = config('y2apidoc.documentation.tags_template_path');
        $filename = strtolower(join('', array_slice(explode('\\', __CLASS__), -1)));

        view()->addLocation($template_path);

        try {
            return view($filename)->with('text', JsonRenderer::prettyPrint($body));
        }
        catch (\Exception $e) {
            return $body;
        }
    }
}
