<?php
//require_once "db_class1.php";
//$db = new lib_mysqli($db_config['host'],$db_config['user'],$db_config['pass'],$db_config['db'],$db_config['port']);

class Chat {

	/**
	 * 登录判断，并返回用户的未读信息条数
	 */
	public static function login($fd,$params){
		global $db;
		$data = [];

		$user = $db->get_row("select * from chat_user where mobile = '". $params['mobile'] ."'");
		if($user){
			if($user['password'] == md5(trim($params['password']))){
				$data['is_success'] = 0;
				$data['user_id'] = $user['id'];

				//修改用户在线状态
				$db->update("chat_user",['is_online'=>1],"mobile = '". $params['mobile'] ."'");

				//修改或添加用户FD
				$is_fd = $db->get_one("select * from chat_user_fd where mobile = '". $params['mobile'] ."'");
				if($is_fd){
					$db->update("chat_user_fd",['fd'=>$fd],"mobile = '". $params['mobile'] ."'");
				}else{
					$fd_data['user_id'] = $user['id'];
					$fd_data['mobile'] = $params['mobile'];
					$fd_data['fd'] = $fd;
					$db->insert("chat_user_fd",$fd_data);
				}

				//获取好友发送未接收信息
				//$user_friend = $db->get_all("SELECT c.mobile, (SELECT count(*) FROM chat_log b WHERE b.receive_user_id = ". $user['id'] ." AND ( b.user_id = a.user_id OR b.user_id = a.friend_user_id ) AND add_time > '". $params['last_time'] ."' ) AS user_count FROM chat_user_friend a left join chat_user c on ((a.user_id = c.id or a.friend_user_id = c.id) and c.id <> ". $user['id'] .") WHERE ( a.user_id = ". $user['id'] ." OR a.friend_user_id = ". $user['id'] ." )");
				//$data['user_friend'] = $user_friend;

				//获取用户所在群组发送未接收消息
				//$user_group = $db->get_all("SELECT c.id,c.name, count(*) AS group_count FROM chat_log a left join chat_group c on c.id = a.receive_user_id WHERE a.log_type = 2 AND a.receive_user_id IN ( SELECT b.group_id FROM chat_user_group b WHERE b.user_id = ". $user['id'] ." ) GROUP BY a.receive_user_id");
				//$data['user_group'] = $user_group;

			}else{
				$data['is_success'] = 2;
				$data['msg'] = '密码错误';
			}
		}else{
			$data['is_success'] = 1;
			$data['msg'] = '此用户不存在！';
		}

		// $user_file = new ChatUser(array(
		// 	'fd'        => $fd,
		// 	'mobile'	=> htmlspecialchars($params['mobile'])
		// ));
		// if(!$user_file->save()){
		// 	throw new Exception('This nick is in use.');
		// }

		return $data;
	}

	//获取最近联系记录
	public static function record( $data ){
		global $db;

		$user = $db->get_all("select a.friend_user_id,b.mobile,b.is_online,b.avatar from chat_record a LEFT JOIN chat_user b ON a.friend_user_id = b.id WHERE (a.user_id = ". $data['params']['id'] .") AND b. STATUS = 0 ORDER BY a.update_time DESC");

		$pushMsg['code'] = 7;
		$pushMsg['time'] = date("H:i");
		$pushMsg['data'] = $user;

		return $pushMsg;
	}

	/**
	 * 获取用户在线列表
	 *
	 */
	public static function getOnlineUsers(){
		$user = new ChatUser();
		$lists = $user->getOnlineUsers();
		$users = array();
		foreach($lists as $_k => $_v){
			$users[$_k] = $user->getUsers($_k,array_slice($_v, 0, 100));
		}
		unset( $lists );
		return $users;
	}
	
	/**
	* 获取用户退出信息
	* $fd 客户端与服务器建立连接后生成的客户端唯一ID，
	*/
	public static function logout($fd){
		global $db;

		$user = $db->get_row("select * from chat_user_fd where fd = ". $fd);

		return $user;
	}

	/**
	* 登录信息错误
	*/
	public static function noLogin( $data ){
		$pushMsg['code'] = 5;
		$pushMsg['data']['msg'] = "手机号或密码不能为空，系统不会存储您的密码";
		if( !$data['params']['mobile']){
			$pushMsg['data']['msg'] = "手机号不能为空!";
		}
		$pushMsg['data']['mine'] = 1;
		unset( $data );
		return $pushMsg;
	}
	
	/**
	* 建立连接
	*/
	public static function open( $data ){
		$pushMsg['code'] = 4;
		$pushMsg['data']['msg'] = '成功建立连接';
		$pushMsg['data']['mine'] = 0;
		//$pushMsg['data']['rooms'] = self::getRooms();

		unset( $data );
		return $pushMsg;
	}

	/**
	* 用户退出
	*/
	public static function doLogout( $data ){
		global $db;
		//删除
		File::logout($data['fd']);
		$pushMsg['code'] = 3;
		$pushMsg['data']['msg'] = $data['params']['user_id']."退出";
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['user_id'] = $data['params']['user_id'];
		unset( $data );

		$db->update("chat_user",['is_online'=>0],"user_id = ". $pushMsg['data']['user_id'] ."");

		return $pushMsg;
	}

