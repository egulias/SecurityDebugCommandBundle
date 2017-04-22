<?php

namespace Egulias\SecurityDebugCommandBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Egulias\SecurityDebugCommandBundle\Security\Voter\VotersDebug;
use Egulias\SecurityDebugCommandBundle\Security\Authorization\DecisionManagerDebug;
use Symfony\Component\Console\Helper\Table;

/**
 *  Voters debug command
 *
 * @author Eduardo Gulias <me@egulias.com>
 */
class SecurityDebugAclVotersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('security:debug:acl_voters')
        ->setDescription('Debug Security access to given Urls')
        ->setDefinition(
            array(
                new InputArgument('username', InputArgument::REQUIRED, "The username for which to debug"),
                new InputArgument(
                    'fqcn',
                    InputArgument::REQUIRED,
                    "Fully Qualified Class Name using / "
                ),
                new InputArgument('oid', InputArgument::REQUIRED, "Object ID"),
                new InputArgument(
                    'perms',
                    InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                    "Permissions strings, e.g: OWNER"
                ),
                new InputOption(
                    'strategy',
                    null,
                    InputOption::VALUE_REQUIRED,
                    "Strategy used to authorize. Possible values are: Affirmative (default), Unanimous, Consensus"
                ),
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
        $class = '\\' . str_replace('/', '\\', $input->getArgument('fqcn'));
        $oid = (int) $input->getArgument('oid');
        $strategy = $input->getOption('strategy', false);
        $userClass = $this->getContainer()->getParameter('egulias_security_debug.user_class');
        $user = new $userClass($input->getArgument('username'), 'fakepass');

        $token = new UsernamePasswordToken(
            $user,
            'fakepass',
            'secured_area',
            $input->getArgument('perms')
        );

        $votersDebug = new VotersDebug($this->getContainer()->get('security.access.decision_manager'));
        $votersDebug->setAcl($class, $oid);
        $decisionMngrDebug = new DecisionManagerDebug($this->getContainer()->get('security.access.decision_manager'));
        $decisionMngrDebug->setAcl($class, $oid);

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
        $table = new Table($output);
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
