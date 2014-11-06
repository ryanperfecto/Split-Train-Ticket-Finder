<?php

/**
* NationalRail
* 
* The NationalRail class is a PHP application written by Ryan Ormrod. The main concept
* is to find the lowest price available using split ticket prices. 
*
* This class requires CURL and knowledge of HTML, Xpath and webform data.
*	
* @author     RYAN ORMROD <*@gmail.com>
*/



class NationalRail {

	const last_updated = '2014-10-28';
	const updated_by = 'Ryan Ormrod';

	private $config = array();
	private $result;
	private $ch;
	private $prices = array();

	/**
       * 
       * Constructor
       *
       * @param string $name  Just a general name of the journey for outputting the data.
       * @param boolean $defaults Load the default values.
       * @param array $general A set of options to be set to the server.
       */
	function __construct( $name, $defaults = TRUE, array $general = array() ) {
		if(!ctype_alpha(str_replace(' ', '', $name))) {
			throw new \InvalidArgumentException(sprintf('The name "%s" is invalid.', $name));
		}
		$this->config['name'] = $name;
		$this->config['general'] = $general;
		if($defaults === TRUE) $this->setDefaults();
	}

	/**
       * 
       * Set a set of default values to be used
       *
       * @return void
       */

	public function setDefaults () {
		$this->setChecks(array(
			'postUrl',
			'referrerUrl',
			'userAgent',
			'cookieFile',
			'lookupString',
			'hops',
			'postFields'
		));

		$minutes = date('i');

		if ( $minutes < 15 ) {
			$minutes = 15;
		} elseif ($minutes < 30 && $minutes > 15) {
			$minutes = 30;
		} elseif ($minutes < 45 && $minutes > 30) {
			$minutes = 45;
		} elseif ($minutes > 45) {
			$minutes = 00;
		}

		$this->setPostFields(array(
			'commandName' => 'journeyPlannerCommand',
			'jpState' => '000', /* Todo: Investigate what this means. Default: '000' */
			'from.searchTerm' => '',
			'to.searchTerm' => '',
			'timeOfOutwardJourney.arrivalOrDeparture' => 'DEPART',
			'timeOfOutwardJourney.monthDay' => 'Today',
			'timeOfOutwardJourney.hour' => isset($this->config['general']['journeyHour']) ? $this->config['general']['journeyHour'] : date('H'),
			'timeOfOutwardJourney.minute' => isset($this->config['general']['jouneyMinute']) ? $this->config['general']['journeyMinute'] : $minutes,
			'_checkbox' => isset($this->config['general']['checkBox']) ? $this->config['general']['checkBox'] : 'on',
			'numberOfAdults' => isset($this->config['general']['numberOfAdults']) ? $this->config['general']['numberOfAdults'] : '1',
			'numberOfChildren' => isset($this->config['general']['numberOfChildren']) ? $this->config['general']['numberOfChildren'] : '0',
			'firstClass' => isset($this->config['general']['firstClass']) ? $this->config['general']['firstClass'] : 'false',
			'_firstClass' => isset($this->config['general']['firstClassToggle']) ? $this->config['general']['firstClassToggle'] : 'off',
			'standardClass' => isset($this->config['general']['standardClass']) ? $this->config['general']['standardClass'] : 'true',
			'_standardClass' => isset($this->config['general']['standardClassToggle']) ? $this->config['general']['standardClassToggle'] : 'off',
			'railcardCodes' => '', /* Todo: Identify the valid options. Default: '' */
			'numberOfEachRailcard' => '0',
			'oldRailcardCodes' => '',
			'viaMode' => 'VIA',
			'via.searchTerm' => 'Station',
			'via1Mode' => 'VIA',
			'via2.searchTerm' => '',
			'offSetOption' => '0',
			'operator.code' => '',
			'_reduceTransfers' => 'off',
			'_lookForSleeper' => 'off',
			'_directTrains' => 'off',
			'_showFastestTrainsOnly' => 'off'
		));
	}

	// Configuration Section

	/*
	*
    * set
    *
    * Just a general method to set a value within the config array
    *
    * @param string $name The name of the value with no special characters.
    * @param string $value The value.
    * @return void
    */

