<?php

class ChatUser extends ChatBase {
	
	//protected $fd=0,$mobile = '', $avatar = '',$password='',$user_id;
	
	public function save($data, $file_name=''){
		//if(File::exists($data['user_id'])){
			//$return = File::changeUsers($file_name.$data['user_id'],array('fd'=>$data['fd'],'mobile'=>$data['mobile'],'user_id'=>$data['user_id'],'time'=>date("H:i",time())));
		//}else{
			$return = File::login($file_name.$data['user_id'],array('fd'=>$data['fd'],'mobile'=>$data['mobile'],'user_id'=>$data['user_id'],'time'=>date("H:i",time())));
		//}
		return $return;
	}

	/**
	* 保存群组文件，判断是否已经存在，存在不操作，不存在，添加
	*/
	public function saveGroup($user_group, $fd, $mobile){
		echo "save_group:".json_encode($user_group)."\n";
		foreach($user_group as $_k => $_v){
			File::checkDir($this->online_dir.'group/'. $_v['group_id'] .'/', false);
				
			//File::login('group/'. $_v['group_id'] . '/' .$_v['user_id'],array('group_id'=>$_v['group_id'],'name'=>$_v['name'],'time'=>date("H:i",time())));

			$tt = $this->save(['fd'=>$fd,'mobile'=>htmlspecialchars($mobile),'user_id'=>$_v['user_id']],$this->online_dir.'group/'. $_v['group_id']. '/');
			//echo "\n savegroup_file_path:".$this->online_dir.'group/'. $_v['group_id']. '/';
			//echo "\n saveGroup_result:".$tt;
		}
	}
	
	public function getOnlineUsers(){
		$users = File::getOnlineUsers();
		return $users;
	}

	//获取指定群组下的在线用户
	public function getGroupOnlineUsers($group_id = null){
		$users = File::getGroupOnlineUsers($group_id);
		return $users;
	}
	
	public function getUsers($lists){
		$users = File::getUsers($lists);
		return $users;
	}
	public function getUsersAll(){
		$lists = File::getUsersAll();
		$info = $this->getUsers(array_slice($lists, 0, 100));
		return $info;
	}
	
	public function getUser( $fd ){
		$user = File::getUser($fd );
		return $user;
	}
	
	public function changeUser($fd){
		$return = File::changeUser($fd);
		return $return;
	}
	
}