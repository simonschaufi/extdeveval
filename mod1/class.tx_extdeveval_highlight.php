<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Kasper Sk�rh�j (kasper@typo3.com)
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
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * @author	Kasper Sk�rh�j <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   46: class tx_extdeveval_cachefiles 
 *   53:     function cacheFiles()	
 *   99:     function removeCacheFiles()	
 *  118:     function removeALLtempCachedFiles()	
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 
require_once(PATH_t3lib.'class.t3lib_tsparser.php');
 
/**
 * Syntax Highlighting TypoScript or PHP code
 */
class tx_extdeveval_highlight {
	var $highLightStyles = array(
		'prespace' 			=> array('<span style="">','</span>'),	// Space before any content on a line
		'objstr_postspace' 	=> array('<span style="">','</span>'),	// Space after the object string on a line
		'operator_postspace' => array('<span style="">','</span>'),	// Space after the operator on a line
		'operator' 			=> array('<span style="color: black; font-weight: bold;">','</span>'),	// The operator char
		'value' 			=> array('<span style="color: #cc0000;">','</span>'),	// The value of a line
		'objstr' 			=> array('<span style="color: #0000cc;">','</span>'),	// The object string of a line
		'value_copy' 		=> array('<span style="color: #006600;">','</span>'),	// The value when the copy syntax (<) is used; that means the object reference
		'value_unset' 		=> array('<span style="background-color: #66cc66;">','</span>'),	// The value when an object is unset. Should not exist.
		'ignored'			=> array('<span style="background-color: #66cc66;">','</span>'),	// The "rest" of a line which will be ignored. 
		'default' 			=> array('<span style="background-color: #66cc66;">','</span>'),	// The default style if none other is applied.
		'comment' 			=> array('<span style="color: #666666; font-style: italic;">','</span>'),	// Comment lines
		'condition'			=> array('<span style="background-color: maroon; color: #ffffff; font-weight: bold;">','</span>'),	// Conditions
		'error' 			=> array('<span style="background-color: yellow; border: 1px red dashed; font-weight: bold;">','</span>'),	// Error messages
		'linenum' 			=> array('<span style="background-color: #eeeeee;">','</span>'),	// Line numbers
	);
	var $highLightStyles_analytic = array(
		'prespace' 			=> array('<span style="background-color: #cccc99;">','</span>'),	// Space before any content on a line
		'objstr_postspace' 	=> array('<span style="background-color: #cccc99;">','</span>'),	// Space after the object string on a line
		'operator_postspace' => array('<span style="background-color: #cccc99;">','</span>'),	// Space after the operator on a line
		'operator' 			=> array('<span style="color: black; font-weight: bold; background-color: #cc6600;">','</span>'),	// The operator char
		'value' 			=> array('<span style="background-color: #ffff00; color: #cc0000;">','</span>'),	// The value of a line
		'objstr' 			=> array('<span style="background-color: #99ffff; color: #0000cc;">','</span>'),	// The object string of a line
		'value_copy' 		=> array('<span style="color: #006600;">','</span>'),	// The value when the copy syntax (<) is used; that means the object reference
		'value_unset' 		=> array('<span style="background-color: #66cc66;">','</span>'),	// The value when an object is unset. Should not exist.
		'ignored'			=> array('<span style="background-color: #66cc66;">','</span>'),	// The "rest" of a line which will be ignored. 
		'default' 			=> array('<span style="background-color: #66cc66;">','</span>'),	// The default style if none other is applied.
		'comment' 			=> array('<span style="color: #666666; font-style: italic;">','</span>'),	// Comment lines
		'condition'			=> array('<span style="background-color: maroon; color: #ffffff; font-weight: bold;">','</span>'),	// Conditions
		'error' 			=> array('<span style="background-color: yellow; border: 1px red dashed; font-weight: bold;">','</span>'),	// Error messages
		'linenum' 			=> array('<span style="background-color: #eeeeee;">','</span>'),	// Line numbers
	);