	public function set ( $name, $value ) {
		if(!ctype_alpha(str_replace(' ', '', $name))) {
			throw new \InvalidArgumentException(sprintf('The name "%s" is invalid.', $name));
		}
		$this->config[$name] = $value;
	}

	/*
	*
    * setGeneralOption
    *
    * The general options are used to control the NationalRail website application.
    *
    * @param string $name The name of the value with no special characters.
    * @param string $value The value.
    * @return void
    */

	public function setGeneralOption( $name, $value ) {
		if(!ctype_alpha(str_replace(' ', '', $name))) {
			throw new \InvalidArgumentException(sprintf('The name "%s" is invalid.', $name));
		}
		$this->config['general'][$name] = $value;
	}

	/*
	*
    * setJourneyHour
    *
    * The hour of the day to travel on a given day. It should be between 00 and 23.
    *
    * @param integer $value The value.
    * @return void
    */
	public function setJourneyHour ( $value ) {
		$valid_options = range(0, 23);
		if ( !intval ( $value ) ) {
			throw new \InvalidArgumentException(sprintf('The journey hour must be numeric "%s".', $value));
		} elseif ( !in_array($value, $valid_options) && !in_array('0'.$value, $valid_options)) {
			throw new \InvalidArgumentException(sprintf('The journey hour must be between 00 and 23 "%s".', $value));
		}
		$this->config['general']['journeyHour'] = $value;
	}

	/*
	*
    * setJourneyMinute
    *
    * The minute of the day to travel on a given day. It should be incrementally 15 from 00 to 45.
    *
    * @param integer $value The value.
    * @return void
    */

	public function setJourneyMinute ( $value ) {
		$valid_options = array('00', '15', '30', '45');
		if ( !intval ( $value ) ) {
			throw new \InvalidArgumentException(sprintf('The journey minute must be numeric "%s".', $value));
		} elseif ( !in_array($value, $valid_options) ) {
			throw new \InvalidArgumentException(sprintf('The journey minute must be between 0 and 24 "%s".', $value));
		}
		$this->config['general']['journeyHour'] = $value;
	}

	/*
	*
    * setCheckBox
    *
    * Set the value of the _checkbox parameter as on or off
    *
    * @param string $value The value.
    * @return void
    */

	public function setCheckBox ( $value ) {
		if ( $value != 'on' || $value != 'off' ) {
			throw new \InvalidArgumentException(sprintf('The value "%s" is invalid.', $value));
		}
		$this->config['general']['checkBox'] = $value;
	}

	/*
	*
    * setNumberOfAdults
    *
    * Set the number of adults to travel
    *
    * @param integer $value The value.
    * @return void
    */

	public function setNumberOfAdults ( $value ) {
		if ( !intval($value) ) {
			throw new \InvalidArgumentException(sprintf('The value "%s" is invalid.', $value));
		}
		$this->config['general']['numberOfAdults'] = $value;
	}

	/*
	*
    * setNumberOfChildren
    *
    * Set the number of children to travel.
    *
    * @param integer $value The value.
    * @return void
    */

	public function setNumberOfChildren( $value ) {
		if ( !intval($value) ) {
			throw new \InvalidArgumentException(sprintf('The value "%s" is invalid.', $value));
		}
		$this->config['general']['numberOfChildren'] = $value;
	}

	/*
	*
    * setFirstClass
    *
    * Want to travel first class? Note: This will disable standard class.
    *
    * @param boolean $value TRUE OR FALSE
    * @return void
    */

	public function setFirstClass ( $value ) {
		if ( $value !== FALSE || $value !== TRUE ) {
			throw new \InvalidArgumentException(sprintf('The value "%s" is invalid.', $value));
		}
		if ( $value === FALSE ) {
			$this->config['general']['firstClass'] = 'false';
			$this->config['general']['firstClassToggle'] = 'off';
			$this->config['general']['standardClass'] = 'true';
			$this->config['general']['standardClassToggle'] = 'on';
		} elseif ( $value === TRUE) {
			$this->config['general']['firstClass'] = 'true';
			$this->config['general']['firstClassToggle'] = 'on';
			$this->config['general']['standardClass'] = 'false';
			$this->config['general']['standardClassToggle'] = 'off';
		}
	}

