<?php

namespace Egulias\SecurityDebugCommandBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class SecurityDebugDataCollector
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class SecurityDebugDataCollector extends DataCollector
{
    const GRANTED = 1;
    const ABSTAIN = 0;
    const DENIED = -1;

    private $votersCollector;
    private $firewallCollector;

    public function __construct(VotersCollector $votersCollector, FirewallCollector $firewallCollector)
    {
        $this->votersCollector = $votersCollector;
        $this->firewallCollector = $firewallCollector;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['voters'] = $this->votersCollector->collect();
        if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            $this->data['firewall'] = $this->firewallCollector->collect($request, $exception);
        }
    }

    public function addSecurityListeners($listeners)
    {
        if (!is_array($this->data['listeners'])) {
            $this->data['listeners'] = array();
        }
        $this->data['listeners'] = array_merge($this->data['listeners'], $listeners);
    }

    public function getVoters()
    {
        return $this->data['voters'];
    }

    public function getFirewall()
    {
        if (!isset($this->data['firewall'])) {
            $this->data['firewall'] = array();
        }
        return $this->data['firewall'];
    }

    public function getListeners()
    {
        if (!isset($this->data['listeners'])) {
            $this->data['listeners'] = array();
        }
        return $this->data['listeners'];
    }

    public function getName()
    {
        return 'egulias_security_debug';
    }
}
