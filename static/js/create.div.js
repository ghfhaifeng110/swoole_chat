/*创建div dom*/
var cdiv = {
	userlists : function(roomid,is_none){
		var arr = [];
		arr = [ '<div class="conv-lists" id="conv-lists-',roomid,'" style="display:',is_none,'"></div>'];
		return arr.join('');
	},
	chatlists : function(roomid,is_none){
		var arr = [];
		arr = [ '<div class="msg-items" id="chatLineHolder-'+roomid+'" style="display:',is_none,'"></div>'];
		return arr.join('');
	},
	render : function(template,params){
		var arr = [];
		switch(template){
			case 'mymessage':
				arr = [
					'<div style="display: block;" class="msg-box"><div class="chat-item me"><div class="clearfix"><div class="avatar"><div class="normal user-avatar" style="background-image: url(',params.avatar,');"></div></div><div class="msg-bubble-box"><div class="msg-bubble-area"><div><div class="msg-bubble"><pre class="text">', params.newmessage,'</pre></div></div></div></div></div></div></div>'
				];
			break;
			
			case 'chatLine':
				arr = [
					'<div style="display: block;" class="msg-box"><div class="chat-item not-me"><div class="chat-profile-info clearfix"><span class="profile-wrp"><span class="name clearfix"><span class="name-text">',params.mobile,'</span></span></span><span class="chat-time">',params.time,'</span></div><div class="clearfix"><div class="avatar"><div class="normal user-avatar" onclick="chat.remind(this)" fd="',params.fd,'" uname="',params.mobile,'" style="background-image: url(\'',params.avatar,'\');"></div></div><div class="msg-bubble-box"><div class="msg-bubble-area"><div class="msg-bubble"><pre class="text">',params.newmessage,'</pre></div></div></div></div></div></div>'
				];
			break;
			case 'friend':
				arr = [
					'<div id=\'user-',params.fd,'\' onclick="chat.list(this)" fd="',params.fd,'" uname="',params.mobile,'" class="list-item conv-item context-menu conv-item-company"><i class="iconfont icon-delete-conv tipper-attached"></i><div class="avatar-wrap"><div class="group-avatar"><div class="normal group-logo-avatar" style="background-image: url(',params.avatar,');"></div></div></div><div class="conv-item-content"><div class="title-wrap info"><div class="name-wrap"><p class="name">',params.mobile,'</p></div><span class="time">',params.time,'</span></div></div></div>'
				];
			break;
			case 'group':
				arr = [
					'<div id=\'user-',params.fd,'\' onclick="chat.group(this)" fd="',params.fd,'" group_id="',params.id,'" uname="',params.name,'" class="list-item conv-item context-menu conv-item-company"><i class="iconfont icon-delete-conv tipper-attached"></i><div class="avatar-wrap"><div class="group-avatar"><div class="normal group-logo-avatar" style="background-image: url(',params.avatar,');"></div></div></div><div class="conv-item-content"><div class="title-wrap info"><div class="name-wrap"><p class="name">',params.name,'</p></div><span class="time">',params.time,'</span></div></div></div>'
				];
			break;
			case 'user':
				arr = [
					'<div id=\'user-',params.fd,'\' onclick="chat.remind(this)" fd="',params.fd,'" uname="',params.mobile,'" class="list-item conv-item context-menu conv-item-company"><i class="iconfont icon-delete-conv tipper-attached"></i><div class="avatar-wrap"><div class="group-avatar"><div class="normal group-logo-avatar" style="background-image: url(',params.avatar,');"></div></div></div><div class="conv-item-content"><div class="title-wrap info"><div class="name-wrap"><p class="name">',params.mobile,'</p></div><span class="time">',params.time,'</span></div></div></div>'
				];
			break;
			case 'newlogin':
				arr = [
					'<div class="chat-status chat-system-notice">系统消息：欢迎&nbsp;',params.mobile,'&nbsp;加入群聊</div>'
				];
				break;
			case 'logout':
				arr = [
					'<div class="chat-status chat-system-notice">系统消息：&nbsp;',params.mobile,'&nbsp;退出了群聊</div>'
				];
				break;
			case 'my':
				arr = [
					'<div class="big-52 with-border user-avatar" uid="',params.fd,'" title="',params.mobile,'" style="background-image: url(',params.avatar,');"></div>'
				];
				break;
		}
		return arr.join('');
	}	
}