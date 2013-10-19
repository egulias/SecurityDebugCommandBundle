<?php

namespace Egulias\SecurityDebugCommandBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Egulias\SecurityDebugCommandBundle\HttpKernel\SimpleHttpKernel;

/**
 * @author Eduardo Gulias <me@egulias.com>
 */
class SecurityDebugFirewallsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('security:debug:firewalls')
        ->setDescription('Debug Security firewalls')
        ->setDefinition(
            array(
                new InputArgument('uri', InputArgument::REQUIRED, "The exact URI you have in the firewall"),
                new InputArgument('firewall', InputArgument::REQUIRED, "Firewall name"),
                new InputArgument('username', InputArgument::REQUIRED, "User to test"),
            )
        )
        ->setHelp(
            <<<EOF
The <info>security:debug:firewalls</info> calls votings over differents elements

EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$securityContext = $this->getContainer()->get('security.firewall.map.context.secured_area')->getContext();

        $uri = '/demo/secured/hello/admin/';
        $uri = $input->getArgument('uri');
        $firewallProvider = $input->getArgument('firewall');
        $username = $input->getArgument('username');

        $token = new AnonymousToken($firewallProvider, $username, ['ROLE_ADMIN']);
        $session = $this->getContainer()->get('session');
        $session->setName('security.debug.console');
        $session->set('_security_' . $firewallProvider, serialize($token));
        $this->getContainer()->get('security.context')->setToken($token);

        $kernel = new SimpleHttpKernel();
        $request = Request::create($uri, 'GET', array(), array('security.debug.console' => true));
        $request->setSession($session);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $rto = $this->getContainer()->get('security.firewall')->onKernelRequest($event);
        \Doctrine\Common\Util\Debug::dump($rto);

        \Doctrine\Common\Util\Debug::dump($event->getResponse());

    }
}
