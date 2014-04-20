# Security debug console command for Symfony2

This bundle provides commands under the `security` namespace (`security:debug:*`) to help debugging your application
security in a simple way, by inspecting Voters, Listeners and (yet to come) ACL.

## IMPORTANT

This bundle fakes credentials and tokens to be able to inspect permissions. This implies a possible security hole in
your application, please be aware of this. *I'm not responsible for any issue derived for a misuse or an insecure use of it*

## Caution

The DataCollector feature re issues the request and some events to be able to inspect the results.
If any of your custom voters, listeners, firewal listeners has side effects **they will be issued twice**

# Usage

As for any command you should use: `app/console` from your project root.
Current available commands are:

- `app/console security:debug:firewalls`      to view listeners for a firewall.
- `app/console security:debug:voters`         to display voters, voters vote and result.
- `app/console security:debug:acl_voters`     to display voters, voters vote and result when ACL is present.
- `app/console security:debug:acl_object`     to display ACL results for each mask provided.

## Available Commands

* `app/console security:debug:firewalls uri firewall username roles`
 * `uri`         The exact URI you have in the firewall
 * `firewall`    Firewall name
 * `username`    User to test
 * `roles`       Multiple space separated roles for the user

* `app/console security:debug:voters` (this can be faked too, but for the moment a real user is needed)
 * `firewall`              Secured area of the app
 * `username`              Username to authenticate
 * `password`              Username Password

* `app/console security:debug:acl_voters username object-fqcn id permission-name`
 * `username`         For which user you need the information
 * `object-fqcn`      The object class for which you are asking (using `/` instead of `\`)
 * `id`               Object ID in the DB
 * `permission-name`  The permission map name, e.g. OWNER

* `app/console security:debug:acl_object username object-fqcn mask-binary`
 * `username`         For which user you need the information
 * `object-fqcn`      The object class for which you are asking (using `/` instead of `\`)
 * `id`               Object ID in the DB
 * `mask-binary`      The binary of the mask, e.g. 128 (OWNER)

## Sample output 
* `app/console security:debug:firewalls`  [here](https://gist.github.com/egulias/7186738)
* `app/console security:debug:voters`     [here](https://gist.github.com/egulias/7186678)
* `app/console security:debug:acl_voters` [here](https://gist.github.com/egulias/8498166)
* `app/console security:debug:acl_object` [here](https://gist.github.com/egulias/8498245)

# Installation and configuration

## Get the bundle
Add to your composer.json

```
{
    "require": {
        "egulias/security-debug-command-bundle": "0.5.0"
    }
}
```

Use composer to download the new requirement
``` 
$ php composer.phar update egulias/security-debug-command-bundle
```

## Add SecurityDebugCommandBundle to your application kernel

``` php
<?php

  // app/AppKernel.php
  public function registerBundles()
  {
    return array(
      // ...
      new Egulias\SecurityDebugCommandBundle\EguliasSecurityDebugCommandBundle(),
      // ...
      );
  }
```
## Configure the user class
In your `app/config/config.yml` you should add the FQCN that you use:
```
egulias_security_debug_command:
    user_class: Acme\DemoBundle\Entity\User
```
