<?php namespace Delejt\Y2apidoc\Tags;

/**
 * Class TableTag
 *
 * @package Delejt\Y2apidoc\Tags
 */
class TableTag
{
    /**
     * @param $body
     * @return mixed
     */
    public function parse($body)
    {
        return $this->render($body);
    }

    /**
     * @param $body
     * @return mixed
     */
    protected function render($body)
    {
        $filename = strtolower(join('', array_slice(explode('\\', __CLASS__), -1)));
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
