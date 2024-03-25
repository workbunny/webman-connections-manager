<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager;

use Workbunny\WebmanConnectionsManager\Handlers\AbstractHandler;
use Workbunny\WebmanConnectionsManager\Handlers\BaseHandler;

/**
 * @property BaseHandler $base 基础接口
 */
class Client
{
    /**
     * Handler别名
     * @var string[]
     */
    protected static array $alias = [
        'base' => BaseHandler::class
    ];

    /** @var AbstractHandler[] */
    protected static array $handlers = [];

    /**
     * 注册自定义
     * @param string $name
     * @param string $class
     * @return bool
     */
    public static function register(string $name, string $class): bool
    {
        if (isset(self::$alias[$name])) {
            throw new ConnectionsManagerException("$name already exists.");
        }
        if ((new $class) instanceof AbstractHandler) {
            self::$alias[$name] = $class;
            return true;
        }
        return false;
    }

    /**
     * @param $name
     * @return mixed
     * @author chaz6chez
     */
    public function __get($name)
    {
        if (!isset($name) or !isset(self::$alias[$name])) {
            throw new ConnectionsManagerException("$name is invalid.");
        }
        // 实例化
        if (!isset(self::$handlers[$name])) {
            $class = self::$alias[$name];
            self::$handlers[$name] = new $class($this);
        }
        // 返回实例
        return self::$handlers[$name];
    }
}