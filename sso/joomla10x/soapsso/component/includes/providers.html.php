<?php
/**
* @version 	$Id: providers.html.php,v V1.1 9918 bytes, 2007-06-07 12:43:34 cero Exp $
* @package 	SSO
* @subpackage 	providers.html.php
* @author	<Tomo.Cerovsek.fgg.uni-lj.si> <Damjan.Murn.uni-lj.si>
* @developers	Tomo Cerovsek, Damjan Murn
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class HTML_providers{

	function show(&$rows, &$pageNav, $search, $message) {
		global $my, $mosConfig_live_site;
		$option = mosGetParam($_REQUEST,'option','com_sso');
		$section = mosGetParam($_REQUEST,'section','providers');
		
		mosCommonHTML::loadOverlib();
		?>
		<script language="javascript" type="text/javascript">
		function submitbutton(pressbutton) {
			var form = document.adminForm;

			if (pressbutton == 'refresh') {
				document.location.href = 'index2.php?option=<?php echo $option ?>&section=<?php echo $section ?>';
			}
			else if (pressbutton == 'local') {
				document.location.href = 'index2.php?option=<?php echo $option ?>&section=<?php echo $section ?>&task=configuration';
			}
			else if (pressbutton == 'add') {
				var url = prompt('Please enter the URL of the Joomla! portal you want to add:', 'http://');
				if (url) {
					document.location.href = 'index2.php?option=<?php echo $option ?>&section=<?php echo $section ?>&task=add&url=' + escape(url);
				}
			}
			else {
				submitform( pressbutton );
			}
		}
		function performOperation(providerId, operation) {
			document.location.href = 'index2.php?option=<?php echo $option ?>&section=<?php echo $section ?>&task=performOperation&providerId=' + providerId + '&operation=' + operation;
		}
		</script>

		<div id="overDiv" style="position:absolute; visibility:hidden; z-index:10000;"></div>
		<form action="index2.php" method="post" name="adminForm">
		<input type="hidden" name="option" id="option" value="<?php echo $option ?>" />
		<input type="hidden" name="section" id="section" value="<?php echo $section ?>" />
		
        <table class="adminheading">
            <tr>
                <th rowspan=2>SSO: Providers</th>
                <td width="right" nowrap="nowrap">
                SEARCH:
                <input type="text" name="search" value="<?php echo $search ?>" class="inputbox" onChange="document.adminForm.submit();" />
                </td>
            </tr>
        </table>

        <div class="message" style="text-align: left; padding-bottom: 10px">
        <?php
		if (is_array($message)) {
			echo implode('<br>', $message);
		} else {
			echo $message;
		}
		?>
        </div>

		<table class="adminlist">
		<tr>
			<th width="20">
			#
			</th>
			<th width="20">
			<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
			</th>
			<th class="title" width="20%" align="left" nowrap>
			Name
			</th>
			<th width="20%" align="left" nowrap>
			Description
			</th>
			<th width="20%" align="left" nowrap>
			URL
			</th>
			<th width="15%" align="left">
			Status
			</th>
			<th width="15%" align="left">
			Operations
			</th>
			<th width="5%" >
			Published
			</th>
		</tr>
		<?php
		$k = 0;
		for ($i=0, $n=count( $rows ); $i < $n; $i++) {
			$row = &$rows[$i];

			$editLink = "index2.php?option=$option&section=$section&task=editA&hidemainmenu=1&providerId=" . urlencode($row->providerId);
			?>
			<tr class="<?php echo 'row'. $k; ?>">
				<td align="center">
					<?php echo $pageNav->rowNumber( $i ); ?>
				</td>
				<td>
					<?php echo mosHTML::idBox($i, $row->providerId); ?>
				</td>
				<td align="left" nowrap>
					<a href="<?php echo $editLink; ?>" title="Show Provider Details">
					<?php echo $row->siteName ?>
					</a>
				</td>
				<td align="left" nowrap>
					<?php echo $row->description ?>
				</td>
				<td align="left" nowrap>
					<?php echo $row->siteUrl ?>
				</td>
				<td align="left">
					<?php echo ssoProvider::getStatusMessage($row->status) ?>
                </td>
				<td align="left" nowrap>
					<?php
					if ($row->providerId == $mosConfig_live_site) {
						echo "N/A";
					} else {
						$operations = operationsSelectList($row);
						$onChange = "performOperation('$row->providerId', this.value)";
						echo mosHTML::selectList( $operations, "operation[$i]", "onChange=\"$onChange\"", 'value', 'text');
					}
	                ?>
				</td>
				<td align="center">
					<a href="javascript:void(0);" onClick="return listItemTask('cb<?php echo $i;?>','<?php echo $row->published ? 'unpublish' : 'publish'; ?>')">
					<img src="images/<?php echo $row->published ? 'tick.png' : 'publish_x.png'; ?>" width="12" height="12" border="0" alt="<?php echo $row->published ? 'Published' : 'Unpublished'; ?>" />
					</a>
				</td>
			</tr>
			<?php
			$k = 1 - $k;
		}
		?>
		</table>
		<?php echo $pageNav->getListFooter(); ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="hidemainmenu" value="0">
		</form>
		<?php
	}

	
	function edit($provider) {
		global $option;
		$section = mosGetParam($_REQUEST,'section','providers');
		
        ?>
		<form action="index2.php?option=<?php echo $option ?>&section=<?php echo $section ?>" method="post" name="adminForm">
		<table class="adminheading">
		<tr>
			<th class="edit">
			Provider <?php echo $provider->siteName ?>
			</th>
		</tr>
		</table>

		<table class="adminform">
		<tr>
			<th colspan="2">
			Details
			</th>
		</tr>
		<tr>
			<td>
			Site URL
			</td>
			<td>
			<a href="<?php echo $provider->siteUrl ?>"><?php echo $provider->siteUrl ?></a>
			</td>
		</tr>
		<tr>
			<td>
			Site Name
			</td>
			<td>
			<?php echo $provider->siteName ?>
			</td>
		</tr>
		<tr>
			<td>
			Description
			</td>
			<td>
			<?php echo $provider->description ?>
			</td>
		</tr>
		<tr>
			<td>
			Username suffix
			</td>
			<td>
			<?php echo $provider->abbreviation ?>
			</td>
		</tr>
		<tr>
			<td>
			IP Address
			</td>
			<td>
			<?php echo $provider->ipAddress ?>
			</td>
		</tr>
		<tr>
			<td>
			Country
			</td>
			<td>
			<?php echo $provider->country ?>
			</td>
		</tr>
		<tr>
			<td>
			Country Code
			</td>
			<td>
			<?php echo $provider->countryCode ?>
			</td>
		</tr>
		<tr>
			<td>
			Language
			</td>
			<td>
			<?php echo $provider->language ?>
			</td>
		</tr>
		<tr>
			<td>
			Status
			</td>
			<td>
			<?php echo ssoProvider::getStatusMessage($provider->status) ?>
			</td>
		</tr>
		<tr>
			<td>
			Comments
			</td>
			<td>
			<input name="comments" class="inputbox" type="text" size="60" maxlength="255" value="<?php echo $provider->comments; ?>" />
			</td>
		</tr>
		<tr>
			<td>
			Published
			</td>
			<td>
			<?php echo mosHTML::yesnoRadioList('published', 'class="inputbox"', $provider->published) ?>
			</td>
		</tr>
		</table>

		<input type="hidden" name="providerId" value="<?php echo $provider->providerId ?>">
		<input type="hidden" name="task" value="">
		</form>
	<?php
	}

 	function editLocalProvider($provider) {
		global $option, $mosConfig_live_site;
		$section = mosGetParam($_REQUEST,'section','providers');
		
        ?>
		<script language="javascript" type="text/javascript">
		function submitbutton(pressbutton) {
			var form = document.adminForm;
			if (pressbutton == 'cancel') {
				submitform( pressbutton );
				return;
			}

			// do field validation
			if (form.siteName.value == '') {
				alert( "Please fill in the site name." );
				form.siteName.focus();
			} else if (form.ipAddress.value == '') {
				alert( "Please fill in the IP address." );
				form.ipAddress.focus();
			} else if ( ! form.ipAddress.value.match(/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/)) {
				alert( "Invalid IP address." );
				form.ipAddress.focus();
			} else if (form.country.value == '') {
				alert( "Please fill in the country." );
				form.country.focus();
			} else if (form.countryCode.value == '') {
				alert( "Please fill in the country code." );
				form.countryCode.focus();
			} else if (form.language.value == '') {
				alert( "Please fill in the language." );
				form.language.focus();
			} else {
				submitform( pressbutton );
			}
		}
		</script>

		<form action="index2.php" method="post" name="adminForm">
		<input type="hidden" name="option" id="option" value="<?php echo $option ?>" />
		<input type="hidden" name="section" id="section" value="<?php echo $section ?>" />
		<table class="adminheading">
		<tr>
			<th class="edit">
			SSO: Local Provider
			</th>
		</tr>
		</table>

		<table class="adminform">
		<tr>
			<th colspan="2">
			Details
			</th>
		</tr>
		<tr>
			<td>
			Site URL
			</td>
			<td>
            <?php echo $mosConfig_live_site ?>
			</td>
		</tr>
		<tr>
			<td>
			Site Name
			</td>
			<td>
			<input name="siteName" class="inputbox" type="text" size="60" maxlength="150" value="<?php echo $provider->siteName; ?>" />
			</td>
		</tr>
		<tr>
			<td>
			Description
			</td>
			<td>
			<textarea name="description" rows="3" cols="60" class="inputbox"><?php echo $provider->description; ?></textarea>
			</td>
		</tr>
		<tr>
			<td>
			IP Address
			</td>
			<td>
			<input name="ipAddress" class="inputbox" type="text" size="15" maxlength="15" value="<?php echo $provider->ipAddress; ?>" />
			</td>
		</tr>
		<tr>
			<td>
			Country
			</td>
			<td>
			<input name="country" class="inputbox" type="text" size="60" maxlength="50" value="<?php echo $provider->country; ?>" />
			</td>
		</tr>
		<tr>
			<td>
			Country Code
			</td>
			<td>
			<input name="countryCode" class="inputbox" type="text" size="2" maxlength="2" value="<?php echo $provider->countryCode; ?>" />
			</td>
		</tr>
		<tr>
			<td>
			Language
			</td>
			<td>
			<input name="language" class="inputbox" type="text" size="60" maxlength="100" value="<?php echo $provider->language; ?>" />
			</td>
		</tr>
		</table>

		<input type="hidden" name="providerId" value="<?php echo $provider->providerId ?>">
		<input type="hidden" name="task" value="">
		</form>
	<?php
	}
}

?>
