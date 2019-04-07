<?php
// no direct access
defined('_JEXEC') or die;

class modJCommentsLatestHelper
{
	public static function getList( &$params )
	{
		$db = JFactory::getDBO();
		$user = JFactory::getUser();

		$source = $params->get('source', 'com_content');
		if (!is_array($source)) {
			$source = explode(',', $source);
		}
		
		$date = JFactory::getDate();

		if (version_compare(JVERSION,'1.6.0','ge')) {
			$now = $date->toSql();
			$access = array_unique(JAccess::getAuthorisedViewLevels($user->get('id')));
			$access[] = 0; // for backward compability
		} else {
			$now = $date->toMySQL();
			$access = $user->get('aid', 0);
		}

		switch($params->get('ordering', ''))
		{
		        case 'vote':
		        	$orderBy = '(c.isgood-c.ispoor) DESC';
		        	break;

			case 'date':
			default:
		        	$orderBy = 'c.date DESC';
				break;
		}


		$where = array();

		$interval = $params->get('interval', '');
		if (!empty($interval)) {

			$timestamp = $date->toUnix();

			switch($interval) {
				case '1-day':
				 	$timestamp = strtotime('-1 day', $timestamp);
					break;

				case '1-week':
				 	$timestamp = strtotime('-1 week', $timestamp);
					break;

				case '2-week':
				 	$timestamp = strtotime('-2 week', $timestamp);
					break;

				case '1-month':
				 	$timestamp = strtotime('-1 month', $timestamp);
					break;

				case '3-month':
				 	$timestamp = strtotime('-3 month', $timestamp);
					break;

				case '6-month':
				 	$timestamp = strtotime('-6 month', $timestamp);
					break;

				case '1-year':
				 	$timestamp = strtotime('-1 year', $timestamp);
					break;
				default:
				 	$timestamp = NULL;
					break;
			}

			if ($timestamp !== NULL) {
				$dateFrom = JFactory::getDate($timestamp);
				$dateTo = $date;

				if (version_compare(JVERSION,'1.6.0','ge')) {
					$where[] = 'c.date BETWEEN ' . $db->Quote($dateFrom->toSQL()) . ' AND ' . $db->Quote($dateTo->toSQL());
				} else {
					$where[] = 'c.date BETWEEN ' . $db->Quote($dateFrom->toMySQL()) . ' AND ' . $db->Quote($dateTo->toMySQL());
				}
			}
		}

		$where[] = 'c.published = 1';
		$where[] = 'c.deleted = 0';
		$where[] = "o.link <> ''";
		$where[] = (is_array($access) ? "o.access IN (" . implode(',', $access) . ")" : " o.access <= " . (int) $access);

		if (JCommentsMultilingual::isEnabled()) {
			$where[] = 'c.lang = ' . $db->Quote(JCommentsMultilingual::getLanguage());
		}

		$joins = array();

		if (count($source) == 1 && $source[0] == 'com_content') {
			$joins[] = 'JOIN #__content AS cc ON cc.id = o.object_id';
			$joins[] = 'LEFT JOIN #__categories AS ct ON ct.id = cc.catid';

			$where[] = "c.object_group = " . $db->Quote($source[0]);
			$where[] = "(cc.publish_down = '" . $db->getNullDate() . "' OR cc.publish_down >= '$now')";
			$where[] = "(cc.publish_down = '" . $db->getNullDate() . "' OR cc.publish_down >= '$now')";

			$categories = $params->get('catid', array());
			if (!is_array($categories)) {
				$categories = explode(',', $categories);
			}

			JArrayHelper::toInteger($categories);

			$categories = implode(',', $categories);
			if (!empty($categories)) {
				$where[] = "cc.catid IN (" . $categories . ")";
			}
		} else if (count($source)) {
			$where[] = "c.object_group in ('" . implode("','", $source) . "')";
		}

		$query = "SELECT c.id, c.userid, c.comment, c.title, c.name, c.username, c.email, c.date, c.object_id, c.object_group, '' as avatar"
			. ", o.title AS object_title, o.link AS object_link, o.access AS object_access, o.userid AS object_owner"
			. " FROM #__jcomments AS c"
			. " JOIN #__jcomments_objects AS o ON c.object_id = o.object_id AND c.object_group = o.object_group AND c.lang = o.lang"
			. (count($joins) ? ' ' . implode(' ', $joins) : '')
			. (count($where) ? ' WHERE  ' . implode(' AND ', $where) : '')
			. " ORDER BY " . $orderBy
			;

		$db->setQuery($query, 0, $params->get('count'));
		$list = $db->loadObjectList();

		if (!is_array($list)) {
			$list = array();
		}

		if (count($list)) {
			$show_date = $params->get('show_comment_date', 0);
			$date_type = $params->get('date_type', '');

			$date_format = $params->get('date_format', 'd.m.Y H:i');
			$show_author = $params->get('show_comment_author', 0);
			$show_object_title = $params->get('show_object_title', 0);
			$show_comment_title = $params->get('show_comment_title', 0);
			$show_smiles = $params->get('show_smiles', 0);
			$show_avatar = $params->get('show_avatar', 0);

			$limit_comment_text = (int) $params->get('limit_comment_text', 0);

			$config = JCommentsFactory::getConfig();
			$bbcode = JCommentsFactory::getBBCode();
			$smiles = JCommentsFactory::getSmiles();
			$acl = JCommentsFactory::getACL();

			if ($show_avatar) {
				JPluginHelper::importPlugin('jcomments');
			
				if (version_compare(JVERSION, '3.0', 'ge')) {
					$dispatcher = JEventDispatcher::getInstance();
				} else {
					$dispatcher = JDispatcher::getInstance();
				}

				$dispatcher->trigger('onPrepareAvatars', array(&$list));
			}

			foreach($list as &$item) {

				$item->displayDate = '';
				if ($show_date) {
					if ($date_type == 'relative') {
						$item->displayDate = modJCommentsLatestHelper::getRelativeDate($item->date);
					} else {
						$item->displayDate = JHTML::_('date', $item->date, $date_format);
					}
				}

				$item->displayAuthorName = '';
				if ($show_author) {
					$item->displayAuthorName = JComments::getCommentAuthorName($item);
				}

				$item->displayObjectTitle = '';
				if ($show_object_title) {
					$item->displayObjectTitle = $item->object_title;
				}

				$item->displayCommentTitle = '';
				if ($show_comment_title) {
					$item->displayCommentTitle = $item->title;
				}

				$item->displayCommentLink = $item->object_link . '#comment-' . $item->id;

				$text = JCommentsText::censor($item->comment);
				$text = preg_replace('#\[quote[^\]]*?\](((?R)|.)*?)\[\/quote\]#ismu', '', $text);
				$text = $bbcode->filter($text, true);
				$text = JCommentsText::fixLongWords($text, $config->getInt('word_maxlength'), ' ');

				if ($acl->check('autolinkurls')) {
					$text = preg_replace_callback( _JC_REGEXP_LINK, array('JComments', 'urlProcessor'), $text);
				}

				$text = JCommentsText::cleanText($text);

				if ($limit_comment_text && JString::strlen($text) > $limit_comment_text) {
					$text = self::truncateText($text, $limit_comment_text - 1);
				}

				switch($show_smiles) {
					case 1:
						$text = $smiles->replace($text);
						break;
					case 2:
						$text = $smiles->strip($text);
						break;
				}

				$item->displayCommentText = $text;

				if ($show_avatar && empty($item->avatar)) {
					$gravatar = md5(strtolower($item->email));
					$item->avatar = '<img src="http://www.gravatar.com/avatar.php?gravatar_id='. $gravatar .'&amp;default=' . urlencode(JCommentsFactory::getLink('noavatar')) . '" alt="'.htmlspecialchars(JComments::getCommentAuthorName($item)).'" />';
				}

				$item->readmoreText = JText::_('MOD_JCOMMENTS_LATEST_READMORE');
			}
		}

		return $list;
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

	public static function groupBy($list, $fieldName, $grouping_direction = 'ksort')
	{
		$grouped = array();

		if (!is_array($list)) {
			if ($list == '') {
				return $grouped;
			}

			$list = array($list);
		}

		foreach($list as $key => $item) {
			if (!isset($grouped[$item->$fieldName])) {
				$grouped[$item->$fieldName] = array();
			}
			$grouped[$item->$fieldName][] = $item;
			unset($list[$key]);
		}

		$grouping_direction($grouped);

		return $grouped;
	}

	public static function getPluralText($text, $value)
	{
	        if (version_compare(JVERSION,'1.6.0','ge')) {
			$result = JText::plural($text, $value);
	        } else {
	        	$toolsPath = JPATH_SITE.'/components/com_jcomments/libraries/joomlatune/language.tools.php';
	        	if (is_file($toolsPath)) {
				require_once ($toolsPath);
				$language = JFactory::getLanguage();
				$suffix = JoomlaTuneLanguageTools::getPluralSuffix($language->getTag(), $value);
				$key = $text . '_' . $suffix;
				if (!$language->hasKey($key)) {
					$key = $text;
				}
			} else {
				$key = $text;
			}

			$result = JText::sprintf($key, $value);
	        }

		return $result;
	}

	public static function getRelativeDate($value, $countParts = 1)
	{
	        if (version_compare(JVERSION,'1.6.0','ge')) {
			$tz = new DateTimeZone(JFactory::getApplication()->getCfg('offset'));
			$now = JFactory::getDate();
			$now->setTimeZone($tz);
		} else {
			$offset = JFactory::getConfig()->getValue('config.offset');
			$now = new JDate();
			$now->setOffset($offset);
		}
		
		$date = new JDate($value);

		$diff = $now->toUnix() - $date->toUnix();
		$result = $value;

		$timeParts = array (
    			  31536000 => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_YEARS'
    			, 2592000 => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_MONTHS'
    			, 604800 => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_WEEKS'
    			, 86400 => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_DAYS'
    			, 3600 => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_HOURS'
    			, 60 => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_MINUTES'
    			, 1 => 'MOD_JCOMMENTS_LATEST_RELATIVE_DATE_SECONDS'
		);

		if ($diff < 5) {
			$result = JText::_('MOD_JCOMMENTS_LATEST_RELATIVE_DATE_NOW');
		} else {
			$dayDiff = floor($diff / 86400);
		        $nowDay = date('d', $now->toUnix());
		        $dateDay = date('d', $date->toUnix());
		        
		        if ($dayDiff == 1 || ($dayDiff == 0 && $nowDay != $dateDay)) {
				$result = JText::_('MOD_JCOMMENTS_LATEST_RELATIVE_DATE_YESTERDAY');
			} else {
				$count = 0;
				$resultParts = array();
			
				foreach ($timeParts as $key => $value) {
					if ($diff >= $key) {
						$time = floor($diff / $key);
						$resultParts[] = modJCommentsLatestHelper::getPluralText($value, $time);
						$diff = $diff % $key;
						$count++;
					
						if ($count > $countParts - 1 || $diff == 0) {
							break;
						}
					}
				}

				if (count($resultParts)) {
					$result = JText::sprintf('MOD_JCOMMENTS_LATEST_RELATIVE_DATE_AGO', implode(', ', $resultParts));
				}
			}
		}

		return $result;
	}

	protected static function getCategories($categories)
	{
		$result = array();

		foreach ($categories as $category) {

			$i = strpos($category, ':');
			if ($i !== false) {
				$component = substr($category, 0, $i);
				$category = substr($category, $i + 1, strlen($category));
			} else {
				$component = 'content';
			}

			if (!isset($result[$component])) {
				$result[$component] = array();
			}

			$result[$component][] = $category;
		}

		return $result;
	}
}
