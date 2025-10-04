<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class S3ImageUpload extends Field
{
    protected string $view = 'filament.forms.components.s3-image-upload';

    protected string $uploadEndpoint = '/api/upload/image';

    protected bool $multiple = false;

    public function uploadEndpoint(string $endpoint): static
    {
        $this->uploadEndpoint = $endpoint;

        return $this;
    }

    public function getUploadEndpoint(): string
    {
        return $this->uploadEndpoint;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }
}
