<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Contains a class, tx_extdeveval_phpdoc, which can parse JavaDoc comments in PHP scripts, insert new, create a data-file for a display-plugin that exists as well.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_extdeveval_phpdoc 
 *   70:     function analyseFile($filepath,$extDir)	
 *  258:     function updateDat($extDir,$extPhpFiles,$passOn_extDir)	
 *  357:     function generateComment($cDat,$commentLinesWhiteSpacePrefix,$isClass)	
 *  420:     function tryToMakeParamTagsFromFunctionDefLine($v)	
 *  444:     function parseFunctionComment($content,$arr)	
 *  496:     function getWhiteSpacePrefix($string)	
 *  509:     function isHeaderClass($string)	
 *  522:     function splitHeader($inStr)	
 *  591:     function includeContent($content, $class)	
 *  612:     function getSectionDivisionComment($string)	
 *
 * TOTAL FUNCTIONS: 10
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 

/**
 * Class for the PHP-doc functions.
 * 
 */
class tx_extdeveval_phpdoc {
	var $fileInfo=array();
	var $includeContent=500;
	var $sectionTextCounter=0;
	var $classCounter=0;
	var $colorCount=array();

	/**
	 * The main function in the class
	 * 
	 * @param	string		The absolute path to an existing PHP file which should be analysed
	 * @param	string		The local/global/system extension main directory relative to PATH_site - normally set to "typo3conf/ext/" for local extensions
	 * @return	string		HTML content from the function
	 */
	function analyseFile($filepath,$extDir)	{
			// Getting the content from the phpfile.
		$content = t3lib_div::getUrl($filepath);
		$hash_current = md5($content);

			// Splitting the file based on a regex:
			// NOTICE: "\{" (escaping a curly brace) should NOT be done when it is in [] - thus below it should be "[^{]" and not "[^\{]" - the last will also find backslash characters in addition to curly braces. But curly braces outside of [] seems to need this.
		$splitRegEx = chr(10).'['.chr(13).chr(9).chr(32).']*('.
				'function[[:space:]]+[[:alnum:]_]+[[:space:]]*\([^{]*'.	// Finding functions...
				'|'.
				'class[[:space:]]+[[:alnum:]_]+[^{]*'.			// Finding classes.
				')\{['.chr(13).chr(9).chr(32).']*'.chr(10);
		$parts = split($splitRegEx,$content);
		
			// Traversing the splitted array and putting the pieces into a new array, $fileParts, where the cut-out part is also added.
		$fileParts=array();
		$lenCount=0;
		foreach($parts as $k => $v)	{
			if ($k)	{
					// Find the part that the regex matched (which is NOT in the parts array):
				$reg = '';
				ereg('^'.$splitRegEx,substr($content,$lenCount),$reg);
				$fileParts[]=$reg[0];
				$lenCount+=strlen($reg[0]);
			}
				// ... Then add the value from the parts-array:
			$fileParts[]=$v;
			$lenCount+=strlen($v);
		}
#debug($fileParts);

			// Finally, if the processing into the $fileParts array was successful the imploded version of this array will match the input $content. So we do this integrity check here:
		if (md5(implode('',$fileParts)) == md5($content))	{
			// Traversing the array, trying to find
			$visualParts=array();
			$currentClass='';
			$this->sectionTextCounter=0;
			$this->classCounter=0;
			foreach($fileParts as $k => $v)		{
				$visualParts[$k]=htmlspecialchars($v);
				
				if ($k%2)	{
					$this->fileInfo[$k]['header']=trim($v);
					$isClassName = $this->isHeaderClass($v);
					if ($isClassName)	{
						$this->fileInfo[$k]['class']=1;
						$this->classCounter++;
						$currentClass=$isClassName;
					}
					$this->fileInfo[$k]['parentClass']=$currentClass;
					
						// Try to locate existing comment:
					$SET=0;
					$cDat=array();
					$comment = t3lib_div::revExplode('**/',$fileParts[$k-1],2);
					if (trim($comment[1]) && ereg('\*\/$',trim($comment[1])))	{
						$SET=1;
						// There was a comment! Now, parse it.

						if ($k>1)	{
							$sectionText = $this->getSectionDivisionComment($comment[0]);
							if (is_array($sectionText))	{
								$this->sectionTextCounter++;
								$this->fileInfo[$k]['sectionText']=$sectionText;
							}
						}
						
						$blankCDat = $this->tryToMakeParamTagsFromFunctionDefLine($v);
						$cDat = $this->parseFunctionComment($comment[1],$blankCDat);
						$this->fileInfo[$k]['cDat']=$cDat;
					} else {
						$comment = t3lib_div::revExplode('}',$fileParts[$k-1],2);
						
						if (isset($comment[1]) && !trim($comment[1]))	{
							$SET=2;
							$comment[0].='}'.chr(10).chr(10).'	';
						} else {
							$comment = t3lib_div::revExplode('{',$fileParts[$k-1],2);						
							if (isset($comment[1]) && !trim($comment[1]))	{
								$SET=2;
								$comment[0].='{'.chr(10).chr(10).'	';
							}
						}
						
						if ($SET==2)	{
							$cDat['text']='[Describe function...]';
							$cDat['param'] = $this->tryToMakeParamTagsFromFunctionDefLine($v);
							$cDat['return'] = array('[type]','...');
						}
					}
					
					if (!isset($fileParts[$k+2]))	{	// ... if this is last item!
						$this->fileInfo[$k]['content'] = $this->includeContent($fileParts[$k+1], $this->fileInfo[$k]['class']);
						$this->fileInfo[$k]['content_size']=strlen($fileParts[$k+1]);
						$this->fileInfo[$k]['content_lines']=substr_count($fileParts[$k+1],chr(10));
					} elseif (isset($this->fileInfo[$k-2]))	{	// ... otherwise operate on the FORMER item!
						$this->fileInfo[$k-2]['content'] = $this->includeContent($comment[0], $this->fileInfo[$k-2]['class']);
						$this->fileInfo[$k-2]['content_size']=strlen($comment[0]);
						$this->fileInfo[$k-2]['content_lines']=substr_count($comment[0],chr(10));
					}

					if ($SET)	{
						$commentLinesWhiteSpacePrefix = $this->getWhiteSpacePrefix($comment[0]);
						$comment[1]=$this->generateComment($cDat,$commentLinesWhiteSpacePrefix,$this->isHeaderClass($v));
					
						$origPart = $fileParts[$k-1];
						$fileParts[$k-1]=implode('',$comment);
						
							// If there was a change, then make a markup of the visual output:
						$vComment = $comment;
						$vComment[0]=htmlspecialchars($vComment[0]);
						if ($k>1)	{
							if (strlen($vComment[0])>1000)	{
								$vComment[0] = substr($vComment[0],0,450).chr(10).'<span style="color:green; font-weight:bold;">[...]</span>'.chr(10).substr($vComment[0],-500);
							}
						}
						
						$color = ($origPart==$fileParts[$k-1] ? 'black' :($SET==1?'navy':'red'));
						$this->colorCount[$color]++;

						$vComment[1]='<span style="color:'.$color.'; font-weight:bold;">'.htmlspecialchars($vComment[1]).'</span>';
						$visualParts[$k-1]=implode('',$vComment);
					}
				}
			}
			
				// Count lines:
			$lines=0;
			foreach($fileParts as $k => $v)		{
				if ($k%2)	{
					$this->fileInfo[$k]['atLine']=$lines;
				}
				$lines+=substr_count($fileParts[$k],chr(10));
			}
#debug($fileParts);
#debug($this->fileInfo);
			$fileParts[0] = $this->splitHeader($fileParts[0]);
			$visualParts[0] = '<span style="color:#663300;">'.htmlspecialchars($fileParts[0]).'</span>';
			

			$output='';
			$output.='<b>Color count:</b><br>"red"=new comments<br>"navy"=existing, modified<br>"black"=existing, not modified'.t3lib_div::view_array($this->colorCount);
			
				// Output the file
			if (t3lib_div::GPvar('_save_script'))	{
				if (@is_file($filepath) && t3lib_div::isFirstPartOfStr($filepath,PATH_site.$extDir))	{
					$output.='<b>SAVED TO: '.substr($filepath,strlen(PATH_site)).'</b>';
					t3lib_div::writeFile($filepath,implode('',$fileParts));
				} else {
					$output.='<b>NO FILE/NO PERMISSION!!!: '.substr($filepath,strlen(PATH_site)).'</b>';
				}
				$output.='<hr>';
				$output.='<input type="submit" name="_" value="RETURN">';
			} else {
				$hash_new = md5(implode('',$fileParts));
				$output.='
				'.$hash_current.' - Current file HASH<br>
				'.$hash_new.' - New file HASH<br>
				(If the hash strings are similar you don\'t need to save since nothing would be changed)<br>
				';
				

				$output.='
				<b><br>This is the substititions that will be carried out if you press the "Save" button in the bottom of this page:</b><hr>';
				$output.='<input type="submit" name="_save_script" value="SAVE!">';
				$output.= '<pre style="font-size:11px; font-family: monospace;">'.str_replace(chr(9),'&nbsp;&nbsp;&nbsp;',implode('',$visualParts)).'</pre>';

				$output.='<hr>';
				$output.='<input type="submit" name="_save_script" value="SAVE!">';
				$output.='<br><br><b>Instructions:</b><br>';
				$output.='0) Make a backup of the script - what if something goes wrong? Are you prepared?<br>';
				$output.='1) Press the button if you are OK with the changes. RED comments are totally new - BLUE comments are existing comments but parsed/reformatted.<br>';
			}
	

#debug($this->fileInfo);

			return $output;
		} else return 'ERROR: There was an internal error in process of splitting the PHP-script.';
	}

	/**
	 * Creates an interface where there user can select which "class." files to include in the ext_php_api.dat file which the function can create/update by a single click.
	 * 
	 * @param	string		$extDir: Extension Directory, absolute path
	 * @param	array		$extPhpFiles: Array with PHP files (rel. paths) from the extension directory
	 * @param	string		The local/global/system extension main directory relative to PATH_site - normally set to "typo3conf/ext/" for local extensions. Used to pass on to analyseFile()
	 * @return	string		HTML output
	 */
	function updateDat($extDir,$extPhpFiles,$passOn_extDir)	{
		if (is_array($extPhpFiles))	{
				// Find current dat file:
			$datArray='';
			if (@is_file($extDir.'ext_php_api.dat'))	{
				$datArray = unserialize(t3lib_div::getUrl($extDir.'ext_php_api.dat'));
				if (!is_array($datArray))	 
					$content.='<br><br><p><strong>ERROR:</strong> "ext_php_api.dat" file did not contain a valid serialized array!</p>';
			} else $content='<br><br><p><strong>INFO:</strong> No "ext_php_api.dat" file found.</p>';
			
				// Show files:
			$newDatArray=array();
			$newDatArray['meta']['title']=$datArray['meta']['title'];
			$newDatArray['meta']['descr']=$datArray['meta']['descr'];
			$inCheck=t3lib_div::GPvar('selectThisFile',1);	
			
			$lines=array();
			foreach ($extPhpFiles as $lFile)	{
					// disable check for "class." by "1"
				if (1 || t3lib_div::isFirstPartOfStr(basename($lFile),'class.'))	{	
						// Get API information about class-file:
					$newAnalyser = t3lib_div::makeInstance('tx_extdeveval_phpdoc');
					$newAnalyser->analyseFile($extDir.$lFile,$passOn_extDir);
					if ((!is_array($inCheck) && isset($datArray['files'][$lFile])) || (is_array($inCheck) && in_array($lFile,$inCheck)))	$newDatArray['files'][$lFile]=array(
						'filesize'=>filesize($extDir.$lFile),
						'header'=>$newAnalyser->headerInfo,
						'DAT' => $newAnalyser->fileInfo
					);
					
						// Format that information:
					$clines=array();
					$cc=0;
					foreach($newAnalyser->fileInfo as $part)	{
						if (is_array($part['sectionText']) && count($part['sectionText']))	{
							$clines[]='';
							$clines[]='      SECTION: '.$part['sectionText'][0];
						}
						
						if ($part['class'])	{
							$clines[]='';
							$clines[]='';
						}

						$line=$part['parentClass'] && !$part['class']?'    ':'';
						$line.=ereg_replace('\{$','',trim($part['header']));
						$clines[]=$line;
					}
				
						// Make HTML table row:
					$lines[]='<tr>
						<td><input type="checkbox" name="selectThisFile[]" value="'.htmlspecialchars($lFile).'"'.(isset($datArray['files'][$lFile])?' checked="checked"':'').'></td>
						<td nowrap valign="top">'.htmlspecialchars($lFile).'</td>
						<td nowrap valign="top">'.t3lib_div::formatSize(filesize($extDir.$lFile)).'</td>
						<td nowrap>'.nl2br(str_replace(' ','&nbsp;',htmlspecialchars(implode(chr(10),$clines)))).'</td>
					</tr>';
				}
			}
			$content.='
			<br><br><p><strong>PHP/INC files where the filename starts with "class.":</strong></p>
			<table border="1" cellspacing="0" cellpadding="0">'.
						implode('',$lines).
						'</table>';
			$content.='
				Package Title:<br>
				<input type="text" name="title_of_collection" value="'.htmlspecialchars($datArray['meta']['title']).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth().'><br>
				Package Description:<br>
				<textarea name="descr_of_collection"'.$GLOBALS['TBE_TEMPLATE']->formWidthText().' rows="5">'.t3lib_div::formatForTextarea($datArray['meta']['descr']).'</textarea><br>
				<input type="submit" value="'.htmlspecialchars('Write/Update "ext_php_api.dat" file').'" name="WRITE">
			';

			$content.='<p>'.md5(serialize($datArray)).' MD5 - from current ext_php_api.dat file</p>';
			$content.='<p>'.md5(serialize($newDatArray)).' MD5 - new, from the selected files</p>';
#debug(strlen(serialize($datArray)));
#debug(strlen(serialize($newDatArray)));
			if (t3lib_div::GPvar('WRITE'))	{
				$newDatArray['meta']['title']=t3lib_div::GPvar('title_of_collection');
				$newDatArray['meta']['descr']=t3lib_div::GPvar('descr_of_collection');
				t3lib_div::writeFile($extDir.'ext_php_api.dat',serialize($newDatArray));

				$content='<hr>';
				$content.='<p><strong>ext_php_api.dat file written to extension directory, "'.$extDir.'"</strong></p>';
				$content.='
					<input type="submit" value="Return..." name="_">
				';
			}
						
		} else $content='<p>No PHP/INC files found extension directory.</p>';
		
		return $content;
	}

	/**
	 * Converts a "cDat" array into a JavaDoc comment
	 * 
	 * @param	array		$cDat: This array contains keys/values which will be turned into a JavaDoc comment (see comment inside this function for the "syntax")
	 * @param	string		$commentLinesWhiteSpacePrefix: Prefix for the lines in the comment starting with " * " (normally a tab or blank string)
	 * @param	boolean		$isClass: Tells whether the comment is for a class.
	 * @return	string		The JavaDoc comment, lines are indented with one tab (except first)
	 */
	function generateComment($cDat,$commentLinesWhiteSpacePrefix,$isClass)	{
		/*	SYNTAX of cDat array:
		
			$cDat['text'] = '
			Lines of text
			
			More lines here.
			';
			
			$cDat['return']=array('string','Description value');
			$cDat['param'][]=array('string','Description value, param 1');
			$cDat['param'][]=array('string','Description value, param 2');
			$cDat['param'][]=array('string','Description value, param 3');
			$cDat['other'][]='@sometag	Another tag string...';
			$cDat['internal']=1;	// boolean
			$cDat['ignore']=1;	// boolean
		*/

		$commentLines=array();

		$commentText = trim($cDat['text']);
		if ($commentText)	{
			$textA = explode(chr(10),$commentText);
			foreach($textA as $v)	{
				$commentLines[]=$commentLinesWhiteSpacePrefix.' * '.$v;
			}
			$commentLines[]=$commentLinesWhiteSpacePrefix.' * ';
		}
		
		if (is_array($cDat['param']))	{
			foreach($cDat['param'] as $v)	{
				$commentLines[]=$commentLinesWhiteSpacePrefix.' * @param	'.$v[0].'		'.$v[1];
			}
		}
		
		if (!$isClass && is_array($cDat['return']))	{
			$commentLines[]=$commentLinesWhiteSpacePrefix.' * @return	'.$cDat['return'][0].'		'.$cDat['return'][1];
		}
		
		if ($cDat['ignore'])	{
			$commentLines[]=$commentLinesWhiteSpacePrefix.' * @ignore';
		}
		if ($cDat['access'])	{
			$commentLines[]=$commentLinesWhiteSpacePrefix.' * @access '.$cDat['access'];
		}
		
		if (is_array($cDat['other']))	{
			foreach($cDat['other'] as $v)	{
				$commentLines[]=$commentLinesWhiteSpacePrefix.' * '.$v;
			}
		}
		
		return '/**
'.implode(chr(10),$commentLines).'
'.$commentLinesWhiteSpacePrefix.' */';
	}

	/**
	 * Creates an array of param-tag parts (designed for a cDat array) from a string containing a PHP-function header
	 * 
	 * @param	string		String with PHP-function header in, eg. '   function blablabla($this, $that="22")	{		'
	 * @return	array		The function arguments (here: $this, $that) in an array
	 */
	function tryToMakeParamTagsFromFunctionDefLine($v)	{
		$reg='';
		ereg('^[^\(]*\((.*)\)[^\)]*$',$v,$reg);
		
		$paramA=array();
		if (trim($reg[1]))	{
			$parts = split(',[[:space:]]*[\$&]',$reg[1]);
#	debug($parts);
			foreach($parts as $vv)	{
				$varName='';
				list($varName) = t3lib_div::trimExplode('=',ereg_replace('^[\$&]','',$vv),1);
				$paramA[]=array('[type]','$'.$varName.': ...');
			}
		}
		return $paramA;
	}

	/**
	 * Parses a JavaDoc comment into a cDat array with contents for the comment.
	 * 
	 * @param	string		$content: The JavaDoc comment to parse (without initial "[slash]**")
	 * @param	array		Default array of parameters.
	 * @return	array		"cDat" array of the parsed JavaDoc comment.
	 */
	function parseFunctionComment($content,$arr)	{
		$pC=0;
		$outArr = array();
		$outArr['text']='';
		$outArr['param']=is_array($arr)?$arr:array();
		$outArr['return']=array('[type]','...');

		$linesInComment = explode(chr(10),$content);
		foreach($linesInComment as $v)	{
			$lineParts = explode('*',$v,2);
			if (count($lineParts)==2 && !trim($lineParts[0]))	{
				$lineContent = trim($lineParts[1]);
				if ($lineContent!='/')	{
					if (substr($lineContent,0,1)=='@')	{
						$lP = split('[[:space:]]+',$lineContent,3);
						switch ($lP[0])	{
							case '@param':
								$outArr['param'][$pC]=array(trim($lP[1]),trim($lP[2]));
								$pC++;
							break;
							case '@ignore':
								$outArr['ignore']=1;
							break;
							case '@access':
								$outArr['access']=$lP[1];
							break;
							case '@return':
								$outArr['return']=array(trim($lP[1]),trim($lP[2]));
							break;
							default:
								$outArr['other'][]=trim($lineContent);
								$outArr['other_index'][$lP[0]][]=trim($lP[1]).' '.trim($lP[2]);
							break;
						}
					} else {
						$outArr['text'].=chr(10).ereg_replace('^[ ]','',$lineParts[1]);
					}
				}
			} else {
				$outArr['text'].=chr(10).$v;
			}
		}
		return $outArr;
	}

	/**
	 * Returns the whitespace before the [slash]** comment.
	 * 
	 * @param	string		$string:
	 * @return	string		The prefix string
	 * @access private
	 */
	function getWhiteSpacePrefix($string)	{
		$reg=array();
		ereg(chr(10).'([^'.chr(10).'])$',$string,$reg);
		return $reg[1];
	}

	/**
	 * Returns the class name if the input string is a class header.
	 * 
	 * @param	string		$string:
	 * @return	string		If a class header, then return class name
	 * @access private
	 */
	function isHeaderClass($string)	{
		$reg = '';
		ereg('class[[:space:]]+([[:alnum:]_]+)[^{]*',trim($string),$reg);
		return $reg[1];
	}

	/**
	 * Processes the script-header (with comments like license, author, class/function index)
	 * 
	 * @param	string		$inStr: The header part.
	 * @return	string		Processed output
	 * @access private
	 */
	function splitHeader($inStr)	{
		$splitStr = md5(microtime());
		$string = $inStr;
		$string = ereg_replace('('.chr(10).'[[:space:]]*)(\/\*\*)','\1'.$splitStr.'\2',$string);
		$string = ereg_replace('(\*\/)([[:space:]]*'.chr(10).')','\1'.$splitStr.'\2',$string);
		
		$comments = explode($splitStr,$string);
		$funcCounter=0;

		if (md5($inStr)==md5(implode('',$comments)))	{
			foreach($comments as $k => $v)	{
				if (substr($v,0,3)=='/**' && substr($v,-2)=='*/')	{	// Checking that the content is solely a comment.
					$cDat = $this->parseFunctionComment(substr($v,3),array());
					if (is_array($cDat['other_index']['@author']))	{
						$this->headerInfo=$cDat;
					}
					if (t3lib_div::isFirstPartOfStr(trim($cDat['text']),'[CLASS/FUNCTION INDEX of SCRIPT]'))	{
#						debug($cDat);
#debug($this->fileInfo);
						$lines=array();
						$cc = count($this->fileInfo)+5-substr_count($comments[$k],chr(10))+($this->sectionTextCounter*2)+($this->classCounter*2)+4;
						foreach($this->fileInfo as $part)	{
							if (is_array($part['sectionText']) && count($part['sectionText']))	{
								$lines[]=' *';
								$lines[]=' *              SECTION: '.$part['sectionText'][0];
							}

							if ($part['class'])	{
								$lines[]=' *';
								$lines[]=' *';
							} else {
								$funcCounter++;
							}
							$line=$part['parentClass'] && !$part['class']?'    ':'';
							$line.=ereg_replace('\{$','',trim($part['header']));
							$line= str_pad($part['atLine']+$cc, 4, ' ', STR_PAD_LEFT).': '.$line;
							$lines[]=' * '.$line;
						}
#debug($lines);

						$comments[$k]=trim('
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
'.implode(chr(10),$lines).'
 *
 * TOTAL FUNCTIONS: '.$funcCounter.'
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */						
						
						');
					}
				}
			}
			$inStr=implode('',$comments);
		} else debug('MD5 error:');
#debug(array($inStr));
		return $inStr;
	}

	/**
	 * Returns content to include in the ->fileInfo array (for API documentation)
	 * 
	 * @param	string		$content: The function content.
	 * @param	boolean		$class: If class start
	 * @return	string		Processed content.
	 * @access private
	 */
	function includeContent($content, $class)	{
		if ($class)	return array($content,-1);
		
		if ($this->includeContent>0)	{
			if (strlen($content) > $this->includeContent+100)	{
				return array(substr($content,0,$this->includeContent*3/4).
					chr(10).
					'[...]'.
					chr(10).
					substr($content,-($this->includeContent*1/4)),1);
			} else return array($content,0);
		}
	}

	/**
	 * Tries to get the division comment above the function
	 * 
	 * @param	string		$string: Content to test
	 * @return	mixed		Returns array with comment text lines if found.
	 * @access private
	 */
	function getSectionDivisionComment($string)	{
		$comment = t3lib_div::revExplode('**/',$string,2);
		if (trim($comment[1]) && ereg('\*\/$',trim($comment[1])))	{
			$outLines=array();
			$cDat = $this->parseFunctionComment($comment[1],array());
			$textLines = t3lib_div::trimExplode(chr(10),$cDat['text'],1);
			foreach($textLines as $v)	{
				if (substr($v,0,1)!='*')	$outLines[]=$v;
			}
			return $outLines;
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extdeveval/mod1/class.tx_extdeveval_phpdoc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extdeveval/mod1/class.tx_extdeveval_phpdoc.php']);
}
?>
