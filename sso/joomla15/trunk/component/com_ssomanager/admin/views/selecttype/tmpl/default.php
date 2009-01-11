<?php defined('_JEXEC') or die('Restricted access'); ?>

<?php
	JHtml::_('behavior.tooltip');
?>

<form action=<?php echo JRoute::_("index.php"); ?> method="post" name="adminForm">

<table class="adminlist" cellpadding="1">
	<thead>
		<tr>
			<th colspan="4">
				<?php echo JText::_('Plugins'); ?>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th colspan="4">
				&nbsp;
			</th>
		</tr>
	</tfoot>
	<tbody>
<?php
$k 		= 0;
$x 		= 0;
$count 	= count($this->plugins);
for ($i=0; $i < $count; $i++) {
	$row = &$this->plugins[$i];
	if($row->type == 'B') {
		$link = 'index.php?option=com_ssomanager&amp;task=edit&amp;mode=serviceprovider&amp;plugin_id='. $row->id;
		if (!$k) {
			?>
			<tr class="<?php echo "row$x"; ?>" valign="top">
			<?php $x = 1 - $x; } ?>
			<td width="50%">
				<span class="editlinktip hasTip" title="<?php echo JText::_(stripslashes($row->name)); ?>">
					<input type="radio" name="module" value="<?php echo $row->id; ?>" id="cb<?php echo $i; ?>">
					<a href="<?php echo $link;?>"><?php echo JText::_($row->name); ?></a></span>
			</td>
			<?php if ($k) : ?>
			</tr>
			<?php endif; ?>
		<?php
		$k = 1 - $k;
	}
}
if($k) {
	?><td>&nbsp;</td></tr><?php
}
?>
</tbody>
</table>

<input type="hidden" name="option" value="com_ssomanager" />
<input type="hidden" name="mode" value="serviceprovider" />
<input type="hidden" name="task" value="edit" />
<input type="hidden" name="boxchecked" value="0" />
<?php echo JHtml::_('form.token'); ?>
</form>
