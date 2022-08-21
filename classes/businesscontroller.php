<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;

class BusinessController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    public function renderBusiness($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/business.html');
    }
    public function getOriginMaterial($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getOriginMaterial($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderDetail($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getOrderDetail($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderDetailUnordered($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getBusinessUnordered($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getCustomerCode($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getCustomerCode($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getCustomerCodes($request, $response, $args){
        // $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getCustomerCodes();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postCustomerCode($request, $response, $args){
        $data = $request->getParsedBody();
        $business = new Business($this->container->db);
        $result = $business->postCustomerCode($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postBusinessMaterial($request, $response, $args){
        $data = $request->getParsedBody();
        $business = new Business($this->container->db);
        $home = new Home($this->container->db);
        $material = $home->postBusinessMaterial($data);
        $result = $business->postBusinessMaterial($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postBusinessTitanizing($request, $response, $args){
        $data = $request->getParsedBody();
        $business = new Business($this->container->db);
        $home = new Home($this->container->db);
        $titanizing = $home->postBusinessTitanizing($data);
        $result = $business->postBusinessTitanizing($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postBusinessHardness($request, $response, $args){
        $data = $request->getParsedBody();
        $business = new Business($this->container->db);
        $home = new Home($this->container->db);
        $result = $home->postBusinessHardness($data);
        // $result = $business->postBusinessHardness($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function getRFIDProcessNmaes($request, $response, $args){
        // $data = $request->getParsedBody();
        $business = new Business($this->container->db);
        $result = $business->getRFIDProcessNmaes();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getRFIDProcessSummary($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result['summary']= $business->getRFIDProcessSummary($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getRFIDProcessState($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $data['type']='Y';
        $resultY = $business->getRFIDProcessState($data);
        $result['readycount']=  count($resultY );
        $result['ready']=  $resultY ;
        $data['type']='N';
        $resultN = $business->getRFIDProcessState($data);
        $result['waitingcount']=  count($resultN);
        $result['waiting']=  $resultN;
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getRFIDOrderDetail($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result= $business->getRFIDOrderDetail($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getRFIDProcessDetail($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result= $business->getRFIDProcessDetail($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getRFIDOrderInformation($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $result = [
            "draw"=>$data['draw']++,
            "data"=>[]
        ];
        $business = new Business($this->container->db);
        $orders = $business->getRFIDOrderInformation($data);

        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $data['length'];
        $start = $data['start'];
        // var_dump($orders);
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
    public function getItemNO($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $result = [
            "draw"=>$data['draw']++,
            "data"=>[]
        ];
        $business = new Business($this->container->db);
        $orders = $business->getItemNO($data);

        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $data['length'];
        $start = $data['start'];
        // var_dump($orders);
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
    public function getItemNOReact($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getItemNOReact($data);
    
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getBusinessDatatables($request, $response, $args)
    {
        // $business = new Business($this->container->db);
        $data = $request->getParsedBody();
        // $result = $business->getBusiness($data);
        $result = [
            "draw"=>$data['draw']++,
            "data"=>[]
        ];
        $business = new Business($this->container->db);
        $orders = $business->getBusiness($data);

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
    public function getBusinessDatatablesUnordered($request, $response, $args)
    {
        // $business = new Business($this->container->db);
        $data = $request->getParsedBody();
        // $result = $business->getBusiness($data);
        $result = [
            "draw"=>$data['draw']++,
            "data"=>[]
        ];
        $business = new Business($this->container->db);
        $orders = $business->getBusinessUnordered($data);

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
    
    public function getBusiness($request, $response, $args)
    {
        $data = $args;
        $business = new Business($this->container->db);
        $result = $business->getBusiness($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getComponents($request, $response, $args)
    {
        $data = $args;
        $business = new Business($this->container->db);
        $data = $business->getComponentsByMIL($data);
        $result = $business->getComponents($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    // public function getProcessCount($request, $response, $args)
    // {
    //     // $data = $args;
    //     $data = $request->getQueryParams();
    //     $business = new Business($this->container->db);
    //     $result = $business->getProcessCount($data);
    //     $response = $response->withHeader('Content-type', 'application/json');
    //     $response = $response->withJson($result);
    //     return $response;
    // }

    public function getProcess($request, $response, $args)
    {
        $data = $args;
        $business = new Business($this->container->db);
        $result = $business->getProcess($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function renderCompare($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, "/compare.html");
    }
    public function getOrderByFile($request, $response, $args)
    {
        // $business = new Business($this->container->db);
        $data = $request->getQueryParams();
        // $result = $business->getBusiness($data);
        $result = [
            "draw"=>$data['draw']++,
            "data"=>[]
        ];
        $business = new Business($this->container->db);
        $orders = $business->getOrderByFile($data);

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
    function renderDispatch($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, "/business/dispatch.html");
    }

    

    function getTitanizing($request, $response, $args){
        $business = new Business($this->container->db);
        $result = $business->getTitanizing([]);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    function getMaterial($request, $response, $args){
        $business = new Business($this->container->db);
        $result = $business->getMaterial([]);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    function getYear($request, $response, $args){
        $business = new Business($this->container->db);
        $result = $business->getYear([]);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    function getHardness($request, $response, $args){
        $business = new Business($this->container->db);
        $result = $business->getHardness([]);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function sendpdfemail($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = array();
        if(isset($data['id'])){
            $result = $home->getEachQuotation(($data['id']),($data['file_id']));
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8090/quotation");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);     //just some very short timeout        
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        
        curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 5); // CURLOPT_TIMEOUT_MS
        // In real life you should use something like:
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            http_build_query(
                array('files'=>json_encode($result))
            )
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
            'Content-Type: application/x-www-form-urlencoded',  
            'Content-Length: ' . strlen(http_build_query(
                array('files'=>json_encode($result))
            )),)  
          );  
        
        $head = curl_exec($ch);
        curl_close($ch);

        sleep(3);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8090/quotation");
        curl_setopt($ch, CURLOPT_USERAGENT, 'api');

        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
        
        curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 5); // CURLOPT_TIMEOUT_MS
        // In real life you should use something like:
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            http_build_query(
                array('files'=>json_encode($result))
            )
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
            'Content-Type: application/x-www-form-urlencoded',  
            'Content-Length: ' . strlen(http_build_query(
                array('files'=>json_encode($result))
            )),)  
          );  
        $head = curl_exec($ch);
        // $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       

        curl_close($ch);

        $content = 'test';
        $email = $data['email'];
        $mail = new PHPMailer(true);                      // Passing `true` enables exceptions

        try {
            //伺服器配置

            $mail->CharSet = "UTF-8";                     //設定郵件編碼
            $mail->SMTPDebug = 0;                        // 除錯模式輸出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = 'smtp.gmail.com';                // SMTP伺服器
            $mail->SMTPAuth = true;                      // 允許 SMTP 認證
            $mail->Username = 'noreply@ictrc.nknu.edu.tw';                // SMTP 使用者名稱  即郵箱的使用者名稱
            $mail->Password = 'YUYTLab970314';             // SMTP 密碼  部分郵箱是授權碼(例如163郵箱)
            $mail->SMTPSecure = 'ssl';                    // 允許 TLS 或者ssl協議
            $mail->Port = 465;                            // 伺服器埠 25 或者465 具體要看郵箱伺服器支援
            // $content = $newContent;
            $mail->IsHTML(true);
            $mail->AddAddress($email, "龍畿智能估價系統");
            $mail->addAttachment($this->container->upload_directory . '/out.pdf');
            // foreach($users as $userkey => $uservalue ){
            //     // var_dump( $uservalue['email']);
            //     $mail->AddAddress( $uservalue['email'], "{$uservalue['module_name']}部門 {$uservalue['name']}");

            // }
            $mail->SetFrom("noreply@ictrc.nknu.edu.tw", "龍畿智能估價系統");
            $mail->Subject = "龍畿智能估價系統通知信";
            $mail->MsgHTML($content); 

            if(!$mail->Send()) {

                $result = ["message"=>"Error while sending Email."];
            } else {

                $result = ["message"=>  "Email sent successfully"];
            }
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            
            // return $response;
        } catch (Exception $e) {

        }
        
    }

    public function sendemail($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $notifyController = new NotifyController();
        $chat = new Chat($this->container->db);

        $data = $request->getParsedBody();
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
        $users = $home->getUserByModule($data);
        if(isset($data['deadline'])){
            $notifyController->postBusinessNotify($request, $response, $args);
            $notifyController->sendBusinessNotify($request, $response, $args);

        }else{
            $notifyController->sendNotify($request, $response, $args);

        }
        
        $departmentUrl = '';
        $departmentName = '';
        foreach($moduleresult as $key => $value ){
            $sentcount = intval(ceil(count($data['other'])/5)) ;
            $allcount = 1;
            for ($i = 0; $i < $sentcount; $i++) {
                $departmentUrl = '';
                $tmpindex = $i*5;
                for($j = 0; $j < 5; $j++) {
                    if($tmpindex+$j< count($data['other'])){
                        $departmentUrl .= "\n (報價單號{$tmpidArr[$allcount-1]}) http://{$_SERVER['HTTP_HOST']}{$value['url']}?id={$data['other'][$tmpindex+$j]}&file_id_dest={$data['other'][$tmpindex+$j]} ,";;

                    }
                    $allcount++;
                }
                
                $departmentUrl = substr_replace($departmentUrl, "", -1);
                $message = $data['message'];
            

                $content = $data['content'];
                // $departmentUrl="http://172.25.25.33{$value['url']}?id={$data['id']}&file_id_dest={$data['id']}";
            
                $content = str_replace("{部門名稱}",$value['name'],$content);
                $content = str_replace("{部門連結}",$departmentUrl,$content);
                /* liveChat */
                if (!empty($value['chatID'])) {
                    $result = $chat->updateMessage(["UID" => 1, "chatID" => $value['chatID'], "Msg" => $content]);
                }
                /* liveChat */
                
                
                $mail = new PHPMailer(true);                      // Passing `true` enables exceptions

                try {
                    //伺服器配置
                    $mail->CharSet = "UTF-8";                     //設定郵件編碼
                    $mail->SMTPDebug = 0;                        // 除錯模式輸出
                    $mail->isSMTP();                             // 使用SMTP
                    $mail->Host = 'smtp.gmail.com';                // SMTP伺服器
                    $mail->SMTPAuth = true;                      // 允許 SMTP 認證
                    $mail->Username = 'noreply@ictrc.nknu.edu.tw';                // SMTP 使用者名稱  即郵箱的使用者名稱
                    $mail->Password = 'YUYTLab970314';             // SMTP 密碼  部分郵箱是授權碼(例如163郵箱)
                    $mail->SMTPSecure = 'ssl';                    // 允許 TLS 或者ssl協議
                    $mail->Port = 465;                            // 伺服器埠 25 或者465 具體要看郵箱伺服器支援
                    // $content = $newContent;
                    $mail->IsHTML(true);
                    // $mail->AddAddress("@gmail.com", "技術部門 張新強");

                    foreach($users as $userkey => $uservalue ){
                        // var_dump( $uservalue['email']);
                        if(!(is_null($uservalue['email']))){
                            $mail->AddAddress( $uservalue['email'], "{$uservalue['module_name']}部門 {$uservalue['name']}");

                        }

                    }
                    $mail->SetFrom("noreply@ictrc.nknu.edu.tw", "龍畿智能估價系統");
                    $mail->Subject = "龍畿智能估價系統通知信";
                    $mail->MsgHTML($content); 

                    if(!$mail->Send()) {
                        $result = ["message"=>"Error while sending Email."];
                    } else {
                        $result = ["message"=>  "Email sent successfully"];

                    }

                    
                } catch (Exception $e) {

                }
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOriginMaterialSupplier($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $fetch = $business->getOriginMaterialSupplier($data);
        $result = [];
        $result['data'] = $fetch;
        $result['total'] = count($fetch);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}