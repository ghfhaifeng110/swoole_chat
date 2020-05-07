<?php
class SqlBase{
    protected $mongo;

    public function __construct(array $options = array()){

        $mongo = new MongoPHP(array('host'=>$mongodb_config['host'],'port'=>$mongodb_config['port']));

        // // 使用 bug 数据库
        $mongo->selectDB('army_chat');
    }
}