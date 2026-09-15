<?php

declare(strict_types=1);

namespace Hexa\Jpn\Integration;

use Hexa\PluginCore\CoreBootstrap\CoreBootstrap;
use Hexa\PluginCore\CoreRuntime\PluginContext;

final class CoreIntegration
{
    private static ?CoreBootstrap $bootstrap = null;

    public static function boot(): void
    {
        if (self::$bootstrap instanceof CoreBootstrap) {
            return;
        }

        if (!class_exists(PluginContext::class) || !class_exists(CoreBootstrap::class)) {
            return;
        }

        $context = new PluginContext([
            'slug'        => 'jpn-structure',
            'basename'    => plugin_basename(HEXA_JPN_PLUGIN_FILE),
            'version'     => HEXA_JPN_VERSION,
            'path'        => HEXA_JPN_PLUGIN_DIR,
            'url'         => plugin_dir_url(HEXA_JPN_PLUGIN_FILE),
            'github_repo' => 'mikeyperes/jpn-structure',
            'admin_page'  => 'notifications-dashboard',
            'capability'  => 'manage_options',
        ]);

        self::$bootstrap = new CoreBootstrap($context);
        self::$bootstrap->boot();
    }

    public static function status(): array
    {
        if (!class_exists('Hexa\\PluginCore\\CoreRuntime\\CorePackageRuntime')) {
            return ['available' => false, 'healthy' => false, 'version' => null];
        }

        $runtime = 'Hexa\\PluginCore\\CoreRuntime\\CorePackageRuntime';

        return [
            'available' => true,
            'healthy'   => (bool) $runtime::healthy(),
            'version'   => (string) $runtime::selected_version(),
        ];
    }
}
