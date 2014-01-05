<?php


        //$token = new UsernamePasswordToken(
        //    'admin', //$input->getArgument('username'),
        //    'adminpass', //$input->getArgument('password'),
        //    'secured_area', //$input->getArgument('firewall')
        //    ['FAKE_ROLE']
        //);

        //$token = $this->getContainer()->get('security.authentication.manager')->authenticate($token);

        //$securityContext = $this->getContainer()->get('security.context');
        //$securityContext->setToken($token);
        //$user = $token->getUser();
        //$acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        //$aclProvider->updateAcl($acl);
        die;

        $decision = 'bar';//$this->getContainer()->get('security.access.decision_manager')->decide($token, ['OWNER'], $objectIdentity);
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
            $result = $voter->vote($token, $objectIdentity, ['OWNER']);
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
