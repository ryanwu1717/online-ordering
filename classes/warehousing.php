<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;
use Slim\Http\UploadedFile;

class warehousing
{
    protected $container;
    protected $db;
    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->db = $container->db;
    }

    private function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        return $filename;
    }

    public function uploadFile($inputFile)
    {
        if ($inputFile->getError() === UPLOAD_ERR_OK) {
            $file_name = $this->moveUploadedFile($this->container->upload_directory, $inputFile);
            return [
                'file_name' => $file_name,
                'client_name' => $inputFile->getClientFilename()
            ];
        } else {
            return ['status' => 'failure',];
        }
    }

    public function createFile($params)
    {
        date_default_timezone_set('Asia/Taipei');
        $bind_values = [
            'client_name' => '',
            'file_name' => '',
            'upload_time' => date("Y-m-d H:i:s"),
            'user_id' => $_SESSION['id']
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        $sql = "INSERT INTO warehousing.file (client_name, file_name, upload_time, user_id)
                VALUES (:client_name, :file_name, :upload_time, :user_id)
                RETURNING id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($bind_values)) {
            return ['id' => $stmt->fetchColumn()];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }

    public function readCurrentOriginMaterialSupplier($params)
    {
        $sql = "WITH current_rfid_tag AS (
            SELECT rfid_tag.rfid_tag_id, MAX(\"dTime\") max_dtime
            FROM public.\"RFID_TABLE_Log\"
            LEFT JOIN rfid_tag ON rfid_tag.rfid_tag = \"RFID_TABLE_Log\".\"cTagID\"
            WHERE \"iAntennaID\" = 4
                AND (:now_time::TIMESTAMP - INTERVAL '5 SECONDS') <= \"dTime\" AND \"dTime\" <= NOW()  /* change timestamp to now(), fixed last 5 secs */
            GROUP BY rfid_tag.rfid_tag_id
            ORDER BY rfid_tag.rfid_tag_id ASC
        ) 
            SELECT 'supplier' \"type\",JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'user_id', user_rfid_tag.user_id,
                        'name', supplier_table.supplier_name,
                        'number', supplier_table.supplier_number,
                        'telephone', supplier_table.telephone,
                        'origin_material_supplier',origin_material_supplier.json_agg
                    )
            )json
            FROM current_rfid_tag
            INNER JOIN system.user_rfid_tag ON user_rfid_tag.rfid_tag_id = current_rfid_tag.rfid_tag_id
            INNER JOIN supplier supplier_table ON supplier_table.user_id = user_rfid_tag.user_id
            INNER JOIN (
                SELECT origin_material_supplier.supplier_id,JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'origin_material_supplier_id', origin_material_supplier.origin_material_supplier_id,
                        'purchase_order_id', purchase_order.id,
                        'origin_material_id', origin_material_supplier.origin_material_id,
                        'origin_material_name', origin_material.origin_material_name,
                        'count', origin_material_supplier.count,
                        'supplier_name', supplier_table.supplier_name,
                        'is_receive', CASE WHEN origin_material_handler.origin_material_supplier_id IS NOT NULL THEN true ELSE false END
                    )   
                    ORDER BY origin_material_supplier.origin_material_supplier_id ASC
                )
                FROM origin_material_supplier
                LEFT JOIN origin_material ON origin_material_supplier.origin_material_id = origin_material.origin_material_id
                LEFT JOIN supplier supplier_table ON supplier_table.supplier_id = origin_material_supplier.supplier_id
                LEFT JOIN purchase_order ON purchase_order.id = origin_material_supplier.purchase_order_id
                LEFT JOIN (
                    SELECT origin_material_handler.origin_material_supplier_id
                    FROM origin_material_handler
                    GROUP BY origin_material_handler.origin_material_supplier_id
                )origin_material_handler ON origin_material_handler.origin_material_supplier_id = origin_material_supplier.origin_material_supplier_id
                GROUP BY origin_material_supplier.supplier_id
            )origin_material_supplier ON origin_material_supplier.supplier_id = supplier_table.supplier_id
            UNION ALL(
                SELECT 'staff' \"type\",JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'user_name', \"user\".name,
                            'user_id', user_rfid_tag.user_id,
                            'module_name', user_modal.module_names
                        )
                )json
                FROM current_rfid_tag
                INNER JOIN system.user_rfid_tag ON user_rfid_tag.rfid_tag_id = current_rfid_tag.rfid_tag_id
                INNER JOIN (
                    SELECT user_modal.uid,STRING_AGG(module.name,',') module_names
                    FROM system.user_modal
                    INNER JOIN setting.module ON user_modal.module_id = module.id
                    GROUP BY user_modal.uid
                )user_modal ON user_modal.uid = user_rfid_tag.user_id
                INNER JOIN system.user ON user_rfid_tag.user_id = \"user\".id
            )
            UNION ALL(
                SELECT 'origin_material_supplier' \"type\", JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'origin_material_supplier_id', origin_material_supplier.origin_material_supplier_id,
                        'purchase_order_id', purchase_order.id,
                        'origin_material_id', origin_material_supplier.origin_material_id,
                        'origin_material_name', origin_material.origin_material_name,
                        'count', origin_material_supplier.count,
                        'supplier_name', supplier_table.supplier_name
                    )   
                    ORDER BY origin_material_supplier.origin_material_supplier_id ASC
                )json
                FROM current_rfid_tag
                INNER JOIN origin_material_supplier_rfid_tag ON origin_material_supplier_rfid_tag.rfid_tag_id = current_rfid_tag.rfid_tag_id
                LEFT JOIN origin_material_supplier ON origin_material_supplier.origin_material_supplier_id = origin_material_supplier_rfid_tag.origin_material_supplier_id
                LEFT JOIN origin_material ON origin_material_supplier.origin_material_id = origin_material.origin_material_id
                LEFT JOIN supplier supplier_table ON supplier_table.supplier_id = origin_material_supplier.supplier_id
                LEFT JOIN purchase_order ON purchase_order.id = origin_material_supplier.purchase_order_id
            )       
        ";
        $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':antenna_id', $params['antenna_id'], PDO::PARAM_INT);
        if ($stmt->execute($params)) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row_key => $row) {
                foreach ($row as $key => $value) {
                    if ($this->isJson($value)) {
                        $result[$row_key][$key] = json_decode($value, true);
                    }
                }
            }
            return $result;
        } else {
            $result = [
                "status" => "failed",
            ];
        }
        return $result;
    }
    function recordOriginalMaterialSupplier($data, $original_material_supplier_id)
    {
        $bind_values = [
            'request_user_id' => '',
            'response_user_id' => '',
            'origin_material_supplier_id' => '',
            'request_file_id' => '',
            'receiver_file_id' => '',
        ];
        if (array_key_exists('request', $data)) {
            $request = $data['request'];
        }
        if (array_key_exists('response', $data)) {
            $response = $data['response'];
        }
        if ($original_material_supplier_id !== null) {
            $bind_values['origin_material_supplier_id'] = $original_material_supplier_id;
        }
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $request)) {
                $bind_values[$key] = $request[$key];
            } else if (array_key_exists($key, $response)) {
                $bind_values[$key] = $response[$key];
            }
        }
        $sql = "INSERT INTO public.origin_material_handler(
            request_user_id, response_user_id, origin_material_supplier_id, request_file_id, receiver_file_id, receive_time)
            VALUES (:request_user_id, :response_user_id, :origin_material_supplier_id, :request_file_id, :receiver_file_id, current_timestamp)
        ";

        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($bind_values)) {
            return ['status' => 'success'];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    function recordSupplierFile($data)
    {
        $bind_values = [
            'origin_material_supplier_id' => '',
            'file_id' => '',
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $bind_values[$key] = $data[$key];
            }
        }
        $sql = "UPDATE origin_material_supplier_rfid_tag
            SET file_id = :file_id
            WHERE origin_material_supplier_id = :origin_material_supplier_id
        ";

        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($bind_values)) {
            return ['status' => 'success'];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    function recordOriginalMaterialSupplierAmount($data)
    {
        $select_amount = [
            'origin_material_supplier_id' => '',
        ];
        $need_insert_rfid = [
            'amount' => 0,
        ];
        foreach ($select_amount as $key => $value) {
            if (array_key_exists($key, $data)) {
                $select_amount[$key] = $data[$key];
            }
        }
        foreach ($need_insert_rfid as $key => $value) {
            if (array_key_exists($key, $data)) {
                $need_insert_rfid[$key] = $data[$key];
            }
        }
        $sql = "SELECT origin_material_supplier.origin_material_supplier_id, COALESCE(origin_material_supplier_tag_count,0) origin_material_supplier_tag_count
            FROM origin_material_supplier
            LEFT JOIN (
                SELECT origin_material_supplier_rfid_tag.origin_material_supplier_id, COUNT(*) origin_material_supplier_tag_count
                FROM origin_material_supplier_rfid_tag
                GROUP BY origin_material_supplier_rfid_tag.origin_material_supplier_id
                ORDER BY origin_material_supplier_rfid_tag.origin_material_supplier_id
            )origin_material_supplier_rfid_tag ON origin_material_supplier.origin_material_supplier_id = origin_material_supplier_rfid_tag.origin_material_supplier_id
            WHERE origin_material_supplier.origin_material_supplier_id = :origin_material_supplier_id
            ORDER BY origin_material_supplier.origin_material_supplier_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($select_amount)) {
            $origin_material_supplier_tag_count = $stmt->fetchColumn(1);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
        $need_insert_rfid_count = $need_insert_rfid['amount'] - $origin_material_supplier_tag_count;
        if ($need_insert_rfid_count > 0) {
            for ($i = 0; $i < $need_insert_rfid_count; $i++) {
                $sql = "INSERT INTO public.rfid_tag(rfid_tag)
                    VALUES ('123')
                    RETURNING rfid_tag_id
                ";

                $stmt = $this->db->prepare($sql);
                if ($stmt->execute()) {
                    $return_rfid_tag_id = $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['rfid_tag_id'];
                    $sql = "INSERT INTO public.origin_material_supplier_rfid_tag(origin_material_supplier_id, rfid_tag_id)
                        VALUES (:origin_material_supplier_id, :rfid_tag_id)
                    ";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindValue(':origin_material_supplier_id', $select_amount['origin_material_supplier_id'], PDO::PARAM_INT);
                    $stmt->bindValue(':rfid_tag_id', $return_rfid_tag_id, PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    return [
                        'status' => 'failure',
                        'error_info' => $stmt->errorInfo()
                    ];
                }
            }
        } else if ($need_insert_rfid_count === 0) {
            return;
        } else {
            for ($i = 0; $i > $need_insert_rfid_count; $i--) {
                $sql = "DELETE FROM public.origin_material_supplier_rfid_tag
                    WHERE (origin_material_supplier_rfid_tag.origin_material_supplier_id, origin_material_supplier_rfid_tag.rfid_tag_id) IN (
                        SELECT origin_material_supplier_rfid_tag.origin_material_supplier_id, MIN(rfid_tag_id) rfid_tag_id
                        FROM origin_material_supplier_rfid_tag
                        WHERE origin_material_supplier_rfid_tag.origin_material_supplier_id = :origin_material_supplier_id
                        GROUP BY origin_material_supplier_rfid_tag.origin_material_supplier_id
                    )
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':origin_material_supplier_id', $select_amount['origin_material_supplier_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    function patchOriginalMaterialSupplier($data){
        $data = '{
            "origin_material_supplier": [
                {
                    "checked": true,
                    "count_rfid": "2",
                    "origin_material_supplier_id": 1,
                    "file_id": 75
                }
            ],
            "supplier": {
                "user_id": 88,
                "file_id": 73
            },
            "staff": {
                "user_id": 7,
                "file_id": 72
            }
        }';
        $data = json_decode($data,true);
        if(empty($data['origin_material_supplier'])){
            return [
                "status" => "failed",
                "message" => "請勾選貨物"
            ];
        }
        if(empty($data["supplier"])){
            return [
                "status" => "failed",
                "message" => "請送貨員簽名"
            ];
        }
        if(empty($data["staff"])){
            return [
                "status" => "failed",
                "message" => "請收貨員簽名"
            ];
        }
        $user = [
            "request_user_id" => 0,
            "request_file_id" => 0,
            "receiver_user_id" => 0,
            "receiver_file_id" => 0
        ];
        $user_temp = $this->getOriginalMaterialSupplierRequestUser($data['supplier']);
        $user_temp += $this->getOriginalMaterialSupplierReceiverUser($data['staff']);
        /*  */
        foreach ($user as $key => $value) {
            if(!array_key_exists($key,$user_temp))
                return [
                    "status" => "failed",
                    "message" => "請確認簽名"
                ];
        }
        $user = $user_temp;
        /*  */
        foreach($data['origin_material_supplier'] as $oms){
            $values = [
                "origin_material_supplier_id"=>null,
                "amount"=>0
            ];
            if(array_key_exists("origin_material_supplier_id",$oms)){
                $values["origin_material_supplier_id"] = $oms["origin_material_supplier_id"];
            }
            if(array_key_exists("count_rfid",$oms)){
                $values["amount"] = intval($oms["count_rfid"]);
            }
            if(is_null($values["origin_material_supplier_id"])){
                continue;
            }
            $this->recordOriginalMaterialSupplierAmount($values);
            $values += $user;
            unset($values["amount"]);
            $sql = "INSERT INTO 
                    public.origin_material_handler(request_user_id, receiver_user_id, origin_material_supplier_id, request_file_id, receiver_file_id)
                VALUES (:request_user_id, :receiver_user_id, :origin_material_supplier_id, :request_file_id, :receiver_file_id);
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
        }
        return [
            "status" => "success"
        ];
    }
    function getOriginalMaterialSupplierRequestUser($data){
        $values = [];
        if(array_key_exists("user_id",$data)){
            $values['request_user_id'] = $data['user_id'];
        }
        if(array_key_exists("file_id",$data)){
            $values['request_file_id'] = $data['file_id'];
        }
        return $values;
    }
    function getOriginalMaterialSupplierReceiverUser($data){
        $values = [];
        if(array_key_exists("user_id",$data)){
            $values['receiver_user_id'] = $data['user_id'];
        }
        if(array_key_exists("file_id",$data)){
            $values['receiver_file_id'] = $data['file_id'];
        }
        return $values;
    }
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
