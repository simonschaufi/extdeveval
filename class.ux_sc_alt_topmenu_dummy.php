<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license 
*  from the author is found in LICENSE.txt distributed with these scripts.
*
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Adding content to dummy script display in top frame; Listing of links for developers
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   57: class ux_SC_alt_topmenu_dummy extends SC_alt_topmenu_dummy 
 *   64:     function dummyContent()	
 *   98:     function ext_links()	
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 

/**
 * Adding content to dummy script display in top frame; Listing of links for developers
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_extdeveval
 */
class ux_SC_alt_topmenu_dummy extends SC_alt_topmenu_dummy {
	
	/**
	 * Creates the dummy content of the top frame if no menu - which is a blank page.
	 * 
	 * @return	void		
	 */
	function dummyContent()	{
		global $TBE_TEMPLATE;
		
		if ($GLOBALS['BE_USER']->isAdmin())	{
				// Start page
			$TBE_TEMPLATE->docType = 'xhtml_trans';
			$TBE_TEMPLATE->bodyTagId.= '-iconmenu';
			
			$this->content.=$TBE_TEMPLATE->startPage('Top frame display of developers links');
		
				// Make menu and add it:
			$this->content.='
	
				<!--
				  Alternative module menu made of icons, displayed in top frame:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-topMenu">
					<tr>
						<td class="c-menu" style="padding-top:4px;"><strong>Dev links:</strong> '.$this->ext_links().'</td>
					</tr>
				</table>';

				// End page:
			$this->content.=$TBE_TEMPLATE->endPage();
		} else {
			parent::dummyContent();
		}
	}

	/**
	 * Render the links from the script options in TYPO3_CONF_VARS
	 * 
	 * @return	string		HTML content
	 */
	function ext_links()	{
		global $TYPO3_CONF_VARS;
		
		$links=array();
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['ext/extdeveval/class.ux_sc_alt_topmenu_dummy.php']['links']))	{
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['ext/extdeveval/class.ux_sc_alt_topmenu_dummy.php']['links'] as $linkConf)	{
				$aOnClick = "return top.openUrlInWindow('".$linkConf[1]."','ShowAPI');";
				$links[]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.htmlspecialchars($linkConf[0]).'</a>';
			}
		}
		
		return implode(' | ',$links);
	}
}
?>