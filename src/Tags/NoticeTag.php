<?php namespace Delejt\Y2apidoc\Tags;

class NoticeTag
{
    public function parse($body)
    {
        return $this->render($body);
    }

    protected function render($body)
    {
        $template_path = config('y2apidoc.documentation.tags_template_path');
        $filename = strtolower(join('', array_slice(explode('\\', __CLASS__), -1)));

        view()->addLocation($template_path);

        try {
            return view($filename)->with('text', $body);
        }
        catch (\Exception $e) {
            return $body;
        }
    }

}
