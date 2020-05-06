<?php

class Chat {
	protected $online_dir;
	private static $instance;

	public function __construct(){

		$this->online_dir = ONLINE_DIR;
	}

	public static function init(){
		if(self::$instance instanceof self){
			return false;
		}
		self::$instance = new self();
	}

	/**
	 * 登录
	 */
	public static function login($params){
		global $db;
		$data = [];

		// try {
		// 	echo "\n select_user_start:";
		// 	$user = $db->getRow("select * from chat_user where mobile = '". $params['params']['mobile'] ."'");
		// } catch (\Exception $e) {
		// 	echo "\n mysql_error:".$e->getCode.",ddd:".$e;
		// 	if ($e->getCode() == 'HY000') {
		// 		$db = new lib_mysqli($db_config['host'],$db_config['user'],$db_config['pass'],$db_config['db'],$db_config['port']);
		// 		$user = $db->getRow("select * from chat_user where mobile = '". $params['params']['mobile'] ."'");
		// 	} else {
		// 		echo "\n user_sql_error:".json_encode($e)."\n";
		// 	}
		// }

		$user = $db->getRow("select * from chat_user where mobile = '". $params['params']['mobile'] ."'");
        echo "登录查询用户信息，语句：".("select * from chat_user where mobile = '". $params['params']['mobile'] ."'")."\n";

		if(!$user){
            //无用户信息，新建用户
            $db->insert("chat_user",[
                'mobile' => $params['params']['mobile'],
                'password' => md5(trim($params['params']['password'])),
                'device_id' => empty($params['params']['device_id']) ? '1':$params['params']['device_id'],
                'add_time' => date("Y-m-d H:i:s")
            ]);

            $user = $db->getRow("select * from chat_user where mobile = '". $params['params']['mobile'] ."'");
        }

        if($user['password'] == md5(trim($params['params']['password']))){
            $data['is_success'] = 1;
            $data['user_id'] = $user['id'];

            //先给

            //需要修改的用户信息
            $user_data['is_online'] = 1;

            //判断是否为第一次登录
            if($params['params']['login_type'] != 'pc' && $user['device_id'] == ''){
                $data['isFirstDevice'] = 0;
            }else{
                //设备号一致
                if($params['params']['device_id'] == $user['device_id']){
                    $data['isFirstDevice'] = 1;
                }else{
                //设备号不一致
                    $data['isFirstDevice'] = 2;
                }
            }

            if($params['params']['device_id']){
                $user_data['device_id'] = $params['params']['device_id'];
            }

            echo "\n is_sms:".$params['params']['is_sms']."\n";
            if($params['params']['is_sms'] == 1 || $data['isFirstDevice'] < 2){
                //获取好友发送未接收信息
                $user_friend = $db->getAll("SELECT c.id as user_id, (SELECT count(*) FROM chat_log b WHERE b.receive_user_id = ". $user['id'] ." AND ( b.user_id = a.user_id OR b.user_id = a.friend_user_id ) AND add_time > '". $params['last_time'] ."' ) AS user_count FROM chat_user_friend a left join chat_user c on ((a.user_id = c.id or a.friend_user_id = c.id) and c.id <> ". $user['id'] .") WHERE ( a.user_id = ". $user['id'] ." OR a.friend_user_id = ". $user['id'] ." )");
                $data['user_friend'] = $user_friend;

                //获取用户所在群组发送未接收消息
                $user_group = $db->getAll("SELECT c.id,c.name, count(*) AS group_count FROM chat_log a left join chat_group c on c.id = a.receive_user_id WHERE a.log_type = 2 AND a.receive_user_id IN ( SELECT b.group_id FROM chat_user_group b WHERE b.user_id = ". $user['id'] ." ) GROUP BY a.receive_user_id");
                $data['user_group'] = $user_group;

                echo "\n is_sms_start:".$params['params']['is_sms']."\n";
                //修改用户在线状态
                $db->update("chat_user",$user_data,"mobile = '". $params['params']['mobile'] ."' and status = 0");
                //echo "登录用户修改用户在线状态-语句：".$db->get_update_db_sql("chat_user",$user_data,"mobile = '". $params['params']['mobile'] ."' and status = 0")."\n";

                //用户所在群组文件操作
                $user_groups = $db->getAll("select a.group_id,a.user_id,b.name from chat_user_group a left join chat_group b on a.group_id = b.id where b.status = 0 and a.status = 0 and a.user_id = ". $user['id']);

                echo "user_group:".json_encode($user_groups)."\n";

                //保存用户所以群组按群组ID加文件夹
                $user_file_class = new ChatUser();
                $user_group_file = $user_file_class->saveGroup($user_groups, $params['fd'],$params['params']['mobile']);

                //保存个人信息
                $user_file = $user_file_class->save(['fd'=>$params['fd'],'mobile'=>$params['params']['mobile'],'user_id'=>$user['id']],self::$instance->online_dir);
            }
        }else{
            $data['is_success'] = 2;
            $data['errmsg'] = '密码错误';
        }

		return $data;
	}

