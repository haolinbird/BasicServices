<?php

namespace Lib;

/**
 * Base service, provides common methods.
 *
 * @author Su Chao<suchaoabc@163.com>
 * @todo 这是一个测试标签
 */
abstract class BaseService {
    protected $clientInfo = array();

    public function setClientInfo(array $info)
    {
        $this->clientInfo = $info;
    }
}