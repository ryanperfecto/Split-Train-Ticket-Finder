<?php
include 'simple_html_dom.php';

class BritishRailStations {
	
	function __construct() {
		$this->stations = array();
		$this->letters = array(
			'A','B','C','D',
			'E','F','G','H',
			'I','J','K','L',
			'M','N','O','P',
			'Q','R','S','T',
			'U','V','W','X',
			'Y','Z'
		);
		$this->stations_url = 'https://en.wikipedia.org/wiki/UK_railway_stations_-_';
		if ( !file_exists('stations.json') )
			$this->__fetch_stations();
		else
			echo file_get_contents("stations.json");
	}
	
	function __fetch_stations() {
		$this->html = new simple_html_dom();
		
		foreach ( $this->letters as $letter ) {
			
			if($this->html->load_file($this->stations_url.$letter) !== FALSE) {
			
			
				$trs = $this->html->find('table.wikitable tr');

				foreach ( $trs as $tr ) {
					
					if ( !empty ( $tr ) ) {
						$tds = $tr->find('td');
						
						if ( !empty ( $tds ) ) {
							
							$station_name = !empty( $tds[0]->plaintext ) ? trim($tds[0]->plaintext) : '';
							$station_postcode = !empty( $tds[1]->plaintext ) ? trim($tds[1]->plaintext) : '';
							$station_code =  !empty( $tds[2]->plaintext ) ? trim($tds[2]->plaintext) : '';
							
							if ( !empty($station_name) && !empty($station_postcode) 
								&& !empty($station_code) ) {
								$this->stations[] = array(
									'stationname' => trim($station_name),
									'stationpostcode' => trim($station_postcode),
									'stationcode' => trim($station_code)
								);
							}
						}
					}
			
				}
			}
		}	
		
		$fh = fopen('stations.json', 'w')
			or die('Error opening stations list');
		
		fwrite( $fh, json_encode( $this->stations, JSON_UNESCAPED_UNICODE ) );
		fclose( $fh );
		
	}
	
	private $letters;
	private $stations_url;
	private $html;
	private $ch;
	private $stations;
};

$ts = new BritishRailStations();
?>
