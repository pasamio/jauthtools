<tr class="<?php echo "row".$this->item->index % 2; ?>">
	<td align="center"><?php echo $this->item->id ?></td>
	<td><a href="index.php?option=com_ssomanager&task=edit&mode=B&id=0&plugin_id=<?php echo $this->item->id ?>">Add new <?php echo $this->item->name ?> identity provider &gt;&gt;&gt;</a></td>
</tr>