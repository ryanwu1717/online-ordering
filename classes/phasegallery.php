<?php

use \Psr\Container\ContainerInterface;
use Slim\Http\UploadedFile;

use function Complex\ln;

class PhaseGallery
{
    protected $container;
    protected $db;


    // constructor receives container instance
    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->db = $container->db;
    }

    public function updateProcessesGroup($data){
        $values  = [
            "processes_group_id" => 0,
            "drawing_help" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }
        $sql = "UPDATE phasegallery.processes_group
        SET  drawing_help=:drawing_help
        WHERE processes_group_id= :processes_group_id;";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return $data;
        }else{
            $result = [
                "status" => "failed",
            ];
        }

    }
    public function insertProcessesGroupnewid($data){
        // var_dump($data);
        $values  = [
            "drawing_help" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }
        $sql="INSERT INTO phasegallery.processes_group(
             drawing_help)
            VALUES (:drawing_help)
            RETURNING processes_group_id;";
         $stmt = $this->db->prepare($sql);
         if ($stmt->execute($values)) {
            // $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data['processes_group_id'] = $this->db->lastInsertId();
            return $data;
         }else{
             $result = [
                 "status" => "failed",
             ];
         }
    }

    public function patchprocesses_group_member($data){
        $processes_group_member = join(',',array_fill(0,count($data['order_process_list']),'?'));
        $tmpstr = "({$processes_group_member})";
        $stmt_array = $data['order_process_list'];
        // var_dump($processes_group_member);
        $sql = "DELETE FROM  phasegallery.processes_group_member
        WHERE order_process_id IN {$tmpstr}
        RETURNING order_process_id";
        $stmt = $this->db->prepare($sql);
            
        if(!$stmt->execute($stmt_array)){
            var_dump($stmt->errorInfo());
        }


        $tmpStr = '';
        foreach ($data['order_process_list'] as $key => $value) {
            $tmpStr .= "({$data['processes_group_id']},{$value}),";
        }
        $tmpStr = substr_replace($tmpStr, "", -1);
        $sql = "INSERT INTO phasegallery.processes_group_member(
            processes_group_id, order_process_id)
            VALUES {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute()){
            var_dump($stmt->errorInfo());
        }
        // var_dump($sql);
        return; 
        
    }

    public function addProcessesGroup($data){
        // array_key_exists('array', $data)
        // var_dump($data[0]);

        foreach($data['group'] AS $key => $value){
            // var_dump($value);

            if(array_key_exists('processes_group_id', $value) && !is_null($value['processes_group_id'])){
                // var_dump('in1');
                $tmppg_id = $this -> updateProcessesGroup($value);

            }else{
                $tmppg_id = $this -> insertProcessesGroupnewid($value);

            }
            $data['group'][$key] = $tmppg_id;
            $this -> patchprocesses_group_member($tmppg_id);
            // var_dump($tmppg_id);

        }
        
        return $data;

    }
    public function getProcesses($data = null)
    {
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT [MW001] process_id
                        ,[MW002] process_name
                    FROM [MIL].[dbo].[CMSMW];
                "]
            )
        );
        // -- WHERE '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) = {$data['order_name']}

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }
    public function getOrder($data = null)
    {
        $values = [
            'date_begin' => date("Ymd"),
            'date_end' => date("Ymd"),
            'coptd_td001' => null,
            'coptd_td002' => null,
            'coptd_td003' => null,
            'coptc_tc003' => null,
            'cur_page' => 1,
            'size' => 10,
            'row_size' => 5,
            'have_image' => null
        ];
        foreach ($data as $key => $value) {
            $values[$key] = $value;
            if ($key == 'date_begin' || $key == 'date_end') {
                $values[$key] = str_replace('-', '', $value);
            }
        }

        $length = $values['size'] * $values['cur_page'];
        $start = $length - $values['size'];

        $WHERE = "";
        if (!is_null($values['coptd_td001'])) {
            $WHERE .= " AND COPTD.TD001 = '{$values['coptd_td001']}'";
        }
        if (!is_null($values['coptd_td002'])) {
            $WHERE .= " AND COPTD.TD002 = '{$values['coptd_td002']}'";
        }
        if (!is_null($values['coptd_td003'])) {
            $WHERE .= " AND COPTD.TD003 = '{$values['coptd_td003']}'";
        }
        if (!is_null($values['coptc_tc003'])) {
            $WHERE .= " AND COPTC.TC004 = '{$values['coptc_tc003']}'";
        }

        $image_condition = "WHERE (file_id IS NOT NULL OR file_id IS NULL)";
        $condition = "";
        $end = $start + 10;
        if (!is_null($values['have_image'])) {
            $image_condition = " WHERE file_id IS NOT NULL";
            $sql = "SELECT \"order\".order_fk->>'coptd_td001' coptd_td001, \"order\".order_fk->>'coptd_td002'coptd_td002, \"order\".order_fk->>'coptd_td003' coptd_td003
                    FROM phasegallery.order_process_list 
                    LEFT JOIN phasegallery.order ON \"order\".order_id = order_process_list.order_id
                    {$image_condition} {$WHERE} 
                    GROUP BY coptd_td001,coptd_td002,coptd_td003
                    ORDER BY coptd_td001 ASC, coptd_td002 ASC, coptd_td003 ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($values['have_image'] == 1) {
                $condition = " AND (";
            } else {
                $condition = " AND NOT(";
            }
            foreach ($result['data'] as $key => $value) {  /* assemble category */
                $condition .= "(COPTD.TD001 = '{$value['coptd_td001']}' ";
                $condition .= "AND COPTD.TD002 = '{$value['coptd_td002']}' ";
                $condition .= "AND COPTD.TD003 = '{$value['coptd_td003']}' )";
                if ($key !== array_key_last($result['data'])) {
                    $condition .= ' OR ';
                }
            }
            $condition .= ")";
        }


        $sql = "SELECT * FROM (   
                    SELECT COPTD.TD001 coptd_td001,COPTD.TD002 coptd_td002,COPTD.TD003 coptd_td003,COPTC.TC004 coptc_tc003,[COPTD].[TD001]+'-'+[COPTD].[TD002]+'-'+[COPTD].[TD003] order_serial,[COPTD].[TD201] order_name,[COPTD].[TD004] itemno, ROW_NUMBER() OVER (ORDER BY COPTC.TC004 ASC) AS \"RowNum\"
                    FROM MIL.dbo.COPTC
                    LEFT JOIN MIL.dbo.COPTD ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
                    WHERE COPTC.TC003 BETWEEN '{$values['date_begin']}' AND '{$values['date_end']}' {$condition}
                ) dt
                WHERE dt.\"RowNum\" > {$start} AND dt.\"RowNum\" <= {$end}
                ORDER BY coptc_tc003 ASC 
        ";
        /* 
            {$length}
            WHERE dt.RowNum > {$start}
        */
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => $sql]
            )
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = [];
        $result['data'] = $head;
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $is_have_image = "";
        if (!is_null($values['have_image'])) {
            if ($values['have_image'] == 1)
                $is_have_image .= " WHERE coptd_file.file_id IS NOT NULL";
            else
                $is_have_image .= " WHERE coptd_file.file_id IS NULL";
        }
        $sql = "SELECT DISTINCT dt.order_serial,dt.coptc_tc003,dt.order_name,dt.itemno,dt.coptd_td001,dt.coptd_td002,dt.coptd_td003,dt.src src,dt.order_id,dt.file_exists
            FROM(
                SELECT dt.coptd_td001,dt.coptd_td002,dt.coptd_td003,dt.coptc_tc003,dt.order_name,dt.itemno,dt.order_serial,'/3DConvert/PhaseGallery/order_image/' || MIN(COALESCE(coptd_file.file_id,0)) src,coptd_file.order_id order_id,MIN(coptd_file.file_id) IS NOT NULL AS file_exists, ROW_NUMBER() OVER (ORDER BY dt.coptc_tc003 ASC) AS \"RowNum\"
                FROM json_to_recordset(
                    '{$result['data']}'
                ) as dt(coptd_td001 text,coptd_td002 text,coptd_td003 text,coptc_tc003 text,order_name text,itemno text,order_serial text)
                LEFT JOIN (
                    SELECT coptd_file.file_id,coptd_file.order_id,coptd_file.coptd_td001,coptd_file.coptd_td002,coptd_file.coptd_td003
                    FROM(
                        SELECT order_process_list.file_id,order_process_list.order_id,\"order\".order_fk->>'coptd_td001' coptd_td001,\"order\".order_fk->>'coptd_td002' coptd_td002,\"order\".order_fk->>'coptd_td003' coptd_td003,ROW_NUMBER() OVER (PARTITION BY \"order\".order_id ORDER BY \"order\".order_id ASC) row_num
                        FROM phasegallery.order_process_list
                        LEFT JOIN phasegallery.order ON \"order\".order_id = order_process_list.order_id
                    )coptd_file
                    WHERE coptd_file.row_num = 1
                )coptd_file ON dt.coptd_td001 = coptd_file.coptd_td001
                    AND dt.coptd_td002 = coptd_file.coptd_td002
                    AND dt.coptd_td003 = coptd_file.coptd_td003
                {$is_have_image}
                GROUP BY dt.coptd_td001,dt.coptd_td002,dt.coptd_td003,dt.coptc_tc003,dt.order_name,dt.itemno,dt.order_serial,coptd_file.order_id
            )dt 
            ORDER BY dt.order_id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $revserse = [];
        $revserse_temp = [];
        foreach ($result['data'] as $key => $value) {
            array_push($revserse_temp, $value);
            if ($key !== 0 && ($key + 1) % $values['row_size'] == 0) {
                array_push($revserse, $revserse_temp);
                $revserse_temp = [];
            }
            if ($key === count($result['data']) - 1 && count($revserse_temp) !== 0) {
                for ($i = 0; $i < $values['row_size']; $i++) {
                    if (!array_key_exists($i, $revserse_temp)) {
                        $revserse_temp[$i] = [];
                    }
                }
                array_push($revserse, $revserse_temp);
            }
        }
        $result['data'] = $revserse;

        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT COUNT(*) total
            FROM MIL.dbo.COPTC
            LEFT JOIN MIL.dbo.COPTD ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
            WHERE COPTC.TC003 BETWEEN '{$values['date_begin']}' AND '{$values['date_end']}' {$condition}
        "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result['total'] = 0;
        foreach (json_decode($head, true) as $row) {
            foreach ($row as $value) {
                $result['total'] = $value;
            }
        }
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $result;
    }

    public function uploadFile($data)
    {
        $uploadedFiles = $data['files'];
        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['inputFile'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->moveUploadedFile($this->container->upload_directory, $uploadedFile);
            $result = array(
                'status' => 'success',
                'file_name' => $filename,
                'file_client_name' => $uploadedFile->getClientFilename()
            );
        } else {
            $result = array(
                'status' => 'failed'
            );
        }
        return $result;
    }

    public function insertFile($data)
    {
        $sql = "INSERT INTO phasegallery.file(
            user_id, file_name, file_client_name)
            VALUES (:user_id, :file_name, :file_client_name);
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':file_name', $data['file_name'], PDO::PARAM_STR);
        $stmt->bindParam(':file_client_name', $data['file_client_name'], PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_STR);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function insertOrderImage($data)
    {
        $values = [
            "coptd_td001"=>null,
            "coptd_td002"=>null,
            "coptd_td003"=>null,
            "order_id"   =>null
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key] = $data[$key];
        }
        $sql = "INSERT INTO phasegallery.order (order_fk)
            VALUES(:order_fk)
            ON CONFLICT(order_fk)
            DO UPDATE SET order_fk = EXCLUDED.order_fk
            RETURNING order_id;
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute(["order_fk"=>json_encode(array_filter($values,function($value,$key){return $key!=='order_id';},ARRAY_FILTER_USE_BOTH))])){
            return [
                "status"=>"failure",
            ];
        }
        $values = [
            "file_id" => 0,
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key] = $data[$key];
        }
        $values["order_id"] = $stmt->fetchColumn(0);
        $sql = "INSERT INTO phasegallery.order_process_list(order_id, file_id)
            VALUES (:order_id, :file_id)
            RETURNING order_process_list_id;
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return [
                "status"=>"failure",
            ];
        }
        return array_merge([
            "status" => "success",
            "order_process_list_id" => $stmt->fetchColumn(0)
        ],$values);
        // $sql = "INSERT INTO phasegallery.coptd_file(
        //         coptd_td001, coptd_td002, coptd_td003, file_id)
        //     VALUES (:coptd_td001, :coptd_td002, :coptd_td003, :file_id)
        //     ON CONFLICT (coptd_td001, coptd_td002, coptd_td003, file_id)
        //     DO NOTHING;
        // ";
        // $stmt = $this->db->prepare($sql);
        // $stmt->bindParam(':coptd_td001', $data['coptd_td001'], PDO::PARAM_STR);
        // $stmt->bindParam(':coptd_td002', $data['coptd_td002'], PDO::PARAM_STR);
        // $stmt->bindParam(':coptd_td003', $data['coptd_td003'], PDO::PARAM_STR);
        // $stmt->bindParam(':file_id', $data['file_id'], PDO::PARAM_INT);
        // if ($stmt->execute()) {
        //     return [
        //         "status" => "success"
        //     ];
        // } else {
        //     return [
        //         "status" => "failed"
        //     ];
        // }
    }

    //Delete the reprocess image with the order_processes_file_ids.
    public function deleteOrderProcessesFile($data)
    {
        $values = [];
        $value = [
            'order_processes_file_id' => []
        ];
        foreach ($data as $key => $row) {
            if ($key === 'data') {
                $value['order_processes_file_id'] = array_map(function ($row_value) {
                    if (array_key_exists('order_processes_file_id', $row_value)) {
                        return $row_value['order_processes_file_id'];
                    }
                }, $row);
                $values[] = $value;
            }
        }

        $stmt_string = "";
        $stmt_array = [];
        foreach ($values as $index => $value) {
            if ($stmt_string === "") {
                $stmt_string .= "WHERE ";
            } else {
                $stmt_string .= "OR ";
            }

            $implode_string =
                implode(",", array_map(
                    function ($index, $order_processes_file_id) {
                        return ":order_processes_file_id_{$index}_{$order_processes_file_id}";
                    },
                    array_fill(0, count($value['order_processes_file_id']), $index),
                    array_keys($value['order_processes_file_id'])
                ));
            $stmt_string .= " order_processes_file_id IN ({$implode_string})";

            foreach ($value['order_processes_file_id'] as $order_processes_file_index => $order_processes_file) {
                $stmt_array["order_processes_file_id_{$index}_{$order_processes_file_index}"] = $order_processes_file;
            }
        }

        $sql = "DELETE FROM phasegallery.order_processes_file
                {$stmt_string}
                RETURNING file_id";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($stmt_array)) {
            $result = array(
                'file_id' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'status' => "success"
            );
        } else {
            $result = ["status" => "failed",];
        }
        return $result;
    }

    //Get phasegallery file_names for deleting existing files.
    public function get_phasegallery_file_name($data)
    {
        $values = [];
        $value = [
            'file_id' => []
        ];

        $value['file_id'] = array_map(function ($row_value) {
            if (array_key_exists('file_id', $row_value)) {
                return $row_value['file_id'];
            }
        }, $data);
        $values[] = $value;

        $stmt_string = "";
        $stmt_array = [];
        foreach ($values as $index => $value) {
            if ($stmt_string === "") {
                $stmt_string .= "WHERE ";
            } else {
                $stmt_string .= "OR ";
            }

            $implode_string =
                implode(",", array_map(function ($index, $file_id) {
                    return ":file_id_{$index}_{$file_id}";
                }, array_fill(0, count($value['file_id']), $index), array_keys($value['file_id'])));
            $stmt_string .= " phasegallery.file.file_id IN ({$implode_string})";

            foreach ($value['file_id'] as $file_index => $file) {
                $stmt_array["file_id_{$index}_{$file_index}"] = $file;
            }
        }

        $sql = "SELECT phasegallery.file.file_id, phasegallery.file.file_name
                FROM phasegallery.file
                {$stmt_string}";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($stmt_array)) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = ["status" => "failed",];
        }
        return $result;
    }

    //Delete the reprocess image with the order_processes_file_ids.
    public function delete_phasegallery_file($data)
    {
        $values = [];
        $value = [
            'file_id' => []
        ];

        $value['file_id'] = array_map(function ($row_value) {
            if (array_key_exists('file_id', $row_value)) {
                return $row_value['file_id'];
            }
        }, $data);
        $values[] = $value;

        $stmt_string = "";
        $stmt_array = [];
        foreach ($values as $index => $value) {
            if ($stmt_string === "") {
                $stmt_string .= "WHERE ";
            } else {
                $stmt_string .= "OR ";
            }

            $implode_string =
                implode(",", array_map(
                    function ($index, $file_id) {
                        return ":file_id_{$index}_{$file_id}";
                    },
                    array_fill(0, count($value['file_id']), $index),
                    array_keys($value['file_id'])
                ));
            $stmt_string .= " file_id IN ({$implode_string})";

            foreach ($value['file_id'] as $file_index => $file) {
                $stmt_array["file_id_{$index}_{$file_index}"] = $file;
            }
        }

        $sql = "DELETE FROM phasegallery.file
                {$stmt_string}";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($stmt_array)) {
            $result = ['status' => "success"];
        } else {
            $result = ["status" => "failed",];
        }
        return $result;
    }

    private function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    public function getImage($data)
    {
        $sql = "SELECT file_name
            FROM phasegallery.file
            WHERE file_id = :file_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($files as $file) {
            return $this->container->upload_directory . '/' . $file['file_name'];
        }
    }
    public function insertOrderProcesses($data)
    {
        $values = [
            "order_processes_id" => 0,
            "order_processes_note" => null,
            "file_id" => 0
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $sql = "INSERT INTO phasegallery.order_processes_file (order_processes_id,order_processes_note,file_id)
        VALUES (:order_processes_id,:order_processes_note,:file_id)
        ON CONFLICT (file_id,order_processes_id)
        DO UPDATE SET order_processes_note = excluded.order_processes_note
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        if ($stmt->rowCount() == 1) {
            return [
                "status" => "success"
            ];
        } else {
            return [
                "status" => "failed"
            ];
        }
    }
    public function getOrderProcesses($data)
    {
        /* 
        $data = {
            order_id : 1
        }
         */
        $values = [
            "order_id" => 1,
            "file_id" => 1,
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $sql = "SELECT order_processes.order_processes_id, order_processes.order_processes_index, processes_fk.processes_fk_value \"MW001\", note
            FROM public.order_processes
            LEFT JOIN public.processes ON processes.processes_id = order_processes.processes_id
            LEFT JOIN processes_fk ON processes.processes_id = processes_fk.processes_id
            WHERE order_processes.order_id = :order_id AND order_processes.file_id = :file_id AND order_processes_index IS NOT NULL
            ORDER BY order_processes.order_processes_index ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $results_key => $result) {
            foreach ($result as $result_key => $value) {
                if ($this->isJson($value)) {
                    $results[$results_key][$result_key] = json_decode($value, true);
                }
            }
        }
        return $results;
    }

    public function insertProcesses($requests)
    {
        $order_id = null;
        $datas = [];
        $fk = [];
        foreach ($requests as $key => $request) {
            if ($key == "fk") {
                foreach ($request as $fk_key => $key_value) {
                    array_push(
                        $fk,
                        [
                            "fk_key" => $fk_key,
                            "fk_value" => $key_value
                        ]
                    );
                }
            } else if ($key == "data") {
                $datas = $request;
            } else if ($key == "order_id") {
                $order_id = $request;
            }
        }
        if (is_null($order_id)) {
            $order_id = $this->getOrderFk($fk);
        }
        if (is_null($order_id)) {
            return [
                "status" => "failed"
            ];
        }
        $value = "";
        $values = [
            "order_id" => 0,
            "file_id" => 0
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key, $requests)&&$values[$key] = $requests[$key];
        }
        $sql = "DELETE FROM public.order_processes
            WHERE order_id = :order_id AND file_id = :file_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        foreach ($datas as $index => $data) {
            $row = [
                "processes_id" => null,
                "process_index" => null,
                "note" => null
            ];
            $note = ',note';
            $note_value = ', :note';
            $note_set = ', note = excluded.note';
            if ($data['note'] == "" || !isset($data['note'])) {
                unset($row['note']);
                $note = '';
                $note_value = '';
                $note_set = '';
            }
            $values = [];
            $values["order_id"] = $order_id;
            if (array_key_exists("fk", $data)) {
                $values["processes_id"] = $this->getProcessesFk($data);
            }
            foreach ($row as $row_key => $row_value) {
                if (array_key_exists($row_key, $data)) {
                    $values["{$row_key}"] = $data[$row_key];
                }
            }
            $values['file_id'] = $requests['file_id'];
            $sql = "INSERT INTO public.order_processes (processes_id,order_processes_index,order_id,file_id{$note})
                VALUES (:processes_id, :process_index, :order_id, :file_id {$note_value})
                ON CONFLICT (order_id,order_processes_index,file_id)
                DO UPDATE SET processes_id = excluded.processes_id {$note_set}
                RETURNING order_processes_id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            $value = "";
            $values = [];
        }
        return ["status" => "success"];
    }
    public function getOrderFk($data)
    {
        
        $value = "";
        $values = [
            "coptd_td001"=>'',
            "coptd_td002"=>'',
            "coptd_td003"=>'',
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $sql = "SELECT order_id
            FROM \"order\"
            WHERE fk->>'coptd_td001' = :coptd_td001 
            AND fk->>'coptd_td002' = :coptd_td002 
            AND fk->>'coptd_td003' = :coptd_td003
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        if ($stmt->rowCount() == 1) {
            $result = $stmt->fetchColumn(0);
            return $result;
        } else {
            $sql = "SELECT DISTINCT order_id
                FROM phasegallery.coptd_file
                WHERE LTRIM(RTRIM(coptd_td001)) = LTRIM(RTRIM(:coptd_td001))
                  AND LTRIM(RTRIM(coptd_td002)) = LTRIM(RTRIM(:coptd_td002))
                  AND LTRIM(RTRIM(coptd_td003)) = LTRIM(RTRIM(:coptd_td003))
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            if ($stmt->rowCount() >= 1) {
                $result = $stmt->fetchColumn(0);
                return $result;
            } else {
                return null;
            }
        }
    }
    public function getProcessesFk($data)
    {
        $value = "";
        $values = [];
        $index = 0;
        foreach ($data["fk"] as $fk_key => $fk_value) {
            $value .= " (processes_fk_key = :processes_fk_key_{$index} AND TRIM(processes_fk_value) = :processes_fk_value_{$index}) OR";
            $values["processes_fk_key_{$index}"] = $fk_key;
            $values["processes_fk_value_{$index}"] = $fk_value;
            $index++;
        }
        $values['count'] = count($data["fk"]);
        $value = rtrim($value, "OR");
        $sql = "SELECT processes_id
        FROM(
            SELECT processes_id
            FROM public.processes_fk
            WHERE {$value}
        )dt
        GROUP BY processes_id
        HAVING COUNT(*) = :count
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        if ($stmt->rowCount() == 1) {
            $processes_id = $stmt->fetchColumn(0);
            return $processes_id;
        } else if ($stmt->rowCount() == 0) {
            $sql = "INSERT INTO public.processes(processes_name)
            VALUES (:processes_name)
            ON CONFLICT(processes_name)
            DO NOTHING
            RETURNING processes_id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':processes_name', $data['processes_name']);
            $stmt->execute();
            $processes_id = $stmt->fetchColumn(0);

            $value = "";
            $values = [];
            $index = 0;
            foreach ($data["fk"] as $fk_key => $fk_value) {
                $value .= " (:processes_id_{$index},:processes_fk_key_{$index},:processes_fk_value_{$index}),";
                $values["processes_fk_key_{$index}"] = $fk_key;
                $values["processes_fk_value_{$index}"] = $fk_value;
                $values["processes_id_{$index}"] = $processes_id;
                $index++;
            }
            $value = rtrim($value, ",");
            $sql = "INSERT INTO public.processes_fk(processes_id,processes_fk_key,processes_fk_value)
            VALUES {$value}
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            return $processes_id;
        }
    }
    // public function getOrderProcessesReprocessHistory($data)
    // {
    //     $values = [
    //         "processes_id" => 0
    //     ];
    //     foreach ($values as $key => $value) {
    //         if (array_key_exists($key, $data)) {
    //             $values[$key] = $data[$key];
    //         }
    //     }
    //     $sql = "SELECT order_processes_reprocess_name
    //         FROM phasegallery.order_processes_reprocess
    //         WHERE order_processes_id = :processes_id;
    //     ";
    //     $stmt = $this->db->prepare($sql);
    //     $stmt->execute($values);
    //     $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $result;
    // }
    public function getOrderProcessesSeries($data)
    {
        $values = [
            "order_processes_id" => []
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        /* 
        
        */
        $sql = "SELECT order_processes.order_processes_id, processes.processes_name,
            COALESCE(order_processes_file.order_processes_file,'[]')order_processes_file, COALESCE(order_processes_file.order_processes,'[]')order_processes
        FROM public.order_processes
        LEFT JOIN public.processes ON order_processes.processes_id = processes.processes_id
        LEFT JOIN (
            SELECT COALESCE(order_processes_subfile.order_processes_id,order_processes_file.order_processes_id) order_processes_id,
            COALESCE(order_processes_file.order_processes_file,'[]')::text order_processes_file,
            JSON_AGG(JSON_BUILD_OBJECT(
                'file_id',order_processes_subfile.subfile_id,
                'order_processes_subfile_id',order_processes_subfile.order_processes_subfile_id
            ))order_processes
            FROM(
                SELECT order_processes_subfile.order_processes_id,
                    order_processes_position.order_processes_subfile_id,
                    order_processes_subfile.file_id subfile_id,
                    JSON_AGG(JSON_BUILD_OBJECT(
                        'canvas_width',order_processes_position.canvas_width,
                        'canvas_height',order_processes_position.canvas_height,
                        'order_processes_position_id',order_processes_position.order_processes_position_id,
                        'order_processes_position_code',order_processes_position.order_processes_position_code,
                        'point_1_x',order_processes_position.point_1_x,
                        'point_1_y',order_processes_position.point_1_y,
                        'point_2_x',order_processes_position.point_2_x,
                        'point_2_y',order_processes_position.point_2_y
                    ))order_processes_data_array
                FROM(
                    SELECT order_processes_position.order_processes_subfile_id,
                        order_processes_position.canvas_width,order_processes_position.canvas_height,
                        order_processes_position.order_processes_position_id,order_processes_position.order_processes_position_code,
                        STRING_AGG(CASE WHEN order_processes_position.position_index=1 THEN order_processes_position.x::text ELSE '0' END,'') point_1_x,
                        STRING_AGG(CASE WHEN order_processes_position.position_index=1 THEN order_processes_position.y::text ELSE '0' END,'') point_1_y,
                        STRING_AGG(CASE WHEN order_processes_position.position_index=2 THEN order_processes_position.x::text ELSE '0' END,'') point_2_x,
                        STRING_AGG(CASE WHEN order_processes_position.position_index=2 THEN order_processes_position.y::text ELSE '0' END,'') point_2_y
                    FROM(
                        SELECT order_processes_subfile.order_processes_subfile_id,ROW_NUMBER() OVER (PARTITION BY order_processes_subfile.order_processes_subfile_id ORDER BY order_processes_subfile ASC) position_index,
                            order_processes_position.order_processes_position_id,
                            position.canvas_width,position.canvas_height,point.x,point.y,
                            order_processes_position.order_processes_position_code
                        FROM phasegallery.order_processes_subfile
                        LEFT JOIN phasegallery.order_processes_position ON order_processes_subfile.order_processes_subfile_id = order_processes_position.order_processes_subfile_id 
                        LEFT JOIN public.position ON position.position_id = order_processes_position.position_id
                        LEFT JOIN public.point ON order_processes_position.position_id = point.position_id
                    )order_processes_position
                    GROUP BY order_processes_position.order_processes_subfile_id,
                        order_processes_position.order_processes_position_id,order_processes_position.canvas_width,
                        order_processes_position.canvas_height,order_processes_position.order_processes_position_code
                )order_processes_position
                LEFT JOIN phasegallery.order_processes_subfile ON order_processes_subfile.order_processes_subfile_id = order_processes_position.order_processes_subfile_id
                GROUP BY order_processes_subfile.order_processes_id,
                    order_processes_position.order_processes_subfile_id,
                    order_processes_subfile.file_id
            )order_processes_subfile
            LEFT JOIN (
                SELECT order_processes_file.order_processes_id,
                    JSON_AGG(JSON_BUILD_OBJECT(
                        'order_processes_file_id',order_processes_file.order_processes_file_id,
                        'file_id', order_processes_file.file_id
                    ))order_processes_file
                FROM phasegallery.order_processes_file
                GROUP BY order_processes_file.order_processes_id
            )order_processes_file ON order_processes_file.order_processes_id = order_processes_subfile.order_processes_id
            GROUP BY order_processes_file.order_processes_id,order_processes_subfile.order_processes_id,COALESCE(order_processes_file.order_processes_file,'[]')::text
        )order_processes_file ON order_processes_file.order_processes_id = order_processes.order_processes_id
        WHERE order_processes.order_processes_id IN ({$data['order_processes_id_list']})
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($values['order_processes_id'] as $key => $value) {
            $stmt->bindValue(":order_processes_id_{$key}", $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $key => $row) {
            foreach ($row as $row_key => $value) {
                if ($this->isJson($value))
                    $result[$key][$row_key] = json_decode($value, true);
                else if ($row_key == "order_processes_id") {
                    $result[$key][$row_key] = $value;
                }
            }
        }
        return $result;
    }
    //yeah he
    public function readSubfilePositionPoint($params)
    {
        $sql = "SELECT order_processes_position.order_processes_position_id, order_processes_position.order_processes_position_code,
                    point.position_id, position.canvas_width, position.canvas_height, point.x, point.y
                FROM point
                LEFT JOIN position ON position.position_id = point.position_id
                LEFT JOIN phasegallery.order_processes_position ON order_processes_position.position_id = position.position_id
                WHERE order_processes_position.order_processes_subfile_id = :order_processes_subfile_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_subfile_id', $params['order_processes_subfile_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function readReprocessPosition($params)
    {
        $sql = "SELECT  order_processes_position_id, order_processes_position_code,
                        position.position_id, x, y, index, canvas_width, canvas_height, draw_type, brush_color
                FROM phasegallery.order_processes_position
                LEFT JOIN position ON order_processes_position.position_id = position.position_id
                LEFT JOIN point ON position.position_id = point.position_id
                LEFT JOIN phasegallery.order_processes_subfile ON order_processes_position.order_processes_subfile_id 
                = order_processes_subfile.order_processes_subfile_id
                WHERE order_processes_subfile.order_processes_subfile_id = :order_processes_subfile_id
                ORDER BY order_processes_position_id, index ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_subfile_id', $params['order_processes_subfile_id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function postReprocessPosition($datas)
    {
        foreach ($datas as $data) {
            $sql = "INSERT INTO position(
                    canvas_width, canvas_height, draw_type, brush_color)
                    VALUES (:canvas_width, :canvas_height, :draw_type, :brush_color)
                    RETURNING position_id;
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':canvas_width', $data['canvas_width']);
            $stmt->bindValue(':canvas_height', $data['canvas_height']);
            $stmt->bindValue(':draw_type', $data['draw_type']);
            $stmt->bindValue(':brush_color', $data['brush_color']);
            $stmt->execute();
            $position_id = $stmt->fetch(PDO::FETCH_ASSOC);
            foreach ($data['point_list'] as $key => $point) {
                $sql = "INSERT INTO point(
                    x, y, index, position_id)
                    VALUES (:x, :y, :index, :position_id);
                ";
                $stmt = $this->container->db->prepare($sql);
                $stmt->bindValue(':x', $point[0]);
                $stmt->bindValue(':y', $point[1]);
                $stmt->bindValue(':index', $key);
                $stmt->bindValue(':position_id', $position_id['position_id']);
                $stmt->execute();
            }
            $sql = "INSERT INTO phasegallery.order_processes_position(
                order_processes_subfile_id, position_id)
                VALUES (:order_processes_subfile_id, :position_id)
                RETURNING order_processes_position_id;
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':order_processes_subfile_id', $data['order_processes_subfile_id']);
            $stmt->bindValue(':position_id', $position_id['position_id']);
            if ($stmt->execute()) {
                $result = [
                    "status" => "success",
                    "data" => $stmt->fetch()['order_processes_position_id']
                ];
            } else {
                $result = [
                    "status" => "fail"
                ];
            }
        }
        return $result;
    }
    public function updateReprocessPosition($params)
    {
        $sql = "UPDATE phasegallery.order_processes_position
                SET order_processes_position_code = :order_processes_position_code
                WHERE order_processes_position_id = :order_processes_position_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_position_code', $params['order_processes_position_code']);
        $stmt->bindValue(':order_processes_position_id', $params['order_processes_position_id']);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function deleteReprocessPosition($params)
    {
        $sql = "DELETE FROM phasegallery.order_processes_position
                WHERE order_processes_position_id = :order_processes_position_id
                RETURNING position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_position_id', $params['order_processes_position_id']);
        $stmt->execute();
        $position_id = $stmt->fetch();
        $sql = "DELETE FROM point
            WHERE position_id = :position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':position_id', $position_id['position_id'], PDO::PARAM_INT);
        $stmt->execute();
        $sql = "DELETE FROM position
            WHERE position_id = :position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':position_id', $position_id['position_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }

    public function insertOrderProcessesFile($data)
    {
        $sql = "INSERT INTO phasegallery.order_processes_file(
            order_processes_id, file_id)
            VALUES (:order_processes_id, :file_id);
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_id', $data['order_processes_id'], PDO::PARAM_INT);
        $stmt->bindValue(':file_id', $data['file_id'], PDO::PARAM_INT);
        $stmt->execute();
        return $this->db->lastInsertId();
    }
    public function getReprocessSubfile($params)
    {
        $sql = "SELECT order_processes_subfile_id, file_id
                FROM phasegallery.order_processes_subfile
                WHERE order_processes_id = :order_processes_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_id', $params['order_processes_id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function readSubfileImageReference($params)  /* get file_id, position_id */
    {
        $sql = "SELECT order_processes_subfile.file_id, order_processes_position.position_id
                FROM phasegallery.order_processes_subfile
                LEFT JOIN phasegallery.order_processes_position
                    ON order_processes_position.order_processes_subfile_id = order_processes_subfile.order_processes_subfile_id
                WHERE order_processes_subfile.order_processes_subfile_id = :order_processes_subfile_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_subfile_id', $params['order_processes_subfile_id']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
    public function deleteSubfileImage($params)
    {
        $sql = "DELETE FROM phasegallery.processes_group_file
                WHERE processes_group_file_id = :order_processes_subfile_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_subfile_id', $params['order_processes_subfile_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function deleteSubfileImagePosition($params)
    {
        $sql = "DELETE FROM phasegallery.order_processes_position
                WHERE order_processes_subfile_id = :order_processes_subfile_id
                RETURNING position_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_subfile_id', $params['order_processes_subfile_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function deleteSubfileImageFile($file_id)
    {
        $sql = "DELETE FROM phasegallery.file
                WHERE file_id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function deletePosition($position_id)
    {
        $sql = "DELETE FROM position
                WHERE position_id = :position_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':position_id', $position_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function deletePoint($position_id)
    {
        $sql = "DELETE FROM point
                WHERE position_id = :position_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':position_id', $position_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function insertSubfileImage($data)
    {
        $sql = "INSERT INTO phasegallery.order_processes_subfile(
            order_processes_id, file_id)
            VALUES (:order_processes_id, :file_id)
            RETURNING order_processes_subfile_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_id', $data['order_processes_id']);
        $stmt->bindValue(':file_id', $data['file_id']);
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        } else {
            return [
                "status" => "failed"
            ];
        }
    }
    public function readOrderFK($params)
    {
        $sql = "SELECT order_key, order_value
                FROM order_fk
                WHERE order_id = :order_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_id', $params['order_id']);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            var_dump($stmt->errorInfo());
            return ["status" => "failed"];
        }
    }
    public function readCoptdFile($order_fk)
    {
        $condition = "";
        foreach ($order_fk as $key => $value) {
            $condition .= "{$value['order_key']}={$value['order_value']}";
            $key !== array_key_last($order_fk) && ($condition .= " AND ");
        }
        $sql = "SELECT file_id
                FROM phasegallery.coptd_file
                WHERE {$condition}
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            var_dump($stmt->errorInfo());
            return ["status" => "failed"];
        }
    }
    public function getOrderProcessesLabel($data)
    {
        $values = [
            'TA001' => '',
            'TA002' => '',
            'order_name' => ''
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $result = [
            "cLine1" => "{$data['TA001']}-{$data['TA002']}",
            "cLine2" => "{$data['order_name']}"
        ];
        return $result;
    }
    public function getOrder_FK($data)
    {
        $fk_data = [];
        $values = [
            "coptd_td001" => '',
            "coptd_td002" => '',
            "coptd_td003" => ''
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $fk_data['fk'] = json_encode($values);
        $sql = "SELECT order_id
            FROM public.order
            WHERE fk = :fk
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->bindValue(':fk', $fk_data['fk']);
        $stmt->execute();
        if ($stmt->rowCount() != 0) {
            $result = $stmt->fetchColumn(0);
            return $result;
        } else {
            $sql = "INSERT INTO public.order (user_id)
                VALUES (NULL)
                RETURNING order_id;
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->execute();
            $order_id = $stmt->fetchColumn(0);
            $fk_data['order_id'] = $order_id;
            $sql = "UPDATE public.order
                SET fk = :fk
                WHERE order_id = :order_id
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->execute($fk_data);
            return $order_id;
        }
    }
    public function getProcessesLinesOuter($data)
    {
        $values = [
            'MW001' => '000'
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $sql = "SELECT [MW001] 
                ,[MW002] processes_name
                ,[MD001] 
                ,[MD002] line_name
            FROM [MIL].[dbo].[CMSMW]
            LEFT JOIN [MIL].[dbo].[CMSMD] ON LTRIM(RTRIM(CMSMD.MD001)) = LTRIM(RTRIM(CMSMW.MW005))
            WHERE LTRIM(RTRIM(MW001)) LIKE LTRIM(RTRIM('{$values['MW001']}')) AND MW003 NOT LIKE '%停用%' AND MD001 IS NOT NULL
            AND LTRIM(RTRIM(MD001)) NOT IN ('C', 'E')
        ";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => $sql]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        return $result;
    }
    public function readCategoryProcesses($params)
    {
        $bind_values = [
            'date_start' => '',
            'date_end' => '',
            'category' => [],
            'material' => '',
            'ti' => '',
            'cur_page' => 0,
            'size' => 0,
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        foreach ($bind_values['category'] as $key => $value) {  /* assemble category */
            $bind_values["category_{$key}"] = $value;
        }
        $category_list = '';
        foreach ($bind_values['category'] as $key => $value) {
            $category_list .= ":category_{$key}";
            if ($key !== array_key_last($bind_values['category'])) {
                $category_list .= ', ';
            }
        }
        if (count($bind_values['category']) !== 0) {
            unset($bind_values['category']);
        }
        $processes_condition = "%";
        foreach ($params['processes_id'] as $key => $value) {  /* assemble processes_id */
            $processes_condition .= "{$value},";
        }
        $processes_condition = rtrim($processes_condition, ",");
        $processes_condition .= "%";
        if ($bind_values['material'] !== '') {  /* special condition */
            $material_condition = "AND item.material = :material";
        } else {
            $material_condition = '';
            unset($bind_values['material']);
        }
        if ($bind_values['ti'] !== '') {
            $ti_condition = "AND item.ti = :ti";
        } else {
            $ti_condition = '';
            unset($bind_values['ti']);
        }
        if ($bind_values['size'] <= 0) {  /* DataTable page limit */
            $length = '';
            $start = '';
            $limit = '';
        } else {
            $length = $bind_values['cur_page'] * $bind_values['size'];
            $start = "WHERE category_processes.row_number >" . ($length - $bind_values['size']);
            $limit = "LIMIT {$length}";
        }
        unset($bind_values['cur_page']);
        unset($bind_values['size']);
        /* sql query */
        $sql = "SELECT number, material, ti, processes, pc, file_id, total
                FROM
                (
                    SELECT number, material, ti, processes, pc, file_id, row_number, COUNT(*) OVER() total
                    FROM (
                        SELECT item.code number, item.material, item.ti,
                            JSON_AGG(
                                JSON_BUILD_OBJECT(
                                    'index', processes_group.order_processes_index,
                                    'processes_id', processes_group.processes_id,
                                    'processes_name', processes_group.processes_name
                                )
                                ORDER BY processes_group.order_processes_index
                            ) processes,
                            STRING_AGG (DISTINCT processes_group.floor_id::TEXT, ', ') file_id,
                            ROW_NUMBER() OVER (ORDER BY item.code),
                            STRING_AGG (
                                processes_group.processes_id::TEXT, ','
                                ORDER BY processes_group.order_processes_index
                            ) pc
                        FROM \"order\"
                        LEFT JOIN item ON item.id = \"order\".item_id
                        LEFT JOIN item_category ON item_category.id = item.item_category_id
                        LEFT JOIN order_processes ON order_processes.order_id = \"order\".order_id
                        LEFT JOIN (
                            SELECT order_processes.order_processes_id, order_processes.order_processes_index,
                                processes.processes_id, processes.processes_name, floor.floor_id
                            FROM \"order\"
                            LEFT JOIN order_processes ON order_processes.order_id = \"order\".order_id
                            LEFT JOIN processes ON processes.processes_id = order_processes.processes_id
                            LEFT JOIN line_machine_processes ON line_machine_processes.processes_id = processes.processes_id
                            LEFT JOIN line_machine ON line_machine.line_machine_id = line_machine_processes.line_machine_id
                            LEFT JOIN machine ON machine.machine_id = line_machine.machine_id
                            LEFT JOIN floor ON floor.floor_id = machine.floor_id
                            WHERE order_processes.order_processes_index IS NOT NULL
                            GROUP BY order_processes.order_processes_id, order_processes.order_processes_index,
                                processes.processes_id, processes.processes_name, floor.floor_id
                            ORDER BY order_processes.order_processes_index
                        ) processes_group ON processes_group.order_processes_id = order_processes.order_processes_id
                        WHERE order_processes.order_processes_index IS NOT NULL
                            AND \"order\".order_date BETWEEN :date_start AND :date_end
                            AND item_category.code IN ({$category_list})
                            {$material_condition} {$ti_condition}
                        GROUP BY item.code, item.material, item.ti
                    ) unlimited
                    WHERE pc LIKE '{$processes_condition}'
                ) category_processes
                {$start}
                {$limit}
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($bind_values)) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($data) >= 1) {
                $total = $data[0]['total'];
            } else {
                $total = 0;
            }
            foreach ($data as $key => $value) {
                unset($data[$key]['pc']);
            }
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
        return [
            'data' => $data,
            'total' => $total
        ];
    }
    public function updateOrderProcessesSubfile($params)
    {
        $sql = "UPDATE phasegallery.order_processes_subfile
                SET order_processes_subfile_draw = :order_processes_subfile_draw, order_processes_subfile_tech = :order_processes_subfile_tech
                WHERE order_processes_subfile_id = :order_processes_subfile_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_subfile_draw', $params['order_processes_subfile_draw']);
        $stmt->bindValue(':order_processes_subfile_tech', $params['order_processes_subfile_tech']);
        $stmt->bindValue(':order_processes_subfile_id', $params['order_processes_subfile_id']);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function getSubfileImage($params)
    {
        $data = [
            'order_processes_subfile_id' => 0
        ];
        foreach ($data as $key => $value) {
            array_key_exists($key, $params) && ($data[$key] = $params[$key]);
        }
        $sql = "SELECT order_processes_subfile_id, order_processes_subfile_draw, order_processes_subfile_tech
                FROM phasegallery.order_processes_subfile
                WHERE order_processes_subfile_id = :order_processes_subfile_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($data)) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            var_dump($stmt->errorInfo());
            return ["status" => "failed"];
        }
    }

    public function insertProcessesFk($data)
    {
        $sql = "INSERT INTO public.processes(
            processes_name)
            VALUES (:processes_name) 
            ON CONFLICT(processes_name) DO NOTHING
            RETURNING processes_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':processes_name', $data['process_name'], PDO::PARAM_STR);
        $stmt->execute();
        var_dump($stmt->errorInfo());

        $sql = "SELECT processes_id FROM processes WHERE processes_name = :processes_name;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':processes_name', $data['process_name'], PDO::PARAM_STR);
        $stmt->execute();

        $data['id'] = $stmt->fetchColumn();

        $sql = "INSERT INTO public.processes_fk(
            processes_id, processes_fk_key, processes_fk_value)
            VALUES (:processes_id, 'CMSMW.MW001', :processes_fk_value) ON CONFLICT(processes_id, processes_fk_key) DO NOTHING;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':processes_id', $data['id'], PDO::PARAM_INT);
        $stmt->bindParam(':processes_fk_value', $data['process_id'], PDO::PARAM_STR);
        $stmt->execute();
        var_dump($stmt->errorInfo());
    }

    public function delete_order_image($params)
    {
        $values = [
            'order_process_list_id' => 0,
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key, $params) && ($values[$key] = $params[$key]);
        }
        $sql = "DELETE FROM phasegallery.order_process_list
            WHERE order_process_list_id = :order_process_list_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function get_order_image($params)
    {
        $data = [
            'coptd_td001' => '',
            'coptd_td002' => '',
            'coptd_td003' => '',
        ];
        foreach ($data as $key => $value) {
            array_key_exists($key, $params) && ($data[$key] = $params[$key]);
        }
        $sql = "SELECT COALESCE(order_process_list.order_process_list,'[]')order_process_list
            FROM phasegallery.order
            LEFT JOIN(
                SELECT order_process_list.order_id,JSON_AGG(order_process_list.*) order_process_list
                FROM(
                    SELECT order_process_list.order_process_list_id,order_process_list.order_id,order_process_list.file_id,'/3DConvert/PhaseGallery/order_image/' || order_process_list.file_id src
                    FROM phasegallery.order_process_list
                )order_process_list
                GROUP BY order_process_list.order_id
            )order_process_list ON \"order\".order_id = order_process_list.order_id
            WHERE order_fk->>'coptd_td001' = :coptd_td001 AND LTRIM(RTRIM(order_fk->>'coptd_td002')) = LTRIM(RTRIM(:coptd_td002)) AND order_fk->>'coptd_td003' = :coptd_td003
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($data)) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $index => $row) {
                foreach ($row as $key => $value) {
                    if ($this->isJson($value)) {
                        $result[$index][$key] = json_decode($value, true);
                    }
                }
            }
            return $result;
        } else {
            return ["status" => "failed"];
        }
    }
    public function getOrderFileId($params)
    {
        $data = [
            'coptd_td001' => '',
            'coptd_td002' => '',
            'coptd_td003' => '',
        ];
        foreach ($data as $key => $value) {
            array_key_exists($key, $params) && ($data[$key] = $params[$key]);
        }
        $sql = "SELECT file_id
            FROM phasegallery.coptd_file
            WHERE coptd_td001 = :coptd_td001 AND LTRIM(RTRIM(coptd_td002)) = LTRIM(RTRIM(:coptd_td002)) AND coptd_td003 = :coptd_td003
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($data)) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            var_dump($stmt->errorInfo());
            return ["status" => "failed"];
        }
    }
    public function deleteCoptdFile($params)
    {
        $data = [
            'coptd_td001' => '',
            'coptd_td002' => '',
            'coptd_td003' => '',
            'file_id' => 0
        ];
        foreach ($data as $key => $value) {
            array_key_exists($key, $params) && ($data[$key] = $params[$key]);
        }
        $sql = "DELETE FROM phasegallery.coptd_file
                WHERE coptd_td001 = :coptd_td001 AND LTRIM(RTRIM(coptd_td002)) = LTRIM(RTRIM(:coptd_td002)) AND coptd_td003 = :coptd_td003 AND file_id = :file_id
                RETURNING file_id";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($data)) {
            $result = array(
                'file_id' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'status' => "success"
            );
        } else {
            $result = ["status" => "failed", "error" => $stmt->errorInfo()];
        }
        return $result;
    }
    public function convertDWGtoJPG($data)
    {
        $home = new Home($this->db);
        if (pathinfo($data, PATHINFO_EXTENSION) === 'dwg') {
            $files = json_encode([$data], JSON_UNESCAPED_SLASHES);
        } else {
            return "file type error";
        }
        $recogUrl = "http://mil_python:8090/dwgTojpg?Files={$files}";
        $result = $home->http_response($recogUrl);
        $result = json_decode($result, true);
        return $result;
    }
    public function insertProcessesGroup($data)
    {
        $values = [
            'order_id' => 0,
            'file_id' => 0,
            'group_name' => '',
            'drawing_help' => false,
            'drawing_note' => '',
            'tech_note' => ''
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key, $data) && ($values[$key] = $data[$key]);
        }
        if(array_key_exists('processes_group_id',$data)){
            $sql = "UPDATE phasegallery.processes_group
                SET order_id = :order_id, file_id = :file_id, group_name = :group_name, drawing_help = :drawing_help
                    , drawing_note = :drawing_note, tech_note = :tech_note
                WHERE processes_group_id = :processes_group_id 
                RETURNING processes_group_id;
            ";
            $values['processes_group_id'] = $data['processes_group_id'];
        }else{
            $sql = "INSERT INTO phasegallery.processes_group(
                    order_id, file_id, group_name, drawing_help, drawing_note, tech_note)
                    VALUES (:order_id, :file_id, :group_name, :drawing_help, :drawing_note, :tech_note)
                    RETURNING processes_group_id;
            ";
        }
        $stmt = $this->container->db->prepare($sql);
        if ($stmt->execute($values)) {
            $result = [
                "status" => "success",
                "processes_group_id" => $stmt->fetchColumn()
            ];
        } else {
            $result = [
                "status" => "fail"
            ];
        }
        return $result;
    }
    public function insertProcessesGroupMember($data)
    {
        $values_fk = [
            'processes_fk_value' => 0
        ];
        $values = [
            'processes_group_id' => 0,
            'processes_id' => 0
        ];
        foreach ($values_fk as $key => $value) {
            array_key_exists($key, $data) && ($values_fk[$key] = $data[$key]);
        }
        $sql = "SELECT processes_id FROM public.processes_fk
                WHERE TRIM(processes_fk_value) = TRIM(:processes_fk_value) 
                AND processes_fk_key = 'CMSMW.MW001';
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->execute($values_fk);
        $values['processes_id'] = $stmt->fetchColumn();
        foreach ($values as $key => $value) {
            array_key_exists($key, $data) && ($values[$key] = $data[$key]);
        }
        $sql = "INSERT INTO phasegallery.processes_group_member
            (processes_group_id, processes_id)
            VALUES (:processes_group_id, :processes_id)
            ON CONFLICT(processes_group_id, processes_id)
            DO NOTHING ;
        ";
        $stmt = $this->container->db->prepare($sql);
        if ($stmt->execute($values)) {
            $result = [
                "status" => "success"
            ];
        } else {
            $result = [
                "status" => "fail"
            ];
        }
        return $result;
    }
    public function deleteProcessesGroup($data)
    {
        $values = [
            'order_id' => 0,
            'file_id' => 0,
            'group' => []
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key, $data) && ($values[$key] = $data[$key]);
        }
        $stmt_array = array_map(function($group)
            {
                return $group['processes_group_id'];
            },
            array_filter($values['group'],
                function($group_){
                    return array_key_exists('processes_group_id',$group_);
                }
            )
        );
        $stmt_string = "";
        if(count($stmt_array)!=0){
            $stmt_string = implode(',',
                array_map(function($column,$index){
                    return "{$column}_{$index}";
                },array_fill(0,count($stmt_array),':processes_group_id'),array_keys($stmt_array))
            );
            $stmt_string = " AND processes_group_id NOT IN ({$stmt_string})";
        }
        unset($values['group']);
        foreach ($stmt_array as $key => $value) {
            $values["processes_group_id_{$key}"] = $value;
        }

        $sql = "DELETE FROM phasegallery.processes_group
                WHERE order_id = :order_id AND file_id = :file_id {$stmt_string}
                RETURNING processes_group_id;
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->execute($values);
        $del_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($del_id as $key => $value) {
            $sql = "DELETE FROM phasegallery.processes_group_member
                    WHERE processes_group_id = :processes_group_id;
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->execute($value);
        }
    }
    public function selectProcessesGroup($data)
    {
        $business = new Business($this->container->db);
        $allProcess = json_encode($business->getallProcess());
        $values = [
            'order_id' => 0,
            'file_id' => 0
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key, $data) && ($values[$key] = $data[$key]);
        }
        $sql = "SELECT processes_group.processes_group_id, group_name, drawing_help, 
                JSON_AGG(JSON_BUILD_OBJECT(
                    'processes_index', order_process.order_process_index, 
                    'processes_id', order_process.order_process_id,
                    'processes_name', process_outer.name 
                )) processes, 
                drawing_note, tech_note 
                
                FROM phasegallery.order_process
                LEFT JOIN phasegallery.processes ON processes.processes_id = order_process.processes_id
                LEFT JOIN phasegallery.processes_group_member ON order_process.order_process_id = processes_group_member.order_process_id
                LEFT JOIN phasegallery.processes_group ON processes_group.processes_group_id = processes_group_member.processes_group_id
                LEFT JOIN json_to_recordset('{$allProcess}')as process_outer (id text,name text)
                    ON TRIM(processes.processes_fk->>'CMSMW.MW001') = TRIM(process_outer.id)
                LEFT JOIN phasegallery.order_process_list ON order_process_list.order_process_list_id = order_process.order_process_list_id
                WHERE order_process_list.order_id = :order_id AND order_process_list.file_id = :file_id
                GROUP BY processes_group.processes_group_id;
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function patch_processes_group_frame($data){
		$crm = new CRM($this->container->db);
        $values = [
            "processes_group_file_id" => 0,
            "position" => []
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        /*  */
        $position = array_filter($values['position'],function($position){
            return array_key_exists('position_id',$position);
        });
        $stmt_array = [$values['processes_group_file_id']];
        $stmt_array = array_merge($stmt_array,array_map(function($position){
                return $position['position_id'];
        },$position));
        $stmt_string = "";
        if(count($position)!==0){
            $stmt_string = implode(',',array_fill(0,count($position),'?'));
            $stmt_string = " AND  position_id NOT IN ({$stmt_string})";
        }

        $sql = "DELETE FROM \"position\"
            WHERE position_id IN (
                SELECT position_id
                FROM phasegallery.processes_group_file_position
                WHERE processes_group_file_id = ? {$stmt_string}
            );
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute($stmt_array)) {
            return [
                "status" => "failure"
            ];
        }
        $sql = "DELETE FROM phasegallery.processes_group_file_position
            WHERE processes_group_file_id = ? {$stmt_string}
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute($stmt_array)) {
            return [
                "status" => "failure"
            ];
        }
        /*  */
        foreach ($values['position'] as &$position_) {
            if(!array_key_exists('position_id',$position_)){
                $position_['position_id'] = $crm->createtPosition($position_)['position_id'];
                foreach ($position_['point_list'] as $key => $value) {
                    $crm->createtPoint($position_['position_id'], $key, $value);
                }
                $position_['processes_group_file_id'] = $values['processes_group_file_id'];
                $result = $this->create_processes_group_position($position_);
                $result['position_id'] = $position_['position_id'];
            }else{
                $sql = "UPDATE phasegallery.processes_group_file_position
                    SET processes_group_file_position_code = :processes_group_file_position_code
                    WHERE position_id = :position_id;
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':processes_group_file_position_code', $position_['processes_group_file_position_code']);
                $stmt->bindValue(':position_id', $position_['position_id']);
                if (!$stmt->execute()) {
                    return ["status" => "failed"];
                }
            }
        }
        /*  */
        return $values;
    }
    public function create_processes_group_position($data){
        $values = [
            "processes_group_file_id" => 0,
            "position_id" => 0,
            "drawRectArea" => 0,
            'processes_group_file_position_code' => ''
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }
        $sql = "INSERT INTO phasegallery.processes_group_file_position (processes_group_file_id, position_id, \"drawRectArea\", processes_group_file_position_code)
                VALUES (:processes_group_file_id, :position_id, :drawRectArea, :processes_group_file_position_code)
                RETURNING processes_group_file_position_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return [
                "status" => "success",
                "processes_group_position_file_id" => $stmt->fetch(PDO::FETCH_ASSOC)["processes_group_file_position_id"]
            ];
        } else {
            return ["status" => "failed",];
        }
    }
    public function delete_processes_group_file($params)
    {
        $sql = "DELETE FROM phasegallery.processes_group_file
                WHERE processes_group_file_id = :order_processes_subfile_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_subfile_id', $params['order_processes_subfile_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }

    public function get_processes_group_file($data){
        $values = [
            "processes_group_file_id" => null,
            "processes_group_id" => null
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $stmt_string = "";
        $stmt_array = [];
        if(!is_null($values['processes_group_file_id'])){
            $stmt_string = "WHERE processes_group_file_id = :processes_group_file_id";
            $stmt_array['processes_group_file_id'] = $values['processes_group_file_id'];
        }else if(!is_null($values['processes_group_id'])){
            $stmt_string = "WHERE processes_group_id = :processes_group_id";
            $stmt_array['processes_group_id'] = $values['processes_group_id'];
        }
        
        $sql = "SELECT processes_group_file_id, processes_group_id, name
            FROM phasegallery.processes_group_file
            {$stmt_string}
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($stmt_array)) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return ["status" => "failed"];
        }
    }
    public function upload_processes_group_file($data){
        $values = [
            "file_name" => '',
            "processes_group_id" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $sql = "INSERT INTO phasegallery.processes_group_file(processes_group_id,\"name\")
            VALUES (:processes_group_id,:file_name)
            RETURNING processes_group_file_id
        ";
        $stmt = $this->container->db->prepare($sql);
        if(!$stmt->execute($values)){
            return ["status"=>"failure"];
        }
        $processes_group_file_id = $stmt->fetchColumn(0);
        return [
            "status"=>"success",
            "src" => "/3DConvert/PhaseGallery/order_image/{$processes_group_file_id}",
            "processes_group_file_id" => $processes_group_file_id
        ];
    }
    public function render_png($data){
        $request = $data['request'];
        $result = $data['result'];
        $response = $data['response'];
        foreach ($result as $row) {
            $picture_name = $row['name'];
            $filepath = $this->container->upload_directory . DIRECTORY_SEPARATOR . $picture_name;
            $source = $this->compressImage($filepath);
            imagealphablending($source, false);
            imagesavealpha($source, true);
            imagepng($source);
            imagedestroy($source);
            $response = $response->withHeader('Content-Description', 'File Transfer')
                ->withHeader('Content-Type', 'image/png')
                ->withHeader('Content-Disposition', 'attachment;filename="' . $picture_name . '"')
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
    public function get_processes_group_frame($data){
        $sql = "SELECT processes_group_file_position.position_id, canvas_width, canvas_height,
            processes_group_file_position.processes_group_file_position_code, draw_type, brush_color,
            COALESCE(JSON_AGG(point.* ORDER BY point.index),'[]') point_list, \"drawRectArea\"
            FROM phasegallery.processes_group_file_position
            LEFT JOIN position ON position.position_id = processes_group_file_position.position_id
            LEFT JOIN point ON point.position_id = position.position_id 
            WHERE processes_group_file_position.processes_group_file_id = :processes_group_file_id
            GROUP BY processes_group_file_position.position_id, canvas_width, canvas_height,processes_group_file_position.processes_group_file_position_code, draw_type, brush_color, \"drawRectArea\"
            ORDER BY processes_group_file_position.position_id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':processes_group_file_id', $data['processes_group_file_id']);
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $index => $row) {
                foreach ($row as $key => $value) {
                    if($key==='point_list'){
                        $result[$index][$key] = array_map(function($location){
                            return [$location['x'],$location['y']];
                        },json_decode($value,true));
                    }
                }
            }
        } else {
            $result = ["status" => "failed"];
        }
        return $result;
    }
        
	public function upload_processes_group_paint($data){
        $sql = "INSERT INTO phasegallery.processes_group_file_paint(processes_group_file_id, file_id)
            VALUES (:processes_group_file_id, :file_id);
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':processes_group_file_id', $data['processes_group_file_id']);
        $stmt->bindValue(':file_id', $data['file_id']);
        if ($stmt->execute()) {
            $result = [
                "status" => "success",
            ];
        } else {
            var_dump($stmt->errorInfo());
            $result = [
                "status" => "failed",
            ];
        }
        return $result;
    }
    public function delete_processes_group_paint($data){
        $sql = "DELETE FROM phasegallery.processes_group_file_paint
            WHERE processes_group_file_id = :processes_group_file_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':processes_group_file_id', $data['processes_group_file_id']);
        if ($stmt->execute()) {
            $result = [
                "status" => "success",
            ];
        } else {
            $result = [
                "status" => "failed",
            ];
        }
        return $result;
    }
    public function get_processes_group_paint($data){
        $values = [
            "processes_group_file_id" => null,
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $sql = "SELECT file_name
            FROM phasegallery.processes_group_file_paint
            LEFT JOIN phasegallery.file ON processes_group_file_paint.file_id = file.file_id
            WHERE processes_group_file_id = :processes_group_file_id;
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return ["status"=>"failure"];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = [];
        foreach ($result as $row) {
            $picture_name = $row['file_name'];
            $filepath = $this->container->upload_directory . DIRECTORY_SEPARATOR . $picture_name;
            $type = pathinfo($filepath, PATHINFO_EXTENSION);
            $data = file_get_contents($filepath);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            $response[] = $base64;
        }
        return $response;
    }
    public function get_order_process_list($data){
        $business = new Business($this->db);
        $process_list = json_encode($business->getRFIDProcessNmaes());
        $values = [
            "order_process_list_id" =>0
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key] = $data[$key];
        }
        $sql = "SELECT order_process.order_process_id, order_process.order_process_list_id, order_process.order_process_index, order_process.order_process_note, order_process.processes_id, processes.processes_fk
            FROM phasegallery.order_process
            LEFT JOIN phasegallery.processes ON processes.processes_id = order_process.processes_id
            WHERE order_process_list_id = :order_process_list_id;
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return ["status"=>"failure"];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row_index => $row) {
            foreach ($row as $key => $value) {
                $this->isJson($value)&&$result[$row_index][$key] = json_decode($value,true);
            }
        }
        return $result;
    }
    public function post_order_process_list($data){
        $order_process_list = [
            "order_process_list_id"=>0, 
            "data" => []
        ];
        foreach ($order_process_list as $key => $value) {
            array_key_exists($key,$data)&&$order_process_list[$key] = $data[$key];
        }
        foreach ($order_process_list['data'] as $key => $value) {
            $order_process_list['data'][$key]['processes_id'] = $this->get_process_id($value);
        }
        /* DELETE */
        $stmt_array = array_merge(
            [$order_process_list['order_process_list_id']]
            ,array_map(function($current){
                return $current['order_process_id'];
            }
            ,array_filter($order_process_list['data'],function($current){
                return !is_null($current['order_process_id']);
            }))
        );
        $stmt_string = implode(','
            ,array_fill(
                0
                ,count(
                    array_filter($order_process_list['data'],function($current){
                        return !is_null($current['order_process_id']);
                    }
                )
            ),'?'
        ));
        $stmt_string = strlen($stmt_string)!==0?" AND order_process_id NOT IN ({$stmt_string})":'';
        $sql = "DELETE FROM phasegallery.order_process
            WHERE order_process_list_id = ? {$stmt_string}
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($stmt_array)){
            return ["status"=>"failure"];
        }
        /*  */
        /* INSERT,UPDATE */
        foreach ($order_process_list['data'] as $row_index => $row) {
            $values = [
                "note" => '',
                "process_index" => 0,
                "processes_id" => 0,
                "order_process_list_id"=>0,
                "order_process_id" => null
            ];
            foreach ($values as $key => $value) {
                array_key_exists($key,$row)&&$values[$key] = $row[$key];
            }
            foreach ($values as $key => $value) {
                array_key_exists($key,$order_process_list)&&$values[$key] = $order_process_list[$key];
            }
            $stmt_array = [
                "order_process_id"=>$values['order_process_id'],
                "order_process_list_id"=>$values['order_process_list_id'], 
                "order_process_index"=>$values['process_index'], 
                "order_process_note"=>$values['note'], 
                "processes_id" => $values['processes_id']
            ];
            if(is_null($stmt_array['order_process_id'])){
                unset($stmt_array['order_process_id']);
                $sql = "INSERT INTO phasegallery.order_process(order_process_list_id, order_process_index, order_process_note, processes_id)
                    VALUES(:order_process_list_id, :order_process_index, :order_process_note, :processes_id)
                    RETURNING order_process_id
                ";
            }else{
                $sql = "UPDATE phasegallery.order_process
                    SET order_process_list_id=:order_process_list_id, order_process_index=:order_process_index, order_process_note=:order_process_note, processes_id = :processes_id
                    WHERE order_process_id = :order_process_id
                    RETURNING order_process_id
                ";
            }
            $stmt = $this->db->prepare($sql);
            if(!$stmt->execute($stmt_array)){
                return ["status"=>"failure"];
            }
            $order_process_list['data'][$row_index]['order_process_id'] = $stmt->fetchColumn(0);
        }
        return $order_process_list['data'];
        /*  */
    }
    public function get_process_id($data){
        $values = [
            "fk" => []
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key] = $data[$key];
        }
        if(count($values['fk'])===0){
            return 0;
        }
        $values = ['processes_fk'=>json_encode($values['fk'])];
        $sql = "INSERT INTO phasegallery.processes(processes_fk)
            VALUES (:processes_fk)
            ON CONFLICT(processes_fk)
            DO UPDATE 
                SET processes_fk = EXCLUDED.processes_fk
            RETURNING processes_id;
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return ["status"=>"failure"];
        }
        return $stmt->fetchColumn(0);
    }
}
