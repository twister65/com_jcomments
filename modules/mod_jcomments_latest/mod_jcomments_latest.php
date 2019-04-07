<?php
/**
 * JComments Latest - Shows latest comments
 *
 * @version 3.0
 * @package JComments
 * @author smart (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by smart (http://www.joomlatune.ru)
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

if ($params->get('useCSS') && !defined ('_JCOMMENTS_LATEST_CSS')) {
	define ('_JCOMMENTS_LATEST_CSS', 1);

	$app = JFactory::getApplication('site');
	$style = JFactory::getLanguage()->isRTL() ? 'style_rtl.css' : 'style.css';
	$css = 'media/' . $module->module . '/css/' . $style;

	if (is_file(JPATH_SITE . '/templates/' . $app->getTemplate() . '/html/' . $module->module . '/css/' . $style)) {
		$css = 'templates/' . $app->getTemplate() . '/html/' . $module->module . '/css/' . $style;
	}

	$document = JFactory::getDocument();
	$document->addStylesheet($css);
}

$list = modJCommentsLatestHelper::getList($params);

if (!empty($list)) {
	$grouped = false;

	$comments_grouping = $params->get('comments_grouping', 'none');
	$item_heading = $params->get('item_heading', 4);

	if ($comments_grouping !== 'none') {
		$grouped = true;
		$list = modJCommentsLatestHelper::groupBy($list, $comments_grouping);
	}

	require (JModuleHelper::getLayoutPath('mod_jcomments_latest', $params->get('layout', 'default')));
}