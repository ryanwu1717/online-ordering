<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;

class prapocontroller
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    public function getProduct($request, $response, $args)
    {
        $prapo = new prapo($this->container->db);
        // $data = $request->getParsedBody();
        $result = $prapo->getProduct();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postProduct($request, $response, $args)
    {
        $prapo = new prapo($this->container->db);
        $data = $request->getParsedBody();
        $result = $prapo->postProduct($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchProduct($request, $response, $args)
    {
        $prapo = new prapo($this->container->db);
        $data = $request->getParsedBody();
        $result = $prapo->patchProduct($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteProduct($request, $response, $args)
    {
        $prapo = new prapo($this->container->db);
        $data = $request->getParsedBody();
        $result = $prapo->deleteProduct($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getOrder($request, $response, $args)
    {
        $prapo = new prapo($this->container->db);
        // $data = $request->getParsedBody();
        $result = $prapo->getOrder();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postOrder($request, $response, $args)
    {
        $prapo = new prapo($this->container->db);
        $data = $request->getParsedBody();
        $result = $prapo->postOrder($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function patchOrder($request, $response, $args)
    {
        $prapo = new prapo($this->container->db);
        $data = $request->getParsedBody();
        $result = $prapo->patchOrder($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteOrder($request, $response, $args)
    {
        $prapo = new prapo($this->container->db);
        $data = $request->getParsedBody();
        $result = $prapo->deleteOrder($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}