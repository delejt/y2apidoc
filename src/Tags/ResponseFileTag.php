<?php namespace Delejt\Y2apidoc\Tags;

use Delejt\Y2apidoc\Renderer\JsonRenderer;
/**
 * Class ResponseFileTag
 *
 * @package Delejt\Y2apidoc\Tags
 */
class ResponseFileTag
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
     * @param $filename
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function render($filename)
    {
        $template_path = config('y2apidoc.documentation.tags_template_path');
        $template_name = strtolower(join('', array_slice(explode('\\', __CLASS__), -1)));

        view()->addLocation($template_path);

        $response_file = storage_path('api') . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($response_file)) {
            throw new Exception("{$filename} from responsefile tag not found");
        }

        $body = file_get_contents($response_file);

        try {
            return view($template_name)->with('text', JsonRenderer::prettyPrint($body));
        }
        catch (\Exception $e) {
            return view();
        }
    }
}
