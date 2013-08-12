<?php
/**
 * User: Michiel Missotten
 * Date: 05/06/12
 * Time: 16:06
 */
namespace Lilweb\LdapBundle\Connection;

use Symfony\Bridge\Monolog\Logger;

use Lilweb\LdapBundle\Exception\LdapConnectionException;

/**
 * Classe ajoutant un niveau objet à la librairie LDAP de php (php5-ldap)
 */
class LdapConnection
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $params = array();

    /**
     * @var ressource
     */
    private $_ress;

    /**
     * {@inheritdoc}
     */
    public function __construct(Logger $logger, array $params)
    {
        $this->logger = $logger;
        $this->params = $params;
    }

    /**
     * Déconnection du LDAP.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function search(array $params)
    {
        if ($this->_ress == null) {
            $this->connect();
        }

        $ref = array(
            'base_dn' => '',
            'filter'  => '',
        );

        if (count($diff = array_diff_key($ref, $params))) {
            throw new LdapConnectionException(sprintf('You must define %s', print_r($diff, true)));
        }

        $attrs = array();
        if (isset($params['attrs']) && !is_array($params['attrs'])) {
            throw new LdapConnectionException('Attribute parameter must be an array.');
        } else if (isset($params['attrs'])) {
            $attrs = $params['attrs'];
        }

        $search = @ldap_search($this->_ress, $params['base_dn'], $params['filter'], $attrs);

        if ($search) {
            return ldap_get_entries($this->_ress, $search);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function bind($user_dn, $password)
    {
        if ($this->_ress == null) {
            $this->connect();
        }

        if (empty($user_dn)) {
            throw new LdapConnectionException('LdapConnection::bind - "userdn" is not defined');
        }

        if (empty($password)) {
            throw new LdapConnectionException('LdapConnection::bind - Password is empty?');
        }

        return (bool) @ldap_bind($this->_ress, $user_dn, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->params['host'];
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return isset($this->params['port']) ? $this->params['port'] : '389';
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseDn($index)
    {
        return $this->params[$index]['base_dn'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilter($index)
    {
        return $this->params[$index]['filter'];
    }

    /**
     * {@inheritdoc}
     */
    public function getNameAttribute($index)
    {
        return $this->params[$index]['name_attribute'];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAttribute($index)
    {
        return $this->params[$index]['user_attribute'];
    }

    /**
     * Connexion au serveur LDAP.
     */
    private function connect()
    {
        $this->logger->debug('LdapConnection::connect - Connecting to LDAP server');

        $port = $this->getPort();

        $ress = @ldap_connect($this->getHost(), $port);

        if (isset($this->params['version']) && $this->params['version'] !== null) {
            ldap_set_option($ress, LDAP_OPT_PROTOCOL_VERSION, $this->params['version']);
        }

        if (isset($this->params['referrals_enabled']) && $this->params['referrals_enabled'] !== null) {
            ldap_set_option($ress, LDAP_OPT_REFERRALS, $this->params['referrals_enabled']);
        }

        if (isset($this->params['username']) && $this->params['version'] !== null) {
            if (!isset($this->params['password'])) {
                throw new LdapConnectionException('LDAP Connection failed : Password undefined');
            }
            $bindress = @ldap_bind($ress, $this->params['username'], $this->params['password']);

            if (!$bindress) {
                throw new LdapConnectionException('LDAP Connection failed : credentials refused');
            }
        } else {
            $bindress = @ldap_bind($ress);

            if (!$bindress) {
                throw new LdapConnectionException('LDAP Connection failed!');
            }
        }

        $this->_ress = $ress;

        return $this;
    }

    /**
     * Déconnection du LDAP.
     */
    public function disconnect()
    {
        $this->logger->debug('LdapConnection - Closing connection');

        if ($this->_ress != null) {
            ldap_close($this->_ress);
        }
    }

    /**
     * Escape string for use in LDAP search filter.
     *
     * @link http://www.php.net/manual/de/function.ldap-search.php#90158
     * See RFC2254 for more information.
     * @link http://msdn.microsoft.com/en-us/library/ms675768(VS.85).aspx
     * @link http://www-03.ibm.com/systems/i/software/ldap/underdn.html
     */
    public function escape($str)
    {
        $metaChars = array('*', '(', ')', '\\', chr(0));

        $quotedMetaChars = array();
        foreach ($metaChars as $key => $value) {
            $quotedMetaChars[$key] = '\\' . str_pad(dechex(ord($value)), 2, '0');
        }
        $str = str_replace($metaChars, $quotedMetaChars, $str);
        return ($str);
    }
}