<?php

namespace Egulias\SecurityDebugCommandBundle\DataCollector;

use Egulias\SecurityDebugCommandBundle\Security\Voter\VotersDebug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Class SecurityDebugDataCollector
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class SecurityDebugDataCollector extends DataCollector
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

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $votersDebug = new VotersDebug($this->accessDecisionManager);
        $token = $this->securityContext->getToken();

        if (!$token || !$token->isAuthenticated()) {
            return;
        }

        $votes = $votersDebug->getVotersVote($token);

        foreach ($votes as $vote) {
            switch ($vote[1]) {
                case VoterInterface::ACCESS_ABSTAIN:
                    $this->data[] = array('class' => $vote[0], 'vote' => 'ABSTAIN');
                    break;
                case VoterInterface::ACCESS_GRANTED:
                    $this->data[] = array('class' => $vote[0], 'vote' => 'GRANTED');
                    break;
                case VoterInterface::ACCESS_DENIED:
                    $this->data[] = array('class' => $vote[0], 'vote' => 'DENIED');
                    break;
            }
        }
    }

    public function getVoters()
    {
        return $this->data;
    }

    public function getName()
    {
        return 'egulias_security_debug';
    }
} 