<?php

/**
 * The main file of the DIQA Util extension
 *
 * @file
 * @ingroup DIQA
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of a MediaWiki extension, it is not a valid entry point.' );
}

define( 'DIQA_UTIL_VERSION', '0.1' );

global $wgVersion;
global $wgExtensionCredits;
global $wgExtensionMessagesFiles;
global $wgHooks;
global $wgResourceModules;
global $wgExtensionFunctions;

// register extension
$wgExtensionCredits[ 'diqa' ][] = array(
	'path' => __FILE__,
	'name' => 'Util',
	'author' => array( 'DIQA Projektmanagement GmbH' ),
	'license-name' => 'GPL-2.0+',
	'url' => 'http://www.diqa-pm.com',
	'descriptionmsg' => 'diqa-util-desc',
	'version' => DIQA_UTIL_VERSION,
);

$dir = dirname( __FILE__ );

$wgExtensionMessagesFiles['DIQAutil'] = $dir . '/DIQAutil.i18n.php';
