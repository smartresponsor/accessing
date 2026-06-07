<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Form\Access\AccessArchiveType;
use App\Accessing\Form\Access\AccessBulkType;
use App\Accessing\Form\Access\AccessCreateType;
use App\Accessing\Form\Access\AccessDeleteType;
use App\Accessing\Form\Access\AccessDuplicateType;
use App\Accessing\Form\Access\AccessExportType;
use App\Accessing\Form\Access\AccessImportType;
use App\Accessing\Form\Access\AccessRestoreType;
use App\Accessing\Form\Access\AccessUpdateType;
use App\Accessing\Service\Http\Access\AccessArchiveService;
use App\Accessing\Service\Http\Access\AccessBulkService;
use App\Accessing\Service\Http\Access\AccessCreateService;
use App\Accessing\Service\Http\Access\AccessDeleteService;
use App\Accessing\Service\Http\Access\AccessDuplicateService;
use App\Accessing\Service\Http\Access\AccessEditService;
use App\Accessing\Service\Http\Access\AccessExportService;
use App\Accessing\Service\Http\Access\AccessImportService;
use App\Accessing\Service\Http\Access\AccessIndexService;
use App\Accessing\Service\Http\Access\AccessNewService;
use App\Accessing\Service\Http\Access\AccessOperatorSecurityEventsService;
use App\Accessing\Service\Http\Access\AccessRestoreService;
use App\Accessing\Service\Http\Access\AccessShowService;
use App\Accessing\Service\Http\Access\AccessUpdateService;
use PHPUnit\Framework\TestCase;

final class AccessCrudGrammarAlignmentTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: class-string}>
     */
    public static function grammarServiceMap(): iterable
    {
        yield 'access.index' => ['access.index', AccessIndexService::class];
        yield 'access.show_id' => ['access.show_id', AccessShowService::class];
        yield 'access.show_slug' => ['access.show_slug', AccessShowService::class];
        yield 'access.new' => ['access.new', AccessNewService::class];
        yield 'access.create' => ['access.create', AccessCreateService::class];
        yield 'access.edit_id' => ['access.edit_id', AccessEditService::class];
        yield 'access.edit_slug' => ['access.edit_slug', AccessEditService::class];
        yield 'access.update_id' => ['access.update_id', AccessUpdateService::class];
        yield 'access.update_slug' => ['access.update_slug', AccessUpdateService::class];
        yield 'access.delete_id' => ['access.delete_id', AccessDeleteService::class];
        yield 'access.delete_slug' => ['access.delete_slug', AccessDeleteService::class];
        yield 'access.bulk' => ['access.bulk', AccessBulkService::class];
        yield 'access.import' => ['access.import', AccessImportService::class];
        yield 'access.export' => ['access.export', AccessExportService::class];
        yield 'access.archive_id' => ['access.archive_id', AccessArchiveService::class];
        yield 'access.archive_slug' => ['access.archive_slug', AccessArchiveService::class];
        yield 'access.restore_id' => ['access.restore_id', AccessRestoreService::class];
        yield 'access.restore_slug' => ['access.restore_slug', AccessRestoreService::class];
        yield 'access.duplicate_id' => ['access.duplicate_id', AccessDuplicateService::class];
        yield 'access.duplicate_slug' => ['access.duplicate_slug', AccessDuplicateService::class];
    }

    /**
     * @return iterable<string, array{0: string, 1: class-string}>
     */
    public static function grammarFormMap(): iterable
    {
        yield 'access.create' => ['access.create', AccessCreateType::class];
        yield 'access.update_id' => ['access.update_id', AccessUpdateType::class];
        yield 'access.update_slug' => ['access.update_slug', AccessUpdateType::class];
        yield 'access.delete_id' => ['access.delete_id', AccessDeleteType::class];
        yield 'access.delete_slug' => ['access.delete_slug', AccessDeleteType::class];
        yield 'access.bulk' => ['access.bulk', AccessBulkType::class];
        yield 'access.import' => ['access.import', AccessImportType::class];
        yield 'access.export' => ['access.export', AccessExportType::class];
        yield 'access.archive_id' => ['access.archive_id', AccessArchiveType::class];
        yield 'access.archive_slug' => ['access.archive_slug', AccessArchiveType::class];
        yield 'access.restore_id' => ['access.restore_id', AccessRestoreType::class];
        yield 'access.restore_slug' => ['access.restore_slug', AccessRestoreType::class];
        yield 'access.duplicate_id' => ['access.duplicate_id', AccessDuplicateType::class];
        yield 'access.duplicate_slug' => ['access.duplicate_slug', AccessDuplicateType::class];
    }

    /**
     * @dataProvider grammarServiceMap
     *
     * @param class-string $serviceClass
     */
    public function testEveryAccessCrudGrammarRouteHasPhysicalService(string $routeName, string $serviceClass): void
    {
        self::assertStringStartsWith('access.', $routeName);
        self::assertStringStartsWith('App\\Accessing\\Service\\Http\\Access\\', $serviceClass);
        self::assertTrue(class_exists($serviceClass), sprintf('Access CRUD route "%s" must resolve to an existing physical service.', $routeName));
    }

    public function testOperatorSurfaceBuilderIsNotAnAccessCrudEntrypointOwner(): void
    {
        self::assertFalse(
            class_exists('App\\Accessing\\Builder\\AccessOperatorSurfaceBuilder'),
            'Operator surface builder must not own access.* entrypoints; operator security-events handling lives in Service/Http/Access.',
        );

        self::assertTrue(class_exists(AccessOperatorSecurityEventsService::class));
    }

    /**
     * @dataProvider grammarFormMap
     *
     * @param class-string $formClass
     */
    public function testEveryAccessCrudGrammarFormHasPhysicalType(string $routeName, string $formClass): void
    {
        self::assertStringStartsWith('access.', $routeName);
        self::assertStringStartsWith('App\\Accessing\\Form\\Access\\', $formClass);
        self::assertTrue(class_exists($formClass), sprintf('Access CRUD route "%s" must resolve to an existing physical form type.', $routeName));
    }

    public function testCrudGrammarTreeDoesNotUseAuthenticationForms(): void
    {
        foreach (self::grammarFormMap() as [$routeName, $formClass]) {
            self::assertStringStartsWith('access.', $routeName);
            self::assertStringNotContainsString('Registration', $formClass);
            self::assertStringNotContainsString('SignIn', $formClass);
            self::assertStringNotContainsString('Recovery', $formClass);
            self::assertStringNotContainsString('Password', $formClass);
            self::assertStringNotContainsString('Verification', $formClass);
        }
    }
}
