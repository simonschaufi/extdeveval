<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Steffen Ritter <info@steffen-ritter.net>
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
 * Generates sprites for t3skin and extensions
 *
 * @package TYPO3
 * @subpackage tx_extdeveval
 * @author	Steffen Ritter <info@steffen-ritter.net>
 */
class tx_extdeveval_sprites {
	/**
	 * The main function in the class
	 *
	 * @return	string		HTML content
	 */
	public function createSpritesForT3Skin() {
		if (t3lib_div::_POST('generate') == 'GENERATE') {
			/** @var $generator t3lib_SpriteManager_SpriteGenerator */
			$generator = t3lib_div::makeInstance('t3lib_SpriteManager_SpriteGenerator', 't3skin');

			$this->unlinkT3SkinFiles();

			$data = $generator
				->setSpriteFolder(TYPO3_mainDir . 'sysext/t3skin/images/sprites/')
				->setCSSFolder(TYPO3_mainDir . 'sysext/t3skin/stylesheets/sprites/')
				->setOmmitSpriteNameInIconName(TRUE)
				->setIncludeTimestampInCSS(TRUE)
				->generateSpriteFromFolder(array(TYPO3_mainDir . 'sysext/t3skin/images/icons/'));

			$gifSpritesPath = PATH_typo3 . 'sysext/t3skin/stylesheets/ie6/z_t3-icons-gifSprites.css';
			if (FALSE === rename($data['cssGif'], $gifSpritesPath)) {
				throw new tx_extdeveval_exception('The file "' . $data['cssGif'] . '" could not be renamed to "' . $gifSpritesPath . '"');
			}

			$stddbPath = PATH_site . 't3lib/stddb/tables.php';
			$stddbContents = file_get_contents($stddbPath);
			$newContent = '$GLOBALS[\'TBE_STYLES\'][\'spriteIconApi\'][\'coreSpriteImageNames\'] = array(' . LF . TAB . '\''
				. implode('\',' . LF . TAB . '\'', $data['iconNames']) . '\'' . LF . ');' . LF;
			$stddbContents = preg_replace('/\$GLOBALS\[\'TBE_STYLES\'\]\[\'spriteIconApi\'\]\[\'coreSpriteImageNames\'\] = array\([\s\',\w-]*\);/' , $newContent, $stddbContents);

			if (FALSE === t3lib_div::writeFile($stddbPath, $stddbContents)) {
				throw new tx_extdeveval_exception('Could not write file "' . $stddbPath . '"');
			}

			$output = 'Sprites successfully regenerated';
		} else {
			$output = '<input type="submit" name="generate" value="GENERATE" /><br/>';
		}

		return $output;
	}

	/**
	 * Unlinks old T3Skin files.
	 *
	 * @return void
	 */
	protected function unlinkT3SkinFiles() {
		$files = array(
			'stylesheets/ie6/z_t3-icons-gifSprites.css',
			'stylesheets/sprites/t3skin.css',
			'images/sprites/t3skin.png',
			'images/sprites/t3skin.gif',
		);

		foreach ($files as $file) {
			$filePath = PATH_typo3 . 'sysext/t3skin/' . $file;
			if (FALSE === unlink($filePath)) {
				throw new tx_extdeveval_exception('The file "' . $filePath . '" could not be removed');
			}
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extdeveval/mod1/class.tx_extdeveval_sprites.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extdeveval/mod1/class.tx_extdeveval_sprites.php']);
}
?>