<?php

declare(strict_types=1);

namespace Aml\Entity;

use DateTime;
use MongoDB\BSON\UTCDateTime;

class AmlList
{
    private $id;
    private $active;
    private $label;
    private $logs = [];
    private $name;
    private $order;
    private $updated;

    ///////////////////////////////////////
    ////////// Getters & Setters //////////
    ///////////////////////////////////////

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $input): void
    {
        $this->id = $input;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }
    public function setActive(bool $input): void
    {
        $this->active = $input;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(string $input): void
    {
        $this->label = $input;
    }

    public function getLogs(): ?array
    {
        return $this->logs;
    }
    public function setLogs(array $input): void
    {
        $this->logs = $input;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(?string $input): void
    {
        $this->name = $input;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }
    public function setOrder(int $input): void
    {
        $this->order = $input;
    }

    public function getUpdated(): ?UTCDateTime
    {
        return $this->updated;
    }
    public function setUpdated(?UTCDateTime $input = null): void
    {
        if (empty($input)) {
            $this->updated = new UTCDateTime();
        } else {
            $this->updated = $input;
        }
    }

    ////////////////////////////////////
    ////////// Public Methods //////////
    ////////////////////////////////////

    public function addLog(array $input): void
    {
        $logs = $this->getLogs();
        if (empty($logs) && ! is_array($logs)) {
            $logs = [];
        }
        if (! empty($input)) {
            $logs[] = $input;
            $this->setLogs($logs);
        }
    }

    public function getAmlList() : array
    {
        $return = [
            'id'      => $this->getId(),
            'active'  => $this->getActive(),
            'label'   => $this->getLabel(),
            'logs'    => $this->getLogs(),
            'name'    => $this->getName(),
            'order'   => $this->getOrder(),
            'updated' => $this->getUpdated(),
        ];
        return $return;
    }

    public function setAmlList(array $input) : void
    {
        if (! empty($input['id'])) {
            $this->setId($input['id']);
        }

        if (isset($input['active'])) {
            $this->setActive($input['active']);
        }

        if (! empty($input['label'])) {
            $this->setLabel($input['label']);
        }

        if (! empty($input['logs'])) {
            $this->setLogs($input['logs']);
        }

        if (! empty($input['name'])) {
            $this->setName($input['name']);
        }

        if (! empty($input['order'])) {
            $this->setOrder($input['order']);
        }

        if (! empty($input['updated'])) {
            $this->setUpdated($input['updated']);
        }
    }
}
