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

/**
 * JComments model
 */
class JCommentsModel
{
	/**
	 * Returns a comments count for given object
	 *
	 * @param array $options
	 * @param boolean $noCache
	 * @return int
	 */
	public static function getCommentsCount($options = array(), $noCache = false)
	{
		static $cache = array();

		$key = md5(serialize($options));

		if (!isset($cache[$key]) || $noCache == true) {
			$db = JFactory::getDbo();
			$db->setQuery(self::_getCommentsCountQuery($options));
			$cache[$key] = (int) $db->loadResult();
		}

		return $cache[$key];
	}

	/**
	 * Returns list of comments
	 *
	 * @param array $options
	 * @return array
	 */
	public static function getCommentsList($options = array())
	{
		if (!isset($options['orderBy'])) {
			$options['orderBy'] = self::_getDefaultOrder();
		}

		$db = JFactory::getDbo();

		$pagination = isset($options['pagination']) ? $options['pagination'] : '';

		if (isset($options['limit']) && $pagination == 'tree') {

			$options['level'] = 0;
			
			$db->setQuery(self::_getCommentsQuery($options));
			$rows = $db->loadObjectList();

			if (count($rows)) {
				$threads = array();
				foreach ($rows as $row){
					$threads[] = $row->id;
				}

				unset($options['level']);
				unset($options['limit']);

				$options['filter'] = ($options['filter'] ? $options['filter'] . ' AND ' : '') . 'c.thread_id IN (' . join(', ', $threads). ')';

				$db->setQuery(self::_getCommentsQuery($options));
				$rows = array_merge($rows, $db->loadObjectList());
			}
		} else {
			$db->setQuery(self::_getCommentsQuery($options));
			$rows = $db->loadObjectList();
		}

		return $rows;
	}

	public static function getLastComment($object_id, $object_group = 'com_content', $parent = 0)
	{
		$comment = null;

		$db = JFactory::getDbo();
		$config = JCommentsFactory::getConfig();

		$options['object_id'] = (int) $object_id;
		$options['object_group'] = trim($object_group);
		$options['parent'] = (int) $parent;
		$options['published'] = 1;
		$options['orderBy'] = 'c.date DESC';
		$options['limit'] = 1;
		$options['limitStart'] = 0;
		$options['votes'] = $config->getInt('enable_voting');

		$db->setQuery(self::_getCommentsQuery($options));
		$rows = $db->loadObjectList();
		if (count($rows)) {
			$comment = $rows[0];
		}

		return $comment;
	}

