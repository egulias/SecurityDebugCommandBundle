# Security debug console command for Symfony2

This bundle provides commands under the `security` namespace (`security:debug:*`) to help debugging your application
security in a simple way, by inspecting Voters, Listeners and (yet to come) ACL.

## IMPORTANT

This bundle fakes credentials and tokens to be able to inspect permissions. This implies a possible security hole in
your application, please be aware of this. *I'm not responsible for any issue derived for a misuse or an insecure use of
it* 

# Usage

As for any command you should use: `app/console` from your project root.
Current available commands are:
- `app/console security:debug:firewalls`  to view listeners for a firewall.
- `app/console security:debug:voters`     to display voters, voters vote and result.

## Available options

There are 4 available options:
- `app/console security:debug:firewalls uri firewall username roles`
uri                   The exact URI you have in the firewall
firewall              Firewall name
username              User to test
roles                 Multiple space separated roles for the user

- `app/console security:debug:voters` (this can be faked too, but for the moment a real user is needed)
firewall              Secured area of the app
username              Username to authenticate
password              Username Password

## Sample output 
* `app/console security:debug:firewalls`  [here](https://gist.github.com/egulias/7186738)
* `app/console security:debug:voters`     [here](https://gist.github.com/egulias/7186678)

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
