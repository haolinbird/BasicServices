<?php
namespace Lib;
class RequestParams{
    public function current () {
    }
    public function next () {
    }

    public function key () {}

    public function valid () {}

    public function rewind () {
    }
    private $post = array(),$get=array();
    public function __construct($get=array(),$post=array())
    {
        $this->get = $get;
        $this->post = $post;
    }
    public function __get($name)
    {
        return isset($this->get[$name]) ? $this->get[$name] : (isset($this->post[$name]) ? $this->post[$name] : null);
    }
    public function getGet($name)
    {
        return isset($this->get[$name]) ? $this->get[$name] : null;
    }
    public function getGetInt($name)
    {
        return (int) $this->getGet($name);
    }
    public function getPost($name)
    {
        return isset($this->post[$name]) ? $this->post[$name] : null;
    }
    public function getPostInt($name)
    {
        return (int) $this->getPost($name);
    }

    public function isPost()
    {
        return strtolower($_SERVER['REQUEST_METHOD']) == 'post';
    }

    /**
     * convert a GET/POST param to int;
     * @param string $name
     * @return number
     */
    public function toInt($name)
    {
        return (int) $this->$name;
    }

    public function getAll()
    {
       return array_merge($this->get, $this->post);
    }
}