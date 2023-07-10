<?php

namespace Location\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Location\Model\LocationService;

class ImportController extends AbstractActionController {

    private $locationService;

    public function __construct(LocationService $locationService) {
        $this->locationService = $locationService;
    }

    public function importgpsAction(){
		
		$data = $this->locationService->getWithoutCoords();
		foreach($data as $location){
			echo $location['full_name'];
			$cord=$this->getCoords($location['name'].',Hungary');
			if($cord){
				$this->locationService->saveCoords($location['id'],$cord);
				echo ' OK'."<BR>";
				
			}
			echo ' NULL'."<BR>";
		}
		

		exit;
		
	}
	
	private  function getCoords($address) {
		$urlencodeAddress = urlencode($address);
		 // Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => "https://nominatim.openstreetmap.org/search.php?format=json&q=" . $urlencodeAddress,
			CURLOPT_USERAGENT => 'Codular Sample cURL Request'
		));
	// Send the request & save response to $resp
		$response = curl_exec($curl);
		// Close request to clear up some resources
		curl_close($curl);		
		if(strpos($response, "Bandwidth limit exceeded") === false){
		
			$json = json_decode($response, true);
			if (!empty($json[0]['lat'])) {		
					$lat = $json[0]['lat'];
					$lng = $json[0]['lon'];   
					return trim($lat.','.$lng);
			}
			return null;
			}
		else{
			return null;
		}
    }

}
