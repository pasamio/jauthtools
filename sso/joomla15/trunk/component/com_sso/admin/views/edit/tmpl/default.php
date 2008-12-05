<form name="adminForm" method="post" action="index.php">
	<table>
		<tr><th>Name: </th><td><?php echo $this->data->name ?></td></tr>
		<tr><th>Params:</th><td><?php echo $this->params ?></td></tr>
	</table>
	
	<input type="hidden" name="option" value="com_sso" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="mode" value="<?php echo $this->mode ?>" />
</form>