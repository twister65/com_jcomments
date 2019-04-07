<?php
defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldJCommentsObjectGroup extends JFormFieldList
{
	protected function getInput()
	{
		$attr = '';
		$attr .= $this->element['class'] ? ' class="'.(string) $this->element['class'].'"' : '';
		$attr .= $this->element['size'] ? ' size="'.(int) $this->element['size'].'"' : '';
		$attr .= $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';
		$attr .= $this->multiple ? ' multiple="multiple"' : '';

		$options = (array) $this->getOptions();

		return JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
	}

	protected function getOptions()
	{
		$options = array();

		$db = JFactory::getDBO();
		$query = "SELECT DISTINCT " . $db->quoteName('element') . " FROM " . $db->quoteName('#__extensions');
		$query .= " WHERE " . $db->quoteName('type') . " = " . $db->quote('component');
		$query .= " ORDER BY " . $db->quoteName('element') . ";";
		//$db->setQuery('SELECT DISTINCT `element` FROM `#__extensions` WHERE `type` = "component" ORDER BY `element`;');
		$db->setQuery($query);
		$components = $db->loadColumn();

		$plugins = JFolder::files(JPATH_SITE . '/components/com_jcomments/plugins/', '\.plugin\.php', true, false);

		if (is_array($plugins)) {
			foreach($plugins as $plugin) {
				$pluginName = str_replace('.plugin.php', '', $plugin);
				foreach($components as $component) {
					if ($pluginName == $component || strpos($pluginName, $component . '_') !== false) {
						$o = new StdClass;
						$o->value = $pluginName;
						$o->text = $pluginName;
						$options[] = $o;
					}
				}
			}
		} else {
			$o = new StdClass;
			$o->value = 'com_jcomments';
			$o->text = 'com_jcomments';
			$options[] = $o;
		}

		return $options;
	}
}
