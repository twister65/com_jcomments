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

$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';
if (file_exists($comments)) {
	require_once ($comments);
} else {
	return;
}

require_once (dirname(__FILE__).'/helper.php');

$list = modJCommentsLatestBackendHelper::getList($params);

require (JModuleHelper::getLayoutPath('mod_jcomments_latest_backend', $params->get('layout', 'default')));

?>