<?php

namespace Location\Model;

use MongoDB\Collection;
use Application\Core\Text;
use MongoDB\BSON\ObjectId;

class LocationService {

    protected $locationCollection;
    protected $streetCollection;

    public function __construct(Collection $locationCollection, Collection $streetCollection) {
        $this->locationCollection = $locationCollection;
        $this->streetCollection = $streetCollection;
    }
    public function findOne($id) {
        $location = $this->locationCollection->findOne(array('_id' => new ObjectId($id)));
        if($location){return $location;}
        $street = $this->streetCollection->findOne(array('_id' => new ObjectId($id)));
        if($street){return $street;}
        return null;
    }
    
    public function find(Array $params, $limit) {
        $result = [];

        //1.query mesta,mestske casti
        if($this->isLocationCollection($params['types'])){
            
            $query = $this->buildQueryForLocations($params,$limit);
            $cursor = $this->locationCollection->find($query, ['limit' => $limit]);
            foreach ($cursor as $data) {
                $result[] = $this->transformLocationText($data);
            }
        }
        
        //2.query streets
        if($this->isStreetCollection($params['types'])){
            $streetsQuery = $this->buildQueryForStreets($params);
            $cursor = $this->streetCollection->find($streetsQuery, ['limit' => $limit]);

            foreach ($cursor as $data) {
                $result[] = $this->transformLocationStreetText($data);
            }
        }
        
        //3.query - kombinacie
        if($this->isStreetCollection($params['types']) && $this->isLocationCollection($params['types'])){
            if (count($result) == 0 && !empty($params['string'])) {
                $string = str_replace(",", " ", $params['string']);
                $substrings = explode(" ", $string);
            
                        
                if (count($substrings) > 1) {
                    $tmps = [];
                    //1.najdem vsetky vysledky pre vsetky slova
                    foreach ($substrings as $value) {
                        if($value==''){continue;}
                        $array = $this->find(['string'=>$value,'types'=>['obec','ulica']],500);
                        
                        $tmps = array_merge($tmps, $array);
                    }
                        
                    //2.rozdelim ich na obce a ulice
                    $towns = [];
                    $streets = [];

                    foreach ($tmps as $item) {
                        if ($item['type'] == 'obec') {
                            $towns[] = $item;
                        }
                        if ($item['type'] == 'ulica') {
                            $streets[] = $item;
                        }
                    }

                    foreach ($towns as $town) {

                        foreach ($streets as $street) {
                            if ($town['id'] == $street['idObecLocation']) {
                                $result[] = $street;
                            }
                        }
                    }
                }
            }
        }
        
    return $result;
    
    }

    /**
     * Get page by id
     * @param \ObjectId $id
     * @return array
     */
    public function getLocation($id) {
        $location = $this->locationCollection->findOne(array('_id' => new ObjectId($id)));
        return $location;
    }

    private function buildQueryForLocations($params,$limit=0) {
    
        $query = [];
        if (!empty($params['string'])) {
            if($limit==1){	
                $regex = Text::regexFromString($params['string'],'^','$');
                $query['name'] =array('$regex' => $regex);;
            
            }
            else{
                $regex = Text::regexFromString($params['string']);
                $query['name'] = array('$regex' => $regex);
            }
            
        }
        if (!empty($params['name'])) {
            if($limit==1){	
                $regex = Text::regexFromString($params['name'],'^');
                $query['name'] = array('$regex' => $regex);
            
            }
            else{
                $regex = Text::regexFromString($params['name']);
                $query['name'] = array('$regex' => $regex);
            }
            
        }
        if(isset($params['realsoft'])){
            $query['realsoft'] = $params['realsoft'];
        }
        if(isset($params['backoffice'])){
            $query['backoffice'] = $params['backoffice'];
        }		
        if (!in_array('all',$params['types'])) {
            
            if(count($params['types'])==1){
                $query['type'] = $params['types'][0];
            }
            else{
                $query['$or'] = array();
                foreach($params['types'] as $type){
                    $query['$or'][]=array('type' => $type);
                }
                
            }
                
         } /*else {
                 $query['$or'] = array(array('type' => 'štát'), array('type' => 'kraj'), array('type' => 'okres'), array('type' => 'obec'), array('type' => 'mestská časť'));
        }*/
        if(isset($params['path'])){
            $query['path'] = $params['path'];
        }
        
        return $query;
    }
    private function buildQueryForStreets($params) {

        $query = [];
        if (!empty($params['string'])) {
            $regex = Text::regexFromString($params['string']);
                $query['street'] = array('$regex' => $regex);      
        }
        if(isset($params['path'])){
            $query['path'] = $params['path'];
        }
        return $query;
    }
    /**
     * Build query for mongo to fetch pages from db by filter
     * @param array $filter
     * @return array
     */
    /* private function buildQuery(array $filter) {
      $query = array();

      if (isset($filter['type'])) {
      $query['type'] = $filter['type'];
      }

      if (isset($filter['path'])) {
      $query['path'] = $filter['path'];
      }

      if (isset($filter['name'])) {
      $query['name'] = $filter['name'];
      }

      return $query;
      } */

