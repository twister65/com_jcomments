<?php
/**
 * JComments Latest - Shows latest comments in Joomla's backend
 *
 * @version 2.2
 * @package JComments.Modules
 * @author smart (smart@joomlatune.ru)
 * @copyright (C) 2006-2014 by smart (http://www.joomlatune.ru)
 * @license GNU General Public License version 2 or later; see license.txt
 *
 **/
 
// no direct access
defined('_JEXEC') or die;

class modJCommentsLatestBackendHelper
{
	public static function getList($params)
	{
		$db = JFactory::getDBO();
		$db->setQuery("SELECT * FROM #__jcomments ORDER BY date DESC", 0, $params->get('count'));
		$items = $db->loadObjectList();

		if (!is_array($items)) {
			$items = array();
		}

		if (count($items)) {

			$config = JCommentsFactory::getConfig();
			$bbcode = JCommentsFactory::getBBCode();

			$limit_comment_text = (int) $params->get('limit_comment_text', 0);

			foreach ($items as &$item) {
				$item->link = 'index.php?option=com_jcomments&&view=comment&layout=edit&id=' . $item->id;
				$item->author = JComments::getCommentAuthorName($item);
		
				$text = JCommentsText::censor($item->comment);
				$text = $bbcode->filter($text, true);
				$text = JCommentsText::cleanText($text);

				if ($limit_comment_text && JString::strlen($text) > $limit_comment_text) {
					$text = self::truncateText($text, $limit_comment_text - 1);
				}

				$item->comment = $text;
			}

		}

		return $items;
	}

	protected static function truncateText($string, $limit)
	{
		$prevSpace = JString::strrpos(JString::substr($string, 0, $limit), ' ');
		$prevLength = $prevSpace !== false ? $limit - max(0, $prevSpace) : $limit;

		$nextSpace = JString::strpos($string, ' ', $limit + 1);
		$nextLength = $nextSpace !== false ? max($nextSpace, $limit) - $limit : $limit;

		$length = 0;

		if ($prevSpace !== false && $nextSpace !== false) {
			$length = $prevLength < $nextLength ? $prevSpace : $nextSpace;
		} elseif ($prevSpace !== false && $nextSpace === false) {
			$length = $length - $prevLength < $length*0.1 ? $prevSpace : $length;
		} elseif ($prevSpace === false && $nextSpace !== false) {
			$length = $nextLength - $length < $length*0.1 ? $nextSpace : $length;
		}

		if ($length > 0) {
			$limit = $length;
		}

		$text = JString::substr($string, 0, $limit);
		if (!preg_match('#(\.|\?|\!)$#ismu', $text)) {
			$text = preg_replace('#\s?(\,|\;|\:|\-)$#ismu', '', $text) . ' ...';
		}

		return $text;
	}
}