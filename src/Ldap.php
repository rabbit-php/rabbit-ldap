<?php
declare(strict_types=1);

namespace Rabbit\Ldap;

use FreeDSx\Ldap\Exception\BindException;
use FreeDSx\Ldap\Exception\OperationException;
use FreeDSx\Ldap\LdapClient;
use Rabbit\Pool\AbstractBase;
use Rabbit\Pool\IUnity;

/**
 * Class Ldap
 * @package Common\Ldap
 */
class Ldap extends AbstractBase implements IUnity
{
    /** @var array */
    protected array $configs = [];
    /** @var array */
    protected array $bind = [];

    public function __construct(array $configs, array $bind, string $poolKey = null)
    {
        parent::__construct($poolKey);
        $this->configs = $configs;
        $this->bind = $bind;
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