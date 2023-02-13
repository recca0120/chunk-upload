<?php

namespace Recca0120\Upload;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Recca0120\Upload\Contracts\Api as ApiContract;

abstract class Api implements ApiContract
{
    /**
     * $request.
     *
     * @var Request
     */
    protected $request;

    /**
     * $files.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * $chunkFile.
     *
     * @var ChunkFileFactory
     */
    protected $ChunkFileFactory;

    /**
     * $config.
     *
     * @var array
     */
    protected $config;

    /**
     * @var ChunkFileFactory
     */
    protected $chunkFileFactory;

    public function __construct($config = [], Request $request = null, Filesystem $files = null, ChunkFileFactory $chunkFileFactory = null)
    {
        $this->request = $request ?: Request::capture();
        $this->files = $files ?: new Filesystem();
        $this->chunkFileFactory = $chunkFileFactory ?: new ChunkFileFactory($this->files);
        $this->config = array_merge([
            'chunks' => sys_get_temp_dir().'/chunks',
            'storage' => 'storage/temp',
            'domain' => $this->request->root(),
            'path' => 'storage/temp',
        ], $config);
    }

    public function domain(): string
    {
        return rtrim($this->config['domain'], '/').'/';
    }

    public function path(): string
    {
        return rtrim($this->config['path'], '/').'/';
    }

    public function makeDirectory(string $path): Api
    {
        if ($this->files->isDirectory($path) === false) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        return $this;
    }

    public function cleanDirectory(string $path): Api
    {
        $time = time();
        $maxFileAge = 3600;
        $files = (array) $this->files->files($path);
        foreach ($files as $file) {
            if ($this->files->isFile($file) === true &&
                $this->files->lastModified($file) < ($time - $maxFileAge)
            ) {
                $this->files->delete($file);
            }
        }

        return $this;
    }

    abstract public function receive(string $name);

    public function deleteUploadedFile($uploadedFile)
    {
        $file = $uploadedFile->getPathname();
        if ($this->files->isFile($file) === true) {
            $this->files->delete($file);
        }
        $this->cleanDirectory($this->chunkPath());

        return $this;
    }

    public function completedResponse(JsonResponse $response): JsonResponse
    {
        return $response;
    }

    protected function chunkPath(): string
    {
        $this->makeDirectory($this->config['chunks']);

        return rtrim($this->config['chunks'], '/').'/';
    }

    protected function storagePath(): string
    {
        $this->makeDirectory($this->config['storage']);

        return rtrim($this->config['storage'], '/').'/';
    }

    protected function createChunkFile(string $name, string $uuid = null, string $mimeType = null): ChunkFile
    {
        return $this->chunkFileFactory->create(
            $name, $this->chunkPath(), $this->storagePath(), $uuid, $mimeType
        );
    }
}
