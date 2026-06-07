<?php

declare(strict_types=1);

namespace App\Accessing\Contract;

/**
 * Accessing's integration contract — the single source of truth for what
 * Accessing exposes to Administering and any other consumer.
 *
 * Rules:
 *  - Accessing owns this file.
 *  - Administering must NOT duplicate these values in its component.yaml.
 *  - Consumers call ComponentIntegrationContractRegistry::get('accessing')
 *    and read only the fields they need.
 *  - All class references are strings (no compile-time coupling to neighbors).
 */
final readonly class AccessIntegrationContract
{
    // -----------------------------------------------------------------
    // Ecosystem identity
    // -----------------------------------------------------------------

    /** What Accessing owns in the ecosystem */
    public string $owns;

    // -----------------------------------------------------------------
    // Subject identity (consumed by Rolling and Administering)
    // -----------------------------------------------------------------

    /**
     * Prefix used when building Rolling ACL subject identifiers.
     * Format: '{prefix}{userId}'
     * Example: 'accessing:user:42'.
     */
    public string $subjectPrefix;

    // -----------------------------------------------------------------
    // User administration services
    // (FQCNs consumed by Administering's admin bridge wiring)
    // -----------------------------------------------------------------

    /** FQCN of the user administration service */
    public string $administrationService;

    /** FQCN of the user administration projection provider */
    public string $administrationProjectionProvider;

    /** FQCN of the user administration action catalog */
    public string $administrationActionCatalog;

    /** FQCN of the user administration bridge */
    public string $administrationBridge;

    /** FQCN of the user administration request validator */
    public string $administrationRequestValidator;

    /** FQCN of the user administration audit recorder */
    public string $administrationAuditRecorder;

    /** FQCN of the user administration audit projection provider */
    public string $administrationAuditProjectionProvider;

    // -----------------------------------------------------------------
    // Readiness / diagnostics surface services
    // -----------------------------------------------------------------

    /** FQCN of the readiness report provider */
    public string $administrationReadinessReportProvider;

    /** FQCN of the execution readiness provider */
    public string $administrationExecutionReadinessProvider;

    /** FQCN of the remediation plan provider */
    public string $administrationRemediationPlanProvider;

    /** FQCN of the work plan provider */
    public string $administrationWorkPlanProvider;

    /** FQCN of the execution plan provider */
    public string $administrationExecutionPlanProvider;

    /** FQCN of the capability matrix provider */
    public string $administrationCapabilityMatrixProvider;

    /** FQCN of the contract matrix provider */
    public string $administrationContractMatrixProvider;

    /** FQCN of the health report provider */
    public string $administrationHealthReportProvider;

    /** FQCN of the diagnostic report provider */
    public string $administrationDiagnosticReportProvider;

    public function __construct(
        string $owns,
        string $subjectPrefix,
        string $administrationService,
        string $administrationProjectionProvider,
        string $administrationActionCatalog,
        string $administrationBridge,
        string $administrationRequestValidator,
        string $administrationAuditRecorder,
        string $administrationAuditProjectionProvider,
        string $administrationReadinessReportProvider,
        string $administrationExecutionReadinessProvider,
        string $administrationRemediationPlanProvider,
        string $administrationWorkPlanProvider,
        string $administrationExecutionPlanProvider,
        string $administrationCapabilityMatrixProvider,
        string $administrationContractMatrixProvider,
        string $administrationHealthReportProvider,
        string $administrationDiagnosticReportProvider,
    ) {
        $this->owns = $owns;
        $this->subjectPrefix = $subjectPrefix;
        $this->administrationService = $administrationService;
        $this->administrationProjectionProvider = $administrationProjectionProvider;
        $this->administrationActionCatalog = $administrationActionCatalog;
        $this->administrationBridge = $administrationBridge;
        $this->administrationRequestValidator = $administrationRequestValidator;
        $this->administrationAuditRecorder = $administrationAuditRecorder;
        $this->administrationAuditProjectionProvider = $administrationAuditProjectionProvider;
        $this->administrationReadinessReportProvider = $administrationReadinessReportProvider;
        $this->administrationExecutionReadinessProvider = $administrationExecutionReadinessProvider;
        $this->administrationRemediationPlanProvider = $administrationRemediationPlanProvider;
        $this->administrationWorkPlanProvider = $administrationWorkPlanProvider;
        $this->administrationExecutionPlanProvider = $administrationExecutionPlanProvider;
        $this->administrationCapabilityMatrixProvider = $administrationCapabilityMatrixProvider;
        $this->administrationContractMatrixProvider = $administrationContractMatrixProvider;
        $this->administrationHealthReportProvider = $administrationHealthReportProvider;
        $this->administrationDiagnosticReportProvider = $administrationDiagnosticReportProvider;
    }

    /**
     * Build from the 'integration' section of Accessing's component.yaml.
     *
     * @param array<string, mixed> $data
     */
    public static function fromYaml(array $data): self
    {
        return new self(
            owns: self::stringValue($data['owns'] ?? null, 'authentication_session_identity'),
            subjectPrefix: self::stringValue($data['subject_prefix'] ?? null, 'accessing:user:'),
            administrationService: self::stringValue($data['administration_service'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationServiceInterface::class),
            administrationProjectionProvider: self::stringValue($data['administration_projection_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationProjectionProviderInterface::class),
            administrationActionCatalog: self::stringValue($data['administration_action_catalog'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationActionCatalogInterface::class),
            administrationBridge: self::stringValue($data['administration_bridge'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationBridgeInterface::class),
            administrationRequestValidator: self::stringValue($data['administration_request_validator'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationRequestValidatorInterface::class),
            administrationAuditRecorder: self::stringValue($data['administration_audit_recorder'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationAuditRecorderInterface::class),
            administrationAuditProjectionProvider: self::stringValue($data['administration_audit_projection_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationAuditProjectionProviderInterface::class),
            administrationReadinessReportProvider: self::stringValue($data['administration_readiness_report_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationReadinessReportProviderInterface::class),
            administrationExecutionReadinessProvider: self::stringValue($data['administration_execution_readiness_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface::class),
            administrationRemediationPlanProvider: self::stringValue($data['administration_remediation_plan_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationRemediationPlanProviderInterface::class),
            administrationWorkPlanProvider: self::stringValue($data['administration_work_plan_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationWorkPlanProviderInterface::class),
            administrationExecutionPlanProvider: self::stringValue($data['administration_execution_plan_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionPlanProviderInterface::class),
            administrationCapabilityMatrixProvider: self::stringValue($data['administration_capability_matrix_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationCapabilityMatrixProviderInterface::class),
            administrationContractMatrixProvider: self::stringValue($data['administration_contract_matrix_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationContractMatrixProviderInterface::class),
            administrationHealthReportProvider: self::stringValue($data['administration_health_report_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationHealthReportProviderInterface::class),
            administrationDiagnosticReportProvider: self::stringValue($data['administration_diagnostic_report_provider'] ?? null, \App\Accessing\ServiceInterface\Admin\AccessUserAdministrationDiagnosticReportProviderInterface::class),
        );
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
}
