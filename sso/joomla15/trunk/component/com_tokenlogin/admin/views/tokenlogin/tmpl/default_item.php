<tr class="<?php echo "row".$this->item->index % 2; ?>"> <!--  no  -->
	<td valign="top" align="center"><?php echo $this->item->cb ?></td>
	<td valign="top" align="center"><a href="index.php?option=com_tokenlogin&task=edit&token=<?php echo $this->item->logintoken ?>"><?php echo $this->item->username ?></a></td>
	<td valign="top" align="center"><?php echo $this->item->logins ?></td>
	<td valign="top" align="center"><?php echo $this->item->expiry ?></td>
	<td valign="top" align="center"><a href="<?php echo $this->item->loginurl ?>"><?php echo $this->item->logintoken ?></a></td>
</tr>
