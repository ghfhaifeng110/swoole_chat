<?php

class ChatBase{
	protected $online_dir;

	public function __construct(array $options = array()){
		$this->online_dir = ONLINE_DIR;

		if(!empty($options)){
			foreach($options as $k=>$v){
				if(isset($this->$k)){
					$this->$k = $v;
				}
			}
		}
	}
}