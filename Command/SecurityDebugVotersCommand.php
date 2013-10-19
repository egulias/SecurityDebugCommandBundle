<?php

namespace Egulias\SecurityDebugCommandBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Egulias\SecurityDebugCommandBundle\Security\Token\AuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * ListenersCommand
 *
 * @author Eduardo Gulias <me@egulias.com>
 */
class SecurityDebugVotersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('security:debug:voters')
        ->setDescription('Debug Security access to given Obtainsects or Urls')
        ->setDefinition(
            array(
                new InputArgument('secured_area', InputArgument::REQUIRED, "Secured area of the app"),
                new InputArgument('username', InputArgument::REQUIRED, "Vote over some attribute"),
                new InputArgument('password', InputArgument::REQUIRED, "pass"),
            )
        )
        ->setHelp(
            <<<EOF
The <info>security:debug:voters</info> calls votings over differents elements

EOF
        );
    }

    //auth use
    //fake user for roles
    //roles-> view role hierarchy
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $token = new UsernamePasswordToken(
            $input->getArgument('username'),
            $input->getArgument('password'),
            $input->getArgument('secured_area'),
            ['ROLE_ADMIN']
        );
        //$token = new AuthenticatedToken(['ROLE_ADMIN']);
        $ll = $this->getContainer()->get('security.authentication.manager')->authenticate($token);

        $securityContext = $this->getContainer()->get('security.access.decision_manager')
            ->decide($token, ['ROLE_USER' ]);
        $decision = $this->getContainer()->get('security.access.decision_manager')->supportsClass('ROLE_ADMIN');
        \Doctrine\Common\Util\Debug::dump($securityContext);
        \Doctrine\Common\Util\Debug::dump($ll->isAuthenticated());

        //$this->services['security.authentication.manager'] =
        //    $instance = new \Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager(
        //        array(0 => new \Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider(
        //            $this->get('security.user.provider.concrete.in_memory'),
        //            new \Symfony\Component\Security\Core\User\UserChecker(),
        //            'secured_area',
        //            $this->get('security.encoder_factory'),
        //            true)
        //        ),
        //        true);

    }
}
