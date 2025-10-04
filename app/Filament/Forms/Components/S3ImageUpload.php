<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class S3ImageUpload extends Field
{
    protected string $view = 'filament.forms.components.s3-image-upload';

    protected string $uploadEndpoint = '/api/upload/image';

    public function uploadEndpoint(string $endpoint): static
    {
        $this->uploadEndpoint = $endpoint;

        return $this;
    }

    public function getUploadEndpoint(): string
    {
        return $this->uploadEndpoint;
    }
}
