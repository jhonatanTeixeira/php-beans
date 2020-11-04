<?php

namespace PhpBeans\Event;

use Psr\EventDispatcher\StoppableEventInterface;

class BeanEvent implements StoppableEventInterface
{
    protected object $bean;
    
    protected string $name;
    
    protected bool $stoped = false;
    
    public function __construct(object $bean, string $name) {
        $this->bean = $bean;
        $this->name = $name;
    }
    
    public function getBean(): object {
        return $this->bean;
    }

    public function getName(): string {
        return $this->name;
    }
    
    public function isPropagationStopped(): bool {
        return $this->stoped;
    }

    public function stopPropagation() {
        $this->stoped = true;
    }
}
