<?php

namespace Egulias\SecurityDebugCommandBundle\DataCollector;

use Egulias\SecurityDebugCommandBundle\HttpKernel\SimpleHttpKernel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Class AccessDeniedListener
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class FirewallCollector
{
    const HAS_RESPONSE = SecurityDebugDataCollector::DENIED;

    private $securityContext;
    private $container;

    public function __construct(
        SecurityContextInterface $securityContext,
        Container $container
    ) {
        $this->securityContext = $securityContext;
        //Container dependency is a bad thing. This is to be refactored to a compiler pass
        //where all the firewall providers will be fetched
        $this->container = $container;
    }

    public function collect(Request $request, \Exception $exception)
    {
        $token = $this->securityContext->getToken();
        if (!method_exists($token, 'getProviderKey')) {
            return;
        }

        $providerKey = $token->getProviderKey();
        $map = $this->container->get('security.firewall.map.context.' . $providerKey);
        $firewallContext = $map->getContext();
        $event = new GetResponseEvent(
            new SimpleHttpKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $firewalls = array();
        foreach ($firewallContext[0] as $i => $listener) {
            $firewalls[$i]= array('class' => get_class($listener), 'result' => SecurityDebugDataCollector::GRANTED);
            try {
                $listener->handle($event);
            } catch (AccessDeniedException $ade) {
                $firewalls[$i]['result'] = SecurityDebugDataCollector::DENIED;
                break;
            }
            if ($event->hasResponse()) {
                $firewalls[$i]['result'] = self::HAS_RESPONSE;
                break;
            }
        }

        return $firewalls;
    }
}
