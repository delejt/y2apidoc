<?php namespace Delejt\Y2apidoc\Tags;

/**
 * Class NoticeTag
 *
 * @package Delejt\Y2apidoc\Tags
 */
class NoticeTag
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
        $filename = strtolower(join('', array_slice(explode('\\', __CLASS__), -1)));

        try {
            return view($filename)->with('text', $body);
        }
        catch (\Exception $e) {
            return $body;
        }
    }

}
