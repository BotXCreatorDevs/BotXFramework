<?php

# Example
if ($v->command == 'test') {
	# You can get translation from your file plugin.json
	$t = '✅ ' . $tr->getTranslation('textTestSuccess');
	# You can also use classes on with your plugin.class.php
	$t = (new Example)->up($t);
	# Obviusly you can use all the NeleBot X fratures
	$t = $bot->bold($t);
	# And finally send the message
	$bot->sendMessage($v->user_id, $t);
	die;
}