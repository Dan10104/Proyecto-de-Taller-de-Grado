<?php

namespace App\Support;

use App\Exceptions\SessionStorageException;
use Illuminate\Session\FileSessionHandler;
use Throwable;

class CheckedFileSessionHandler extends FileSessionHandler
{
    public function write($sessionId, $data): bool
    {
        try {
            if ($this->files->put($this->path.'/'.$sessionId, $data, true) !== false) {
                return true;
            }
        } catch (Throwable) {
        }
        throw new SessionStorageException('No se pudo guardar la sesión técnica.');
    }

    public function destroy($sessionId): bool
    {
        try {
            $this->files->delete($this->path.'/'.$sessionId);
            if (! $this->files->isFile($this->path.'/'.$sessionId)) {
                return true;
            }
        } catch (Throwable) {
        }
        throw new SessionStorageException('No se pudo invalidar la sesión técnica.');
    }

    public function read($sessionId): string|false
    {
        try {
            return parent::read($sessionId);
        } catch (Throwable) {
            throw new SessionStorageException('No se pudo leer la sesión técnica.');
        }
    }
}
