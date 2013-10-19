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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
                new InputArgument('roles', InputArgument::IS_ARRAY, "Multiple space separated roles for the user"),
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
        $uri = $input->getArgument('uri');
        $firewallProvider = $input->getArgument('firewall');
        $username = $input->getArgument('username');
        $roles = $input->getArgument('roles');

        $token = new AnonymousToken($firewallProvider, $username, $roles);
        $session = $this->getContainer()->get('session');
        $session->setName('security.debug.console');
        $session->set('_security_' . $firewallProvider, serialize($token));
        $this->getContainer()->get('security.context')->setToken($token);

        $kernel = new SimpleHttpKernel();
        $request = Request::create($uri, 'GET', array(), array('security.debug.console' => true));
        $request->setSession($session);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        try {
            $this->getContainer()->get('security.firewall')->onKernelRequest($event);
            $output->writeln(sprintf('<info>Access Granted</info>'));
        } catch (AccessDeniedException $ade) {
            $output->writeln(
                sprintf(
                    '<error>Acces Denied</error> for firewall <comment>%s</comment> and roles <comment>%s</comment>',
                    $firewallProvider,
                    implode($roles, ',')
                )
            );
        }
        $map = $this->getContainer()->get('security.firewall.map.context.' . $firewallProvider);
        $formatter = $this->getHelperSet()->get('formatter');
        $formattedLine = $formatter->formatSection(
            "Who's listening?",
            sprintf('Firewall <comment>%s</comment> listeners', $firewallProvider)
        );
        $output->writeln($formattedLine);
        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(array('Class', 'Stopped propagation'));
        $firewallContext = $map->getContext();
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        foreach ($firewallContext[0] as $listener) {
            $row = array(get_class($listener));
            try {
                $listener->handle($event);
            } catch (AccessDeniedException $ade) {
                $row[] = 'X';
                $table->addRow($row);
                break;
            }
            if ($event->hasResponse()) {
                $row[] = 'X';
            }
            $table->addRow($row);
        }
        $table->render($output);
    }
}
