<?php

declare(strict_types=1);

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class UploadReceiptRequestValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['receipt'] ?? null;

        // Validate uploaded file
        if (! $uploadedFile) {
            throw new ValidationException(['receipt' => ['Please select a receipt file']]);
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['receipt' => ['Failed to upload the receipt file']]);
        }

        // Validate file size
        $maxFileSize = 5 * 1024 * 1024;
        if ($uploadedFile->getSize() > $maxFileSize) {
            throw new ValidationException(['receipt' => ['Maximum allowed size is 5 MB']]);
        }

        // Validate file name
        $fileName = $uploadedFile->getClientFilename();
        if (! preg_match('/^[a-zA-Z0-9\s._-]+$/', $fileName)) {
            throw new ValidationException(['receipt' => ['Invalid filename']]);
        }

        // Validate file type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $tmpFilePath = $uploadedFile->getStream()->getMetadata('uri');

        if (! in_array($uploadedFile->getClientMediaType(), $allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['Receipt has to be an image or a pdf document']]);
        }

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeTypeFromFile($tmpFilePath);

        if (! in_array($mimeType, $allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['Receipt has to be either "jpeg", "jpg", "png" or "pdf"']]);
        }

        return $data;
    }
}