    private function transform(array $data) {
        $data['id'] = (string) $data['_id'];
        unset($data['_id']);
        unset($data['used']);
        unset($data['readOnly']);
        return $data;
    }

    private function transformLocationText(array $data) {

        $path = explode(",", $data['path']);
        if ($data['type'] == 'obec' || $data['type'] == 'mestská časť') {
            if (empty($data['full_name'])) {
                $parentLocation = $this->getLocation(end($path));

                if ($parentLocation['type'] == 'obec') {
                    $full_name = 'lokalita ' . $data['name'] . ', obec ' . $parentLocation['name'];
                } elseif ($parentLocation['type'] == 'okres') {
                    $full_name = $data['name'] . ' (okres ' . $parentLocation['name'] . ')';
                } else {
                    $full_name = $data['name'];
                }
                $this->locationCollection->updateOne(['_id' => new ObjectId($data['_id'])], ['$set' => ['full_name' => $full_name]]);
                $data['full_name'] = $full_name;
            }
            $data['path'] .= ',' . (string) $data['_id'];
        } else {
            if (empty($data['full_name'])) {
                $data['full_name'] = ucfirst($data['type']) . ' ' . $data['name'];
                $this->locationCollection->updateOne(['_id' => new ObjectId($data['_id'])], ['$set' => ['full_name' => $data['full_name']]]);
            }
        }
        return $this->transform($data);
    }

    private function transformLocationStreetText(array $data) {
        if (empty($data['full_name'])) {
            $parentLocation = $this->getLocation((string) $data['idObecLocation']);
            if (!isset($data['coordinates'])) {
                $data['coordinates'] = $parentLocation['coordinates'];
            }
            $data['full_name'] = $parentLocation['name'] . ', ul. ' . $data['street'];
            if (!empty($parentLocation['psc'])) {
                $data['psc'] = $parentLocation['psc'];
            }
            $data['path'] = $parentLocation['path'] . ',' . (string) $data['idObecLocation'];
            $this->streetCollection->updateOne(['_id' => new ObjectId($data['_id'])], ['$set' => $data]);
        }
        $data['type'] = 'ulica';
        $data['idObecLocation'] = (string) $data['idObecLocation'];

        return $this->transform($data);
    }
        
    private function isLocationCollection(Array $types){

        if(in_array('all',$types)){
            return true;
        }
        if(in_array('štát',$types) || in_array('kraj',$types) || in_array('okres',$types) || in_array('obec',$types) || in_array('mestská časť',$types)){
            return true;
        }
        return false;
    }	
    private function isStreetCollection(Array $types){
        if(in_array('all',$types)){
            return true;
        }
        if(in_array('ulica',$types)){
            return true;
        }
        return false;
    }
    public function getWithoutCoords() {
        $cursor= $this->locationCollection->find(['path'=>['$regex'=>'593e48466c99e08c2d00131b'],'coordinates' => ['$exists'=>0]]);
        foreach ($cursor as $data) {
                $result[] = $this->transformLocationText($data);
            }
            return $result;
        //return $this->locationCollection->find(['_id' => new ObjectId($data['_id'])], ['$set' => ['full_name' => $full_name]]);
    }
    
    public function saveCoords($id,$cords){
        
        $this->locationCollection->updateOne(['_id' => new ObjectId($id)], ['$set' => ['coordinates' => $cords]]);
    }
}
