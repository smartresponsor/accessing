<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Recorder\SecurityEvent\AccessSecurityEventRecorder;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventRecorderInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use PHPUnit\Framework\TestCase;

final class AccessSecurityEventRecorderTest extends TestCase
{
    public function testLegacyContractIsOnlyATypedCompatibilityAlias(): void
    {
        self::assertFalse(is_subclass_of(
            AccessSecurityEventRecorderInterface::class,
            AccessSecurityEventServiceInterface::class,
        ));

        $interfaceMethod = new \ReflectionMethod(AccessSecurityEventRecorderInterface::class, 'record');
        self::assertSame('App\\Accessing\\ValueObject\\AccessSecurityEventType', (string) $interfaceMethod->getParameters()[0]->getType());
        self::assertSame('App\\Accessing\\ValueObject\\AccessSecurityEventSeverity', (string) $interfaceMethod->getParameters()[1]->getType());

        $method = new \ReflectionMethod(AccessSecurityEventRecorder::class, 'record');
        $parameters = $method->getParameters();

        self::assertSame('App\\Accessing\\ValueObject\\AccessSecurityEventType', (string) $parameters[0]->getType());
        self::assertSame('App\\Accessing\\ValueObject\\AccessSecurityEventSeverity', (string) $parameters[1]->getType());
    }

    public function testLegacyRecorderFilesContainNoFreeFormRecordContract(): void
    {
        $interfaceSource = file_get_contents(__DIR__.'/../../src/ServiceInterface/SecurityEvent/AccessSecurityEventRecorderInterface.php');
        $recorderSource = file_get_contents(__DIR__.'/../../src/Recorder/SecurityEvent/AccessSecurityEventRecorder.php');

        self::assertIsString($interfaceSource);
        self::assertIsString($recorderSource);
        self::assertStringNotContainsString('function record(string $eventType', $interfaceSource);
        self::assertStringNotContainsString('AccessSecurityEventRepositoryInterface', $recorderSource);
        self::assertStringContainsString('AccessSecurityEventServiceInterface', $recorderSource);
    }
}
