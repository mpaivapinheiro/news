<?php

/**
* ownCloud - News
*
* @author Alessandro Cosentino
* @author Bernhard Posselt
* @copyright 2012 Alessandro Cosentino cosenal@gmail.com
* @copyright 2012 Bernhard Posselt dev@bernhard-posselt.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\News\Utility\ArticleEnhancer;

use \OCA\News\Utility\SimplePieFileFactory;


abstract class ArticleEnhancer {


	private $feedRegex;
	private $articleUrlRegex;
	private $articleXPath;
	private $purifier;
	private $fileFactory;
	private $maximumTimeout;


	/**
	 * @param $purifier the purifier object to clean the html which will be
	 * matched
	 * @param SimplePieFileFactory a factory for getting a simple pie file instance
	 * @param string $articleUrlRegex the regex to match which article should be
	 * handled
	 * @param string $articleXPath the xpath which tells the fetcher with what
	 * body the feed should be replaced
	 * @param int $maximumTimeout maximum timeout in seconds
	 */
	public function __construct($purifier, SimplePieFileFactory $fileFactory, 
	                            $articleUrlRegex, $articleXPath, 
	                            $maximumTimeout=10){
		$this->purifier = $purifier;
		$this->articleUrlRegex = $articleUrlRegex;
		$this->articleXPath = $articleXPath;
		$this->fileFactory = $fileFactory;
		$this->timeout = $maximumTimeout;
	}


	public function enhance($item){
		if(preg_match($this->articleUrlRegex, $item->getUrl())) {
			$file = $this->fileFactory->getFile($item->getUrl(), $this->maximumTimeout);
			$dom = new \DOMDocument();
			@$dom->loadHTML($file->body);
			$xpath = new \DOMXpath($dom);
			$xpathResult = $xpath->evaluate($this->articleXPath);

			// in case it wasnt a text query assume its a single 
			if(!is_string($xpathResult)) {
				$xpathResult = $this->domToString($xpathResult);
			}

			$sanitizedResult = $this->purifier->purify($xpathResult);
			$item->setBody($sanitizedResult);
		}

		return $item;
	}


	/**
	 * Method which turns an xpath result to a string
	 * Assumes that the result matches a single element. If the result 
	 * is not a single element, you can customize it by overwriting this
	 * method
	 * @param $xpathResult the result from the xpath query
	 * @return the result as a string
	 */
	protected function domToString($xpathResult) {
		if($xpathResult->length > 0) {
			return $this->toInnerHTML($xpathResult->item(0));
		} else {
			return "";
		}
	}


	protected function toInnerHTML($node) {
		$dom = new \DOMDocument();     
		$dom->appendChild($dom->importNode($node, true));
		return trim($dom->saveHTML());
	}


}