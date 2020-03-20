<?php
namespace DIQA\Util;
use Title;
use HTML; 
/**
 * @author Hansch
 *
 */
class FileLinkUtils {
    
    /**
     * Rewrite links to images in order to open them in an overlay
     *
     */
    public function __construct() { 
    }
    
    /**
     * Rewrites links to images in order to open them into a overlay
     * @param unknown $dummy
     * @param \Title $target
     * @param unknown $text
     * @param unknown $customAttribs
     * @param unknown $query
     * @param unknown $options
     * @param unknown $ret
     * @return boolean
     */    
    public static function ImageOverlayLinkBegin($dummy, \Title $target, &$text, &$customAttribs, &$query, &$options, &$ret) {
        global $wgDIQAImageOverlayWhitelist;
        global $wgDIQADownloadWhitelist;
           
        if (!self::isImage($target->mNamespace)) {
            // don't rewrite links not pointing to images
            return true; 
        }
        
        $file = wfFindFile($target);
        if ( !$file ) { 
            // don't rewrite links to non-existing files
            return true; 
        }
        
        $ext = $file->getExtension();
        if ( isset($wgDIQAImageOverlayWhitelist) && in_array( $ext, $wgDIQAImageOverlayWhitelist )) { 
            // open in overlay
            $ret = Html::rawElement ( 
                'a', 
                array ( 'href' => $file->getFullURL(), 'class' => 'imageOverlay' ), 
                self::getDisplayTitle($target) );
            return false;
        }
        
        if ( isset($wgDIQADownloadWhitelist) && in_array( $ext, $wgDIQADownloadWhitelist )) { 
            // download
            $ret = Html::rawElement ( 
                'a', 
                array ( 'href' => $file->getFullURL() ), 
                self::getDisplayTitle($target) );
            return false;
        }
        
        // don't rewrite links to files with inappropriate extensions
        return true; 
    }

    /**
     * adapted from DisplayTitleHook:
     * 
     * Get displaytitle page property text.
     *
     * @param Title $title the Title object for the page
     * @return string &$displaytitle to return the display title, if set
     */
    private static function getDisplayTitle( Title $title ) {
        $pagetitle = $title->getPrefixedText();
        // remove fragment
        $title = Title::newFromText( $pagetitle );
        
        $values = \PageProps::getInstance()->getProperties( $title, 'displaytitle' );
        $id = $title->getArticleID();
        if ( array_key_exists( $id, $values ) ) {
            $value = $values[$id];
            if ( trim( str_replace( '&#160;', '', strip_tags( $value ) ) ) !== '' ) {
                return $value;
            }
        }
        
        // no display title found
        return $title->getPrefixedText();
    }
    
    private static function isImage( $namespace ) {
        return ( $namespace == NS_FILE );
    }    
}