	/*
	*
    * setFirstClass
    *
    * Want to travel first class? Note: This will disable first class.
    *
    * @param boolean $value TRUE OR FALSE
    * @return void
    */

	public function setStandardClass ( $value ) {
		if ( $value !== FALSE || $value !== TRUE ) {
			throw new \InvalidArgumentException(sprintf('The value "%s" is invalid.', $value));
		}
		if ( $value === FALSE ) {
			$this->config['general']['standardClass'] = 'false';
			$this->config['general']['standardClassToggle'] = 'off';
			$this->config['general']['firstClass'] = 'true';
			$this->config['general']['firstClassToggle'] = 'on';
		} elseif ( $value === TRUE) {
			$this->config['general']['standardClass'] = 'true';
			$this->config['general']['standardClassToggle'] = 'on';
			$this->config['general']['firstClass'] = 'false';
			$this->config['general']['firstClassToggle'] = 'off';

		}
	}

	/*
	*
    * get
    *
    * Get a particular value from the config array
    *
    * @param string $name The name of the value that needs to be obtained.
    * @return string
    */

	public function get ( $name ) {
		if( !$this->has ( $name ) ) {
			throw new \InvalidArgumentException(sprintf('The name "%s" is invalid.', $name));
		}
		return $this->config[$name];
	} 

	/*
	*
    * edit
    *
    * Edit a particular value in the config array
    *
    * @param string $name The name of the value that needs to be obtained.
    * @param string $value The new value that needs to be set.
    * @return void
    */

	public function edit ( $name, $value ) {
		if( !$this->has ( $name ) ) {
			throw new \InvalidArgumentException(sprintf('The name "%s" does not exist and can not be edited.', $name));
		}
		$this->config[$name] = $value;
	}

	/*
	*
    * has
    *
    * Check whether or not a value exists within the config array.
    *
    * @param string $name The name of the value that needs to be obtained.
    * @param string $value The new value that needs to be set.
    * @return string
    */

	public function has ( $name ) {
		return isset($this->config['name']);
	}

	/*
	*
    * setPostUrl
    *
    * In order to know where the data is being posted to, the post url
    * must be specified. This can be found by inspecting the HTML
    * source code. <form action=""
    *
    * @param string $url The url where the server is waiting for post data.
    * @return void
    */

	public function setPostUrl ( $url ) {
		if( !filter_var($url, FILTER_VALIDATE_URL) ) {
			throw new \InvalidArgumentException(sprintf('The url "%s" is invalid.', $url));
		}
		$this->config['postUrl'] = $url;
	}

	/*
	*
    * setPostReferrer
    *
    * Many scripts might check that the referrer (the page that posted the data) is legimate.
    * This is not necessarily done for security purposes, but to keep track of routing.
    *
    * @param string $url The url where the server is waiting for post data.
    * @return void
    */

	public function setPostReferrer ( $url ) {
		if( !filter_var($url, FILTER_VALIDATE_URL) ) {
			throw new \InvalidArgumentException(sprintf('The referrer url "%s" is invalid.', $url));
		}
		$this->config['referrerUrl'] = $url;
	}

	/*
	*
    * setUserAgent
    *
    * It is best to send a standard user agent, since some websites might blacklist user agents search
    * as Google search bot. There is no reason not to send this item.
    *
    * @param string $userAgent The User Agent to be sent.
    * @return void
    */

	public function setUserAgent ( $userAgent ) {
		if ( empty ( $userAgent ) ) {
			throw new \InvalidArgumentException(sprintf('The user agent "%s" is invalid.', $userAgent));
		}
		$this->config['userAgent'] = $userAgent;
	}

	/*
	*
    * setCookieFile
    *
    * A cookie file has been set to ensure that it is there for future purposes.
    *
    * @param string $cookieFile The file used to store cookies.
    * @return void
    */

	public function setCookieFile ( $cookieFile ) {
		$file = fopen($cookieFile, "w");
		fclose($file);
		if ( !is_writeable ( $cookieFile ) ) {
			throw new \InvalidArgumentException(sprintf('The cookie file "%s" is invalid.', $cookieFile));
		}
		$this->config['cookieFile'] = $cookieFile;
	}

