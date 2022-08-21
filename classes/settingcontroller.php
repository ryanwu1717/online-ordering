<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;

class settingController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }
    public function render_ocr($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/setting/ocr.html', []);
    }

    public function upload($request, $response, $args)
    {

        $data = $request->getQueryParams();
        $directory = $this->container->upload_directory . '/setting/';
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['file'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
            $setting = new Setting($this->container->db);
            $result = $setting->upload($uploadedFile->getClientFilename(), $filename); //return img id
            if($data['mode']=='ocr'){
                $response = $response->withHeader('Content-type', 'application/json');
                $response = $response->withJson([
                    "file_id"=>$result
                ]);
            }
            return $response;
        }
    }

    public function getFileById($request, $response, $args)
    {
        $data = $args;
        $setting = new Setting($this->container->db);
        $file_ids = $setting->getFileById($data);
        foreach ($file_ids as $key => $file_id) {
            $file = $this->container->upload_directory . '/setting/' . $file_id['FileName'];

            $recogUrl = 'http://127.0.0.1:8090/rotate?filename=%2Fsetting%2F' . $file_id['FileName'];
            $degrees = $setting->http_response($recogUrl);
            $degrees = json_decode($degrees,true);

            $response = $response->withHeader('Content-Description', 'File Transfer')
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Disposition', 'attachment;filename="' . $file_id['FileName'] . '"')
                ->withHeader('Expires', '0')
                ->withHeader('Cache-Control', 'must-revalidate')
                ->withHeader('Pragma', 'public')
                ->withHeader('Content-Length', filesize($file));
            ob_clean();
            ob_end_flush();
            // Load
            $source = imagecreatefromjpeg($file);

            // Rotate
            $rotate = imagerotate($source, $degrees['rotate'], 0);

            // Output
            imagejpeg($rotate);
            // $handle = fopen($file, "rb");
            // while (!feof($handle)) {
            //     echo fread($handle, 1000);
            // }
            return $response;
        }
    }

    public function getPicture($request, $response, $args)
    {

        $setting = new Setting($this->container->db);

        // $data = $request->getParsedBody();
        $data = $request->getQueryParams();

        $result = $setting->getPicture($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);

        return $response;
    }
    function getCustomerPlan($request, $response, $args){
        $data = $request->getQueryParams();
        $setting = new Setting($this->container->db);
        $file_ids = $setting->getFileById($data);
        foreach ($file_ids as $key => $file_id) {
            $recogUrl = 'http://127.0.0.1:8090/CustomerPlan?fileName=%2Fsetting%2F' . $file_id['FileName'];
            $result = $setting->http_response($recogUrl);
            // var_dump($result);
            $result = json_decode($result,true);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
    }

    function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        return $filename;
    }
}