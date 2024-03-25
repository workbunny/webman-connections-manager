<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager\Process;

use Psr\Log\LoggerInterface;
use Workbunny\WebmanRqueue\Headers;
use Workbunny\WebmanRqueue\Builders\QueueBuilder;
use Illuminate\Redis\Connections\Connection;

class ConnectionEventBuilder extends QueueBuilder
{
    
    /** @see QueueBuilder::$configs */
    protected array $configs = [
        // 默认由类名自动生成
        'queues'          => [
            'ConnectionEventBuilder'
        ],
        // 默认由类名自动生成        
        'group'           => 'ConnectionEventBuilder',
        // 是否延迟         
        'delayed'         => false,
        // QOS    
        'prefetch_count'  => 0,
        // Queue size
        'queue_size'      => 0,
        // 消息pending超时，毫秒
        'pending_timeout' => 0             
    ];
    
    /** @var float|null 消费间隔 1ms */
    protected ?float $timerInterval = 1.0;
    
    /** @var string redis配置 */
    protected string $connection = 'plugin.workbunny.webman-connections-manager.default';

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->timerInterval = config('plugin.worbunny.webman-connections-manager.connection_event_builder.interval', 1.0);
        $this->configs['prefetch_count'] = config('plugin.worbunny.webman-connections-manager.connection_event_builder.prefetch_count', 0);
        $this->configs['queue_size'] = config('plugin.worbunny.webman-connections-manager.connection_event_builder.queue_size', 0);
        $this->configs['pending_timeout'] = config('plugin.worbunny.webman-connections-manager.connection_event_builder.pending_timeout', 0);
        parent::__construct($logger);
    }

    /** @inheritDoc */
    public function handler(string $id, array $value, Connection $connection): bool 
    {
        $header = new Headers($value['_header']);
        $data   = self::decode($value['_body']);
        $handler = config('plugin.worbunny.webman-connections-manager.connection_event_builder.handler');
        if ($handler) {
            call_user_func($handler, $header, $data, $connection);
        }
        return true;
    }

    /**
     * @param string|null $data
     * @return array|null
     */
    public static function decode(mixed $data): ?array
    {
        if (is_string($data)) {
            return unserialize($data);
        }
        return [];
    }

    /**
     * @param array $data
     * @return string
     */
    public static function encode(array $data): string
    {
        return serialize($data);
    }
}