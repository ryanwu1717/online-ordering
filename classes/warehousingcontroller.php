<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;

class warehousingcontroller
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->ip_webcam = 'rtsp://mil:mil12345@192.168.2.200:554/stream1';
    }
    public function getCamera($request, $response, $args)
    {
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, 'jpg');
        $filename = $this->container->upload_directory . DIRECTORY_SEPARATOR . $filename;
        $ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries' => '/usr/local/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/local/bin/ffprobe'
        ]);
        // $video = $ffmpeg->open('rtsp://admin:admin@192.168.2.202:554/');
        shell_exec("/usr/local/bin/ffmpeg -rtsp_transport tcp -i {$this->ip_webcam} -frames 1 {$filename}");

        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . $filename . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($filename));
        ob_clean();
        ob_end_flush();
        $source = imagecreatefromjpeg($filename);
        imagejpeg($source);
        unlink($filename);
        return $response;
    }

    public function postUploadFile($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $inputFile = $request->getUploadedFiles()['inputFile'];
        $warehousing = new warehousing($this->container->db);
        $file_name = $warehousing->uploadFile($inputFile);
        $result = $warehousing->createFile($file_name);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function readCurrentOriginMaterialSupplier($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $warehousing = new warehousing($this->container->db);
        $result = $warehousing->readCurrentOriginMaterialSupplier($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchOriginalMaterialSupplier($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $warehousing = new warehousing($this->container->db);
        $result = $warehousing->patchOriginalMaterialSupplier($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}
