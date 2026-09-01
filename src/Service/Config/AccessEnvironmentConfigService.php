<?php

declare(strict_types=1);

namespace App\Accessing\Service\Config;

use App\Accessing\Dto\Config\AccessEnvironmentConfigData;
use App\Accessing\Form\Config\AccessEnvironmentConfigType;
use App\Configuring\ServiceInterface\Config\ConfigToolServiceInterface;
use App\Configuring\ServiceInterface\Config\ConfigVariableToolServiceInterface;
use App\Configuring\ServiceInterface\Config\ManagedConfigVariablesProviderInterface;
use App\Configuring\Value\Config\ConfigToolDescriptor;
use App\Configuring\Value\Config\ConfigVariable;
use App\Configuring\Value\Config\ConfigVariableType;
use Symfony\Component\Yaml\Yaml;

/**
 * Accessing-owned configuration tool for authentication/runtime environment settings.
 */
final readonly class AccessEnvironmentConfigService implements ConfigToolServiceInterface, ManagedConfigVariablesProviderInterface, ConfigVariableToolServiceInterface
{
    public function descriptor(): ConfigToolDescriptor
    {
        return new ConfigToolDescriptor(
            applicationCode: 'Accessing',
            toolCode: 'accessing.environment',
            label: 'Accessing Environment',
            description: 'Safe runtime flags and thresholds stored in the Accessing component runtime manifest.',
            formClass: AccessEnvironmentConfigType::class,
            serviceClass: self::class,
            requiredPermission: 'administration.config.update',
            editableFields: [
                'mailerSender',
                'phoneVerificationProvider',
                'sessionMaxIdleDays',
                'recoveryCodeTtlMinutes',
                'verificationCodeTtlMinutes',
                'userLockThreshold',
                'userLockMinutes',
            ],
            sensitiveFields: [],
            readableFiles: ['config/component/runtime.yaml'],
            writableFiles: ['config/component/runtime.yaml'],
            metadata: [
                'section' => 'Configuration',
                'kind' => 'environment',
                'writer_owner' => 'accessing',
            ],
            secretNames: [],
            applyStrategy: 'component_runtime_yaml',
        );
    }

    /** @return iterable<ConfigVariable> */
    public function managedVariables(): iterable
    {
        yield ConfigVariable::yaml('accessing_mailer_sender', 'config/component/runtime.yaml')
            ->withLabel('Mailer sender')
            ->required();
        yield ConfigVariable::yaml('accessing_phone_verification_provider', 'config/component/runtime.yaml')
            ->withLabel('Phone verification provider')
            ->required()
            ->withConstraints(['choices' => ['fake', 'null']]);
        yield ConfigVariable::yaml('accessing_session_max_idle_days', 'config/component/runtime.yaml', ConfigVariableType::INT)
            ->withLabel('Session max idle days')
            ->required();
        yield ConfigVariable::yaml('accessing_recovery_code_ttl_minutes', 'config/component/runtime.yaml', ConfigVariableType::INT)
            ->withLabel('Recovery code TTL minutes')
            ->required();
        yield ConfigVariable::yaml('accessing_verification_code_ttl_minutes', 'config/component/runtime.yaml', ConfigVariableType::INT)
            ->withLabel('Verification code TTL minutes')
            ->required();
        yield ConfigVariable::yaml('accessing_user_lock_threshold', 'config/component/runtime.yaml', ConfigVariableType::INT)
            ->withLabel('User lock threshold')
            ->required();
        yield ConfigVariable::yaml('accessing_user_lock_minutes', 'config/component/runtime.yaml', ConfigVariableType::INT)
            ->withLabel('User lock minutes')
            ->required();
    }

    public function loadData(): object
    {
        $data = new AccessEnvironmentConfigData();
        $runtime = $this->runtimeManifest();

        $data->mailerSender = self::stringValue($runtime['accessing_mailer_sender'] ?? null, $data->mailerSender);
        $data->phoneVerificationProvider = self::stringValue($runtime['accessing_phone_verification_provider'] ?? null, $data->phoneVerificationProvider);
        $data->sessionMaxIdleDays = self::stringValue($runtime['accessing_session_max_idle_days'] ?? null, $data->sessionMaxIdleDays);
        $data->recoveryCodeTtlMinutes = self::stringValue($runtime['accessing_recovery_code_ttl_minutes'] ?? null, $data->recoveryCodeTtlMinutes);
        $data->verificationCodeTtlMinutes = self::stringValue($runtime['accessing_verification_code_ttl_minutes'] ?? null, $data->verificationCodeTtlMinutes);
        $data->userLockThreshold = self::stringValue($runtime['accessing_user_lock_threshold'] ?? null, $data->userLockThreshold);
        $data->userLockMinutes = self::stringValue($runtime['accessing_user_lock_minutes'] ?? null, $data->userLockMinutes);

        return $data;
    }

    public function save(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);

        return [
            'status' => 'pending',
            'messages' => ['Accessing environment changes were staged.'],
            'masked_changes' => self::stringMap($this->runtimePatch($payload)),
            'file_changes' => [],
            'secret_changes' => [],
        ];
    }

    public function apply(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);
        $patch = $this->runtimePatch($payload);
        $path = $this->runtimeManifestPath();
        $manifest = array_replace($this->runtimeManifest(), $patch);
        $written = $this->writeRuntimeManifest($path, $manifest);

        return [
            'status' => $written ? 'applied' : 'failed',
            'messages' => $written ? ['Accessing runtime manifest updated.'] : ['Accessing runtime manifest could not be written.'],
            'masked_changes' => self::stringMap($patch),
            'file_changes' => [[
                'path' => $path,
                'status' => $written ? 'applied' : 'failed',
            ]],
            'secret_changes' => [],
        ];
    }

    /** @return array<string, mixed> */
    public function loadVariableData(): array
    {
        $payload = $this->assertData($this->loadData());

        return [
            'accessing_mailer_sender' => $payload->mailerSender,
            'accessing_phone_verification_provider' => $payload->phoneVerificationProvider,
            'accessing_session_max_idle_days' => (int) $payload->sessionMaxIdleDays,
            'accessing_recovery_code_ttl_minutes' => (int) $payload->recoveryCodeTtlMinutes,
            'accessing_verification_code_ttl_minutes' => (int) $payload->verificationCodeTtlMinutes,
            'accessing_user_lock_threshold' => (int) $payload->userLockThreshold,
            'accessing_user_lock_minutes' => (int) $payload->userLockMinutes,
        ];
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<string, mixed> $context
     *
     * @return array{status:string, messages:list<string>, masked_changes:array<string, string>, file_changes:array<int, array<string, mixed>>, secret_changes:array<int, array<string, mixed>>}
     */
    public function saveVariables(array $variables, array $context = []): array
    {
        return [
            'status' => 'pending',
            'messages' => ['Accessing environment changes were staged.'],
            'masked_changes' => self::stringMap($this->runtimePatchFromVariables($variables)),
            'file_changes' => [],
            'secret_changes' => [],
        ];
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<string, mixed> $context
     *
     * @return array{status:string, messages:list<string>, masked_changes:array<string, string>, file_changes:array<int, array<string, mixed>>, secret_changes:array<int, array<string, mixed>>}
     */
    public function applyVariables(array $variables, array $context = []): array
    {
        $patch = $this->runtimePatchFromVariables($variables);
        $path = $this->runtimeManifestPath();
        $manifest = array_replace($this->runtimeManifest(), $patch);
        $written = $this->writeRuntimeManifest($path, $manifest);

        return [
            'status' => $written ? 'applied' : 'failed',
            'messages' => $written ? ['Accessing runtime manifest updated.'] : ['Accessing runtime manifest could not be written.'],
            'masked_changes' => self::stringMap($patch),
            'file_changes' => [[
                'path' => $path,
                'status' => $written ? 'applied' : 'failed',
            ]],
            'secret_changes' => [],
        ];
    }

    private function assertData(object $data): AccessEnvironmentConfigData
    {
        if (!$data instanceof AccessEnvironmentConfigData) {
            throw new \InvalidArgumentException('Accessing environment config expects AccessEnvironmentConfigData.');
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function runtimeManifest(): array
    {
        $path = $this->runtimeManifestPath();
        $parsed = is_file($path) ? Yaml::parseFile($path) : [];

        return is_array($parsed) ? self::stringKeyMap($parsed) : [];
    }

    private function runtimeManifestPath(): string
    {
        return dirname(__DIR__, 3).'/config/component/runtime.yaml';
    }

    /** @param array<string, mixed> $manifest */
    private function writeRuntimeManifest(string $path, array $manifest): bool
    {
        $yaml = Yaml::dump($manifest, 4, 2);
        $directory = dirname($path);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }

        return false !== @file_put_contents($path, $yaml);
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @return array<string, mixed>
     */
    private function runtimePatchFromVariables(array $variables): array
    {
        return [
            'accessing_mailer_sender' => self::stringValue($variables['accessing_mailer_sender'] ?? null, ''),
            'accessing_phone_verification_provider' => self::stringValue($variables['accessing_phone_verification_provider'] ?? null, 'fake'),
            'accessing_session_max_idle_days' => self::intValue($variables['accessing_session_max_idle_days'] ?? null, 30),
            'accessing_recovery_code_ttl_minutes' => self::intValue($variables['accessing_recovery_code_ttl_minutes'] ?? null, 30),
            'accessing_verification_code_ttl_minutes' => self::intValue($variables['accessing_verification_code_ttl_minutes'] ?? null, 10),
            'accessing_user_lock_threshold' => self::intValue($variables['accessing_user_lock_threshold'] ?? null, 5),
            'accessing_user_lock_minutes' => self::intValue($variables['accessing_user_lock_minutes'] ?? null, 15),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runtimePatch(AccessEnvironmentConfigData $data): array
    {
        return [
            'accessing_mailer_sender' => $data->mailerSender,
            'accessing_phone_verification_provider' => $data->phoneVerificationProvider,
            'accessing_session_max_idle_days' => (int) $data->sessionMaxIdleDays,
            'accessing_recovery_code_ttl_minutes' => (int) $data->recoveryCodeTtlMinutes,
            'accessing_verification_code_ttl_minutes' => (int) $data->verificationCodeTtlMinutes,
            'accessing_user_lock_threshold' => (int) $data->userLockThreshold,
            'accessing_user_lock_minutes' => (int) $data->userLockMinutes,
        ];
    }

    private static function stringValue(mixed $value, string $default): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || null === $value) {
            return (string) ($value ?? $default);
        }

        return $default;
    }

    private static function intValue(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    private static function stringMap(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[$key] = self::stringValue($value, '');
        }

        return $normalized;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function stringKeyMap(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
