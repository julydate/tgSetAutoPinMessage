<?php 
/*Telegram Bot Token*/
$token = "123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11";
$bot_id = 123456;
/*
setAutoPinMessage - 自动恢复置顶消息 on 为开启 off 为关闭
setAutoDelPinMessage - 自动删除频道置顶消息 on 为开启 off 为关闭
设置 Telegram Bot WebHook:
https://api.telegram.org/bot$token/setWebhook?url=https://example.com/channelBot.php?token=$token
另外需要在本程序同目录下创建 channelbotconfig 文件夹，并在该文件夹里创建一个 config.json 文件
 */
if(@$_GET['token'] === $token) {
	$msg = @file_get_contents('php://input');
	$msg_json = json_decode($msg, true);
	$chat_id = $msg_json['message']['chat']['id'];
	$chat_from = $msg_json['message']['from']['id'];
	$chat_type = $msg_json['message']['chat']['type'];
	$chat_text = $msg_json['message']['text'];
	$message_id = $msg_json['message']['message_id'];
	$entities_type_command = $msg_json['message']['entities'][0]['type'];//bot_command
	$pinned_message = @$msg_json['message']['pinned_message'];
	$config = file_get_contents('channelbotconfig/config.json');
	$config_json = json_decode($config, true);

	/*校验是否群消息*/
	if($chat_type !== "group" && $chat_type !== "supergroup") {
		$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=本机器人请在群组使用");
		exit(0);
	}

	/*校验是否人工置顶消息*/
	if($pinned_message && $chat_from !== 777000) {
		if($config_json[$chat_id]['is_auto_pin'] === "on") {
			file_put_contents('channelbotconfig/'.md5($chat_id).'.json', $msg);
		} else {
			exit(0);
		}
	}

	/*校验是否频道置顶消息*/
	if($chat_from === 777000) {
		if($config_json[$chat_id]['is_auto_pin'] === "on") {
			if($config_json[$chat_id]['is_auto_del'] === "on") {
				$callback = @file_get_contents("https://api.telegram.org/bot".$token."/deleteMessage?chat_id=".$chat_id."&message_id=".$message_id);
			}
			$pinned_message_file = @file_get_contents('channelbotconfig/'.md5($chat_id).'.json');
			if($pinned_message_file) {
				$pinned_message_json = json_decode($pinned_message_file, true);
				$pinned_message_id = $pinned_message_json['message']['pinned_message']['message_id'];
				$pinned_message_chat_id = $pinned_message_json['message']['pinned_message']['chat']['id'];
				$callback = @file_get_contents("https://api.telegram.org/bot".$token."/pinChatMessage?chat_id=".$pinned_message_chat_id."&message_id=".$pinned_message_id."&disable_notification=true");
				/*自动删除提示消息，message_id判断有问题，待解决*/
				if($config_json[$chat_id]['is_auto_del'] === "on") {
					$callback = @file_get_contents("https://api.telegram.org/bot".$token."/deleteMessage?chat_id=".$chat_id."&message_id=".++$message_id);
				}
			} else {
				$callback = @file_get_contents("https://api.telegram.org/bot".$token."/unpinChatMessage?chat_id=".$chat_id);
			}
		} else {
			if($config_json[$chat_id]['is_auto_del'] === "on") {
				$callback = @file_get_contents("https://api.telegram.org/bot".$token."/deleteMessage?chat_id=".$chat_id."&message_id=".$message_id);
			}
		}
	}

	/*判断群组管理员*/
	function check_chat_admin($token, $bot_id, $chat_id, $chat_from) {
		$check_admin = @file_get_contents("https://api.telegram.org/bot".$token."/getChatAdministrators?chat_id=".$chat_id);
		if($check_admin) {
			$is_admin = false;
			$is_bot_admin = false;
			$check_admin_json = json_decode($check_admin, true);
			$num = count($check_admin_json['result']); 
			for($i=0; $i<$num; ++$i){ 
				if($check_admin_json['result'][$i]['user']['id'] === $bot_id) {
					$is_bot_admin = true;
					$bot_can_pin_messages = $check_admin_json['result'][$i]['can_pin_messages'];
            		$bot_can_delete_messages = $check_admin_json['result'][$i]['can_delete_messages'];
				}
				if($check_admin_json['result'][$i]['user']['id'] === $chat_from) {
					$is_admin = true;
				}
			}
			if(!$is_admin) {
				$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=你不是本群组管理员");
				exit(0);
			} else if(!$is_bot_admin) {
				$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=请先添加bot为本群组管理员");
				exit(0);
			} else if(!$bot_can_delete_messages || !$bot_can_pin_messages) {
				$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=bot权限不足");
				exit(0);
			}
		} else {
			$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=无法获取到权限信息");
			exit(0);
		}
	}

  	/*校验是否配置文件修改请求*/
	if(preg_match('/\/setAutoPinMessage/i', $chat_text) && $entities_type_command === "bot_command") {
		check_chat_admin($token, $bot_id, $chat_id, $chat_from);
		if(preg_match('/on/i', $chat_text)) {
			$config_json[$chat_id]['is_auto_pin'] = "on";
			$config = json_encode($config_json);
			file_put_contents('channelbotconfig/config.json', $config);
			$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=自动恢复置顶消息功能已开启");
		} else if(preg_match('/off/i', $chat_text)) {
			$config_json[$chat_id]['is_auto_pin'] = "off";
			$config = json_encode($config_json);
			file_put_contents('channelbotconfig/config.json', $config);
			$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=自动恢复置顶消息功能已关闭");
		}
	} else if(preg_match('/\/setAutoDelPinMessage/i', $chat_text) && $entities_type_command === "bot_command") {
		check_chat_admin($token, $bot_id, $chat_id, $chat_from);
		if(preg_match('/on/i', $chat_text)) {
			$config_json[$chat_id]['is_auto_del'] = "on";
			$config = json_encode($config_json);
			file_put_contents('channelbotconfig/config.json', $config);
			$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=自动删除频道置顶消息功能已开启");
		} else if(preg_match('/off/i', $chat_text)) {
			$config_json[$chat_id]['is_auto_del'] = "off";
			$config = json_encode($config_json);
			file_put_contents('channelbotconfig/config.json', $config);
			$callback = @file_get_contents("https://api.telegram.org/bot".$token."/sendMessage?chat_id=".$chat_id."&text=自动删除频道置顶消息功能已关闭");
		}
	}
}
?>