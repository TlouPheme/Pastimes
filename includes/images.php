<?php
function save_product_image(array $file, string $targetDirectory, ?string &$error = null): string
{
    $error = null;

    // Empty file inputs are optional, so return without treating them as errors.
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        $error = 'The image could not be uploaded. Please choose another file.';
        return '';
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        $error = 'Product images must be 3MB or smaller.';
        return '';
    }

    // Validate the image by reading its real image type instead of trusting the extension.
    $imageInfo = getimagesize($file['tmp_name']);
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif',
    ];

    if (!$imageInfo || !array_key_exists($imageInfo[2], $allowedTypes)) {
        $error = 'Please upload a JPG, PNG, WEBP, or GIF image.';
        return '';
    }

    if (!is_dir($targetDirectory)) {
        mkdir($targetDirectory, 0775, true);
    }

    // Build a filesystem-safe name while keeping enough of the original name readable.
    $originalName = basename($file['name']);
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($originalName, PATHINFO_FILENAME));
    $safeName = trim($safeName, '-') ?: 'product';
    $image = strtolower($safeName . '-' . time() . '.' . $allowedTypes[$imageInfo[2]]);

    if (!move_uploaded_file($file['tmp_name'], rtrim($targetDirectory, '/\\') . DIRECTORY_SEPARATOR . $image)) {
        $error = 'The image could not be saved. Please try again.';
        return '';
    }

    return $image;
}

function save_product_images(array $files, string $targetDirectory, ?string &$error = null): array
{
    $error = null;
    $savedImages = [];
    $fileCount = is_array($files['name'] ?? null) ? count($files['name']) : 0;

    for ($i = 0; $i < $fileCount; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $file = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];

        $image = save_product_image($file, $targetDirectory, $error);

        if ($error) {
            // If one gallery upload fails, remove any files already saved for this batch.
            foreach ($savedImages as $savedImage) {
                $savedPath = rtrim($targetDirectory, '/\\') . DIRECTORY_SEPARATOR . $savedImage;

                if (is_file($savedPath)) {
                    unlink($savedPath);
                }
            }

            return [];
        }

        if ($image !== '') {
            $savedImages[] = $image;
        }
    }

    return $savedImages;
}