	/*
	*
    * setHops
    *
    * A method to set all of the hops (stations) needed to travel through to get to a particular destination.
    * Todo: Determine a list of all stations between station A, and station B nationally.
    *
    * @param array $hops The file used to store cookies.
    * @return void
    */

	public function setHops ( array $hops ) {
		if ( !is_array ( $hops ) || count ( $hops ) < 1 ) {
			throw new \InvalidArgumentException('Invalid hops');
		}
		$this->config['hops'] = $hops;
	}

	/*
	*
    * setHop
    *
    * A method to set all of the hops (stations) needed to travel through to get to a particular destination.
    * Todo: Determine a list of all stations between station A, and station B nationally.
    *
    * @param string $stationFrom The station that is being departed from.
    * @param string $stationTo The station that is next in line.
    * @return void
    */

	public function setHop ( $stationFrom, $stationTo ) {
		if(!preg_match('/[a-zA-Z\s-()]/i', $stationFrom)) {
			throw new \InvalidArgumentException(sprintf('The name "%s" is invalid.', $stationFrom));
		} elseif(!preg_match('/[a-zA-Z\s-()]/i', $stationTo)) {
			throw new \InvalidArgumentException(sprintf('The name "%s" is invalid.', $stationTo));
		}
		$this->config['hops'][] = array('stationFrom' => $stationFrom, 'stationTo' => $stationTo);
	}

	/*
	*
    * setLookupString
    *
    * The lookup string is the xpath query to find the price on a particular page. Xpath knowledge is required.
    *
    * @param string $stationFrom The station that is being departed from. The default is: ( //label[@class='opsingle'] );
    * @return void
    */

	public function setLookupString ( $lookupString ) {
		if ( empty ( $lookupString ) ) {
			throw new \InvalidArgumentException(sprintf('The lookup string "%s" is invalid.', $lookupString));
		}
		$this->config['lookupString'] = $lookupString;
	}

	/*
	*
    * setChecks
    *
    * Before a form is posted a number of details must already exist. These can be edited using the setCheck option.
    *
    * @param array $checks A single-dimensional array of keys that need to exist.
    * @return void
    */

	public function setChecks ( array $checks ) {
		if ( !is_array ( $checks ) || count ( $checks ) < 1 ) {
			throw new \InvalidArgumentException('Invalid checks');
		}
		$this->config['checks'] = $checks;
	}

	/*
	*
    * setCheck
    *
    * Before a form is posted a number of details must already exist. These can be edited using the setCheck option.
    *
    * @param string $name The name of the value.
    * @param string $value The value itself.
    * @return void
    */

	public function setCheck ( $name, $value ) {
		if ( empty ( $name ) ) {
			throw new \InvalidArgumentException(sprintf('The field "%s" is invalid.', $field) );
		}
		$this->config['checks'][$name] = $value;
	}

	/*
	*
    * editCheck
    *
    * Edit the value of a particular check. 
    *
    * @param string $name The name of the value.
    * @param string $value The value itself.
    * @return void
    */

	public function editCheck ( $name, $value ) {
		if( !array_key_exists($name, $this->config['checks']) ) {
			throw new \InvalidArgumentException(sprintf('The name "%s" does not exist and can not be edited.', $name));
		}
		$this->config['checks'][$name] = $value;
	}

	/*
	*
    * setPostFields
    *
    * The form that is being posted to has a bunch of hidden and visible fields that need to be declared.
    *
    * @param array $fields Insert all of the required fields by array. ($field_name => $field_value)
    * @return void
    */

	public function setPostFields ( array $fields ) {
		if ( !is_array ( $fields ) || count ( $fields ) < 1 ) {
			throw new \InvalidArgumentException('Invalid post fields');
		}
		$this->config['postFields'] = $fields;
	}

	/*
	*
    * setPostField
    *
    * The form that is being posted to has a bunch of hidden and visible fields that need to be declared.
    *
    * @param string $name Provide the name for the field (it should exist on the form, otherwise why send it?)
    * @param string $value Provide the default value. 
    * @return void
    */

	public function setPostField ( $name, $value ) {
		if ( empty ( $name ) ) {
			throw new \InvalidArgumentException(sprintf('The field "%s" is invalid.', $field) );
		}
		$this->config['postFields'][$name] = $value;
	}

