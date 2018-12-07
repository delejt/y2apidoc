<?php namespace Delejt\Y2apidoc\Tags;

class TableTag
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

        $rows = preg_split('/\r\n|\r|\n/', $body);

        $table = [];
        foreach($rows as $row_num => $row) {
            $table[$row_num] = explode('|', trim($row));
        }

        try {
            return view($filename)->with('table', $table);
        }
        catch (\Exception $e) {
            return $body;
        }
    }

}
