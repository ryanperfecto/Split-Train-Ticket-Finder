<?php

/** (C) Ryan Ormrod, 2014 **/

class NationalRail {
	
	function __construct() {
		$this->hops = array();
		$this->fields = array();
		$this->prices = array();
	}
	
	function _post() {
		$this->html = curl_exec( $this->ch );
		curl_close( $this->ch );
	}
	
	function _post_hops() {
		foreach ( $this->hops as $route_string => $stations ) {
			foreach ( $stations as $hop ) {
				$this->_set_station_from($hop[0]);
				$this->_set_station_to($hop[1]);
				$this->_setup_curl();
				$this->_post();
				$this->_lookup_prices();
			}
		}
	}
	
	function _set_station_from ( $station ) {
		$this->_edit_field('from.searchTerm' , $station);
	}
	
	function _set_station_to ( $station ) {
		$this->_edit_field('to.searchTerm' , $station);
	}
	
	function _set_route_string ( $route_string ) {
		$this->route_string = $route_string;
	}
	
	function _add_hop( $hopFrom, $hopTo ) {
		$this->hops[$this->route_string][] = array( $hopFrom, $hopTo );
	}
	
	function _add_field ( $fieldName, $fieldValue ) {
		$this->fields[$fieldName] = $fieldValue;
	}
	
	function _set_fields ( $fields ) {
		$this->fields = $fields;
	}
	
	function _edit_field ($fieldName, $fieldValue ) {
		$this->fields[$fieldName] = $fieldValue;
	}
	
	function _get_field($fieldName) {
		return $this->fields[$fieldName];
	}
	
	private function _fields_to_string() {
		$return = '';
	
		foreach ($this->fields as $name => $data) {
			$return .= $name . '='.$data.'&'; 
		}
	
		return rtrim($return, '&');
	}
	
	function _set_lookup_string( $string ) {
		$this->lookup_string = $string;
	}
	
	function _lookup_prices() {
		libxml_use_internal_errors(true);
	
		$dom = new DOMDocument();

		@$dom->loadHTML( $this->html );
		$xpath = new DOMXPath( $dom );
		
		$this->prices[$this->route_string][] = array(
			'station.from' => $this->_get_field('from.searchTerm'),
			'station.to' => $this->_get_field('to.searchTerm'),
			'station.prices' => $xpath->query($this->lookup_string)
		);
	}
	
	function _get_prices_list() {
		return $this->prices[$this->route_string];
	}
	
	function _output_prices_list() {
		$count = 0;
		echo '<h1> Prices for ' . $this->route_string . '</h1>';
		echo '<table width="50%" cellpadding="5" cellspacing="5">';
		echo '<tr><th>Station From</th><th>Station To</th><th>Prices</th></tr>';
		
		foreach ($this->prices[$this->route_string] as $hop) {
			echo '<tr><td>';
			echo $hop['station.from'];
			echo '</td>';
			echo '<td>';
			echo $hop['station.to'];
			echo '</td>';
			echo '<td>';
			$prices = array();
			foreach ($hop['station.prices'] as $price) {
				$prices[] = $price->nodeValue;
			}
			asort($prices);
			echo $prices[0];
			$count += str_replace('£', '', $prices[0]);
			echo '</td></tr>';
		}
		
		echo '</tr></table>';
		echo '<strong>Total Cost is </strong>£' . $count . ' for ' . $this->_get_field('numberOfAdults') . ' adults';
	}
	
	function _set_post_url ( $url ) {
		$this->post_url = $url;
	}
	
	function _set_user_agent ( $user_agent ) {
		$this->user_agent = $user_agent;
	}
	
	function _set_cookie_file ( $cookie_file ) {
		$this->cookie_file = $cookie_file;
	}
	
	function _set_referrer ( $referrer ) {
		$this->referrer = $referrer;
	}
	
	function _setup_curl() {
		if( !empty ($this->post_url) && !empty ( $this->_fields_to_string() ) ) {
			$this->ch = curl_init();
			curl_setopt( $this->ch, CURLOPT_URL, $this->post_url );
			curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt( $this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt( $this->ch, CURLOPT_TIMEOUT, 10);
			curl_setopt( $this->ch, CURLOPT_HEADER, FALSE ); 
			curl_setopt( $this->ch, CURLOPT_POST, count( $this->_fields_to_string() ) );
			curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $this->_fields_to_string() );    
			curl_setopt( $this->ch, CURLOPT_REFERER, $this->referrer);
			curl_setopt( $this->ch, CURLOPT_USERAGENT, $this->user_agent);
			curl_setopt( $this->ch, CURLOPT_AUTOREFERER, TRUE);
			curl_setopt( $this->ch, CURLOPT_VERBOSE, FALSE);
			curl_setopt( $this->ch, CURLOPT_COOKIEJAR, $this->cookie_file);
		}
	}
	
