<?php

namespace Fastbolt\Connector;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseConnector implements ConnectorInterface
{
    /**
     * @var string
     */
    protected $webServiceHost;

    /**
     * @var callable|null
     */
    protected $clientFactory;

    /**
     * @param string     $path
     * @param string     $requestMethod
     * @param array|null $parameters
     * @param string     $acceptHeader
     *
     * @return string
     */
    public function request(
        string $path,
        string $requestMethod = Request::METHOD_POST,
        ?array $parameters = [],
        string $acceptHeader = self::ACCEPT_HEADER_JSON
    ): string {
        $clientFactory     = $this->getClientFactory();
        $client            = $clientFactory();
        $requestParameters = [
            'verify'  => false,
            'query'   => $this->getCredentials(),
            'headers' => [
                'Accept' => $acceptHeader,
            ],
        ];

        foreach ($parameters as $parameterRealm => $realmParameters) {
            // [ 'json' => $data ]
            if (!\is_array($realmParameters)) {
                $requestParameters[$parameterRealm] = $realmParameters;

                continue;
            }
            foreach ($realmParameters as $parameterName => $parameterValue) {
                if (!isset($requestParameters[$parameterRealm])) {
                    $requestParameters[$parameterRealm] = [];
                }
                $requestParameters[$parameterRealm][$parameterName] = $parameterValue;
            }
        }

        $url = '';
        try {
            /** @var Response $response */
            $response = $client->request(
                $requestMethod,
                $url = $this->getUrl($path),
                $requestParameters
            );
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            throw new HttpException(
                $response ? $response->getStatusCode() : 0,
                sprintf(
                    'Error connecting to %s (%s request to %s returned %s (%s)) (%s).',
                    $this->getType(),
                    $requestMethod,
                    $url,
                    $response ? $response->getStatusCode() : 'null',
                    $response ? $response->getReasonPhrase() : 'empty response',
                    $exception->getMessage()
                )
            );
        }

        if ($response->getStatusCode() !== HttpResponse::HTTP_OK) {
            throw new HttpException(
                $response->getStatusCode(),
                sprintf(
                    'Error connecting to %s (%s request to %s returned %s (%s)).',
                    $this->getType(),
                    $requestMethod,
                    $url,
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                )
            );
        }

        if ((string)($body = $response->getBody()->getContents()) === '') {
            throw new HttpException(
                $response->getStatusCode(),
                sprintf(
                    'Error connecting to %s (%s request to %s returned %s (%s), but resulted in empty data.).',
                    $this->getType(),
                    $requestMethod,
                    $url,
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                )
            );
        }

        return $body;
    }

    /**
     * @return callable
     */
    protected function getClientFactory(): callable
    {
        if (null === ($clientFactory = $this->clientFactory)) {
            $clientFactory = static function () {
                return new Client(['verify' => false]);
            };
        }

        return $clientFactory;
    }

    /**
     * @return array
     */
    abstract protected function getCredentials(): array;

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getUrl(string $path): string
    {
        return sprintf(
            '%s/%s',
            rtrim($this->webServiceHost, '/'),
            ltrim($path, '/')
        );
    }
}