<?php

namespace Lib;

/**
 * Base controller of MVC apps, provides common methods.
 *
 * @author Su Chao<suchaoabc@163.com>
 */
 class BaseController {
    protected $controller = 'Index';
    protected $action = 'Index';
    /**
     * request parameters includeing GET and POST
     * @var \Lib\RequestParams
     */
    protected $requestParams;
    /**
     * template engine
     * @var ViewTemplate
     */
    protected $templateEngine;
    public function setController($name)
    {
        $this->controller = $name;
        return $this;
    }
    public function setAction($name)
    {
        $this->action = $name;
        return $this;
    }
    public function getController()
    {
    	return $this->controller;
    }
    public function getAction()
    {
    	return $this->action;
    }
    public function run($appName)
    {
        if($this->controller == 'Index' && $this->action == 'Index')
        {
            $this->initEvn($appName);
        }
        $className = 'App\\'.$appName.'\\Controller\\'.$this->controller.'\\'.$this->action;

        if(!class_exists($className))
        {
            if($this->controller != 'Errors' && $this->action != '_404')
            {
                $this->setController('Errors')->setAction('_404');
                $this->run($appName);
                return false;
            }
            else if($this->controller == 'Errors' && $this->action == '_404')
            {
                throw new \Lib\ControllerException('Controller/Action do not exist. Please make a friendly custom 404 page as  Controller/Errors/_404 to replace this message.');
            }
            else
            {
                throw new \Lib\ControllerException('Unknown controller/action!');
            }
        }
        $user = \Lib\User::current();
        \Lib\Log::instance('admin')->log(array($user->info->name, $this->getController(), $this->getAction(), $this->requestParams->getAll()));
        $handler = new $className();
        $handler->setController($this->controller)->setAction($this->action);
        $handler->requestParams = $this->requestParams;
        $handler->execute();
    }
    public function parseUriParams()
    {
        $uriComponent = parse_url($_SERVER['REQUEST_URI']);
        $paths = array();
        if($uriComponent)
        {
            if(isset($uriComponent['query']))
            {
                parse_str($uriComponent['query'],$_GET);
            }

            if(isset($uriComponent['path']))
            {
                $paths = explode('/',trim($uriComponent['path'],'/'));
            }
        }
        if(!empty($paths[0]))$this->setController($paths[0]);
        if(!empty($paths[1])) $this->setAction($paths[1]);
    }
    protected function initEvn($appName)
    {
        /**
         * root directory of the app.
         * @var string
         */
        define('APP_ROOT', SYS_ROOT.'App'.DS.$appName.DS);
        /**
         * name of the current app
         * @var string
         */
        define('APP_NAME', $appName);
        $this->parseUriParams();
        $this->requestParams = new \Lib\RequestParams($_GET,$_POST);
    }
    /**
     *
     * @param string $templateEngineName
     * @return \JMTemplate
     */
    public function getTemplate($templateEngineName='default')
    {
        if(!$this->templateEngine)
        {
            $this->templateEngine = ViewTemplate::instance();
            $this->templateEngine->controller = $this;
        }
        return $this->templateEngine;
    }
}