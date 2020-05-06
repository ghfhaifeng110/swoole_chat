<?php
class File {
	private static $instance;
	protected $online_dir;
    protected $history = array();
    protected $history_max_size = 100;
    protected $history_write_count = 0;
	
	private function __construct() {
		global $rooms;
        $this->online_dir = ONLINE_DIR;
		// foreach($rooms as $_k => $_v){
		// 	$this->checkDir($this->online_dir.$_k.'/', true);
		// }
    }
	public static function init(){
		if(self::$instance instanceof self){
			return false;
		}
		self::$instance = new self();
	}
	
	public static function clearDir($dir) {
        $n = 0;
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '.' or $file == '..') {
                    continue;
                }
                if (is_file($dir . $file)) {
                    unlink($dir . $file);
                    $n++;
                }
                if (is_dir($dir . $file)) {
                    self::clearDir($dir . $file . '/');
                    $n++;
                }
            }
        }
        closedir($dh);
        return $n;
	}
	
	//删除群组文件
	public static function clearGroup($dir){
		self::clearDir(self::$instance->online_dir.$dir);
	}
	
	public static function changeUser($fd){
		$old = self::$instance->online_dir.$fd;
		$new = self::$instance->online_dir.$fd;
		$return = copy($old,$new); //拷贝到新目录
		unlink($old); //删除旧目录下的文件
		return $return;
	}

	//修改用户信息
	public static function changeUsers($user_id,$info){
		$file_path = self::$instance->online_dir.$user_id;
		
		$content = file_get_contents($file_path);
		//按换行符把全部内容分隔成数组
		$con_array = explode("\n", $content);

		//替换掉指定行
		$con_array[0] = serialize($info);
		//组合回字符串
		$con = implode("\n", $con_array);
			
		//写回文档
		$flag = @file_put_contents($file_path, $con);

		return $flag;
	}
	
	/**
	* 检查文件目录是否存在，
	* $dir:文件目录名，
	* $clear_file:如果目录存在，是否清除，
	*/
	public static function checkDir($dir, $clear_file = false) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                rw_deny:
                trigger_error("can not read/write dir[".$dir."]", E_ERROR);
                return;
            }else{
				return true;
			}
        }  else if ($clear_file) {
           	self::clearDir($dir);
        }
    }
	//写入文件
	public static function login($file_name, $info){
		$flag = @file_put_contents($file_name, @serialize($info));
		return $flag;
    }
	/**
	 * 获取所有房间的在线用户
	 */
	public static function getOnlineUsers(){
		global $rooms;
		$online_users = array();
		foreach($rooms as $_k => $_v){
			$online_users[$_k] = array_slice(scandir(self::$instance->online_dir.$_k.'/'), 2);
		}
        return $online_users;
    }

	/**
	 * 获取指定群组下的的在线用户
	 */
	public static function getGroupOnlineUsers($group_id = null){
		global $rooms;
		$online_users = array();

		$online_users = array_slice(scandir(self::$instance->online_dir.'group/'.$group_id.'/'), 2);

        return $online_users;
    }
	
	public static function getUsersAll(){
		$users = array_slice(scandir(self::$instance->online_dir), 2);
		return $users;
	}
	
	public static function getUsers($users) {
        $ret = array();
        foreach($users as $v){
            $ret[] = self::getUser($v);
        }
        return $ret;
    }
	
	public static function getUser($userid) {
        if (!is_file(self::$instance->online_dir.$userid)) {
            return false;
        }
        $ret = @file_get_contents(self::$instance->online_dir.$userid);
        $info = @unserialize($ret);
		//$info['roomid'] = $roomid;//赋予用户所在的房间
        return $info;
    }

	
	public static function logout($userid,$user_group = null) {
		if(self::exists($userid)){
			unlink(self::$instance->online_dir.$userid);

			if(is_array($user_group) && $user_group){
				foreach($user_group as $_k => $_v){
					unlink(self::$instance->online_dir."group/". $_v['group_id'] . '/' .$userid);
				}
				//unlink(self::$instance->online_dir."group/".$userid);
			}
			//break;
		}
	}
	
	public static function exists($userid){
		if(file_exists(self::$instance->online_dir.$userid)){
			return is_file(self::$instance->online_dir.$userid);
		}
		return false;
    }
}