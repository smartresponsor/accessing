<?php

declare(strict_types=1);

namespace App\Accessing\Value\Surface;

use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;

final readonly class AccessPageSurfaceContract implements InterfaceTemplateRenderableInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $view,
        public string $templateName,
        public array $parameters,
        public int $statusCode = 200,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toTemplateContext(): array
    {
        return [
            'word' => 'access',
            'view' => $this->view,
            'templateName' => $this->templateName,
            ...$this->parameters,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFallbackData(): array
    {
        return [
            'word' => 'access',
            'view' => $this->view,
            'parameters' => $this->parameters,
        ];
    }

    public function templateName(): string
    {
        return $this->templateName;
    }
}
