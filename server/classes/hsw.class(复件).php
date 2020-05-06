<?php
class hsw {
	private $serv = null;
	public function __construct(){
		//初始化文件类
		//File::init();
		//Chat::init($socket_server_ip);

		$this->serv = new Swoole\WebSocket\Server($socket_server_ip,$socket_server_port);
		$this->serv->set(array(
			'task_worker_num'     => TASK_WORKER_NUM, //线程个数
			// 'heartbeat_idle_time' => 600,
			// 'heartbeat_check_interval' => 60,
			'daemonize' => DAEMONIZE, //是否作为守护进程
			'log_file' => LOG_FILE,
		));
		$this->serv->on("open",array($this,"onOpen")); //客户端与服务器建立连接并完成握手后会回调此函数
		$this->serv->on("message",array($this,"onMessage")); //服务器收到来自客户端的数据帧
		$this->serv->on("Task",array($this,"onTask")); //进程任务
		$this->serv->on("Finish",array($this,"onFinish")); //程投递的任务完成时
		$this->serv->on("close",array($this,"onClose")); //客户端连接关闭后
		echo "222";
		$this->serv->start(); //启动服务器
	}
	
	/**
	* 客户端与服务器建立连接并完成握手后会回调此函数
	* $request 是一个Http请求对象，包含了客户端发来的握手请求信息
	* $fd 客户端与服务器建立连接后生成的客户端唯一ID，
	*/
	public function onOpen( $serv , $request ){
		echo "open";
		$data = array(
			'task' => 'open', //进程任务名称
			'fd' => $request->fd
		);
		$this->serv->task( json_encode($data) ); //加入进程任务
		echo "open\n";
	}
	
	/**
	* 服务器收到来自客户端的数据帧
	* $frame 是swoole_websocket_frame对象，包含了客户端发来的数据帧信息 -- json格式
	*/
	public function onMessage( $serv , $frame ){
		$data = json_decode( $frame->data , true );

		switch($data['type']){
			case 'login'://登录
				echo "登录开始-->";
				echo "登录原数据：". $frame->data ."\n";
				$data = array(
					'task' => 'login',
					'params' => array(
						'mobile' => $data['mobile'],
						'password' => $data['password'],
						'last_time' => $data['last_time'],
						'device_id' => $data['device_id'],
						'login_type' => $data['login_type'],
						'is_sms' => $data['is_sms']
					),
					'fd' => $frame->fd
				);
				if(!$data['params']['mobile'] || !$data['params']['password'] ){
					$data['task'] = "nologin"; //登录信息错误
					$this->serv->task( json_encode($data) );
					break;
				}
				echo "登录数据提交到进程任务。\n";
				$this->serv->task( json_encode($data) );
				break;
			case 'message': //新消息
				echo "消息开始-->";
				echo "消息原数据：". $frame->data ."\n";
				$data['task'] = 'message';
				$data['fd'] = $frame->fd;

				if($data['msg_type'] == 1){
					$data['receive_user_id'] = $data['receive_user_id'];
				}else{
					$data['group_id'] = $data['group_id'];
				}
				echo "消息数据提交到进程任务。\n";
				$this->serv->task( json_encode($data) );
				break;
			case 'user_read': //获取指定用户的未读信息
				$data['task'] = 'user_read';
				$data['fd'] = $frame->fd;
				
				$this->serv->task( json_encode($data) );
				break;
			case 'user_read_group': //获取指定用户的未读信息
				$data['task'] = 'user_read_group';
				$data['fd'] = $frame->fd;
				
				$this->serv->task( json_encode($data) );
				break;
			case 'logout':
				$data['task'] = 'logout';
				$data['fd'] = $frame->fd;

				echo "退出数据提交到进程任务。\n";
				$this->serv->task( json_encode($data) );
				break;
			case 'burn_read': //阅后即焚消息，阅读后通知给对方
				echo "阅后即焚通知消息开始-->";
				echo "阅后即焚通知消息原数据：". $frame->data ."\n";
				$data['task'] = 'burn_read';
				$data['fd'] = $frame->fd;
				
				echo "阅后即焚通知消息数据提交到进程任务。\n";
				$this->serv->task( json_encode($data) );
				break;
			case 'group_user':
				echo "群组用户操作消息开始-->";
				echo "群组用户操作消息原数据：". $frame->data ."\n";
				$data['task'] = 'group_user';
				$data['fd'] = $frame->fd;
				$data['msg_type'] = 2;
				
				echo "群组用户操作消息数据提交到进程任务。\n";
				$this->serv->task( json_encode($data) );
				break;
			case 'relogin':
				echo "用户重新登录操作消息开始-->";
				echo "用户重新登录操作消息原数据：". $frame->data ."\n";
				$data['task'] = 'relogin';
				$data['fd'] = $frame->fd;
				$data['msg_type'] = 1;
				
				echo "用户重新登录消息数据提交到进程任务。\n";
				$this->serv->task( json_encode($data) );
				break;
			case 'group_disband':
				echo "群组解散操作消息开始-->";
				echo "群组解散操作消息原数据：". $frame->data ."\n";
				$data['task'] = 'group_disband';
				$data['fd'] = $frame->fd;
				$data['msg_type'] = 2;
				
				echo "群组解散消息数据提交到进程任务。\n";
				$this->serv->task( json_encode($data) );
				break;
			case 'add_group':
				echo "创建群组操作消息开始-->";
				echo "创建群组操作消息原数据：". $frame->data ."\n";
				$data['task'] = 'add_group';
				$data['fd'] = $frame->fd;
				$data['msg_type'] = 2;
				
				echo "创建群组消息数据提交到进程任务。\n";
				$this->serv->task( json_encode($data) );
				break;
			default :
				$this->serv->push($frame->fd, json_encode(array('code'=>0,'msg'=>'type error')));
		}
	}

