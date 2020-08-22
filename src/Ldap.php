<?php
declare(strict_types=1);

namespace Rabbit\Ldap;

use FreeDSx\Ldap\Exception\BindException;
use FreeDSx\Ldap\Exception\OperationException;
use FreeDSx\Ldap\LdapClient;
use Rabbit\Base\Exception\InvalidConfigException;
use Rabbit\Pool\AbstractBase;
use Rabbit\Pool\IUnity;

/**
 * Class Ldap
 * @package Common\Ldap
 */
class Ldap extends AbstractBase implements IUnity
{
    protected array $configs = [];
    protected array $bind = [];

    public function __construct(string $dsn, string $poolKey = null)
    {
        parent::__construct($poolKey);
        if (empty($dsn)) {
            throw new InvalidConfigException("dsn not set!");
        }
        $urlArr = explode(';', $dsn);
        foreach ($urlArr as $dsn) {
            $urlArr = parse_url($dsn);
            if ($urlArr['scheme'] === 'ldaps') {
                $this->configs += [
                    'use_ssl' => true,
                    'ssl_allow_self_signed' => true,
                    'ssl_validate_cert' => false
                ];
            }
            $this->configs['servers'][] = isset($urlArr['host']) ? $urlArr['host'] : '';
            $this->configs['port'] = isset($urlArr['port']) ? $urlArr['port'] : ($urlArr['scheme'] === 'ldaps' ? 636 : 389);
            parse_str(isset($urlArr['query']) ? $urlArr['query'] : '', $options);
            if (!isset($options['base_dn'])) {
                throw new InvalidConfigException("base_dn not set");
            }
            $this->configs += $options;
            $user = isset($urlArr['user']) ? $urlArr['user'] : '';
            $pass = isset($urlArr['pass']) ? $urlArr['pass'] : '';
            empty($this->bind) && $this->bind = [$user, $pass];
        }
    }

    /**
     * @return LdapClient
     * @throws BindException
     * @throws OperationException
     */
    public function build()
    {
        $client = new LdapClient($this->configs);
        $client->bind(...$this->bind);
        return $client;
    }
}