<?php
namespace Lib\Util;
/**
 * Response class file.
* @author Su Chao<chaos@jumei.com>
*/

/**
 * This class provides a series of methods to handle reponses to clients.
*/
class Response
{
    /**
     * Send json/jsonp data and http header.
     *
     * @param mixed $data
     * @param string $callback
     */
    public static function json($data, $callback=null)
    {
        ob_clean();
        $data = json_encode($data, 256);
        if($callback)
        {
            $data = $callback.'('.$data.');';
            $header = 'Content-type: text/javascript; charset=utf-8;';
        }
        else
        {
            $header = 'Content-type: application/json; charset=utf-8;';
        }
        header($header);
	echo $data;
    }
}
