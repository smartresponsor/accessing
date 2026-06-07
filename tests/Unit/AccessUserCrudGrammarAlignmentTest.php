<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Builder\AccessOperatorSurfaceBuilder;
use App\Accessing\Form\Access\User\AccessUserArchiveType;
use App\Accessing\Form\Access\User\AccessUserBulkType;
use App\Accessing\Form\Access\User\AccessUserCreateType;
use App\Accessing\Form\Access\User\AccessUserDeleteType;
use App\Accessing\Form\Access\User\AccessUserDuplicateType;
use App\Accessing\Form\Access\User\AccessUserExportType;
use App\Accessing\Form\Access\User\AccessUserImportType;
use App\Accessing\Form\Access\User\AccessUserRestoreType;
use App\Accessing\Form\Access\User\AccessUserUpdateType;
use App\Accessing\Service\Http\Access\User\AccessUserArchiveService;
use App\Accessing\Service\Http\Access\User\AccessUserBulkService;
use App\Accessing\Service\Http\Access\User\AccessUserCreateService;
use App\Accessing\Service\Http\Access\User\AccessUserDeleteService;
use App\Accessing\Service\Http\Access\User\AccessUserDuplicateService;
use App\Accessing\Service\Http\Access\User\AccessUserEditService;
use App\Accessing\Service\Http\Access\User\AccessUserExportService;
use App\Accessing\Service\Http\Access\User\AccessUserImportService;
use App\Accessing\Service\Http\Access\User\AccessUserIndexService;
use App\Accessing\Service\Http\Access\User\AccessUserNewService;
use App\Accessing\Service\Http\Access\User\AccessUserRestoreService;
use App\Accessing\Service\Http\Access\User\AccessUserShowService;
use App\Accessing\Service\Http\Access\User\AccessUserUpdateService;
use PHPUnit\Framework\TestCase;

final class AccessUserCrudGrammarAlignmentTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: class-string}>
     */
    public static function grammarServiceMap(): iterable
    {
        yield 'access.user.index' => ['access.user.index', AccessUserIndexService::class];
        yield 'access.user.show_id' => ['access.user.show_id', AccessUserShowService::class];
        yield 'access.user.show_slug' => ['access.user.show_slug', AccessUserShowService::class];
        yield 'access.user.new' => ['access.user.new', AccessUserNewService::class];
        yield 'access.user.create' => ['access.user.create', AccessUserCreateService::class];
        yield 'access.user.edit_id' => ['access.user.edit_id', AccessUserEditService::class];
        yield 'access.user.edit_slug' => ['access.user.edit_slug', AccessUserEditService::class];
        yield 'access.user.update_id' => ['access.user.update_id', AccessUserUpdateService::class];
        yield 'access.user.update_slug' => ['access.user.update_slug', AccessUserUpdateService::class];
        yield 'access.user.delete_id' => ['access.user.delete_id', AccessUserDeleteService::class];
        yield 'access.user.delete_slug' => ['access.user.delete_slug', AccessUserDeleteService::class];
        yield 'access.user.bulk' => ['access.user.bulk', AccessUserBulkService::class];
        yield 'access.user.import' => ['access.user.import', AccessUserImportService::class];
        yield 'access.user.export' => ['access.user.export', AccessUserExportService::class];
        yield 'access.user.archive_id' => ['access.user.archive_id', AccessUserArchiveService::class];
        yield 'access.user.archive_slug' => ['access.user.archive_slug', AccessUserArchiveService::class];
        yield 'access.user.restore_id' => ['access.user.restore_id', AccessUserRestoreService::class];
        yield 'access.user.restore_slug' => ['access.user.restore_slug', AccessUserRestoreService::class];
        yield 'access.user.duplicate_id' => ['access.user.duplicate_id', AccessUserDuplicateService::class];
        yield 'access.user.duplicate_slug' => ['access.user.duplicate_slug', AccessUserDuplicateService::class];
    }

    /**
     * @return iterable<string, array{0: string, 1: class-string}>
     */
    public static function grammarFormMap(): iterable
    {
        yield 'access.user.create' => ['access.user.create', AccessUserCreateType::class];
        yield 'access.user.update_id' => ['access.user.update_id', AccessUserUpdateType::class];
        yield 'access.user.update_slug' => ['access.user.update_slug', AccessUserUpdateType::class];
        yield 'access.user.delete_id' => ['access.user.delete_id', AccessUserDeleteType::class];
        yield 'access.user.delete_slug' => ['access.user.delete_slug', AccessUserDeleteType::class];
        yield 'access.user.bulk' => ['access.user.bulk', AccessUserBulkType::class];
        yield 'access.user.import' => ['access.user.import', AccessUserImportType::class];
        yield 'access.user.export' => ['access.user.export', AccessUserExportType::class];
        yield 'access.user.archive_id' => ['access.user.archive_id', AccessUserArchiveType::class];
        yield 'access.user.archive_slug' => ['access.user.archive_slug', AccessUserArchiveType::class];
        yield 'access.user.restore_id' => ['access.user.restore_id', AccessUserRestoreType::class];
        yield 'access.user.restore_slug' => ['access.user.restore_slug', AccessUserRestoreType::class];
        yield 'access.user.duplicate_id' => ['access.user.duplicate_id', AccessUserDuplicateType::class];
        yield 'access.user.duplicate_slug' => ['access.user.duplicate_slug', AccessUserDuplicateType::class];
    }

    /**
     * @dataProvider grammarServiceMap
     *
     * @param class-string $serviceClass
     */
    public function testEveryAccessUserCrudGrammarRouteHasPhysicalService(string $routeName, string $serviceClass): void
    {
        self::assertStringStartsWith('access.user.', $routeName);
        self::assertStringStartsWith('App\\Accessing\\Service\\Http\\Access\\User\\', $serviceClass);
        self::assertTrue(class_exists($serviceClass), sprintf('CRUD route "%s" must resolve to an existing physical service.', $routeName));
    }

    public function testOperatorSurfaceBuilderDoesNotOwnUserCrudEntrypoints(): void
    {
        $builder = new \ReflectionClass(AccessOperatorSurfaceBuilder::class);

        self::assertFalse($builder->hasMethod('users'));
        self::assertFalse($builder->hasMethod('userDetail'));
    }

    /**
     * @dataProvider grammarFormMap
     *
     * @param class-string $formClass
     */
    public function testEveryAccessUserCrudGrammarFormHasPhysicalType(string $routeName, string $formClass): void
    {
        self::assertStringStartsWith('access.user.', $routeName);
        self::assertStringStartsWith('App\\Accessing\\Form\\Access\\User\\', $formClass);
        self::assertTrue(class_exists($formClass), sprintf('CRUD route "%s" must resolve to an existing physical form type.', $routeName));
    }

    public function testCrudGrammarTreeDoesNotUseAuthenticationForms(): void
    {
        foreach (self::grammarFormMap() as [$routeName, $formClass]) {
            self::assertStringStartsWith('access.user.', $routeName);
            self::assertStringNotContainsString('Registration', $formClass);
            self::assertStringNotContainsString('SignIn', $formClass);
            self::assertStringNotContainsString('Recovery', $formClass);
            self::assertStringNotContainsString('Password', $formClass);
            self::assertStringNotContainsString('Verification', $formClass);
        }
    }
}
