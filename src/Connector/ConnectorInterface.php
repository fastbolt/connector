<?php

namespace Fastbolt\Connector;


use Symfony\Component\HttpFoundation\Request;

interface ConnectorInterface
{
    public const ACCEPT_HEADER_JSON = 'application/json';

    public const ACCEPT_HEADER_XML = 'application/xml';

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
    ): string;

    /**
     * @return string
     */
    public function getType(): string;
}