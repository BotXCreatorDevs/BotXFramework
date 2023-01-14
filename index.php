<?php

$start_time = microtime(true);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_log', __DIR__ . '/logs.txt');
ini_set('ignore_repeated_errors', 1);

require_once('configs.php');
require_once('errorHandler.php');
require_once('TelegramBot.php');
require_once('Variables.php');
require_once('Database.php');
require_once('AntiFlood.php');
require_once('NeleBotX.php');
require_once('Languages.php');
require_once('BotX.php');

# Initialize framework
try {
	if (!$configs['token'] && $_GET['token']) { // Webhook multi-bot
		$configs['token'] = $_GET['token'];
	} elseif ($configs['token'] && $_GET['key']) { // Webhook per-bot
		if (crypt($_GET['key'], 'NeleBotX') != $configs['token']) {
			http_response_code(403);
			die;
		}
		$configs['token'] = $_GET['key'];
	} else {
		http_response_code(401);
		die;
	}
	$NeleBotX = new NeleBotX($configs);
	$bot = $NeleBotX->api;
	$v = $NeleBotX->v;
	$db = $NeleBotX->db;
	$user = $NeleBotX->user;
	$group = $NeleBotX->group;
	$channel = $NeleBotX->channel;
	
	# Global Translations for Bot X Framework
	if (!$user['lang']) $user['lang'] = 'en';
	$tr = new Languages($user['lang'], $db);
	
	# Start Bot X class
	$botx = new BotX($NeleBotX, $botxconf, $botinfo);
} catch (Exception $e) {
	# Bot Exceptions
	$NeleBotX->response = ['ok' => false, 'error_code' => 500, 'description' => 'Class Error: ' . $e->getMessage()];
}
if (!$NeleBotX->response['ok']) {
	if ($NeleBotX->response['error_code'] != 429) $bot->sendLog($bot->bold('The Bot was stopped!') . PHP_EOL . $bot->code($NeleBotX->response['description'], 1));
	die($NeleBotX->response['error_code']);
} else {
	http_response_code(200);
	fastcgi_finish_request(); // to close fastcgi request (Required)
	
	# Bot X Verify command
	if ($v->chat_type == 'private' && $v->command == 'verifybotxcreator') {
		$bot->sendMessage($v->chat_id, $bot->bold('✅ Bot X Creator Framework', 1));
		die;
	}

	# Cancel a user action
	if ($v->command == 'cancel' || strpos($v->query_data, 'cancel') === 0 || $v->query_data == 'ccb:cancel') {
		if ($db->rget('BotXCC-action-' . $botx->id . '-' . $v->user_id)) {
			$db->rdel('BotXCC-action-' . $botx->id . '-' . $v->user_id);
			if ($t = $botx->getProperty('CommandCanceled', 2)) {
			} else {
				$t = '😕 ' . $tr->getTranslation('messageCommandCanceled');
			}
		} else {
			if ($t = $botx->getProperty('noCommandRunning', 2)) {
			} else {
				$t = '😴 ' . $tr->getTranslation('messageNoCommandRunning');
			}
		}
		$db->query('UPDATE utenti SET settings = settings::jsonb #- ?::text[] WHERE user_id = ?', ['{temp}', $v->user_id]);
		if ($v->command) {
			$bot->sendMessage($v->chat_id, $t, ['remove'], 'def', 'def', false, 'remove');
			die;
		} elseif ($v->query_data == 'cancel') {
			$bot->editText($v->chat_id, $v->message_id, $t);
			$bot->answerCBQ($v->query_id);
			die;
		} else {
			$v->query_data = str_replace('cancel-', '', $v->query_data);
		}
	}

	# User action variable
	if (isset($v->user_id)) $action = $botx->getAction($v->user_id);
	
	# isOwner
	$isOwner = ($v->user_id == $botinfo['owner'] ? true : false);

	# Load plugins
	if (is_array($configs['plugins']) && !empty($configs['plugins'])) {
		foreach ($configs['plugins'] as $plugin => $status) {
			if ($status && file_exists('plugins/' . $plugin)) {
				$tr_file = 'plugins/' . str_replace('.php', '.json', $plugin);
				if (file_exists($tr_file)) $tr->translations = array_merge_recursive(json_decode(file_get_contents($tr_file), true), $tr->translations);
				$class_file = 'plugins/' . str_replace('.php', '.class.php', $plugin);
				if (file_exists($class_file)) require_once($class_file);
				require('plugins/' . $plugin);
			}
		}
	}
	
	if ($v->command == 'start' || $v->query_data == 'ccb:start') {
		$t = '';
		if ($isOwner && $v->chat_type == 'private') $t .= '🧸 ' . $tr->getTranslation('messageSetupHelp');
		$t .= $bot->bold($tr->getTranslation('textCredits'));
		if ($v->query_data) {
			$bot->editText($v->chat_id, $v->message_id, $t);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t);
		}
		die;
	} elseif ($v->query_id) {
		$bot->answerCBQ($v->query_id, '🧐 ' . $tr->getTranslation('callbackNotFound'), 1);
	} else {
		if ($v->chat_type == 'private') {
			if ($v->command) {
				$t = '🙃 ' . $tr->getTranslation('messageUnknownCommand');
			} else {
				$t = '😴 ' . $tr->getTranslation('messageNoCommandRunning');
			}
			$bot->sendMessage($v->chat_id, $t);
		}
	}

}

?>
