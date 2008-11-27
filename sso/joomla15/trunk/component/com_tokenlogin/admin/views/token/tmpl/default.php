<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Nov 27, 2008
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
?>
<form name="adminForm" method="post" action="index.php">
<table class="">
	<tr>
		<th><?php echo JText::_('Login Token') ?></th>
		<td><?php echo $this->data->logintoken ?></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<th><?php echo JText::_('Username') ?></th>
		<td><input type="text" name="username" value="<?php echo $this->data->username ?>" /></td>
		<td><?php echo JText::_('USERNAMEDESC') ?></td>
	</tr>
	<tr>
		<th><?php echo JText::_('Login') ?></th>
		<td><input type="text" name="logins" value="<?php echo $this->data->logins ?>" /></td>
		<td><?php echo JText::_('LOGINSDESC') ?></td>
	</tr>
	<tr>
		<th><?php echo JText::_('Expiry') ?></th>
		<td><input type="text" name="expiry" value="<?php echo $this->data->expiry ?>" /></td>
		<td><?php echo JText::_('EXPIRYDESC') ?></td>
	</tr>
	<tr>
		<th><?php echo JText::_('Landing Page') ?></th>
		<td><input type="text" name="landingpage" value="<?php echo $this->data->landingpage ?>" /></td>
		<td><?php echo JText::_('LANDINGPAGEDESC') ?></td>
	</tr>
	<tr>
		<th><?php echo JText::_('Login URL') ?></th>
		<td><?php if($this->loginurl) : ?>
			<a href="<?php echo $this->loginurl ?>"><?php echo JText::_('Login URL Location') ?>	</a>
			<?php else : ?>
			<p><?php echo JText::_('No login URL available') ?></p>
			<?php endif; ?>
		</td>
		<td><?php echo JText::_('LOGINURLDESC') ?></td>
	</tr>
</table>
<input type="hidden" name="option" value="com_tokenlogin" />
<input type="hidden" name="task" value="save" />
<input type="hidden" name="token" value="<?php echo $this->data->logintoken ?>" />
<?php echo JHTML::_( 'form.token' ); ?>  
</form>