	private $lookup_string;
	private $hops;
	private $user_agent;
	private $cookie_file;
	private $referrer;
	private $fields;
	private $post_url;
	private $ch;
	private $html;
	private $prices;
	private $route_string;
};
?>
<!doctype HTML>
<head>
	<meta charset="UTF-8" />
	<title>Split Ticket Calculator</title>
	<style type="text/css">
		table a:link {
			color: #666;
			font-weight: bold;
			text-decoration:none;
		}
		table a:visited {
			color: #999999;
			font-weight:bold;
			text-decoration:none;
		}
		table a:active,
		table a:hover {
			color: #bd5a35;
			text-decoration:underline;
		}
		table {
			font-family:Arial, Helvetica, sans-serif;
			color:#666;
			font-size:12px;
			text-shadow: 1px 1px 0px #fff;
			background:#eaebec;
			margin:20px;
			border:#ccc 1px solid;

			-moz-border-radius:3px;
			-webkit-border-radius:3px;
			border-radius:3px;

			-moz-box-shadow: 0 1px 2px #d1d1d1;
			-webkit-box-shadow: 0 1px 2px #d1d1d1;
			box-shadow: 0 1px 2px #d1d1d1;
		}
		table th {
			padding:21px 25px 22px 25px;
			border-top:1px solid #fafafa;
			border-bottom:1px solid #e0e0e0;

			background: #ededed;
			background: -webkit-gradient(linear, left top, left bottom, from(#ededed), to(#ebebeb));
			background: -moz-linear-gradient(top,  #ededed,  #ebebeb);
		}
		table th:first-child {
			text-align: left;
			padding-left:20px;
		}
		table tr:first-child th:first-child {
			-moz-border-radius-topleft:3px;
			-webkit-border-top-left-radius:3px;
			border-top-left-radius:3px;
		}
		table tr:first-child th:last-child {
			-moz-border-radius-topright:3px;
			-webkit-border-top-right-radius:3px;
			border-top-right-radius:3px;
		}
		table tr {
			text-align: center;
			padding-left:20px;
		}
		table td:first-child {
			text-align: left;
			padding-left:20px;
			border-left: 0;
		}
		table td {
			padding:18px;
			border-top: 1px solid #ffffff;
			border-bottom:1px solid #e0e0e0;
			border-left: 1px solid #e0e0e0;

			background: #fafafa;
			background: -webkit-gradient(linear, left top, left bottom, from(#fbfbfb), to(#fafafa));
			background: -moz-linear-gradient(top,  #fbfbfb,  #fafafa);
		}
		table tr.even td {
			background: #f6f6f6;
			background: -webkit-gradient(linear, left top, left bottom, from(#f8f8f8), to(#f6f6f6));
			background: -moz-linear-gradient(top,  #f8f8f8,  #f6f6f6);
		}
		table tr:last-child td {
			border-bottom:0;
		}
		table tr:last-child td:first-child {
			-moz-border-radius-bottomleft:3px;
			-webkit-border-bottom-left-radius:3px;
			border-bottom-left-radius:3px;
		}
		table tr:last-child td:last-child {
			-moz-border-radius-bottomright:3px;
			-webkit-border-bottom-right-radius:3px;
			border-bottom-right-radius:3px;
		}
		table tr:hover td {
			background: #f2f2f2;
			background: -webkit-gradient(linear, left top, left bottom, from(#f2f2f2), to(#f0f0f0));
			background: -moz-linear-gradient(top,  #f2f2f2,  #f0f0f0);	
		}
	</style>
	<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css" />
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>
	
	<script>
		$(function() {
			$("#from-name").autocomplete({
				source: "stations.php",
				minLength: 2,
				select: function(event, ui) {
					var url = ui.item.id;
					if(url != '#') {
						location.href = '/blog/' + url;
					}
				},
		 
				html: false
	
				open: function(event, ui) {
					$(".ui-autocomplete").css("z-index", 1000);
				}
			});
	  });
	</script>
</head>
<body>
	
<form method="post" action="#">
	<label>From Station:</label>
	<input type="text" id="from_name" name="from_name" />
	<label>To Station:</label>
	<input type="text" id="to_name[]" name="to_name[]" />
	
	<input type="submit" value="Split Tickets" />
</form>



<?php

$nr = new NationalRail();
$nr->_set_post_url('http://ojp.nationalrail.co.uk/service/planjourney/plan');
$nr->_set_referrer('http://ojp.nationalrail.co.uk/service/planjourney/search');
$nr->_set_user_agent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/34.0.1847.116 Chrome/34.0.1847.116 Safari/537.36');
$nr->_set_cookie_file('/tmp/rail');
$nr->_set_route_string('Tile Hill to Severn Beach');
$nr->_add_hop('Tile Hill', 'Birmingham New Street');
$nr->_add_hop('Birmingham New Street', 'University (Birmingham)');
$nr->_add_hop('University (Birmingham)', 'Longbridge');
$nr->_add_hop('Longbridge', 'Cheltenham Spa');
$nr->_add_hop('Cheltenham Spa', 'ChippenHam');
$nr->_add_hop('ChippenHam', 'Bath Spa');
$nr->_add_hop('Bath Spa', 'Bristol Temple Meads');
$nr->_add_hop('Bristol Temple Meads', 'Severn Beach');
$nr->_set_lookup_string("//label[@class='opsingle']");
$nr->_set_fields(array(
	'commandName' => 'journeyPlannerCommand',
	'jpState' => '000',
	'from.searchTerm' => '',
	'to.searchTerm' => '',
	'timeOfOutwardJourney.arrivalOrDeparture' => 'DEPART',
	'timeOfOutwardJourney.monthDay' => 'Today',
	'timeOfOutwardJourney.hour' => date("H"),
	'timeOfOutwardJourney.minute' => '45',
	'_checkbox' => 'on',
	'numberOfAdults' => 1,
	'numberOfChildren' => 0,
	'firstClass' => 'false',
	'_firstClass' => 'off',
	'standardClass' => 'true',
	'_standardClass' => 'off',
	'railcardCodes' => '',
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
$nr->_post_hops();

$result = $nr->_get_prices_list();
$nr->_output_prices_list();
?>
</body>
</html>
