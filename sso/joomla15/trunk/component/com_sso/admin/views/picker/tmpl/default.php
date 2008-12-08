<form method="post" action="index.php" name="adminForm">
	<?php if (count($this->items)) : ?>
		<table class="adminlist" cellspacing="1" width="100%">
		<thead>
			<tr>
			<th class="title" width="5%">ID</th>
			<th class="title">Name</th>
			</tr>
		</thead>
		
		<tbody>
		<?php for($i = 0; $i < count($this->items); $i++) : 
			$this->loadItem($i);
			echo $this->loadTemplate('item');	
		endfor; ?>
		</tbody>
		</table>
	<?php else: ?>
		<p>No plugins available</p>
	<?php endif; ?>
	<input type="hidden" name="option" value="com_sso" />
	<input type="hidden" name="mode" value="<?php echo $this->mode ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="" />
</form>