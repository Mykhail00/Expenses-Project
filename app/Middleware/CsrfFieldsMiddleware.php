<?php

namespace App\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;

class CsrfFieldsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Twig $twig,
        private readonly ContainerInterface $container
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $csrf = $this->container->get('csrf');

        $csrfNameKey = $csrf->getTokenNameKey();
        $csrfValueKey = $csrf->getTokenValueKey();
        $csrfName = $csrf->getTokenName();
        $csrfValue = $csrf->getTokenValue();

        $this->twig->getEnvironment()->addGlobal('csrf', [
            'keys' => [
                'name'  => $csrfNameKey,
                'value' => $csrfValueKey
            ],
            'name'  => $csrfName,
            'value' => $csrfValue,
            'fields' => <<<CSRF_FIELDS
<input type="hidden" name="$csrfNameKey" value="$csrfName">
<input type="hidden" name="$csrfValueKey" value="$csrfValue">
CSRF_FIELDS
        ]);

        return $handler->handle($request);
    }
}