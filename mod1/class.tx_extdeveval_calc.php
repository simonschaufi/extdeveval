<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2004 Kasper Skårhøj (kasper@typo3.com)
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
 * Contains a class, tx_extdeveval_calc, which can do various handy calculations
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
 *   59: class tx_extdeveval_calc
 *   71:     function main()
 *
 *              SECTION: Tools functions:
 *  121:     function calc_unixTime()
 *  158:     function calc_crypt()
 *  182:     function calc_md5()
 *  205:     function calc_diff()
 *  297:     function calc_sql()
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Class for calculations
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_extdeveval
 */
class tx_extdeveval_calc {

		// Internal GPvars:
	var $cmd;				// Command array
	var $inputCalc;			// Data array


	/**
	 * Main function, launching the calculator.
	 *
	 * @return	string		HTML content for the module.
	 */
	function main()	{

			// Set GPvar:
		$this->cmd = @key(t3lib_div::_GP('cmd'));
		$this->inputCalc = t3lib_div::_GP('inputCalc');

			// Call calculators:
		switch($this->cmd)	{
			case 'unixTime_toTime':
			case 'unixTime_toSeconds':
				$content.=$this->calc_unixTime();
			break;
			case 'crypt':
				$content.=$this->calc_crypt();
			break;
			case 'md5':
				$content.=$this->calc_md5();
			break;
			case 'diff':
				$content.=$this->calc_diff();
			break;
			case 'sql':
				$content.=$this->calc_sql();
			break;
			default:
				$content.=$this->calc_unixTime();
				$content.=$this->calc_crypt();
				$content.=$this->calc_md5();
				$content.=$this->calc_diff();
				$content.=$this->calc_sql();
			break;
		}

			// Return content:
		return $content;
	}



	/*************************
	 *
	 * Tools functions:
	 *
	 *************************/

	/**
	 * Converting from human-readable time to unix time and vice versa
	 *
	 * @return	string		HTML content
	 */
	function calc_unixTime()	{

			// Processing incoming command:
		if ($this->cmd=='unixTime_toTime')	{
			$this->inputCalc['unixTime']['seconds'] = intval($this->inputCalc['unixTime']['seconds']);
		} elseif ($this->cmd=='unixTime_toSeconds')	{
			$timeParts=array();
			ereg('([0-9]+)[ ]*-[ ]*([0-9]+)[ ]*-([0-9]+)[ ]*([0-9]*):?([0-9]*):?([0-9]*)',trim($this->inputCalc['unixTime']['time']),$timeParts);
			$this->inputCalc['unixTime']['seconds'] = mktime($timeParts[4],$timeParts[5],$timeParts[6],$timeParts[2],$timeParts[1],$timeParts[3]);
		} else {
			$this->inputCalc['unixTime']['seconds'] = time();
		}

			// Render input form:
		$content.='
			<h3>Time:</h3>
			<p>Input UNIX time seconds:</p>
				<input type="text" name="inputCalc[unixTime][seconds]" value="'.htmlspecialchars($this->inputCalc['unixTime']['seconds']).'" size="30" style="'.($this->cmd=='unixTime_toSeconds' ? 'color: red;' :'').'" />
				<input type="submit" name="cmd[unixTime_toTime]" value="'.htmlspecialchars('>>').'" />
				<input type="submit" name="cmd[unixTime_toSeconds]" value="'.htmlspecialchars('<<').'" />
				<input type="text" name="inputCalc[unixTime][time]" value="'.htmlspecialchars(date('d-m-Y H:i:s',$this->inputCalc['unixTime']['seconds'])).'" size="30" style="'.($this->cmd=='unixTime_toTime' ? 'color: red;' :'').'" />(d-m-Y H:i:s)
		';

			// Check if the input time was different:
		if (t3lib_div::isFirstPartOfStr($this->cmd,'unixTime') && $this->inputCalc['unixTime']['time'] && date('d-m-Y H:i:s',$this->inputCalc['unixTime']['seconds']) != trim($this->inputCalc['unixTime']['time']))	{
			$content.='<p><strong>Notice: </strong>The input time string was reformatted during clean-up! Please check it!</p>';
		}

			// Output:
		return $content;
	}