	/**
	* 进程任务
	* $task_id是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id 和 $from_id 组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
	* $from_id 来自于哪个worker进程
	* $data 是任务的内容
	*/
	public function onTask( $serv , $task_id , $from_id , $data ){
		$pushMsg = array('code'=>0,'msg'=>'','data'=>array());
		$data = json_decode($data,true);

		switch( $data['task'] ){
			case 'open':
				$pushMsg = Chat::open( $data );
				$this->serv->push( $data['fd'] , json_encode($pushMsg) );
				return 'Finished';
			case 'login':
				echo "登录进程任务中的数据：". json_encode($data,true)."\n";
				$pushMsg = Chat::doLogin( $data );
				break;
			case 'message':
				echo "消息进程任务中的数据：". json_encode($data,true)."\n";
				$pushMsg = Chat::sendNewMsg( $data );
				break;
			case 'logout':
				echo "退出进程任务中的数据：". json_encode($data,true)."\n";
				$pushMsg = Chat::doLogout( $data );
				break;
			case 'nologin':
				$pushMsg = Chat::noLogin( $data );
				$this->serv->push( $data['fd'] ,json_encode($pushMsg));
				return "Finished";
			case 'user_read':
				$pushMsg = Chat::userRead( $data );
				$pushMsg['fd'] = $data['fd'];
				$this->serv->push( $data['fd'] , json_encode($pushMsg) );
				return 'User Read Finished';
			case 'user_read_group':
				$pushMsg = Chat::userReadGroup( $data );
				$pushMsg['fd'] = $data['fd'];
				$this->serv->push( $data['fd'] , json_encode($pushMsg) );
				return 'User Read Group Finished';
			case 'burn_read':
				echo "阅后即焚通知消息进程任务中的数据：". json_encode($data,true)."\n";
				$pushMsg = Chat::sendBurnRead( $data );
				break;
			case 'group_user':
				echo "群组用户操作消息进程任务中的数据：". json_encode($data,true)."\n";
				$pushMsg = Chat::groupUser( $data );
				break;
			case 'relogin':
				echo "用户重新登录操作消息进程任务中的数据：". json_encode($data,true)."\n";
				$pushMsg = Chat::reLogin( $data );
				break;
			case 'group_disband':
				echo "群组解散操作消息进程任务中的数据：". json_encode($data,true)."\n";
				$pushMsg = Chat::groupDisband( $data );
				break;
			case 'add_group':
				echo "创建群组操作消息进程任务中的数据：". json_encode($data,true)."\n";
				$pushMsg = Chat::addGroup( $data );
				break;
		}
		echo "pushMsg数据:".json_encode($pushMsg,JSON_UNESCAPED_UNICODE)."\n";
		$this->sendMsg($pushMsg,$data['fd'],$data['msg_type']);
		return "Finished";
	}
	
	/**
	* 客户端连接关闭后
	* $fd 是连接的文件描述符
	*/
	public function onClose( $serv , $fd ){
		$pushMsg = array('code'=>0,'msg'=>'','data'=>array());

		//获取用户信息
		$user = Chat::logout($fd);
		if($user){
			$data = array(
				'task' => 'logout',
				'params' => array(
					'user_id' => $user['user_id'],
					'mobile' => $user['mobile']
				),
				'fd' => $fd
			);
			//$this->serv->task( json_encode($data) );
		}
		
		echo "client {$fd} closed\n";
	}
	
	/**
	* 推送信息
	* $pushMsg 推送的内容
	* $myfd 推送者$fd
	* $msg_type 推送类型 1单推，2群推
	*/
	public function sendMsg($pushMsg,$myfd,$msg_type){
		echo "推送的信息：". json_encode($pushMsg)."\n";

		//发送给自己一个推送，确认已经成功获取信息
		//如果是退出，就不需要再发送给本人了，因为他已经断开连接了，无法发送成功，会报错，要判断处理掉
		if($pushMsg['data']['type'] != 'logout'){
			$pushMsg['data']['mine'] = 1;
			if($myfd){
				$this->serv->push($myfd, json_encode($pushMsg));
			}
		}

		if($msg_type == 1){		
			if($pushMsg['data']['remains'][0]['fd']){
				$pushMsg['data']['mine'] = 0;
				echo "\n sendMsg-remains:".json_encode($pushMsg,JSON_UNESCAPED_UNICODE);
				$this->serv->push($pushMsg['data']['remains'][0]['fd'], json_encode($pushMsg));
			}
		}else{
			echo "msgType<>1:".json_encode($pushMsg)."\n";
			if($pushMsg['data']['remains']){
				foreach($pushMsg['data']['remains'] as $_k => $_v) {
					if($_v['fd']){
						$pushMsg['data']['mine'] = 0; //来自其它客户端

						echo "msgType<>1-0:".json_encode($pushMsg)."\n";
						$this->serv->push($_v['fd'], json_encode($pushMsg));
					}
				}
			}
		}
	}
	
	/**
	* 进程中的任务完成时调用，发送调试信息
	* $task_id 任务ID
	* $data 内容
	*/
	public function onFinish( $serv , $task_id , $data ){
		echo "Task {$task_id} finish\n";
        echo "Result: {$data}\n";
	}
}