<?php

namespace Lib;

/**
 * distribute tasks to services
 *
 * @author Su Chao<suchaoabc@163.com>
 */
class Processor {
    protected $module;
    protected $action;
    protected $params = array ();
    protected $service;
    /**
     *
     * @param string $module
     * @param string $action
     * @param mixed $args
     *            arg1[,arg2[,arg3]...]
     */
    public function __construct($module, $action, $args = null) {
        $this->module = $module;
        $this->action = $action;
        $args = func_get_args ();
        unset ( $args [0], $args [1] );
        $this->params = $args;
        $className = '\App\Server\Service\\' . $this->module . '\\' . $this->action;
        if (! class_exists ( $className )) {
            throw new \Exception ( 'Class ' . $className . ' does not exists!' );
        }
        $this->service = new $className ();
    }

    /**
     * each controller has to implement "execute" method.
     *
     * @param mixed $args
     *            arg1[,arg2[,arg3]...] all params are passed to "execute" of
     *            controller.
     */
    public function execute($args = null) {
        $args = func_get_args ();
        if (count ( $args ))
            $this->params = $args;

        return call_user_func_array ( array (
                $this->service,
                'execute'
        ), $this->params );
    }

    public function getService()
    {
        return $this->service;
    }
}