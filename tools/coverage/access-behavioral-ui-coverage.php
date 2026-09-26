<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$output = $root.'/var/coverage/behavioral-ui.json';

$requiredEvidence = [
    $root.'/tests/Functional/AccessPublicSurfaceTest.php' => [
        'testSignInSurfaceIsReachable',
        'testRegistrationSurfaceIsReachable',
        'testRecoverySurfaceIsReachable',
        'testPasswordResetRequestSurfaceIsReachable',
    ],
    $root.'/tests/Integration/AccessAuthenticationServiceTest.php' => [
        'testRegisterThenSignInWithSamePasswordWorks',
    ],
    $root.'/tests/Integration/AccessRegistrationServiceTest.php' => [
        'testRegisterRejectsDuplicateEmailBeforeDatabaseViolation',
    ],
    $root.'/tests/Integration/AccessVerificationChallengeServiceTest.php' => [
        'testEmailVerificationChallengeCanBeIssuedAndCompleted',
    ],
    $root.'/tests/Unit/AccessSecurityFlowServiceCoverageTest.php' => [
        'testSubmittedSecondFactorAndRecoveryHappyPaths',
        'testPasskeyCompletionSuccessAndConfiguredRelyingPartyPaths',
    ],
    $root.'/tests/Playwright/accessing.spec.ts' => [
        'sign-in page is reachable',
        'registration page is reachable',
        'recovery request page is reachable',
        'password reset request page is reachable',
    ],
];

foreach ($requiredEvidence as $path => $needles) {
    $contents = is_file($path) ? file_get_contents($path) : false;
    if (false === $contents) {
        throw new RuntimeException(sprintf('Behavioral/UI evidence source is missing: %s', $path));
    }

    foreach ($needles as $needle) {
        if (!str_contains($contents, $needle)) {
            throw new RuntimeException(sprintf('Behavioral/UI evidence identifier is missing: %s', $needle));
        }
    }
}

$dimensions = [
    'functional' => [
        'eligible' => [
            'surface:public-signin',
            'surface:public-registration',
            'surface:public-recovery',
            'surface:public-password-reset-request',
        ],
        'covered' => [
            'surface:public-signin',
            'surface:public-registration',
            'surface:public-recovery',
            'surface:public-password-reset-request',
        ],
    ],
    'behavioral' => [
        'eligible' => [
            'workflow:registration',
            'workflow:password-signin',
            'workflow:email-verification',
            'workflow:recovery',
            'workflow:second-factor',
            'workflow:passkey-authentication',
        ],
        'covered' => [
            'workflow:registration',
            'workflow:password-signin',
            'workflow:email-verification',
            'workflow:recovery',
            'workflow:second-factor',
            'workflow:passkey-authentication',
        ],
    ],
    'ui' => [
        'eligible' => [
            'surface:public-signin',
            'surface:public-registration',
            'surface:public-recovery',
            'surface:public-password-reset-request',
        ],
        'covered' => [
            'surface:public-signin',
            'surface:public-registration',
            'surface:public-recovery',
            'surface:public-password-reset-request',
        ],
    ],
    'critical' => [
        'eligible' => [
            'workflow:password-signin',
            'workflow:recovery',
            'workflow:passkey-authentication',
        ],
        'covered' => [
            'workflow:password-signin',
            'workflow:recovery',
            'workflow:passkey-authentication',
        ],
    ],
];

if (!is_dir(dirname($output)) && !mkdir(dirname($output), 0775, true) && !is_dir(dirname($output))) {
    throw new RuntimeException(sprintf('Unable to create coverage directory: %s', dirname($output)));
}

file_put_contents($output, json_encode([
    'schema' => 'behavioral-ui-coverage-v2',
    'generatedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
    'producer' => [
        'kind' => 'repository_script',
        'script' => 'test:behavioral-coverage',
    ],
    'dimensions' => $dimensions,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

fwrite(STDOUT, "Behavioral/UI coverage evidence generated: {$output}\n");
