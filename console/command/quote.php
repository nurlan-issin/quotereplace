<?php
/**
 *
 * Replace quotes from old mod. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, Nurlan Issin, http://nurlan.info
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nissin\quotereplace\console\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Replace quotes from old mod console command.
 */
class quote extends \phpbb\console\command\command
{
	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param \phpbb\user $user User instance (mostly for translation)
	 */
	public function __construct(\phpbb\user $user, \phpbb\db\driver\driver_interface $db)
	{
		parent::__construct($user);

		// Set up additional properties here
		$this->db = $db;
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->user->add_lang_ext('nissin/quotereplace', 'cli');
		$this
			->setName('replace:quote')
			->setDescription($this->user->lang('CLI_QUOTE'))
		;
	}

	/**
	 * Executes the command replace:quote.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);

		$sql = 'SELECT post_id, post_text, bbcode_bitfield, bbcode_uid, enable_bbcode, enable_smilies, enable_magic_url
			FROM ' . POSTS_TABLE;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$new_post_text = preg_replace_callback('/\[quote=(?:"|&quot;|&amp;quot;)([^"]*?)(?:"|&quot;|&amp;quot;);p=(?:"|&quot;|&amp;quot;)(\d+)(?:"|&quot;|&amp;quot;)(:[0-9a-z]{8})?\]/iu', array($this, 'new_bbcode'), $row['post_text']);
			$new_post_text = preg_replace('/\[\/quote:[0-9a-z]{8}\]/iu', '[/quote]', $new_post_text);
			if ($new_post_text !== $row['post_text'])
			{
				$flags = ($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0;
				$flags |= ($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0;
				$flags |= ($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0;
				$record = generate_text_for_edit($new_post_text, $row['bbcode_uid'], $flags);

				$text = html_entity_decode($record['text'], ENT_QUOTES, 'UTF-8');
				$bitfield = $flags = null;
				generate_text_for_storage(
					$text,
					$row['bbcode_uid'],
					$bitfield,
					$flags,
					$row['enable_bbcode'],
					$row['enable_magic_url'],
					$row['enable_smilies'],
					true,
					true,
					true,
					true,
					'post'
				);
				
				$sql_ary = array(
					'post_text'			=> $text,
					'bbcode_bitfield'	=> $bitfield,
					'post_checksum'		=> '',
				);

				$sql = 'UPDATE ' . POSTS_TABLE . '
					SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE post_id = ' . (int) $row['post_id'];
				$this->db->sql_query($sql);
			}
		}
		$this->db->sql_freeresult($result);

		$io->success($this->user->lang('CLI_QUOTE_FINISH'));
	}
	
	function new_bbcode(array $matches)
	{
		$username = $matches[1];
		$post_id = (int) $matches[2];

		if (preg_match('/^(\d+)$/', $username))
		{
			$user_id = (int) $username;
			$sql = 'SELECT user_id, username
				FROM ' . USERS_TABLE . '
				WHERE user_id = ' . $user_id;
			$result = $this->db->sql_query($sql);
			if ($row = $this->db->sql_fetchrow($result))
			{
				$username = get_username_string('username', $user_id, $row['username']);
			}
			$this->db->sql_freeresult($result);
		}

		if ($post_id)
		{
			$sql = 'SELECT p.post_id, p.poster_id, p.post_time, p.post_username, u.username
				FROM ' . POSTS_TABLE . ' p 
				LEFT JOIN ' . USERS_TABLE . ' u ON (p.poster_id = u.user_id)
				WHERE p.post_id = ' . $post_id;
			$result = $this->db->sql_query($sql);
			if ($row = $this->db->sql_fetchrow($result))
			{
				$post_time = (int) $row['post_time'];
				if (empty($user_id) || $user_id == $username)
				{
					$user_id = (int) $row['poster_id'];
					$username = get_username_string('username', $user_id, $row['username'], '', $row['post_username']);
				}
				return '[quote="' . $username . '" post_id=' . $post_id . ' time=' . $post_time . ' user_id=' . $user_id . ']';
			}
			$this->db->sql_freeresult($result);
		}
		
		if (isset($user_id) && $user_id)
		{
			return '[quote="' . $username . '" user_id=' . $user_id . ']';
		}
		return '[quote="' . $username . '"]';
	}
}
