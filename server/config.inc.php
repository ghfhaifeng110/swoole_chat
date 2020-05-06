<?php
error_reporting(E_ALL ^ E_NOTICE);

CONST STORAGE = "file"; //file 文件存储，mysql 数据库存储，redis 缓存存储
CONST DOMAIN = "http://127.0.0.1";
CONST ONLINE_DIR = '/data/wwwroot/default/rooms/';
CONST TASK_WORKER_NUM = 8;//线程个数
CONST DAEMONIZE = false;//是否开户守护进程，true是，flase否
CONST LOG_FILE  = __DIR__.'/server.log';//开户守护进程时，日志信息保存文档

//参数配置
CONST SOCKET_SERVER_IP = '0.0.0.0'; //服务器IP地址
CONST SOCKET_SERVER_PORT = 9501; //socket服务器端口
CONST SOCKET_MYSQL_PROT = 9508; //socket数据库端口

/*房间配置*/
$rooms = array(
	'a' => 'a'
);

//数据库配置
$db_config = array(
    'host'      => '120.27.44.55',
    'port'      => '3306',
    'user'      => 'phpweb',
    'pass'      => '123ghfhaifeng',
    'db'        => 'db_chat',
    'charset'   => 'utf8',
);

//mongodb数据库配置
global $mongodb_config;
$mongodb_config = array(
    'host'      => '127.0.0.1',
    'port'      => '27017',
    //'user'      => 'army',
    //'pass'      => 'army@123',
    'db'        => 'db_chat',
    //'charset'   => 'utf8',
);

// require_once "classes/db_class1.php";
// $db = new lib_mysqli($db_config['host'],$db_config['user'],$db_config['pass'],$db_config['db'],$db_config['port']);

require_once "classes/db_class.php";
$db = new ConnectMysqli($db_config);