	//用户重新登录
	public static function reLogin( $data ){
		global $db;

		$pushMsg['code'] = 10;
		$pushMsg['data']['user_id'] = $data['user_id'];
		$pushMsg['data']['fd'] = $data['fd'];

		//获取用户信息
		$user_class = new ChatUser();
		//$user_info = $user_class->getUser($data['user_id']);
		//if($user_info){
			//获取好友发送未接收信息
			$user_friend = $db->getAll("SELECT c.id as user_id, (SELECT count(*) FROM chat_log b WHERE b.receive_user_id = ". $data['user_id'] ." AND ( b.user_id = a.user_id OR b.user_id = a.friend_user_id ) AND add_time > '". $data['last_time'] ."' ) AS user_count FROM chat_user_friend a left join chat_user c on ((a.user_id = c.id or a.friend_user_id = c.id) and c.id <> ". $data['user_id'] .") WHERE ( a.user_id = ". $data['user_id'] ." OR a.friend_user_id = ". $data['user_id'] ." )");
			$pushMsg['data']['user_friend'] = $user_friend;

			echo "\n relogin_sql_user_friend:SELECT c.id as user_id, (SELECT count(*) FROM chat_log b WHERE b.receive_user_id = ". $data['user_id'] ." AND ( b.user_id = a.user_id OR b.user_id = a.friend_user_id ) AND add_time > '". $data['last_time'] ."' ) AS user_count FROM chat_user_friend a left join chat_user c on ((a.user_id = c.id or a.friend_user_id = c.id) and c.id <> ". $data['user_id'] .") WHERE ( a.user_id = ". $data['user_id'] ." OR a.friend_user_id = ". $data['user_id'] ." )";

			//获取用户所在群组发送未接收消息
			$user_group = $db->getAll("SELECT c.id,c.name, count(*) AS group_count FROM chat_log a left join chat_group c on c.id = a.receive_user_id WHERE a.log_type = 2 AND a.receive_user_id IN ( SELECT b.group_id FROM chat_user_group b WHERE b.user_id = ". $data['user_id'] ." ) GROUP BY a.receive_user_id");
			$pushMsg['data']['user_group'] = $user_group;

			echo "\n relogin_sql_user_group:SELECT c.id,c.name, count(*) AS group_count FROM chat_log a left join chat_group c on c.id = a.receive_user_id WHERE a.log_type = 2 AND a.receive_user_id IN ( SELECT b.group_id FROM chat_user_group b WHERE b.user_id = ". $data['user_id'] ." ) GROUP BY a.receive_user_id";

			unset($user_group);

			//用户所在群组文件操作
			$user_groups = $db->getAll("select a.group_id,a.user_id,b.name from chat_user_group a left join chat_group b on a.group_id = b.id where b.status = 0 and a.status = 0 and a.user_id = ". $data['user_id']);

			$user_group_file = $user_class->saveGroup($user_groups, $data['fd'],'');

			echo "\n relogin:user_file_path:".self::$instance->online_dir.$data['user_id'];

			//保存个人信息
			$user_file = $user_class->save(['fd'=>$data['fd'],'mobile'=>'','user_id'=>$data['user_id']],self::$instance->online_dir);

			unset($user_groups);
			echo "\n relogin:user_file_result:".$user_file;

			if($user_file && $user_group_file){
				$pushMsg['data']['is_success'] = 1;
				$pushMsg['data']['msg'] = '登录成功';
			}else{
				$pushMsg['data']['is_success'] = 2;
				$pushMsg['data']['msg'] = '登录失败';
			}
		// }else{
		// 	//用户信息不存在
		// 	$pushMsg['data']['is_success'] = 3;
		// 	$pushMsg['data']['msg'] = '用户未登录';
		// }

		return $pushMsg;
	}