	var $highLightBlockStyles = 'border-left: black solid 3px;';

					
	/**
	 * The main function in the class
	 * 
	 * @return	string		HTML content
	 */
	function main()	{
		$inputCode = t3lib_div::GPvar('input_code');
	
		$content = '';
		$content.='
		<textarea name="input_code"'.$GLOBALS['TBE_TEMPLATE']->formWidthText(48,'','off').' rows="20" wrap="off">'.t3lib_div::formatForTextarea($inputCode).'</textarea>
		
		<input type="submit" name="highlight_php" value="PHP" />
		<input type="submit" name="highlight_ts" value="TypoScript" />
		<input type="submit" name="highlight_xml" value="XML" />
		<br />
		<input type="checkbox" name="option_linenumbers" value="1"'.(t3lib_div::GPvar('option_linenumbers')?' checked="checked"':'').' /> Linenumbers (TS/PHP)<br />
		<input type="checkbox" name="option_blockmode" value="1"'.(t3lib_div::GPvar('option_blockmode')?' checked="checked"':'').' /> Blockmode (TS)<br />
		<input type="checkbox" name="option_analytic" value="1"'.(t3lib_div::GPvar('option_analytic')?' checked="checked"':'').' /> Analytic style (TS/XML)<br />
		<input type="checkbox" name="option_showparsed" value="1"'.(t3lib_div::GPvar('option_showparsed')?' checked="checked"':'').' /> Show parsed structure (TS/XML)<br />
		
		';
		
		if (trim($inputCode))	{
				// Highlight PHP
			if (t3lib_div::GPvar('highlight_php'))	{
				if (substr(trim($inputCode),0,2)!='<?')	$inputCode = '<?php'.chr(10).chr(10).chr(10).$inputCode.chr(10).chr(10).chr(10).'?>';
				
				$formattedContent = highlight_string($inputCode, 1);

				if (t3lib_div::GPvar('option_linenumbers'))	{
					$lines = explode('<br />',$formattedContent);
					foreach($lines as $k => $v)	{
						$lines[$k] = '<font color="black">'.str_pad(($k-2),4,' ',STR_PAD_LEFT).':</font> '.$v;
					}
					$formattedContent = implode('<br />',$lines);
				}

				$content.='<hr /><pre class="ts-hl">'.$formattedContent.'</pre>';
			}
				// Highlight TypoScript
			if (t3lib_div::GPvar('highlight_ts'))	{
				$tsparser = t3lib_div::makeInstance("t3lib_TSparser");
				if (t3lib_div::GPvar('option_analytic'))	{
					$tsparser->highLightStyles = $this->highLightStyles_analytic;
					$tsparser->highLightBlockStyles_basecolor= '';
					$tsparser->highLightBlockStyles = $this->highLightBlockStyles;
				} else {
					$tsparser->highLightStyles = $this->highLightStyles;
				}
				$tsparser->lineNumberOffset=0;
				$formattedContent = $tsparser->doSyntaxHighlight($inputCode,t3lib_div::GPvar('option_linenumbers')?array($tsparser->lineNumberOffset):'',t3lib_div::GPvar('option_blockmode'));
				$content.='<hr />'.$formattedContent;

#debug($inputCode);
#$tsparser->xmlToTypoScriptStruct($inputCode);

				if (t3lib_div::GPvar('option_showparsed'))	{
					$content.='<hr />'.t3lib_div::view_array($tsparser->setup);
					/*
					ob_start();
					print_r($tsparser->setup);
					$content.='<hr /><pre>'.ob_get_contents().'</pre>';
					ob_end_clean();
					*/
				}
			}
				// Highlight XML
			if (t3lib_div::GPvar('highlight_xml'))	{
				$formattedContent = $this->xmlHighLight($inputCode,t3lib_div::GPvar('option_analytic')?$this->highLightStyles_analytic:$this->highLightStyles);
				$content.='<hr /><em>Notice: This highlighted version of the above XML data is parsed and then re-formatted. Therefore comments are not included and a 100% similarity with the source is not guaranteed. However the content should be just as valid XML as the source (except CDATA which is not detected as such!!!).</em><br><br>'.$formattedContent;

				if (t3lib_div::GPvar('option_showparsed'))	{
					$treeDat = t3lib_div::xml2tree($inputCode);
					$content.='<hr />';
					$content.='MD5: '.md5(serialize($treeDat));
					$content.=t3lib_div::view_array($treeDat);
				}
			}
		}
		return $content;
	}

