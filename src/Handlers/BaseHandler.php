<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager\Handlers;

use GuzzleHttp\RequestOptions;
use Workbunny\WebmanConnectionsManager\App\Controller\IndexController;

class BaseHandler extends AbstractHandler
{

    /**
     * @param string $connectionId
     * @return false|array
     * @link IndexController::get()
     */
    public function Get(string $connectionId): false|array
    {
        if ($res = $this->request( 'GET', '/workbunny/webman-connections-manager/get', [
            RequestOptions::QUERY => self::_filter([
                'connectionId' => $connectionId,
            ])
        ])) {
            return json_decode($res, true);
        }
        return $res;
    }

    /**
     * @param array $query = [
     *  [
     *      'connectionId' => string,
     *  ]
     * ]
     * @return false|array
     * @link IndexController::list()
     */
    public function Query(array $query): false|array
    {
        foreach ($query as $item) {
            self::_verify($item, [
                ['connectionId', 'is_string', true],
            ]);
        }
        if ($res = $this->request( 'GET', '/workbunny/webman-connections-manager/query', [
            RequestOptions::QUERY => self::_filter([
                'query'   =>  $query,
            ])
        ])) {
            return json_decode($res, true);
        }
        return $res;
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return false|array
     * @link IndexController::list()
     */
    public function List(int $page, int $pageSize = 15): false|array
    {
        if ($res = $this->request( 'GET', '/workbunny/webman-connections-manager/list', [
            RequestOptions::QUERY => self::_filter([
                'page'      => $page,
                'page_size' => $pageSize
            ])
        ])) {
            return json_decode($res, true);
        }
        return $res;
    }

    /**
     * @return false|array
     * @link RoomManagerController::list()
     */
    public function All(): false|array
    {
        if ($res = $this->request( 'GET', '/workbunny/webman-connections-manager/list')) {
            return json_decode($res, true);
        }
        return $res;
    }

    /**
     * @return false|array
     * @link IndexController::count()
     */
    public function Count(): false|array
    {
        if ($res = $this->request( 'GET', '/workbunny/webman-connections-manager/count')) {
            return json_decode($res, true);
        }
        return $res;

    }

    /**
     * @param string $connectionId
     * @param string $url
     * @return false|array
     * @link IndexController::create()
     */
    public function Create(string $connectionId, string $url): false|array
    {
        if ($res = $this->request( 'POST', '/workbunny/webman-connections-manager/create', [
            RequestOptions::JSON => self::_filter([
                'connectionId' => $connectionId,
                'url'          => $url
            ])
        ])) {
            return json_decode($res, true);
        }
        return $res;
    }

    /**
     * @param string $connectionId
     * @return false|array
     * @link IndexController::remove()
     */
    public function Remove(string $connectionId): false|array
    {
        if ($res = $this->request( 'DELETE', '/workbunny/webman-connections-manager/remove', [
            RequestOptions::QUERY => self::_filter([
                'connectionId' => $connectionId,
            ])
        ])) {
            return json_decode($res, true);
        }
        return $res;
    }

    /**
     * @return false|array
     * @link IndexController::removeAll()
     */
    public function RemoveAll(): false|array
    {
        if ($res = $this->request( 'DELETE', '/workbunny/webman-connections-manager/remove/all')) {
            return json_decode($res, true);
        }
        return $res;
    }

    /**
     * @param string $connectionId
     * @return false|array
     * @link IndexController::disconnect()
     */
    public function Disconnect(string $connectionId): false|array
    {
        if ($res = $this->request( 'POST', '/workbunny/webman-connections-manager/disconnect', [
            RequestOptions::JSON => self::_filter([
                'connectionId' => $connectionId,
            ])
        ])) {
            return json_decode($res, true);
        }
        return $res;
    }

    /**
     * @param string $connectionId
     * @param string $data
     * @return false|array
     * @link IndexController::send()
     */
    public function Send(string $connectionId, string $data): false|array
    {
        if ($res = $this->request( 'POST', '/workbunny/webman-connections-manager/send', [
            RequestOptions::JSON => self::_filter([
                'connectionId' => $connectionId,
                'data'         => $data
            ])
        ])) {
            return json_decode($res, true);
        }
        return $res;
    }


}
