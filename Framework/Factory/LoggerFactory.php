<?php

namespace GrowBitTech\Framework\Factory;

use GrowBitTech\Framework\Config\Interface\GlobalInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class LoggerFactory
{
    private string $path;

    private Level $level;

    private array $handler = [];

    private ?HandlerInterface $test = null;

    public function __construct(GlobalInterface $globel)
    {
        $this->path = $globel->get('logger')['path'] ?? 'vfs://root/logs';
        $this->level = $globel->get('logs')['level'] ?? Level::Debug;
    }

    public function createLogger(string $name = null): LoggerInterface
    {
        $logger = new Logger($name ?? Uuid::v4()->toRfc4122());

        foreach ($this->handler as $handler) {
            $logger->pushHandler($handler);
        }

        $this->handler = [];

        return $logger;
    }

    public function addHandler(HandlerInterface $handler): self
    {
        $this->handler[] = $handler;

        return $this;
    }

    public function addFileHandler($action, Level $level = null): self
    {
        $filename = sprintf('%s/%s', $this->path, $action.'.log');
        $rotatingFileHandler = new RotatingFileHandler($filename, 0, $level ?? $this->level, true, 0777);

        // The last "true" here tells monolog to remove empty []'s
        $rotatingFileHandler->setFormatter(new LineFormatter(null, null, false, true));

        $this->addHandler($rotatingFileHandler);

        return $this;
    }

    public function addConsoleHandler(Level $level = null): self
    {
        $streamHandler = new StreamHandler('php://output', $level ?? $this->level);
        $streamHandler->setFormatter(new LineFormatter(null, null, false, true));

        $this->addHandler($streamHandler);

        return $this;
    }
}
