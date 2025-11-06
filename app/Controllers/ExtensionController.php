<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\Response;

final class ExtensionController extends Controller
{
    public function request(): string
    {
        // TODO: Implement extension request workflow (photographer applies for due date extension).
        return Response::error('not implemented', 501);
    }

    public function approve(): string
    {
        // TODO: Implement extension approval workflow (device admin updates checkouts.due_at).
        return Response::error('not implemented', 501);
    }
}
