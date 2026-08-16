<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteMiddlewareCoverageTest extends TestCase
{
    public function test_all_auth_protected_routes_have_active_middleware()
    {
        $routes = Route::getRoutes()->getRoutes();

        $authRoutesMissingActive = [];

        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();

            if (in_array('auth', $middlewares) && ! in_array('active', $middlewares)) {
                $authRoutesMissingActive[] = $route->uri();
            }
        }

        $this->assertEmpty(
            $authRoutesMissingActive,
            'Found auth-protected routes missing the "active" middleware: '.implode(', ', $authRoutesMissingActive)
        );
    }

    public function test_api_user_route_has_auth_and_active_middleware()
    {
        $route = collect(Route::getRoutes()->getRoutes())->first(function ($r) {
            return $r->uri() === 'api/user' && in_array('GET', $r->methods());
        });

        $this->assertNotNull($route, 'Route /api/user not found.');

        $middlewares = $route->gatherMiddleware();

        $this->assertContains('auth', $middlewares, '/api/user does not have auth middleware.');
        $this->assertContains('active', $middlewares, '/api/user does not have active middleware.');
    }
}
