<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use Recca0120\Upload\Plupload;
use PHPUnit\Framework\TestCase;

class PluploadTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testReceiveUploadSingleFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Plupload(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );
        $inputName = 'foo';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $request->shouldReceive('get')->once()->with('chunks')->andReturn('');
        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Plupload(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );
        $files->shouldReceive('isDirectory')->twice()->andReturn(true);

        $inputName = 'foo';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $request->shouldReceive('get')->once()->with('chunks')->andReturn($chunks = 8);
        $request->shouldReceive('get')->once()->with('chunk')->andReturn($chunk = 8);
        $request->shouldReceive('get')->once()->with('name')->andReturn($originalName = 'foo.php');
        $uploadedFile->shouldReceive('getPathname')->once()->andReturn($pathname = 'foo');
        $request->shouldReceive('header')->once()->with('content-length')->andReturn($contentLength = 1049073);
        $uploadedFile->shouldReceive('getMimeType')->once()->andReturn($mimeType = 'foo');

        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $chunkFile->shouldReceive('setToken')->once()->with($token)->andReturnSelf();
        $chunkFile->shouldReceive('setChunkPath')->once()->with($chunksPath)->andReturnSelf();
        $chunkFile->shouldReceive('setStoragePath')->once()->with($storagePath)->andReturnSelf();
        $chunkFile->shouldReceive('setName')->once()->with($originalName)->andReturnSelf();
        $chunkFile->shouldReceive('setMimeType')->once()->with($mimeType)->andReturnSelf();
        $chunkFile->shouldReceive('appendStream')->once()->with($pathname, $chunk * $contentLength)->andReturnSelf();
        $chunkFile->shouldReceive('createUploadedFile')->once()->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );

        $api->receive($inputName);
    }

    public function testReceiveChunkedFileAndThrowChunkedResponseException()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Plupload(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );
        $files->shouldReceive('isDirectory')->twice()->andReturn(true);

        $inputName = 'foo';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $request->shouldReceive('get')->once()->with('chunks')->andReturn($chunks = 8);
        $request->shouldReceive('get')->once()->with('chunk')->andReturn($chunk = 6);
        $request->shouldReceive('get')->once()->with('name')->andReturn($originalName = 'foo.php');
        $uploadedFile->shouldReceive('getPathname')->once()->andReturn($pathname = 'foo');
        $request->shouldReceive('header')->once()->with('content-length')->andReturn($contentLength = 1049073);
        $uploadedFile->shouldReceive('getMimeType')->once()->andReturn($mimeType = 'foo');

        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $chunkFile->shouldReceive('setToken')->once()->with($token)->andReturnSelf();
        $chunkFile->shouldReceive('setChunkPath')->once()->with($chunksPath)->andReturnSelf();
        $chunkFile->shouldReceive('setStoragePath')->once()->with($storagePath)->andReturnSelf();
        $chunkFile->shouldReceive('setName')->once()->with($originalName)->andReturnSelf();
        $chunkFile->shouldReceive('setMimeType')->once()->with($mimeType)->andReturnSelf();
        $chunkFile->shouldReceive('appendStream')->once()->with($pathname, $chunk * $contentLength)->andReturnSelf();
        $chunkFile->shouldReceive('throwException')->once();
        $api->receive($inputName);
    }

    public function testCompletedResponse()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Plupload(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $files = m::mock('Recca0120\Upload\Filesystem'),
            $chunkFile = m::mock('Recca0120\Upload\ChunkFile')
        );
        $response = m::mock('Illuminate\Http\JsonResponse');
        $response->shouldReceive('getData')->once()->andReturn($data = []);
        $response->shouldReceive('setData')->once()->with([
            'jsonrpc' => '2.0',
            'result' => $data,
        ])->andReturnSelf();

        $this->assertSame($response, $api->completedResponse($response));
    }
}
