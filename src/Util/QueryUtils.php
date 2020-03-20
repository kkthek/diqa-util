<?php
namespace DIQA\Util;

use Article;
use Title;

use SMWDataItem;
use SMWDITime;
use SMWQueryProcessor;
use SMWQueryResult;
use SMWTimeValue;
use SMWPropertyValue;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\StoreFactory;
use SMW\Query\PrintRequest;
use SMW\DataValueFactory;

/**
 * Utility class for executing SMW Queries
 *
 * @author Michael Erdmann
 */
class QueryUtils {

	/**
	 * @param queryString
	 * @param printouts
	 * @param parameters
	 * @return SMWDIWikiPage[]
	 */
	public static function executeQuery($queryString, $printouts = array(), $parameters = array()) {
		$smwQueryResult = static::executeBasicQuery($queryString, $printouts, $parameters);

		return $smwQueryResult->getResults();
	}

	/**
	 * @param queryString
	 * @param printouts
	 * @param parameters
	 * @return \SMWQueryResult
	 */
	public static function executeBasicQuery($queryString, $printouts = array(), $parameters = array()) {
		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );

		$smwQueryObject = SMWQueryProcessor::createQuery(
				$queryString,
				SMWQueryProcessor::getProcessedParams( $parameters, $printouts ),
				SMWQueryProcessor::SPECIAL_PAGE,
				'',
				$printouts
		);

		$smwStore = ApplicationFactory::getInstance()->getStore();

		return $smwStore->getQueryResult( $smwQueryObject );
	}
	
	/**
	 * @param queryString
	 * @param printouts
	 * @param parameters
	 * @return \SMWQueryResult
	 */
	public static function executeCountQuery($queryString, $printouts = array(), $parameters = array()) {
		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );
	
		$smwQueryObject = SMWQueryProcessor::createQuery(
				$queryString,
				SMWQueryProcessor::getProcessedParams( $parameters, $printouts ),
				SMWQueryProcessor::SPECIAL_PAGE,
				'count',
				$printouts
		);
	
		$smwStore = ApplicationFactory::getInstance()->getStore();
	
		return $smwStore->getQueryResult( $smwQueryObject );
	}


	/**
	 * @param String $pageName
	 * @param String $propertyName
	 * @return array of SMWDataItem
	 */
	 public static function getPropertyValues($pageName, $propertyName) {
	    $store = StoreFactory::getStore ();
	    $title = Title::newFromText ( $pageName );
	    $subject = DIWikiPage::newFromTitle ( $title );
	    $property = new DIProperty ( $propertyName );
	    $values = $store->getPropertyValues ( $subject, $property );
	    return $values;
	}

	/**
	 * @param String $pageName
	 * @param String $propertyName
	 * @return String with all property values (commaseparated)
	 */
	public static function getPropertyValuesAsString($pageName, $propertyName) {
	    $values = static::getPropertyValues($pageName, $propertyName);
	    $return = implode ( ', ', $values );
	    return $return;
	}

	/**
	 * @param String $pageName
	 * @param String $propertyName
	 * @return DIWikiPage
	 */
	public static function getPropertyValueAsPage($pageName, $propertyName) {
	    $values = static::getPropertyValues($pageName, $propertyName);
	    if(array_key_exists(0, $values)) {
	       return $values[0];
	    } else {
	        return null;
	    }
	}

	/**
	 * @param String $pageName
	 * @param String $propertyName
	 * @return SMWDataItem
	 */
	public static function getPropertyValue($pageName, $propertyName) {
	    $values = static::getPropertyValues($pageName, $propertyName);
	    if(! $values) {
	        return null;
	    } else if(count($values) > 0) {
	       return $values[0];
	    } else {
	        return null;
	    }
	}

	/**
	 * @param String $pageName
	 * @param String $propertyName
	 * @return String of the form 2001/12/31
	 */
	public static function getPropertyValueAsDate($pageName, $propertyName) {
	    $dateDI = static::getPropertyValue($pageName, $propertyName);
	    if(!$dateDI) {
	        return null;
	    }
	    $dateDV = new SMWTimeValue(DIProperty::TYPE_TIME);
	    $dateDV->setDataItem($dateDI);
	    $year = $dateDV->getYear(SMWDITime::CM_GREGORIAN);
   	    $month = $dateDV->getMonth(SMWDITime::CM_GREGORIAN);
   	   	$day = $dateDV->getDay(SMWDITime::CM_GREGORIAN);
        if ($month < 10) {
            $month = "0" . $month;
        }
        if ($day < 10) {
            $day = "0" . $day;
        }
        $return = $year . "/" . $month . "/" . $day;
        return $return;
    }

    /**
	 * @param String $pageName
     * @return Title of the item's category, the first one without spaces, if more than one exists
     */
    public static function getCategory($pageName) {
    	global $wgODBCategoriesToShowInTitle;

        $categories = static::getCategories($pageName);
        if (count($categories) > 0) {
            foreach ($categories as $category) {
                if (in_array($category->getPrefixedText(), $wgODBCategoriesToShowInTitle)) {
                    return $category;
	            }
	        }
            return $categories[0];
	    } else {
            return null;
	    }
	}

	/**
	 * @param String $pageName
	 * Returns a list of categories this page is a member of.
	 * Results will include hidden categories
	 *
	 * @return array \Title
	 */
	public static function getCategories($pageName) {
	    $title = Title::newFromText ( $pageName );
	    $page = new Article($title);
	    $categoriesIterator = $page->getPage()->getCategories();

	    $categories = array();
	    foreach ($categoriesIterator as $categoryTitle) {
	        $categories[] = $categoryTitle;
	    }
	    return $categories;
	}

	/**
	 * @param $propertyName string of the property name and the label within the query
	 * @return a PrintRequest object with the given property name and label
	 */
	 public static function newPropertyPrintRequest($propertyName) {
		return new PrintRequest(PrintRequest::PRINT_PROP, $propertyName, 
		                  DataValueFactory::getInstance()->newPropertyValueByLabel( $propertyName ));
	}

}