<?php
if (!defined('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE') {
		// register our toolbar item
	$GLOBALS['TYPO3backend']->addToolbarItem('Developer Links', 'tx_extdeveval_additionalBackendItems');
}