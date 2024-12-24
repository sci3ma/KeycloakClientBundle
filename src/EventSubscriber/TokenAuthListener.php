<?php

declare(strict_types=1);

namespace Mainick\KeycloakClientBundle\EventSubscriber;

use Mainick\KeycloakClientBundle\Annotation\ExcludeTokenValidationAttribute;
use Mainick\KeycloakClientBundle\Interface\IamClientInterface;
use Mainick\KeycloakClientBundle\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class TokenAuthListener implements EventSubscriberInterface
{
    private const EXCLUDED_ROUTES = [
        'app.swagger',
        'app.swagger_ui',
    ];

    private const EXCLUDED_ROUTES_PREFIX = [
        'mainick_keycloak_security_auth_',
        '_wdt',
        '_profiler',
    ];

    public function __construct(
        private LoggerInterface $keycloakClientLogger,
        private IamClientInterface $iamClient,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['checkValidToken', 10],
        ];
    }

    public function checkValidToken(RequestEvent $requestEvent): void
    {
        if (!$requestEvent->isMainRequest()) {
            return;
        }

        $request = $requestEvent->getRequest();

        // Check if the route belongs to the API documentation generated by nelmio/api-doc-bundle
        // Check if the route belongs to the Controller generated by mainick/keycloak-client-bundle
        if ($this->shouldSkipRouteValidation($request->attributes->get('_route'))) {
            return;
        }

        // Check if the method has the ExcludeTokenValidationAttribute attribute
        if ($this->shouldSkipControllerValidation($request->attributes->get('_controller'))) {
            return;
        }

        $jwtToken = $request->headers->get('X-Auth-Token');
        if (!$jwtToken) {
            $this->setUnauthorizedResponse($requestEvent, 'Token not found');
            return;
        }

        if (!$this->validateToken($jwtToken, $request)) {
            $this->setUnauthorizedResponse($requestEvent, 'Token not valid');
            return;
        }
    }

    private function shouldSkipRouteValidation(?string $route): bool
    {
        if (null === $route) {
            return false;
        }

        return in_array($route, self::EXCLUDED_ROUTES, true) ||
            !empty(array_filter(self::EXCLUDED_ROUTES_PREFIX, static fn (string $prefix): bool => str_starts_with($route, $prefix)));
    }

    private function shouldSkipControllerValidation(mixed $controller): bool
    {
        if (!$controller) {
            return false;
        }

        if ($controller instanceof \Closure) {
            return true;
        }

        $controllerClass = null;
        $controllerMethod = null;
        if (is_array($controller)) {
            $controllerClass = is_object($controller[0]) ? get_class($controller[0]) : $controller[0];
            $controllerMethod = $controller[1];
        }
        else {
            // Check if "Controller::method" or "Controller:method" format
            $parts = preg_split('/:{1,2}/', $controller);
            if (count($parts) === 2) {
                $controllerClass = $parts[0];
                $controllerMethod = $parts[1];
            }
        }

        if (!isset($controllerClass) || !isset($controllerMethod)) {
            return false;
        }

        try {
            $reflectionMethod = new \ReflectionMethod($controllerClass, $controllerMethod);
            return !empty($reflectionMethod->getAttributes(ExcludeTokenValidationAttribute::class));
        }
        catch (\ReflectionException) {
            return false;
        }
    }

    private function validateToken(string $jwtToken, Request $request): bool
    {
        $token = new AccessToken();
        $token->setToken($jwtToken);
        $token->setRefreshToken('');
        $token->setExpires(time() + 3600);
        $userInfo = $this->iamClient->userInfo($token);
        if ($userInfo) {
            $request->attributes->set('user', $userInfo);
            return true;
        }

        return false;
    }

    private function setUnauthorizedResponse(RequestEvent $requestEvent, string $message): void
    {
        $this->keycloakClientLogger->error($message);
        $requestEvent->setResponse(new JsonResponse(['message' => 'Token not found'], Response::HTTP_UNAUTHORIZED));
    }
}
