<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HomeController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }
//報價新首頁
    // public function renderNewQuotationHome($request, $response, $args)
    // {
    //     $renderer = new PhpRenderer($this->container->view);
    //     return $renderer->render($response, '/quotation/home.html', []);
    // }

    public function home($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/index.php', []);
    }
    public function renderValitation($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/ai/validation/index.html', []);
    }
    
    public function drawPic($request, $response)
    {
        function getCode($num, $w, $h)
        {
            $code = "";
            for ($i = 0; $i < $num; $i++) {
                $code .= rand(0, 9);
            }
            //4位驗證碼也可以用rand(1000,9999)直接生成
            //將生成的驗證碼寫入session，備驗證時用
            session_start();
            $_SESSION["codeCheck"] = $code;
            session_write_close();
            //建立圖片，定義顏色值
            header("Content-type: image/PNG");
            $im = imagecreate($w, $h);
            $black = imagecolorallocate($im, 0, 0, 0);
            $gray = imagecolorallocate($im, 200, 200, 200);
            $bgcolor = imagecolorallocate($im, 255, 255, 255);
            //填充背景
            imagefill($im, 0, 0, $gray);
            //畫邊框
            imagerectangle($im, 0, 0, $w - 1, $h - 1, $black);

            //將數字隨機顯示在畫布上,字元的水平間距和位置都按一定波動範圍隨機生成
            $strx = rand(3, 30);
            for ($i = 0; $i < $num; $i++) {
                $strpos = rand(1, 5);
                imagestring($im, 5, $strx, $strpos, substr($code, $i, 1), $black);
                $strx += rand(6, 15);
            }
            imagepng($im); //輸出圖片
            imagedestroy($im); //釋放圖片所佔記憶體
        }
        getCode(4, 100, 40);
        return $response;
    }
    public function register($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $data = $data['register'];
        $username = $data['user_name'];
        $password = $data['password'];
        $password2 = $data['password2'];
        $email = $data['email'];

        $number = 0;
        $tmpsambaSID = '';
        $tmpuidnumber = '';
        $tmpgidnumber = '';


        while (true) {
            $tmpsambaSID = 'S-1-5-21-1286864893-3306830231-2186725024-' . str_pad($number, 4, "0", STR_PAD_LEFT);
            $tmpuidnumber = '100' . str_pad($number, 4, "0", STR_PAD_LEFT);
            $tmpgidnumber = '100' . str_pad($number, 4, "0", STR_PAD_LEFT);
            // var_dump($tmpsambaSID);

            $ldap = $this->container->ldap;
            $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", '(sambasid=' . $tmpsambaSID . ')');
            $sambasidinfo = ldap_get_entries($ldap['conn'], $sr);
            $ldap = $this->container->ldap;
            $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", '(uidnumber=' . $tmpuidnumber . ')');
            $uidnumberinfo = ldap_get_entries($ldap['conn'], $sr);
            $ldap = $this->container->ldap;
            $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", '(gidnumber=' . $tmpgidnumber . ')');
            $gidnumberinfo = ldap_get_entries($ldap['conn'], $sr);

            // var_dump($uidnumberinfo['count'], $gidnumberinfo['count'],$sambasidinfo['count'] );


            if ($number == 100) {
                break;
            } else if ($uidnumberinfo['count'] == 0 && $gidnumberinfo['count'] == 0 && $sambasidinfo['count'] == 0) {
                // var_dump(str_pad($number, 4, "0", STR_PAD_LEFT));
                // var_dump('0');
                break;
            }
            $number++;
        }
        // var_dump($tmpsambaSID );
        // var_dump($tmpuidnumber );
        // var_dump($tmpgidnumber );

        // return $data;


        $ldap = $this->container->ldap;
        $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", '(uid=' . $username . ')');
        $info = ldap_get_entries($ldap['conn'], $sr);
        // var_dump($info);

        if ($info['count'] !== 0) {
            $errors[] = "username allready in use";
            ldap_close($ldap['conn']);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($errors);
            return $response;
        }
        $info = array();
        $info["objectClass"] = array();
        $info["objectClass"][] = "apple-user";
        $info["objectClass"][] = "extensibleObject";
        $info["objectClass"][] = "inetOrgPerson";
        $info["objectClass"][] = "organizationalPerson";
        $info["objectClass"][] = "person";
        $info["objectClass"][] = "posixAccount";
        $info["objectClass"][] = "sambaIdmapEntry";
        $info["objectClass"][] = "sambaSamAccount";
        $info["objectClass"][] = "shadowAccount";
        $info["objectClass"][] = "top";
        $info["sambasid"] = $tmpsambaSID;
        $info["uid"] = $username;
        $info["sn"] = $username;
        $info["uidnumber"] = $tmpuidnumber;
        $info["gidnumber"] = $tmpgidnumber;

        $info["homeDirectory"] = "/home/" . $username;
        $info["givenName"] = $username;
        $info["displayName"] = $username;
        $info["cn"] = $username;
        $info["mail"] = $email;
        $info["userPassword"] = "" . $password;
        $info["memberOf"] = "cn=users,cn=groups,dc=mil,dc=com,dc=tw";


        $add = ldap_add($ldap['conn'], "uid=$username,cn=users,dc=mil,dc=com,dc=tw", $info);
        ldap_close($ldap['conn']);

        $home = new Home($this->container->db);
        $data['editor_id'] = $_SESSION['id'];
        $result = $home->addnewUser($data);
        // foreach ($data['module_id'] as $data_) {
        //     $home->createUserModal($result[0]['id'], $data_);
        // }
        $res = [];
        $res['editor'] = $home->readUserDetailEditorName($data['editor_id'])['editor'];
        $res['id'] = $result[0]['id'];
        $res['status'] = 'success';
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($res);
        return $response;
    }

    public function login($request, $response, $args)
    {
        $data = $request->getParsedBody();
        if ($data["code"] != $_SESSION["codeCheck"]) {
            $result = ['message' => '驗證碼錯誤'];
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            $response = $response->withStatus(500);
            return $response;
        }

        $account = $data['account'];
        $password = $data['password'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:80/login_remote");
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                $data
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        $name = $result['name'];
        $result = $result['password'];
        // $ldap = $this->container->ldap;
        // $result = ldap_search($ldap['conn'], "dc=mil,dc=com,dc=tw", "objectClass=person") or die("Error in query");
        // $datas = ldap_get_entries($ldap['conn'], $result);
        // foreach ($datas as $data) {
        //     for ($i = 0; $i < $data['uid']['count']; $i++) {
        //         if ($data['uid'][$i] == $account) {
        //             $dn = $data['dn'];
        //             for ($j=0; $j < $data['displayname']['count']; $j++) { 
        //                 $name = $data['displayname'][$j];
        //             }
        //         }
        //     }
        // }
        // $result = ldap_bind($ldap['conn'], $dn, $password);
        // ldap_close($ldap['conn']);
        if (!$result) {
            $result = ["message" => "帳號或密碼錯誤"];
            $response = $response->withStatus(500);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
        $data = [
            "uid" => $account,
            "name" => $name
        ];
        $home = new Home($this->container);
        $users = $home->getUserByUid($data);
        if (count($users) == 0) {
            $result = ["href" => "/"];
            $home->addUser($data);
        } else {
            $result = ["href" => "/"];
            foreach ($users as $key => $user) {
                if (!is_null($user['module_id']))
                    $result = ["href" => "/?module_id={$user['module_id']}"];
            }
        }
        foreach ($users as $key => $user) {
            session_start();
            $_SESSION['id'] = $user['id'];
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function renderLogin($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/login.html', []);
    }
    public function renderRegister($request, $response, $args)
    {

        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/register.html', []);
    }
    public function renderChangePassword($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/changePassword.html', []);
    }
    public function renderBusiness($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/business.php', []);
    }

    public function renderDiscriptNewOther($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/discript/newother.html', []);
    }
    public function renderDiscriptOther($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/discript/other.html', []);
    }
    public function renderappraisalSummary($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/discript/appraisalSummary.html', []);
    }

    public function renderRegistration($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/registration.html', []);
    }
    public function renderHomePage($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/homePage.html', []);
    }
    public function renderIndex($request, $response, $args)
    {
        $data = $request->getQueryParams();
        if (!isset($data['id'])) {
            $home = new Home($this->container->db);
            $file_id = $home->insertFile($data);
            $response = $response->withRedirect("/home?id={$file_id}", 301);
            return $response;
        }
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/home.php', []);
    }
    public function renderOrder($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/neworder.php', []);
    }
    public function renderTable($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/table.php', []);
    }
    public function renderDrafting($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/drafting.php', []);
    }
    public function renderEditing($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/editing.php', []);
    }

    public function renderComponents($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/components.php', []);
    }
    public function renderClassification($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/classification.php', []);
    }
    public function renderFinish($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/finish.php', []);
    }

    public function hello($request, $response, $args)
    {
        phpinfo();
        return $response;
    }

    public function postSimilarityCustomer($request, $response, $args)
    {
        // $data = $request->getQueryParams();
        // $directory = $this->container->upload_directory;
        // $uploadedFiles = $request->getUploadedFiles();
        // $uploadedFile = $uploadedFiles['file'];

        // if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        //     $filename = moveUploadedFile($directory, $uploadedFile);
        //     $home = new Home($this->container->db);
        //     $data['clientFileName'] = $uploadedFile->getClientFilename();
        //     $data['fileName'] = $filename;
        //     if (strpos($filename, '.pdf') !== FALSE || strpos($filename, '.PDF') !== FALSE) {
        //         $cutUrl = 'http://127.0.0.1:8090/pdf?filename=' . $filename;
        //         $cutResult = $home->http_response($cutUrl);
        //         $filename_jpg = json_decode($cutResult, true);
        //         $data['fileName'] = $filename_jpg['filename'];
        //         $filename = $filename_jpg['filename'];
        //     }
        //     $filepath = $this->container->upload_directory . $filename;
        //     $phasegallerycontroller = new PhaseGalleryController();
        //     $exif = @exif_read_data($filepath);
        //     $source = $phasegallerycontroller->compressImage($filepath, $filepath, 100);
        $data = $request->getParams();
        $home = new Home($this->container->db);
        $logoArr = array();
        $directory = $this->container->upload_directory;
        $uploadedFiles = $request->getUploadedFiles();

        $uploadedFile = $uploadedFiles['inputFile'];
        // $file_ids = $home->getFileById($data);
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $result = ["status"=>"failure"];
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
        // var_dump($uploadedFile);
        $fileName = moveUploadedFile($directory, $uploadedFile);
        if (strpos($fileName, '.pdf') !== FALSE || strpos($fileName, '.PDF') !== FALSE) {
            $tmpfile =  json_encode([$fileName]);
            $recogUrl = "http://mil_python:8090/pdfSplit?Files={$tmpfile}";
            $jpgarr = $home->http_response($recogUrl);
            $jpgarr = json_decode($jpgarr, true);
            $jpgarr = $jpgarr[$fileName];
           
        //     $cutUrl = 'http://mil_python:8090/pdf?filename=' . $fileName;
        //     $cutResult = $home->http_response($cutUrl);

        //     $filename_jpg = json_decode($cutResult, true);
        //     // $data['fileName'] = $filename_jpg['filename'];
        //     $fileName = $filename_jpg['filename'];
        // var_dump($jpgarr);

        }else{
            $jpgarr = array($fileName);
        }

        $allresult = array();
       
        foreach($jpgarr AS $key => $value){
            // var_dump($value);
            $logoArr = json_encode($logoArr);
            $recogUrl = "http://mil_python:8090/matchCustomer?fileName={$value}&logo={$logoArr}";
            $maxID = $home->http_response($recogUrl);
            $maxID = json_decode($maxID);

            $image = $home -> getBase64_encode($value);
            $result = array('customer' => $maxID->value ,'src' => $image);

            array_push($allresult,$result);

        }


       
        // var_dump($maxID);


            
            // $result = array('customer' => $maxID->value ,'src' => $image);

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($allresult);
        return $response;
    }

    public function getsimilaritypic($request, $response, $args)
    {
        $business = new Business($this->container->db);
        $result = [];
        $result['customer'] = $business->getCustomerCodes();
        $result['item_type'] = [
            "06"=>"06-前沖棒",
            "07"=>"07-後沖棒",
            "03"=>"03-模仁",
            "08"=>"08-通孔沖棒",
            "02"=>"02-模組",
            "02"=>"02-模組",
            "03"=>"03-模仁",
            "01"=>"01-切刀",
            "04"=>"04-模殼",
            "09"=>"09-套管",
            "10"=>"10-墊塊",
            "11"=>"11-沖棒固定塊",
            "12"=>"12-公牙",
            "13"=>"13-夾子",
            "14"=>"14-零件",
            "15"=>"15-棘輪",
            "16"=>"16-PIN",
            "17"=>"17-通孔管",
            "18"=>"18-其他",
        ];
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);

        return $response;
    }
    public function postsimilaritypic($request, $response, $args)
    {
     
        $home = new Home($this->container->db);

        $data = $request->getParams();
        // $result = $home->getProcessId($args['component_id']);
        // $resultEncode = json_encode($result);
        $directory = $this->container->upload_directory;
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['inputFile'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $result = ["status"=>"failure"];
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }

       

        // $data['FileName'] = moveUploadedFile($directory, $uploadedFile);
        $fileName = moveUploadedFile($directory, $uploadedFile);
        // if (strpos($fileName, '.pdf') !== FALSE || strpos($fileName, '.PDF') !== FALSE) {
        //     $tmpfile =  json_encode([$fileName]);
        //     $recogUrl = "http://mil_python:8090/pdfSplit?Files={$tmpfile}";
        //     $jpgarr = $home->http_response($recogUrl);
        //     $jpgarr = json_decode($jpgarr, true);
        //     $jpgarr = $jpgarr[$fileName];
           
        // }else{
            $jpgarr = array($fileName);
        // }
        
        $allresult = array();
       
        foreach($jpgarr AS $key => $value){
            $value = array_merge(['FileName'=>$value],$data);
            $result = $home->postsimilaritypic($value);
            $allresult = array_merge($allresult,$result);
        }
            

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($allresult);

        return $response;
    }

    public function getRegistration($request, $response, $args)
    {
        $home = new Home($this->container->db);
        // $data = $request->getParsedBody();
        $result = $home->getRegistration();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function patchPassword($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $data = $data['tmpArr'];
        // 1qaz@WSX3edc


        $result = $home->patchPassword($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function patchRegistration($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->patchRegistration($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteCommonHardness($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteCommonHardness($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function deleteCommonTitanizing($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteCommonTitanizing($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function deleteCommonMaterial($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteCommonMaterial($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function getCommon($request, $response, $args)
    {
        $home = new Home($this->container->db);
        // $data = $request->getQueryParams();
        $material = $home->getCommonMaterial();
        $titanizing = $home->getCommonTitanizing();
        $hardness = $home->getCommonHardness();
        $result['material'] = $material;
        $result['titanizing'] = $titanizing;
        $result['hardness'] = $hardness;
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function cloneFile($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $home = new Home($this->container->db);
        // $business = new Business($this->container->db);

        // $detail = $business->getFileDetail($data);
        $result = $home->cloneFile($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getDelivery_date($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getDelivery_date($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function patchRotate($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();

        $file_ids = $home->getFileById($data);
        foreach ($file_ids as $key => $file_id) {
            $file = $this->container->upload_directory . '/' . $file_id['FileName'];
            // Load
            $source = imagecreatefromjpeg($file);
            if (!$source) {
                $source = imagecreatefrompng($file);
            }
            if ($data['rotate'] < 0) {
                $data['rotate'] += 360;
            }
            // Rotate
            $rotate = imagerotate($source, 360 - intval($data['rotate']), 0);

            // Output
            imagejpeg($rotate, $file);
        }
        $result = ["status" => "success"];
        // $result = $home->patchRotate($data);
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

    public function patchDelivery_week($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->patchDelivery_week($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function getFile($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $file = $this->container->upload_directory . '/' . $params['name'];
        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . $params['name'] . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($file));
        ob_clean();
        ob_end_flush();
        $handle = fopen($file, "rb");
        while (!feof($handle)) {
            echo fread($handle, 1000);
        }
        return $response;
    }
    public function getFilename($request, $response, $args)
    {
        $data = $args;
        $home = new Home($this->container->db);
        $result = ['data' => $home->getFilename($data)];
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getCropname($request, $response, $args)
    {
        $data = $args;
        $home = new Home($this->container->db);
        $result = ['data' => $home->getCropname($data)];
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getsameCustomerFiles($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $result = [
            "draw" => $data['draw']++,
            "data" => []
        ];
        $home = new Home($this->container->db);
        $orders = $home->getsameCustomerFiles($data);

        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $data['length'];
        $start = $data['start'];
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

    public function getFiles($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $result = [
            "draw" => $data['draw']++,
            "data" => []
        ];
        $home = new Home($this->container->db);
        $orders = $home->getFiles($data);

        $result['recordsTotal'] = 0;
        $result['recordsFiltered'] = 0;
        $length = $data['length'];
        $start = $data['start'];
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
    public function getCrops($request, $response, $args)
    {
        $data = $args;
        $fileID = $data['id'];
        $home = new Home($this->container->db);
        $files = $home->getFileById($data);
        foreach ($files as $key => $file) {
            $fileName = $file['FileName'];
            $recogUrl = "http://127.0.0.1:8090/CustomerParts?fileName={$file['FileName']}&rotate={$file['rotate']}";
            $result = $home->http_response($recogUrl);
            $result = json_decode($result);
            $bounding_box = json_encode($result);

            $recogUrl = "http://127.0.0.1:8090/PartsWithBox?fileName={$fileName}&bounding_box={$bounding_box}&rotate={$file['rotate']}";
            $result = $home->http_response($recogUrl);


            // var_dump($result);
            $result = json_decode($result);

            // var_dump($result->Crop_file);
            $Crop_file = $result->Crop_file;
            $bbox = $result->Bounding_boxes;
            $result = json_encode($result);



            $home->setCrop($fileID, '', $Crop_file,  $bbox); //return img id


            // $crops = $home->getCrops(['id' => $fileID]);
            // $cutUrl = 'http://127.0.0.1:8090/cut?filename=' . $file['FileName'];
            // $cutResult = $home->http_response($cutUrl);
            // $cutResult = json_decode($cutResult, true);
            // $home->setCrop($data['id'], $cutResult['No_recog'], $cutResult['Crop_file'], $cutResult['Bounding_boxes']); //return img id
            $crops = $home->getCrops(['id' => $data['id']]);
            $data_id = $data['id'];
            // $data = ['data' => ['' => []]];
            // foreach ($crops as $key => $crop) {
            //     array_push($data['data'][''], $crop['id']);
            // }
            // $result = $home->insertComponent($data);
            // // var_dump($result);
            // $processArr = [];
            // foreach ($result as $key => $value) {
            //     // var_dump($value);
            //     $processresult = $home->getProcessId($value);
            //     // var_dump($processresult) ;
            //     array_push($processArr, $processresult['process_id']);
            //     // var_dump($processArr) ;

            //     // return $processArr;

            //     $resultEncode = json_encode($processresult);
            //     $curl_recognition = "http://127.0.0.1:8090/compare?data={$resultEncode}";
            //     // $home->http_response($curl_recognition,1);
            // }
            // No_recog
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson([
                "file_id" => $data_id,
                "crops" => $crops
            ]);
        }
        return $response;
    }

    public function getFileByCropId($request, $response, $args)
    {
        $data = $args;
        $home = new Home($this->container->db);
        $file_ids = $home->getFileByCropId($data);
        foreach ($file_ids as $key => $file_id) {
            $file = $this->container->upload_directory . '/Crop/' . $file_id['name'];
            if (!file_exists($file)) {
                return $response;
            }

            $response = $response->withHeader('Content-Description', 'File Transfer')
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Disposition', 'attachment;filename="' . $file_id['name'] . '"')
                ->withHeader('Expires', '0')
                ->withHeader('Cache-Control', 'must-revalidate')
                ->withHeader('Pragma', 'public')
                ->withHeader('Content-Length', filesize($file));
            ob_clean();
            ob_end_flush();
            $handle = fopen($file, "rb");
            while (!feof($handle)) {
                echo fread($handle, 1000);
            }
            return $response;
        }
    }

    public function getFileById($request, $response, $args)
    {
        $data = $args;
        $home = new Home($this->container->db);
        $file_ids = $home->getFileById($data);
        foreach ($file_ids as $key => $file_id) {
            $file = $this->container->upload_directory . '/' . $file_id['FileName'];
            if (!file_exists($file)) {
                return $response;
            }
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
            if (!$source) {
                $source = imagecreatefrompng($file);
            }
            // Rotate
            $rotate = imagerotate($source, intval($file_id['rotate']), 0);

            // Output
            imagejpeg($rotate);
            // $handle = fopen($file, "rb");
            // while (!feof($handle)) {
            //     echo fread($handle, 1000);
            // }
            return $response;
        }
    }

    public function getFileFactoryById($request, $response, $args)
    {
        $data = $args;
        $home = new Home($this->container->db);
        $file_ids = $home->getFileFactoryById($data);
        foreach ($file_ids as $key => $file_id) {
            $file = $this->container->upload_directory . '/' . $file_id['FileNameFactory'];
            $fh = fopen($file, 'rb');
            $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

            $response = $response->withHeader('Content-Description', 'File Transfer')
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Disposition', 'attachment;filename="' . $file_id['FileNameFactory'] . '"')
                ->withHeader('Expires', '0')
                ->withHeader('Cache-Control', 'must-revalidate')
                ->withHeader('Pragma', 'public')
                ->withHeader('Content-Length', filesize($file))
                ->withBody($stream); // all stream contents will be sent to the response
            return $response;
        }
    }

    public function setFinish($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $progress_ids = $home->getFinish($data);
        foreach ($progress_ids as $key => $progress_id) {
            $result = $home->setProgress($data['id'], $progress_id['id']);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($progress_id);
            return $response;
        }
    }

    public function setProgress($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $progress_ids = $home->getProgress($data);
        foreach ($progress_ids as $key => $progress_id) {
            $result = $home->setProgress($data['id'], $progress_id['id']);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
    }


    public function uploadFactory($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $directory = $this->container->upload_directory;
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['file'];

        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $uploadedFile);
            $home = new Home($this->container->db);
            $data['clientFileName'] = $uploadedFile->getClientFilename();
            $data['fileName'] = $filename;
            if (strpos($filename, '.pdf') !== FALSE) {
                $cutUrl = 'http://127.0.0.1:8090/pdf?filename=' . $filename;
                $cutResult = $home->http_response($cutUrl);
                $filename_jpg = json_decode($cutResult, true);
                $data['fileName'] = $filename_jpg['filename'];
                $filename = $filename_jpg['filename'];
            }
            $result = $home->uploadFactory($data);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson([
                "file_id" => $result,
            ]);
            return $response;
        }
    }

    public function getMatchCustomer($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $logo = $home->getOtherLogo($data);

        $logoArr = array();
        array_push($logoArr, array("file_id" => '0', "name" => '1010180.png', "value" => '1010180', "type" => 'logo'));
        array_push($logoArr, array("file_id" => '1', "name" => '1010400.png', "value" => '1010400', "type" => 'logo'));
        array_push($logoArr, array("file_id" => '2', "name" => '1010660.png', "value" => '1010660', "type" => 'logo'));
        array_push($logoArr, array("file_id" => '3', "name" => '1020010.png', "value" => '1020010', "type" => 'logo'));
        array_push($logoArr, array("file_id" => '4', "name" => '2080010.png', "value" => '2080010', "type" => 'logo'));


        $file_ids = $home->getFileById($data);
        $fileName = $file_ids[0]['FileName'];

        if ($fileName == '' || is_null($fileName)) {
            $maxID = array();
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($maxID);
            return $response;
        }

        $logoArr = json_encode($logoArr);
        $recogUrl = "http://127.0.0.1:8090/matchCustomer?fileName={$fileName}&logo={$logoArr}";
        $maxID = $home->http_response($recogUrl);
        $maxID = json_decode($maxID);

        // $result = $home->getFileCustomer($maxID);

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($maxID);
        return $response;
    }


    public function patchCropandCheck($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        if (array_key_exists('array', $data)) {
            $result = $home->patchCrops($data);
        }

        $crops = $home->getCrops(['id' => $data['id']]);
        $data_id = $data['id'];
        $data = ['data' => ['' => []]];
        $cropfileStr = '';

        foreach ($crops as $key => $crop) {
            array_push($data['data'][''], $crop['id']);
            $cropfileStr .= "%22../uploads/Crop/{$crop['file_name']}%22,";
        }
        $result = $home->insertComponent($data);
        $cropfileStr = substr_replace($cropfileStr, "", -1);

        // var_dump($result);
        // return;
        // var_dump($result);

        $processArr = [];
        foreach ($result as $key => $value) {
            // var_dump($value);
            $processresult = $home->getProcessId($value);
            // var_dump($processresult) ;
            array_push($processArr, $processresult['process_id']);
            // var_dump($processresult);
            // var_dump($cropfileStr);
            $curl_recognition = "http://localhost:8090/CNNPartSuggestion?top_k=5&crops={%22paths%22:[{$cropfileStr}]}";
            $CNNPartSuggestion = $home->http_response($curl_recognition);
            // var_dump( $CNNPartSuggestion );
            $CNNPartSuggestion = json_decode($CNNPartSuggestion);

            $CNNresult = $home->insertCNNResult(['process_id' => $processresult['process_id'], 'CNN' => $CNNPartSuggestion, 'crops' => $crops]);

            // return;
            // var_dump($processArr) ;

            // return $processArr;

            // $resultEncode = json_encode($processresult);
            // $curl_recognition = "http://127.0.0.1:8090/compare?data={$resultEncode}";
            // $home->http_response($curl_recognition,1);
        }



        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getCropandCheck($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $this->getCrops($request, $response, $data);
        $result = $home->getCrops($data);
        $cropfileStr = '';
        foreach ($result as $key => $value) {
            $cropfileStr .= "%22../uploads/Crop/{$value['file_name']}%22,";
        }
        $cropfileStr = substr_replace($cropfileStr, "", -1);
        // var_dump($cropfileStr);
        $recogUrl = "http://127.0.0.1:8090/CNNPartFilter?crops={%22paths%22:[{$cropfileStr}]}";
        $CNNPartFilter = $home->http_response($recogUrl);
        // var_dump( $CNNPartFilter );
        $CNNPartFilter = json_decode($CNNPartFilter);
        // $CNNPartFilter = json_encode($CNNPartFilter);
        foreach ($CNNPartFilter as $key => $value) {
            $result[$key]['isPart'] = $value->isPart;
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function upload($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $directory = $this->container->upload_directory;
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['file'];

        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $uploadedFile);
            $home = new Home($this->container->db);
            $data['clientFileName'] = $uploadedFile->getClientFilename();
            $data['fileName'] = $filename;
            if (strpos($filename, '.pdf') !== FALSE || strpos($filename, '.PDF') !== FALSE) {
                $cutUrl = 'http://127.0.0.1:8090/pdf?filename=' . $filename;
                $cutResult = $home->http_response($cutUrl);
                $filename_jpg = json_decode($cutResult, true);
                $data['fileName'] = $filename_jpg['filename'];
                $filename = $filename_jpg['filename'];
            }
            $filepath = $this->container->upload_directory . $filename;
            $phasegallerycontroller = new PhaseGalleryController();
            $exif = @exif_read_data($filepath);
            $source = $phasegallerycontroller->compressImage($filepath, $filepath, 100);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $source = imagerotate($source, 180, 0);
                        break;

                    case 6:
                        $source = imagerotate($source, -90, 0);
                        break;

                    case 8:
                        $source = imagerotate($source, 90, 0);
                        break;
                }
            }
            imagejpeg($source, $filepath);
            // $cutUrl = 'http://127.0.0.1:8090/rotate?filename=' . $filename;
            // $cutResult = $home->http_response($cutUrl);
            // $degrees = json_decode($cutResult, true);
            // $data['rotate'] = $degrees['rotate'];
            $data['rotate'] = 0;
            $result = $home->upload($data);
            if ($data['mode'] == 'components') {
                $cutUrl = 'http://127.0.0.1:8090/cut?filename=' . $filename;
                $cutResult = $home->http_response($cutUrl);
                $cutResult = json_decode($cutResult, true);
                $home->setCrop($result, $cutResult['No_recog'], $cutResult['Crop_file'], $cutResult['Bounding_boxes']); //return img id
                $crops = $home->getCrops(['id' => $result]);
                // No_recog
                $response = $response->withHeader('Content-type', 'application/json');
                $response = $response->withJson([
                    "file_id" => $result,
                    "crops" => $crops
                ]);
            } else if ($data['mode'] == 'compare') {
                $cutUrl = 'http://127.0.0.1:8090/cut?filename=' . $filename;
                $cutResult = $home->http_response($cutUrl);
                $cutResult = json_decode($cutResult, true);
                $home->setCrop($result, $cutResult['No_recog'], $cutResult['Crop_file'], $cutResult['Bounding_boxes']); //return img id
                $result_match = $home->getProcessIdForMatch($result);
                $resultEncode = json_encode($result_match);
                $cutUrl = "http://127.0.0.1:8090/match?data={$resultEncode}";
                $cutResult = $home->http_response($cutUrl);
                $crops = $home->getCrops(['id' => $result]);
                // No_recog
                $response = $response->withHeader('Content-type', 'application/json');
                $response = $response->withJson([
                    "file_id" => $result,
                    "process" => $result_match,
                    "crops" => $crops
                ]);
            } else if ($data['mode'] == 'textrecog') {
                // $this->getCrops($request, $response, ["id" => $result]);
                $result_match = $home->getProcessIdForMatch($result);
                $resultEncode = json_encode($result_match);
                $cutUrl = "http://127.0.0.1:8090/match?data={$resultEncode}";
                $cutResult = $home->http_response($cutUrl, 1);
                // // No_recog




                $response = $response->withHeader('Content-type', 'application/json');
                $response = $response->withJson([
                    "file_id" => $result,
                ]);
            }
            return $response;
        }
    }

    public function renderCompare($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, "/compare_file.html");
    }

    public function renderMaterialrecog($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, "/materialrecog.html");
    }
    public function getComment($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getComment($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchComment($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->patchComment($data);
        return $this->setProgress($request, $response, $args);
    }
    public function getOutsourcerList($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $result = $home->getOutsourcerList();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getOutsourcerSetting($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $result = $home->getOutsourcerSetting();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getOutsourcerCost($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $result = $home->getOutsourcerCost();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOutsourcerLimittime($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getOutsourcerLimittime($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOutsourcerHistory($request, $response, $args)
    {
        $home = new Home($this->container->db);
        // $data = $request->getQueryParams();
        $result = $home->getOutsourcerHistory();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOutsourcerCount($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getOutsourcerCount($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOutsourcer($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getOutsourcer($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getCommentOutsourcerHistory($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getCommentOutsourcerHistory($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function updateOutsourcer($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->updateOutsourcer($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getMaterial($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result['material'] = $home->getMaterial($data);
        $result['titanizing'] = $home->getTitanizing($data);
        $result['hardness'] = $home->getHardness($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postLock($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postLock($data);
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

    public function getFinishSuggestion($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result['title'] = $home->getFinishSuggestion($data);
        $result['val'] = $home->getFinishSuggestionVal($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postFinishSuggestion($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postFinishSuggestion($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchFinishSuggestion($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->patchFinishSuggestion($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteFinishSuggestion($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteFinishSuggestion($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postFinishSuggestionVal($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postFinishSuggestionVal($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postComment($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postComment($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getFile_comment($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result['comment'] = $home->getFile_comment($data);
        $result['fileinfo'] = $home->getFileInfo($data);
        $result['material'] = $home->getMaterial($data);
        $result['titanizing'] = $home->getTitanizing($data);
        $result['hardness'] = $home->getHardness($data);
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
    public function getFile_commentCanvas($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getFile_commentCanvas($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getFile_commentTextbox($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getFile_commentTextbox($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postFile_commentTextbox($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postFile_commentTextbox($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function postFile_commentCanvas($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postFile_commentCanvas($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function deleteComment($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteComment($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postProcessComment($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postProcessComment($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteProcessComment($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteProcessComment($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getCommentComponent($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getCommentComponent($data);
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
    public function deleteCommentComponent($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteCommentComponent($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getFileInfomation($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $args;
        $result = $home->getFileInfomation($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function renderFileComment($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/file/comment.html', []);
    }

    public function renderCheckQuotation($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/quotation/check.html', []);
    }

    public function renderState($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/file/state.html', []);
    }
    public function getState($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $args;
        $data['user_id'] = $_SESSION['id'];
        foreach ($request->getQueryParams() as $key => $param) {
            $data[$key] = $param;
        }
        $result['file_information'] = $home->getFileInfomation($data);
        $result['state'] = $home->getState($data);
        $result['station'] = $home->getStation($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postmodifyprocess($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        #var_dump($request->getParsedBody());
        $result = $home->postmodifyprocess($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getProcess_cost($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getQueryParams();
        $result['now'] = $home->getProcess_cost($data);
        $result['other'] = $home->getOtherProcess_cost($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postProcess_cost($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        $result = $home->postProcess_cost($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postmodifyprocessOutsourcer($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        #var_dump($request->getParsedBody());
        $result = $home->postmodifyprocessOutsourcer($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getmodifyprocessOutsourcerHistory($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getQueryParams();

        #var_dump($request->getParsedBody());
        $unfinish = $home->getmodifyprocessOutsourcerHistory($data);
        $result['unfinish'] = $unfinish;


        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $finish = $business->getProcessCount($data);
        $result['finish'] = $finish;
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getmodifyprocessOutsourcerTemperary($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getQueryParams();

        $unfinish = $home->getmodifyprocessOutsourcerTemperary($data);
        $result['unfinish'] = $unfinish;


        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $finish = $business->getProcessCount($data);
        $result['finish'] = $finish;
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getmodifyprocessOutsourcerCount($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getmodifyprocessOutsourcerCount($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getAllmodifyprocess($request, $response, $args)
    {
        $business = new Business($this->container->db);
        $data = $request->getQueryParams();
        // $unfinish = $home->getAllmodifyprocess($data);
        $data['is_finish'] = 'N';
        $result = [];
        $result['unfinish'] = $business->getProcessCount($data);
        $data['is_finish'] = 'Y';
        $result['finish'] = $business->getProcessCount($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getAllProcessCount($request, $response, $args)
    {
        $business = new Business($this->container->db);
        $data = $request->getQueryParams();
        $result = $business->getAllProcessCount($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getmodifyprocess($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getQueryParams();
        #var_dump($request->getParsedBody());
        $result = $home->getmodifyprocess($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getTech_width($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        #var_dump($request->getParsedBody());
        $result = $home->getTech_width($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postTech_width($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        $result = $home->postTech_width($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function patchOrderName($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        #var_dump($request->getParsedBody());
        $result = $home->patchOrderName($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function insertOrderSerial($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        #var_dump($request->getParsedBody());
        $result = $home->insertOrderSerial($data);
        $result = $home->setProgress($data['id'], 1);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function insertResult($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        #var_dump($request->getParsedBody());
        $result = $home->insertResult($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getComponentMatch($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $business = new Business($this->container->db);

        $data = $args;
        foreach ($request->getQueryParams() as $key => $value) {
            $data[$key] = $value;
        }
        #var_dump($request->getParsedBody());
        $result = $home->getComponentMatch($data);
        $result['process'] = $business->getProcessByComponentName($result);
        $result['status'] = $home->getProcessStatus($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function getResultMatch($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $args;
        foreach ($request->getQueryParams() as $key => $param) {
            $data[$key] = $param;
        }
        #var_dump($request->getParsedBody());
        $result['result'] = $home->getResultMatch($data);
        $result['status'] = $home->getProcessStatus($data);
        $result['process_id'] = $data['process_id'];
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function stopResultMatch($request, $response, $args)
    {

        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->delete($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function insertResultMatch($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        #var_dump($request->getParsedBody());
        $result = $home->insertResultMatch($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }



    public function getOverview($request, $response, $args)
    {

        $data = $args;

        $home = new Home($this->container->db);
        $result = [];
        $result['daily_order'] = $home->getDailyOrder($data);
        $result['history_order'] = $home->getHistoryOrder($data);
        $result['dow_order'] = $home->getDOWOrder($data);
        $data = $request->getQueryParams();

        $result['all_order'] = $home->getAllOrder($data);
        $result['all_order_collapse'] = $home->getAllOrderCollapse($data);
        $result['card_authority'] = $home->getCardAuthority($data);

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);

        return $response;
    }

    public function getProgresses($request, $response, $args)
    {

        $data = $args;

        $home = new Home($this->container->db);
        $result = $home->getProgresses($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getProcessId($request, $response, $args)
    {

        $home = new Home($this->container->db);

        // $data = $request->getParsedBody();
        $result = $home->getProcessId($args['component_id']);
        $resultEncode = json_encode($result);
        $curl_recognition = "http://localhost:8090/compare?data={$resultEncode}";
        $home->http_response($curl_recognition);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);

        return $response;
    }

    public function postLogo($request, $response, $args)
    {
        $data = $request->getParsedBody();

        $home = new Home($this->container->db);
        $file_ids = $home->getFileById($data);
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($file_ids[0]['FileName']);
        $fileID = $data['id'];

        $fileName = $file_ids[0]['FileName'];


        $box = json_encode($data['box']);
        $recogUrl = "http://127.0.0.1:8090/cutLogo?fileName={$fileName}&box={$box}&rotate={$file_ids[0]['rotate']}&type={$data['type']}";
        $result = $home->http_response($recogUrl);

        $name = json_decode($result);
        $result = $home->postLogo($data, $name);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getPartsWithBox($request, $response, $args)
    {
        $data = $request->getQueryParams();

        $home = new Home($this->container->db);
        $file_ids = $home->getFileById($data);
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($file_ids[0]['FileName']);
        $fileID = $data['id'];

        $fileName = $file_ids[0]['FileName'];


        $bounding_box = json_encode($data['box']);
        $recogUrl = "http://127.0.0.1:8090/PartsWithBox?fileName={$fileName}&bounding_box={$bounding_box}&rotate={$file_ids[0]['rotate']}";
        $result = $home->http_response($recogUrl);


        // var_dump($result);
        $result = json_decode($result);

        // var_dump($result->Crop_file);
        $Crop_file = $result->Crop_file;
        $bbox = $result->Bounding_boxes;
        $result = json_encode($result);



        $home->setCrop($fileID, '', $Crop_file,  $bbox); //return img id
        $crops = $this->getCrops($request, $response, ["id" => $result]);
        // No_recog
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson([
            "file_id" => $fileID,
            "crops" => $crops
        ]);
        return $response;
    }

    public function getClassificationNum($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getCrops($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getCustomerParts($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $file_ids = $home->getFileById($data);
        foreach ($file_ids as $key => $file_id) {
            $recogUrl = "http://127.0.0.1:8090/CustomerParts?fileName={$file_id['FileName']}&rotate={$file_id['rotate']}";
            $result = $home->http_response($recogUrl);
            // var_dump($result);
            $result = json_decode($result);
            $result = json_encode($result);
            return ($result);
        }
    }

    public function getRecog($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $file_ids = $home->getFileById($data);
        foreach ($file_ids as $key => $file_id) {
            $recogUrl = 'http://127.0.0.1:8090/recog?filename=' . $file_id['FileName'] . "&rotate={$file_id['rotate']}";
            $result = $home->http_response($recogUrl);
            // var_dump($result);
            $result = json_decode($result);
            $result = json_encode($result);
            return ($result);
        }
    }

    public function getHistoryPicture($request, $response, $args)
    {

        $home = new Home($this->container->db);

        $data = $request->getParsedBody();
        $data = $args;

        $result = $home->getHistoryPicture($data);
        $file = @$result['picture'];
        if(!file_exists($file)) {
            $result = $home->get_picture_by_order_name($data);
            $file = @$result['picture'];
            if (!file_exists($file)) {
                return $response;
            }
        }
        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . $data['name'] . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($file));
        ob_clean();
        ob_end_flush();
        $handle = fopen($file, "rb");
        while (!feof($handle)) {
            echo fread($handle, 1000);
        }
        return $response;
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($result);
        // if( $result['status'] == 'success'){
        //     $file = $result['picture'];
        //     $response = $response->withHeader('Content-Description', 'File Transfer')
        //         ->withHeader('Content-Type', 'application/octet-stream')
        //         ->withHeader('Content-Disposition', 'attachment;filename="out.pdf"')
        //         ->withHeader('Expires', '0')
        //         ->withHeader('Cache-Control', 'must-revalidate')
        //         ->withHeader('Pragma', 'public')
        //         ->withHeader('Content-Length', filesize($file));
        //     readfile($file);
        //     return $response;

        // }


        return $response;
    }

    public function getDraftingPicture($request, $response, $args)
    {

        $home = new Home($this->container->db);

        // $data = $request->getParsedBody();
        $data = $request->getQueryParams();

        $result = $home->getDraftingPicture($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);

        return $response;
    }
    public function postTextrecog($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postTextrecog($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function insertTextrecog($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->insertTextrecog($data);
        $progress_ids = $home->getProgress($data);
        foreach ($progress_ids as $key => $progress_id) {
            $result['url'] = $home->setProgress($data['id'], $progress_id['id']);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
    }
    public function setViewed($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result['status'] = "success";
        $progress_ids = $home->getProgress($data);
        foreach ($progress_ids as $key => $progress_id) {
            $result['url'] = $home->setProgress($data['id'], $progress_id['id']);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
    }

    public function deleteTextrecog($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteTextrecog($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function getModifyTextrecog($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getModifyTextrecog($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function stopProcess($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->stopProcess($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getAllCard($request, $response, $args)
    {
        $home = new Home($this->container->db);
        // $data = $request->getQueryParams();
        $result = $home->getAllCard();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getAllModuleUrl($request, $response, $args)
    {
        $home = new Home($this->container->db);
        // $data = $request->getQueryParams();
        $result = $home->getAllModuleUrl();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getModuleSetting($request, $response, $args)
    {
        $home = new Home($this->container->db);
        // $data = $request->getQueryParams();
        $result = $home->getModuleSetting();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getModuleUrl($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getModuleUrl($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getNextUrl($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getNextUrl($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getCardAuthority($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getCardAuthority($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function updateCardAuthority($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->updateCardAuthority($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getUrlAuthority($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getUrlAuthority($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function updateUrlAuthority($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->updateUrlAuthority($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getFileInfo($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getFileInfo($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function updateCustomerSend($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->updateCustomerSend($data);
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

    public function updateDeadline($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->setProgress($data['id'], 4);
        if (array_key_exists('other', $data)) {
            foreach ($data['other'] as $other) {
                $home->setProgress($other, 4);
            }
        }
        $result = $home->updateDeadline($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getPdfQuotation($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = array();
        $result = $home->getEachQuotation(json_decode($data['id']), $data['file_id']);
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($result);
        // return $response;
        $business = new Business($this->container->db);
        // $postQuotation = $business->postQuotationSQLSever($result);
        // var_dump($postQuotation);
        // return;
        $type = "xlsx";
        if(array_key_exists("type",$data)) $type = $data["type"];
        if($type=="pdf"){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://mil_python:8090/quotation");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);     //just some very short timeout        
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    
            curl_setopt($ch, CURLOPT_POST, true);
            // curl_setopt($ch, CURLOPT_TIMEOUT, 5); // CURLOPT_TIMEOUT_MS
            // In real life you should use something like:
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query(
                    array('files' => json_encode($result))
                )
            );
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen(http_build_query(
                        array('files' => json_encode($result))
                    )),
                )
            );
    
            $head = curl_exec($ch);
            curl_close($ch);
    
            sleep(3);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://mil_python:8090/quotation");
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
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query(
                    array('files' => json_encode($result))
                )
            );
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen(http_build_query(
                        array('files' => json_encode($result))
                    )),
                )
            );
            $head = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    
            curl_close($ch);
    
            if ($httpcode == 200) {
                ignore_user_abort(true);          //very important!
                $file = $this->container->upload_directory . '/out.pdf';
                $response = $response->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Disposition', 'attachment;filename="out.pdf"')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate')
                    ->withHeader('Pragma', 'public')
                    ->withHeader('Content-Length', filesize($file));
                readfile($file);
    
                return $response;
            }
        }else{
            $home = new Home($this->container->db);
            $params = $request->getParsedBody();
            $spreadsheet = $home->exportXlsx($result);
            $writer = new Xlsx($spreadsheet);  /* use PhpSpreadsheet xlsx writer */
            $writer->save('php://output');
            $response = $response->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response = $response->withHeader('Content-Disposition', "attachment; filename=\"報價單.xlsx\"");
            return $response;
        }
    }

    public function getQuotation($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $args;
        $result = $home->getQuotation($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postQuotation($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->postQuotation($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function deleteFalseProgress($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->deleteFalseProgress($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getTrendCost($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $file_id = $home->getFileByProcess_mapping_id($data);

        $business = new Business($this->container->db);
        $result = $business->getTrendCost($file_id[0]);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getCurrency($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $result = $home->getCurrency();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }


    public function getFalseProgress($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getFalseProgress($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function addFalseProgress($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->addFalseProgress($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function setProcessCrop($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        // $result = $home->getProcessId($args['component_id']);
        // $resultEncode = json_encode($result);
        // $curl_recognition = "localhost:8090/compare?data={$resultEncode}";
        // $home->http_response($curl_recognition);
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($result);
        return $response;
    }

    public function insertComponent($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->insertComponent($data);
        // var_dump($result);
        $processArr = [];
        foreach ($result as $key => $value) {
            // var_dump($value);
            $processresult = $home->getProcessId($value);
            // var_dump($processresult) ;
            array_push($processArr, $processresult['process_id']);
            // var_dump($processArr) ;

            // return $processArr;

            $resultEncode = json_encode($processresult);
            $curl_recognition = "http://127.0.0.1:8090/compare?data={$resultEncode}";
            $home->http_response($curl_recognition);
        }

        $result = $home->setProgress($data['id'], 6);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getProcessIdByFileId($request, $response, $args)
    {
        $data = $args;
        $home = new Home($this->container->db);
        $processArr = [];
        $cropArr = [];
        $processresult = $home->getProcessIdByFileId($data);
        foreach ($processresult as $key => $value) {
            array_push($cropArr, $value['crop_id']);
            if (!in_array($value['process_id'], $processArr))
                array_push($processArr, $value['process_id']);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson(['process' => $processArr, "crop" => $cropArr]);
        return $response;
    }

    function getMaterialRecogArea($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $file_ids = $home->getFileById($data);
        foreach ($file_ids as $key => $file_id) {
            $recogUrl = 'http://127.0.0.1:8090/CustomerPlan?fileName=' . $file_id['FileName'] . '&area=' . $data['area'] . '&keywords=["materiale","durezza","rivestimenti"]' . '&rotate=' . $file_id['rotate'];
            // var_dump($recogUrl);

            $result = $home->http_response($recogUrl);
            // var_dump($result);
            $result = json_decode($result, true);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
    }
    function getTrainDetail($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);

        // $customer ='';
        // var_dump($data['customer']);
    
        $customer = implode(",",$data['customer']);
        $itemno = implode(",",$data['itemno']);
        // var_dump($customer);
        // var_dump($itemno);
        $recogUrl = "http://192.168.2.100:8090/traingdetail?customer={$customer}&itemno={$itemno}";
        $result = $home->http_response($recogUrl);
        $result = json_decode($result, true);
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
            $recogUrl = 'http://127.0.0.1:8090/CNNTextRec?Files=["../uploads/' . $file_id['FileName'] . '"]';
            // $recogUrl = 'http://192.168.2.100:8091/CNNTextRec?Files=["../uploads/0a935a34d370f4e1.jpg"]';
            $result = $home->http_response($recogUrl);
            // var_dump($result);
            $result = json_decode($result, true);
            $material = '';
            foreach ($result as $key => $values) {
                foreach ($values as $key => $value) {
                    $material = $value;
                    break 2;
                }
            }
            $business = new Business($this->container->db);
            $result = $business->getMaterialMatch([$material]);
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


    function getCustomerPlan($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $file_ids = $home->getFileById($data);
        foreach ($file_ids as $key => $file_id) {
            $recogUrl = 'http://127.0.0.1:8090/CustomerPlan?fileName=' . $file_id['FileName'] . '&rotate=' . $file_id['rotate'];
            $result = $home->http_response($recogUrl);
            // var_dump($result);
            $result = json_decode($result, true);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
    }
    function recognizeOrderName($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $recogUrl = 'http://127.0.0.1:8090/CustomerPlan?fileName=' . $data['FileName'] . '&rotate=0';
        $file = $data['FileName'];

        $tmep_file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $file;
        // Load
        $source = imagecreatefromjpeg($tmep_file);
        if (!$source) {
            $source = imagecreatefrompng($tmep_file);
        }
        if (@$data['rotate'] < 0) {
            @$data['rotate'] += 360;
        }
        // Rotate
        $rotate = imagerotate($source, 360 - intval(@$data['rotate']), imagecolorallocate($source, 255, 255, 255));
        if (pathinfo($file, PATHINFO_EXTENSION) === 'jpg' || pathinfo($file, PATHINFO_EXTENSION) === 'jpeg')
            unlink($tmep_file);
        // Output
        imagejpeg($rotate, $tmep_file);

        $results = $home->http_response($recogUrl);
        $results = json_decode($results, true);
        $result = [];
        $result = $home->getMessageHistory(["order_names" => array_map(function ($result) {
            return $result['text'];
        }, array_filter($results, function ($result) {
            return array_key_exists('text', $result);
        }))]);
        foreach ($result as $row) {
            if (array_key_exists('history', $row)) {
                foreach ($row['history'] as $history) {
                    if (array_key_exists('客戶圖號', $history)) {
                        $result = ["text" => $history['客戶圖號']];
                        goto EndFunction;
                    }
                }
            }
        }
        EndFunction:
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getFinishInformation($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $result = [];
        $result['process_mapping'] = $home->getProcessMapping($data['process_mapping']);
        $result['process_result'] = $home->getProcessResult($data['process_result']);
        $result['modify_process'] = $home->getmodifyprocess($data['modify_process']);;
        $result['total'] = $home->getProcessTotal($data['process_mapping']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getUser($request, $response, $args)
    {
        // $_SESSION['id'] = 7;
        $home = new Home($this->container->db);
        $data = ["id" => $_SESSION['id']];
        $result = $home->getUser($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getMessageFileList($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $result = $home->getMessageFileList($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function toggleMessageFileLock($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $home = new Home($this->container->db);
        $result = $home->toggleMessageFileLock($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    /* 
    file_name : string
    */
    public function postMessageFiletoUpload($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $home = new Home($this->container->db);
        $result = $home->checkMessageFile($data);
        $result = $home->copyMessageFile($result);
        $crm = new CRM($this->container->db);
        $result = $crm->decompress_delivery_meet_content_file([
            'delivery_meet_content_file_name' => @$result['file_name'],
            'listAll' => true
        ]);
        $result = $home->concatImagePath($result);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getMessageQuotation($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $result = $home->getMessageQuotation($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getMessageImages($request, $response, $args)
    {
        $data = $args;
        $home = new Home($this->container->db);
        $result = $home->getMessageImages($data);
        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . 'phasegallery.jpg' . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public');
        return $response;
    }
    public function getMessageHistory($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $home = new Home($this->container->db);
        $result = $home->getMessageHistory($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postMessageHistory($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->getFileByFK($data, false);
        foreach ($result as $key => $row) {
            if (array_key_exists('type', $data)) {
                if ($data['type'] === 'old')
                    $row['itemno'] = '002';
                else if ($data['type'] === 'new')
                    $row['itemno'] = '001';
            }
            $result = $home->cloneFile($row);
            if (array_key_exists('id', $result)) {
                $result += $data;
                $home->postfix($result);
                break;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function insertMessageFileByOrderName($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $home = new Home($this->container->db);
        if (array_key_exists('type', $data)) {
            if ($data['type'] === 'old')
                $data['itemno'] = '002';
            else if ($data['type'] === 'new')
                $data['itemno'] = '001';
        }
        $result = $home->insertFileByOrderName($data);
        $home->setProgress($result['file_id'], 1);
        $result += $data;
        $home->postfix($result);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchRotateByFileName($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();

        $file = $this->container->upload_directory . '/' . $data['file_name'];
        // Load
        $source = imagecreatefromjpeg($file);
        if (!$source) {
            $source = imagecreatefrompng($file);
        }
        if ($data['rotate'] < 0) {
            $data['rotate'] += 360;
        }
        // Rotate
        $rotate = imagerotate($source, 360 - intval($data['rotate']), 0);
        unlink($file);
        // Output
        imagejpeg($rotate, $file);
        $result = ["status" => "success"];
        // $result = $home->patchRotate($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getFileByFK($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->getFileByFK($data);
        foreach ($result as $key => $row) {
            $result = $home->cloneFile($row);
            if (array_key_exists('id', $result)) {
                $response = $response->withRedirect("/finish?id={$result['id']}&file_id_dest={$result['id']}", 301);
                break;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getUserDetail($request, $response, $args)
    {
        $params = $request->getQueryParams();
        (!array_key_exists('user_id', $params)) && ($params['user_id'] = null);
        (!array_key_exists('module_id', $params)) && ($params['module_id'] = null);
        $home = new Home($this->container->db);
        $result = $home->readUserDetail($params);
        foreach ($result as $key => $value) {
            $result[$key]['module_id'] = json_decode($value['module_id']);
            $result[$key]['module_name'] = json_decode($value['module_name']);
            $result[$key]['oldpassword'] = $home->readPassword($value['user_id']);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchUserDetail($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $home = new Home($this->container->db);
        $result = [];
        foreach ($params as $key => $value) {
            $value['session_user'] = $_SESSION['id'];
            $updateUserDetail = $home->updateUserDetail($value);
            if (array_key_exists('password', $value) && array_key_exists('password1', $value) && array_key_exists('oldpassword', $value)) {
                $updatePassword = $home->updatePassword($value);
            } else {
                $updatePassword['status'] = 'success';
            }
            if ($updateUserDetail['status'] === 'success' && $updatePassword['status'] === 'success') {
                $deleteUserModal = $home->deleteUserModal($value);
                if ($deleteUserModal['status'] === 'success') {
                    foreach ($value['module_id'] as $key2 => $value2) {
                        $createUserModal = $home->createUserModal($value['user_id'], $value2);
                        if ($createUserModal['status'] === 'success') {
                            $readUserDetailEditorName = $home->readUserDetailEditorName($_SESSION['id']);
                        } else {
                            $result = ['createUserModal' => $createUserModal];
                            goto exceptionError;
                        }
                    }
                } else {
                    $result = ['deleteUserModal' => $deleteUserModal];
                    goto exceptionError;
                }
            } else {
                $result = [
                    'updateUserDetail' => $updateUserDetail, 'updatePassword' => $updatePassword
                ];
                goto exceptionError;
            }
            if ($readUserDetailEditorName['editor']) {
                array_push($result, [
                    'status' => 'success',
                    'editor' => $readUserDetailEditorName['editor']
                ]);
            } else {
                $result = ['readUserDetailEditorName' => $readUserDetailEditorName];
                goto exceptionError;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
        exceptionError:
        $response = $response->withStatus(500);
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteUserDetail($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $home = new Home($this->container->db);
        foreach ($params as $key => $value) {
            $deleteUserDetail = $home->deleteUserDetail($value);
            if ($deleteUserDetail['status'] === 'success') {
                $result = ['status' => 'success'];
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function batchRegister($request, $response, $args)
    {
        $number = 0;
        $tmpsambaSID = '';
        $tmpuidnumber = '';
        $tmpgidnumber = '';


        while (true) {
            $tmpsambaSID = 'S-1-5-21-1286864893-3306830231-2186725024-' . str_pad($number, 4, "0", STR_PAD_LEFT);
            $tmpuidnumber = '100' . str_pad($number, 4, "0", STR_PAD_LEFT);
            $tmpgidnumber = '100' . str_pad($number, 4, "0", STR_PAD_LEFT);
            // var_dump($tmpsambaSID);

            $ldap = $this->container->ldap;
            $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", '(sambasid=' . $tmpsambaSID . ')');
            $sambasidinfo = ldap_get_entries($ldap['conn'], $sr);
            $ldap = $this->container->ldap;
            $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", '(uidnumber=' . $tmpuidnumber . ')');
            $uidnumberinfo = ldap_get_entries($ldap['conn'], $sr);
            $ldap = $this->container->ldap;
            $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", '(gidnumber=' . $tmpgidnumber . ')');
            $gidnumberinfo = ldap_get_entries($ldap['conn'], $sr);

            // var_dump($uidnumberinfo['count'], $gidnumberinfo['count'],$sambasidinfo['count'] );


            if ($number == 100) {
                break;
            } else if ($uidnumberinfo['count'] == 0 && $gidnumberinfo['count'] == 0 && $sambasidinfo['count'] == 0) {
                // var_dump(str_pad($number, 4, "0", STR_PAD_LEFT));
                // var_dump('0');
                break;
            }
            $number++;
        }
        $data = $request->getParsedBody();

        foreach ($data as $key => $value) {
            $uid = $value['uid'];
            $username = $value['user_name'];
            $password = $value['password'];
            $email = @$value['email'];
            $tmpsambaSID = 'S-1-5-21-1286864893-3306830231-2186725024-' . str_pad($number + $key, 4, "0", STR_PAD_LEFT);
            $tmpuidnumber = '100' . str_pad($number + $key, 4, "0", STR_PAD_LEFT);
            $tmpgidnumber = '100' . str_pad($number + $key, 4, "0", STR_PAD_LEFT);


            $ldap = $this->container->ldap;
            $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", '(uid=' . $uid . ')');
            $info = ldap_get_entries($ldap['conn'], $sr);
            // var_dump($info);

            if ($info['count'] !== 0) {
                $data = [
                    'message' => "工號已經被使用",
                    'user_name' => $uid
                ];
                ldap_close($ldap['conn']);
                $response = $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode($data));
                return $response;
            }

            // var_dump($tmpsambaSID );
            // var_dump($tmpuidnumber );
            // var_dump($tmpgidnumber );

            // return $data;

            $info = array();
            $info["objectClass"] = array();
            $info["objectClass"][] = "apple-user";
            $info["objectClass"][] = "extensibleObject";
            $info["objectClass"][] = "inetOrgPerson";
            $info["objectClass"][] = "organizationalPerson";
            $info["objectClass"][] = "person";
            $info["objectClass"][] = "posixAccount";
            $info["objectClass"][] = "sambaIdmapEntry";
            $info["objectClass"][] = "sambaSamAccount";
            $info["objectClass"][] = "shadowAccount";
            $info["objectClass"][] = "top";
            $info["sambasid"] = $tmpsambaSID;
            $info["uid"] = $uid;
            $info["sn"] = $username;
            $info["uidnumber"] = $tmpuidnumber;
            $info["gidnumber"] = $tmpgidnumber;

            $info["homeDirectory"] = "/home/" . $username;
            $info["givenName"] = $username;
            $info["displayName"] = $username;
            $info["cn"] = $username;
            $info["mail"] = $email;
            $info["userPassword"] = "" . $password;
            $info["memberOf"] = "cn=users,cn=groups,dc=mil,dc=com,dc=tw";

            $add = ldap_add($ldap['conn'], "uid=$uid,cn=users,dc=mil,dc=com,dc=tw", $info);

            $home = new Home($this->container->db);
            $data[$key]['editor_id'] = $_SESSION['id'];
            $data[$key]['name'] = $data[$key]['user_name'];
            unset($data[$key]['user_name']);
            $result = $home->addnewUser($data[$key]);
            foreach ($data[$key]['module_id'] as $data_) {
                $home->createUserModal($result[0]['id'], $data_);
            }
        }
        $res = [];
        $res['editor'] = $home->readUserDetailEditorName($_SESSION['id'])['editor'];
        $res['status'] = 'success';
        ldap_close($ldap['conn']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($res);
        return $response;
    }
    public function downloadSample($request, $response, $args)
    {
        $type = $request->getQueryParams()['type'];
        $home = new Home($this->container->db);
        $module_value = $home->getModuleSetting();
        $module = "部門(";
        foreach ($module_value as $key => $value) {
            $index = $key + 1;
            $module .= "{$value['name']}:{$index}";
            if ($key !== array_key_last($module_value)) {
                $module .= ', ';
            }
        }
        $module .= ")";
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('../public/resource/Sample.' . $type);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->getCell('E1')->setValue($module);
        switch ($type) {
            case "xlsx":
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                break;
            case "xls":
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
                break;
            case "csv":
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
                $writer->setUseBOM(true);
                break;
        }
        $writer->save('../public/resource/Sample.' . $type);
        switch ($type) {
            case "xlsx":
                $file = "resource/Sample." . $type;
                if (!file_exists($file)) {
                    $response = $response->withStatus(500);
                    return $response;
                }
                break;
            case "xls":
                $file = "resource/Sample." . $type;
                if (!file_exists($file)) {
                    $response = $response->withStatus(500);
                    return $response;
                }
                break;
            case "csv":
                $file = "resource/Sample." . $type;
                if (!file_exists($file)) {
                    $response = $response->withStatus(500);
                    return $response;
                }
                break;
        }
        header('content-disposition:attachment;filename=Sample.' . $type);    //告訴瀏覽器通過何種方式處理檔案
        header('content-length:' . filesize($file));    //下載檔案的大小
        readfile($file);     //讀取檔案
    }
    function postUploadSpreadsheet($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $uploadedFile = $request->getUploadedFiles()['inputFile'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $fixed_thead = ['A' => 'uid', 'B' => 'user_name', 'C' => 'country', 'D' => 'gender', 'E' => 'module_id', 'F' => 'email', 'G' => 'password'];
            $result = $home->convertSpreadsheetToJson($uploadedFile, $fixed_thead);
        } else {
            $result = [
                'status' => 'failure',
                'error_info' => $uploadedFile->getError()
            ];
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    function get_custom_id($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getQueryParams();
        $result = $home->get_custom_id($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    function patch_custom_id($request, $response, $args)
    {
        $home = new Home($this->container->db);
        $data = $request->getParsedBody();
        $result = $home->patch_custom_id($data);
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
