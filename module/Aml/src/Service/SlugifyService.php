<?php

declare(strict_types=1);

namespace Aml\Service;

use Cocur\Slugify\Slugify;

class SlugifyService
{
    private $slug;

    public function __construct()
    {
        $this->setSlug(new Slugify([
            'rulesets' => [
                // Don't change, must be first rule
                'default',
                // Additional rules
                'slovak',
                'czech',
                'hungarian'
            ],
            'trim' => false,
            'separator' => ' '
        ]));
    }

    ///////////////////////////////////////
    ////////// Getters & Setters //////////
    ///////////////////////////////////////

    private function getSlug(): Slugify
    {
        return $this->slug;
    }
    private function setSlug(Slugify $input): void
    {
        $this->slug = $input;
    }

    ////////////////////////////////////
    ////////// Public Methods //////////
    ////////////////////////////////////

    public function get(?string $input): string
    {
        return trim($this->getSlug()->slugify($input));
    }
}
