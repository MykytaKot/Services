<?php

namespace Realsoft\Controller;

//use Realsoft\Model\GlobalSettings\GlobalSettings;
use Laminas\Mvc\Controller\AbstractActionController;
use Application\Core\CollectionMap;
use MongoDB\Database;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use Realsoft\Service\Reality\RealityService;
use Laminas\View\Model\JsonModel;

class IndexController extends AbstractActionController {

    protected $log_collection;
    protected $queue_collection;
    protected $realityService;

    const SUCCESS_INSERT = 1;
    const SUCCESS_UPDATE = 2;
    const ERROR_LOGIN_DATA = 10;
    const ERROR_CUSTOM = 13;

    public function __construct($log_collection,$queue_collection, RealityService $realityService) {
        $this->log_collection = $log_collection;
        $this->queue_collection = $queue_collection;
        $this->realityService = $realityService;
    }

    public function importAction() {
        try {
        
            $postedData = $this->params()->fromPost();
            
            if (count($postedData) == 0) {
                exit;
            }
            $time_start = microtime(true);
            $return = [];
            $log = [];
            $log['created'] = new UTCDateTime();
            $log['action'] = 'realsoft_import';
            $log['data'] = $postedData;
            $log['fromIp'] = $_SERVER['REMOTE_ADDR'];
            $log['apiKey'] = $postedData["apiKey"];
            $reality = $this->realityService->getReality(trim($postedData["apiKey"]));

            if ($reality === NULL) {
                $log['result'] = 'error';
                $log['result_message'] = "Reality not exists";
                $return["code"] = self::ERROR_LOGIN_DATA;
                $return["message"] = "Reality not exists";
            } else {
                if ($this->realityService->importAllowed()) {
                    if ($this->realityService->getImportType() == RealityService::IMPORT_TYPE_API) {
                        if ($postedData["action"] == 1) {
                            
                            // prevod na array aby sme mohli doplnit primary id
                            $tempArray = json_decode($postedData['data'], true);
                            $primary_id = $tempArray[$this->realityService->getPrimaryIdName()];
                            if (empty($primary_id)) {
                                $log['result'] = 'error';
                                $log['result_message'] = "Primary id not exists";
                                $return["code"] = self::ERROR_CUSTOM;
                                $return["message"] = "Internal error";
                                $this->log_collection->insertOne($log);
                                return new JsonModel($return);
                            }
                            if (is_numeric($primary_id)) {
                                $primary_id = (int) $primary_id;
                            } else {
                                $primary_id = (int) filter_var($primary_id, FILTER_SANITIZE_NUMBER_INT);
                                if ($primary_id == 0) {
                                   
                                    $log['result'] = 'error';
                                    $log['result_message'] = "Custom id not integer";
                                    $return["code"] = self::ERROR_CUSTOM;
                                    $return["message"] = "Custom id not integer";
                                    $this->log_collection->insertOne($log);
                                    return new JsonModel($return);
                                }
                            }
                            $tempArray['primary_id'] =$primary_id;
                            $tempJson = json_encode($tempArray);
                        $postedData['data'] = $tempJson;
                        }
                        
                        $urlForExport = is_array($reality['import_url']) ? $reality['import_url'] : [$reality['import_url']];
                        
                        foreach($urlForExport as $url){
                            ob_start();
                            $tmpx=['cityproperty'=>$postedData];
                            $this->log_collection->insertOne($tmpx);
                            $result = $this->realityService->export($postedData, $url);
                            ob_end_clean();

                            if ($result['statusCode'] == 200) {
                                $resultData = $result['data'];
                                $return["code"] = isset($resultData["code"]) ? $resultData["code"] : self::ERROR_CUSTOM;
                                $return["importId"] = isset($resultData["importId"]) ? $resultData["importId"] : null;
                                // $return["url"] = isset($resultData["url"]) ? $resultData["url"] : null;
                                $log['result'] = 'ok';
                                $log['result_data'] = $result;
                                if (isset($result['data']['error_message'])) {
                                    $return["message"] = $resultData['error_message'];
                                    $log['result'] = 'error';
                                    $log['result_data'] = $result;
                                }

                                if ($return["code"] == self::SUCCESS_INSERT || $return["code"] == self::SUCCESS_UPDATE) {
                                    $postedDataArray = json_decode($postedData['data'], true);
                                    if (isset($postedDataArray['images'])) {
                                        foreach ($postedDataArray['images'] as $index => $image) {
                                            if ($image['changed'] == true) {
                                             //   $this->realityService->addToQueue($primary_id, $reality['import_url'], 'image_advert', $image['url'],$tempArray['id'],$index);				
                                            }
                                        }
                                        $this->realityService->addToQueueAll($primary_id, $url, 'images_advert',$postedDataArray['images'],$tempArray['id']);
                                    }
                                    if (isset($postedDataArray['image'])) {
                                        if ($postedDataArray['image']['changed'] == true) {
                                            $this->realityService->addToQueue($postedDataArray["user_id"], $url, 'image_broker', $postedDataArray['image']['url']);
                                        }
                                    }
                                }
                            } else {
                                $log['result'] = 'error';
                                $log['error_message'] = $result['error_message'];
                                $log['result_message'] = 'Status code:' . $result['statusCode'] . $result['data'];
                                $return["code"] = self::ERROR_CUSTOM;
                                $return["message"] = "Internal error";
                            }
                        }
                    }
                    
                    if ($this->realityService->getImportType() == RealityService::IMPORT_TYPE_QUEUE) {
                        $log['result'] = 'processing';
                        $postedData['created'] = new UTCDateTime();
                        $this->queue_collection->insertOne($postedData);
                    }
                } else {
                    $log['result'] = 'error';
                    $log['result_message'] = 'Import is not allowed';
                    $return["code"] = self::ERROR_CUSTOM;
                    $return["message"] = "Import is not allowed";
                }
            }
            //$log['data']['data'] = json_decode($log['data']['data'],true);
            $log['data']['data'] = json_decode($postedData['data'], true);
            $time_end = microtime(true);
            $log['response_time'] = $time_end - $time_start;
            $this->log_collection->insertOne($log);

            return new JsonModel($return);
        } catch (Exception $ex) {
            $this->log_collection->insertOne($ex);
            exit;
        }
    }

