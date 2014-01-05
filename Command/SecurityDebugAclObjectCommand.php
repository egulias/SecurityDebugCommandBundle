<?php

namespace Egulias\SecurityDebugCommandBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;

/**
 *  ACL Objects debug command
 *
 * @author Eduardo Gulias <me@egulias.com>
 */
class SecurityDebugAclObjectCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('security:debug:acl_object')
            ->setDescription('Debug Security access to given Objects')
            ->setDefinition(
                array(
                    new InputArgument('username', InputArgument::REQUIRED, "Username to authenticate"),
                    new InputArgument(
                        'fqcn',
                        InputArgument::REQUIRED,
                        "Fully Qualified Class Name using / "
                    ),
                    new InputArgument('oid', InputArgument::REQUIRED, "Object ID"),
                    new InputArgument(
                        'masks',
                        InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                        "Permissions Masks value"
                    ),
                )
            )
            ->addOption(
                '--field',
                null,
                InputOption::VALUE_REQUIRED,
                'Field name to check access'
            )
            ->setHelp(
                <<<EOF
The <info>security:debug:acl_object</info> shows the permissions on a given Object

EOF
            );
    }

    /**
     * execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $class = '\\' . str_replace('/', '\\', $input->getArgument('fqcn'));
        $oid = (int) $input->getArgument('oid');
        $masks = $input->getArgument('masks');
        $field = $input->getOption('field', null);
        array_walk(
            $masks,
            function (&$v, $k) {
                $v = (int)$v;
            }
        );
        $user = new \Symfony\Component\Security\Core\User\User($username, 'fakepass');
        $securityIdentity = UserSecurityIdentity::fromAccount($user);

        $aclProvider = $this->getContainer()->get('security.acl.provider');
        $object = new $class;
        $object->setId($oid);
        $objectIdentity = ObjectIdentity::fromDomainObject($object);

        $acl = $aclProvider->findAcl($objectIdentity, [$securityIdentity]);
        $results = $this->getAccesses($acl, $masks, [$securityIdentity], $field);

        $output->writeln(sprintf('For Class/Object <info>%s::id == %d</info>', $class, $oid));
        $output->writeln(sprintf('With User <info>%s</info>', $username));
        if ($field) {
            $access = $acl->isFieldGranted($field, [$mask], [$securityIdentity], false);
            $output->writeln(sprintf('For field <info>%s</info>', $field));
        } else {
            $access = $acl->isGranted($masks, [$securityIdentity], false) ? 'Allow' : 'Deny';
        }

        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(array('Mask', 'Grant', 'Deny'));
        $table->setRows($results);
        $table->render($output);

        $output->writeln(
            sprintf('The ACL Voter will probably <comment>%s</comment> access with those Masks', $access)
        );
    }

    /**
     * getAccesses
     *
     * @param mixed     $acl
     * @param array     $masks
     * @param array     $sids
     * @param string    $field
     * @return array
     */
    protected function getAccesses($acl, array $masks, array $sids, $field = null)
    {
        $result = [];
        foreach ($masks as $mask) {
            $granted = '';
            $denied = 'X';
            try {
                if (null === $field) {
                    if ($acl->isGranted([$mask], $sids, false)) {
                        $granted = 'X';
                        $denied = '';
                    }
                } else {
                    if ($acl->isFieldGranted($field, [$mask], $sids, false)) {
                        $granted = 'X';
                        $denied = '';
                    }
                }
            } catch (NoAceFoundException $e) {
                $denied = 'With NoAceFoundException: ' . $e->getMessage();
            }
            $result[] = [$mask, $granted, $denied];
        }

        return $result;
    }
}