	/*
	*
    * editPostField
    *
    * Edit the field value.
    *
    * @param string $name Provide the name for the field (it should exist on the form, otherwise why send it?)
    * @param string $value Provide the default value. 
    * @return void
    */

	public function editPostField ( $name, $value ) {
		if( !array_key_exists($name, $this->config['postFields']) ) {
			throw new \InvalidArgumentException(sprintf('The name "%s" does not exist and can not be edited.', $name));
		}
		$this->config['postFields'][$name] = $value;
	}

	/*
	*
    * getPostField
    *
    * Get the value of the field.
    *
    * @param string $name Provide the name for the field.
    * @return void
    */

	public function getPostField ( $name ) {
		if( !array_key_exists($name, $this->config['postFields']) ) {
			throw new \InvalidArgumentException(sprintf('The name "%s" does not exist.', $name));
		}
		return $this->config['postFields'][$name];
	}

	/*
	*
    * getFieldsAsString
    *
    * Create a querystring of all the fields and their values to be sent to the server.
    * field=value&field2=value2&field3=value3 etc.
    *
    * @return void
    */

	private function getFieldsAsString() {
		$return = '';
	
		foreach ($this->config['postFields'] as $name => $data) {
			$return .= $name . '='.$data.'&'; 
		}
	
		return rtrim($return, '&');
	}

	/*
	*
    * postHops
    *
    * All of the stations have been setup as hops. This function must loop through each
    * of the stations and obtain the price for each one.
    *
    * @return boolean
    */

	public function postHops ( ) {
		foreach ( $this->config['hops'] as $key => $value ) {
			$this->editPostField('from.searchTerm', $value['stationFrom']);
			$this->editPostField('to.searchTerm', $value['stationTo']);
			if ( ! $this->initCurl() ) {
				return FALSE;
			} else {
				$this->postHop();
				$this->getPrices();
			}
		}
		return TRUE;
	}

	/*
	*
    * initCurl
    *
    * Setup curl with the information it requires.
    *
    * @return boolean
    */

