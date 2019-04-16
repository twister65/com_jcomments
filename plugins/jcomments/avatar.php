<?php
/**
 * JComments - Joomla Comment System
 *
 * Enable avatar support for JComments
 *
 * @version 4.1
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2014 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

class plgJCommentsAvatar extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	public function onPrepareAvatar(&$comment)
	{
		$comments = array();
		$comments[0] =& $comment;
		$this->onPrepareAvatars($comments);
	}

	public function onPrepareAvatars(&$comments)
	{
		$db = JFactory::getDBO();

		$avatar_type = $this->params->get('avatar_type', 'gravatar');
		$avatar_default_avatar = $this->params->get('avatar_default_avatar');
		$avatar_custom_default_avatar = $this->params->get('avatar_custom_default_avatar');

		$avatar_link = $this->params->get('avatar_link', 0);
		$avatar_link_target = $this->params->get('avatar_link_target');
		$avatar_link_target = $avatar_link_target != '_self' ? ' target="' . $avatar_link_target . '"' : '';

		$users = array();
		foreach ($comments as &$comment) {
			if ($comment->userid != 0) {
				$users[] = (int)$comment->userid;
			}

			$comment->avatar = '';
		}

		$users = array_unique($users);

		$avatars = array();

		switch ($avatar_type) {
			case 'aup':
				if (count($users)) {
					$db->setQuery('SELECT userid, avatar, referreid FROM #__alpha_userpoints WHERE userid in (' . implode(',', $users) . ')');
					$avatars = $db->loadObjectList('userid');
				}

				$Itemid = self::getItemid('index.php?option=com_alphauserpoints&view=users');
				if (empty($Itemid)) {
					$Itemid = self::getItemid('index.php?option=com_alphauserpoints&view=account');
				}

				$avatarA = JPATH_SITE . '/components/com_alphauserpoints/assets/images/avatars/';
				$avatarL = JURI::base() . 'components/com_alphauserpoints/assets/images/avatars/';

				foreach ($comments as &$comment) {
					$uid = (int)$comment->userid;

					$comment->profileLink = $uid ? JRoute::_('index.php?option=com_alphauserpoints&view=account&userid=' . $avatars[$uid]->referreid . $Itemid) : '';

					if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
						if (is_file($avatarA . $avatars[$uid]->avatar)) {
							$comment->avatar = $avatarL . $avatars[$uid]->avatar;
						}
					}
				}
				break;

			case 'cb':
				if (count($users)) {
					$db->setQuery('SELECT user_id, avatar FROM #__comprofiler WHERE user_id in (' . implode(',', $users) . ') AND avatarapproved = 1');
					$avatars = $db->loadObjectList('user_id');
				}

				$Itemid = self::getItemid('index.php?option=com_comprofiler&task=profile');
				if (empty($Itemid)) {
					$Itemid = self::getItemid('index.php?option=com_comprofiler&task=userslist');
					if (empty($Itemid)) {
						$Itemid = self::getItemid('index.php?option=com_comprofiler');
					}
				}

				foreach ($comments as &$comment) {
					$uid = (int)$comment->userid;

					$comment->profileLink = $uid ? JRoute::_('index.php?option=com_comprofiler&task=userProfile&user=' . $uid . $Itemid) : '';

					if (isset($avatars[$uid]) && !empty($avatars[$uid]->avatar)) {
						$tn = strpos($avatars[$uid]->avatar, 'gallery') === 0 ? '' : 'tn';
						$comment->avatar = JURI::base() . 'images/comprofiler/' . $tn . $avatars[$uid]->avatar;
					}
				}
				break;

			case 'contacts':
				if (count($users)) {
					$query = 'SELECT cd.user_id as userid, cd.image as avatar'
						. ' , CASE WHEN CHAR_LENGTH(cd.alias) THEN CONCAT_WS(":", cd.id, cd.alias) ELSE cd.id END as slug'
						. ' , CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'
						. ' FROM #__contact_details AS cd '
						. ' INNER JOIN #__categories AS cc on cd.catid = cc.id'
						. ' WHERE cd.user_id in (' . implode(',', $users) . ')';
					$db->setQuery($query);
					$avatars = $db->loadObjectList('userid');
				}

				foreach ($comments as &$comment) {
					$uid = (int)$comment->userid;

					$comment->profileLink = $uid ? JRoute::_('index.php?option=com_contact&view=contact&id=' . $avatars[$uid]->slug . '&catid=' . $avatars[$uid]->catslug) : '';

					if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
						$comment->avatar = JURI::base() . '/' . $avatars[$uid]->avatar;
					}
				}
				break;

			case 'discussions':
				if (count($users)) {
					$db->setQuery('SELECT id as userid, avatar FROM #__discussions_users WHERE id in (' . implode(',', $users) . ')');
					$avatars = $db->loadObjectList('userid');
				}

				$avatarA = JPATH_SITE . '/images/discussions/users/';
				$avatarL = JURI::base() . 'images/discussions/users/';

				foreach ($comments as &$comment) {
					$uid = (int)$comment->userid;

					$comment->profileLink = '';

					if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
						if (file_exists($avatarA . $uid . '/large/' . $avatars[$uid]->avatar)) {
							$comment->avatar = $avatarL . $uid . '/large/' . $avatars[$uid]->avatar;
						}
					}
				}
				break;

			case 'easyblog':
				$router = JPATH_SITE . '/components/com_easyblog/helpers/router.php';
				if (is_file($router)) {
					require_once($router);
					require_once(EBLOG_HELPERS . '/image.php');

					if (count($users)) {
						$db->setQuery('SELECT id as userid, avatar FROM #__easyblog_users WHERE id in (' . implode(',', $users) . ')');
						$avatars = $db->loadObjectList('userid');
					}

					$avatarA = JPATH_ROOT . DS . EasyImageHelper::getAvatarRelativePath() . DS;
					$avatarL = JURI::base() . EasyImageHelper::getAvatarRelativePath() . '/';

					foreach ($comments as &$comment) {
						$uid = (int)$comment->userid;

						$comment->profileLink = EasyBlogRouter::_('index.php?option=com_easyblog&view=blogger&layout=listings&id=' . $uid, false);

						if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
							if (file_exists($avatarA . $avatars[$uid]->avatar)) {
								$comment->avatar = $avatarL . $avatars[$uid]->avatar;
							}
						}
					}
				}
				break;

			case 'easydiscuss':
				$router = JPATH_SITE . '/components/com_easydiscuss/helpers/router.php';
				if (is_file($router)) {
					require_once($router);
					require_once(JPATH_SITE . '/components/com_easydiscuss/helpers/helper.php');
					require_once(JPATH_SITE . '/components/com_easydiscuss/helpers/image.php');

					if (count($users)) {
						$db->setQuery('SELECT id as userid, avatar FROM #__discuss_users WHERE id in (' . implode(',', $users) . ')');
						$avatars = $db->loadObjectList('userid');
					}

					$avatarA = JPATH_ROOT . DS . DiscussImageHelper::getAvatarRelativePath() . DS;
					$avatarL = JURI::base() . DiscussImageHelper::getAvatarRelativePath() . '/';

					foreach ($comments as &$comment) {
						$uid = (int)$comment->userid;

						$comment->profileLink = DiscussRouter::_('index.php?option=com_easydiscuss&view=profile&id=' . $uid, false);

						if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
							if (file_exists($avatarA . $avatars[$uid]->avatar)) {
								$comment->avatar = $avatarL . $avatars[$uid]->avatar;
							}
						}
					}
				}
				break;

			case 'jomsocial':
				if (count($users)) {
					$db->setQuery('SELECT userid, thumb as avatar FROM #__community_users WHERE userid in (' . implode(',', $users) . ')');
					$avatars = $db->loadObjectList('userid');
				}

				$avatarA = JPATH_SITE . DS;
				$avatarL = JURI::base() . '/';

				foreach ($comments as &$comment) {
					$uid = (int)$comment->userid;

					$comment->profileLink = $uid ? JRoute::_('index.php?option=com_community&view=profile&userid=' . $uid) : '';

					if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '' && $avatars[$uid]->avatar != 'components/com_community/assets/default_thumb.jpg') {
						if (file_exists($avatarA . $avatars[$uid]->avatar)) {
							$comment->avatar = $avatarL . $avatars[$uid]->avatar;
						}
					}
				}
				break;

			case 'joobb':
				$cfgFile = JPATH_SITE . '/components/com_joobb/system/joobbconfig.php';

				if (is_file($cfgFile)) {
					include_once($cfgFile);

					if (count($users)) {
						$db->setQuery('SELECT id, avatar_file as avatar FROM #__joobb_users WHERE id in (' . implode(',', $users) . ')');
						$avatars = $db->loadObjectList('id');
					}

					$Itemid = self::getItemid('index.php?option=com_joobb');

					$config = JoobbConfig::getInstance();
					$avatarsPath = $config->getAvatarSettings('avatar_path');

					$avatarA = JPATH_SITE . DS . $avatarsPath . DS;
					$avatarL = JURI::root() . '/' . $avatarsPath . '/';

					foreach ($comments as &$comment) {
						$uid = (int)$comment->userid;

						$comment->profileLink = $uid ? JRoute::_('index.php?option=com_joobb&view=profile&id=' . $avatars[$uid]->id . $Itemid) : '';

						if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
							if (file_exists($avatarA . $avatars[$uid]->avatar)) {
								$comment->avatar = $avatarL . $avatars[$uid]->avatar;
							}
						}
					}
				}
				break;

			case 'k2':
				$router = JPATH_SITE . '/components/com_k2/helpers/route.php';
				if (is_file($router)) {
					require_once($router);
					require_once(JPATH_SITE . '/components/com_k2/helpers/utilities.php');

					if (count($users)) {
						$db->setQuery('SELECT userid, image as avatar FROM #__k2_users WHERE userid in (' . implode(',', $users) . ')');
						$avatars = $db->loadObjectList('userid');
					}

					$avatarA = JPATH_SITE . '/media/k2/users/';
					$avatarL = JURI::base() . 'media/k2/users/';

					foreach ($comments as &$comment) {
						$uid = (int)$comment->userid;

						$comment->profileLink = $uid ? JRoute::_(K2HelperRoute::getUserRoute($uid)) : '';

						if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
							if (file_exists($avatarA . $avatars[$uid]->avatar)) {
								$comment->avatar = $avatarL . $avatars[$uid]->avatar;
							}
						}
					}
				}
				break;

			case 'kunena':
				$api = JPATH_ADMINISTRATOR . '/components/com_kunena/api.php';
				if (is_file($api)) {
					require_once($api);

					if (count($users)) {
						$db->setQuery('SELECT userid, avatar FROM #__kunena_users WHERE userid in (' . implode(',', $users) . ')');
						$avatars = $db->loadObjectList('userid');
					}

					$avatarA = JPATH_SITE . '/media/kunena/avatars/';
					$avatarL = JURI::base() . 'media/kunena/avatars/';

					foreach ($comments as &$comment) {
						$uid = (int)$comment->userid;

						$comment->profileLink = $uid ? KunenaRoute::_('index.php?option=com_kunena&func=profile&userid=' . $uid) : '';

						if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
							if (is_file($avatarA . $avatars[$uid]->avatar)) {
								$comment->avatar = $avatarL . $avatars[$uid]->avatar;
							}
						}
					}
				}
				break;

			case 'phocagallery':
				$helper = JPATH_SITE . '/components/com_phocagallery/libraries/phocagallery/path/path.php';
				if (is_file($helper)) {
					require_once($helper);

					if (count($users)) {
						$db->setQuery('SELECT userid, avatar FROM #__phocagallery_user WHERE userid in (' . implode(',', $users) . ')');
						$avatars = $db->loadObjectList('userid');
					}

					$path = PhocaGalleryPath::getPath();

					$avatarA = $path->avatar_abs;
					$avatarL = $path->avatar_rel;

					foreach ($comments as &$comment) {
						$uid = (int)$comment->userid;

						$comment->profileLink = '';

						if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
							if (is_file($avatarA . $avatars[$uid]->avatar)) {
								$comment->avatar = $avatarL . $avatars[$uid]->avatar;
							}
						}
					}
				}
				break;

			case 'slogin':
				$helper = JPATH_SITE . '/plugins/slogin_integration/profile/profile.php';
				if (is_file($helper)) {
					if (count($users)) {
						$db->setQuery('SELECT user_id as userid, social_profile_link as link, avatar FROM #__plg_slogin_profile WHERE current_profile = 1 AND user_id in (' . implode(',', $users) . ')');
						$avatars = $db->loadObjectList('userid');
					}

					$plugin = JPluginHelper::getPlugin('slogin_integration', 'profile');
					$pluginParams = new JRegistry();
					$pluginParams->loadString($plugin->params);
					$folder = $pluginParams->get('rootfolder', 'images/avatar');

					$avatarA = JPATH_SITE . '/' . $folder . '/';
					$avatarL = JURI::base() . $folder . '/';

					foreach ($comments as &$comment) {
						$uid = (int)$comment->userid;

						if (isset($avatars[$uid])) {
							$comment->profileLink = $avatars[$uid]->link;

							if ($avatars[$uid]->avatar != '') {
								if (file_exists($avatarA . $avatars[$uid]->avatar)) {
									$comment->avatar = $avatarL . $avatars[$uid]->avatar;
								}
							}
						} else {
							$comment->profileLink = '';
						}
					}
				} else {
					$helper = JPATH_SITE . '/plugins/slogin_integration/slogin_avatar/slogin_avatar.php';
					if (is_file($helper)) {
						if (count($users)) {
							$db->setQuery('SELECT userid, photo_src as avatar FROM #__plg_slogin_avatar WHERE main = 1 AND userid in (' . implode(',', $users) . ')');
							$avatars = $db->loadObjectList('userid');
						}

						$plugin = JPluginHelper::getPlugin('slogin_integration', 'slogin_avatar');
						$pluginParams = new JRegistry();
						$pluginParams->loadString($plugin->params);
						$folder = $pluginParams->get('rootfolder', 'images/avatar');

						$avatarA = JPATH_SITE . '/' . $folder . '/';
						$avatarL = JURI::base() . $folder . '/';

						foreach ($comments as &$comment) {
							$uid = (int)$comment->userid;

							$comment->profileLink = '';

							if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
								if (file_exists($avatarA . $avatars[$uid]->avatar)) {
									$comment->avatar = $avatarL . $avatars[$uid]->avatar;
								}
							}
						}
					}
				}
				break;

			case 'easyprofile':
				$api = JPATH_ADMINISTRATOR . '/components/com_jsn/jsn.php';
				if (is_file($api)) {
					if (count($users)) {
						$db->setQuery('SELECT id as userid, avatar FROM #__jsn_users WHERE id in (' . implode(',', $users) . ')');
						$avatars = $db->loadObjectList('userid');
					}

					foreach ($comments as &$comment) {
						$uid = (int)$comment->userid;

						$comment->profileLink = $uid ? JRoute::_('index.php?option=com_jsn&view=profile&id=' . $uid) : '';

						if (isset($avatars[$uid]) && $avatars[$uid]->avatar != '') {
							if (is_file(JPATH_SITE . '/' . $avatars[$uid]->avatar)) {
								$comment->avatar = JURI::base() . $avatars[$uid]->avatar;
							}
						}
					}
				}
				break;

			case 'gravatar':
			default:
				foreach ($comments as &$comment) {
					$comment->profileLink = '';
					$comment->avatar = $this->getGravatar($comment->email);
				}
				break;
		}

		if ($avatar_default_avatar == 'custom' && empty($avatar_custom_default_avatar)) {
			$avatar_default_avatar = 'default';
		}

		foreach ($comments as &$comment) {
			if (empty($comment->avatar)) {
				switch ($avatar_default_avatar) {
					case 'gravatar':
						$comment->avatar = $this->getGravatar($comment->email);
						break;

					case 'custom':
						$comment->avatar = JURI::base() . ltrim($avatar_custom_default_avatar, '/');
						break;

					case 'default':
						$comment->avatar = JURI::base() . 'components/com_jcomments/images/no_avatar.png';
						break;
				}
			}

			$comment->avatar = self::createImg($comment->avatar, JComments::getCommentAuthorName($comment));

			if ($avatar_link && !empty($comment->profileLink)) {
				$comment->avatar = self::createLink($comment->avatar, $comment->profileLink, $avatar_link_target);
			}
		}

		return;
	}

	protected static function getItemid($link)
	{
		$menu = JFactory::getApplication()->getMenu();
		$item = $menu->getItems('link', $link, true);

		$id = null;

		if (is_array($item)) {
			if (count($item) > 0) {
				$id = $item[0]->id;		
			}
		} else if (is_object($item)) {
			$id = $item->id;
		}

		return ($id !== null) ? '&Itemid=' . $id : '';
	}

	protected function getGravatar($email)
	{
		$avatar_default_avatar = $this->params->get('avatar_default_avatar');

		switch ($avatar_default_avatar) {
			case 'custom':
				$avatar_custom_default_avatar = $this->params->get('avatar_custom_default_avatar');
				if (!empty($avatar_custom_default_avatar)) {
					$default = urlencode(JURI::base() . ltrim($avatar_custom_default_avatar, '/'));
				} else {
					$default = $this->params->get('gravatar_default', 'mm');
				}
				break;

			case 'default':
				$default = urlencode(JURI::base() . 'components/com_jcomments/images/no_avatar.png');
				break;

			case 'gravatar':
			default:
				$default = $this->params->get('gravatar_default', 'mm');
				break;
		}

		return 'http://www.gravatar.com/avatar/' . md5(strtolower($email)) . '?d=' . $default;
	}

	protected static function createImg($src, $alt = '')
	{
		return '<img src="' . $src . '" alt="' . htmlspecialchars($alt) . '" />';
	}

	protected static function createLink($text, $link, $target = '')
	{
		return !empty($link) ? ('<a href="' . $link . '"' . $target . '>' . $text . '</a>') : $text;
	}
}