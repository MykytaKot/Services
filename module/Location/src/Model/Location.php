<?php

namespace Location\Model;

class Location {

    protected $rawData;
    protected $locationProvider;
    protected $pathObjects = array();
    protected static $pathOrder = array(0 => 'štát', 1 => 'kraj', 2 => 'okres', 3 => 'obec', 4 => 'mestská časť');

    public function __construct(array $rawData, $locationProvider) {
        $this->rawData = $rawData;
        $this->locationProvider = $locationProvider;
        $this->preparePathObject();
    }

    public function getId() {
        if (isset($this->rawData['_id'])) {
            return (string) $this->rawData['_id'];
        } elseif (isset($this->rawData['id'])) {
            return $this->rawData['id'];
        }
    }

    public function getName() {
        return isset($this->rawData['name']) ? $this->rawData['name'] : '';
    }

    public function getLabel() {
        return isset($this->rawData['label']) ? $this->rawData['label'] : '';
    }

    public function getType() {
        return isset($this->rawData['type']) ? $this->rawData['type'] : '';
    }

    public function getUsed() {
        return isset($this->rawData['used']) ? $this->rawData['used'] : 0;
    }

    public function getPsc() {
        return isset($this->rawData['psc']) ? $this->rawData['psc'] : null;
    }

    public function getPath() {
        return isset($this->rawData['path']) ? $this->rawData['path'] : '';
    }

    public function getCoordinates() {
        return isset($this->rawData['coordinates']) ? $this->rawData['coordinates'] : null;
    }

    public function getParent() {
        $key = array_search($this->rawData['type'], self::$pathOrder);
        if ($key == 0 || !$key) {
            return false;
        }
        return $this->pathObjects[self::$pathOrder[($key - 1)]];
    }

    public function hasParent() {
        $key = array_search($this->rawData['type'], self::$pathOrder);
        if ($key == 0 || !$key) {
            return false;
        }
        return true;
    }

    private function preparePathObject() {
        $path = explode(',', $this->rawData['path']);

        foreach ($path as $loc) {
            if(empty($loc)){
                continue;
            }
            $loc = $this->locationProvider->getLocation(new \MongoId($loc));
            if ($loc) {
                $this->pathObjects[$loc['type']] = new Location($loc, $this->locationProvider);
            }
        }
    }

}