	/**
	 * Converting input string with "crypt()" - for maing htaccess passwords.
	 *
	 * @return	string		HTML content
	 */
	function calc_crypt()	{

			// Render input form:
		$content.='
			<h3>Input string to crypt:</h3>
			<p>Useful for making passwords for .htaccess files.</p>
				<input type="text" name="inputCalc[crypt][input]" value="'.htmlspecialchars($this->inputCalc['crypt']['input']).'" size="50" />
				<input type="submit" name="cmd[crypt]" value="Crypt" />
		';
		if ($this->cmd=='crypt' && trim($this->inputCalc['crypt']['input']))	{
			$content.='
			<p>Crypted string:</p>
			<input type="text" name="-" value="'.htmlspecialchars(crypt($this->inputCalc['crypt']['input'])).'" size="50" />
			';
		}

		return $content;
	}

	/**
	 * Creating MD5 hash of input content.
	 *
	 * @return	string		HTML content
	 */
	function calc_md5()	{

			// Render input form:
		$content.='
			<h3>Input string to MD5 process:</h3>
				<textarea rows="10" name="inputCalc[md5][input]" wrap="off"'.$GLOBALS['TBE_TEMPLATE']->formWidthText(48,'width:98%;','off').'>'.
				t3lib_div::formatForTextarea($this->inputCalc['md5']['input']).
				'</textarea>
				<input type="submit" name="cmd[md5]" value="MD5 process" />
		';
		if ($this->cmd=='md5' && trim($this->inputCalc['md5']['input']))	{
			$content.='
			<p>MD5 hash: <strong>'.md5($this->inputCalc['md5']['input']).'</strong></p>';
		}

		return $content;
	}

	/**
	 * Shows a diff between the two input text strings.
	 *
	 * @return	string		HTML content
	 */
	function calc_diff()	{

			// Render input form:
		$content.='
			<h3>Diff\'ing strings:</h3>
			<p>"Old" string (red):</p>
				<textarea rows="10" name="inputCalc[diff][input1]" wrap="off"'.$GLOBALS['TBE_TEMPLATE']->formWidthText(48,'width:98%;','off').'>'.
				t3lib_div::formatForTextarea($this->inputCalc['diff']['input1']).
				'</textarea>
				'.($this->inputCalc['diff']['input1']?'<p>MD5 hash: <strong>'.md5($this->inputCalc['diff']['input1']).'</strong></p>':'').'
			<p>"New" string (green):</p>
				<textarea rows="10" name="inputCalc[diff][input2]" wrap="off"'.$GLOBALS['TBE_TEMPLATE']->formWidthText(48,'width:98%;','off').'>'.
				t3lib_div::formatForTextarea($this->inputCalc['diff']['input2']).
				'</textarea>
				'.($this->inputCalc['diff']['input2']?'<p>MD5 hash: <strong>'.md5($this->inputCalc['diff']['input2']).'</strong></p>':'').'
				<br />
				<input type="submit" name="cmd[diff]" value="Make diff" /><br />

				<input type="radio" name="inputCalc[diff][diffmode]" value="0"'.(!$this->inputCalc['diff']['diffmode']?' checked="checked"':'').' /> Classic diff (line by line)<br />
				<input type="radio" name="inputCalc[diff][diffmode]" value="1"'.($this->inputCalc['diff']['diffmode']==1?' checked="checked"':'').' /> Unified output, 3 lines<br />
				<input type="radio" name="inputCalc[diff][diffmode]" value="2"'.($this->inputCalc['diff']['diffmode']==2?' checked="checked"':'').' /> Diff word by word<br />
		';
		if ($this->cmd=='diff' && trim($this->inputCalc['diff']['input1']) && trim($this->inputCalc['diff']['input2']))	{
			if (strcmp($this->inputCalc['diff']['input1'],$this->inputCalc['diff']['input2']))	{
				require_once(PATH_t3lib.'class.t3lib_diff.php');

				$diffEngine = t3lib_div::makeInstance('t3lib_diff');
				switch($this->inputCalc['diff']['diffmode'])	{
					case 1:	// Unified
						$diffEngine->diffOptions = '--unified=3';

						$resultA = $diffEngine->getDiff($this->inputCalc['diff']['input1'],$this->inputCalc['diff']['input2']);
						$result='';
						foreach($resultA as $line)	{
							if (substr($line,0,3)!='---' && substr($line,0,3)!='+++')	{
								switch(substr($line,0,1))	{
									case '+':	// New
										$result.='<span class="diff-g">'.htmlspecialchars($line).'</span>';
									break;
									case '-':	// Old
										$result.='<span class="diff-r">'.htmlspecialchars($line).'</span>';
									break;
									default:
										$result.=htmlspecialchars($line);
									break;
								}
								$result.=chr(10);
							}
						}
					break;
					case 2:	// Word by word
						$result = $diffEngine->makeDiffDisplay($this->inputCalc['diff']['input1'],$this->inputCalc['diff']['input2']);
					break;
					default:
						$resultA = $diffEngine->getDiff($this->inputCalc['diff']['input1'],$this->inputCalc['diff']['input2']);
						$result='';
						foreach($resultA as $line)	{
							switch(substr($line,0,1))	{
								case '>':	// New
									$result.='<span class="diff-g">'.htmlspecialchars($line).'</span>';
								break;
								case '<':	// Old
									$result.='<span class="diff-r">'.htmlspecialchars($line).'</span>';
								break;
								default:
									$result.=htmlspecialchars($line);
								break;
							}
							$result.=chr(10);
						}
					break;
				}
				$content.='
					<hr />
					<pre>'.$result.'</pre>
					<hr />
				';
			} else {
				$content.='
					<p><strong>The two test strings are exactly the same!</strong></p>
				';
			}
		}

		return $content;
	}

