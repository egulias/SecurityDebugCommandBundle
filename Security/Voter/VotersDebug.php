<?php

namespace Egulias\SecurityDebugCommandBundle\Security\Voter;

use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Egulias\SecurityDebugCommandBundle\Security\DebugUtils;

class VotersDebug
{
    protected $strategy;
    protected $objectIdentity = null;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
        $this->rflClass = new \ReflectionClass(get_class($decisionManager));
    }

    public function setAcl($class, $id)
    {
        $object = new $class;
        $object->setId($id);
        $this->objectIdentity = ObjectIdentity::fromDomainObject($object);
    }

    public function getVotersVote($token)
    {
        $rolesStrings = DebugUtils::getRolesStrings($token);
        $rflVoters = $this->rflClass->getProperty('voters');
        $rflVoters->setAccessible(true);
        $voters = $rflVoters->getValue($this->decisionManager);
        $votes = array();
        foreach ($voters as $voter) {
            $votes[] = array(get_class($voter), $voter->vote($token, $this->objectIdentity, $rolesStrings));
        }
        return $votes;
    }
}