	/**
	 * Delete all comments for given ids
	 *
	 * @param  $ids Array of comments ids
	 * @return void
	 */
	public static function deleteCommentsByIds($ids)
	{
		if (is_array($ids)) {
			if (count($ids)) {
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query
					->select('DISTINCT ' . $db->quoteName(array('object_group', 'object_id')))
					->from($db->quoteName('#__jcomments'))
					->where($db->quoteName('parent') . ' IN (' . implode(',', $ids) . ')');
				$db->setQuery($query);
				$objects = $db->loadObjectList();

				if (count($objects)) {
					require_once (JCOMMENTS_LIBRARIES . '/joomlatune/tree.php');

					$descendants = array();

					foreach ($objects as $o) {
						$query
							->clear()
							->select($db->quoteName(array('id', 'parent')))
							->from($db->quoteName('#__jcomments'))
							->where($db->quoteName('object_group') . ' = ' . $db->Quote($o->object_group))
							->where($db->quoteName('object_id') . ' = ' . $db->Quote($o->object_id));
						$db->setQuery($query);
						$comments = $db->loadObjectList();

						$tree = new JoomlaTuneTree($comments);

						foreach ($ids as $id) {
							$descendants = array_merge($descendants, $tree->descendants((int) $id));
						}
						unset($tree);
						$descendants = array_unique($descendants);
					}
					$ids = array_merge($ids, $descendants);
				}
				unset($descendants);

				$ids = implode(',', $ids);

				$query
					->clear()
					->delete($db->quoteName('#__jcomments'))
					->where($db->quoteName('id') . ' IN (' . $ids . ')');
				$db->setQuery($query);
				$db->execute();

				$query
					->clear()
					->delete($db->quoteName('#__jcomments_votes'))
					->where($db->quoteName('commentid') . ' IN (' . $ids . ')');
				$db->setQuery($query);
				$db->execute();

				$query
					->clear()
					->delete($db->quoteName('#__jcomments_reports'))
					->where($db->quoteName('commentid') . ' IN (' . $ids . ')');
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	public static function deleteComments($object_id, $object_group = 'com_content')
	{
		$object_group = trim($object_group);
		$oids = is_array($object_id) ? implode(',', $object_id) : $object_id;

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName('id'))
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('object_group') . ' = ' . $db->Quote($object_group))
			->where($db->quoteName('object_id') . ' IN (' . $oids . ')');
		$db->setQuery($query);
		$cids = $db->loadColumn();

		JCommentsModel::deleteCommentsByIds($cids);

		$query
			->clear()
			->delete($db->quoteName('#__jcomments_objects'))
			->where($db->quoteName('object_group') . ' = ' . $db->Quote($object_group))
			->where($db->quoteName('object_id') . ' = ' . $db->Quote($object_id));
		$db->setQuery($query);
		$db->execute();

		return true;
	}

	protected static function _getCommentsCountQuery(&$options)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$object_id = @$options['object_id'];
		$object_group = @$options['object_group'];
		$published = @$options['published'];
		$userid = @$options['userid'];
		$parent = @$options['parent'];
		$level = @$options['level'];
		$filter = @$options['filter'];

		$where = array();

		if (!empty($object_id)) {
			$where[] = $db->quoteName('c.object_id') . ' = ' . (int)$object_id;
		}

		if (!empty($object_group)) {
			$where[] = $db->quoteName('c.object_group') . ' = ' . $db->Quote($object_group);
		}

		if ($parent !== null) {
			$where[] = $db->quoteName('c.parent') . ' = ' . (int)$parent;
		}

		if ($level !== null) {
			$where[] = $db->quoteName('c.level') . ' = ' . (int)$level;
		}

		if ($published !== null) {
			$where[] = $db->quoteName('c.published') . ' = ' . (int)$published;
		}

		if ($userid !== null) {
			$where[] = $db->quoteName('c.userid') . ' = ' . (int)$userid;
		}

		if ($filter != "") {
			$where[] = $filter;
		}

		if (JCommentsMultilingual::isEnabled()) {
			$where[] = $db->quoteName('c.lang') . ' = ' . $db->quote(JCommentsMultilingual::getLanguage());
		}

		$query
			->select('count(*)')
			->from($db->quoteName('#__jcomments','c'));
		if (count($where)) {
			$query->where(implode(' AND ', $where));
		}

		return $query;
	}

