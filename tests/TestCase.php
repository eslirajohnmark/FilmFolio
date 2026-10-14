<?php

namespace Tests;

use App\Models\Admin;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Sign an administrator into the `admin` guard, leaving the member (`web`)
     * guard untouched, the way a browser holding an admin session would.
     */
    protected function actingAsAdmin(?Admin $admin = null): static
    {
        $this->app['auth']->guard('admin')->setUser($admin ?? Admin::factory()->create());

        return $this;
    }
}