	/**
	 * Parsing input SQL with t3lib_sqlengine
	 *
	 * @return	string		SQL content
	 */
	function calc_sql()	{

			// Render input form:
		$content.='
			<h3>Input SQL string:</h3>
				<textarea rows="10" name="inputCalc[sql][input]" wrap="off"'.$GLOBALS['TBE_TEMPLATE']->formWidthText(48,'width:98%;','off').'>'.
				t3lib_div::formatForTextarea($this->inputCalc['sql']['input']).
				'</textarea>
				<input type="submit" name="cmd[sql]" value="Parse SQL" />
		';
		if ($this->cmd=='sql' && trim($this->inputCalc['sql']['input']))	{

				// Start SQL engine:
			require_once(PATH_t3lib.'class.t3lib_sqlparser.php');
			$sqlParser = t3lib_div::makeInstance('t3lib_sqlparser');

				// Parse query:
			$result = $sqlParser->parseSQL($this->inputCalc['sql']['input']);

				// If success (array is returned), recompile/show:
			if (is_array($result))	{

					// TEsting if query can be re-compiled and will match original:
				$recompiledSQL = $sqlParser->compileSQL($result);
				if ($parts = $sqlParser->debug_parseSQLpartCompare($this->inputCalc['sql']['input'],$recompiledSQL))	{
					if ($parts = $sqlParser->debug_parseSQLpartCompare($this->inputCalc['sql']['input'],$recompiledSQL,TRUE))	{
						$content.= '<p><strong>Error:</strong> Re-compiled query did not match original!</p>'.t3lib_div::view_array($parts);
					} else {
						$content.= '<p><strong>CASE Error:</strong> Re-compiled OK insensitive to character case, BUT did not match original without case equalization!</p>'.t3lib_div::view_array($parts);
					}
				} else {
					$content.= '<p><strong>OK: </strong> Re-compiled query OK</p>';
				}
				$content.= '<hr />';

				$content.= t3lib_div::view_array($result);
			} else {
				$content.= '<p>'.$result.'</p>';
			}
		}

		return $content;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extdeveval/mod1/class.tx_extdeveval_calc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extdeveval/mod1/class.tx_extdeveval_calc.php']);
}
?>