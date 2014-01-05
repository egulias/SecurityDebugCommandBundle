<?php

namespace Egulias\SecurityDebugCommandBundle\Security\Authorization;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Egulias\SecurityDebugCommandBundle\Security\DebugUtils;

class DecisionManagerDebug
{
    protected $strategy;
    protected $objectIdentity = null;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
        $this->rflClass = new \ReflectionClass(get_class($decisionManager));
    }

    public function getDecisionManagerStrategy()
    {
        if (!$this->strategy) {
            $rflStrategy = $this->rflClass->getProperty('strategy');
            $rflStrategy->setAccessible(true);
            $this->strategy = $rflStrategy->getValue($this->decisionManager);
        }

        return $this->strategy;
    }

    public function setDecisionManagerStrategy($strategy)
    {
        $this->strategy = $strategy;
        $rflStrategy = $this->rflClass->getProperty('strategy');
        $rflStrategy->setAccessible(true);
        $rflStrategy->setValue($this->decisionManager, $strategy);
        return $this->strategy;
    }

    public function setAcl($class, $id)
    {
        $object = new $class;
        $object->setId($id);
        $this->objectIdentity = ObjectIdentity::fromDomainObject($object);
    }

    public function decide(TokenInterface $token)
    {
        return $this->decisionManager->decide($token, DebugUtils::getRolesStrings($token), $this->objectIdentity);
    }
}
