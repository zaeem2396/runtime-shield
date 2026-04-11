<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeShield\Support\PackageVersion;

final class InstallCommand extends Command
{
    /** @var string */
    protected $signature = 'runtime-shield:install';

    /** @var string */
    protected $description = 'Publish the RuntimeShield configuration file';

    public function handle(): int
    {
        $this->comment(sprintf(
            'Installing RuntimeShield v%s...',
            PackageVersion::VERSION,
        ));

        $this->callSilently('vendor:publish', [
            '--tag' => 'runtime-shield-config',
        ]);

        $this->info('Configuration published to config/runtime_shield.php');
        $this->line('');
        $this->line('Next steps:');
        $this->line('  1. Review <comment>config/runtime_shield.php</comment>');
        $this->line('  2. Add <comment>RuntimeShieldMiddleware</comment> to <comment>bootstrap/app.php</comment> (Laravel 11/12/13):');
        $this->line('');
        $this->line('     <comment>->withMiddleware(function (Middleware $middleware): void {</comment>');
        $this->line('         <comment>$middleware->append(\RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware::class);</comment>');
        $this->line('     <comment>})</comment>');
        $this->line('');
        $this->line('     For Laravel 10, add it to the <comment>$middleware</comment> array in <comment>app/Http/Kernel.php</comment>.');
        $this->line('');
        $this->info('RuntimeShield installed successfully.');

        return self::SUCCESS;
    }
}
