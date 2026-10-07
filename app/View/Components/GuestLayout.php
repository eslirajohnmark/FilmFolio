<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * Auth pages render as a card over the blurred cinema background.
     */
    public function __construct(
        public string $heading = 'Welcome',
        public ?string $subheading = null,
        public bool $admin = false,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