	private function initCurl() {
		if( $this->valid() ) {
			$this->ch = curl_init();
			curl_setopt( $this->ch, CURLOPT_URL, 				$this->config['postUrl'] );
			curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 	TRUE);
			curl_setopt( $this->ch, CURLOPT_FOLLOWLOCATION, 	TRUE);
			curl_setopt( $this->ch, CURLOPT_TIMEOUT, 			10);
			curl_setopt( $this->ch, CURLOPT_HEADER, 			FALSE ); 
			curl_setopt( $this->ch, CURLOPT_POST, 				count( $this->getFieldsAsString() ) );
			curl_setopt( $this->ch, CURLOPT_POSTFIELDS, 		$this->getFieldsAsString() );    
			curl_setopt( $this->ch, CURLOPT_REFERER, 			$this->config['referrerUrl'] );
			curl_setopt( $this->ch, CURLOPT_USERAGENT, 			$this->config['userAgent']);
			curl_setopt( $this->ch, CURLOPT_AUTOREFERER, 		TRUE);
			curl_setopt( $this->ch, CURLOPT_VERBOSE, 			FALSE);
			curl_setopt( $this->ch, CURLOPT_COOKIEJAR, 			$this->config['cookieFile']);
			return TRUE;
		}
		return FALSE;
	}

	/*
	*
    * postHop
    *
    * Submits a hop to the server. Retrieves the html data and puts it into the result variable.
    *
    * @return void
    */

	private function postHop () {
		$this->result = curl_exec ( $this->ch );
		curl_close ( $this->ch );
	}

	/*
	*
    * getPrices
    *
    * Once all of the information has been obtained the prices must be inserted into the prices array.
    *
    * @return void
    */

	private function getPrices () {
		libxml_use_internal_errors(true);
	
		$dom = new DOMDocument();

		@$dom->loadHTML( $this->result );
		$xpath = new DOMXPath( $dom );
		
		$this->prices[] = array(
			'station.from' => $this->getPostField('from.searchTerm'),
			'station.to' => $this->getPostField('to.searchTerm'),
			'station.prices' => $xpath->query($this->config['lookupString'])
		);
	}

	/*
	*
    * getPriceList
    *
    * Returns a list of all of the prices that have been discovered.
    *
    * @return array
    */
	
	public function getPriceList() {
		if( count($this->prices) > 0 ) {
			throw new \InvalidArgumentException(sprintf('The name "%s" is invalid.', $name));
		}
		return $this->prices;
	}

	// Validation

	/*
	*
    * valid
    *
    * If an item is missing from the array then false will be returned otherwise the result will be true.
    *
    * @return boolean
    */
	private function valid() {
		foreach($this->config['checks'] as $key) {
			if(!array_key_exists($key, $this->config)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	// Output

	/*
	*
    * toSerial
    *
    * Obtain the information as a serialized array
    *
    * @return string
    */

	public function toSerial() {
		return serialize($this->toArray());
	}

	/*
	*
    * toJson
    *
    * Obtain the information in JSON format.
    *
    * @return string
    */

	public function toJson() {
		return json_encode($this->toArray());
	}

	/*
	*
    * toArray
    *
    * Obtain the information as an array
    *
    * @return array
    */
	public function toArray() {
		return array(
			'name' => $this->config['name'],
			'hops' => $this->config['hops'],
			'prices' => $this->prices
		);
	}

	/*
	*
    * toTabular
    *
    * Output a basic tabular (table) output of the information
    *
    * @return void
    */

	public function toTabular() {
		setlocale(LC_MONETARY, 'en_GB');

		$totalCount = 0; // Count get the total journey price
		echo '<table width="50%" cellpadding="5" cellspacing="5">';
			echo '<tr><th align="left">Station From</th><th align="left">Station To</th><th align="left">Prices</th></tr>';

			foreach ( $this->prices as $hop ) {
				echo '<tr><td>'.$hop['station.from'].'</td>';
				echo '<td>'.$hop['station.to'].'</td><td>';

				$prices = array();
				foreach ( $hop['station.prices'] as $price ) {
					$prices[] = $price->nodeValue;
				}

				asort($prices);
				$fmt = '<strong>Selected Price:</strong>&pound;%i ';
				echo money_format($fmt, floatval(str_replace('£', '', $prices[0]))) . "\n";
				$totalCount += str_replace('£', '', $prices[0]);
				echo '</td></tr>';

			}
		echo '</table>';

		$fmt = '<strong>Total Cost is </strong> &pound;%i ';
		echo money_format($fmt, $totalCount) . "\n";

		echo ' for ' . $this->getPostField('numberOfAdults') . ' adult(s) ';
		echo ' and ' . $this->getPostField('numberOfChildren') . ' child(s).';
	}

	/*
	*
    * debug
    *
    * Output a dump of the config and prices variables.
    *
    * @return void
    */

	public function debug() {
		var_dump(array(
			'config' => $this->config,
			'prices' => $this->prices
		));
	}
};
?>
<html><head><meta charset="UTF-8" /><title>Train Journey</title></head><body>
<?php

try {
$nr = new NationalRail('Tile Hill to Seven Trent');
$nr->setPostUrl('http://ojp.nationalrail.co.uk/service/planjourney/plan');
$nr->setPostReferrer('http://ojp.nationalrail.co.uk/service/planjourney/search');
$nr->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/34.0.1847.116 Chrome/34.0.1847.116 Safari/537.36');
$nr->setCookieFile('rail.txt');
$nr->setHop('Tile Hill', 'Birmingham New Street');
$nr->setHop('Birmingham New Street', 'University (Birmingham)');
$nr->setHop('University (Birmingham)', 'Longbridge');
$nr->setHop('Longbridge', 'Cheltenham Spa');
$nr->setHop('Cheltenham Spa', 'ChippenHam');
$nr->setHop('ChippenHam', 'Bath Spa');
$nr->setHop('Bath Spa', 'Bristol Temple Meads');
$nr->setHop('Bristol Temple Meads', 'Severn Beach');
$nr->setLookupString("//label[@class='opsingle']");
$nr->postHops();

$nr->toTabular();

//$nr->debug();
// var_dump($nr->toSerial());
// var_dump($nr->toJson());
// var_dump($nr->toArray());

} catch ( InvalidArgumentException $e ) {
	echo $e->getMessage();
}
?></body></html>
