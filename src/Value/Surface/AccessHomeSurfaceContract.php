<?php

declare(strict_types=1);

namespace App\Accessing\Value\Surface;

use App\Interfacing\Contract\Surface\InterfaceSurfaceRenderableInterface;

final readonly class AccessHomeSurfaceContract implements InterfaceSurfaceRenderableInterface
{
    public const WORD = 'access';

    /**
     * @param array<string, string> $slotMap
     * @param array<string, mixed>  $slots
     */
    public function __construct(
        public string $word,
        public string $view,
        public string $templateName,
        public array $slotMap,
        public array $slots,
        public int $statusCode = 200,
    ) {
    }

    /**
     * @return array{
     *     word: string,
     *     view: string,
     *     templateName: string,
     *     slotMap: array<string, string>,
     *     slots: array<string, mixed>,
     *     accessingProductName: string,
     *     user: mixed,
     *     events: array<int, mixed>
     * }
     */
    public function toTemplateContext(): array
    {
        return [
            'word' => $this->word,
            'view' => $this->view,
            'templateName' => $this->templateName,
            'slotMap' => $this->slotMap,
            'slots' => $this->slots,
            'accessingProductName' => is_scalar($this->slots['accessingProductName'] ?? null) ? (string) $this->slots['accessingProductName'] : 'Accessing',
            'user' => $this->slots['user'] ?? null,
            'events' => is_array($this->slots['events'] ?? null) ? array_values($this->slots['events']) : [],
        ];
    }

    /**
     * @return array{
     *     word: string,
     *     view: string,
     *     slots: array<string, mixed>
     * }
     */
    public function toFallbackData(): array
    {
        return [
            'word' => $this->word,
            'view' => $this->view,
            'slots' => $this->slots,
        ];
    }

    public function templateName(): string
    {
        return $this->templateName;
    }
}
