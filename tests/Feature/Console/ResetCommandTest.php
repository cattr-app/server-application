<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * cattr:reset truncates every table (TRUNCATE commits implicitly in MySQL), so running it for real
 * would wipe the shared test database. The table listing is stubbed to return nothing and db:seed
 * is replaced with a recorder, so only the command flow is exercised: both db:seed calls and the
 * real module:seed command from nwidart/laravel-modules.
 */
class ResetCommandTest extends TestCase
{
    private SeedCommand $seedCommand;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::partialMock()->shouldReceive('getTableListing')->andReturn([]);

        $this->seedCommand = new class ($this->app['db']) extends SeedCommand {
            public array $calls = [];

            public function handle(): int
            {
                $this->calls[] = $this->input->getArgument('class') ?? $this->option('class');

                return self::SUCCESS;
            }
        };
        $this->app->instance(SeedCommand::class, $this->seedCommand);
    }

    public function test_reset_with_seed_runs_db_and_module_seeders(): void
    {
        $this->artisan('cattr:reset', ['--force' => true, '--seed' => true])
            ->expectsOutputToContain('Seeding module')
            ->assertSuccessful();

        $this->assertSame(['InitialSeeder', 'Database\\Seeders\\DatabaseSeeder'], $this->seedCommand->calls);
    }

    public function test_reset_without_seed_runs_only_initial_seeder(): void
    {
        $this->artisan('cattr:reset', ['--force' => true])
            ->doesntExpectOutputToContain('Seeding module')
            ->assertSuccessful();

        $this->assertSame(['InitialSeeder'], $this->seedCommand->calls);
    }
}
