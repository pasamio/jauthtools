<?php defined('_JEXEC') or die('Restricted access'); 

	JHTML::_('behavior.tooltip'); 
	// clean item data
	JFilterOutput::objectHTMLSafe( $this->plugin, ENT_QUOTES, '' );
	JFilterOutput::objectHTMLSafe( $this->provider, ENT_QUOTES, '' );

?>
<script language="javascript" type="text/javascript">
	function submitbutton(pressbutton) {
		if (pressbutton == "cancel") {
			submitform(pressbutton);
			return;
		}
		// validation
		var form = document.adminForm;
		if (form.name.value == "") {
			alert( "<?php echo JText::_( 'Plugin must have a name', true ); ?>" );
		} else {
			submitform(pressbutton);
		}
	}
</script>

<form action="index.php" method="post" name="adminForm">
<div class="col width-60">
	<fieldset class="adminform">
	<legend><?php echo JText::_( 'Details' ); ?></legend>
	<table class="admintable">
		<tr>
			<td width="100" class="key">
				<label for="name">
					<?php echo JText::_( 'Name' ); ?>:
				</label>
			</td>
			<td>
				<input class="text_area" type="text" name="name" id="name" size="35" value="<?php echo $this->provider->name; ?>" />
			</td>
		</tr>
		<tr>
			<td valign="top" class="key">
				<?php echo JText::_( 'Published' ); ?>:
			</td>
			<td>
				<?php echo $this->lists['published']; ?>
			</td>
		</tr>
		<tr>
			<td valign="top" class="key">
				<label for="folder">
					<?php echo JText::_( 'Type' ); ?>:
				</label>
			</td>
			<td>
				<?php echo $this->plugin->element; ?>
			</td>
		</tr>
		<tr>
			<td valign="top" class="key">
				<label for="element">
					<?php echo JText::_( 'Abbreviation' ); ?>:
				</label>
			</td>
			<td>
				<input class="text_area" type="text" name="abbreviation" id="abbreviation" size="35" value="<?php echo $this->provider->abbreviation; ?>" />
			</td>
		</tr>
		<tr>
			<td valign="top" class="key">
				<?php echo JText::_( 'Order' ); ?>:
			</td>
			<td>
				<?php echo $this->lists['ordering']; ?>
			</td>
		</tr>
		<tr>
			<td valign="top" class="key">
				<?php echo JText::_( 'Plugin Description' ); ?>:
			</td>
			<td>
				<?php echo JText::_( $this->plugin->description ); ?>
			</td>
		</tr>
		<tr>
			<td valign="top" class="key">
				<?php echo JText::_( 'Provider Description'); ?>:
			</td>
			<td>
				<?php echo JText::_($this->provider->description); ?>
			</td>
		</tr>
		</table>
	</fieldset>
</div>
<div class="col width-40">
	<fieldset class="adminform">
	<legend><?php echo JText::_( 'Parameters' ); ?></legend>
	<?php
		jimport('joomla.html.pane');
        // TODO: allowAllClose should default true in J!1.6, so remove the array when it does.
		$pane = &JPane::getInstance('sliders', array('allowAllClose' => true));
		echo $pane->startPane('plugin-pane');
		echo $pane->startPanel(JText :: _('Plugin Parameters'), 'param-page');
		if($output = $this->params->render('params')) :
			echo $output;
		else :
			echo "<div style=\"text-align: center; padding: 5px; \">".JText::_('There are no parameters for this item')."</div>";
		endif;
		echo $pane->endPanel();

		if ($this->params->getNumParams('advanced')) {
			echo $pane->startPanel(JText :: _('Advanced Parameters'), "advanced-page");
			if($output = $this->params->render('params', 'advanced')) :
				echo $output;
			else :
				echo "<div  style=\"text-align: center; padding: 5px; \">".JText::_('There are no advanced parameters for this item')."</div>";
			endif;
			echo $pane->endPanel();
		}

		if ($this->params->getNumParams('legacy')) {
			echo $pane->startPanel(JText :: _('Legacy Parameters'), "legacy-page");
			if($output = $this->params->render('params', 'legacy')) :
				echo $output;
			else :
				echo "<div  style=\"text-align: center; padding: 5px; \">".JText::_('There are no legacy parameters for this item')."</div>";
			endif;
			echo $pane->endPanel();
		}
		echo $pane->endPane();
	?>
	</fieldset>
</div>
<div class="clr"></div>

	<input type="hidden" name="option" value="com_ssomanager" />
	<input type="hidden" name="plugin_id" value="<?php echo $this->provider->plugin_id; ?>" />
	<input type="hidden" name="id" value="<?php echo $this->provider->id; ?>" />
	<input type="hidden" name="cid[]" value="<?php echo $this->provider->id; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="mode" value="<?php echo $this->mode ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>