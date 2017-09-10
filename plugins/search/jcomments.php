<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 3.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
include_once(JPATH_ROOT . '/components/com_jcomments/jcomments.legacy.php');

/**
 * Search plugin
 */
class plgSearchJComments extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @param object $subject The object to observe
	 * @param array $config  An array that holds the plugin configuration
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('plg_search_jcomments', JPATH_SITE);
	}

	/**
	 * @return array An array of search areas
	 */
	function onContentSearchAreas()
	{
		static $areas = array('comments' => 'PLG_SEARCH_JCOMMENTS_COMMENTS');

		return defined('JCOMMENTS_JVERSION') ? $areas : array();
	}

	/**
	 * Comments Search method
	 *
	 * @param string $text Target search string
	 * @param string $phrase mathcing option, exact|any|all
	 * @param string $ordering ordering option, newest|oldest|popular|alpha|category
	 * @param mixed $areas An array if the search it to be restricted to areas, null if search all
	 * @return array
	 */
	function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		$text = JString::strtolower(trim($text));
		$result = array();

		if ($text == '' || !defined('JCOMMENTS_JVERSION')) {
			return $result;
		}

		if (is_array($areas)) {
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
				return $result;
			}
		}
		if (file_exists(JPATH_ROOT . '/components/com_jcomments/jcomments.php')) {
			require_once(JPATH_ROOT . '/components/com_jcomments/jcomments.php');

			$db = JFactory::getDBO();
			$limit = $this->params->def('search_limit', 50);

			switch ($phrase) {
				case 'exact':
					$text = $db->Quote('%' . $db->escape($text, true) . '%', false);
					$wheres2[] = 'LOWER(' . $db->quoteName('c.name') . ') LIKE ' . $text;
					$wheres2[] = 'LOWER(' . $db->quoteName('c.comment') . ') LIKE ' . $text;
					$wheres2[] = 'LOWER(' . $db->quoteName('c.title') . ') LIKE ' . $text;
					$where = '(' . implode(') OR (', $wheres2) . ')';
					break;
				case 'all':
				case 'any':
				default:
					$words = explode(' ', $text);
					$wheres = array();
					foreach ($words as $word) {
						$word = $db->Quote('%' . $db->escape($word, true) . '%', false);
						$wheres2 = array();
						$wheres2[] = 'LOWER(' . $db->quoteName('c.name') . ') LIKE ' . $word;
						$wheres2[] = 'LOWER(' . $db->quoteName('c.comment') . ') LIKE ' . $word;
						$wheres2[] = 'LOWER(' . $db->quoteName('c.title') . ') LIKE ' . $word;
						$wheres[] = implode(' OR ', $wheres2);
					}
					$where = '(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $wheres) . ')';
					break;
			}

			switch ($ordering) {
				case 'oldest':
					$order = $db->quoteName('c.date') . ' ASC';
					break;
				case 'newest':
				default:
					$order = $db->quoteName('c.date') . ' DESC';
					break;
			}


			$acl = JCommentsFactory::getACL();
			$access = $acl->getUserAccess();

			if (is_array($access)) {
				$accessCondition = "AND " . $db->quoteName('jo.access') . " IN (" . implode(',', $access) . ")";
			} else {
				$accessCondition = "AND " . $db->quoteName('jo.access') . " <= " . (int)$access;
			}

			$query = $db->getQuery(true);
			$query
				->select($db->quoteName('c.comment','text'))
				->select($db->quoteName('c.date','created'))
				->select($db->quoteName('2','browsernav'))
				->select($db->quoteName(JText::_('PLG_SEARCH_JCOMMENTS_COMMENTS'),'section'))
				->select($db->quoteName('','href'))
				->select($db->quoteName('c.id'))
				->select($db->quoteName('jo.title','object_title'))
				->select($db->quoteName('jo.link','object_link'))
				->from($db->quoteName('#__jcomments','c'))
				->join('INNER', $db->quoteName('#__jcomments_objects', 'jo') . ' ON (' .
					$db->quoteName('jo.object_id') . ' = ' . $db->quoteName('c.object_id') . ' AND ' .
					$db->quoteName('jo.object_group') . ' = ' . $db->quoteName('c.object_group') . ' AND ' .
					$db->quoteName('jo.lang') . ' = ' . $db->quoteName('c.lang') . ')')
				->where($db->quoteName('c.published') . '=' . (int)1)
				->where($db->quoteName('c.deleted') . '=' . (int)0)
				->where($db->quoteName('jo.link') . '<>' . $db->quote(''));
			if(JCommentsMultilingual::isEnabled()) {
				$query->where('c.lang = ' . $db->quote(JCommentsMultilingual::getLanguage()));
			}
			$query->where($where . $accessCondition);
			$query->order($db->quoteName('c.object_id'), $order);

			$db->setQuery($query, 0, $limit);
			$rows = $db->loadObjectList();

			$cnt = count($rows);

			if ($cnt > 0) {
				$config = JCommentsFactory::getConfig();
				$enableCensor = $acl->check('enable_autocensor');
				$word_maxlength = $config->getInt('word_maxlength');

				for ($i = 0; $i < $cnt; $i++) {
					$text = JCommentsText::cleanText($rows[$i]->text);

					if ($enableCensor) {
						$text = JCommentsText::censor($text);
					}

					if ($word_maxlength > 0) {
						$text = JCommentsText::fixLongWords($text, $word_maxlength);
					}

					if ($text != '') {
						$rows[$i]->title = $rows[$i]->object_title;
						$rows[$i]->text = $text;
						$rows[$i]->href = $rows[$i]->object_link . '#comment-' . $rows[$i]->id;
						$result[] = $rows[$i];
					}
				}
			}
			unset($rows);
		}

		return $result;
	}

	function onSearchAreas()
	{
		return $this->onContentSearchAreas();
	}

	function onSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		return $this->onContentSearch($text, $phrase, $ordering, $areas);
	}
}
