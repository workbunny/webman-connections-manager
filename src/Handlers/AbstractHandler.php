<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager\Handlers;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Workbunny\WebmanConnectionsManager\Client;
use Workbunny\WebmanConnectionsManager\ConnectionsManagerException;
use Workbunny\WebmanConnectionsManager\Traits\ErrorMsg;

abstract class AbstractHandler
{
    use ErrorMsg;

    /** @var Client */
    protected Client $client;

    /** @var HttpClient */
    protected HttpClient $httpClient;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->httpClient = new HttpClient([
            'timeout' => config('plugin.workbunny.webman-connections-manager.client.timeout', 120),
            'headers' => [
                'Connection'    => 'keep-alive',
                'Content-Type'  => 'application/json'
            ],
            'base_uri' => config('plugin.workbunny.webman-connections-manager.client.host', 'http://127.0.0.1:7701')
        ]);
    }

    /**
     * 请求
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return bool|string
     */
    public function request(string $method, string $uri, array $options = []): bool|string
    {
        try {
            $options[RequestOptions::HEADERS]['timestamp'] = ms_time();
            $response = $this->httpClient()->request($method, $uri, $options);
            return $response->getBody()->getContents();
        } catch (RequestException $exception) {
            if ($exception->hasResponse()) {
                if (200 != $exception->getResponse()->getStatusCode()) {
                    return $this->setError(false, $exception->getResponse()->getBody()->getContents());
                }
            }
            return $this->setError(false, 'server notice：' . $exception->getMessage());
        } catch (GuzzleException $exception) {
            throw new ConnectionsManagerException($exception->getMessage(), $exception->getCode(), $exception);
        }

    }

    /**
     * 创建guzzle客户端
     * @return HttpClient
     */
    public function httpClient(): HttpClient
    {
        return $this->httpClient;
    }


    /**
     * @param array $input
     * @return array
     */
    protected function _filter(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function _verify(mixed $options, array $validators): void
    {
        if (!is_array($options)) {
            throw new \InvalidArgumentException('Invalid Options. ');
        }
        foreach ($validators as $validator) {
            if (count($validator) !== 3) {
                throw new \InvalidArgumentException('Invalid Validator. ', -1);
            }
            list($key, $handler, $required) = $validator;
            $requiredString = $required === false ? 'false' : 'true';
            if (isset($options[$key])) {
                if (!function_exists($handler)) {
                    throw new \InvalidArgumentException(
                        "Invalid Function: $key [handler: $handler require: $requiredString}]", -2);
                }
                if (call_user_func($handler, $options[$key])) {
                    continue;
                }
                goto fail;
            }
            if ($required) {
                fail:
                throw new \InvalidArgumentException(
                    "Invalid Argument: $key [handler: $handler require: $requiredString]",
                    -3
                );
            }
        }
    }
}