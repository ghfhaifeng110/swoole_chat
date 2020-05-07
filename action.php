<?php
require_once("../server/classes/db_class1.php");

$db_config = array(
    'host'      => '192.168.80.32',
    'port'      => '3306',
    'user'      => 'army',
    'pass'      => 'army@123',
    'db'        => 'army_chat',
    'charset'   => 'utf8',
);

$db = new lib_mysqli($db_config['host'],$db_config['user'],$db_config['pass'],$db_config['db'],$db_config['port']);

//$type = $_POST['type'];
$type ='login';

if($type == 'login'){
    $mobile = $_POST['mobile'];
    $password = $_POST['password'];

    $user = $db->get_row("select * from chat_user where mobile = '". $mobile ."'");
    if($user){
        if($user['password'] == $password){
            //$db->update("chat_user",['is_online'=>1],"mobile = '". $mobile ."'");
            echo json_encode(['errcode'=>0,'errmsg'=>'登录成功']);
        }else{
            echo json_encode(['errcode'=>2,'errmsg'=>'密码错误']);
        }
    }else{
        echo json_encode(['errcode'=>1,'errmsg'=>'此用户不存在！']);
    }
}