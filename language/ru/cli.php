<?php
/**
 *
 * Replace quotes from old mod. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, Nurlan Issin, http://nurlan.info
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'CLI_QUOTE'			=> 'Замена старых цитат в текстах сообщений',
	'CLI_QUOTE_FINISH'	=> 'Успешно произведена замена старых цитат в сообщениях.',
));
