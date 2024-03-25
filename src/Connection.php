<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager;

use Closure;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Timer;

class Connection extends AsyncTcpConnection
{
    /** @var array 连接信息 */
    protected array $_infos = [];

    /** @var int|null 心跳定时器 */
    protected ?int $_heartbeatTimer = null;

    /** @var int 心跳间隔 */
    protected int $_heartbeat = 0;

    /** @var Closure|null */
    protected ?Closure $_heartbeatCallback = null;

    /**
     * 设置心跳
     *
     * @param int $heartbeat
     * @param Closure|null $closure
     * @return void
     */
    public function setHeartbeatTimer(int $heartbeat, ?Closure $closure): void
    {
        $this->_heartbeat = $heartbeat;
        $this->_heartbeatCallback = $closure;
        $this->_heartbeatTimer = Timer::add($this->getHeartbeat(), $this->getHeartCallback());
    }

    /**
     * 移除心跳
     *
     * @return void
     */
    public function delHeartbeatTimer(): void
    {
        $this->_heartbeat = 0;
        if ($this->_heartbeatTimer) {
            Timer::del($this->_heartbeatTimer);
            $this->_heartbeatTimer = null;
        }
    }

    /**
     * 获取心跳定时器id
     *
     * @return int|null
     */
    public function getHeartbeatTimer(): ?int
    {
        return $this->_heartbeatTimer;
    }

    /**
     * 获取心跳回调
     *
     * @return Closure|null
     */
    public function getHeartCallback(): ?Closure
    {
        return $this->_heartbeatCallback;
    }

    /**
     * 获取心跳间隔
     *
     * @return int
     */
    public function getHeartbeat(): int
    {
        return $this->_heartbeat;
    }

    /**
     * 设置连接信息
     *
     * @param array $infos
     * @return void
     */
    public function setInfos(array $infos): void
    {
        $this->_infos = $infos;
    }

    /**
     * 获取连接信息
     *
     * @return array
     */
    public function getInfos(): array
    {
        return $this->_infos;
    }

    /**
     * 设置连接信息【单】
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setInfo(string $key, mixed $value): void
    {
        $this->_infos[$key] = $value;
    }

    /**
     * 获取连接信息【单】
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getInfo(string $key, mixed $default = null): mixed
    {
        return $this->_infos[$key] ?? $default;
    }
}
