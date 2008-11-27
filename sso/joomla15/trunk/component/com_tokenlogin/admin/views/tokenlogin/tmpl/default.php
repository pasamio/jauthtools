<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Nov 14, 2008
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<form method="post" action="index.php" name="adminForm">
	<?php if (count($this->items)) : ?>
	<table class="adminlist" cellspacing="1" width="100%">
		<thead>
			<tr>
		<th class="title"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ); ?>);" /></th>
		<th class="title">Username</th>
		<th class="title">Remaining Logins</th>
		<th class="title">Token Expiry</th>
		<th class="title">Login Token/URL</th>
			</tr>
		</thead>
		<tbody>
		<?php for ($i=0, $n=count($this->items), $rc=0; $i < $n; $i++, $rc = 1 - $rc) : ?>
			<?php
				$this->loadItem($i);
				echo $this->loadTemplate('item');
			?>
		<?php endfor; ?>
		</tbody>
	</table>
	<div id="navigation" style="text-align: center">
		<span><?php echo $this->pagination->getPagesLinks(); ?></span>
		<span><?php echo $this->pagination->getPagesCounter(); ?></span>
	</div>
	<?php else : ?>
		<?php echo JText::_( 'No tokens found!' ); ?>
	<?php endif; ?>

<input type="hidden" name="option" value="com_tokenlogin" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<?php echo JHTML::_( 'form.token' ); ?> 
</form>
