<?php

declare(strict_types=1);

namespace App\Accessing\Service\Config;

use App\Accessing\Form\Config\AccessEnvironmentConfigFormType;
use App\Accessing\Value\Form\Config\AccessEnvironmentConfigData;
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
            formClass: AccessEnvironmentConfigFormType::class,
            serviceClass: self::class,
            requiredPermission: 'administration.config.update',
            editableFields: [
                'mailerSender',
                'phoneVerificationProvider',
                'sessionMaxIdleDays',
                'recoveryCodeTtlMinutes',
                'verificationCodeTtlMinutes',
                'accountLockThreshold',
                'accountLockMinutes',
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
        yield ConfigVariable::yaml('accessing_account_lock_threshold', 'config/component/runtime.yaml', ConfigVariableType::INT)
            ->withLabel('Account lock threshold')
            ->required();
        yield ConfigVariable::yaml('accessing_account_lock_minutes', 'config/component/runtime.yaml', ConfigVariableType::INT)
            ->withLabel('Account lock minutes')
            ->required();
    }

    public function loadData(): object
    {
        $data = new AccessEnvironmentConfigData();
        $runtime = $this->runtimeManifest();

        $data->mailerSender = (string) ($runtime['accessing_mailer_sender'] ?? $data->mailerSender);
        $data->phoneVerificationProvider = (string) ($runtime['accessing_phone_verification_provider'] ?? $data->phoneVerificationProvider);
        $data->sessionMaxIdleDays = (string) ($runtime['accessing_session_max_idle_days'] ?? $data->sessionMaxIdleDays);
        $data->recoveryCodeTtlMinutes = (string) ($runtime['accessing_recovery_code_ttl_minutes'] ?? $data->recoveryCodeTtlMinutes);
        $data->verificationCodeTtlMinutes = (string) ($runtime['accessing_verification_code_ttl_minutes'] ?? $data->verificationCodeTtlMinutes);
        $data->accountLockThreshold = (string) ($runtime['accessing_account_lock_threshold'] ?? $data->accountLockThreshold);
        $data->accountLockMinutes = (string) ($runtime['accessing_account_lock_minutes'] ?? $data->accountLockMinutes);

        return $data;
    }

    public function save(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);

        return [
            'status' => 'pending',
            'messages' => ['Accessing environment changes were staged.'],
            'masked_changes' => $this->runtimePatch($payload),
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
            'masked_changes' => $patch,
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
            'accessing_account_lock_threshold' => (int) $payload->accountLockThreshold,
            'accessing_account_lock_minutes' => (int) $payload->accountLockMinutes,
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
            'masked_changes' => $this->runtimePatchFromVariables($variables),
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
            'masked_changes' => $patch,
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

        return is_array($parsed) ? $parsed : [];
    }

    private function runtimeManifestPath(): string
    {
        return dirname(__DIR__, 3).'/config/component/runtime.yaml';
    }

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
            'accessing_mailer_sender' => (string) ($variables['accessing_mailer_sender'] ?? ''),
            'accessing_phone_verification_provider' => (string) ($variables['accessing_phone_verification_provider'] ?? 'fake'),
            'accessing_session_max_idle_days' => (int) ($variables['accessing_session_max_idle_days'] ?? 30),
            'accessing_recovery_code_ttl_minutes' => (int) ($variables['accessing_recovery_code_ttl_minutes'] ?? 30),
            'accessing_verification_code_ttl_minutes' => (int) ($variables['accessing_verification_code_ttl_minutes'] ?? 10),
            'accessing_account_lock_threshold' => (int) ($variables['accessing_account_lock_threshold'] ?? 5),
            'accessing_account_lock_minutes' => (int) ($variables['accessing_account_lock_minutes'] ?? 15),
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
            'accessing_account_lock_threshold' => (int) $data->accountLockThreshold,
            'accessing_account_lock_minutes' => (int) $data->accountLockMinutes,
        ];
    }
}
