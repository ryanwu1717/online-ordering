<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;

class ProductionController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    public function renderProcess($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/production/process_new.php', []);
    }
    public function getManuProcess($request, $response, $args){
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $result = $home->getManuProcess($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}