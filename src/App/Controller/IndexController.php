<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager\App\Controller;

use app\exceptions\LogicException;
use Respect\Validation\Validator as v;
use support\Request;
use support\Response;
use Workbunny\WebmanConnectionsManager\ConnectionsManagerException;
use Workbunny\WebmanConnectionsManager\Server;

class IndexController
{
    /**
     * manager-server 信息
     *
     * @return Response
     */
    public function index(): Response
    {
        return rest_success([
            'server'    => 'workbunny/webman-connections-manager',
            'timestamp' => ms_time()
        ]);
    }

    /**
     * 获取信息
     *
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        parse_str($request->queryString(), $query);
        $data = v::input($query, [
            'page'      => v::nullable(v::intVal())->setName('page'),
            'page_size' => v::nullable(v::intVal())->setName('page_size'),
        ]);
        $res = [];
        $connections = Server::All(
            intval($data['page'] ?? null), intval($data['page_size'] ?? null)
        );
        foreach ($connections as $connection) {
            $res[] = $connection->getInfos();
        }
        return rest_success($res);
    }

    /**
     * 获取单个信息
     *
     * @param Request $request
     * @return Response
     */
    public function get(Request $request): Response
    {
        parse_str($request->queryString(), $query);
        $data = v::input($query, [
            'connectionId'  => v::stringType()->setName('connectionId'),
        ]);
        $res = Server::Get($data['connectionId'])?->getInfos();
        return rest_success($res ?: []);
    }

    /**
     * 批量获取ws信息
     *
     * @param Request $request
     * @return Response
     */
    public function query(Request $request): Response
    {
        parse_str($request->queryString(), $query);
        $data = v::input($query, [
            'query' => v::notEmpty()->each(
                v::arrayVal()
                    ->key('connectionId', v::stringType()->setName('query.*.connectionId'))
            )->setName('query'),
        ]);
        $res = [];
        foreach ($data['query'] ?? [] as $d) {
            $info = Server::Get($connectionId = $d['connectionId'])?->getInfos();
            if ($info) {
                $res[$data[$connectionId]] = $info;
            }
        }
        return rest_success($res);
    }

    /**
     * 统计ws连接数量
     *
     * @param Request $request
     * @return Response
     */
    public function count(Request $request): Response
    {
        parse_str($request->queryString(), $query);
        $data = v::input($query, [
            'detail' => v::intVal()->setName('detail'),
        ]);
        $detail = intval($data['detail']);
        if ($detail) {
            $total = $alive = 0;
            $connections = Server::All();
            foreach ($connections as $connection) {
                $total ++;
                if ($connection->getInfo('alive', 0) === 1) {
                    $alive ++;
                }
            }
            return rest_success([
                'total' => $total,
                'alive' => $alive
            ]);
        } else {
            return rest_success([
                'total' => Server::Count()
            ]);
        }
    }

    /**
     * ws 连接发送消息
     *
     * @param Request $request
     * @return Response
     */
    public function send(Request $request): Response
    {
        $data = v::input($request->all(), [
            'connectionId'  => v::stringType()->setName('connectionId'),
            'data'          => v::stringType()->setName('data'),
        ]);
        $room = Server::Get($data['connectionId']);
        $room?->send($data['data']);
        return rest_success(null);
    }

    /**
     * 创建ws连接
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        $data = v::input($request->all(), [
            'connectionId'  => v::stringType()->setName('connectionId'),
            'url'           => v::stringType()->setName('url'),
        ]);
        try {
            Server::Connect($data['connectionId'], $data['url']);
        } catch (ConnectionsManagerException $throwable) {
            throw new LogicException('Connection error. ', 1, [
                'errorInfo' => $throwable->getMessage(),
                'errorCode' => $throwable->getCode()
            ]);
        }
        return rest_success($data);
    }

    /**
     * 断开连接
     *
     * @param Request $request
     * @return Response
     */
    public function disconnect(Request $request): Response
    {
        $data = v::input($request->all(), [
            'connectionId'  => v::stringType()->setName('connectionId'),
        ]);
        Server::Disconnect($data['connectionId']);
        return rest_success(null);
    }

    /**
     * 销毁连接
     *
     * @param Request $request
     * @return Response
     */
    public function remove(Request $request): Response
    {
        parse_str($request->queryString(), $query);
        $data = v::input($query, [
            'connectionId'   => v::stringType()->setName('connectionId'),
        ]);
        return rest_success(Server::Delete($data['connectionId']));
    }

    /**
     * 销毁全部连接
     *
     * @return Response
     */
    public function removeAll(): Response
    {
        return rest_success(Server::DeleteAll());
    }
}
