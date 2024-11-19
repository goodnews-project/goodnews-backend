<?php

namespace App\Protocol;

use App\Protocol\Types\ActivityPubProtocol;
use App\Protocol\Types\ProtocolInterface;
use Hyperf\Cache\Exception\InvalidArgumentException;
use Hyperf\Contract\ConfigInterface;
use function Hyperf\Support\make;

class ProtocolManager
{

    protected array $protocols = [];

    public function __construct(protected ConfigInterface $config)
    {
    }

    public function getProtocol(string $name): ProtocolInterface
    {
        if (isset($this->protocols[$name]) && $this->protocols[$name] instanceof ProtocolInterface) {
            return $this->protocols[$name];
        }

        $config = $this->config->get("protocol.{$name}");
        if (empty($config)) {
            throw new InvalidArgumentException(sprintf('The protocol config %s is invalid.', $name));
        }

        $activityPubProtocolClass = $config['type'] ?? ActivityPubProtocol::class;

        $protocol = make($activityPubProtocolClass);

        return $this->protocols[$name] = $protocol;
    }

}