	/**
	 * Parses XML input into a PHP array AND formats it again for syntax highlighting/structure view.
	 *
	 * @param	string		XML data input
	 * @return	string		Either error message or the highlighted content wrapped in <pre></pre>
	 */
	function xmlHighLight($string,$HLstyles) {
		$parser = xml_parser_create();
		$vals = array();
		$index = array();
		$lines = explode(chr(10),$string);
		
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, $string, $vals, $index);
		if (xml_get_error_code($parser))	{
			return '<b>Error in line '.xml_get_current_line_number($parser).':</b> '.xml_error_string(xml_get_error_code($parser)).'<br /><em>'.htmlspecialchars($lines[xml_get_current_line_number($parser)-1]).'</em>';
		}
		xml_parser_free($parser);

		$lines='';
		$curLine='';
		foreach($vals as $val) {
			$type = $val['type'];
				// open tag:
			if ($type=='open' || $type=='complete') {
				$curLine=str_pad('',($val['level']-1)*4,' ').'&lt;'.$HLstyles['objstr'][0].htmlspecialchars($val['tag']).$HLstyles['objstr'][1];
				if(isset($val['attributes']))  {
					foreach($val['attributes'] as $k => $v)	{
						$curLine.=' '.$HLstyles['value_copy'][0].htmlspecialchars($k).$HLstyles['value_copy'][1].$HLstyles['operator'][0].'="'.$HLstyles['operator'][1].$HLstyles['value'][0].htmlspecialchars(htmlspecialchars($v)).$HLstyles['value'][1].$HLstyles['operator'][0].'"'.$HLstyles['operator'][1];
					}
				}
				if ($type=='complete')	{
					if(isset($val['value']))	{
						$curLine.='&gt;'.$HLstyles['value'][0].htmlspecialchars(htmlspecialchars($val['value'])).$HLstyles['value'][1].'&lt;/'.$HLstyles['objstr'][0].htmlspecialchars($val['tag']).$HLstyles['objstr'][1].'&gt;';
					} else $curLine.='/&gt;';
				} else $curLine.='&gt;';
				$lines.=$curLine.chr(10);

				if ($type=='open' && isset($val['value']))	{
					$lines.=str_pad('',$val['level']*4,' ').$HLstyles['value'][0].htmlspecialchars(htmlspecialchars($val['value'])).$HLstyles['value'][1].chr(10);
				}
			}
				// finish tag:
			if ($type=='complete' || $type=='close')	{
				if ($type=='close')	{
					$curLine=str_pad('',($val['level']-1)*4,' ').'&lt;/'.$HLstyles['objstr'][0].htmlspecialchars($val['tag']).$HLstyles['objstr'][1].'&gt;';
					$lines.=$curLine.chr(10);
				}
			}
			if($type=='cdata') {
				$lines.=str_pad('',$val['level']*4,' ').$HLstyles['value'][0].htmlspecialchars(htmlspecialchars($val['value'])).$HLstyles['value'][1].chr(10);
			}
		}
		return '<pre class="ts-hl">'.$lines.'</pre>';
	}	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extdeveval/mod1/class.tx_extdeveval_highlight.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extdeveval/mod1/class.tx_extdeveval_highlight.php']);
}
?>