    public function distributeAction() {
        try {
            
            
            
            register_shutdown_function(function () {
                $a = error_get_last();
                if ($a != null) {
                    $this->log_collection->insertOne($a);
                }
            });
            $resultedData=0;
            $errors=0;
            $limit=(int)$this->params()->fromQuery('limit',10);
            $cursor = $this->queue_collection->find([], ['limit' => $limit,'sort'=>['created'=>1]]);
            $cursored = iterator_to_array($cursor);
            foreach ($cursored as $request) {

                $dataForExport = [];
                $dataForExport['data'] = [];
                $dataForExport["data"]["id"] = $request["id"];
                $dataForExport["data"]["url"] = $request["data"];
                
                if ($request["type"] == 'advert') {
                    $dataForExport["action"] = 1;
                }
                if ($request["type"] == 'agent') {
                    $dataForExport["action"] = 2;
                }
                if ($request["type"] == 'image_advert') {
                    $dataForExport["action"] = 3;
                }
                if ($request["type"] == 'image_broker') {
                    $dataForExport["action"] = 4;
                }
                if ($request["type"] == 'images_advert') {
                    $dataForExport["action"] = 5;
                    $dataForExport["data"]["data"] = $request["data"];
                    unset($dataForExport["data"]["url"]);
                }
                
                $dataForExport["data"]["secondId"] = $request["secondId"];
                if(isset($request["indexInArray"])){
                    $dataForExport["data"]["indexInArray"] = $request["indexInArray"];
                }
                if(!isset($request['attempts'])){
                    $attempts = 1;
                }
                else{
                    $attempts = (int)$request['attempts']+1;
                }
                
                $dataForExport["data"] = json_encode($dataForExport["data"]);
                //echo $request["import_url"];
                $result = $this->realityService->export($dataForExport, $request["import_url"]);
                
                
                $resultData = null;
                if ($result['statusCode'] == 200) {
                    $resultData = ($result['data']);
                    
                    $code = isset($resultData["code"]) ? $resultData["code"] : self::ERROR_CUSTOM;

                    if ($code == self::SUCCESS_INSERT || $code == self::SUCCESS_UPDATE) {
                        $this->queue_collection->deleteOne(['_id' => $request['_id']]);
                        $resultedData++;
                    } else {
                        
                        
                        $this->queue_collection->updateOne(['_id' => $request['_id']],['$set' => ['attempts'=>$attempts,'created' => new UTCDateTime()]]);
                        $log = [];
                        $log['created'] = new UTCDateTime();
                        $log['action'] = 'realsoft_export_image';
                        $log['data'] = $dataForExport;
                        $log['response'] = $result;
                        $log['result'] = 'error';
                        $log['fromIp'] = $_SERVER['REMOTE_ADDR'];
                        $errors++;
                        $this->log_collection->insertOne($log);
                    }
                    
                    //return new JsonModel(['result' => 1]);
                }
                else{
                    $errors++;
                    $error = error_get_last();
                    if(!$error){
                        $error =$resultData;
                    }
                    
                    if($resultData==null){
                        $error = 'null response';
                    }
                    if(isset($result['data']['error'])){
                        $error = $result['data']['error'];
                    }
                    $this->queue_collection->updateOne(['_id' => $request['_id']],['$set' => ['attempts'=>$attempts,'created' => new UTCDateTime(),'error'=>print_r($error,true)]]);
                    throw new \Exception($resultData["error_message"],$resultData["code"]);
                }
            }
            
            $result= $resultedData>0 ? $resultedData : 'nothing to do';
            return new JsonModel(['result' => $result,'errors'=>$errors]);
        } catch (Exception $ex) {
            $this->log_collection->insertOne($ex);
            
            exit;
        }
    }
    public function jsonimportAction(){
        
        ini_set('max_execution_time', 60000);
        ini_set('memory_limit', '6000M');
        
       $postedData = $this->params()->fromPost();
       if($postedData){
       $json=(json_decode(file_get_contents($_FILES['data']['tmp_name'])));
       $i=1;
       
       
       $handles = [];
       $multihandles = [];
       
       echo 'pocet nehn.'.count($json)."<br>";
      
      foreach($json as $real){
          if(!isset($multihandles[$i])){
            $handles[$i]=[];
            $multihandles[$i] = curl_multi_init();
            $x=1;
            echo 'vytvaram '.$i.' multihandle'."<br>";
        }
          
        $postedData['data'] = json_encode($real);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://realsoft.realvia.sk/import");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$postedData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_multi_add_handle($multihandles[$i],$ch);
        $x++;
        $handles[$i][]=$ch;
        echo 'vkladam do '.$i.' '.$x.' handle'."<br>";
        if($x==15){
            $i++;
        }
        if(count($multihandles)==2){
            //break;
        }
      }
     /* foreach($handles as $index=>$a){
          echo $index.' '. count($a)."<br>";
      }*/
     
      
      //execute the multi handle
foreach($multihandles as $indexM=>$mh){	  

 echo $indexM.' '."<br>";
 $x=0;
    do {
        echo "&nbsp;&nbsp;".++$x."<br>";
        $status = curl_multi_exec($mh, $active);
        if ($active) {
            // Wait a short time for more activity
            curl_multi_select($mh);
        }
    } while ($active && $status == CURLM_OK);
    sleep(5);
    foreach($handles[$indexM] as $ch){
        curl_multi_remove_handle($mh, $ch);
        }	
    curl_multi_close($mh);
    
    if($x%5==0){
        sleep(10);	
        echo "&nbsp;&nbsp;waiting 5<br>";
    }
    if($x%15==0){
        sleep(30);
        echo "&nbsp;&nbsp;waiting 30<br>";		
    }
    if($x%30==0){
        sleep(30);	
        echo "&nbsp;&nbsp;waiting 30<br>";
    }
    if($x%45==0){
        sleep(30);	
        echo "&nbsp;&nbsp;waiting 30<br>";
    }
    if($x%60==0){
        sleep(30);	
        echo "&nbsp;&nbsp;waiting 30<br>";
    }
     
}	
echo 'ooooooooooooooooooooooooooookkkk';
    /*  
$server_output = curl_exec($ch);
        curl_close ($ch);
        echo ++$i."<br>";
        print_r($server_output);
*/
        
       }
    }
    
    public function lastLogAction() {
        /*$url = "https://maps.google.com/maps/api/geocode/json?key=AIzaSyCq4FClYeGXfJY8k11uVrZiaFF6veD1Qnw&sensor=false&address=Pego,spain";
        $response = file_get_contents($url);
        $json = json_decode($response, true);
        var_dump($json);*/
        $query = [];
        $id = $this->params()->fromQuery('id',null);
        if($id){
            $query['data.data.primary_id']=(int)$id;
        }
        $data=$this->log_collection->findOne($query,['sort'=>['created'=>-1]]);
        echo '<pre>';
        print_r($data);
        exit;
    }
    public function reImportAction() {
        $query = [];
        $id = $this->params()->fromQuery('id',null);
        if($id){
            $query['data.data.primary_id']=(int)$id;
        }
        $data=$this->log_collection->findOne($query,['sort'=>['created'=>-1]]);
        //var_dump($data);
        echo '<pre>';
        $result = $this->realityService->export($data,'https://realsoft.realvia.sk/import');
        var_dump($result);
        exit;
    }
}
