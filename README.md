LdapBundle
==========

This bundle helps connecting to an existing LDAP server. 


## Installation

Add the dependency to your `composer.json` file :

```json
"lilweb/ldap-bundle" : "dev-master@dev"
```    
  
Add the bundle to `AppKernel.php` :

```php 
new Lilweb\LdapBundle\LilwebLdapBundle(),
```
  
## Configuration 

Add the following to your config.yml file :

```yaml
lilweb_ldap:
    host: %ldap_client_host%
    port: %ldap_client_port%
    username: %ldap_client_username%
    password: %ldap_client_password%
    version: %ldap_client_version%
```

## Usage

A new service called `lilweb.ldap_connection` is now available in your container. 

Example :

```php
$entries = $this
    ->ldapConnection
    ->search(
        array(
            'base_dn' => $this->params['base_dn'],
            'filter'  => sprintf('(&%s(%s=%s))', $filter, $this->params['name_attribute'], $this->ldapConnection->escape($username))
        )
    );
```


## Technical information

- The connection is not made until a method on the connection is called. 
- This bundle requires the PHP-ldap extension
