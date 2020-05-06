<?php
$link=new swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_SYNC);//TCP方式、同步
$link->connect('192.168.80.39',9508);//连接
$link->send('select * from chat_user');//执行查询
$res=$link->recv();
 
if(!$res){
    echo 'Failed!';
}
else{
    print_r($res);
}
$link->close();