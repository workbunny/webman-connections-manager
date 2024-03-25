<?php declare(strict_types=1);

use Webman\Route;
use Workbunny\WebmanConnectionsManager\App\Controller\IndexController;
use Workbunny\WebmanConnectionsManager\App\Middleware\ConnectionsManagerMiddleware;

Route::group('/workbunny/webman-connections-manager', function () {
    // 获取连接
    Route::get('/get', [IndexController::class, 'get']);
    // 连接列表
    Route::get('/list', [IndexController::class, 'list']);
    // 查询连接
    Route::get('/query', [IndexController::class, 'query']);
    // 查询连接数
    Route::get('/count', [IndexController::class, 'count']);

    // 新增/开启连接
    Route::post('/create', [IndexController::class, 'create']);
    // 移除连接
    Route::delete('/remove', [IndexController::class, 'remove']);
    // 移除连接
    Route::delete('/remove/all', [IndexController::class, 'removeAll']);
    // 断开连接
    Route::post('/disconnect', [IndexController::class, 'disconnect']);

    // 发送消息
    Route::post('/send', [IndexController::class, 'send']);
})->middleware([
    ConnectionsManagerMiddleware::class
]);