<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Command\AccessDiagnosticsCommand;
use App\Accessing\Command\AccessIdentityDiagnosticsCommand;
use App\Accessing\Command\AccessReportSecurityCommand;
use App\Accessing\Command\AccessSessionCleanupCommand;
use App\Accessing\Command\AccessVerificationCleanupCommand;
use App\Accessing\Context\AccessCurrentContext;
use App\Accessing\DTO\AccessPageViewDTO;
use App\Accessing\DTO\AccessSecondFactorEnrollmentDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Factory\Rendering\AccessPageViewFactory;
use App\Accessing\Policy\Lifecycle\AccessLifecyclePolicy;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\Resolver\Rendering\AccessPageTemplateResolver;
use App\Accessing\Responder\Rendering\AccessTwigPageResponder;
use App\Accessing\ServiceInterface\Session\AccessSessionServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Interfacing\ServiceInterface\Rendering\InterfaceRendererInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Response;

final class AccessRcCoverageExpansionTest extends TestCase
{
    public function testCommandsExerciseDiagnosticsAndMaintenanceContracts(): void
    {
        $server = $_SERVER;
        $env = $_ENV;

        try {
            $_SERVER['APP_ENV'] = 'test';
            $_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
            unset($_SERVER['MAILER_DSN'], $_ENV['MAILER_DSN']);
            $_ENV['ACCESSING_PHONE_VERIFICATION_PROVIDER'] = 'fake';

            $diagnostics = new CommandTester(new AccessDiagnosticsCommand());
            self::assertSame(0, $diagnostics->execute([]));
            self::assertStringContainsString('Accessing diagnostics', $diagnostics->getDisplay());
            self::assertStringContainsString('fake', $diagnostics->getDisplay());

            $_SERVER['APP_ENV'] = ['invalid'];
            $_SERVER['DATABASE_URL'] = '   ';
            $_SERVER['MAILER_DSN'] = 'smtp://configured';
            unset($_SERVER['ACCESSING_PHONE_VERIFICATION_PROVIDER'], $_ENV['ACCESSING_PHONE_VERIFICATION_PROVIDER']);
            $fallbackDiagnostics = new CommandTester(new AccessDiagnosticsCommand());
            self::assertSame(0, $fallbackDiagnostics->execute([]));
            self::assertStringContainsString('unknown', $fallbackDiagnostics->getDisplay());
            self::assertStringContainsString('not-set', $fallbackDiagnostics->getDisplay());

            unset($_SERVER['APP_ENV']);
            $_ENV['APP_ENV'] = 'env-only';
            $environmentDiagnostics = new CommandTester(new AccessDiagnosticsCommand());
            self::assertSame(0, $environmentDiagnostics->execute([]));
            self::assertStringContainsString('env-only', $environmentDiagnostics->getDisplay());

            $boolLabel = new \ReflectionMethod(AccessDiagnosticsCommand::class, 'boolLabel');
            $diagnosticsCommand = new AccessDiagnosticsCommand();
            self::assertSame('no', $boolLabel->invoke($diagnosticsCommand, ''));
            self::assertSame('no', $boolLabel->invoke($diagnosticsCommand, '   '));
            self::assertSame('yes', $boolLabel->invoke($diagnosticsCommand, 'configured'));
            self::assertSame('yes', $boolLabel->invoke($diagnosticsCommand, ' configured '));
        } finally {
            $_SERVER = $server;
            $_ENV = $env;
        }

        $users = $this->createMock(AccessRepositoryInterface::class);
        $users->expects(self::once())->method('findRecentUsers')->with(250)->willReturn([]);

        $events = $this->createMock(AccessSecurityEventRepositoryInterface::class);
        $reportUser = new AccessEntity('report-user@example.test');
        $events->expects(self::exactly(2))->method('findRecentEvents')->willReturnOnConsecutiveCalls(
            [],
            [
                new AccessSecurityEventEntity('sign_in_succeeded', 'warning', $reportUser),
                new AccessSecurityEventEntity('sign_in_failed', 'info'),
            ],
        );

        $identity = new CommandTester(new AccessIdentityDiagnosticsCommand($users, $events));
        self::assertSame(0, $identity->execute([]));
        self::assertStringContainsString('Locked users', $identity->getDisplay());

        $report = new CommandTester(new AccessReportSecurityCommand($events));
        self::assertSame(0, $report->execute([]));
        self::assertStringContainsString('Occurred', $report->getDisplay());

        $sessions = $this->createMock(AccessSessionServiceInterface::class);
        $sessions->expects(self::once())->method('cleanupSessions')->willReturn(3);
        $sessionCleanup = new CommandTester(new AccessSessionCleanupCommand($sessions));
        self::assertSame(0, $sessionCleanup->execute([]));
        self::assertStringContainsString('Removed 3 stale session record(s).', $sessionCleanup->getDisplay());

        $verification = $this->createMock(AccessVerificationChallengeServiceInterface::class);
        $verification->expects(self::once())->method('cleanupExpiredChallenges')->willReturn(4);
        $verificationCleanup = new CommandTester(new AccessVerificationCleanupCommand($verification));
        self::assertSame(0, $verificationCleanup->execute([]));
        self::assertStringContainsString('Removed 4 expired or consumed verification challenge record(s).', $verificationCleanup->getDisplay());
    }

