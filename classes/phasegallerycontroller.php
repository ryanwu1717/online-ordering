<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;

class PhaseGalleryController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }
    public function renderPhaseGallery($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/3DConvert/index.html', []);
    }
    public function renderProcessesAdd($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/3DConvert/processesAdd.html', []);
    }
    public function getProcesses($request, $response, $args)
    {
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->getProcesses();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderProcesses($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $fetch = [];
        $fetch = $phasegallery->getOrderProcesses($data);
        foreach ($fetch as $key => $value) {
            $tmp = $phasegallery->getProcessesLinesOuter($value);
            foreach ($tmp as $tmp_) {
                $fetch[$key] += $tmp_;
            }
        }
        $MD_temp = '';
        $MD_count = -1;
        $result = [];
        foreach ($fetch as $value) {
            if ($MD_temp != $value['MD001']) {
                $line_tmep = [
                    'line_id' => $value['MD001'],
                    'line_name' => $value['line_name'],
                    'processes' => []
                ];
                array_push($result, $line_tmep);
                $MD_temp = $value['MD001'];
                $MD_count += 1;
            }
            $processes_temp = [
                'order_processes_id' => $value['order_processes_id'],
                'processes_index' => $value['order_processes_index'],
                'processes_id' => $value['MW001'],
                'processes_name' => $value['processes_name'],
                'note' => $value['note']
            ];
            array_push($result[$MD_count]['processes'], $processes_temp);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderProcessesSeries($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $order_processes_id_list = '';
        foreach ($data['order_processes_id'] as $key => $value) {
            $order_processes_id_list .= ":order_processes_id_{$key}";
            if ($key !== array_key_last($data['order_processes_id'])) {
                $order_processes_id_list .= ', ';
            }
        }
        $data['order_processes_id_list'] = $order_processes_id_list;
        $fetch = $phasegallery->getOrderProcessesSeries($data);
        // for ($i = 0; $i < count($fetch); $i++) {
        //     for ($j = 0; $j < count((array)$fetch[$i]['order_processes_reprocesses']); $j++) {
        //         $fetch_subfile = $phasegallery->getReprocessSubfile($fetch[$i]);
        //         for ($k = 0; $k < count($fetch_subfile); $k++) {
        //             $subfile_position_point = $phasegallery->readSubfilePositionPoint($fetch_subfile[$k]);
        //             $fetch_position = $this->getPointList($subfile_position_point);
        //             foreach ($fetch_position as $key => $value) {
        //                 foreach ($value['point_list'] as $key2 => $value2) {
        //                     $idx = $key2 + 1;
        //                     $fetch_position[$key]["point_{$idx}_x"] = $value2[0];
        //                     $fetch_position[$key]["point_{$idx}_y"] = $value2[1];
        //                 }
        //             }
        //             $fetch_subfile[$k]['order_processes_reprocess_data_array'] = $fetch_position;
        //         }
        //         $fetch[$i]['order_processes_reprocesses'][$j]['order_processes_reprocess_file'] = $fetch_subfile;
        //     }
        // }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($fetch);
        return $response;
    }
    public function getOrderProcessesReprocessSubfile($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $subfile_position_point = $phasegallery->readSubfilePositionPoint($params);
        $result = $this->getPointList($subfile_position_point);
        foreach ($result as $key => $value) {
            foreach ($value['point_list'] as $key2 => $value2) {
                $idx = $key2 + 1;
                $result[$key]["point_{$idx}_x"] = $value2[0];
                $result[$key]["point_{$idx}_y"] = $value2[1];
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    // public function getOrderProcessesReprocessHistory($request, $response, $args)
    // {
    //     $data = $request->getQueryParams();
    //     $phasegallery = new PhaseGallery($this->container->db);
    //     $result = $phasegallery->getOrderProcessesReprocessHistory($data);
    //     $response = $response->withHeader('Content-type', 'application/json');
    //     $response = $response->withJson($result);
    //     return $response;
    // }
    public function insertOrderProcesses($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->insertOrderProcesses($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function uploadOrderImage($request, $response, $args)
    {
        $data = $request->getParams();
        $data['files'] = $request->getUploadedFiles();
        $phasegallery = new PhaseGallery($this->container->db);
        $file = $phasegallery->uploadFile($data);
        unset($data['files']);
        $file['user_id'] = 0;
        $file_id = $phasegallery->insertFile($file);
        $data['file_id'] = $file_id;
        $result = $phasegallery->insertOrderImage($data);
        $result += ["src" => "/3DConvert/PhaseGallery/order_image/$file_id"];
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function uploadReprocessImage($request, $response, $args)
    {
        $data = $request->getParams();
        $data['files'] = $request->getUploadedFiles();
        $phasegallery = new PhaseGallery($this->container->db);
        $file = $phasegallery->uploadFile($data);
        unset($data['files']);
        $file['user_id'] = 0;
        $data['file_id'] = $phasegallery->insertFile($file);
        $result['file_id'] = $data['file_id'];
        $result['src'] = "/3DConvert/PhaseGallery/order_processes/reprocess_image/" . $data['file_id'];
        $result['order_processes_file_id'] = $phasegallery->insertOrderProcessesFile($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteReprocessImage($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->deleteOrderProcessesFile($data); //Delete the reprocess image with the order_processes_file_ids.
        if ($result['status'] === "failed") {
            $response = $response->withStatus(500);
            return $response;
        }

        $file_name = $phasegallery->get_phasegallery_file_name($result['file_id']); //Get phasegallery file_names for deleting existing files.
        $delete_phasegallery_file = $phasegallery->delete_phasegallery_file($file_name);

        foreach ($file_name as $key => $value) {
            $file = $this->container->upload_directory . '/' . "{$value['file_name']}";
            if (!file_exists($file)) {
                $response = $response->withStatus(500);
                return $response;
            }
            unlink($file);
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($delete_phasegallery_file);
        return $response;
    }
    public function getOrder($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->getOrder($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getImage($request, $response, $args)
    {
        $data = $args;
        $phasegallery = new PhaseGallery($this->container->db);
        $file = $phasegallery->getImage($data);
        if (!file_exists($file)) {
            $response = $response->withStatus(500);
            return $response;
        }
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'pdf':
                header('content-disposition:attachment;filename=file.' . $extension);    //告訴瀏覽器通過何種方式處理檔案
                header('content-length:' . filesize($file));    //下載檔案的大小
                readfile($file);     //讀取檔案
                break;
            default:
                $exif = @exif_read_data($file);
                $source = $this->compressImage($file, $file, 100);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $source = imagerotate($source, 180, 0);
                            break;

                        case 6:
                            $source = imagerotate($source, 90, 0);
                            break;

                        case 8:
                            $source = imagerotate($source, -90, 0);
                            break;
                    }
                }

                imagejpeg($source);
                $response = $response->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Disposition', 'attachment;filename="' . 'phasegallery.jpg' . '"')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate')
                    ->withHeader('Pragma', 'public');
                return $response;
        }
    }

    function compressImage($source = false, $destination = false, $quality = 80, $filters = false)
    {
        $info = getimagesize($source);
        switch ($info['mime']) {
            case 'image/jpeg':
                /* Quality: integer 0 - 100 */
                if (!is_int($quality) or $quality < 0 or $quality > 100) $quality = 80;
                return imagecreatefromjpeg($source);

            case 'image/gif':
                return imagecreatefromgif($source);

            case 'image/png':
                /* Quality: Compression integer 0(none) - 9(max) */
                if (!is_int($quality) or $quality < 0 or $quality > 9) $quality = 6;
                return imagecreatefrompng($source);

            case 'image/webp':
                /* Quality: Compression 0(lowest) - 100(highest) */
                if (!is_int($quality) or $quality < 0 or $quality > 100) $quality = 80;
                return imagecreatefromwebp($source);

            case 'image/bmp':
                /* Quality: Boolean for compression */
                if (!is_bool($quality)) $quality = true;
                return imagecreatefrombmp($source);

            default:
                return;
        }
    }
    function insertProcesses($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->insertProcesses($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getReprocessPosition($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->readReprocessPosition($params);
        $result = $this->getPointList($result);
        for ($i = 0; $i < count($result); $i++) {
            $point_count = 1;
            foreach ($result[$i]['point_list'] as $point_list) {
                $result[$i]["point_{$point_count}_x"] = $point_list[0];
                $result[$i]["point_{$point_count}_y"] = $point_list[1];
                $point_count++;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postReprocessPosition($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $pg = new PhaseGallery($this->container->db);
        $result = $pg->postReprocessPosition($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchReprocessPosition($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        foreach ($params as $params_) {
            $result = $phasegallery->updateReprocessPosition($params_);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteReprocessPosition($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->deleteReprocessPosition($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getPointList($request_data)
    {
        $result = [];
        foreach ($request_data as $key => $value) {
            $request_keys = array_keys($value);
            if (!isset($result[$value['position_id']])) {   /* bind position_id as group */
                $result[$value['position_id']]['point_list'] = [];  /* init & push point_list */
                $point = [$value['x'], $value['y']];
                array_push($result[$value['position_id']]['point_list'], $point);
                foreach ($request_keys as $key2 => $value2) {  /* clear used request_keys */
                    if (array_search('position_id', $request_keys)) {
                        unset($request_keys[array_search('position_id', $request_keys)]);
                    };
                    if (array_search('x', $request_keys)) {
                        unset($request_keys[array_search('x', $request_keys)]);
                    };
                    if (array_search('y', $request_keys)) {
                        unset($request_keys[array_search('y', $request_keys)]);
                    };
                }
                foreach ($request_keys as $key3 => $value3) {  /* duplicate unused values */
                    $result[$value['position_id']][$value3] = $value[$value3];
                }
            } else {  /* push point_list */
                $point = [$value['x'], $value['y']];
                array_push($result[$value['position_id']]['point_list'], $point);
            }
        }
        $result = array_values($result);
        return $result;
    }
    public function deleteSubfileImage($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = [];
        $reference = $phasegallery->readSubfileImageReference($params);
        // if ($phasegallery->deletePoint($reference['position_id'])['status'] === 'success') {
        //     if ($phasegallery->deletePosition($reference['position_id'])['status'] === 'success') {
        //         if ($phasegallery->deleteSubfileImageFile($reference['file_id'])['status'] === 'success') {
                    if ($phasegallery->deleteSubfileImagePosition($params)['status'] === 'success') {
                        $result = $phasegallery->deleteSubfileImage($params);
                    }
        //         }
        //     }
        // }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postSubfileImage($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $pg = new PhaseGallery($this->container->db);
        $result = $pg->insertSubfileImage($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function uploadSubfileImage($request, $response, $args)
    {
        $data = $request->getParams();
        $data['files'] = $request->getUploadedFiles();
        $phasegallery = new PhaseGallery($this->container->db);
        $file = $phasegallery->uploadFile($data);
        unset($data['files']);
        $file['user_id'] = 0;

        $data['file_id'] = $phasegallery->insertFile($file);
        $result['file_id'] = $data['file_id'];
        $result['src'] = "/3DConvert/PhaseGallery/order_processes/reprocess_image/" . $data['file_id'];
        $result['order_processes_subfile_id'] = $phasegallery->insertSubfileImage($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function printOrderLabel($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $oPG = new PhaseGallery($this->container->db);
        $outer = $oPG->getOrder($data);
        foreach ($outer['data'] as $outer_) {
            foreach ($outer_ as $outer_in) {
                $data['order_id'] = $oPG->getOrder_FK($outer_in);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($data);
        return $response;
    }
    public function getFile($request, $response, $args)
    {
        $phasegallery = new PhaseGallery($this->container->db);
        $filename = $phasegallery->getImage($args);
        header('content-disposition:attachment;filename=' . $filename);
        header('content-length:' . filesize($filename));
    }
    public function getCategoryProcesses($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $phaseGallery = new PhaseGallery($this->container->db);
        (!array_key_exists('material', $params)) && ($params['material'] = '');
        (!array_key_exists('ti', $params)) && ($params['ti'] = '');
        (!array_key_exists('processes_id', $params)) && ($params['processes_id'] = []);
        $result = $phaseGallery->readCategoryProcesses($params);
        foreach ($result['data'] as $key => $value) {
            foreach ($value as $key1 => $value1) {
                if ($phaseGallery->isJson($value1)) {
                    $result['data'][$key][$key1] = json_decode($value1, true);
                }
                if ($key1 == 'file_id') {
                    $result['data'][$key]['file_id'] = explode(', ', $result['data'][$key]['file_id']);
                }
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchOrderProcessesSubfile($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        foreach ($params as $params_) {
            $result = $phasegallery->updateOrderProcessesSubfile($params_);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getSubfileImage($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->getSubfileImage($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postProcessesFk($request, $response, $args)
    {
        $pg = new PhaseGallery($this->container->db);
        $data = $pg->getProcesses();
        foreach ($data as $key => $value) {
            $result = $pg->insertProcessesFk($data[$key]);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function delete_order_image($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->delete_order_image($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_order_image($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->get_order_image($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderFileId($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->getOrderFileId($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteOrderFileId($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        foreach ($data as $key => $value) {
            $result = $phasegallery->deleteCoptdFile($value);
            if ($result['status'] === "failed") {
                $response = $response->withStatus(500);
                $response = $response->withHeader('Content-type', 'application/json');
                $response = $response->withJson($result);
                return $response;
            }
            $file_name = $phasegallery->get_phasegallery_file_name($result['file_id']);
            $delete_phasegallery_file = $phasegallery->delete_phasegallery_file($file_name);

            foreach ($file_name as $key => $value) {
                $file = $this->container->upload_directory . '/' . "{$value['file_name']}";
                if (!file_exists($file)) {
                    $response = $response->withStatus(500);
                    return $response;
                }
                unlink($file);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($delete_phasegallery_file);
        return $response;
    }
    public function convertDWGtoJPG($request, $response, $args)
    {
        $data = $request->getParams();
        $data['files'] = $request->getUploadedFiles();
        $phasegallery = new PhaseGallery($this->container->db);
        $file = $phasegallery->uploadFile($data);
        $result = $phasegallery->convertDWGtoJPG($file['file_name']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postProcessesGroup($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        
        $result = $phasegallery->addProcessesGroup($data);
        // foreach ($data['group'] as $key => $value) {
        //     $value['order_id'] = $data['order_id'];
        //     $value['file_id'] = $data['file_id'];
        //     $result = $phasegallery->insertProcessesGroup($value);
        //     $data['group'][$key]['processes_group_id'] = $result['processes_group_id'];
        //     foreach ($value['processes'] as $key_ => $value_) {
        //         $member = [];
        //         $member['processes_group_id'] = $result['processes_group_id'];
        //         $member['processes_fk_value'] = $value_;
        //         $phasegallery->insertProcessesGroupMember($member);
        //     }
        // }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getProcessesGroup($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->selectProcessesGroup($data);
        foreach ($result as $key => $value) {
            // unset($result[$key]['processes_group_id']);
            $result[$key]['processes'] = json_decode($value['processes']);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function exportPDF($request, $response, $args)
    {
        $filename = "" . date("Y-m-d") . ".pdf";
        $tcpdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $tcpdf->setPrintHeader(false);  /* hide default header / footer */
        $tcpdf->setPrintFooter(false);
        $tcpdf->AddPage();  /* new page */
        $tcpdf->Ln();
        $tcpdf->SetFont('msungstdlight', '', 12);  /* table */
        $pdf_table = <<<EOD
        <table cellpadding="2" align="left" vertical-align="middle">
            
            <tr>
                <td width="3%" border="1">工作註記</td>
                <td width="97%" border="1"></td>
            </tr>
            <tr>
                <td width="100%" height="325" border="1"><br></td>
            </tr>
            <tr>
                <td width="60%" border="1">製程項目 : 線別A-切料</td>
                <td width="6%" rowspan="2" border="1" align="center"><img src="../uploads/mil.png" width="35"></td>
                <td width="34%" rowspan="2" border="1" align="center" style="font-size:18px;">MAJOR&emsp;INDUSTRIES&emsp;LTD.</td>
            </tr>
            <tr>
                <td width="16%" border="1" align="center">請執行首件及抽樣檢查</td>
                <td width="20%" border="1" align="center">同心度,垂直度0.02mm以內</td>
                <td width="24%" border="1">規格 :</td>
            </tr>
            <tr>
                <td width="36%" rowspan="4" border="1" align="center">特別註記</td>
                <td width="12%" border="1">材質 :</td>
                <td width="12%" border="1">硬度 :</td>
                <td width="20%" border="1">客戶版次 :</td>
                <td width="20%" border="1">編號 :</td>
            </tr>
            <tr>
                <td width="24%" rowspan="3" border="1">本注記處 : 公差▽▽▽</td>
                <td width="20%" border="1">客戶代號 :</td>
                <td width="20%" border="1">客戶圖號 :</td>
            </tr>
            <tr>
                <td width="20%" border="1">繪製者 :</td>
                <td width="20%" border="1">DRAW TYPE : 0000/00/00</td>
            </tr>
            <tr>
                <td width="23%" border="1">品號 :</td>
                <td width="17%" border="1">版次 :</td>
            </tr>
           
        </table>
        EOD;
        $tcpdf->writeHTML($pdf_table, true, false, false, false, '');
        $result = $tcpdf->Output('complaint.pdf', 'I');  /* export file */
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Disposition: attachment;filename="' . $filename . '";');
        header('Content-Type: application/csv; charset=UTF-8');
        return $result;
    }
    public function patch_processes_group_frame($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->patch_processes_group_frame($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_processes_group_files($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $data += $args;
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->get_processes_group_file($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function delete_processes_group_file($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $data += $args;
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->delete_processes_group_file($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_processes_group_file($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $data += $args;
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->get_processes_group_file($data);
        $data = [
            'request'=>$request,
            'result' => $result,
            'response'=>$response
        ];
        $response = $phasegallery->render_png($data);
        return $response;
    }
    public function upload_processes_group_file($request, $response, $args)
    {
        $data = $request->getParams();
        $data['files'] = $request->getUploadedFiles();
        $phasegallery = new PhaseGallery($this->container->db);
        $file = $phasegallery->uploadFile($data);
        unset($data['files']);
        $file += $data;
        $result = $phasegallery->upload_processes_group_file($file);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_processes_group_frame($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result['frame'] = $phasegallery->get_processes_group_frame($data);
        $result['paint'] = $phasegallery->get_processes_group_paint($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function upload_processes_group_paint($request, $response, $args)
    {
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();
		$crm = new CRM($this->container->db);
		$phasegallery = new PhaseGallery($this->container->db);
		foreach($data['files'] as $file){
			$file = $phasegallery->uploadFile(["files"=>["inputFile"=>$file]]);
			unset($data['files']);
			$file['user_id'] = 0;
			$data['file_id'] = $phasegallery->insertFile($file);
			$result = $phasegallery->upload_processes_group_paint($data);
		}
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
    }
    public function delete_processes_group_paint($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->delete_processes_group_paint($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_processes_group_paint($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->get_processes_group_paint($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_order_process_list($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->get_order_process_list($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_line_process($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getRFIDProcessNmaes();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function post_order_process_list($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $phasegallery = new PhaseGallery($this->container->db);
        $result = $phasegallery->post_order_process_list($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getallProcess($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new Business($this->container->db);
        $result = $business->getallProcess($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}