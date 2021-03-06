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

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldUsergroups extends JFormFieldList
{
	protected $type = 'Usergroups';

	protected function getInput()
	{
		if (!is_array($this->value)) {
			$this->value = explode(',', $this->value);
		}

		return parent::getInput();
	}

	protected function getOptions()
	{
		$options = array();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select(array('a.*', 'COUNT(DISTINCT ' . $db->quoteName('b.id') . ') AS level'))
			->from($db->quoteName('#__usergroups','a'))
			->join('LEFT', $db->quoteName('#__usergroups','b') . ' ON ' .
				$db->quoteName('a.lft') . ' > ' . $db->quoteName('b.lft') . ' AND ' .
				$db->quoteName('a.rgt') . ' < ' . $db->quoteName('b.rgt'))
			->group($db->quoteName(array('a.id', 'a.title', 'a.lft', 'a.rgt', 'a.parent_id')))
			->order($db->quoteName('a.lft') . ' ASC');
		$db->setQuery($query);
		$groups = $db->loadObjectList();

		$divider = (version_compare(JVERSION, '3.0', 'ge')) ? ' ' : '|&mdash;';

		foreach ($groups as $group) {
			$prefix = trim(str_repeat($divider, $group->level));
			$options[] = JHTML::_('select.option', $group->id, trim($prefix . ' ' . $group->title));
		}

		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