	protected static function _getCommentsQuery(&$options)
	{
		$acl = JCommentsFactory::getACL();
		$db = JFactory::getDbo();

		$object_id = @$options['object_id'];
		$object_group = @$options['object_group'];
		$parent = @$options['parent'];
		$level = @$options['level'];
		$published = @$options['published'];
		$userid = @$options['userid'];
		$filter = @$options['filter'];

		$orderBy = @$options['orderBy'];
		$limitStart = isset($options['limitStart']) ? $options['limitStart'] : 0;
		$limit = @$options['limit'];

		$votes = isset($options['votes']) ? $options['votes'] : true;
		$objectinfo = isset($options['objectinfo']) ? $options['objectinfo'] : false;

		$where = array();

		if (!empty($object_id)) {
			$where[] = $db->quoteName('c.object_id') . ' = ' . $object_id;
		}

		if (!empty($object_group)) {
			if (is_array($object_group)) {
				$where[] = '(' . $db->quoteName('c.object_group') . ' = ' . 
					implode(' OR ' . $db->quoteName('c.object_group') . ' = ', $db->quote($object_group)) . ')';
			} else {
				$where[] = $db->quoteName('c.object_group') . ' = ' . $db->Quote($object_group);
			}
		}

		if ($parent !== null) {
			$where[] = $db->quoteName('c.parent') . ' = ' . $parent;
		}

		if ($level !== null) {
			$where[] = $db->quoteName('c.level') . ' = ' . (int) $level;
		}

		if ($published !== null) {
			$where[] = $db->quoteName('c.published') . ' = ' . $published;
		}

		if ($userid !== null) {
			$where[] = $db->quoteName('c.userid') . ' = ' . $userid;
		}

		if (JCommentsMultilingual::isEnabled()) {
			$language = isset($options['lang']) ? $options['lang'] : JCommentsMultilingual::getLanguage();
			$where[] = $db->quoteName('c.lang') . ' = ' . $db->Quote($language);
		}

		if ($objectinfo && isset($options['access'])) {
			if (is_array($options['access'])) {
				$access = implode(',', $options['access']);
				$where[] = $db->quoteName('jo.access') . ' IN (' . $access . ')';
			} else {
				$where[] = $db->quoteName('jo.access') . ' <= ' . (int)$options['access'];
			}
		}

		if ($filter != "") {
			$where[] = $filter;
		}

		$query = $db->getQuery(true);
		$selection = array('c.id', 'c.parent', 'c.object_id', 'c.object_group', 'c.userid', 'c.name', 
			           'c.username', 'c.title', 'c.comment', 'c.email', 'c.homepage', 'c.date', 'c.date as datetime', 'c.ip',
				   'c.published', 'c.deleted', 'c.checked_out','c.checked_out_time', 'c.isgood', 'c.ispoor');
		array_push($selection, $votes ? 'v.value as voted' : '1 as voted');
		$query->select($selection);
		switch ($db->getServerType()) {
			case 'postgresql' :
				$query->select('case when ' . $db->quoteName('c.parent') .' = 0 then (SELECT extract(epoch FROM ' . $db->quoteName('c.date') . ')) else 0 end as threaddate');
				break;
			case 'mysql' :
			case 'mysqli' :
				$query->select('case when c.parent = 0 then UNIX_TIMESTAMP(c.date) else 0 end as threaddate');
				break;
			default :
				//Microsoft SQL server
		}
		$query->select($objectinfo ? array($db->quoteName('jo.title','object_title') , $db->quoteName('jo.link','object_link'), $db->quoteName('jo.access','object_access')) :
					     array("'' AS object_title", "'' AS object_link", "0 AS object_access", "0 AS object_owner"));
		$query->from($db->quoteName('#__jcomments','c'));
		if ($votes) {
			$query->join('LEFT', $db->quoteName('#__jcomments_votes','v') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('v.commentid') . 
					($acl->getUserId() ? (" AND " . $db->quoteName('v.userid') . " = " . $acl->getUserId()) : 
						     	     (" AND " . $db->quoteName('v.userid') . " = 0 AND " . $db->quoteName('v.ip') . " = " . $db->quote($acl->getUserIP()))
					)
				    );
		}
		if ($objectinfo) {
			$query->join('LEFT', $db->quoteName('#__jcomments_objects','jo') . ' ON ' . 
				$db->quoteName('jo.object_id') . ' = ' . $db->quoteName('c.object_id') . ' AND ' .
				$db->quoteName('jo.object_group') . ' = ' . $db->quoteName('c.object_group') . ' AND ' .
				$db->quoteName('jo.lang') . ' = ' . $db->quoteName('c.lang'));
		}
		if (count($where)) {
			$query->where(implode(' AND ', $where));
		}
		$query->order($orderBy);
		if ($limit > 0) {
			$query->setLimit($limit, $limitStart);
		}
		return $query;
	}

	/**
	 * Returns default order for comments list
	 *
	 * @return string
	 */
	protected static function _getDefaultOrder()
	{
		$config = JCommentsFactory::getConfig();

		if ($config->get('template_view') == 'tree') {
			switch($config->getInt('comments_tree_order')) {
				case 2:
					$result = 'threadDate DESC, c.date ASC';
					break;
				case 1:
					$result = 'c.parent, c.date DESC';
					break;
				default:
					$result = 'c.parent, c.date ASC';
					break;
			}
		} else {
			$result = 'c.date ' . $config->get('comments_list_order');
		}

		return $result;
	}
}
