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
use Egulias\SecurityDebugCommandBundle\Security\Voter\VotersDebug;
use Egulias\SecurityDebugCommandBundle\Security\Authorization\DecisionManagerDebug;

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
        $votersDebug = new VotersDebug($this->getContainer()->get('security.access.decision_manager'));
        $decisionMngrDebug = new DecisionManagerDebug($this->getContainer()->get('security.access.decision_manager'));


        $token = $this->getContainer()->get('security.authentication.manager')->authenticate($token);

        if ($strategy) {
            $decisionMngrDebug->setDecisionManagerStrategy($strategy);
        }
        $decision = $decisionMngrDebug->decide($token);

        $formatter = $this->getHelperSet()->get('formatter');
        $formattedLine = $formatter->formatSection(
            "Who's voting?",
            'Vote intention'
        );

        $output->writeln($formattedLine);
        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(array('Class', 'Abstain', 'Grant', 'Deny'));
        $votes = $votersDebug->getVotersVote($token);

        foreach ($votes as $vote) {
            switch ($vote[1]) {
                case VoterInterface::ACCESS_ABSTAIN:
                    $row = array($vote[0], 'X');
                    break;
                case VoterInterface::ACCESS_GRANTED:
                    $row = array($vote[0], '', 'X');
                    break;
                case VoterInterface::ACCESS_DENIED:
                    $row = array($vote[0], '', '', 'X');
                    break;
            }
            $table->addRow($row);
        }
        $table->render($output);

        $formattedLine = $formatter->formatSection(
            'Strategy',
            $decisionMngrDebug->getDecisionManagerStrategy()
        );
        $output->writeln($formattedLine);

        $formattedLine = $formatter->formatSection(
            'Result',
            ($decision) ? 'Allow' : 'Deny'
        );
        $output->writeln($formattedLine);
    }
}
