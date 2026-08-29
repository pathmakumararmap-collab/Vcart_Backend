<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Guest carts are identified by a client-supplied token (header `X-Cart-Token`)
 * rather than a server-side PHP session, since this is a stateless token-based
 * API consumed by browsers, mobile apps, and POS terminals alike.
 */
trait ResolvesCartToken
{
    protected function cartToken(Request $request): string
    {
        return $request->header('X-Cart-Token') ?: (string) Str::uuid();
    }
}
