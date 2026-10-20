<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * Auth pages render as a card over the blurred cinema background.
     * $errorBanner is shown in a red banner whenever the form comes back with errors.
     */
    public function __construct(
        public string $heading = 'Welcome',
        public ?string $subheading = null,
        public bool $admin = false,
        public ?string $errorBanner = 'Please fix the highlighted fields.',
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
