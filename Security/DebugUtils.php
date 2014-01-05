<?php

namespace Egulias\SecurityDebugCommandBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DebugUtils
{
    public static function getRolesStrings(TokenInterface $token)
    {
        $roles = $token->getRoles();
        $rolesStrings = [];
        foreach ($roles as $role) {
            $rolesStrings[] = $role->getRole();
        }
        return $rolesStrings;
    }
}
