<?php

namespace Egulias\SecurityDebugCommandBundle\DataCollector;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Egulias\SecurityDebugCommandBundle\Security\Voter\VotersDebug;

/**
 * Class VotersCollector
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class VotersCollector
{
    private $accessDecisionManager;
    private $securityContext;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        SecurityContextInterface $securityContext
    ) {
        $this->accessDecisionManager = $decisionManager;
        $this->securityContext = $securityContext;
    }

    public function collect()
    {
        $votersDebug = new VotersDebug($this->accessDecisionManager);
        $token = $this->securityContext->getToken();

        if (!$token || !$token->isAuthenticated()) {
            return;
        }

        $votes = $votersDebug->getVotersVote($token);
        $voters = array();

        foreach ($votes as $vote) {
            switch ($vote[1]) {
                case VoterInterface::ACCESS_ABSTAIN:
                    $voters[] = array('class' => $vote[0], 'vote' => SecurityDebugDataCollector::ABSTAIN);
                    break;
                case VoterInterface::ACCESS_GRANTED:
                    $voters[] = array('class' => $vote[0], 'vote' => SecurityDebugDataCollector::GRANTED);
                    break;
                case VoterInterface::ACCESS_DENIED:
                    $voters[] = array('class' => $vote[0], 'vote' => SecurityDebugDataCollector::DENIED);
                    break;
            }
        }

        return $voters;
    }
}