    public function testContextLifecyclePageFactoryAndResponderContracts(): void
    {
        $context = new AccessCurrentContext(42, 'person@example.test', 'Person', ['ROLE_USER'], true, true, false);
        self::assertSame(42, $context->userId());
        self::assertSame('accessing:user:42', $context->subjectIdentifier());
        self::assertSame('person@example.test', $context->userIdentifier());
        self::assertSame('Person', $context->displayName());
        self::assertSame(['ROLE_USER'], $context->bootstrapRoles());
        self::assertTrue($context->locked());
        self::assertTrue($context->emailVerified());
        self::assertFalse($context->secondFactorEnabled());

        self::assertTrue(AccessLifecyclePolicy::canTransition('registered', 'verified'));
        self::assertTrue(AccessLifecyclePolicy::canTransition('active', 'active'));
        self::assertFalse(AccessLifecyclePolicy::canTransition('registered', 'deleted'));
        self::assertSame(['active', 'disabled', 'deleted'], AccessLifecyclePolicy::allowedTargets('locked'));
        self::assertSame([], AccessLifecyclePolicy::allowedTargets('unknown'));
        AccessLifecyclePolicy::assertCanTransition('verified', 'active');

        $factory = new AccessPageViewFactory();
        $user = new AccessEntity('person@example.test', 'Person');
        $form = new FormView();
        $enrollment = new AccessSecondFactorEnrollmentDTO('secret', 'otpauth://totp/example', ['recovery']);

        $views = [
            $factory->home($user, ['event']),
            $factory->overview($user, ['event']),
            $factory->verifyEmail($user, $form),
            $factory->requestPhoneVerification($user, $form),
            $factory->confirmPhoneVerification($user, $form),
            $factory->secondFactor($user, $form, $enrollment, true, true),
            $factory->sessions($user),
            $factory->securityEvents(['event']),
            $factory->password($user, $form),
            $factory->operatorUsers([$user]),
            $factory->operatorUserDetail($user, ['event']),
            $factory->operatorSecurityEvents(['event']),
            $factory->register($form, 422),
            $factory->signIn($form, 401),
            $factory->secondFactorChallenge($user, $form),
            $factory->requestRecovery($form),
            $factory->resetRecovery($form),
            $factory->resetPasswordRequest($form),
            $factory->resetPasswordCheckEmail(),
            $factory->resetPassword($form),
        ];

        self::assertSame('access.overview', $views[0]->view);
        self::assertSame('access.second_factor', $views[5]->view);
        self::assertSame($enrollment, $views[5]->parameters['enrollment']);
        self::assertSame(422, $views[12]->statusCode);
        self::assertSame(401, $views[13]->statusCode);
        self::assertSame('access.reset_password_reset', $views[19]->view);

        $renderer = $this->createMock(InterfaceRendererInterface::class);
        $response = new Response('rendered', 202);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'access/sign_up.html.twig',
                ['word' => 'access', 'view' => 'access.register', 'form' => 'form-value'],
                202,
            )
            ->willReturn($response);

        $responder = new AccessTwigPageResponder(new AccessPageTemplateResolver(), $renderer);
        self::assertSame(
            $response,
            $responder->respond(new AccessPageViewDTO('access.register', ['form' => 'form-value'], 202)),
        );
    }

    public function testLifecycleRejectsInvalidTransition(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid Accessing lifecycle transition from "deleted" to "active".');
        AccessLifecyclePolicy::assertCanTransition('deleted', 'active');
    }
}
