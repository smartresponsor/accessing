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
     * Format: '{prefix}{accountId}'
     * Example: 'accessing:account:42'.
     */
    public string $subjectPrefix;

    // -----------------------------------------------------------------
    // Account administration services
    // (FQCNs consumed by Administering's admin bridge wiring)
    // -----------------------------------------------------------------

    /** FQCN of the account administration service */
    public string $administrationService;

    /** FQCN of the account administration projection provider */
    public string $administrationProjectionProvider;

    /** FQCN of the account administration action catalog */
    public string $administrationActionCatalog;

    /** FQCN of the account administration bridge */
    public string $administrationBridge;

    /** FQCN of the account administration request validator */
    public string $administrationRequestValidator;

    /** FQCN of the account administration audit recorder */
    public string $administrationAuditRecorder;

    /** FQCN of the account administration audit projection provider */
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
            owns: (string) ($data['owns'] ?? 'authentication_session_identity'),
            subjectPrefix: (string) ($data['subject_prefix'] ?? 'accessing:account:'),
            administrationService: (string) ($data['administration_service'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationServiceInterface::class),
            administrationProjectionProvider: (string) ($data['administration_projection_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationProjectionProviderInterface::class),
            administrationActionCatalog: (string) ($data['administration_action_catalog'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationActionCatalogInterface::class),
            administrationBridge: (string) ($data['administration_bridge'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationBridgeInterface::class),
            administrationRequestValidator: (string) ($data['administration_request_validator'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationRequestValidatorInterface::class),
            administrationAuditRecorder: (string) ($data['administration_audit_recorder'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationAuditRecorderInterface::class),
            administrationAuditProjectionProvider: (string) ($data['administration_audit_projection_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationAuditProjectionProviderInterface::class),
            administrationReadinessReportProvider: (string) ($data['administration_readiness_report_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationReadinessReportProviderInterface::class),
            administrationExecutionReadinessProvider: (string) ($data['administration_execution_readiness_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationExecutionReadinessProviderInterface::class),
            administrationRemediationPlanProvider: (string) ($data['administration_remediation_plan_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationRemediationPlanProviderInterface::class),
            administrationWorkPlanProvider: (string) ($data['administration_work_plan_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationWorkPlanProviderInterface::class),
            administrationExecutionPlanProvider: (string) ($data['administration_execution_plan_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationExecutionPlanProviderInterface::class),
            administrationCapabilityMatrixProvider: (string) ($data['administration_capability_matrix_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationCapabilityMatrixProviderInterface::class),
            administrationContractMatrixProvider: (string) ($data['administration_contract_matrix_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationContractMatrixProviderInterface::class),
            administrationHealthReportProvider: (string) ($data['administration_health_report_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationHealthReportProviderInterface::class),
            administrationDiagnosticReportProvider: (string) ($data['administration_diagnostic_report_provider'] ?? \App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationDiagnosticReportProviderInterface::class),
        );
    }
}
