<?php

namespace Admin\Model;
use MongoDB\Collection;

use MongoDB\BSON\ObjectId;
class AdminService {


    protected $adminCollection;

    public function __construct(Collection $adminCollection) {
        $this->adminCollection = $adminCollection;
       
    }

    public function getOne($id){
        $admin = $this->adminCollection->findOne(array('_id' => new ObjectId($id)));
        if($admin){return $admin;}
        return NULL;

    }

    public function getAll(){
        $cursor = $this->adminCollection->find();
        $items = [];
        foreach ($cursor as $document) {
            $items[] = $document;
        }
        $items = array_reverse($items);
        
        return $items;
    }

    public function createNewItem(array $data) {
        $insertOneResult =$this->adminCollection->insertOne([
            'key' => $data['key'],
            'name' => $data['name'],
            'desc' => $data['desc']
        ]);
        return $insertOneResult->getInsertedId();
    }

    public function delete($id){
        $this->adminCollection->deleteOne(['_id' => new ObjectId($id)]);
    }
    public function update($id, $data){
        $this->adminCollection->updateOne(['_id' => new ObjectId($id)], ['$set' => $data]);
    }
    
}