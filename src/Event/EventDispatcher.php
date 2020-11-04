<?php

namespace PhpBeans\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    private $listeners = [];
    
    public function dispatch(object $event) {
        foreach ($this->getListenersForEvent($event) as $listener) {
            $listener($event);
        } 
    }

    public function getListenersForEvent(object $event): iterable {
        return $this->listeners[get_class($event)] ?? [];
    }
    
    public function registerListener(callable $listener) {
        $listener = \Closure::fromCallable($listener);
        
        $reflectionMethod = new \ReflectionFunction($listener);
        
        $parameters = $reflectionMethod->getParameters();
        $parametersCount = count($parameters);
        
        if ($parametersCount > 1 || $parametersCount == 0) {
            throw new \BadMethodCallException("listener must have exactly 1 parameter {$parametersCount} detected");
        }
        
        /* @var $eventClass \ReflectionClass */
        $eventClass = $parameters[0]->getClass();
        
        if (!$eventClass) {
            throw new \BadMethodCallException("listener parameter must br type hinted");
        }
        
        if (!isset($this->listeners[$eventClass->name])) {
            $this->listeners[$eventClass->name] = [];
        }
        
        $this->listeners[$eventClass->name][] = $listener;
    }
}
