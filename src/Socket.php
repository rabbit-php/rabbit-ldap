<?php
declare(strict_types=1);

namespace FreeDSx\Socket;

use Co\Client;
use FreeDSx\Socket\Exception\ConnectionException;
use RuntimeException;

/**
 * Represents a generic socket.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Socket
{
    public const TRANSPORTS = [
        SWOOLE_SOCK_TCP,
        SWOOLE_SOCK_TCP6,
        SWOOLE_SOCK_UDP,
        SWOOLE_SOCK_UDP6
    ];
    protected bool $isEncrypted = false;
    protected ?Client $socket;

    /**
     * @var array
     */
    protected array $options = [
        'transport' => SWOOLE_SOCK_TCP,
        'port' => 389,
        'use_ssl' => false,
        'ssl_validate_cert' => true,
        'ssl_allow_self_signed' => null,
        'timeout_connect' => 3,
        'timeout_read' => 15
    ];

    /**
     * @param resource|null $resource
     * @param array $options
     */
    public function __construct($resource = null, array $options = [])
    {
        $this->socket = $resource;
        $this->options = \array_merge($this->options, $options);
        if (!\in_array($this->options['transport'], self::TRANSPORTS, true)) {
            throw new RuntimeException(sprintf(
                'The transport "%s" is not valid. It must be one of: %s',
                $this->options['transport'],
                implode(',', self::TRANSPORTS)
            ));
        }
    }

    /**
     * @param bool $block
     * @return string|false
     */
    public function read(bool $block = true)
    {
        if (!$block && false === $this->socket->peek()) {
            return false;
        }
        return $this->socket->recv();
    }

    /**
     * @param string $data
     * @return $this
     */
    public function write(string $data)
    {
        $this->socket->send($data);

        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->socket !== null && $this->socket->connected;
    }

    /**
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->isEncrypted;
    }

    /**
     * @return $this
     */
    public function close()
    {
        if ($this->socket !== null) {
            $this->socket->close();
        }
        $this->socket = null;
        $this->isEncrypted = false;

        return $this;
    }

    /**
     * Enable/Disable encryption on the TCP connection stream.
     *
     * @param bool $encrypt
     * @return $this
     */
    public function encrypt(bool $encrypt)
    {
        $this->isEncrypted = $encrypt;
        $this->socket->set([
            'ssl_validate_cert' => $this->options['ssl_validate_cert'],
            'ssl_allow_self_signed' => $this->options['ssl_allow_self_signed'],
        ]);
        $this->socket->enableSSL();
        return $this;
    }

    /**
     * @param string $host
     * @return $this
     * @throws ConnectionException
     */
    public function connect(string $host)
    {
        $socket = new Client($this->options['transport']);
        if (false === $socket->connect($host, $this->options['port'], $this->options['timeout_connect'])) {
            throw new ConnectionException(sprintf(
                'Unable to connect to %s: %s',
                $host,
                $socket->errMsg
            ));
        }
        $socket->set([
            'read_timeout' => $this->options['timeout_read'],
        ]);
        $this->socket = $socket;
        $this->isEncrypted = $this->options['use_ssl'];
        if ($this->isEncrypted) {
            $socket->set([
                'ssl_validate_cert' => $this->options['ssl_validate_cert'],
                'ssl_allow_self_signed' => $this->options['ssl_allow_self_signed'],
            ]);
            $this->socket->enableSSL();
        }

        return $this;
    }

    /**
     * Get the options set for the socket.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Create a socket by connecting to a specific host.
     *
     * @param string $host
     * @param array $options
     * @return Socket
     * @throws ConnectionException
     */
    public static function create(string $host, array $options = []): Socket
    {
        return (new self(null, $options))->connect($host);
    }

    /**
     * Create a TCP based socket.
     *
     * @param string $host
     * @param array $options
     * @return Socket
     * @throws ConnectionException
     */
    public static function tcp(string $host, array $options = []): Socket
    {
        return self::create($host, \array_merge($options, ['transport' => SWOOLE_SOCK_TCP]));
    }

    /**
     * Create a UDP based socket.
     *
     * @param string $host
     * @param array $options
     * @return Socket
     * @throws ConnectionException
     */
    public static function udp(string $host, array $options = []): Socket
    {
        return self::create($host, \array_merge($options, [
            'transport' => SWOOLE_SOCK_UDP,
            'buffer_size' => 65507,
        ]));
    }
}