	//发送新消息
	public static function sendNewMsg( $data ){
		global $db;

		//$user1 = $db->get_row("select * from chat_user where mobile = '". $data['params']['mobile'] ."'");
		$log_data = array(
			'user_id' => $data['params']['user_id'],
			'add_time' => date("Y-m-d H:i:s"),
			'log_type' => $data['msg_type']
		);

		$pushMsg['code'] = 2;
		$pushMsg['msg'] = "";
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['c'] = $data['c'];
		$pushMsg['data']['user_id'] = $data['params']['user_id'];
		$pushMsg['data']['newmessage'] = escape(htmlspecialchars($data['newmessage']));
		$pushMsg['data']['receive_user_id'] = $data['receive_user_id'];
		$pushMsg['data']['msg_type'] = $data['msg_type'];
		$pushMsg['data']['remains'] = array();
		if($data['c'] == 'img'){
			//$log_data['intro'] = $pushMsg['data']['newmessage'];

			$pushMsg['data']['newmessage'] = '<img class="chat-img" onclick="preview(this)" style="display: block; max-width: 120px; max-height: 120px; visibility: visible;" src='.$pushMsg['data']['newmessage'].'>';
		} else {
			global $emotion;
			foreach($emotion as $_k => $_v){
				$pushMsg['data']['newmessage'] = str_replace($_k,$_v,$pushMsg['data']['newmessage']);
			}
		}

		//$tmp = self::remind($pushMsg['data']['newmessage'],$data['msg_type'],$data['receive_mobile']);

		//
		if($data['msg_type'] == 1){
			$tmp[0]['receive_user_id'] = $data['receive_user_id'];

			//$user2 = $db->cache_row("select * from chat_user where mobile = '". $data['receive_mobile'] ."'");
			//if($user2 && $user2['is_online'] == 1){
				$fd_user = $db->get_row("select * from chat_user_fd where user_id = '". $data['receive_user_id'] ."'");
				echo "\n fd_user:select * from chat_user_fd where user_id = '". $data['receive_user_id'] ."'";
				if($fd_user){
					$tmp[0]['fd'] = $fd_user['fd'];
				}else{
					$tmp[0]['fd'] = '';
				}
			// }else{
			// 	$tmp[0]['fd'] = '';
			// }

			$log_data['receive_user_id'] = $data['receive_user_id'];

			$log_data['type'] = 'user';
		}


		if($tmp){
			//$pushMsg['data']['newmessage'] = $tmp['msg'];
			$pushMsg['data']['remains'] = $tmp;
		}else{
			$pushMsg['data']['remains'] = '';
		}
		unset( $tmp );

		$log_data['intro'] = $pushMsg['data']['newmessage'];

		$pushMsg['data']['time'] = date("H:i",time());
		unset( $data );

		$db->insert("chat_log",$log_data);

		return $pushMsg;
	}

	/**
	* 进程任务中的登录
	*/
	public static function doLogin( $data ){
		$pushMsg['code'] = 1;		
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['mobile'] = $data['params']['mobile'];
		$pushMsg['data']['time'] = date("H:i",time());

		$tmp = self::login($data['fd'],$data['params']);
		$pushMsg['data']['result'] = $tmp;

		unset( $data );
		return $pushMsg;
	}
	
	public static function remind($msg,$msg_type,$receive_mobile){
		global $db;
		$data = array();
		if( $msg != ""){
			if($receive_mobile){
				$user = new ChatUser();
				$users = $user->getUsersAll();

				$m3 = array();
				foreach($users as $_k => $_v){
					$m3[$_v['mobile']] = $_v['fd'];
				}

				if($msg_type == 1){
					//一对一
					if(array_key_exists($receive_mobile, $m3)){
						echo "m3-fd:".$m3[$receive_mobile]."\n";
						//$data['msg'] = str_replace($m1[$_k],'<font color="blue">'.trim($m1[$_k]).'</font>',$data['msg']);
						//$data['remains'][0]['msg'] = $data['msg'];
						$data['remains'][0]['fd'] = $m3[$receive_mobile];
						$data['remains'][0]['mobile'] = $receive_mobile;
					}
				}else{
					//查询群组下的用户
					$user_group = $db->get_all("select a.group_id,c.id,c.username,c.mobile from chat_user_group a left join chat_user c on c.id = a.user_id where a.user_id in (select id from chat_user b where b.status = 0 ) and a.group_id = ". $receive_mobile);
					//正则匹配出所有@的人来
					//$s = preg_match_all( '~@(.+?)　~' , $msg, $matches  ) ;
					if($user){
						//$m1 = array_unique( $matches[0] );
						//$m2 = array_unique( $matches[1] );

						$i = 0;
						foreach($user_group as $_k => $_v){
							if(array_key_exists($_v['id'],$m3)){
								//$data['msg'] = str_replace($m1[$_k],'<font color="blue">'.trim($m1[$_k]).'</font>',$data['msg']);
								//$data['remains'][$i]['msg'] = $data['msg'];
								$data['remains'][$i]['fd'] = $m3[$_v['mobile']];
								$data['remains'][$i]['mobile'] = $_v['mobile'];
								$i++;
							}
						}
						//unset($m1,$m2,$m3);
					}
				}
				//unset($m1,$m2,$m3);
				unset($users);
			}
		}
		return $data;
	}
}