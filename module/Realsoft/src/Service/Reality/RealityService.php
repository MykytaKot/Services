<?php

namespace Realsoft\Service\Reality;

use MongoDB\Collection;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client as GuClient;
use GuzzleHttp\Exception\RequestException;
use MongoDB\BSON\UTCDateTime;

class RealityService {

    protected $settingsCollection;
    protected $queueCollection;
    private $reality;
    
    const IMPORT_TYPE_API = "api";
    const IMPORT_TYPE_QUEUE = "queue";
    const PRIMARY_ID_NAME = "object_id";

    public function __construct(Collection $settingsCollection,$queueCollection) {
        $this->settingsCollection = $settingsCollection;
        $this->queueCollection = $queueCollection;
    }

    public function getReality($apiKey): ?array {
        $this->reality = $this->settingsCollection->findOne(["apiKey" => $apiKey]);
        return $this->reality;
    }
    
    public function importAllowed(): bool{
        if(isset($this->reality) && !empty($this->reality["allowImport"])){
            return $this->reality["allowImport"];
        }
        return false;
    }
    
    public function getImportType(): ?string{
        if(isset($this->reality) && !empty($this->reality["import_type"])){
            return $this->reality["import_type"];
        }
        return self::IMPORT_TYPE_API;
    }
    
    public function getPrimaryIdName(): ?string{
        if(isset($this->reality) && !empty($this->reality["primary_id_name"])){
            return $this->reality["primary_id_name"];
        }
        return self::PRIMARY_ID_NAME;
    }
    
    public function export(Array $data,$url): ?array{
        $return = [];
        $client = new GuClient(["http_errors" => true]);
        try{
			$response = $client->request("POST", $url, ["form_params"=>($data),'timeout' => 240]);  
			//echo $response->getBody();
			$return["statusCode"] = $response->getStatusCode(); 
			$returnedData =(string) $response->getBody(); 
			
			if($response->getHeader('content-type')[0]=='application/json; charset=utf-8'){
			 
				$return['data'] = json_decode($returnedData, true);  
			}
			else{
				$return['data'] = $returnedData;
			}
		}
		catch(\GuzzleHttp\Exception\ConnectException $e){
			$return['data'] = ['error'=>'connection timeout'];
		}
		catch(\Exception $e){
			
			$return['data'] = ['error'=>$e->getMessage()];
		}
        return $return;
    }
    
    public function addToQueue($objectId,$url,$type,$data,$secondId=null,$index=null): void{
        $array=[];
        $array['id']=$objectId;
        if($secondId){
            $array['secondId']=$secondId;
        }
        $array['import_url']=$url;
        $array['type']=$type;
        $array['data']=$data;
		$array['created'] = new UTCDateTime();
        if(is_int($index)){
            $array['indexInArray']=$index;
        }
        $this->queueCollection->insertOne($array);
    }
	
	public function addToQueueAll($objectId,$url,$type,$images,$secondId=null): void{
        $array=[];
        $array['id']=$objectId;
        if($secondId){
            $array['secondId']=$secondId;
        }
        $array['import_url']=$url;
        $array['type']=$type;
        $array['data']=$images;
        $array['created'] = new UTCDateTime();
		
        $this->queueCollection->insertOne($array);
    }
} 
