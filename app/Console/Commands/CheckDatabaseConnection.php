<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:db-check')]
#[Description('Checks the configured database connection')]
class CheckDatabaseConnection extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing database connection...');

        try {
            $start = microtime(true);

            DB::connection()->getPdo();

            $result = DB::select('SELECT version() AS version');

            $elapsed = round((microtime(true) - $start) * 1000, 2);

            $this->newLine();
            $this->info('✓ Database connection successful');
            $this->line('Connection: ' . config('database.default'));
            $this->line('Database: ' . config('database.connections.' . config('database.default') . '.database'));
            $this->line('Host: ' . config('database.connections.' . config('database.default') . '.host'));
            $this->line('Latency: ' . $elapsed . ' ms');

            if (!empty($result)) {
                $this->newLine();
                $this->comment('Server Version:');
                $this->line($result[0]->version);
            }

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->newLine();
            $this->error('✗ Database connection failed');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