	//解散群组操作
	public static function groupDisband($data){
		global $db;

		$pushMsg['code'] = 11;
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['user_id'] = $data['user_id'];
		$pushMsg['data']['msg_type'] = $data['msg_type'];
		$pushMsg['data']['remains'] = array();

		//获取用户是否为群组创建者
		$group = $db->getRow("select * from chat_group where id = ".$data['group_id']);
		if($group){
			//用户ID非群组创建者
			if($data['user_id'] != $group['user_id']){
				$pushMsg['data']['is_success'] = 3;
				$pushMsg['data']['msg'] = "用户非群组创建者";
			}else{
				//获取此群组下的所有在线用户
				$tmp = self::remind($data);
				echo "remind内容：".json_encode($tmp)."\n";
				if($tmp){
					//$pushMsg['data']['newmessage'] = $tmp['msg'];
					$pushMsg['data']['remains'] = $tmp['remains'];
				}
				unset( $tmp );

				//删除群组文件信息
				$del_group = File::clearGroup('group/'. $data['group_id']. '/');
				if($del_group){
					//解散成功
					$pushMsg['data']['is_success'] = 1;
					$pushMsg['data']['msg'] = "成功";
				}else{
					//解散失败
					$pushMsg['data']['is_success'] = 2;
					$pushMsg['data']['msg'] = "失败";
				}
			}
		}else{
			//群组不存在
			$pushMsg['data']['is_success'] = 4;
			$pushMsg['data']['msg'] = "此群组不存在";
		}
	}

	//获取指定用户未读信息
	public static function userRead( $data ){
		global $db;

		$user = $db->getAll("select intro,add_time from chat_log where receive_user_id = ". $data['user_id'] ." and user_id = ". $data['friend_user_id'] ." and log_type = 1 and add_time >= '". $data['last_time'] ."' and status = 0 ");

		$pushMsg['code'] = 6;
		$pushMsg['data'] = $user;
		$pushMsg['friend_user_id'] = $data['friend_user_id'];

		return $pushMsg;
	}

	//获取指定用户所在群组未读信息
	public static function userReadGroup( $data ){
		global $db;

		$user = $db->getAll("select intro,add_time from chat_log where receive_user_id = ". $data['user_id'] ." and user_id = ". $data['group_id'] ." and log_type = 2 and add_time >= '". $data['last_time'] ."' and status = 0 ");

		$pushMsg['code'] = 7;
		$pushMsg['data'] = $user;
		$pushMsg['group_id'] = $data['group_id'];

		return $pushMsg;
	}

