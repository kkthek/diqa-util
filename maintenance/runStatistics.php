<?php
use DIQA\Util\LoggerUtils;
use DIQA\Util\WikiUpdatorUtil;
use DIQA\Util\QueryUtils;
use SMW\StoreFactory;
/**
 * RunStatistics
 *
 * @ingroup DIQA Util
 */

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class RunStatistics extends Maintenance {
	public function __construct() {
		parent::__construct ();
		$this->mDescription = "RunStatistics";
		$this->addOption ( 'dateformat', 'Format of date (eg. Y-d-m)', false, true );
	}
	
	public function execute() {
		$this->logger = new LoggerUtils ( 'RunStatistics', 'Util' );
		
		try {
			$statistics = $this->getStatisticSites();
			foreach($statistics as $stat) {
				$this->logger->log("Site: ".$stat['title']->getPrefixedText());
				$query = $stat['statistikQuery'];
				$this->logger->log("statistikQuery: ".$query);
				$template = $stat['statistikTemplate'];
				$this->logger->log("statistikTemplate: ".$template);
				$value = $this->doQuery($query, $stat['title']);
				$this->logger->log("statistikQuery value: ".$value);
			
				$this->appendToSite($stat['title'], $template, $value);
			}
		} catch ( Exception $e ) {
			$this->logger->error ( $e->getMessage () );
		}
	}
	
	/**
	 * Returns array of statistic site with StatistikQuery, StatistikTemplate annotations
	 * @return array of [ title, statistikQuery, statistikTemplate ]
	 */
	private function getStatisticSites() {
		$result = [ ];
		
		$StatistikQuery = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "StatistikQuery",
				\SMWPropertyValue::makeUserProperty ( 'StatistikQuery' ) );
		$StatistikTemplate = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "StatistikTemplate",
				\SMWPropertyValue::makeUserProperty ( 'StatistikTemplate' ) );
		
		$pages = QueryUtils::executeBasicQuery ( '[[Category:Statistik]]', [$StatistikQuery, $StatistikTemplate ], [] );
		
		while ( $res = $pages->getNext () ) {
			$pageID = $res [0]->getNextText ( SMW_OUTPUT_WIKI );
			$StatistikQuery = $res [1]->getNextText ( SMW_OUTPUT_WIKI );
			$StatistikTemplate = $res [2]->getNextText ( SMW_OUTPUT_WIKI );
			$mwTitle = \Title::newFromText ( $pageID );
			$result [] = [ 'title' => $mwTitle, 
						   'statistikQuery' => urldecode($StatistikQuery),
						   'statistikTemplate' => urldecode($StatistikTemplate)
			];
		}
		
		return $result;
	}
	
	/**
	 * Executes the statistic query. (count-Query!) 
	 * @param string $query
	 * @return number
	 */
	private function doQuery($query, $title) {
		
		if (preg_match('/\{\{#ask:([^}]*)\}\}/', $query, $matches) == 1) {
			$parts = explode("|", $matches[1]);
			$query = array_shift($parts);
			
			$params = [];
			foreach($parts as $p) {
				$keyValue = explode("=", $p);
				$params[trim($keyValue[0])] = trim($keyValue[1]);
			}
			$out = QueryUtils::executeCountQuery ( $query, [], $params )->getCountValue();
		} else {
			// assume $query is wikitext
			global $wgParser;
			$popt = new ParserOptions();
			$out = strip_tags($wgParser->parse( $query, $title, $popt )->getRawText());
		}
		return $out;
	}
	
	/**
	 * Append template (=subobject) to the page
	 * @param Title $mwTitle
	 * @param string $template
	 * @param string $value
	 */
	private function appendToSite($mwTitle, $template, $value) {
		$dateFormat = $this->getOption('dateformat', 'd.m.Y');
		$template = str_replace('{{{now}}}', date($dateFormat), $template);
		$template = str_replace('{{{value}}}', $value, $template);
		$template = str_replace(["\n", "\r"], "", $template);
		$this->logger->log("add statistic object: ".$template);
		WikiUpdatorUtil::createOrAppendsToTitle($mwTitle, $template);
	}
}

$maintClass = "RunStatistics";
require_once RUN_MAINTENANCE_IF_MAIN;
