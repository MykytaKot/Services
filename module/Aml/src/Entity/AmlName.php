<?php

declare(strict_types=1);

namespace Aml\Entity;

class AmlName
{
    private $birthdate;
    private $fullname;
    private $list_id;

    ///////////////////////////////////////
    ////////// Getters & Setters //////////
    ///////////////////////////////////////

    public function getBirthdate(): ?string
    {
        return $this->birthdate;
    }
    public function setBirthdate(?string $input): void
    {
        $this->birthdate = $input;
    }

    public function getFullname(): string
    {
        return $this->fullname;
    }
    public function setFullname(string $input): void
    {
        $this->fullname = $input;
    }

    public function getListId(): int
    {
        return $this->list_id;
    }
    public function setListId(int $input): void
    {
        $this->list_id = $input;
    }

    /////////////////////////////////////
    ////////// Private Methods //////////
    /////////////////////////////////////

    private function reset(): void
    {
        $this->birthdate = null;
        $this->fullname = null;
        $this->list_id = null;
    }

    ////////////////////////////////////
    ////////// Public Methods //////////
    ////////////////////////////////////

    public function getAmlName() : array
    {
        $return = [
            'birthdate' => $this->getBirthdate(),
            'fullname' => $this->getFullname(),
            'list_id'  => $this->getListId(),
        ];
        return $return;
    }

    public function setAmlName(array $input) : void
    {
        $this->reset();

        if (! empty($input['birthdate'])) {
            $this->setBirthdate($input['birthdate']);
        }

        if (! empty($input['fullname'])) {
            $this->setFullname($input['fullname']);
        }

        if (! empty($input['list_id'])) {
            $this->setListId($input['list_id']);
        }
    }
}
