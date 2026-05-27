<?php

declare(strict_types=1);

namespace App\Accessing\Service\Config;

use App\Accessing\Form\Config\AccessingEnvironmentConfigFormType;
use App\Accessing\Value\Form\Config\AccessingEnvironmentConfigData;
use App\Administering\Service\Config\ConfigApplyService;
use App\Administering\Service\Config\ConfigFileWriterService;
use App\Administering\ServiceInterface\Config\AdministrationConfigToolServiceInterface;
use App\Administering\Value\Config\AdministrationConfigToolDescriptor;
use Symfony\Component\Yaml\Yaml;

final readonly class AccessingEnvironmentConfigService implements AdministrationConfigToolServiceInterface
{
    public function __construct(
        private string $projectDir,
        private ConfigApplyService $applyService,
        private ConfigFileWriterService $fileWriter,
    ) {
    }

    public function descriptor(): AdministrationConfigToolDescriptor
    {
        return new AdministrationConfigToolDescriptor(
            applicationCode: 'Accessing',
            toolCode: 'accessing.environment',
            label: 'Accessing Environment',
            description: 'Safe runtime flags and thresholds stored in the Accessing component runtime manifest.',
            formClass: AccessingEnvironmentConfigFormType::class,
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
            ],
            secretNames: [],
            applyStrategy: 'component_runtime_yaml',
        );
    }

    public function loadData(): object
    {
        $data = new AccessingEnvironmentConfigData();
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
        $values = $this->stateRows($payload, 'pending');
        $masked = [
            'accessing_mailer_sender' => $payload->mailerSender,
            'accessing_phone_verification_provider' => $payload->phoneVerificationProvider,
            'accessing_session_max_idle_days' => $payload->sessionMaxIdleDays,
            'accessing_recovery_code_ttl_minutes' => $payload->recoveryCodeTtlMinutes,
            'accessing_verification_code_ttl_minutes' => $payload->verificationCodeTtlMinutes,
            'accessing_account_lock_threshold' => $payload->accountLockThreshold,
            'accessing_account_lock_minutes' => $payload->accountLockMinutes,
        ];

        return $this->applyService->save($this->descriptor(), (string) ($context['actor'] ?? 'system'), $values, $masked, []);
    }

    public function apply(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);
        $patch = $this->runtimePatch($payload);
        $write = $this->fileWriter->write(
            $this->projectDir.'/../Accessing',
            'config/component/runtime.yaml',
            $patch,
            $this->descriptor()->writableFiles,
        );

        $status = 'applied' === $write['status'] ? 'applied' : 'failed';
        $values = $this->stateRows($payload, $status);

        return $this->applyService->apply(
            $this->descriptor(),
            (string) ($context['actor'] ?? 'system'),
            $values,
            $patch,
            [],
            [[
                'path' => $write['path'],
                'backup_path' => $write['backup_path'],
                'status' => $write['status'],
                'message' => $write['message'],
            ]],
            [],
            'applied' === $write['status'] ? null : $write['message'],
            $status,
        );
    }

    private function assertData(object $data): AccessingEnvironmentConfigData
    {
        if (!$data instanceof AccessingEnvironmentConfigData) {
            throw new \InvalidArgumentException('Accessing environment config expects AccessingEnvironmentConfigData.');
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function runtimeManifest(): array
    {
        $path = $this->projectDir.'/../Accessing/config/component/runtime.yaml';
        $parsed = is_file($path) ? Yaml::parseFile($path) : [];

        return is_array($parsed) ? $parsed : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function runtimePatch(AccessingEnvironmentConfigData $data): array
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

    /**
     * @return array<string, array{fieldType:string, secret:bool, current:?string, pending:?string, masked:?string, status:string}>
     */
    private function stateRows(AccessingEnvironmentConfigData $data, string $status): array
    {
        return [
            'accessing_mailer_sender' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->mailerSender, 'pending' => $data->mailerSender, 'masked' => null, 'status' => $status],
            'accessing_phone_verification_provider' => ['fieldType' => 'choice', 'secret' => false, 'current' => $data->phoneVerificationProvider, 'pending' => $data->phoneVerificationProvider, 'masked' => null, 'status' => $status],
            'accessing_session_max_idle_days' => ['fieldType' => 'integer', 'secret' => false, 'current' => $data->sessionMaxIdleDays, 'pending' => $data->sessionMaxIdleDays, 'masked' => null, 'status' => $status],
            'accessing_recovery_code_ttl_minutes' => ['fieldType' => 'integer', 'secret' => false, 'current' => $data->recoveryCodeTtlMinutes, 'pending' => $data->recoveryCodeTtlMinutes, 'masked' => null, 'status' => $status],
            'accessing_verification_code_ttl_minutes' => ['fieldType' => 'integer', 'secret' => false, 'current' => $data->verificationCodeTtlMinutes, 'pending' => $data->verificationCodeTtlMinutes, 'masked' => null, 'status' => $status],
            'accessing_account_lock_threshold' => ['fieldType' => 'integer', 'secret' => false, 'current' => $data->accountLockThreshold, 'pending' => $data->accountLockThreshold, 'masked' => null, 'status' => $status],
            'accessing_account_lock_minutes' => ['fieldType' => 'integer', 'secret' => false, 'current' => $data->accountLockMinutes, 'pending' => $data->accountLockMinutes, 'masked' => null, 'status' => $status],
        ];
    }
}
