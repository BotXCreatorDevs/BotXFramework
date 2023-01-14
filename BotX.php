<?php

#█████████████████████████████████████████████████████████████████████#
#█████████████████████████████████████████████████████████████████████#
#█▄─▄─▀█─▄▄─█─▄─▄─███▄─▀─▄███─▄▄▄─█▄─▄▄▀█▄─▄▄─██▀▄─██─▄─▄─█─▄▄─█▄─▄▄▀█#
#██─▄─▀█─██─███─██████▀─▀████─███▀██─▄─▄██─▄█▀██─▀─████─███─██─██─▄─▄█#
#▀▄▄▄▄▀▀▄▄▄▄▀▀▄▄▄▀▀▀▀▄▄█▄▄▀▀▀▄▄▄▄▄▀▄▄▀▄▄▀▄▄▄▄▄▀▄▄▀▄▄▀▀▄▄▄▀▀▄▄▄▄▀▄▄▀▄▄▀#
#######################################################################
#  This Bot X Creator extension was created to develop new plugins!   #
#  It's all commented, did you need more? Visit our page on GitHub!   #

class BotX {
	private $version = '1.0.0';
	private $releaseDate = '15 January 2023';
	public $configs = [];
	public $botxconf = [];
	public $db = [];
	public $v = [];
	public $property_types = [
		# Type ID => On/Off
		0 => 0, // Anything
		1 => 1, // Bot settings
		2 => 1, // Bot messages
		3 => 1, // Bot Custom Commands	[Custom Command plugin]
		4 => 1, // Bot Command alias	[Custom Command plugin]
		5 => 1, // Translations			[Translations plugin]
		6 => 1, // Support messages IDs	[Support Chat plugin]
	];
	
	public function __construct (
		$NeleBotX,			// NeleBotX class
		$botxconf	= [],	// Others Bot X configurations
		$botinfo	= []	// Bot informations (from Bot X Database)
	) {
		$this->bot = $botinfo;
		$this->id = $botinfo['botx_id'];
		$this->configs = $NeleBotX->configs;
		$this->botxconf = $botxconf;
		$this->api = $NeleBotX->api;
		$this->db = $NeleBotX->db;
		$this->v = $NeleBotX->v;
		$this->user = $NeleBotX->user;
		$this->group = $NeleBotX->group;
		$this->channel = $NeleBotX->channel;
	}
	
	# Get a Property from Bot X database
	public function getProperty ($id, $type = 0) {
		if (isset($this->property_types[$type]) && $this->property_types[$type]) {
			$q = $this->db->query('SELECT p_id, data FROM properties WHERE id = ? and type = ? ' . $this->db->limit(1), [$id, $type], 1);
			if (isset($q['p_id'])) return $q['data'];
		}
	}
	
	# Get multiple Properties
	public function getProperties ($type = 0, $limit = 5, $offset = 0, $order = 'name') {
		if (isset($this->property_types[$type]) && $this->property_types[$type]) {
			$q = $this->db->query('SELECT p_id, id, creation, last_edit FROM properties WHERE type = ? ORDER BY ' . $order . ' ' . $this->db->limit($limit, $offset), [$type], 2);
			return $q;
		}
		return [];
	}
	
	# Return true if the Property already exists
	public function existsProperty ($id, $type = 0) {
		if (isset($this->property_types[$type]) && $this->property_types[$type]) {
			$q = $this->db->query('SELECT p_id FROM properties WHERE id = ? and type = ? ' . $this->db->limit(1), [$id, $type], 1);
			if (isset($q['p_id'])) return true;
		}
		return false;
	}
	
	# Set Property
	public function setProperty ($id, $type, $data) {
		if (isset($this->property_types[$type]) && $this->property_types[$type]) {
			if ($this->existsProperty($id, $type)) {
				return $this->db->query('UPDATE properties SET data = ?, last_edit = ? WHERE id = ? and type = ?', [$data, time(), $id, $type]);
			} else {
				$this->delProperty($id, $type);
				return $this->db->query('INSERT INTO properties (id, type, data, creation, last_edit) VALUES (?,?,?,?,?)', [$id, $type, $data, time(), time()]);
			}
		} else {
			return ['error' => 'Unknown property type'];
		}
	}
	
	# Delete property
	public function delProperty ($id, $type = 0) {
		if (isset($this->property_types[$type]) && $this->property_types[$type]) {
			return $this->db->query('DELETE FROM properties WHERE id = ? and type = ?', [$id, $type]);
		} else {
			return ['error' => 'Unknown property type'];
		}
	}
	
	# Get the Action by user ID
	public function getAction (int $id) {
		return $this->db->rget('BotXCC-action-' . $this->id . '-' . $id);
	}
	
	# Return if the Action exists on user
	public function existsAction (int $id, string $action = null) {
		if (!is_null($action)) {
			return ($this->getAction($id) == $action) ? true : false;
		} else {
			return $this->db->redis->exists('BotXCC-action-' . $this->id . '-' . $id);
		}
	}
	
	# Set Action for a user
	public function setAction (int $id, string $action, int $timer = null) {
		return $this->db->rset('BotXCC-action-' . $this->id . '-' . $id, $action, $timer);
	}
	
	# Delete an Action for a user
	public function delAction (int $id) {
		return $this->db->rdel('BotXCC-action-' . $this->id . '-' . $id);
	}
	
	# Get a tag from a message
	public function getTag ($arg, $key) {
		switch ($arg) {
			case 'botx':
				switch ($key) {
					case 'version': return $this->version;
					case 'releaseDate': return $this->releaseDate;
				}
			case 'bot':
				switch ($key) {
					case 'id': return $this->bot['id'];
					case 'name': return $this->bot['name'];
					case 'username': return $this->bot['username'];
					case 'registrationDate': return date('j F Y', $this->bot['reg_time']);
				}
			case 'user':
				switch ($key) {
					case 'id': return $this->v->user_id;
					case 'name': return $this->v->user_first_name;
					case 'surname': return $this->v->user_last_name;
					case 'username': return $this->v->user_username;
				}
			case 'group':
				if (in_array($this->v->chat_type, ['group', 'supergroup'])) {
					switch ($key) {
						case 'id': return $this->v->chat_id;
						case 'title': return $this->v->chat_title;
						case 'username': return $this->v->chat_username;
						case 'description': return $this->group['description'];
					}
				}
			case 'channel':
				if ($this->v->chat_type == 'channel') {
					switch ($key) {
						case 'id': return $this->v->chat_id;
						case 'title': return $this->v->chat_title;
						case 'username': return $this->v->chat_username;
						case 'description': return $this->channel['description'];
					}
				}
		}
		return;
	}
	
	# Apply tags to the message
	public function applyTags ($text, $parse_mode = '') {
		if (strpos($text, '[') !== false and strpos($text, ']') !== false) {
			$tags = explode('[', $text);
			foreach ($tags as $cr) {
				$tag = explode(']', $cr, 2)[0];
				if (strpos($tag, '?') !== false) {
					$properties = explode('?', $tag, 2);
					if (strpos($properties[1], '!') !== false) {
						$e = explode('!', $properties[1], 2);
						$result = $this->getTag($properties[0], $e[0]);
						if (!$result) {
							$result = $e[1];
						}
					} else {
						$result = $this->getTag($properties[0], $properties[1]);
					}
					$text = str_replace('[' . $tag . ']', $this->api->specialchars($result, $parse_mode), $text);
				}
			}
		}
		return $text;
	}
}

?>