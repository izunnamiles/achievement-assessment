<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Run DatabaseSeeder after RefreshDatabase's migrate:fresh, so reference
     * data (achievements, badges, system settings, products) - now seeded
     * only via Seeders, not the migrations themselves - is present for tests
     * that rely on it.
     */
    protected bool $seed = true;
}
