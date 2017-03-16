<?php
namespace DIQA\Util;

use Exception;
use Http;
use MWHttpRequest;
use mikehaertl\wkhtmlto\Pdf;
use MediaWiki\Logger\LoggerFactory;

/**
 *
 * @author Michael
 *
 */
class RequestUtils {

    /**
     * Requests a session cookie for a user.
     *
     * @param string $user
     * @param string $pass
     * @throws \Exception
     * @return [ 'cookieprefix' => ... , 'sessionId' => ... ]
     */
    public static function getSessionCookieForExportUser($user, $pass) {
        global $wgServerHTTP, $wgScriptPath, $wgODBTechnicalUser;

        $user = urlencode ( $user );
        $pass = urlencode ( $pass );

        // first: request a token
        $apiPath = "/api.php?action=login&format=json&lgname=$user&lgpassword=$pass";
        $response = Http::post ( $wgServerHTTP . $wgScriptPath . $apiPath, array () );
        
        $responseObj = json_decode ( $response );
        if (isset ( $responseObj->login->result ) && $responseObj->login->result == 'NeedToken') {

            // second: re-send token and request the final sessionId
            $cookieprefix = $responseObj->login->cookieprefix;
            $sessionId = $responseObj->login->sessionid;
            $token = urlencode($responseObj->login->token);
            $response = static::post ( $wgServerHTTP . $wgScriptPath . $apiPath . "&lgtoken=$token", array (), array (
                $cookieprefix . '_session' => $sessionId
            ) );
            
            $responseObj = json_decode ( $response );

            // check if login was successful
            if (isset ( $responseObj->login->result ) && $responseObj->login->result != 'Success') {
                throw new Exception ( "Could not login user $wgODBTechnicalUser" );
            }
            $cookieprefix = $responseObj->login->cookieprefix;
            $sessionId = $responseObj->login->sessionid;
            $lguserid = $responseObj->login->lguserid;
            $lgusername = $responseObj->login->lgusername;
            
            return [
                'cookieprefix' => $cookieprefix,
                'sessionId' => $sessionId,
                'lguserid' => $lguserid,
                'lgusername' => $lgusername
            ];
        }
        throw new Exception( "Could not login user '$user'." );
    }

    /**
     * Utility method. Extends Http::post by cookies.
     *
     * @param string $url
     * @param array $options
     * @param array $cookies
     * @return Ambigous <boolean, string, String>
     */
    public static function post($url, $options = array(), $cookies = array()) {
        global $wgServerHTTP;

        $domain = str_replace ( "http://", "", $wgServerHTTP );

        $method = 'POST';

        $options ['method'] = strtoupper ( $method );

        if (! isset ( $options ['timeout'] )) {
            $options ['timeout'] = 'default';
        }
        if (! isset ( $options ['connectTimeout'] )) {
            $options ['connectTimeout'] = 'default';
        }

        $req = MWHttpRequest::factory ( $url, $options );
        foreach ( $cookies as $key => $val ) {
            $req->setCookie ( $key, $val, array (
                'domain' => $domain
            ) );
        }
        
        $status = $req->execute ();

        $content = false;
        if ($status->isOK ()) {
            $content = $req->getContent ();
        } else {
            $errors = $status->getErrorsByType ( 'error' );
            $logger = LoggerFactory::getInstance ( 'http' );
            $logger->warning ( $status->getWikiText (), array() );
        }

        return $content;
    }

    /**
     * turns the page identified by the request to PDF and stores it in a temporary file
     * returns the filename of the resulting PDF document
     *
     * @param String $url
     * @param boolean Set HTTP header fields if true
     * @return String pointing to the filename of the created PDF document
     * @throws Exception
     */
    public static function exportPDFAction($url, $setHeader = true) {
		global $wgServer;
        global $wgServerHTTP;

        global $wgODBTechnicalUser;
        global $wgODBTechnicalUserPassword;

        $sessionCookie = RequestUtils::getSessionCookieForExportUser( $wgODBTechnicalUser, $wgODBTechnicalUserPassword );

        if ($setHeader) {
	        header ( "Content-type: application/pdf" );
	        header ( sprintf ( "Content-Disposition: attachment; filename=\"odbwiki_export_%s.pdf\"", date ( "Ymd_His" ) ) );
	        header ( "Pragma: no-cache" );
	        header ( "Expires: 0" );
        }

        $fullUrl = str_replace($wgServer, $wgServerHTTP, $url);
        return  PDFUtils::createPDF($fullUrl, $sessionCookie);
    }

}
