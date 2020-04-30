<?php
namespace Lib;
/**
 * template factory that provides templage engines to controller
 * @author Su Chao<suchaoabc@163.com>
 */
class ViewTemplate{
    protected static $engines=array();
    /**
     * @var \JMTemplate; *not always this class, it's for the purpose of code assitant*
     */
    protected $engine;
    /**
     * @var BaseController
     */
    public $controller;
    /**
     * get a template engine instance of views
     * @param string $templateEngineName
     * @return \JMTemplate; *not always this class, it's for the purpose of code assitant*
     */
    public static function instance($templateEngineName='default')
    {
        if(empty(self::$engines[$templateEngineName]))
        {
            switch($templateEngineName)
            {
                default:
                    require_once TEMPLATE_ENGINE_ROOT.'JMTemplate'.DS.'JMTemplate.class.php';
                        $tplCfg = array('template_path'=>APP_ROOT.'View'.DS,'file_extension'=>'.php');
                        self::$engines[$templateEngineName] = new self();
                        self::$engines[$templateEngineName]->engine = new \JMTemplate($tplCfg);
                    continue;
            }
        }
        return self::$engines[$templateEngineName];
    }
    
    /**
     * overide purpose
     */
    public function display($tpl=null)
    {
    	if(!$tpl)
    	{
    		$tpl = $this->controller->getController().DS.$this->controller->getAction();
    	}
    	$this->engine->display($tpl);
    }
    public function __set($name,$value)
    {
    	$this->engine->$name = $value;
    }
    public function __get($name)
    {
    	return $this->engine->$name;
    }
    public function __call($name,$args)
    {
    	return call_user_func_array(array($this->engine, $name), $args);
    }
}