<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DocumentService $service) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->service->statsFor($request->user())->toArray(),
        ]);
    }
}
