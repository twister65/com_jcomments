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

class JCommentsImportSliComments extends JCommentsImportAdapter
{
	public function __construct()
	{
		$this->code = 'slicomments';
		$this->extension = 'com_slicomments';
		$this->name = 'sliComments';
		$this->author = 'Jonnathan Soares Lima';
		$this->license = 'GNU/GPL';
		$this->licenseUrl = 'http://www.gnu.org/licenses/gpl-3.0.html';
		$this->siteUrl = 'https://github.com/jonnsl/sliComments';
		$this->tableName = '#__slicomments';
	}

	public function execute($language, $start = 0, $limit = 100)
	{
		$db = JFactory::getDBO();
		$source = $this->getCode();

		$query = $db->getQuery(true);

                $query
			->select(array('c.*'))
		        ->from($db->quoteName($this->tableName,'c'))
		        ->select(array($db->quoteName('u.username','user_username'), $db->quoteName('u.name','user_name'), $db->quoteName('u.email','user_email')))
		        ->join('LEFT', $db->quoteName('#__users','u') . ' ON ' . $db->quoteName('c.userid') . ' = ' . $db->quoteName('u.id'))
			->order($db->escape('c.created'));

		$db->setQuery($query, $start, $limit);
		$rows = $db->loadObjectList();

		foreach ($rows as $row) {
			$table = JTable::getInstance('Comment', 'JCommentsTable');
			$table->object_id = $row->article_id;
			$table->object_group = 'com_content';
			$table->parent = 0;
			$table->userid = $row->user_id;
			$table->name = $row->user_id ? $row->user_name : $row->name;
			$table->username = $row->user_id ? $row->user_username : $row->name;;
			$table->comment = $row->raw;
			$table->email = $row->user_id ? $row->user_email : $row->email;
			$table->published = $row->status;
			$table->date = $row->created;
			$table->isgood = $row->rating > 0 ? $row->rating : 0;
			$table->ispoor = $row->rating < 0 ? abs($row->rating) : 0;
			$table->lang = $language;
			$table->source_id = $row->id;
			$table->source = $source;
			$table->store();
		}
	}
}
