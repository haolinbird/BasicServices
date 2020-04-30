<?php

namespace App\Server\Service\Exchange;

/**
 * Register a channel to exchange certain type of messges.<br />
 * All channels should be reviewed , approved and registered by system
 * administrator.
 * Applications are not able to register a channel freely.
 * <br /><br />
 * <strong>ParamExample</strong>
 * <pre>
 * <code>
 * $idKey = 'imageprocessing';
 * $name = '图片压缩处理';
 * $comments = '图片裁剪,缩略图生成';
 * </code>
 * </pre>
 * @todo not implemented
 * @author Su Chao<suchaoabc@163.com>
 */
class RegisterChannel extends \Lib\BaseService {
    /**
     *
     * @param string $idKey
     *            Unique string to identify a channel. It should be created
     *            manually and easy to recognize.
     * @param string $name
     * @param string $comments
     *            channel descriptions.
     */
    public function __construct($idKey, $name, $comments = '') {
    }
}