	/**
	* 用户创建群组操作
	*/
	public static function addGroup($data){
		global $db;

		$pushMsg['code'] = 12;
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['msg_type'] = $data['msg_type'];
		$pushMsg['data']['group_id'] = $data['group_id'];
		$pushMsg['data']['group_name'] = $data['group_name'];
		$pushMsg['data']['remains'] = array();

		//先创建群组文件信息
		$tt = File::checkDir(self::$instance->online_dir.'group/'. $data['group_id'] .'/', false);
		echo "\n group_file:".self::$instance->online_dir.'group/'. $data['group_id'] .'/';
		echo "\n group_file_tt:".$tt;

		$user_class = new ChatUser();
		//判断是否有用户
		$users = explode(",",$data['group_user']);
		foreach($users as $_k=>$_v){
			if($_v){
				$user_info = $user_class->getUser($_v);
				echo "\n add_group_user_info:".$_v.",user_info:".json_encode($user_info);
				//用户信息在线
				if($user_info){
					//保存个人信息
					$user_file = $user_class->save(['fd'=>$user_info['fd'],'mobile'=>$user_info['mobile'],'user_id'=>$user_info['user_id']],self::$instance->online_dir.'group/'. $data['group_id'] .'/');
					echo "\n add_group_save_user_result:".$_v.",user_file:".$user_file;

					$pushMsg['data']['remains'][$_k]['fd'] = $user_info['fd'];
					$pushMsg['data']['remains'][$_k]['user_id'] = $user_info['user_id'];
				}
				unset($user_info);
			}
		}

		if($tt){
			$pushMsg['data']['is_success'] = 1;
			$pushMsg['data']['msg'] = '操作成功';
		}else{
			$pushMsg['data']['is_success'] = 2;
			$pushMsg['data']['msg'] = '操作失败';
		}

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
	* 获取文件用户信息
	*/
	public static function logout($user_id){
		$user = new ChatUser();
		$userInfo = $user->getUser($user_id);
		return $userInfo;
	}

	/**
	* 登录信息错误
	*/
	public static function noLogin( $data ){
		$pushMsg['code'] = 5;
		$pushMsg['msg'] = "系统不会存储您的密码";
		if( !$data['params']['mobile']){
			$pushMsg['msg'] = "手机号不能为空";
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
		$pushMsg['msg'] = 'success';
		$pushMsg['data']['mine'] = 0;
		//$pushMsg['data']['rooms'] = self::getRooms();
		//$pushMsg['data']['users'] = self::getOnlineUsers();
		unset( $data );
		return $pushMsg;
	}

	/**
	* 用户退出
	*/
	public static function doLogout( $data ){
		global $db;
		//删除

		$users = self::logout($data['user_id']);

		//用户所在群组
		$user_group = $db->getAll('select group_id,user_id,status from chat_user_group where status = 0 and user_id= '. $data['user_id']);

		File::logout($data['user_id'],$user_group);
		$pushMsg['code'] = 3;
		$pushMsg['msg'] = $users['mobile']."退出";
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['mobile'] = $users['mobile'];
		$pushMsg['data']['user_id'] = $data['user_id'];
		$pushMsg['data']['type'] = 'logout';

		unset( $data );

		$db->update("chat_user",['is_online'=>0],"mobile = '". $users['mobile'] ."'");
		//echo "\n logout_sql:".$db->get_update_db_sql("chat_user",['is_online'=>0],"mobile = '". $users['mobile'] ."'");
		unset( $users );

		return $pushMsg;
	}

	//发送新消息
	public static function sendNewMsg( $data ){
		global $db;

		//生成消息唯一编号
		$log_number = date('YmdHis').rand(100000,999999);

		$log_data = array(
			'user_id' => $data['user_id'],
			'add_time' => date("Y-m-d H:i:s"),
			'log_type' => $data['msg_type'],
			'source_type' => $data['source_type'],
			'log_number' => $log_number
		);

		if($data['msg_type'] == 1){
			$log_data['receive_user_id'] = $data['receive_user_id'];
			$pushMsg['data']['receive_user_id'] = $data['receive_user_id'];
		}else{
			$log_data['receive_user_id'] = $data['group_id'];
			$pushMsg['data']['group_id'] = $data['group_id'];
		}

		$pushMsg['code'] = 2;
		$pushMsg['msg'] = "";
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['user_id'] = $data['user_id'];
		//$pushMsg['data']['newmessage'] = escape(htmlspecialchars($data['message']));
		$pushMsg['data']['newmessage'] = $data['newmessage'];
		$pushMsg['data']['msg_type'] = $data['msg_type'];
		$pushMsg['data']['c'] = $data['c'];
		$pushMsg['data']['source_type'] = $data['source_type'];
		$pushMsg['data']['is_burn_read'] = $data['is_burn_read'] ? $data['is_burn_read'] : 0;
		$pushMsg['data']['read_time'] = $data['read_time'] ? $data['read_time'] : 0;
		$pushMsg['data']['log_number'] = $log_number;
		$pushMsg['data']['remains'] = array();

		if($data['c'] == 'img'){
			//图片处理
			if($data['source_type'] == 'pc'){
				$pushMsg['data']['newmessage'] = '<img class="chat-img" onclick="preview(this)" style="display: block; max-width: 120px; max-height: 120px; visibility: visible;" src='.$pushMsg['data']['newmessage'].'>';
			}
		}else if($data['c'] == 'audio'){
			//音频文件文件处理
		}else if($data['c'] == 'video'){
			//视频文件文件处理
		}else {
			//文本处理
			// global $emotion;
			// foreach($emotion as $_k => $_v){
			// 	$pushMsg['data']['newmessage'] = str_replace($_k,$_v,$pushMsg['data']['newmessage']);
			// }
		}

		$tmp = self::remind($data);
		echo "remind内容：".json_encode($tmp)."\n";
		if($tmp){
			//$pushMsg['data']['newmessage'] = $tmp['msg'];
			$pushMsg['data']['remains'] = $tmp['remains'];
		}
		unset( $tmp );

		$pushMsg['data']['time'] = time();

		$log_data['intro'] = $pushMsg['data']['newmessage'];

		//生成sql语句
		if($data['msg_type'] == 1){
			$sql = "insert into chat_log (user_id,log_number,receive_user_id,log_type,source_type,intro,add_time,is_burn_read,read_time,is_read) values (". $data['user_id'] .",'". $log_number ."',". $data['receive_user_id'] .",". $data['msg_type'] .",'". $data['source_type'] ."','". $pushMsg['data']['newmessage'] ."','". date("Y-m-d H:i:s") ."',". $pushMsg['data']['is_burn_read'] .",". $pushMsg['data']['read_time'] .",1)";
		}else{
			$sql = "insert into chat_log (user_id,log_number,receive_user_id,log_type,source_type,intro,add_time) values (". $data['user_id'] .",'". $log_number ."',". $data['group_id'] .",". $data['msg_type'] .",'". $data['source_type'] ."','". $pushMsg['data']['newmessage'] ."','". date("Y-m-d H:i:s") ."')";
		}

		echo "pushMsg消息推送插入数据库信息：".json_encode($pushMsg)."\n";

		//$tt = $db->query_sql($sql);
		//echo "\n log_insert_sql_list:".$db->get_insert_db_sql("chat_log",$log_data)."\n";
		unset($log_data);
		unset( $data );
		unset($sql);

		echo "消息推送信息：".json_encode($pushMsg)."\n";

		return $pushMsg;
	}

	//登录
	public static function doLogin( $data ){
		$pushMsg['code'] = 1;
		//$pushMsg['msg'] = $data['params']['mobile']."登录";

		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['mobile'] = $data['params']['mobile'];
		$pushMsg['data']['time'] = time();

		$tmp = self::login($data);
		$pushMsg['data']['result'] = $tmp;
		$pushMsg['data']['result']['login_type'] = $data['params']['login_type'];
		$pushMsg['data']['result']['is_sms'] = $data['params']['is_sms'];

		unset( $data );
		return $pushMsg;
	}

	/**
	* 阅后即焚通知
	*/
	public static function sendBurnRead($data){
		global $db;

		$user = new ChatUser();

		$friend_user = $user->getUser($data['friend_user_id']);

		$pushMsg['code'] = 8;
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['friend_user_id'] = $data['friend_user_id'];
		$pushMsg['data']['user_id'] = $data['user_id'];
		$pushMsg['data']['log_number'] = $data['log_number'];
		$pushMsg['data']['remains'][0]['fd'] = $friend_user['fd'];

		$db->update("chat_log",['is_read'=>0],"log_number = '". trim($data['log_number']) ."'");
		unset($friend_user);

		return $pushMsg;
	}

	/**
	* 群组中的用户操作处理
	*/
	public static function groupUser($data){
		global $db;

		$user = new ChatUser();

		$pushMsg['code'] = 9;
		$pushMsg['data']['user_id'] = $data['user_id'];
		$pushMsg['data']['fd'] = $data['fd'];
		$pushMsg['data']['group_id'] = $data['group_id'];
		$pushMsg['data']['operation_type'] = $data['operation_type'];

		$user_arr = explode(",",$data['user_id']);
		echo "\n user_arr:".json_encode($user_arr);

		$pushMsg['data']['remains'] = [];

		foreach($user_arr as $_v){
			if(trim($_v)){
				//获取用户所在群组中是否存在
				$user_group = $db->getRow("select * from chat_user_group where group_id = ". $data['group_id'] ." and user_id = ". trim($_v) ." and status = 0");

				echo "\n sql1:select * from chat_user_group where group_id = ". $data['group_id'] ." and user_id = ". trim($_v) ." and status = 0";

				//记录操作结果
				$result_arr = [];

				$user_class = new ChatUser();

				//读取用户信息
				$user_info = $user_class->getUser(trim($_v));

				//判断是添加，还是退出
				if($data['operation_type'] == 'add'){
					if($user_group){
						//在群组中存在
						$result_arr['user_id'] = trim($_v);
						$result_arr['is_success'] = 3;
						$result_arr['msg'] = '您已在此群组中，不需要再次加入!';

						array_push($pushMsg['data']['remains'],$result_arr);
					}else{
						//添加用户到群组
						$sql = "insert into chat_user_group (group_id,user_id,add_time) values (". $data['group_id'] .",". trim($_v) .",'". date("Y-m-d H:i:s") ."')";

						echo "\n sql2:".$sql;

						//$tt = $db->query_sql($sql);
						unset($sql);

						if($tt){
							$result_arr['is_success'] = 1;
							$result_arr['msg'] = 'OK';
						}else{
							$result_arr['is_success'] = 2;
							$result_arr['msg'] = 'ERROR';
						}

						if($user_info){
							//把用户信息添加到所在群组文件中
							$user_file = $user->save(['fd'=>$user_info['fd'],'mobile'=>$user_info['mobile'],'user_id'=>trim($_v)],'group/'. $data['group_id'] .'/');

							$result_arr['fd'] = $user_info['fd'];
						}
					}
				}else if($data['operation_type'] == 'delete'){
					//把用户信息从数据表中删除
					$tt = $db->update("chat_user_group",['status'=>1],"user_id = ". trim($_v) ." and group_id = ". $data['group_id'] ." and status = 0");

					if($tt){
						$result_arr['is_success'] = 1;
						$result_arr['msg'] = 'OK';
					}else{
						$result_arr['is_success'] = 2;
						$result_arr['msg'] = 'ERROR';
					}

					if($user_info){
						//把用户信息从所在群组中删除
						File::logout('group/'. $data['group_id'] .'/' . trim($_v));

						$result_arr['fd'] = $user_info['fd'];
					}
				}

				$result_arr['user_id'] = trim($_v);

				array_push($pushMsg['data']['remains'],$result_arr);

				unset($result_arr);
			}
		}

		return $pushMsg;
	}

	//组织返回信息格式
	public static function remind($params){
		global $db;
		$data = array();

		$msg_type = $params['msg_type'];

		$user = new ChatUser();

		if($msg_type == 1){
			//一对一
			$users = $user->getUser($params['receive_user_id']);

			$data['remains'][0]['user_id'] = $params['receive_user_id'];

			if($users && !empty($users['fd']) ){
				$data['remains'][0]['fd'] = $users['fd'];
			}else{
				$data['remains'][0]['fd'] = '';
			}

			unset($users);
		}else{
			$data['remains'] = [];
			// 群组,查找该群组下的在线用户信息
			$group_users = $user->getGroupOnlineUsers($params['group_id']);

			if($group_users){
				$new_user = [];
				foreach($group_users as $_k => $_v){
					$new_user = [];
					$user_info = $user->getUser('group/'. $params['group_id'] . '/' .$_v);
					//排除掉发送者本人的信息，
					if($user_info && $user_info['user_id'] != $params['user_id']){
						$new_user['user_id'] = $_v;
						$new_user['fd'] = $user_info['fd'];

						array_push($data['remains'],$new_user);
						unset($new_user);
					}else{
						//$data['remains'][$_k]['fd'] = '';
					}
				}
			}else{
				$data['remains'] = '';
			}
		}

		return $data;
	}
}