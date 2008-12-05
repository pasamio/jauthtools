<tr class="<?php echo "row".$this->item->index % 2; ?>">
	<td valign="top" align="center"><?php echo $this->item->cb ?></td>
	<td align="center"><?php echo $this->item->id ?></td>
	<td><a href="index.php?option=com_sso&task=edit&mode=<?php echo $this->mode ?>&id=<?php echo $this->item->id ?>"><?php echo $this->item->name ?></a></td>
	<td align="center"><?php echo $this->item->published ?></td>
	<td align="center"><?php echo $this->item->ordering ?></td>
	<td align="center"><?php echo $this->item->type ?></td>
</tr>