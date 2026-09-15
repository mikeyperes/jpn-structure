<?php

declare(strict_types=1);

namespace Hexa\Jpn;

use Hexa\Jpn\Admin\EventAdmin;
use Hexa\Jpn\Admin\EventExports;
use Hexa\Jpn\Admin\HostRole;
use Hexa\Jpn\Admin\NotificationDashboard;
use Hexa\Jpn\Content\AcfFields;
use Hexa\Jpn\Content\ContentTypes;
use Hexa\Jpn\Cli\MigrationCommand;
use Hexa\Jpn\Events\EventDates;
use Hexa\Jpn\Events\EventQueries;
use Hexa\Jpn\Events\EventRelations;
use Hexa\Jpn\Frontend\Privacy;
use Hexa\Jpn\Frontend\Shortcodes;
use Hexa\Jpn\Integration\CoreIntegration;
use Hexa\Jpn\Rest\EventBindings;
use Hexa\Jpn\Rest\EventController;
use Hexa\Jpn\Rest\HostController;

final class Plugin
{
    private static ?self $instance = null;

    public static function boot(): self
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        self::$instance = new self();
        self::$instance->register();

        return self::$instance;
    }

    private function register(): void
    {
        $dates = new EventDates();
        $queries = new EventQueries($dates);
        $relations = new EventRelations($queries);

        (new ContentTypes())->register();
        (new AcfFields())->register();
        (new HostRole())->register();
        (new EventAdmin($dates))->register();
        (new EventExports($queries))->register();
        (new NotificationDashboard($queries, $dates))->register();
        $relations->register();
        (new Shortcodes($queries, $dates))->register();
        (new Privacy())->register();

        $bindings = new EventBindings();
        (new EventController($bindings, $dates, $relations))->register();
        (new HostController())->register();
        MigrationCommand::register();

        add_action('plugins_loaded', [CoreIntegration::class, 'boot'], 20);
    }
}
