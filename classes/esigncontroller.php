<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;


class esignController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    public function renderIndex($request, $response, $args)
    {

        $renderer = new \Slim\Views\PhpRenderer($this->container->view);
        return $renderer->render($response, '/eSign/index.html');
    }
    public function uploadContentFile($request, $response, $args)
    {
        $chat = new Esign($this->container->db);
        $result = $chat->uploadContentFile( $this->container->upload_directory, $request->getUploadedFiles(),$args['content_id']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getdetaildataTable($request, $response, $args)
    {
        $data = $request->getQueryParams();
        // $result = $business->getBusiness($data);
        $result = [
            "draw" => $data['draw']++,
            "data" => []
        ];
        $esign = new Esign($this->container->db);
        $lists = $esign->getdetaildataTable();



        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $data['length'];
        $start = $data['start'];
        foreach ($lists as $key => $list) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $list);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getlistdataTable($request, $response, $args)
    {
        $data = $request->getQueryParams();
        // $result = $business->getBusiness($data);
        $result = [
            "draw" => $data['draw']++,
            "data" => []
        ];
        $esign = new Esign($this->container->db);
        $lists = $esign->getlistdataTable();



        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $data['length'];
        $start = $data['start'];
        foreach ($lists as $key => $list) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $list);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getQuestionContent($request, $response, $args)
    {
        $esign = new Esign($this->container->db);
        $data = $request->getQueryParams();
        $result = $esign->getQuestionContent($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    

    public function getQuestionSequence($request, $response, $args)
    {
        $esign = new Esign($this->container->db);
        $data = $request->getQueryParams();
        $result = $esign->getQuestionSequence($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function getReceiverSequence($request, $response, $args)
    {
        $esign = new Esign($this->container->db);
        $data = $request->getQueryParams();
        $result = $esign->getReceiverSequence($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    
    public function patchQuestionFeedback($request, $response, $args)
    {
        $esign = new Esign($this->container->db);
        $data = $request->getParsedBody();
        $result = $esign->patchQuestionFeedback($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postQuestion($request, $response, $args)
    {
        $esign = new Esign($this->container->db);
        $data = $request->getParsedBody();
        $result = $esign->postQuestion($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function getStaff($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $result = [];
        $result['data'] = [['name' => '(生管部)嚴永強'], ['name' => '(管理部)李榮光'], ['name' => '(管理部)李清光'], ['name' => '(管理部)李清光'], ['name' => '(生管部)吳素英'], ['name' => '(營業部)吳金蓮'], ['name' => '(生管部)龔秀蓮'], ['name' => '(生管部)黃淑蒂'], ['name' => '(財務部)張雯琪'], ['name' => '(製圖課)劉和育'], ['name' => '(生技部)塗婷羽'], ['name' => '(生技部)李峻豪'], ['name' => '(生管部)黎美娟'], ['name' => '(生技部)陳柏丞'], ['name' => '(管理部)李英彥'], ['name' => '(研技部)鄭又瑋'], ['name' => '(生管部)洪秀華'], ['name' => '(管理部)李欣靜'], ['name' => '(營業部)羅仙惠'], ['name' => '(生管部)陳惠蓉'], ['name' => '(資訊部)蔡妙琴'], ['name' => '(生管部)黃佳慧'], ['name' => '(製圖課)葉睿杰'], ['name' => '(生技部)康志正'], ['name' => '(營業部)宋俊英'], ['name' => '(製圖課)龔靖婷'], ['name' => '(生管部)李嘉玲'], ['name' => '(生技部)洪詩婷'], ['name' => '(製圖課)吳玲雅'], ['name' => '(製圖課)陳姵妏'], ['name' => '(研技部)劉哲維'], ['name' => '(生管部)夏川容'], ['name' => '(營業部)梁晉瑄'], ['name' => '(資訊部)朱冠宇'], ['name' => '(營業部)張簡國廷'], ['name' => '(資訊部)陳宥靜'], ['name' => '(營業部)許瑜庭'], ['name' => '(營業部)李紫琦'], ['name' => '(營業部)吳俐慧'], ['name' => '(營業部)劉廉錚'], ['name' => '(營業部)蘇家鈴'], ['name' => '(營業部)李瑞美'], ['name' => '(生管部)陳建勳'], ['name' => '(生技部)陳冠宇'], ['name' => '(營業部)周雅婷'], ['name' => '(資訊部)黃博慧'], ['name' => '(資訊部)姚孝明'], ['name' => '(生技部)姜昀廷'], ['name' => '(營業部)段惠齡'], ['name' => '(營業部)陳韋伶']];
        $paging = [];
        foreach ($result['data'] as $i => $value) {
            if ($i >= $data['start']) {
                if ($data['length'] > 0) {
                    array_push($paging, $value);
                }
                $data['length']--;
            }
        }
        $result['draw'] = $data['draw']++;
        $result['recordsTotal'] = count($result['data']);
        $result['recordsFiltered'] = count($result['data']);
        $result['data'] = $paging;
        $response = $response->withHeader('Content-Type', 'application/json');
        $response = $response->withJSON($result);
        return $response;
    }
}
