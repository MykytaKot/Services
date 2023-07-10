<?php

declare(strict_types=1);

namespace Aml\Service;

use Aml\Mapper\AmlMapper;
use Aml\Service\EmailService;

class AmlService
{
    private $email;
    private $mapper;

    public function __construct(AmlMapper $mapper, EmailService $email)
    {
        $this->setEmail($email);
        $this->setMapper($mapper);
    }

    ///////////////////////////////////////
    ////////// Getters & Setters //////////
    ///////////////////////////////////////

    private function getEmail(): EmailService
    {
        return $this->email;
    }
    private function setEmail(EmailService $input): void
    {
        $this->email = $input;
    }

    private function getMapper(): AmlMapper
    {
        return $this->mapper;
    }
    private function setMapper(AmlMapper $input): void
    {
        $this->mapper = $input;
    }

    /////////////////////////////////////
    ////////// Private Methods //////////
    /////////////////////////////////////

    private function hasError(?array $input): ?bool
    {
        $return = null;
        if (is_array($input)) {
            $return = false;
        }
        if (! empty($input)) {
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $sub_key => $sub_value) {
                        if ($sub_key == 'error') {
                            $return = true;
                            break 2;
                        }
                    }
                } else {
                    if ($key == 'error') {
                        $return = true;
                        break;
                    }
                }
            }
        }
        return $return;
    }

    ////////////////////////////////////
    ////////// Public Methods //////////
    ////////////////////////////////////

    public function lists(): array
    {
        $response = $this->getMapper()->lists();
        return $response;
    }

    public function search(?string $fullname, ?string $birthdate): array
    {
        $response = $this->getMapper()->search($fullname, $birthdate);
        return $response;
    }

    public function update(string $input): array
    {
        switch ($input) {
            case 'all':
                $response = $this->getMapper()->updateAll();
                break;
            default:
                $response = $this->getMapper()->update($input);
        }
        $has_error = $this->hasError($response);
        if (! empty($has_error)) {
            $this->getEmail()->send($response);
        }
        return $response;
    }
}
