<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;

class NotifyController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    public function getCallback($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $line = $this->container->line;
        $line['code'] = $data['code'];
        $notify = new Notify($this->container->db);
        if(!$notify->getCallback($line)){
            $response = $response->withStatus(500);
            return $response;
        }
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "https://notify-bot.line.me/oauth/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            http_build_query($line)
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head,true);
        $line['access_token'] = $result['access_token'];
        $result = $notify->setAccessToken($line);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function setState($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $notify = new Notify($this->container->db);
        $result = $notify->setState($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson(['url'=>"https://notify-bot.line.me/oauth/authorize?response_type=code&client_id=mq12YZkNFKm7rAgaLkQeZ7&redirect_uri=http://10.248.11.98:10180/notify/callback&scope=notify&state={$result}"]);
        return $response;
    }

    public function renderSetting($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/setting/notify.html', []);
    }

    public function getModule($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $notify = new Notify($this->container->db);
        $result = $notify->getModule($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getNotifyFinishModule($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $notify = new Notify($this->container->db);
        $result = $notify->getNotifyFinishModule($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getNotifyFinish($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $notify = new Notify($this->container->db);
        $result = $notify->getNotifyFinish($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    
    public function postBusinessNotify($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $notify = new Notify($this->container->db);
        $result = $notify->postBusinessNotify($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postNotifyFinish($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $notify = new Notify($this->container->db);
        $result = $notify->postNotifyFinish($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getToken($request, $response, $args)
    {
        // $business = new Business($this->container->db);
        $data = $request->getQueryParams();
        // $result = $business->getBusiness($data);
        $result = [
            "draw"=>$data['draw']++,
            "data"=>[]
        ];
        $notify = new Notify($this->container->db);
        $orders = $notify->getToken($data);

        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $data['length'];
        $start = $data['start'];
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if($length>0 && $key >= $start){
                array_push($result['data'],$order);
                $length--;
            }

        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function checkdeadlineNotify($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $moduleresult = $home->checkclosedeadline();
        $departmentUrl = "";
        // var_dump($moduleresult);
        foreach($moduleresult as $key => $value ){
           
            $message = "請{部門名稱}部門於{期限}前，請儘速 完成此報價的回饋資訊
            連結如下： {部門連結}";
            $departmentUrl="http://{$_SERVER['HTTP_HOST']}{$value['url']}?id={$value['file_id']}&file_id_dest={$value['file_id']}";
            $message = str_replace("{部門名稱}",$value['module_name'],$message);
            $message = str_replace("{部門連結}",$departmentUrl,$message);
            $message = str_replace("{期限}",$value['deadline'],$message);
            
    
            $tmpArr = array($value['module_id']);
            $notify = new Notify($this->container->db);
            $access_tokens = $notify->getAccessToken(array("module"=>$tmpArr),$value['module_name']);
            // $module_information = $notify->getModuleInformation($data);
            if(!$access_tokens){
                $response = $response->withStatus(500);
                return $response;
            }
            foreach ($access_tokens as $key => $access_token) {
                if(is_null($access_token['access_token'])) continue;
                $ch = curl_init();
                // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
                curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer {$access_token['access_token']}"
                ));
                curl_setopt($ch, CURLOPT_POST, 1);
                // In real life you should use something like:
                curl_setopt($ch, CURLOPT_POSTFIELDS, 
                    http_build_query([
                        "message"=>$message
                    ])
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $head = curl_exec($ch);
                $result = json_decode($head,true);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;

        
    }

    public function sendBusinessNotify($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $home = new Home($this->container->db);

        if(!array_key_exists('other',$data)){
            $data['other'] = [$data['id']];
        }
        $tmpidresult = $home->getTmpid($data);
        $data['other'] = [];
        $tmpidArr = [];
        foreach($tmpidresult AS $key => $value){
            array_push($data['other'] , $value["id"]);
            array_push($tmpidArr , $value["tmpid"]);
    
        }
        $moduleresult = $home->getModuleUrl($data);
        $departmentUrl = '';
        $departmentName = '';
        foreach($moduleresult as $key => $value ){
            if($value['name'] == '生管'){
                break;
            }
            // var_dump(count($data['other']));
            $sentcount = intval(ceil(count($data['other'])/5)) ;
            $allcount = 1;
            for ($i = 0; $i < $sentcount; $i++) {
                $departmentUrl = '';
                $tmpindex = $i*5;
                for($j = 0; $j < 5; $j++) {
                    if($tmpindex+$j< count($data['other'])){
                        $departmentUrl .= " \n (報價單號{$tmpidArr[$allcount-1]})  http://{$_SERVER['HTTP_HOST']}{$value['url']}?id={$data['other'][$tmpindex+$j]}&file_id_dest={$data['other'][$tmpindex+$j]} ,";;

                    }
                    $allcount++;
                }
                $departmentUrl = substr_replace($departmentUrl, "", -1);
                // var_dump($departmentUrl);
                $message = $data['message'];
                // $departmentUrl="http://172.25.25.33{$value['url']}?id={$data['id']}&file_id_dest={$data['id']}";
                // var_dump($departmentUrl);
                // var_dump($departmentName);
        
                // $data['content']
                // $tmpDeadline = date_format($data['deadline'], 'd/m/Y H:i:s');
                // var_dump($data['deadline']);
                // $content = str_replace("{回饋期限}",$data['deadline'],$data['content']);
                $message = str_replace("{部門名稱}",$value['name'],$message);
                $message = str_replace("{部門連結}",$departmentUrl,$message);
                // var_dump($content);
        
                // return $content;
        
                $notify = new Notify($this->container->db);
                $access_tokens = $notify->getAccessToken($data,$value['name']);
                $module_information = $notify->getModuleInformation($data);
                if(!$access_tokens){
                    $response = $response->withStatus(500);
                    return $response;
                }
                foreach ($access_tokens as $key => $access_token) {
                    if(is_null($access_token['access_token'])) continue;
                    $ch = curl_init();
                    // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
                    curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Authorization: Bearer {$access_token['access_token']}"
                    ));
                    curl_setopt($ch, CURLOPT_POST, 1);
                    // In real life you should use something like:
                    curl_setopt($ch, CURLOPT_POSTFIELDS, 
                        http_build_query([
                            "message"=>$message
                        ])
                    );
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    $head = curl_exec($ch);
                    $result = json_decode($head,true);
                }

            }
            
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function sendNotify($request, $response, $args)
    {
        $data = $request->getParsedBody();
        var_dump($data);

        
        $home = new Home($this->container->db);
        $moduleresult = $home->getModuleUrl($data);
        $departmentUrl = '';
        $departmentName = '';
        
        foreach($moduleresult as $key => $value ){
            $message = $data['message'];
            $departmentUrl="http://{$_SERVER['HTTP_HOST']}{$value['url']}?id={$data['id']}&file_id_dest={$data['id']}";
            // var_dump($departmentUrl);
            // var_dump($departmentName);
    
            // $data['content']
            // $tmpDeadline = date_format($data['deadline'], 'd/m/Y H:i:s');
            // var_dump($data['deadline']);
            // $content = str_replace("{回饋期限}",$data['deadline'],$data['content']);
            $message = str_replace("{部門名稱}",$value['name'],$message);
            $message = str_replace("{部門連結}",$departmentUrl,$message);
            // var_dump($content);
    
            // return $content;
    
            $notify = new Notify($this->container->db);
            $access_tokens = $notify->getAccessToken($data,$value['name']);
            $module_information = $notify->getModuleInformation($data);
            if(!$access_tokens){
                $response = $response->withStatus(500);
                return $response;
            }
            foreach ($access_tokens as $key => $access_token) {
                if(is_null($access_token['access_token'])) continue;
                $ch = curl_init();
                // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
                curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer {$access_token['access_token']}"
                ));
                curl_setopt($ch, CURLOPT_POST, 1);
                // In real life you should use something like:
                curl_setopt($ch, CURLOPT_POSTFIELDS, 
                    http_build_query([
                        "message"=>$message
                    ])
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $head = curl_exec($ch);
                $result = json_decode($head,true);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}