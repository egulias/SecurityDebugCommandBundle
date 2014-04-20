<?php

namespace Egulias\SecurityDebugCommandBundle\EventListener;

use Egulias\SecurityDebugCommandBundle\DataCollector\FirewallCollector;
use Egulias\SecurityDebugCommandBundle\DataCollector\SecurityDebugDataCollector;
use Sensio\Bundle\FrameworkExtraBundle\EventListener\SecurityListener;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class SecurityListenersDebugListener
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class SecurityListenersDebugListener
{
    private $sensioExtraSecurityListener;
    private $collector;

    public function __construct(SecurityListener $securityListener, SecurityDebugDataCollector $collector)
    {
        $this->sensioExtraSecurityListener = $securityListener;
        $this->collector = $collector;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof AccessDeniedException) {
            return;
        }
        $controller = $event->getRequest()->get('_controller');
        $controllerEvent = new FilterControllerEvent(
            $event->getKernel(),
            $controller,
            $event->getRequest(),
            $event->getRequestType()
        );
        try {
            $this->sensioExtraSecurityListener->onKernelController($controllerEvent);
            $this->collector->addSecurityListeners(
                array(
                    array(
                        'class' => get_class($this->sensioExtraSecurityListener),
                        'result' => SecurityDebugDataCollector::GRANTED
                    )
                )
            );
        } catch (AccessDeniedException $e) {
            $this->collector->addSecurityListeners(
                array(
                    array(
                        'class' => get_class($this->sensioExtraSecurityListener),
                        'result' => SecurityDebugDataCollector::DENIED
                    )
                )
            );
        }

    }
}
