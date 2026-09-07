<?php

namespace App\View\Components;

use App\Services\PlatformBrandService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $branding;

    public function __construct(PlatformBrandService $platformBrandService)
    {
        $this->branding = $platformBrandService->branding();
    }

    public function render(): View
    {
        return view('layouts.admin');
    }
}
