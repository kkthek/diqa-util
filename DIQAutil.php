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
global $wgAPIModules;

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
$wgHooks['ParserFirstCallInit'][] = 'DIQA\Util\ParserFunctions\StripTags::registerParserHooks';
$wgHooks['ParserFirstCallInit'][] = 'DIQA\Util\ParserFunctions\LatestRevisionComment::registerParserHooks';
$wgHooks['ParserFirstCallInit'][] = 'DIQA\Util\ParserFunctions\IsUserMemberOfGroup::registerParserHooks';
$wgHooks['ParserFirstCallInit'][] = 'DIQA\Util\ParserFunctions\ReadObjectCache::registerParserHooks';
$wgHooks['ParserFirstCallInit'][] = 'wfDIQAUtilRegisterModules';

//Change the image links
$wgHooks['LinkBegin'][] = 'DIQA\Util\FileLinkUtils::ImageOverlayLinkBegin';
$wgAPIModules['diqa_util_userdataapi'] = 'DIQA\Util\Api\UserDataAPI';

$wgExtensionFunctions[] = function() {
	
	global $wgResourceModules;
	
	$wgResourceModules['ext.diqa.util'] = array(
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'Util',
			'scripts' => array(
					'scripts/util.js'
			),
			'styles' => 'skins/util.css',
			'dependencies' => array()
	);
};

function wfDIQAUtilRegisterModules() {
	global $wgOut;
	
	$wgOut->addModules('ext.diqa.util');
}

/**
 * Makes a call to an endpoint providing wiki session cookies and PHPSESSID.
 * 
 * @param $proxyUrl which is responsible for ending the session. (usually a proxy)
 * @return
 */
function wfDIQAUtilLogout($proxyUrl) {
	global $wgServerHTTP, $wgScriptPath, $wgDBname;

	$http = function ($url, $cookies) {
		$res = "";
		$header = "";

		// Create a curl handle to a non-existing location
		$ch = curl_init($url);

		$cookieArray = [];
		foreach($cookies as $key => $value) {
			$cookieArray[] = "$key=$value";
		}


		// Execute
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookieArray));
		$res = curl_exec($ch);

		$status = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		curl_close($ch);

		$bodyBegin = strpos($res, "\r\n\r\n");
		list($header, $res) = $bodyBegin !== false ? array(substr($res, 0, $bodyBegin), substr($res, $bodyBegin+4)) : array($res, "");
		return array($header, $status, str_replace("%0A%0D%0A%0D", "\r\n\r\n", $res));
	};

	$userid = isset($_COOKIE[$wgDBname.'UserID']) ? $_COOKIE[$wgDBname.'UserID'] : '';
	$userName = isset($_COOKIE[$wgDBname.'UserName']) ? $_COOKIE[$wgDBname.'UserName'] : '';
	$sessionId = isset($_COOKIE[$wgDBname.'_session']) ? $_COOKIE[$wgDBname.'_session'] : '';

	$PHPSESSID = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '';

	$cookies = [
	$wgDBname.'UserID' => $userid,
	$wgDBname.'UserName' => $userName,
	$wgDBname.'_session' => $sessionId,
	'PHPSESSID' => $PHPSESSID,
	];

	$res = $http($wgServerHTTP . $wgScriptPath . $proxyUrl, $cookies);
};
