<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager\App\Middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use function Workbunny\WebmanConnectionsManager\rest_error;

/**
 * 过滤器
 *
 * 因为主sever和自定义进程共享了route列表，
 * 所以在此判定哪些接口属于主server，哪些接口属于connections-manager
 */
class ConnectionsManagerMiddleware implements MiddlewareInterface
{
    /** @inheritdoc  */
    public function process(Request $request, callable $handler): Response
    {
        $port = config('plugin.workbunny.webman-connections-manager.app.server_port', 7701);
        // 拒绝从其他端口进入
        if ($request->getLocalPort() !== (int)$port) {
            return rest_error(403, 'workbunny.webman-connections-manager.forbidden', 403);
        }
        // 拒绝访问非workbunny.webman-connections-manager的路由
        if (!str_starts_with($request->uri(), '/workbunny/webman-connections-manager')) {
            return rest_error(404, 'workbunny.webman-connections-manager.not-found', 404);
        }
        return $handler($request);
    }
}
