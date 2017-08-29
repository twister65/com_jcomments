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

class JCommentsModelBlacklists extends JCommentsModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'jb.id',
				'ip', 'jb.ip',
				'reason', 'jb.reason',
				'notes', 'jb.notes',
				'created', 'jb.created',
				'name', 'u.name',
			);
		}

		parent::__construct($config);
	}

	public function getTable($type = 'Blacklist', $prefix = 'JCommentsTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	protected function getListQuery()
	{
		$query = $this->_db->getQuery(true);
		$query
			->select('jb.*')
			->from($this->_db->quoteName('#__jcomments_blacklist','jb'));

		// Join over the users
		$query
			->select($this->_db->quoteName('u.name'))
			->join('LEFT', $this->_db->quoteName('#__users','u') . ' ON ' .
				$this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('jb.created_by'));

		// Join over the users
		$query
			->select($this->_db->quoteName('u2.name','editor'))
			->join('LEFT', $this->_db->quoteName('#__users','u2') . ' ON ' .
				$this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('jb.checked_out'));

		$search = $this->getState('filter.search');
		if (!empty($search)) {
			$search = $this->_db->Quote('%' . $this->_db->escape($search, true) . '%');
			$query->where('(LOWER(jb.ip) LIKE ' . $search . ' OR LOWER(jb.reason) LIKE ' . $search . ' OR LOWER(jb.notes) LIKE ' . $search . ')');
		}

		$ordering = $this->state->get('list.ordering', 'jb.ip');
		$direction = $this->state->get('list.direction', 'asc');
		$query->order($this->_db->escape($ordering . ' ' . $direction));

		return $query;
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication('administrator');

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		parent::populateState('jb.ip', 'asc');
	}
}
