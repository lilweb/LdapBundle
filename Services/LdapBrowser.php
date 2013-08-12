<?php
/**
 * Author: Michiel Missotten
 * Date: 04/02/13
 * Time: 10:01
 */
namespace Lilweb\LdapBundle\Services;

use Symfony\Bridge\Monolog\Logger;

use Lilweb\LdapBundle\Connection\LdapConnection;
use Lilweb\LdapBundle\Exception\LdapException;

/**
 * Surcouche sur l'utilisation du serveur LDAP.
 */
class LdapBrowser
{
    /**
     * @var LdapConnection La connection sur le LDAP.
     */
    private $ldapConnection;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $params;

    /**
     * Constructeur.
     *
     * @param \Lilweb\LdapBundle\Connection\LdapConnection $ldapConnection
     */
    public function __construct(LdapConnection $ldapConnection, Logger $logger, array $params)
    {
        $this->ldapConnection = $ldapConnection;
        $this->logger = $logger;
        $this->params = $params;
    }

    /**
     * Va valider le mot de passe de l'utilisateur.
     *
     * @param $username
     * @param $password
     */
    public function authenticate($username, $password)
    {
        $ldapUser = $this->findUserByUsername($username);

        return (bool) $this->ldapConnection->bind($ldapUser['dn'], $password);
    }

    /**
     * Cherche un utilisateur.
     *
     * @param $username
     * @return mixed
     * @throws LdapException
     */
    public function findUserByUsername($username)
    {
        $this->logger->debug('LdapBrowser::findUserByUsername - Recherche de l\'utilisateur sur le LDAP : "' . $username . '"');

        // Cherche l'utilisateur sur le LDAP
        $filter = isset($this->params['user']['filter']) ? $this->params['user']['filter'] : '';
        $entries = $this
            ->ldapConnection
            ->search(
            array(
                'base_dn' => $this->params['user']['base_dn'],
                'filter'  => sprintf('(&%s(%s=%s))', $filter, $this->params['user']['name_attribute'], $this->ldapConnection->escape($username))
            )
        );

        // Plusieurs utilisateurs avec ce nom d'utilisateur, exception.
        if ($entries['count'] > 1) {
            throw new LdapException("This search can only return a single user");
        }

        // Aucun utilisateur ne correspond.
        if ($entries['count'] == 0) {
            throw new LdapException('Cannot find the user');
        }

        return $entries[0];
    }
}
