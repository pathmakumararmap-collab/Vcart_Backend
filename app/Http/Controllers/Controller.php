<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Vcart E-commerce API',
    description: 'REST API for the Vcart Ecommerce System — admin panel, customer storefront, POS, warehouse & inventory management, coupons, payments, and reporting. All sales channels (website, Facebook, outlet POS) share a single central inventory.',
    contact: new OA\Contact(email: 'support@royalsl.example'),
)]
#[OA\Server(url: '/api/v1', description: 'API v1')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    name: 'Authorization',
    in: 'header',
    description: 'Sanctum personal access token. Send as: Authorization: Bearer {token}',
)]
#[OA\Tag(name: 'Auth', description: 'Registration, login, logout')]
#[OA\Tag(name: 'Catalog', description: 'Public product & category browsing')]
#[OA\Tag(name: 'Cart', description: 'Customer shopping cart')]
#[OA\Tag(name: 'Customer Orders', description: 'Customer checkout, order history, addresses')]
#[OA\Tag(name: 'Admin Products', description: 'Admin product catalog management')]
#[OA\Tag(name: 'Admin Users', description: 'Admin user, role & permission management')]
#[OA\Tag(name: 'Admin Orders', description: 'Admin order management (website, Facebook, POS)')]
#[OA\Tag(name: 'Coupons', description: 'Coupon management')]
#[OA\Tag(name: 'POS', description: 'Outlet point-of-sale')]
#[OA\Tag(name: 'Inventory', description: 'Warehouses, stock, transfers, purchases, suppliers')]
#[OA\Tag(name: 'Reports', description: 'Sales, order, product, and stock reports')]
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;
}
