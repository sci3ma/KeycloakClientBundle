KeycloakClientBundle
====================

[![Latest Version](https://img.shields.io/github/release/mainick/KeycloakClientBundle.svg?style=flat-square)](https://github.com/mainick/KeycloakClientBundle/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/mainick/keycloak-client-bundle.svg?style=flat-square)](https://packagist.org/packages/mainick/keycloak-client-bundle)

The `KeycloakClientBundle` bundle is a wrapper for the `stevenmaguire/oauth2-keycloak` package,
designed to simplify Keycloak integration into your application in Symfony and provide additional functionality
for token management and user information access.
It also includes a listener to verify the token on every request.

## Configuration

Before installing this package, you need to configure it manually.
You can do this by creating a `mainick_keycloak_client.yaml` file in the `config/packages` directory of your project
and adding the following configuration:

```yaml
# config/packages/mainick_keycloak_client.yaml

mainick_keycloak_client:
  keycloak:
    verify_ssl: '%env(bool:IAM_VERIFY_SSL)%'
    base_url: '%env(IAM_BASE_URL)%'
    realm: '%env(IAM_REALM)%'
    client_id: '%env(IAM_CLIENT_ID)%'
    client_secret: '%env(IAM_CLIENT_SECRET)%'
    redirect_uri: '%env(IAM_REDIRECT_URI)%'
    encryption_algorithm: '%env(IAM_ENCRYPTION_ALGORITHM)%'
    encryption_key: '%env(IAM_ENCRYPTION_KEY)%'
    encryption_key_path: '%env(IAM_ENCRYPTION_KEY_PATH)%'
    version: '%env(IAM_VERSION)%'
```

Additionally, it's recommended to add the following environment variables to your project's environment file
(e.g., `.env` or `.env.local`) with the appropriate values for your configuration:

```shell
###> mainick/keycloak-client-bundle ###
IAM_VERIFY_SSL=true # Verify SSL certificate
IAM_BASE_URL='<your-base-server-url>'  # Keycloak server URL
IAM_REALM='<your-realm>' # Keycloak realm name
IAM_CLIENT_ID='<your-client-id>' # Keycloak client id
IAM_CLIENT_SECRET='<your-client-secret>' # Keycloak client secret
IAM_REDIRECT_URI='<your-redirect-uri>' # Keycloak redirect uri
IAM_ENCRYPTION_ALGORITHM='<your-algorithm>' # RS256, HS256, etc.
IAM_ENCRYPTION_KEY='<your-public-key>' # public key
IAM_ENCRYPTION_KEY_PATH='<your-public-key-path>' # public key path
IAM_VERSION='<your-version-keycloak>' # Keycloak version
###< mainick/keycloak-client-bundle ###
```

Make sure to replace the placeholder values with your actual configuration values.
Once you have configured the package and environment variables, you can proceed with the installation.

## Installation

You can install this package using [Composer](http://getcomposer.org/):

```
composer require mainick/keycloak-client-bundle
```

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Mainick\KeycloakClientBundle\MainickKeycloakClientBundle::class => ['all' => true],
];
```

By configuring the package before installation, you ensure that it will be ready to use once installed.

## Usage

### Get the Keycloak client

You can get the Keycloak client by injecting the `Mainick\KeycloakClientBundle\Interface\IamClientInterface`
interface in your controller or service.

To use it, you need to add the following configuration
to your `config/services.yaml` file:

```yaml
services:
    Mainick\KeycloakClientBundle\Interface\IamClientInterface:
        alias: Mainick\KeycloakClientBundle\Provider\KeycloakClient
```

Then, you can use it in your controller or service:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Mainick\KeycloakClientBundle\Interface\IamClientInterface;

class IamService
{
    public function __construct(
        private IamClientInterface $iamClient
    ) {
    }
}
```

Perform the desired operations, such as retrieving additional user claims, assigned roles, associated groups, etc.


```php
// authenticate the user with username and password
$accessToken = $this->iamClient->authenticate($username, $password);

// authenticate the user with authorization code
$accessToken = $this->iamClient->authenticateCodeGrant($authorizationCode);

// verify and introspect the token
$userRepresentation = $this->iamClient->verifyToken($accessToken);
echo $userRepresentation->id; // id
echo $userRepresentation->username; // username
echo $userRepresentation->email; // email
echo $userRepresentation->firstName; // first name
echo $userRepresentation->lastName; // last name
echo $userRepresentation->name; // full name
echo $userRepresentation->groups; // all groups assigned to the user
echo $userRepresentation->realmRoles; // realm roles assigned to the user
echo $userRepresentation->clientRoles; // client roles assigned to the user
echo $userRepresentation->applicationRoles; // specific client roles assigned to the user
echo $userRepresentation->attributes; // additional user attributes

// refresh the token
$accessToken = $this->iamClient->refreshToken($accessToken);

// get user info
$userInfo = $this->iamClient->userInfo($accessToken);
echo $userInfo->id; // id
echo $userInfo->username; // username
echo $userInfo->email; // email
echo $userInfo->firstName; // first name
echo $userInfo->lastName; // last name
echo $userInfo->name; // full name
echo $userInfo->groups; // all groups assigned to the user
echo $userInfo->realmRoles; // realm roles assigned to the user
echo $userInfo->clientRoles; // client roles assigned to the user
echo $userInfo->applicationRoles; // specific client roles assigned to the user
echo $userInfo->attributes; // additional user attributes

// has role
$hasRole = $this->iamClient->hasRole($accessToken, $roleName);

// has any role
$hasAnyRole = $this->iamClient->hasAnyRole($accessToken, $roleNames);

// has all roles
$hasAllRoles = $this->iamClient->hasAllRoles($accessToken, $roleNames);

// has group
$hasGroup = $this->iamClient->hasGroup($accessToken, $groupName);

// has any group
$hasAnyGroup = $this->iamClient->hasAnyGroup($accessToken, $groupNames);

// has all groups
$hasAllGroups = $this->iamClient->hasAllGroups($accessToken, $groupNames);

// has scope
$hasScope = $this->iamClient->hasScope($accessToken, $scopeName);

// has any scope
$hasAnyScope = $this->iamClient->hasAnyScope($accessToken, $scopeNames);

// has all scopes
$hasAllScopes = $this->iamClient->hasAllScopes($accessToken, $scopeNames);
```

### Token Verification Listener

The KeycloakClientBundle includes a built-in listener, `TokenAuthListener`, that automatically validates the
JWT token on every request, ensuring the security and validity of your Keycloak integration.
This listener seamlessly handles token validation, allowing you to focus on your application's logic.

#### Using TokenAuthListener

In your Symfony project, add the `TokenAuthListener` to your `config/services.yaml` file as a registered service
and tag it as a `kernel.event_listener`. This will enable the listener to trigger on every request.

```yaml
services:
    Mainick\KeycloakClientBundle\EventSubscriber\TokenAuthListener:
        tags:
          - { name: kernel.event_listener, event: kernel.request, method: checkValidToken, priority: 0 }
```

#### Retrieve user information

Additionally, the `TokenAuthListener` adds an `user` attribute to the Symfony request object,
which contains the `UserRepresentationDTO` object.

```php
// get the user object from the request
$user = $request->attributes->get('user');
```

This `user` attribute contains the user information fetched from the JWT token and is an instance
of the `UserRepresentationDTO` class.
This allows your application to easily access user-related data when processing requests.

#### Excluding Routes from Token Validation

`TokenAuthListener` verifies the token for all incoming requests by default. However,
if you have specific routes for which you want to exclude token validation,
you can do so using the `ExcludeTokenValidationAttribute` attribute.

To exclude token validation for a particular route, apply the `ExcludeTokenValidationAttribute` to the
corresponding controller method.

```php
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Mainick\KeycloakClientBundle\Annotation\ExcludeTokenValidationAttribute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MyController extends AbstractController
{
    #[Route("/path/to/excluded/route", name: "app.excluded_route", methods: ["GET"])]
    #[ExcludeTokenValidationAttribute]
    public function excludedRouteAction(): Response
    {
        // This route is excluded from token validation.
        // ...
    }
}
```

When the `ExcludeTokenValidationAttribute` is applied to a method, `TokenAuthListener` will skip token validation
for requests to that specific route.

## Symfony Security Configuration

### Bundle configuration

To use the `KeycloakClientBundle` with Symfony's security component, you need to configure the security system to use the Keycloak client.

First you need to add a new section to the bundle configuration file:

```yaml
# config/packages/mainick_keycloak_client.yaml
mainick_keycloak_client:
  security:
    default_target_route_name: '%env(TARGET_ROUTE_NAME)%'
```

Then you need to configure the Keycloak redirect uri to the `mainick_keycloak_security_auth_connect_check` bundle route, which redirects to the default route or referer route after successful login.

It's recommended to change the following environment variable to your project's environment file
(e.g., `.env` or `.env.local`) with the uri. The same URI must be configured in the Keycloak application client:

```shell
###> mainick/keycloak-client-bundle ###
IAM_REDIRECT_URI='https://app.local/auth/keycloak/check'
TARGET_ROUTE_NAME=app_home
###< mainick/keycloak-client-bundle ###
```

Below is the complete configuration file:

```yaml
# config/packages/mainick_keycloak_client.yaml

mainick_keycloak_client:
  keycloak:
    verify_ssl: '%env(bool:IAM_VERIFY_SSL)%'
    base_url: '%env(IAM_BASE_URL)%'
    realm: '%env(IAM_REALM)%'
    client_id: '%env(IAM_CLIENT_ID)%'
    client_secret: '%env(IAM_CLIENT_SECRET)%'
    redirect_uri: '%env(IAM_REDIRECT_URI)%'
    encryption_algorithm: '%env(IAM_ENCRYPTION_ALGORITHM)%'
    encryption_key: '%env(IAM_ENCRYPTION_KEY)%'
    encryption_key_path: '%env(IAM_ENCRYPTION_KEY_PATH)%'
    version: '%env(IAM_VERSION)%'
  security:
      default_target_route_name: '%env(TARGET_ROUTE_NAME)%'
```

### Route configuration

Create a new file in ```config/routes/``` to load pre configured bundle routes.

```yaml
# config/routes/mainick_keycloak_security.yaml
mainick_keycloak_security_auth_connect:
  path:       /auth/keycloak/connect
  controller: Mainick\KeycloakClientBundle\Controller\KeycloakController::connect

mainick_keycloak_security_auth_connect_check:
  path:       /auth/keycloak/check
  controller: Mainick\KeycloakClientBundle\Controller\KeycloakController::connectCheck

mainick_keycloak_security_auth_logout:
  path:       /auth/keycloak/logout
  controller: Mainick\KeycloakClientBundle\Controller\KeycloakController::logout
```

### Security configuration

Then you need to configure the security system to use the Keycloak client.
You can do this by adding the following configuration to your `config/packages/security.yaml` file to use the bundle's UserProvider:

```yaml
# config/packages/security.yaml
providers:
  mainick_keycloak_user_provider:
    id: Mainick\KeycloakClientBundle\Security\User\KeycloakUserProvider
```

Here is a simple configuration that restrict access to ```/app/*``` routes only to user with roles "ROLE_USER" or "ROLE_ADMIN" :

```yaml
# config/packages/security.yaml
security:
  providers:
    mainick_keycloak_user_provider:
      id: Mainick\KeycloakClientBundle\Security\User\KeycloakUserProvider

  firewalls:
    dev:
      pattern: ^/(_(profiler|wdt)|css|images|js)/
      security: false

    auth_connect:
      pattern: /auth/keycloak/connect
      security: false

    secured_area:
      pattern: ^/
      provider: mainick_keycloak_user_provider
      entry_point: Mainick\KeycloakClientBundle\Security\EntryPoint\KeycloakAuthenticationEntryPoint
      custom_authenticator:
        - Mainick\KeycloakClientBundle\Security\Authenticator\KeycloakAuthenticator
      logout:
        path: mainick_keycloak_security_auth_logout

  role_hierarchy:
    ROLE_ADMIN: ROLE_USER

  # Easy way to control access for large sections of your site
  # Note: Only the *first* access control that matches will be used
  access_control:
    - { path: ^/app, roles: ROLE_ADMIN }
```

### Logout

To logout the user, you can use the following code:

```php
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Mainick\KeycloakClientBundle\Annotation\ExcludeTokenValidationAttribute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MyController extends AbstractController
{
    #[Route("/logout", name: "app.logout", methods: ["GET"])]
    public function logout(): RedirectResponse
    {
        return $this->redirectToRoute('mainick_keycloak_security_auth_logout');
    }
}
```

or create a link in your twig template:

```twig
<a href="{{ path('mainick_keycloak_security_auth_logout') }}">Logout</a>
```

This will redirect the user to the Keycloak logout page, where the user will be logged out from the Keycloak server.

### Redirect after login

To redirect the user to a specific route after login, you can set the `TARGET_ROUTE_NAME` environment variable
to the desired route name.

```shell
###> mainick/keycloak-client-bundle ###
TARGET_ROUTE_NAME=app_home
###< mainick/keycloak-client-bundle ###
```

This will redirect the user to the `app_home` route after a successful login.

### Troubleshooting - You have Access Denied in your browser

If you have an Access Denied error in your browser, it is maybe because scope roles is misconfigured.

For correction:

1. Check whether the **ROLE_ADMIN** and **ROLE_USER** roles have been created for the application client.
2. Click on **Client scopes** on left panel, then **roles**:
3. Click on **Mappers** tab, then **client roles**:
4. Disabled **Add to userinfo**, click on **Save**, then enabled **Add to userinfo** and click on **Save**:

Please check the roles assigned to the user in Keycloak and the roles configured in the Symfony security configuration.

## Running the Tests

Install the [Composer](http://getcomposer.org/) dependencies:

```bash
git clone https://github.com/mainick/KeycloakClientBundle.git
cd KeycloakClientBundle
composer update
```

Then run the test suite:

```bash
composer test
```

## Author

- [Maico Orazio](https://github.com/mainick)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.


## Contributing

We welcome your contributions! If you wish to enhance this package or have found a bug,
feel free to create a pull request or report an issue in the [issue tracker](https://github.com/mainick/KeycloakClientBundle/issues).

Please see [CONTRIBUTING](https://github.com/mainick/KeycloakClientBundle/blob/main/CONTRIBUTING.md) for details.

<!-- ## Contributing -->
<!-- Please see [Contributing](CONTRIBUTING.md) for details. -->

<!-- ## Acknowledgments -->
<!-- A big thank you to [Steven Maguire](https://github.com/stevenmaguire/oauth2-keycloak) for his `stevenmaguire/oauth2-keycloak` package upon which this wrapper is built. -->

<!-- ## Changelog -->
<!-- Please see [Changelog](CHANGELOG.md) for details. -->
