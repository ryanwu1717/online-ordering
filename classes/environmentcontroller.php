<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;

class EnvironmentController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    public function postQuality($request, $response, $args)
    {
        $data = $request->getBody();
        $data = json_decode($data,true);
        if(is_null($data)){
            $data = $request->getParsedBody();
        }
        $environment = new Environment($this->container->db);
        $result = ['status'=>"failed"];
        $result = $environment->postQuality($data);
        foreach ($result as $key => $value) {
            if($key==='status'){
                if($value==='failed'){
                    $response = $response->withStatus(500);
                }
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getQuality($request, $response, $args)
    {
        $environment = new Environment($this->container->db);
        $result = $environment->getQuality();
        foreach ($result as $key => $value) {
            if($key==='status'){
                if($value === 'failed'){
                    $response = $response->withStatus(500);
                }
            }else if($key === 'data'){
                $result = $value;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}