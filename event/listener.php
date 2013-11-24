<?php
/**
*
* @package ABBC3
* @copyright (c) 2013 Matt Friedman
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace vse\abbc3\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'							=> 'load_abbc3_on_setup',
			'core.modify_text_for_display_before'		=> 'parse_abbcodes_before',
			'core.modify_text_for_display_after'		=> 'parse_abbcodes_after',
			'core.display_custom_bbcodes'				=> 'setup_bbcode_icons',
			'core.display_custom_bbcodes_modify_sql'	=> 'custom_bbcode_modify_sql', // needs to be requested
			'core.display_custom_bbcodes_modify_row'	=> 'display_custom_bbcodes',
		);
	}

	/**
	* Load common files during user setup
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function load_abbc3_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'vse/abbc3',
			'lang_set' => 'abbc3',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	* Alter bbcodes before they are processed by phpBB
	*
	* This is used to change old/malformed ABBC3 BBcodes to a newer structure
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function parse_abbcodes_before($event)
	{
		global $phpbb_container;

		$phpbb_container->get('vse.abbc3.parser')->pre_parse_bbcodes($event);
	}

	/**
	* Alter bbcodes after they are processed by phpBB
	*
	* This is used on ABBC3 BBcodes that require additional post-processing
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function parse_abbcodes_after($event)
	{
		global $phpbb_container;

 		$phpbb_container->get('vse.abbc3.manager')->parse_bbcodes($event);
	}

	/**
	* Modify the SQL statement to gather custom bbcode data
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function custom_bbcode_modify_sql($event)
	{
		$sql = 'SELECT bbcode_id, bbcode_tag, bbcode_helpline
			FROM ' . BBCODES_TABLE . '
			WHERE display_on_posting = 1
			ORDER BY bbcode_order';
		$event['sql'] = $sql;
	}

	/**
	* Alter custom bbcodes to display an icon
	*
	* Uses GIF images named exactly the same as the bbcode_tag
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function display_custom_bbcodes($event)
	{
		global $phpbb_root_path;

		$row = $event['row'];
		$custom_tags = $event['custom_tags'];
		
		$bbcode_img = 'abbc3/images/icons/' . strtolower(rtrim($row['bbcode_tag'], '=')) . '.gif';

		static $images = array();
		
		if (empty($images))
		{
			$images = $this->get_images();
		}

		$custom_tags['BBCODE_IMG'] = (isset($images['ext/' . $bbcode_img])) ? $phpbb_root_path . 'ext/vse/' . $bbcode_img : '';

		$event['custom_tags'] = $custom_tags;
	}

	/**
	* Setup BBcode icon parameters
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function setup_bbcode_icons($event)
	{
		global $template, $phpbb_root_path;

		$template->assign_vars(array(
			'ABBC3_BBCODE_ICONS' => $phpbb_root_path . 'ext/vse/abbc3/images/icons',
		));
	}

	/*
	* Get image paths/names from ABBC3's icons folder
	*
	* @return	Array of file data from ext/vse/abbc3/styles/all/theme/images/icons
	* @access	private
	*/
	private function get_images()
	{
		global $phpbb_root_path, $phpbb_extension_manager;

		$finder = $phpbb_extension_manager->get_finder();

		return $finder
			->extension_suffix('.gif')
			->extension_directory('/images/icons')
			->find_from_extension('abbc3', $phpbb_root_path . 'ext/vse/abbc3');
	}
}