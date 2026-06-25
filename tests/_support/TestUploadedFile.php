<?php

namespace Tests\Support;

use CodeIgniter\HTTP\Files\UploadedFile;

class TestUploadedFile extends UploadedFile
{
    private bool $mockIsValid = true;

    public function isValid(): bool
    {
        return $this->mockIsValid && $this->getError() === UPLOAD_ERR_OK;
    }

    public function setMockIsValid(bool $valid)
    {
        $this->mockIsValid = $valid;
    }

    public function move(string $targetPath, ?string $name = null, bool $overwrite = false): bool
    {
        $targetPath = rtrim($targetPath, '/') . '/';
        $targetPath = $this->setPath($targetPath);

        if ($this->hasMoved) {
            throw \CodeIgniter\HTTP\Exceptions\HTTPException::forAlreadyMoved();
        }

        if (! $this->isValid()) {
            throw \CodeIgniter\HTTP\Exceptions\HTTPException::forInvalidFile();
        }

        $name ??= $this->getName();
        $destination = $overwrite ? $targetPath . $name : $this->getDestination($targetPath . $name);

        // Copy instead of move_uploaded_file
        $success = copy($this->getTempName(), $destination);
        if ($success) {
            @unlink($this->getTempName());
            $this->hasMoved = true;
        }

        if ($this->hasMoved === false) {
            throw \CodeIgniter\HTTP\Exceptions\HTTPException::forMoveFailed(basename($this->getTempName()), $targetPath, 'mock copy failed');
        }

        @chmod($targetPath, 0777 & ~umask());

        $this->path = $targetPath;
        $this->name = basename($destination);

        return true;
    }
}
