<?php

namespace Realsoft;

use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\I18n\Translator\Resources;

class Module
{
    
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}