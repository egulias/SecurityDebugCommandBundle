<?php

namespace Egulias\SecurityDebugCommandBundle\Security\Voter;

use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Egulias\SecurityDebugCommandBundle\Security\DebugUtils;

class VotersDebug
{
    protected $strategy;
    protected $objectIdentity = null;

    /**
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
        $this->rflClass = new \ReflectionClass(get_class($decisionManager));
    }

    /**
     *
     * @param string  $class
     * @param string  $id
     */
    public function setAcl($class, $id)
    {
        $object = new $class;
        $object->setId($id);
        $this->objectIdentity = ObjectIdentity::fromDomainObject($object);
    }

    /**
     *
     * @param Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     *
     * @return array
     */
    public function getVotersVote(TokenInterface $token)
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
