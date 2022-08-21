<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;

class quotationbusinesscontroller
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

   

    public function renderQuotation($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/quotation/index.html');
    }

    public function patchRotate($request, $response, $args)
	{
        $data = $request->getParsedBody();
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $home = new Home($this->container->db);

		$result = $quotationbusiness->getFileName($data);
        if(is_null($result)){
            $result = ["status" => 'failed'];
        }else{
            $file = $result;
            if (file_exists($this->container->upload_directory . DIRECTORY_SEPARATOR . $file)) {
                $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $file;
            } else {
                $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . "noImage.png";
            }
            $source = $home->compressImage($file, $file, 100);
            // imagejpeg($source);
            if ($data['rotate'] < 0) {
                $data['rotate'] += 360;
            }
            // Rotate
            $rotate = imagerotate($source, 360 - intval($data['rotate']), 0);

            // Output
            imagejpeg($rotate, $file);

            $image = $home -> getBase64_encode($result);

            $result = ["status" => 'failed',"image"=>$image];

        }
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;

		
	}

    public function patchUpdateFileName($request, $response, $args)
	{
        $data = $request->getParsedBody();
        $quotationbusiness = new Quotationbusiness($this->container->db);
		$result = $quotationbusiness->patchUpdateFileName($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;

		
	}
    
    

    public function getUpdateFileName($request, $response, $args)
	{
        // $data = $request->getQueryParams();
        $directory = $this->container->upload_directory;
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['inputFile'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $uploadedFile);
            $home = new Home($this->container->db);
            $clientFileName = $uploadedFile->getClientFilename();
            $image = $home -> getBase64_encode($filename);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson(["status"=> "success","file_name"=> $filename,"client_name"=> $clientFileName ,"image"=>$image]);
            return $response;
        }else{
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson(["status"=> "failed"]);
            return $response;
        }

		
	}

    public function deleteFile($request, $response, $args)
	{
		$data = $request->getParsedBody();
		$quotationbusiness = new Quotationbusiness($this->container->db);
		$result = $quotationbusiness->deleteFile($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
    

    function getMaterialRecog($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $file_ids = $home->getFileById($data);

        foreach ($file_ids as $key => $file_id) {
            $recogUrl = 'http://mil_python:8090/CNNTextRec?Files=["../uploads/' . $file_id['FileName'] . '"]';
            // $recogUrl = 'http://mil_python:8090/CNNTextRec?Files=["../uploads/a4176ba4d55e66b9.jpg"]';
            $result = $home->http_response($recogUrl);
            $result = json_decode($result, true);
            // var_dump($result);

            // $material = '';
            // foreach ($result as $key => $values) {
            //     foreach ($values as $key => $value) {
            //         $material = $value;
            //         break 2;
            //     }
            // }
            // var_dump($material);

            // $business = new Business($this->container->db);
            // $result = $business->getMaterialMatch([$material]);
            /* 
            $material = '';
            foreach ($result as $key => $values) {
                $result = $values;
                break;
            }
            foreach ($result as $key => $value) {
                if($key == "material"){
                    $material = $value;
                    break;
                }
            }
            $business = new Business($this->container->db);
            $result['material'] = $business->getMaterialMatch($material);
            */
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
    }

    public function sendNotify($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $quotationbusiness = new Quotationbusiness($this->container->db);

        $notify_finish = $quotationbusiness->getNotify_finish($data);
        $data['id'] = $data['quotation_business_id'];
        $data['module'] = $notify_finish;
        if(!array_key_exists('other',$data)){
            $data['other'] = [$data['quotation_business_id']];
        }
        $tmpidresult = $quotationbusiness->getTmpid($data);
        $data['other'] = [];
        $tmpidArr = [];
        $tmptypeArr = [];
        foreach($tmpidresult AS $key => $value){
            array_push($data['other'] , $value["quotation_business_id"]);
            array_push($tmpidArr , $value["tmpid"]);
            array_push($tmptypeArr , $value["type_name"]);
    
        }
        $data['type'] = $tmpidresult[0]['type_name'];
        $data['content'] = "\n {$data['type']}編號{$data['quotation_business_id']} {$data['module_name']}部門已完成填寫\n檢視連結如下：{部門連結}";
        $data['message'] = "\n {$data['type']}編號{$data['quotation_business_id']} {$data['module_name']}部門已完成填寫\n檢視連結如下：{部門連結}";
      
        
        
        $home = new Home($this->container->db);
        $moduleresult = $home->getModuleUrl($data);
        $departmentUrl = '';
        $departmentName = '';
        
        foreach($moduleresult as $key => $value ){
            $message = $data['message'];
            $departmentUrl="http://{$_SERVER['HTTP_HOST']}{$value['module_url']}/{$data['id']}";
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

    public function sendemail($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $notifyController = new NotifyController();
        $chat = new Chat($this->container->db);
        $quotationbusiness = new Quotationbusiness($this->container->db);


        $data = $request->getParsedBody();

        $progress = $quotationbusiness->postFinishProgress($data);
        // return;
        $notify_finish = $quotationbusiness->getNotify_finish($data);
        $data['id'] = $data['quotation_business_id'];
        $data['module'] = $notify_finish;
        $data['content'] = "\n 報價編號{$data['quotation_business_id']} {$data['module_name']}部門已完成填寫\n檢視連結如下：{部門連結}";
        $data['message'] = "\n 報價編號{$data['quotation_business_id']} {$data['module_name']}部門已完成填寫\n檢視連結如下：{部門連結}";
      
        
        if(!array_key_exists('other',$data)){
            $data['other'] = [$data['quotation_business_id']];
        }
        

        $tmpidresult = $quotationbusiness->getTmpid($data);
        $data['other'] = [];
        $tmpidArr = [];
        $tmptypeArr = [];
        foreach($tmpidresult AS $key => $value){
            array_push($data['other'] , $value["quotation_business_id"]);
            array_push($tmpidArr , $value["tmpid"]);
            array_push($tmptypeArr , $value["type_name"]);
    
        }
        // var_dump($data );
        // var_dump($tmpidArr );

        // return ;

        $moduleresult = $home->getModuleUrl($data);

        $users = $home->getUserByModule($data);
        if(isset($data['deadline'])){
            $notifyController->postBusinessNotify($request, $response, $args);
            $notifyController->sendBusinessNotify($request, $response, $args);

        }else{
            $this->sendNotify($request, $response, $args);

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
                        $departmentUrl .= "\n ({$tmptypeArr[$allcount-1]}號{$tmpidArr[$allcount-1]}) http://{$_SERVER['HTTP_HOST']}{$value['url']}?id={$data['other'][$tmpindex+$j]}&file_id_dest={$data['other'][$tmpindex+$j]} ,";;

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

    
    public function getFinishDetail($request, $response, $args)
	{
		global $container;
		$quotationbusiness = new Quotationbusiness($container->db);
		$data = $request->getQueryParams();
		$result = $quotationbusiness->getFinishDetail($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

    public function getQuotationBusiness($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $result = [
            "draw" => 1,
            "data" => []
        ];
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $orders = $quotationbusiness->getQuotationBusiness($data);

        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        // $data['length']
        $length = $data['size'];
        $start = $data['size']*($data['cur_page']-1);
        foreach ($orders as $key => $order) {
            $result['recordsTotal'] += 1;
            $result['recordsFiltered'] += 1;
            if ($length > 0 && $key >= $start) {
                array_push($result['data'], $order);
                $length--;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    function get_attach_frame($request, $response, $args)
	{
		global $container;
		$quotationbusiness = new Quotationbusiness($container->db);
		$phasegallerycontroller = new PhaseGalleryController($container->db);
		$params = $request->getQueryParams();
		$result = $quotationbusiness->get_attach_frame($params);
		// $result = $phasegallerycontroller->getPointList($delivery_meet_content_position);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

    public function getRef_file($request, $response, $args)
	{
		global $container;
		$quotationbusiness = new Quotationbusiness($container->db);
		$data = $request->getQueryParams();
		$result = $quotationbusiness->getRef_file($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}


    public function delete_attach_paint($request, $response, $args)
	{
		$data = $request->getParsedBody();
		$quotationbusiness = new Quotationbusiness($this->container->db);
		$result = $quotationbusiness->delete_attach_paint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

    public function upload_attach_paint($request, $response, $args)
	{
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();
		$quotationbusiness = new Quotationbusiness($this->container->db);
		$phasegallery = new PhaseGallery($this->container->db);
        $data['index'] = json_decode($data['index'], true);
		foreach($data['files'] as $key =>$file){
            
			$file = $phasegallery->uploadFile(["files"=>["inputFile"=>$file]]);
			unset($data['files']);
			$file['user_id'] = 0;
			$data['file_id'] = $phasegallery->insertFile($file);
            $data['sequence'] = $data['index'][$key];
			$result = $quotationbusiness->post_attach_file_paint($data);
		}
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
    

    public function get_attach_file_paint($request, $response, $args)
	{
		global $container;
		$quotationbusiness = new Quotationbusiness($container->db);
		$data = $request->getQueryParams();
		$result = $quotationbusiness->get_attach_file_paint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

    function patch_attach_frame($request, $response, $args)
	{
		global $container;
		$quotationbusiness = new Quotationbusiness($container->db);
		$params = $request->getParsedBody();
		$result = $quotationbusiness->updateDeliveryMeetContentPosition($params);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

    public function patchDelivery_date($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->patchDelivery_date($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function updateItemno($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->updateItemno($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    
    public function deleteCommentComponent($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteCommentComponent($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postOrderConfirm($request, $response, $args)
    {
        $datas = $request->getParsedBody();

        $quotationbusiness = new Quotationbusiness($this->container->db);
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();

        $result = $quotationbusiness->copyFileDetail($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        $result = $quotationbusiness->sendNotify($datas);

        foreach ($datas as $row => $data) {

            $result = $quotationbusiness->quotationPatchData($data);
            $result = $quotationbusiness->postQuotationAttachFile($data);
            $result = $quotationbusiness->postQuotationFileContent($data);
            if ($data['TB001'] !== null && $data['TB002'] !== null && $data['TB003'] !== null) {

                $datause['fk'] = json_encode([
                        'TB001'=>$data['TB001'],
                        'TB002'=>$data['TB002'],
                        'TB003'=>$data['TB003'],
                        'ClientName'=>$data['FileName'],
                        'FileName'=>$data['FileName'],
                        'file_id'=>$data['file_id'],                        
                ]);
                $result = $home->getFileByFK($datause, false);
                foreach ($result as $key => $row) {
                    $row['itemno'] = '002';
                    $row['file_id']= $data['file_id'];
                    $result = $home->cloneFile($row, false);
                    if (array_key_exists('id', $result)) {
                        $result += $datause;
                        $home->postfix($result);
                        break;
                    }
                }
            } else {
                $data['itemno'] = '001';
                $result = $quotationbusiness->quotationPatchFile($data);
                $home->setProgress($data['file_id'], 1);
                $home->setProgress($data['file_id'], 2);
                $home->setProgress($data['file_id'], 3);
                $home->setProgress($data['file_id'], 4);
                $quotationbusiness->postQuotationProgress($data);
                $home->postfix($data);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function copyFileDetail($request, $response, $args)
    {
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $data = $request->getParsedBody();
        $result = $quotationbusiness->copyFileDetail($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postCommentComponent($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postCommentComponent($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    
    public function postProcessCost($request, $response, $args)
    {

        $quotationbusiness = new Quotationbusiness($this->container->db);

        $data = $request->getParsedBody();
        $result = $quotationbusiness->postProcess_cost($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    
    public function getProcessCost($request, $response, $args)
    {
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $data = $request->getQueryParams();
        $result= $quotationbusiness->getProcess_cost($data);
        // $result['other'] = $home->getOtherProcess_cost($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getProcessProcesses ($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $business = new Business($this->container->db);
        $quotationbusiness = new Quotationbusiness($this->container->db);


        $processArr = [];
        $cropArr = [];
        $processresult = $home->getProcessIdByFileId($data);
        foreach ($processresult as $key => $value) {
            array_push($cropArr, $value['crop_id']);
            if (!in_array($value['process_id'], $processArr))
                array_push($processArr, $value['process_id']);
        }

        $data['process_id'] = $processArr[0];
        // $home = new Home($this->container->db);
		$business = new Business($this->container->db);

		
		$result = $home->getComponentMatch($data);
        $result = $business->getProcessByComponentName($result);
        $result['crop'] = $cropArr;
        foreach ($result['result'] as $row_key => $row) {
            foreach ($row as $key => $value) {
                if(!is_array($value)){
                    if ($quotationbusiness->isJson($value)) {
                        $result['result'][$row_key][$key] = json_decode($value, true);
                    }
                }
            }
        }

        
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
       
    }

    public function postFile_comment($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postFile_comment($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    

    public function postMaterialSequence($request, $response, $args)
    {
        $quotationbusiness = new Quotationbusiness($this->container->db);

        $data = $request->getParsedBody();
        $result = $quotationbusiness->postMaterialSequence($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postMaterial($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postMaterial($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postTitanizing($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postTitanizing($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postHardness($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postHardness($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    
    public function getFile_comment($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getFile_comment($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getDetailMaterial($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new Quotationbusiness($this->container->db);
        $result['material'] = $business->getMaterialFilter();
        $result['titanizing'] = $business->getTitanizingFilter();
        $result['hardness'] = $business->getHardnessFilter();
        
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    
    public function getDetailProcesses ($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $business = new Business($this->container->db);
        $quotationbusiness = new Quotationbusiness($this->container->db);


        $processArr = [];
        $cropArr = [];
        $processresult = $home->getProcessIdByFileId($data);
        foreach ($processresult as $key => $value) {
            array_push($cropArr, $value['crop_id']);
            if (!in_array($value['process_id'], $processArr))
                array_push($processArr, $value['process_id']);
        }

        $data['process_id'] = $processArr[0];
        // $home = new Home($this->container->db);
		$business = new Business($this->container->db);

		// $data = $args;
		// foreach ($request->getQueryParams() as $key => $value) {
		// 	$data[$key] = $value;
		// }
		$result = $quotationbusiness->getComponentMatch($data);
        $result['crop'] = $cropArr;
        foreach ($result['result'] as $row_key => $row) {
            $result['result'][$row_key]['img'] = '/file/'.$row['id'];
            // var_dump($value['img']);
            foreach ($row as $key => $value) {
                if ($quotationbusiness->isJson($value)) {
                    $result['result'][$row_key][$key] = json_decode($value, true);
                }
            }
        }
        // $result['material'] = $home->getMaterial($data);
        // $result['titanizing'] = $home->getTitanizing($data);
        // $result['hardness'] = $home->getHardness($data);
		// $result['process'] = $business->getMaterialStuffByOrderSerial($result);
		// $result['status'] = $home->getProcessStatus($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
       
    }

    public function getDetail($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new Quotationbusiness($this->container->db);
        $result = $business->getDetail($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getMailQuotation($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new Quotationbusiness($this->container->db);
        $home = new Home($this->container->db);
        $result = $home->getMessageQuotation($data);
        $result = $business->search_customer_code($result);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getMailHistory($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $insert_resoonse = $quotationbusiness->insertFile($data);
        if ($insert_resoonse['status'] === 'success') {
            $file_id = $insert_resoonse['file_id'];
        } else {
            $file_id = $insert_resoonse['status'];
        }
        $result = $home->getMessageHistory($data);
        foreach ($result as $index => $row) {
            $result[$index]['file_id'] = $file_id;
        }
        count($result)==0?$result=[["file_id"=>$file_id,"history"=>[]]]:$result;
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getMailBusinessDatatablesUnordered($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $business = new Quotationbusiness($this->container->db);
        $result = $business->getMailHistoryUnordered($data);

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getEmail($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $quotationbusiness = new quotationbusiness($this->container->db);
        $per_data = $quotationbusiness->imapFetchContents($data);
        unset($per_data['file_names']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($per_data);
        return $response;
    }
    public function getEmailByDate($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $quotationbusiness = new quotationbusiness($this->container->db);
        $result = $quotationbusiness->imapFetchSubjects($data); //測全部用
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getEmailAttach($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $quotationbusiness = new quotationbusiness($this->container->db);
        $home = new Home($this->container->db);
        $crm = new CRM($this->container->db);
        $per_data['quotation_business_id'] = $quotationbusiness->post_quotation($params);
        if(is_null($per_data['quotation_business_id'])){
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson([]);
            return $response;
        }
        $per_data = $quotationbusiness->imapFetchContents($params);
        $per_data['file_array']=[];
        $matchCustomerResult = array();
        foreach ($per_data['file_names'] as $per_file => $file_name) {            
            if(in_array(strtolower(pathinfo($file_name, PATHINFO_EXTENSION)),['xlsx','xls'])){
                $per_data['file_array'][]=[
                    "alt"=> "此附件不為圖檔，檔名：{$file_name}",
                    "file_client_name"=> "{$file_name}",
                    "file_name"=> "{$file_name}",
                    "file_name_text"=> "{$file_name}",
                    "rotate"=> 0,
                    "src"=> "/file/message/image/{$file_name}",

                ];
            }else{
                $result = $crm->decompress_delivery_meet_content_file([
                    'delivery_meet_content_file_name' => @$file_name,
                    'listAll' => true
                ]);
                $result = $home->concatImagePath($result);

                
                foreach ($result as $data) {
                    $quotationbusiness = new quotationbusiness($this->container->db);
                    $data['rotate'] = 0;
                    // $file_name_text = $quotationbusiness->mailNmaeParse($data);
                    // $data['file_name_text'] = $file_name_text['text'];
                    $data['file_name_text'] = '';
                    $per_data['file_array'][] = $data;
                    $allresult = array();
                    
                  
                        
                    $logoArr=array();
                    $logoArr = json_encode($logoArr);
                    $recogUrl = "http://mil_python:8090/matchCustomer?fileName={$data['file_name']}&logo={$logoArr}";
                    $maxID = $home->http_response($recogUrl);
                    $maxID = json_decode($maxID,true);
                    // var_dump($maxID);
                    if(!is_null($maxID))
                        if(array_key_exists('value',$maxID))
                            if(!is_null($maxID['value'])){
                                array_push($matchCustomerResult,$maxID['value']);
                            }
                        
                    break;
                }


            }
        }
        
        $tmpvalues = array_count_values($matchCustomerResult);
        arsort($tmpvalues);
        $popular = array_slice(array_keys($tmpvalues), 0, 5, true);
        if(count($popular) == 0){
            $popular = null;
            $customerName = null;
        }else{
            $popular =  $popular[0];
            $customerName = $quotationbusiness->search_customer_code(['customer'=>(string)$popular]);
            $customerName =  $customerName ['customer'];

        }

        $per_data['customer'] = $popular ;
        $per_data['customer_name'] =  $customerName;
        
        $per_data['quotation_business_id'] = $quotationbusiness->post_quotation($params);
        
        unset($per_data['subject']);
        unset($per_data['contents']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($per_data);
        return $response;
    }

    public function recognize_order_name($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $quotationbusiness = new quotationbusiness($this->container->db);
        $result = $quotationbusiness->mailNameParse($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getCustomerCodes($request, $response, $args)
    {
        // $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getCustomerCodes();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getMailBusinessDatatables($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $quotationbusiness = new quotationbusiness($this->container->db);
        $result = $quotationbusiness->getMailBusiness($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getAllMailModuleUrl($request, $response, $args)
    {
        $quotationbusiness = new Quotationbusiness($this->container->db);
        // $data = $request->getQueryParams();
        $result = $quotationbusiness->getAllMailModuleUrl();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    function getWeight($request, $response, $args)
    {
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $data = $request->getQueryParams();
        $result = $quotationbusiness->getWeight($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    function patchWeight($request, $response, $args)
    {
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $data = $request->getParsedBody();
        $result = $quotationbusiness->patchWeight($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postProcessConfirm($request, $response, $args)
    {
        $datas = $request->getParsedBody();

        $quotationbusiness = new Quotationbusiness($this->container->db);
        $home = new Home($this->container->db);

        $result = $quotationbusiness->sendNotify($datas);

        $datas = $quotationbusiness->get_quotation_business_id_bymailid($datas);
        foreach ($datas as $row => $data) {
            $result = $quotationbusiness->quotationPatchData($data);
            $result = $quotationbusiness->postQuotationAttachFile($data);
            $result = $quotationbusiness->postQuotationFileContent($data);

            if ($data['TB001'] !== null && $data['TB002'] !== null && $data['TB003'] !== null) {
                $datause['fk'] = json_encode([
                        'TB001'=>$data['TB001'],
                        'TB002'=>$data['TB002'],
                        'TB003'=>$data['TB003'],
                        'ClientName'=>$data['FileName'],
                        'FileName'=>$data['FileName'],
                        'file_id'=>$data['file_id'],                        
                ]);
                $result = $home->getFileByFK($datause, false);
                // var_dump($result);
                foreach ($result as $key => $row) {
                    $row['itemno'] = '002';
                    $row['file_id']= $data['file_id'];
                    $row['overall_comment'] = $datas[$key]['overall_comment'];
                    $row['quotation_business_id']= $datas[$key]['quotation_business_id'];
                    $stmt_parent = [
                        "parent" => 0
                    ];
                    foreach($datas[$key]as $parent_key=>$parent_value){
                        array_key_exists($key,$datas[$key])&&$stmt_parent[$parent_key]=$datas[$key][$parent_key];
                    }
                    $row['parent']= $stmt_parent['parent'];

                    $result = $home->cloneFile($row, false);
                    if (array_key_exists('id', $result)) {
                        $result += $datause;

                        $result['file_id'] = $data['file_id'];
                        
                        $result['FileName'] = $data['FileName'];
                        $home->postfix($result);
                        break;
                    }
                }
            } else {
                $data['itemno'] = '001';
                $result = $quotationbusiness->quotationPatchFile($data);
                $home->setProgress($data['file_id'], 4);
                $quotationbusiness->postQuotationProgress($data);
                $home->postfix($data); 
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getWeightList($request, $response, $args){
        $data = $request->getQueryParams();
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $result = [];
        $result['stuff'] = $quotationbusiness->getStuff($data);
        $result['material'] = $quotationbusiness->getMaterial($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getWeightRecord($request, $response, $args){
        $data = $request->getQueryParams();
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $result = $quotationbusiness->getWeightRecord($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postWeightRecord($request, $response, $args)
    {
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $data = $request->getParsedBody();
        $result = $quotationbusiness->postWeightRecord($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteWeightRecord($request, $response, $args)
    {
        $quotationbusiness = new Quotationbusiness($this->container->db);
        $data = $request->getParsedBody();
        $result = $quotationbusiness->deleteWeightRecord($data);
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