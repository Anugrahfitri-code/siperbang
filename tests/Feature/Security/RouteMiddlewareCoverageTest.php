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
}
