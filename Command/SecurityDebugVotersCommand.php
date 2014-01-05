<?php

namespace Egulias\SecurityDebugCommandBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 *  Voters debug command
 *
 * @author Eduardo Gulias <me@egulias.com>
 */
class SecurityDebugVotersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('security:debug:voters')
        ->setDescription('Debug Security access to given Urls')
        ->setDefinition(
            array(
                new InputArgument('firewall', InputArgument::REQUIRED, "Secured area of the app"),
                new InputArgument('username', InputArgument::REQUIRED, "Username to authenticate"),
                new InputArgument('password', InputArgument::REQUIRED, "Username Password"),
                new InputOption(
                    'strategy',
                    null,
                    InputOption::VALUE_REQUIRED,
                    "Strategy used to authorize. Possible values are: Affirmative (default), Unanimous, Consensus"
                )
            )
        )
        ->setHelp(
            <<<EOF
The <info>security:debug:voters</info> calls votings over differents elements

EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $token = new UsernamePasswordToken(
            $input->getArgument('username'),
            $input->getArgument('password'),
            $input->getArgument('firewall')
        );
        $strategy = $input->getOption('strategy', false);

        $token = $this->getContainer()->get('security.authentication.manager')->authenticate($token);

        $roles = $token->getRoles();
        foreach ($roles as $role) {
            $rolesStrings[] = $role->getRole();
        }

        $decision = $this->getContainer()->get('security.access.decision_manager')->decide($token, $rolesStrings);
        $decisionManager = $this->getContainer()->get('security.access.decision_manager');

        $rflClass = new \ReflectionClass('Symfony\Component\Security\Core\Authorization\AccessDecisionManager');
        $rflStrategy = $rflClass->getProperty('strategy');
        $rflStrategy->setAccessible(true);
        $currentStrategy = $rflStrategy->getValue($decisionManager);
        if ($strategy) {
            $rflStrategy->setValue('strategy', $strategy);
            $currentStrategy = $strategy;
        }

        $rflVoters = $rflClass->getProperty('voters');
        $rflVoters->setAccessible(true);
        $voters = $rflVoters->getValue($decisionManager);

        $formatter = $this->getHelperSet()->get('formatter');
        $formattedLine = $formatter->formatSection(
            "Who's voting?",
            'Vote intention'
        );

        $output->writeln($formattedLine);
        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(array('Class', 'Abstain', 'Grant', 'Deny'));

        foreach ($voters as $voter) {
            $result = $voter->vote($token, null, ['ROLE_USER']);
            switch ($result) {
                case VoterInterface::ACCESS_ABSTAIN:
                    $row = array(get_class($voter),'X');
                    break;
                case VoterInterface::ACCESS_GRANTED:
                    $row = array(get_class($voter),'','X');
                    break;
                case VoterInterface::ACCESS_DENIED:
                    $row = array(get_class($voter),'', '', 'X');
                    break;
            }
            $table->addRow($row);
        }
        $table->render($output);

        $formattedLine = $formatter->formatSection(
            'Strategy',
            $currentStrategy
        );
        $output->writeln($formattedLine);

        $formattedLine = $formatter->formatSection(
            'Result',
            ($decision) ? 'Allow' : 'Deny'
        );
        $output->writeln($formattedLine);
    }
}
