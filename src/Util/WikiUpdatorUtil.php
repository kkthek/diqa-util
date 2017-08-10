<?php
namespace DIQA\Util;

use WikiPage;
use Revision;
use ContentHandler;

class WikiUpdatorUtil {
	
	/**
	 * Updates/creates wiki page by appending $append
	 * 
	 * @param Title $title        	
	 * @param string $FileContent
	 * @throws Exception         	
	 */
	public static function createOrAppendsToTitle($title, $append) {
		
		$oWikiPage = new WikiPage ( $title );
		if ($oWikiPage->exists ()) {
			$Revision = Revision::newFromTitle ( $title );
			$WikiPageContent = $Revision->getContent ( Revision::RAW )->serialize (); // or: $WikiMarkup = WikiPage::getContent(...)->serialize();
			$oContent = ContentHandler::makeContent ( $WikiPageContent . "\n$append", $title );
			
			$Result = $oWikiPage->doEditContent ( $oContent, "auto-inserted by runStatistics", EDIT_UPDATE );
			if ($Result->ok) {
				return;
			} 
			throw new \Exception("Error saving: ".$title->getPrefixedText());
		} else {
			$oContent = ContentHandler::makeContent ( $append, $title );
			$Result = $oWikiPage->doEditContent ( $oContent, "auto-inserted by runStatistics", EDIT_NEW );
			if ($Result->ok) {
				return;
			} 
			throw new \Exception("Error saving: ".$title->getPrefixedText());
		}
	}
}