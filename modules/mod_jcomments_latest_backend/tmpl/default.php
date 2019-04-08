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
?>
<?php if (version_compare(JVERSION, '3.0.0', 'ge')) : ?>
<?php 
	JHtml::_('bootstrap.tooltip');
?>
<div class="row-striped">
	<?php if (count($list)) : ?>
		<?php foreach ($list as $i=>$item) : ?>
		<div class="row-fluid">
			<div class="span9">
				<?php echo JHtml::_('jgrid.published', $item->published, $i, '', false); ?>
				<?php if ($item->checked_out) : ?>
					<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time); ?>
				<?php endif; ?>

				<strong class="row-title">
					<?php if ($item->link) : ?>
						<a href="<?php echo $item->link; ?>"><?php echo htmlspecialchars($item->comment, ENT_QUOTES, 'UTF-8');?></a>
					<?php else : ?>
						<?php echo htmlspecialchars($item->comment, ENT_QUOTES, 'UTF-8'); ?>
					<?php endif; ?>
				</strong>

				<small class="hasTooltip" title="<?php echo JHtml::tooltipText('MOD_JCOMMENTS_LATEST_BACKEND_HEADING_AUHTOR'); ?>">
					<?php echo $item->author;?>
				</small>
			</div>
			<div class="span3">
				<span class="small"><i class="icon-calendar"></i> <?php echo JHtml::_('date', $item->date, 'Y-m-d'); ?></span>
			</div>
		</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="row-fluid">
			<div class="span12">
				<div class="alert"><?php echo JText::_('MOD_JCOMMENTS_LATEST_BACKEND_NO_MATCHING_RESULTS');?></div>
			</div>
		</div>
	<?php endif; ?>
</div>
<?php else : ?>
<table class="adminlist">
	<thead>
		<tr>
			<th>
				<?php echo JText::_('MOD_JCOMMENTS_LATEST_BACKEND_HEADING_COMMENT'); ?>
			</th>
			<th>
				<strong><?php echo JText::_('MOD_JCOMMENTS_LATEST_BACKEND_HEADING_AUHTOR');?></strong>
			</th>
			<th>
				<strong><?php echo JText::_('MOD_JCOMMENTS_LATEST_BACKEND_HEADING_CREATED'); ?></strong>
			</th>
			<th>
				<strong><?php echo JText::_('MOD_JCOMMENTS_LATEST_BACKEND_HEADING_STATE'); ?></strong>
			</th>
		</tr>
	</thead>
	<?php if (count($list)) : ?>
	<tbody>
		<?php foreach ($list as $i=>$item) : ?>
		<tr>
			<th scope="row">
				<?php if ($item->link) :?>
					<a href="<?php echo $item->link; ?>"><?php echo htmlspecialchars($item->comment, ENT_QUOTES, 'UTF-8');?></a>
				<?php else : ?>
					<?php echo htmlspecialchars($item->comment, ENT_QUOTES, 'UTF-8'); ?>
				<?php endif; ?>
			</th>
			<td class="center">
				<?php echo $item->author;?>
			</td>
			<td class="center">
				<?php echo JHtml::_('date', $item->date, 'Y-m-d H:m'); ?>
			</td>
			<td class="center">
				<?php if (version_compare(JVERSION, '1.6.0', 'ge')) : ?>
					<?php echo JHtml::_('jgrid.published', $item->published, $i, '', false); ?>
				<?php else : ?>
					<img src="images/<?php echo $item->published ? 'tick.png' : 'publish_x.png'; ?>" border="0" alt="<?php echo $item->published ? JText::_('Published') : JText::_('Unpublished'); ?>" />
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
	<?php else : ?>
	<tbody>
		<tr>
			<td colspan="4">
				<p class="noresults"><?php echo JText::_('MOD_JCOMMENTS_LATEST_BACKEND_NO_MATCHING_RESULTS');?></p>
			</td>
		</tr>
	</tbody>
	<?php endif; ?>
</table>
<?php endif; ?> 