<?php

namespace SolutionPlus\DynamicPages\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DynamicPagesPublishRoutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dynamic-pages:publish-routes {--force : Overwrite existing published route files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the routes for the Dynamic Pages package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $routesPublishSubDirectory = $this->getRoutesPublishSubDirectory();
        $routeFiles = $this->getRouteFiles();

        if (!$this->option('force') && $this->areRoutesAlreadyPublished($routeFiles, $routesPublishSubDirectory)) {
            $this->warn("Routes have already been published in routes/{$routesPublishSubDirectory} directory.");
            return Command::SUCCESS;
        }

        if (!$this->shouldPublishAlreadyLoadedRoutes()) {
            $this->warn('Routes publishing is aborted.');
            return Command::SUCCESS;
        }

        $this->publishRouteFiles($routeFiles, $routesPublishSubDirectory);

        $this->callSilent('vendor:publish', [
            '--provider' => 'SolutionPlus\DynamicPages\DynamicPagesServiceProvider',
        ]);

        exec('composer dump-autoload');

        $this->info('Routes have been published successfully.');

        return Command::SUCCESS;
    }

    /**
     * Get the routes publish subdirectory from config.
     *
     * @return string
     */
    private function getRoutesPublishSubDirectory(): string
    {
        return config('dynamic_pages.routes_publish_subdirectory');
    }

    /**
     * Get the list of route files to be published.
     *
     * @return array
     */
    private function getRouteFiles(): array
    {
        return [
            'dynamic_pages_admin_routes.php',
            'dynamic_pages_support_routes.php',
            'dynamic_pages_website_routes.php',
        ];
    }

    /**
     * Check if routes are already published.
     *
     * @param array $routeFiles
     * @param string $routesPublishSubDirectory
     * @return bool
     */
    private function areRoutesAlreadyPublished(array $routeFiles, string $routesPublishSubDirectory): bool
    {
        foreach ($routeFiles as $routeFile) {
            if (!File::exists(base_path("routes/{$routesPublishSubDirectory}{$routeFile}"))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if routes should be published when they are already loaded.
     *
     * @return bool
     */
    private function shouldPublishAlreadyLoadedRoutes(): bool
    {
        if (config('dynamic_pages.load_routes')) {
            return $this->confirm('Loading routes is enabled in the configuration file. Do you want to publish them anyway?', false);
        }

        return true;
    }

    /**
     * Publish route files to the destination directory.
     *
     * @param array $routeFiles
     * @param string $routesPublishSubDirectory
     * @return void
     */
    private function publishRouteFiles(array $routeFiles, string $routesPublishSubDirectory): void
    {
        foreach ($routeFiles as $routeFile) {
            $sourcePath = __DIR__ . "/../../routes/{$routeFile}";
            $destinationPath = base_path("routes/{$routesPublishSubDirectory}{$routeFile}");
            File::copy($sourcePath, $destinationPath);
        }
    }
}
