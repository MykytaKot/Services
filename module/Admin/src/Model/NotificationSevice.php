<?
namespace Admin\Model;
use MongoDB\Collection;

use MongoDB\BSON\ObjectId;
class NotificationSevice {


    protected $notificationCollection;

    public function __construct(Collection $notificationCollection) {
        $this->notificationCollection = $notificationCollection;
       
    }
    public function getAll(){
        $cursor = $this->notificationCollection->find();
        $items = [];
        foreach ($cursor as $document) {
            $items[] = $document;
        }
        $items = array_reverse($items);
        
        return $items;
    }
    public function createNewItem(array $data) {
        $insertOneResult =$this->notificationCollection->insertOne($data);
        return $insertOneResult->getInsertedId();
    }
    public function delete($id){
        $this->notificationCollection->deleteOne(['_id' => new ObjectId($id)]);
    }
    
}