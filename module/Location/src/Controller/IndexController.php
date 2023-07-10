<?php

namespace Location\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Location\Model\LocationService;

class IndexController extends AbstractRestfulController {

    private $locationService;

    public function __construct(LocationService $locationService) {
        $this->locationService = $locationService;
    }

    public function getList() {
        $return = ['data'=>[],'count'=>0];
        try {
            $params = $this->params()->fromQuery();
            
            if(count($params)>0){
                $params['string'] = isset($params['string']) ? trim($params['string']) : null;
                $params['name'] = isset($params['name']) ? trim($params['name']) : null;
                $limit = isset($params['limit']) ? (int)$params['limit'] : 75;
                $params['types'] = isset($params['type']) ? explode(',',$params['type']) : ['all']; 
                
                $return['data'] = $this->locationService->find($params,$limit);
                $return['count'] = count($return['data']);
            }
        } catch (\Exception $e) {
             $return['error'] = $e->getMessage();
        }
        
        return new JsonModel($return);
    }

    public function get($id) {
        
        
        $ret = ['data'=>null,'count'=>0];
        try {
            $ret['data'] = $this->locationService->findOne($id);
            if($ret['data']===null){
                $ret['count'] = 0;
            }
            else{
                $ret['count'] = 1;
            }
            
        } catch (\Exception $e) {
          $return['error'] = $e->getMessage();
        }
        return new JsonModel($ret);
    }

}
