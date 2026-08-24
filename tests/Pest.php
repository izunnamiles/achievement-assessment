<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Model doubles
|--------------------------------------------------------------------------
|
| Plain forceFill()'d instances for use in Unit tests. Model::factory()->make()
| still resolves nested Model::factory() attributes (and any definition()
| closures that query the database) for real, even under make() - which
| breaks in Unit tests, since that suite intentionally has no migrated
| database. These bypass factories entirely.
|
*/

function makeUser(array $attributes = []): \App\Models\User
{
    return (new \App\Models\User)->forceFill($attributes);
}

function makeProduct(array $attributes = []): \App\Models\Product
{
    return (new \App\Models\Product)->forceFill(array_merge(['price' => 0, 'stock' => 0], $attributes));
}

function makePurchase(array $attributes = []): \App\Models\Purchase
{
    return (new \App\Models\Purchase)->forceFill($attributes);
}

function makeBadge(array $attributes = []): \App\Models\Badge
{
    return (new \App\Models\Badge)->forceFill($attributes);
}

function makeAchievement(array $attributes = []): \App\Models\Achievement
{
    return (new \App\Models\Achievement)->forceFill($attributes);
}

function makeBankAccount(array $attributes = []): \App\Models\BankAccount
{
    return (new \App\Models\BankAccount)->forceFill($attributes);
}
