<?php

namespace Egulias\SecurityDebugCommandBundle\DataCollector;

use Egulias\SecurityDebugCommandBundle\HttpKernel\SimpleHttpKernel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class AccessDeniedListener
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class FirewallCollector
{
    const HAS_RESPONSE = SecurityDebugDataCollector::DENIED;

    private $tokenStorage;
    private $container;

    public function __construct(
        TokenStorage $tokenStorage,
        Container $container
    ) {
        $this->tokenStorage = $tokenStorage;
        //Container dependency is a bad thing. This is to be refactored to a compiler pass
        //where all the firewall providers will be fetched
        $this->container = $container;
    }

    public function collect(Request $request, \Exception $exception)
    {
        $token = $this->tokenStorage->getToken();
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
