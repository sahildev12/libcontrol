<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public function __construct(
        public string $portal = 'branch',
        public string $title = '',
        public string $subtitle = '',
        public string $name = '',
        public ?string $logoUrl = null,
        public ?string $faviconUrl = null,
    ) {
        $this->name = $name ?: config('libcontrol.product.name');
    }

    public function render(): View
    {
        return view('layouts.guest');
    }
}
