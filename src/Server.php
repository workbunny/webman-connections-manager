<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager;

use Closure;
use Monolog\Logger;
use support\Log;
use support\Request;
use Webman\App;
use Workbunny\WebmanConnectionsManager\Process\ConnectionEventBuilder;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;
use function Workbunny\WebmanRqueue\sync_publish_get_ids;

class Server extends App
{
    /** @var Connection[] */
    protected static array $connections = [];

    /** @var int 计数 */
    protected static int $count = 0;

    protected static ?Closure $connectionCallback;

    public function __construct(?Closure $connectionCallback = null)
    {
        self::$connectionCallback = $connectionCallback;
        parent::__construct(
            Request::class,
            Log::channel(),
            base_path() . '/vendor/workbunny/webman-connections-manager/App/',
            public_path()
        );
    }

    /**
     * 创建链接
     *
     * @param string $connectionId
     * @param string $url
     * @return void
     */
    public static function Connect(string $connectionId, string $url): void
    {
        if (!$connection = static::Get($connectionId)) {
            try {
                $connection = new Connection($url);
            } catch (\Throwable $throwable) {
                throw new ConnectionsManagerException($throwable->getMessage(), $throwable->getCode(), $throwable);
            }
        }
        $connection->onConnect = function () use ($connectionId) {
            if ($connection = static::Get($connectionId)) {
                static::$count ++;
                $connection->setInfos([
                    'connectionId'   => $connectionId,
                    'alive'          => 1,
                    'autoReconnect'  => 1,
                    'connectedAt'    => ms_time(),
                    'lastReceivedAt' => 0,
                    'lastError'      => '',
                    'connectCount'   => $connection->getInfo('connectCount',0) + 1,
                    'receiveCount'   => $connection->getInfo('receiveCount',0) + 1,
                    'errorCount'     => $connection->getInfo('errorCount',0) + 1,
                    'extra'          => []
                ]);
                sync_publish_get_ids(
                    ConnectionEventBuilder::instance(),
                    ConnectionEventBuilder::encode([
                        'event' => 'onConnect',
                        'info'  => $connection->getInfos(),
                    ])
                );
            }
        };
        $connection->onError = function (AsyncTcpConnection $connection, $code, $msg) use ($connectionId) {
            if ($connection = static::Get($connectionId)) {
                $connection->setInfo('errorCount', $connection->getInfo('errorCount', 0) + 1);
                $connection->setInfo('lastError', "[$code] $msg");
                sync_publish_get_ids(
                    ConnectionEventBuilder::instance(),
                    ConnectionEventBuilder::encode([
                        'event' => 'onError',
                        'info'  => $connection->getInfos(),
                    ])
                );
            }
        };
        $connection->onClose = function () use ($connectionId) {
            if ($connection = static::Get($connectionId)) {
                static::$count --;
                // 如果自动重连
                if ($connection->getInfo('autoReconnect', 0)) {
                    $connection->setInfo('alive', 0);
                    $connection->setInfo('connectedAt', 0);
                    $connection->setInfo('connectCount', $connection->getInfo('connectCount',0) + 1);
                    $connection->reConnect(1);
                }
                // 非自动重连
                else {
                    unset(static::$connections[$connectionId]);
                }
                sync_publish_get_ids(
                    ConnectionEventBuilder::instance(),
                    ConnectionEventBuilder::encode([
                        'event' => 'onClose',
                        'info'  => $connection->getInfos(),
                    ])
                );
            }
        };
        $connection->onMessage = function (AsyncTcpConnection $connection, $data) use ($connectionId) {
            if ($connection = static::Get($connectionId)) {
                $connection->setInfo('lastReceivedAt', ms_time());
                $connection->setInfo('alive', 1);
                sync_publish_get_ids(
                    ConnectionEventBuilder::instance(),
                    ConnectionEventBuilder::encode([
                        'event' => 'onError',
                        'info'  => $connection->getInfos(),
                        'data'  => $data
                    ])
                );
            }
        };
        // 连接回调，可以用于新建连接时设置心跳，设置ssl等
        if (static::$connectionCallback) {
            $connection->delHeartbeatTimer();
            $connection = call_user_func(static::$connectionCallback, $connection);
        }
        static::$connections[$connectionId] = $connection;
        $connection->connect();
    }

    /**
     * 关闭连接
     *
     * @param string $connectionId
     * @return void
     */
    public static function Disconnect(string $connectionId): void
    {
        $connection = static::Get($connectionId);
        // 移除心跳
        $connection?->delHeartbeatTimer();
        // 关闭连接
        $connection?->close();
    }

    /**
     * 获取连接
     *
     * @param string $connectionId
     * @return Connection|null
     */
    public static function Get(string $connectionId): ?Connection
    {
        return static::$connections[$connectionId] ?? null;
    }

    /**
     * 获取所有连接
     *
     * @param int|null $page
     * @param int|null $size
     * @return Connection[]
     */
    public static function All(?int $page = null, ?int $size = null): array
    {
        // 是否需要分页输出
        return ($page and $size) ?
            // 使用slice进行模拟分页
            array_slice(static::$connections, ($page - 1) * $size, $size, true) :
            // 输出全部
            static::$connections;
    }

    /**
     * 移除ws连接
     *
     * @param string $connectionId
     * @param bool $close
     * @return array
     */
    public static function Delete(string $connectionId, bool $close = true): array
    {
        // 获取连接
        $connection = static::$connections[$connectionId] ?? null;
        if ($close) {
            static::Disconnect($connectionId);
        }
        // 移除静态数组内的连接对象
        unset(static::$connections[$connectionId]);
        // 返回info
        return $connection ? $connection->getInfos() : [];
    }

    /**
     * 移除ws连接
     *
     * @return array[]
     */
    public static function DeleteAll(bool $close = true): array
    {
        $result = [];
        foreach (static::$connections as $connection) {
            $result[] = $connection->getInfos();
            if ($close) {
                static::Disconnect($connection->getInfo('connectionId', ''));
            }
            $connection->close();
        }
        static::$connections = [];
        return $result;
    }

    /**
     * 获取当前连接数
     *
     * @return int
     */
    public static function Count(): int
    {
        return static::$count;
    }

    /** @inheritdoc  */
    public function onWorkerStart($worker): void
    {
        if ($worker instanceof Worker and $worker->count !== 1) {
            throw new ConnectionsManagerException('Connections Manager can only run in single-process mode.');
        }
        parent::onWorkerStart($worker);
    }
}
