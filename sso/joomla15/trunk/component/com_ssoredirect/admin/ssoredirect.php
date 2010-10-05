<?php
defined('_JEXEC') or die('For the island!');

                JToolBarHelper::title( JText::_( 'Login Redirect' ), 'user.png' );
                JToolBarHelper::preferences('com_ssoredirect', '550');
?>
<p><?php echo JText::_('Press preferences to alter settings') ?></p>
