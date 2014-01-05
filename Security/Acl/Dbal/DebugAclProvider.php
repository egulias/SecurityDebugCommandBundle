<?php

namespace Egulias\SecurityDebugCommandBundle\Security\Acl\Dbal;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;

class DebugAclProvider extends MutableAclProvider
{
    /** @var Connection */
    protected $connection;

    /** Locates all objects that the specified User has access to.
     *
     *Note that this method has a few limitations:
     *   *  - No support for filtering by mask.
     *        *  - No support for ACEs that match one of the User's roles (only ACEs that
     *             *      reference the User's security identity will be matched).
     *                  *  - Every ACE that matches is assumed to grant access.
     *                       *
     *                            * @param UserInterface $user
     *                                 * @param string        $type   If set, filter by object type
     *                                 (classname).
     *                                      *
     *                                           * @return ObjectIdentity[]
     *                                                */
    public function findObjectIdentitiesForUser(UserInterface $user, $type = null)
    {
        $securityIdentity = UserSecurityIdentity::fromAccount($user);
        $identifier = sprintf(
            '%s-%s',
            $securityIdentity->getClass(),
            $securityIdentity->getUsername()
        );

        $sql = <<<END
SELECT
          o.object_identifier
        , c.class_type
    FROM
        {$this->options['sid_table_name']} s
    LEFT JOIN
        {$this->options['entry_table_name']} e
            ON (
                    (e.security_identity_id = s.id)
                or {$this->connection->getDatabasePlatform()->getIsNullExpression('e.security_identity_id')}
            )
    LEFT JOIN
        {$this->options['oid_table_name']} o
            ON (o.id = e.object_identity_id)
    LEFT JOIN
        {$this->options['class_table_name']} c
            ON (c.id = o.class_id)
    WHERE
            s.identifier = {$this->connection->quote($identifier)}
END;

        if ($type) {
            $sql .= <<<END
        AND c.class_type = {$this->connection->quote($type)}
END;
        }

        $objectIdentities = array();

        /* @kludge It would be awesome if we could use hydrateObjectIdentities()
         *  here.  Then we could do super fancy stuff like filter by mask and
         *           *  check whether ACEs grant or deny access.
         *                    *
         *                             * Unfortunately, that method is not accessible to subclasses.
         *                                      */
        foreach ($this->connection->executeQuery($sql)->fetchAll() as $row) {
            $objectIdentities[] = new ObjectIdentity($row['object_identifier'], $row['class_type']);
        }

        return $objectIdentities;
    }
}
