<?php

use \Psr\Container\ContainerInterface;
use Slim\Http\UploadedFile;


use function Complex\ln;

class Quotationbusiness
{
    protected $container;
    protected $db;
    protected $db_sqlsrv;

    
    // constructor receives container instance
    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->db = $container->db;
        $this->db_sqlsrv = $container->db_sqlsrv;
        $this->quotation_module_id = 1;
    }

    

    public function getProcess_cost($data)
    {
        $sql = "SELECT *
             FROM public.process_cost
            WHERE  file_id=:file_id
            ORDER BY sequence";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if($stmt->rowCount()==0){
            $sql = "SELECT process_cost.name,process_cost.cost,:file_id file_id ,process_cost , sequence
                FROM process_cost
                WHERE process_cost.file_id = (
                    SELECT process_cost.file_id
                    FROM process_cost
                    LEFT JOIN file ON file.id = process_cost.file_id
                    WHERE file.order_name = (
                        SELECT file.order_name
                        FROM file
                        WHERE file.id = :file_id
                    )
                    GROUP BY process_cost.file_id
                    ORDER BY process_cost.file_id DESC
                    LIMIT 1
                )
                ORDER BY sequence
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':file_id', $data['file_id']);
            $stmt->execute();
            $result = [
                "file_id"=>$data['file_id'],
                "arr"=>$stmt->fetchAll()
            ];
            $this->postProcess_cost($result);
            $result = $result["arr"];
        }
        return $result;
    }

    public function postProcess_cost($data)
    {
        $sql = "DELETE FROM public.process_cost
            WHERE file_id=:file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();


        
        $tmpStr = "";
        $tmpArr = [] ;
        foreach ($data['arr'] as $key => $value) {
            $values  = [
                "name" => "",
                "cost" => 0 ,
                "file_id" => 0,
                "sequence" => 0
    
            ];

            if(array_key_exists("file_id",$data)){
                $values["file_id"] = $data["file_id"];
            }

            $tmpStr .= "(?,?,?,?),";
            foreach ($values as $valkey => $value) {
                if(array_key_exists($valkey,$data['arr'][$key])){
                    $values[$valkey] = ($data['arr'][$key][$valkey]);
                }
                array_push($tmpArr, $values[$valkey] );
            }
            
        }

        $tmpStr = substr_replace($tmpStr, "", -1);
        $sql = "INSERT INTO public.process_cost(
            name, cost, file_id , sequence)
            VALUES  {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        // return  $tmpArr ;
        if($stmt->execute($tmpArr)){
            return ["status" => "success"];
        }else {
            // var_dump($stmt->errorInfo());
            return["status" => "failed"];
        }
        
    }

    public function getFileName($data){

        $values  = [
            "file_id" => 0,
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }

        $sql = "SELECT *
        FROM public.file
        WHERE id = :file_id";
        $stmt = $this->db->prepare($sql);
        if( $stmt->execute($values)){
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result[0]['FileName'];
        }
        
    }

    public function postMaterialSequence($data){
        $this-> postMaterial($data , 'material');
        $this-> postMaterial($data , 'titanizing');
        $this-> postMaterial($data , 'hardness');
        return $data;
    } 
    public function postMaterial($data , $type){
        $sql = "DELETE FROM public.{$type}
        WHERE file_id=:file_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();

        $tmpstr =  "";
        $tmparr =  array();
        foreach ($data['sequence'] AS $key => $value){

            array_key_exists($type,$value)&&array_push($tmparr, $data['file_id'] ,$value[$type] ,$value['index'])&&$tmpstr .=" (?,?,?),";
        }
        $tmpstr = substr_replace($tmpstr, "", -1);

        if ($tmpstr ==  "") {
            return;
        }
        $sql = "INSERT INTO public.{$type}(file_id, {$type}_id,sequence)
        VALUES {$tmpstr}
        ON CONFLICT (file_id, {$type}_id) 
        DO NOTHING;
        ";
        $stmt = $this->db->prepare($sql);
        if( $stmt->execute($tmparr)){
            return ;
        }
    }


    public function patchUpdateFileName($data){
        $values  = [
            "file_id" => 0,
            "file_name" => '',
            "client_name" => ''

        ];
        // var_dump($data);
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        $sql = "UPDATE public.file
        SET \"ClientName\"=:client_name , \"FileName\"= :file_name , update_time = NOW()
        WHERE id = :file_id";
         $stmt = $this->db->prepare($sql);
         if ($stmt->execute($values)) {
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

    public function deleteFile($data){

        $values  = [
            "file_id" => 0

        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }

        $sql="DELETE FROM public.file
        WHERE id=:file_id;";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
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


    public function postFinishProgress($data){
        $values  = [
            "quotation_business_id" => 0,
            "module_id" => 0 

        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }

        $sql = "INSERT INTO public.progress(file_id, progress_id,update_time , later)
        SELECT file.id AS file_id ,setting_progress.id AS progress_id , NOW() , false
        FROM  quotation_business.quotation 
        LEFT JOIN public.file ON file.quotation_business_id = quotation.quotation_business_id
        CROSS jOIN  setting.progress AS setting_progress
        LEFT JOIN(
            SELECT * 
            FROM (
                SELECT ROW_NUMBER() OVER (PARTITION BY file_id,progress_id ORDER BY update_time DESC) as RowNum, *
                FROM public.progress
                GROUP BY file_id ,update_time,progress_id
            ) AS progress
            WHERE RowNum = 1 
        )AS progress ON progress.file_id = file.id AND progress.progress_id = setting_progress.id
        WHERE quotation.quotation_business_id = :quotation_business_id AND setting_progress.module_id = :module_id AND (progress.later IS NULL OR progress.later = false)";
        $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':mail_id', $data['mail_id']);
        if ($stmt->execute($values)) {
            $result = [
                "status" => "success",
            ];
        } else {
            $result = [
                "status" => "failed",
            ];
        }
        // var_dump($result);
        return $result;
    }

    public function getTmpid($data){
        $tmpStr = '(';
        foreach ($data['other'] as $key => $value) {
            $tmpStr .= "{$value},";
        }
        $tmpStr = substr_replace($tmpStr, ")", -1);
        $sql = "SELECT tmpfile.tmpid, quotation.*,quotation_business.type AS type_name
        FROM quotation_business.quotation 
        LEFT JOIN (
            SELECT quotation_business_id AS id, to_char(create_time::timestamp,'YYYYMMDD') || '-' || to_char(ROW_NUMBER () OVER (
                    PARTITION BY to_char(create_time::timestamp,'DD-MM-YYYY')
                    ORDER BY
                        quotation_business_id ASC
                ), 'FM0000') AS tmpid
            FROM quotation_business.quotation 
        )AS tmpfile  ON quotation.quotation_business_id = tmpfile.id
        LEFT jOIN setting.quotation_business ON quotation.type = quotation_business.id
        WHERE quotation.quotation_business_id in {$tmpStr}
        order by quotation.quotation_business_id  ASC;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getNotify_finish($data){
        $values  = [
            "module_id" => 0,
            "quotation_business_id" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }
        $sql="SELECT notify_finish.notify
        FROM quotation_business.quotation
        LEFT JOIN public.file ON quotation.quotation_business_id = file.quotation_business_id
        LEFT JOIN public.notify_finish ON notify_finish.file_id = file.id AND finish = :module_id
        WHERE notify_finish.notify IS NOT NULL AND file.quotation_business_id = :quotation_business_id
        GROUP BY notify_finish.notify";
        $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':mail_id', $data['mail_id']);
        if ($stmt->execute($values)) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $result = [
                "status" => "failed",
            ];
        }
        $tmpresult=[];
        if(count($result) > 0){
            foreach($result AS $key => $value){
                array_push($tmpresult,$value['notify']);
            }
        }
        return $tmpresult;
    }

    public function get_quotation_business_id_bymailid($data){
        $values  = [
            "mail_id" => 0

        ];
        foreach ($data as $row) {
            foreach ($values as $key => $value) {
                if(array_key_exists($key,$row)){
                    $values[$key] = intval($row[$key]);
                    goto ENDTOFIND;
                }
            }
        }
        ENDTOFIND:
        $sql="SELECT quotation_business_id
        FROM quotation_business.quotation
        WHERE mail_id = :mail_id";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            $result = [
                "status" => "success",
            ];
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($data AS $key => $value){
                $data[$key]['quotation_business_id'] = $result[0]['quotation_business_id'];
            } 
            return $data;

        } else {
            $result = [
                "status" => "failed",
            ];
        }
        return $result;
    }

    public function getFinishDetail($data){
        $business = new Business($this->db);
        $result = $business->getCustomerCodes();
        $customer_code = json_encode($result);
        $result = $business->getMaterial([]);
        $material = json_encode($result);
        $result = $business->getTitanizing([]);
        $titanizing = json_encode($result);
        $result = $business->getHardness([]);
        $hardness = json_encode($result);
        $result = $business->getCC();
        $customerCode = json_encode($result);

        $values = [
            "quotation_business_id" => $data['quotation_business_id']
        ];
        
        
        $sql = "WITH tmpcost AS(SELECT crop_id AS file_id ,module_id ,module.name,
            case when module_id = 3 then SUM ( NULLIF(process, '')::int ) end as rd_cost,
            case when module_id = 4 then SUM ( NULLIF(process, '')::int ) end as technical_cost,
            case when module_id = 5 then SUM ( NULLIF(process, '')::int ) end as production_cost
        FROM comment_process
        LEFT JOIN public.process_mapping ON comment_process.process_mapping_id = process_mapping.id
        LEFT JOIN setting.module ON module.id = comment_process.module_id
        GROUP BY crop_id,module_id,module.name)
        SELECT quotation.*, to_char(quotation.create_time, 'YYYY-MM-DD HH24:MI:SS') AS create_time , customer_outer_code.customer_outer_name,urgent.urgent_name,
            JSON_AGG(
                JSON_BUILD_OBJECT(
                    'file_id', file.id,
                    'itemno' , file.itemno,
                    'custom_material',file.custom_material,
                    'custom_titanizing',file.custom_titanizing,
                    'rd_cost',file.rd_cost,
                    'technical_cost',file.technical_cost,
                    'production_cost',file.production_cost,
                    'technical_process_cost',file.sumtech,
                    'production_process_cost',file.summanage,
                    
                    'quotation',file.quotation,
                    'material_label',file.material_label,
                    'titanizing_label',file.titanizing_label,
                    'hardness_label',file.hardness_label,
                    'suggestion',file.suggestion
                )
            ) file
        FROM quotation_business.quotation AS quotation
        LEFT JOIN (
            SELECT urgent_id, urgent_name
            FROM quotation_business.urgent
        )AS urgent ON urgent.urgent_id = quotation.urgent_id
        LEFT JOIN (
            SELECT \"客戶代號\" AS customer_outer_code ,\"客戶名稱\" AS customer_outer_name
                FROM json_to_recordset(
                    '{$customer_code}'
                ) as setting_customer_code(\"客戶代號\" text,\"客戶名稱\" text)
        ) AS customer_outer_code  ON trim(quotation.customer) = trim(customer_outer_code.customer_outer_code)
        LEFT JOIN(
            SELECT file.id,file.itemno,file.order_name,file.id AS file_id ,file.\"FileName\",file.quotation_business_id,attach_file.*,
                    material.material_label,material.material,titanizing.titanizing,titanizing.titanizing_label,
                    hardness.hardness,hardness.hardness_label,
                    CONCAT('/file/',file.id :: TEXT) AS img,suggestion.suggestion,
                    tmpfile.tmpid,quotation.quotation,file.custom_material,file.custom_titanizing, 
                    cost.technical_cost,cost.production_cost,cost.rd_cost,modify_process.sumtech,modify_process.summanage
            FROM public.file
            LEFT JOIN (
                SELECT file_id, COALESCE(SUM(NULLIF(cost, '')::BIGINT),0) AS sumtech ,COALESCE(SUM(NULLIF(outsourcer_cost, '')::BIGINT),0) AS summanage
                FROM(
                    SELECT file_id,cost,outsourcer_cost
                    FROM public.modify_process
                    UNION ALL(
                        SELECT file_id,cost,''
                        FROM public.process_cost
                    )   
                )modify_process
                GROUP BY file_id
            )AS modify_process ON modify_process.file_id=file.id
            LEFT JOIN(
                select
                    file_id,
                    coalesce(sum(technical_cost), 0) as technical_cost,
                    coalesce(sum(production_cost), 0) as production_cost,
                    coalesce(sum(rd_cost), 0) as rd_cost
                    
                from tmpcost
                group by file_id
            )AS cost ON cost.file_id = file.id
            LEFT JOIN(
                SELECT file_id,JSON_AGG(
                                    JSON_BUILD_OBJECT(
                                        'title', title,
                                        'type',type,
                                        'suggestion',suggestion_value.suggest_detail
                                    )
                                ) suggestion
                    FROM public.suggestion

                    LEFT JOIN (
                        SELECT suggest_id,JSON_AGG(JSON_BUILD_OBJECT('process_mapping_id', process_mapping_id,'val',val)) suggest_detail
                        FROM public.suggestion_value
                        GROUP BY suggest_id
                    )AS suggestion_value  ON suggestion_value.suggest_id = suggestion.id
                    WHERE type='rd'
                    GROUP BY file_id

            ) AS suggestion ON suggestion.file_id = file.id
            LEFT JOIN(
                SELECT file_id, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'update_time', update_time,
                            'descript', descript,
                            'discount', discount,
                            'update_time', update_time,
                            'cost', cost,
                            'deadline', deadline,
                            'delivery_week', delivery_week,
                            'currency', currency,
                            'delivery_range', delivery_range
                        )) AS quotation
                FROM public.quotation
                GROUP BY file_id
            )AS quotation ON quotation.file_id = file.id
            LEFT JOIN (
                SELECT attach_file_id, file_id, file_name, upload_time
	            FROM quotation_business.attach_file
            )AS  attach_file ON attach_file.file_id = file.id
            LEFT JOIN (
                SELECT id, to_char(upload_time::timestamp,'YYYYMMDD') || '-' || to_char(ROW_NUMBER () OVER (
                        PARTITION BY to_char(upload_time::timestamp,'DD-MM-YYYY') 
                        ORDER BY
                            id ASC
                    ), 'FM0000') AS tmpid
                FROM file 
            )AS tmpfile  ON file.id = tmpfile.id
            
            LEFT JOIN(
                SELECT file_id,  string_agg(setting.label, ',') as material_label, JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'material_id', material_id
                    )
                ) material
                FROM  public.material
                LEFT JOIN (
                    SELECT label, trim(value) AS value,value AS origin_value
                    FROM json_to_recordset(
                        '{$material}'
                    ) as setting_material(label text,value text)
                )AS setting ON setting.value = material.material_id
                GROUP BY file_id
            )AS material ON material.file_id = file.id
            LEFT JOIN(
                SELECT file_id,string_agg(setting.label, ',') as titanizing_label, JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'titanizing_id', titanizing_id
                    )
                ) titanizing
                FROM  public.titanizing
                LEFT JOIN (
                    SELECT label, trim(value) AS value,value AS origin_value
                    FROM json_to_recordset(
                        '{$titanizing}'
                    ) as setting_titanizing(label text,value text)
                )AS setting ON setting.value = titanizing.titanizing_id
                GROUP BY file_id
            )AS titanizing ON titanizing.file_id = file.id
            LEFT JOIN(
                SELECT file_id,string_agg(setting.label, ',') as hardness_label, JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'hardness_id', hardness_id
                    )
                ) hardness
                FROM  public.hardness
                LEFT JOIN (
                    SELECT label, trim(value) AS value
                    FROM json_to_recordset(
                        '{$hardness}'
                    ) as setting_hardness(label text,value text)
                    UNION ALL(
                        SELECT  name AS label , id :: TEXT AS value 
                        FROM public.common_hardness
                    )
                )AS setting ON setting.value = hardness.hardness_id
                GROUP BY file_id
            )AS hardness ON hardness.file_id = file.id
            
           
        )AS file ON file.quotation_business_id =  quotation.quotation_business_id
        WHERE quotation.quotation_business_id = :quotation_business_id 
        
        GROUP BY quotation.quotation_business_id,customer_outer_code.customer_outer_name,urgent.urgent_name
       
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row_key => $row) {
                foreach ($row as $key => $value) {
                    if ($this->isJson($value)) {
                        $result[$row_key][$key] = json_decode($value, true);
                    }
                }
            }
            return $result;
        }else{
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }

    public function getQuotationBusiness($data){
        $business = new Business($this->db);
        $result = $business->getCustomerCodes();
        $customer_code = json_encode($result);

        $values  = [
            "type" => 0,
            "finish" => 'false',
            'date_start'=>'',
            'date_end'=>''
        ];
        // var_dump($data);
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }

        $query = '';
        if ($values['date_start'] != '' || $values['date_end'] != '') {
            // AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
            if ($values['date_start'] == '') {
                $starttime = 'NOW()::date';
                unset($values['date_start']);
            } else {
                $starttime = ":date_start";
            }
            if ($values['date_end'] == '') {
                $endtime = 'NOW()::date';
                unset($values['date_end']);
            } else {
                $endtime = ":date_end::date+ interval '1' day";
            }
            
            $query .= " AND  (quotation.create_time BETWEEN {$starttime} AND {$endtime}) ";
        }else{
            unset($values['date_start']);
            unset($values['date_end']);
        }
        // var_dump($query );
        // var_dump($values );
        
        $sql = "WITH finish_progress AS(SELECT quotation.quotation_business_id, count(*)
                FROM setting.module
                CROSS JOIN public.file
                LEFT JOIN quotation_business.quotation ON file.quotation_business_id = quotation.quotation_business_id
                LEFT JOIN setting.progress AS setting ON setting.module_id = module.id
                
                LEFT JOIN(
                    SELECT file_id, progress.progress_id, update_time, later
                    FROM (
                        SELECT ROW_NUMBER() OVER (PARTITION BY file_id,progress_id ORDER BY update_time DESC) as RowNum, *
                        FROM public.progress
                        GROUP BY file_id ,update_time,progress_id
                    ) AS progress
                    LEFT JOIN(
                        SELECT *
                        FROM setting.progress
                    )AS setting ON progress.progress_id=setting.id
                    WHERE progress.RowNum = 1 
                
                    GROUP BY file_id,progress.progress_id, update_time, later
                )AS progress ON progress.progress_id = setting.id AND progress.file_id = file.id
                WHERE later IS NULL 
                GROUP BY quotation.quotation_business_id
            )
            , finalprogress AS (
            SELECT quotation.quotation_business_id,module_id , update_time,module_name,
                ROW_NUMBER() OVER (Partition BY quotation.quotation_business_id,module_id
                                   ORDER BY (CASE WHEN update_time IS  NULL THEN 1 ELSE 2 END),
                                            update_time DESC
                                  ) AS finalnum
            FROM(
                SELECT file.id AS file_id,module.id AS module_id,file.quotation_business_id , progress.update_time,progress.later,module.name AS module_name,
                        ROW_NUMBER() OVER (Partition BY module.id,file.id
                                   ORDER BY (CASE WHEN progress.update_time IS  NULL THEN 1 ELSE 2 END),
                                            progress.update_time DESC
                                  ) AS rownum
                FROM setting.module
                CROSS JOIN public.file
                LEFT JOIN setting.progress AS setting ON setting.module_id = module.id
                LEFT JOIN(
                    SELECT file_id, progress.progress_id, update_time, later
                    FROM (
                        SELECT ROW_NUMBER() OVER (PARTITION BY file_id,progress_id ORDER BY update_time DESC) as RowNum, *
                        FROM public.progress
                        GROUP BY file_id ,update_time,progress_id
                    ) AS progress
                    LEFT JOIN(
                        SELECT *
                        FROM setting.progress
                    )AS setting ON progress.progress_id=setting.id
                    WHERE progress.RowNum = 1 
                    
                    GROUP BY file_id,progress.progress_id, update_time, later
                ) AS progress ON progress.progress_id = setting.id AND progress.file_id = file.id
                
                WHERE module.id IN (2,3,4)
                GROUP BY file.id, module.id ,progress.update_time,progress.later,file.quotation_business_id,module.name
                ORDER BY file.id,module.id
            )AS allprogress
            LEFT JOIN  quotation_business.quotation ON quotation.quotation_business_id = allprogress.quotation_business_id

            WHERE allprogress.rownum=1
            GROUP BY quotation.quotation_business_id,module_id , update_time,module_name
            )


            SELECT quotation.quotation_business_id,to_char(quotation.create_time, 'YYYYMMDD')  AS create_time,quotation.customer,quotation.customer_order_id,'' AS deliveryweek,
                customer_outer_code.customer_outer_name,
                JSON_AGG(JSON_BUILD_OBJECT('module_id',module.id,'module_name',module.name
                                          ,'update_time',CASE WHEN update_time IS NULL THEN '未完成' ELSE to_char(update_time, 'YYYY-MM-DD HH24:MI:SS') END)) AS progress,
                quotation.urgent_id,urgent.urgent_name
            FROM quotation_business.quotation
            CROSS JOIN setting.module 
            INNER JOIN (
                SELECT finalprogress.*,CASE WHEN finish_progress.count=0 THEN 'true' ELSE 'false' END AS finish
                FROM finalprogress
                LEFT JOIN finish_progress ON finish_progress.quotation_business_id = finalprogress.quotation_business_id

                WHERE finalnum = 1
            )AS finalprogress ON finalprogress.quotation_business_id = quotation.quotation_business_id AND module.id = finalprogress.module_id
            LEFT JOIN (
                SELECT \"客戶代號\" AS customer_outer_code ,\"客戶名稱\" AS customer_outer_name
                    FROM json_to_recordset(
                        '{$customer_code}'
                    ) as setting_customer_code(\"客戶代號\" text,\"客戶名稱\" text)
            ) AS customer_outer_code  ON trim(quotation.customer) = trim(customer_outer_code.customer_outer_code)
            LEFT JOIN quotation_business.urgent ON quotation.urgent_id = urgent.urgent_id
            WHERE module.id IN (2,3,4) AND quotation.type= :type 
                AND finalprogress.finish = :finish {$query}
            GROUP BY  quotation.quotation_business_id,quotation.create_time,quotation.customer,quotation.customer_order_id,customer_outer_code.customer_outer_name,quotation.urgent_id,urgent.urgent_name
            ORDER BY quotation.create_time DESC
        ";
        $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':quotation_business_id', $data['quotation_business_id']);
        // $stmt->bindValue(':type', $data['type']);
        if ($stmt->execute($values)) {
            $result =  $stmt->fetchAll();;
        } else {
            var_dump($stmt->errorInfo());
            $result = [
                "status" => "failed",
            ];
        }
        foreach ($result as $row_key => $row) {
            foreach ($row as $key => $value) {
                if ($this->isJson($value)) {
                    $result[$row_key][$key] = json_decode($value, true);
                }
            }
        }
        return $result;

    }

    public function get_attach_frame($data){
        $sql = "SELECT file_position.position_id, canvas_width, canvas_height,
                file_position.file_position_code, draw_type, brush_color,
                COALESCE(point.point_list,'[]') point_list, \"drawRectArea\",
                COALESCE(file_position.sequence, 0) AS sequence
            FROM public.file_position
            LEFT JOIN  public.\"position\" ON position.position_id = file_position.position_id
            LEFT JOIN (
                SELECT point.position_id,JSON_AGG(point.* ORDER BY point.index) point_list
                FROM public.point
                GROUP BY point.position_id
            )point ON point.position_id = position.position_id  
            WHERE file_position.file_id = :file_id
            ORDER BY sequence,file_position.position_id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['attach_file_id']);
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

    public function getRef_file($data){
        $sql="SELECT file.id ,file.order_name , quotation.customer
        FROM public.file
        LEFT JOIN quotation_business.quotation ON quotation.quotation_business_id = file.quotation_business_id
        WHERE customer_order_id = :customer_order_id AND quotation.type=1 ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':customer_order_id', $data['customer_order_id']);
        if ($stmt->execute()) {
            $result =  $stmt->fetchAll();;
        } else {
            $result = [
                "status" => "failed",
            ];
        }
        return $result;

    }

    public function delete_attach_paint($data)
    {
        $values  = [
            "attach_file_id" => 0,
            "sequence" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        $sql = "DELETE FROM public.file_paint
            WHERE file_id = :attach_file_id AND sequence=:sequence
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
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

    public function post_attach_file_paint($data)
    {
        // var_dump($data);
        $values  = [
            "attach_file_id" => 0,
            "file_id" => 0,
            "sequence" => 0

        ];
        // var_dump($data);
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }
        $sql = "INSERT INTO public.file_paint(
            file_id, phasegallery_file_id,sequence)
            VALUES (:attach_file_id, :file_id ,:sequence);
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values )) {
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
    public function get_attach_file_paint($data)
    {
        $sql = "SELECT file_paint.file_id,file.file_name,COALESCE(file_paint.sequence, 0) AS sequence
            FROM public.file_paint
            LEFT JOIN phasegallery.file ON file_paint.phasegallery_file_id = file.file_id
            WHERE file_paint.file_id = :attach_file_id
            GROUP BY file_paint.file_id,file.file_name,file_paint.sequence
            ORDER BY file_paint.sequence
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':attach_file_id', $data['attach_file_id']);
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $index => $row) {
                foreach ($row as $key => $value) {
                    if($key==='file_name'){
                        $filepath = $this->container->upload_directory.DIRECTORY_SEPARATOR.$value;
                        if(file_exists($filepath)){
                            $type = pathinfo($filepath, PATHINFO_EXTENSION);
                            $data = file_get_contents($filepath);
                            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            $result[$index][$key] = $base64;
                        }else{
                            $result[$index][$key] = '';
                        }
                    }
                }
            }
        } else {

            $result = [
                "status" => "failed",
            ];
        }
        return $result;
    }

    public function create_complaint_position($params)
    {
        $values = [
            "attach_file_id" => 0,
            "position_id" => 0,
            "drawRectArea" => 0,
            'attach_file_position_code' => '',
            "sequence" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$params)){
                $values[$key] = intval($params[$key]);
            }
        }
        $sql = "INSERT INTO public.file_position (file_id, position_id, \"drawRectArea\", file_position_code , sequence)
                VALUES (:attach_file_id, :position_id, :drawRectArea, :attach_file_position_code ,:sequence)
                RETURNING id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return [
                "status" => "success",
                "id" => $stmt->fetch(PDO::FETCH_ASSOC)["id"]
            ];
        } else {
            return ["status" => "failed",];
        }
    }

    public function updateDeliveryMeetContentPosition($data)
    {
        $crm = new CRM($this->db);
        $values = [
            "attach_file_id" => 0,
            "position" => []
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        // var_dump($values);

        /*  */
        $position = array_filter($values['position'],function($position){
            return array_key_exists('position_id',$position);
        });

        $stmt_array = [$values['attach_file_id']];


        $stmt_array_position = array_map(function($position){
                return $position['position_id'];
        },$position);
        $stmt_array = array_merge($stmt_array, $stmt_array_position);

        $stmt_string = "";
        if(count($stmt_array_position)!==0){
            $stmt_string = implode(',',array_fill(0,count($position),'?'));
            $stmt_string = " AND  position_id NOT IN ({$stmt_string})";
        }
        // var_dump($stmt_string);
        // var_dump($stmt_array);
        

        $sql = "DELETE FROM \"position\"
            WHERE position_id IN (
                SELECT position_id
                FROM public.file_position
                WHERE file_id = ? {$stmt_string}
            );
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute($stmt_array)) {

            return [
                "status" => "failure"
            ];
        }
        $sql = "DELETE FROM public.file_position
            WHERE file_id = ? {$stmt_string}
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
                $position_['attach_file_id'] = $values['attach_file_id'];
                $result = $this->create_complaint_position($position_);
                $result['position_id'] = $position_['position_id'];
            }else{
                $sql = "UPDATE public.file_position
                    SET file_position_code = :attach_file_position_code , sequence = :sequence
                    WHERE position_id = :position_id;
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':attach_file_position_code', $position_['attach_file_position_code']);
                $stmt->bindValue(':position_id', $position_['position_id']);
                $stmt->bindValue(':sequence', $position_['sequence']);
                if (!$stmt->execute()) {
                    return ["status" => "failed"];
                }
            }
        }
        /*  */
        return $values;
    }

    public function copyFileDetail($data){
        $quotation_business_id = $this->insertCopyQuotation_bussiness($data);
        // var_dump($quotation_business_id);
        foreach ($data AS $key => $value){
            $value['quotation_business_id'] = intval($quotation_business_id);
            $this->insertCopyFile($value);
            $this->insertCopyFile_comment($value);
            $this->insertCopyMaterial($value);
            $this->insertCopyHardness($value);
            $this->insertCopyTitanizing($value);
            $this->insertCopyModify_process($value);
            $this->insertCopyProcess_cost($value);
        }
        return;
    }

    public function insertCopyProcess_cost($data){
        $sql = "INSERT INTO public.process_cost(file_id,name, cost)
        SELECT :newfile_id, name, cost
        FROM  public.process_cost
        WHERE file_id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['ref_file_id']);
        $stmt->bindValue(':newfile_id', $data['file_id']);
        $stmt->execute();
        return  ;
    }

    public function insertCopyModify_process($data){
        $sql = "INSERT INTO public.modify_process( file_id, component_id, process_id, num, code, name, mark, cost, outsourcer, deadline, outsourcer_cost)
        SELECT :newfile_id, component_id, process_id, num, code, name, mark, cost, outsourcer, deadline, outsourcer_cost
        FROM  public.modify_process
        WHERE file_id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['ref_file_id']);
        $stmt->bindValue(':newfile_id', $data['file_id']);
        $stmt->execute();
        return  ;
    }

    public function insertCopyTitanizing($data){
        $sql = "INSERT INTO public.titanizing( file_id, titanizing_id)
        SELECT :newfile_id, titanizing_id
        FROM  public.titanizing
        WHERE file_id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['ref_file_id']);
        $stmt->bindValue(':newfile_id', $data['file_id']);
        $stmt->execute();
        return  ;
    }

    public function insertCopyHardness($data){
        $sql = "INSERT INTO public.hardness( file_id, hardness_id)
        SELECT :newfile_id, hardness_id
        FROM  public.hardness
        WHERE file_id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['ref_file_id']);
        $stmt->bindValue(':newfile_id', $data['file_id']);
        $stmt->execute();
        return  ;
    }

    public function insertCopyMaterial($data){
        $sql = "INSERT INTO public.material( file_id, material_id)
        SELECT :newfile_id, material_id
        FROM  public.material
        WHERE file_id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['ref_file_id']);
        $stmt->bindValue(':newfile_id', $data['file_id']);
        $stmt->execute();
        return  ;
    }

    public function insertCopyFile_comment($data){
        $sql = "INSERT INTO public.file_comment(file_id, module_id, comment, canvas, \"time\")
        SELECT :newfile_id, module_id, comment, canvas, \"time\"
        FROM  public.file_comment
        WHERE file_id = :file_id
        ";
       
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['ref_file_id']);
        $stmt->bindValue(':newfile_id', $data['file_id']);
        $stmt->execute();
        return  ;
    }
    
    public function insertCopyFile($data){

        $sql = "UPDATE  public.file
        SET \"ClientName\"=subquery.\"ClientName\",
        \"FileName\"=subquery.\"FileName\",
        
        order_serial=subquery.order_serial,
        multiple=subquery.multiple,
        rotate=subquery.rotate,
        deadline=subquery.deadline,
        outsourcer=subquery.outsourcer,
        outsourcer_amount=subquery.outsourcer_amount,
        \"FileNameFactory\"=subquery.\"FileNameFactory\",
        customer=subquery.customer,
        delivery_date=subquery.delivery_date,
        itemno=subquery.itemno,
        delivery_week=subquery.delivery_week,
        tech_width=subquery.tech_width,
        fk=subquery.fk,
        lock=subquery.lock,
        custom_id=subquery.custom_id,
        custom_material=subquery.custom_material,
        custom_titanizing=subquery.custom_titanizing,
        weight=subquery.weight,
        upload_time=NOW(),
        quotation_business_id=:quotation_business_id ,
        parent = :parent

        FROM (SELECT *
            FROM  public.file
            WHERE id = :file_id) AS subquery
        WHERE  file.id  = :newfile_id;

        ";
       
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['ref_file_id']);
        $stmt->bindValue(':parent', $data['parent']);
        $stmt->bindValue(':newfile_id', $data['file_id']);
        $stmt->bindValue(':quotation_business_id', $data['quotation_business_id']);


        if (!$stmt->execute()) {

            return [
                "status" => "failure"
            ];
        }else{
            return;
        }
        // $stmt->execute();

    }
    public function insertCopyQuotation_bussiness($data){
        foreach ($data AS $key => $value){
            $sql = "INSERT INTO quotation_business.quotation( mail_id,deadline, urgent_id, file_name, customer, customer_order_id, overall_comment, create_time, type)

            SELECT  :mail_id, deadline, urgent_id, file_name, customer, customer_order_id, overall_comment, NOW() , 2
            FROM quotation_business.quotation
            WHERE customer_order_id =:customer_order_id AND type = 1;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':customer_order_id', $value['customer_order_id']);
            $stmt->bindValue(':mail_id', $value['mail_id']);
            $stmt->execute();
            return   $this->db->lastInsertId();
        }
        
    }

    public function getComponentMatch($data)
    {
        $business = new Business($this->db);
        $result = $business->getCustomerCodes();
        $customer_code = json_encode($result);
        $result = $business->getMaterial([]);
        $material = json_encode($result);
        $result = $business->getTitanizing([]);
        $titanizing = json_encode($result);
        $result = $business->getHardness([]);
        $hardness = json_encode($result);

        $sql = "WITH result AS (
                SELECT JSON_AGG(JSON_BUILD_OBJECT('crop_id',crop.id,'crop_img',CONCAT('/file/',crop_id),'confidence',result.confidence,'source',crop_org.id)) crop_ids,AVG(result.confidence) AVG,component.name,file.order_name,crop.\"fileID\",crop.\"fileID\" id,JSON_AGG(DISTINCT JSONB_BUILD_OBJECT('id', crop_org.id)) org_ids,comment_process.comment ,CONCAT('/file/',crop.\"fileID\") AS img, CASE WHEN comment_process.comment IS NULL THEN 0 ELSE 1 END AS checked,comment_process.material,comment_process.stuff,comment_process.process,comment_process.outsourcer_cost,comment_process.outsourcer_comment,file.order_serial,
                        material_label.material_label,titanizing_label.titanizing_label,hardness_label.hardness_label
                FROM result
				LEFT JOIN process ON process.id = result.process_id
                LEFT JOIN component ON process.component_id = component.id
                LEFT JOIN crop crop_org ON crop_org.\"name\" = result.source
                INNER JOIN crop ON crop.name = result.filename
                LEFT JOIN file ON file.id = crop.\"fileID\"
                LEFT JOIN public.process_mapping ON  process.id = process_mapping.process_id AND process_mapping.crop_id = crop.\"fileID\"
                LEFT JOIN comment_process ON comment_process.process_mapping_id = process_mapping.id AND comment_process.module_id = (
                    SELECT id
                    FROM setting.module
                    WHERE module.\"name\" = :module_name
                )
                
                LEFT JOIN(
                    SELECT file_id,  string_agg(setting.label, ',') as material_label, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'material_id', material_id
                        )
                    ) material
                    FROM  public.material
                    LEFT JOIN (
                        SELECT label, trim(value) AS value,value AS origin_value
                        FROM json_to_recordset(
                            '{$material}'
                        ) as setting_material(label text,value text)
                    )AS setting ON setting.value = material.material_id
                    GROUP BY file_id
                )AS material_label ON material_label.file_id = crop.\"fileID\"
                LEFT JOIN(
                    SELECT file_id,string_agg(setting.label, ',') as titanizing_label, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'titanizing_id', titanizing_id
                        )
                    ) titanizing
                    FROM  public.titanizing
                    LEFT JOIN (
                        SELECT label, trim(value) AS value,value AS origin_value
                        FROM json_to_recordset(
                            '{$titanizing}'
                        ) as setting_titanizing(label text,value text)
                    )AS setting ON setting.value = titanizing.titanizing_id
                    GROUP BY file_id
                )AS titanizing_label ON titanizing_label.file_id = crop.\"fileID\"
                LEFT JOIN(
                    SELECT file_id,string_agg(setting.label, ',') as hardness_label, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'hardness_id', hardness_id
                        )
                    ) hardness
                    FROM  public.hardness
                    LEFT JOIN (
                        SELECT label, trim(value) AS value
                        FROM json_to_recordset(
                            '{$hardness}'
                        ) as setting_hardness(label text,value text)
                        UNION ALL(
                            SELECT  name AS label , id :: TEXT AS value 
                            FROM public.common_hardness
                        )
                    )AS setting ON setting.value = hardness.hardness_id
                    GROUP BY file_id
                )AS hardness_label ON hardness_label.file_id = crop.\"fileID\"
                WHERE result.process_id = :process_id
                GROUP BY component.name,crop.\"fileID\",file.order_name,comment_process.comment,comment_process.material,comment_process.stuff,comment_process.process,comment_process.outsourcer_cost,comment_process.outsourcer_comment,file.order_serial,material_label.material_label,titanizing_label.titanizing_label,hardness_label.hardness_label
                ORDER BY MAX(result.confidence) DESC
            )
            SELECT result.id,result.img,material.material,titanizing.titanizing,hardness,result.material_label,result.titanizing_label,result.hardness_label,result.checked,result.crop_ids,result.avg,result.name,result.org_ids,result.\"fileID\",result.order_name,result.comment,result.stuff,result.process,result.outsourcer_cost,result.outsourcer_comment,result.order_serial
            FROM result
            LEFT JOIN  ( 
                SELECT file_id, JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'material_id', material_id
                    )) AS  material
                FROM  public.material
                GROUP BY file_id
            ) AS material ON material.file_id = result.id
            LEFT JOIN  ( 
                SELECT file_id, JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'titanizing_id', titanizing_id
                    )) AS  titanizing
                FROM  public.titanizing
                GROUP BY file_id
            ) AS titanizing ON titanizing.file_id = result.id
            LEFT JOIN  ( 
                SELECT file_id, JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'hardness_id', hardness_id
                    )) AS  hardness
                FROM  public.hardness
                GROUP BY file_id
            ) AS hardness ON hardness.file_id = result.id
            WHERE result.avg>=:threshold
            ORDER BY result.avg DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id'], PDO::PARAM_STR);
        $stmt->bindValue(':threshold', $data['threshold']);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->execute();
        $orderresult = $stmt->fetchAll();


        if (!isset($data['year']) && !isset($data['material']) && !isset($data['titanizing']) && !isset($data['hardness'])) {
            $result = [];
            foreach ($orderresult as $key => $value) {
                array_push($result, $value);
                if (count($result) >= $data['amount']) {
                    $ack = array(
                        'id' => $data['process_id'],
                        'result' => $result
                    );
                    return $ack;
                }
            }
            $ack = array(
                'id' => $data['process_id'],
                'result' => $result
            );
            return $ack;
        }

        $ordernameStr = "(";
        foreach ($orderresult as $key => $value) {
            $ordernameStr .= "'{$value['order_name']}',";
        }
        $ordernameStr = substr_replace($ordernameStr, ")", -1);

        $tmpStr = "";
        if (isset($data['year'])) {
            $yearStr = " and year(COPTC.TC003) IN (";
            foreach ($data['year'] as $key => $value) {
                $yearStr .= "'{$value}',";
            }
            $yearStr = substr_replace($yearStr, ")", -1);
            $tmpStr .= $yearStr;
        }

        if (isset($data['material'])) {
            $materialStr = " and COPTD.TD205 IN(";
            foreach ($data['material'] as $key => $value) {
                $materialStr .= "'{$value}',";
            }
            $materialStr = substr_replace($materialStr, ")", -1);
            $tmpStr .= $materialStr;
        }

        if (isset($data['titanizing'])) {
            $titanizingStr = "and COPTD.TD204 IN(";
            foreach ($data['titanizing'] as $key => $value) {
                $titanizingStr .= "'{$value}',";
            }
            $titanizingStr = substr_replace($titanizingStr, ")", -1);
            $tmpStr .= $titanizingStr;
        }

        if (isset($data['hardness'])) {
            $hardnessStr = "and COPTD.TD206 IN(";
            foreach ($data['hardness'] as $key => $value) {
                $hardnessStr .= "'{$value}',";
            }
            $hardnessStr = substr_replace($hardnessStr, ")", -1);
            $tmpStr .= $hardnessStr;
        }

        // return $tmpStr;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT [COPTD].[TD201] AS name
                FROM [MIL].[dbo].[COPTC],[MIL].[dbo].[COPTD]
                WHERE COPTC.TC001=COPTD.TD001 
                    and COPTC.TC002 = COPTD.TD002
                    {$tmpStr}
                    
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $fliterresult = json_decode($head, true);
        curl_close($ch);
        // var_dump($fliterresult);

        $result = [];
        foreach ($orderresult as $key => $value) {
            if (isset($fliterresult)) {
                foreach ($fliterresult as $fliterkey => $flitervalue) {

                    if ($value['order_name'] == $flitervalue['name']) {
                        // var_dump($value['order_name'] == $flitervalue['name']);

                        array_push($result, $value);
                        if (count($result) >= $data['amount']) {
                            $ack = array(
                                'id' => $data['process_id'],
                                'result' => $result
                            );
                            return $ack;
                        }
                        break;
                    }
                }
            }
        }

        $ack = array(
            'id' => $data['process_id'],
            'result' => $result
        );
        return $ack;
    }

    public function getMaterialFilter(){
        $business = new Business($this->db);

        $result = $business->getMaterial([]);
        $material = json_encode($result);
        $sql = " SELECT label, trim(MAX(value)) AS value,MAX(value) AS origin_value,
        CASE WHEN MAX(COALESCE(common.id,0)) IS NOT NULL  THEN 1 ELSE 0 END AS common
        FROM json_to_recordset(
            '{$material}'
        ) as setting_material(label text,value text)
        LEFT JOIN  public.common_material AS common ON RTRIM(LTRIM(common.name))  = RTRIM(LTRIM(setting_material.label)) 
        GROUP BY label
        ORDER BY label
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);


    }
    public function getTitanizingFilter(){
        $business = new Business($this->db);

        $result = $business->getTitanizing([]);
        $titanizing = json_encode($result);
        $sql = "SELECT label, trim(MAX(value)) AS value,MAX(value) AS origin_value,
        CASE WHEN MAX(common.id) IS NOT NULL  THEN 1 ELSE 0 END AS common
        FROM json_to_recordset(
            '{$titanizing}'
        ) as setting_titanizing(label text,value text)
        LEFT JOIN  public.common_titanizing AS common ON RTRIM(LTRIM(common.name))  = RTRIM(LTRIM(setting_titanizing.label)) 
        GROUP BY label
        ORDER BY CASE label
            WHEN 'NO PVD' THEN 1
            ELSE 2 
        END,label
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getHardnessFilter(){
        $business = new Business($this->db);

        $result = $business->getHardness([]);
        $hardness = json_encode($result);
        $sql = "SELECT label, trim(MAX(value)) AS value,MAX(value) AS origin_value,hardness.common
        FROM(SELECT label, trim(value) AS value ,value AS origin_value, true AS common 
            FROM json_to_recordset(
                '{$hardness}'
            ) as setting_hardness(label text,value text)
            
            UNION ALL(
                SELECT  name AS label ,MAX(id) :: TEXT AS value , MAX(id) :: TEXT AS origin_value,common 
                FROM public.common_hardness
                GROUP BY name ,common
            )
        ) AS hardness
        GROUP BY hardness.label,hardness.common
        ORDER BY hardness.label,hardness.common
        
        ";
        $stmt = $this->db->prepare($sql);
        
        if($stmt->execute()){
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }else{
            return $stmt->errorInfo();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getDetail($data){
        $business = new Business($this->db);
        $result = $business->getCustomerCodes();
        $customer_code = json_encode($result);
        $result = $business->getMaterial([]);
        $material = json_encode($result);
        $result = $business->getTitanizing([]);
        $titanizing = json_encode($result);
        $result = $business->getHardness([]);
        $hardness = json_encode($result);
        $result = $business->getCC();
        $customerCode = json_encode($result);

        $values = [
            "quotation_business_id" => 0,
            'date_start'=>'',
            'date_end'=>'',
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key] = $data[$key];
        }
        $query = '';
        if ($values['date_start'] != '' || $values['date_end'] != '') {
            // AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
            if ($values['date_start'] == '') {
                $starttime = 'NOW()::date';
            } else {
                $starttime = "'{$values['date_start']}'";
            }
            if ($values['date_end'] == '') {
                $endtime = 'NOW()::date';
            } else {
                $endtime = "'{$values['date_end']}'";
            }
           
            $query .= " AND  (quotation.create_time BETWEEN {$starttime} AND {$endtime}) ";
        }
        unset($values['date_start']);
        unset($values['date_end']);
        
        $sql = "WITH tmpcost AS(SELECT crop_id AS file_id ,module_id ,module.name,
            case when module_id = 4 then SUM ( NULLIF(process, '')::int ) end as technical_cost,
            case when module_id = 5 then SUM ( NULLIF(process, '')::int ) end as production_cost
        FROM comment_process
        LEFT JOIN public.process_mapping ON comment_process.process_mapping_id = process_mapping.id
        LEFT JOIN setting.module ON module.id = comment_process.module_id
        GROUP BY crop_id,module_id,module.name)
        SELECT quotation.*, to_char(quotation.create_time, 'YYYY-MM-DD HH24:MI:SS') AS create_time , customer_outer_code.customer_outer_name,customer_outer_code.customer_short_name,urgent.urgent_name,
            COALESCE(file.file,'[]') file   
        FROM quotation_business.quotation AS quotation
        LEFT JOIN (
            SELECT urgent_id, urgent_name
            FROM quotation_business.urgent
        )AS urgent ON urgent.urgent_id = quotation.urgent_id
        LEFT JOIN (
            SELECT \"客戶代號\" AS customer_outer_code ,\"客戶名稱\" AS customer_outer_name ,\"客戶簡稱\" AS customer_short_name
                FROM json_to_recordset(
                    '{$customer_code}'
                ) as setting_customer_code(\"客戶代號\" text,\"客戶名稱\" text , \"客戶簡稱\" text)
        ) AS customer_outer_code  ON trim(quotation.customer) = trim(customer_outer_code.customer_outer_code)
        LEFT JOIN(
            SELECT file.quotation_business_id,JSON_AGG(
                JSON_BUILD_OBJECT(
                    'file_id', file.id,
                    'tmpid',file.tmpid,
                    'itemno', file.itemno,
                    'img',file.img,
                    'parent',file.parent,
                    'quotation_business_id',file.quotation_business_id,
                    'delivery_date',file.delivery_date,
                    'cost',file.cost,
                    'number',file.num,
                    'technical_cost',file.technical_cost,
                    'production_cost',file.production_cost,
                    'order_name',file.order_name,
                    'FileName',file.\"FileName\",
                    'attach_file_id',file.attach_file_id,
                    'upload_time',file.upload_time,
                    'file_name',file.file_name,
                    'material_label',file.material_label,
                    'titanizing_label',file.titanizing_label,
                    'hardness_label',file.hardness_label,
                    'crop',file.crop,
                    'progress',file.progress,
                    'file_comment',file.file_comment,
                    'material',file.material,
                    'titanizing',file.titanizing,
                    'hardness',file.hardness
                    
                )
            ) file
            FROM(
                SELECT file.id,file.itemno,file.order_name,file.id AS file_id ,file.\"FileName\",file.quotation_business_id,attach_file.*,
                        material.material_label,material.material,titanizing.titanizing,titanizing.titanizing_label,
                        hardness.hardness,hardness.hardness_label,file_comment.file_comment,progress.progress,
                        CONCAT('/file/',file.id :: TEXT) AS img,file.delivery_date,
                        crop.crop,tmpfile.tmpid,cost.technical_cost,production_cost,quotation.cost,quotation.num,
                        file.parent
                FROM public.file
                LEFT JOIN(
                    SELECT MAX(update_time) AS update_time,file_id,num , descript, discount, cost  , deadline, delivery_week, currency, delivery_range
                    FROM public.quotation
                    GROUP BY file_id, num, descript, discount, cost, deadline, delivery_week, currency, delivery_range
                )AS quotation ON quotation.file_id = file.id
                LEFT JOIN(
                    select
                        file.id file_id,
                        coalesce(sum(technical_cost), 0) || ' + 加工成本： ' || coalesce(sum(NULLIF(cost, '')::float), 0)  as technical_cost,
                        coalesce(sum(production_cost), 0) as production_cost
                    FROM file
                    LEFT JOIN tmpcost ON tmpcost.file_id = file.id
                    LEFT JOIN process_cost ON process_cost.file_id = file.id
                    group by file.id
                )AS cost ON cost.file_id = file.id
                LEFT JOIN (
                    SELECT attach_file_id, file_id, file_name, upload_time
                    FROM quotation_business.attach_file
                )AS  attach_file ON attach_file.file_id = file.id
                LEFT JOIN (
                    SELECT id, to_char(upload_time::timestamp,'YYYYMMDD') || '-' || to_char(ROW_NUMBER () OVER (
                            PARTITION BY to_char(upload_time::timestamp,'DD-MM-YYYY') 
                            ORDER BY
                                id ASC
                        ), 'FM0000') AS tmpid
                    FROM file 
                )AS tmpfile  ON file.id = tmpfile.id
                LEFT JOIN(
                    SELECT  \"fileID\" AS file_id,JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'crop_id', id
                        )
                    ) crop
                    FROM public.crop
                    GROUP BY \"fileID\"
                )AS crop ON crop.file_id = file.id
                LEFT JOIN(
                    SELECT file_id,  string_agg(setting.label, ',') as material_label, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'material_id', material_id
                        )
                        ORDER BY material.sequence
                    ) material
                    FROM  public.material
                    LEFT JOIN (
                        SELECT label, trim(value) AS value,value AS origin_value
                        FROM json_to_recordset(
                            '{$material}'
                        ) as setting_material(label text,value text)
                    )AS setting ON setting.value = material.material_id
                    GROUP BY file_id
                )AS material ON material.file_id = file.id
                LEFT JOIN(
                    SELECT file_id,string_agg(setting.label, ',') as titanizing_label, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'titanizing_id', titanizing_id
                        )
                        ORDER BY titanizing.sequence
                    ) titanizing
                    FROM  public.titanizing
                    LEFT JOIN (
                        SELECT label, trim(value) AS value,value AS origin_value
                        FROM json_to_recordset(
                            '{$titanizing}'
                        ) as setting_titanizing(label text,value text)
                    )AS setting ON setting.value = titanizing.titanizing_id
                    GROUP BY file_id
                )AS titanizing ON titanizing.file_id = file.id
                LEFT JOIN(
                    SELECT file_id,string_agg(setting.label, ',') as hardness_label, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'hardness_id', hardness_id
                        )
                        ORDER BY hardness.sequence
                    ) hardness
                    FROM  public.hardness
                    LEFT JOIN (
                        SELECT label, trim(value) AS value
                        FROM json_to_recordset(
                            '{$hardness}'
                        ) as setting_hardness(label text,value text)
                        UNION ALL(
                            SELECT  name AS label , id :: TEXT AS value 
                            FROM public.common_hardness
                        )
                    )AS setting ON setting.value = hardness.hardness_id
                    GROUP BY file_id
                )AS hardness ON hardness.file_id = file.id
                LEFT JOIN (
                    SELECT file_comment.file_id, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'file_id', file_comment.file_id,
                            'module_id',file_comment.module_id,
                            'module_name',module.name,
                            'comment',file_comment.comment
                        )
                    ) file_comment
                    FROM  public.file_comment
                    LEFT JOIN setting.module ON module.id = file_comment.module_id
                    GROUP BY file_comment.file_id
                )AS file_comment ON file_comment.file_id = file.id
                LEFT JOIN (
                    SELECT file_id, JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'progress_id', progress_id,
                            'file_id',file_id,
                            'later',later,
                            'update_time',update_time,
                            'module_id',module_id
                        )
                    ) progress
                    FROM (
                        SELECT ROW_NUMBER() OVER (PARTITION BY file_id,progress_id ORDER BY update_time DESC) as RowNum, *
                        FROM public.progress
                        GROUP BY file_id ,update_time,progress_id
                    ) AS progress
                    LEFT JOIN(
                        SELECT *
                        FROM setting.progress
                    )AS setting ON progress.progress_id=setting.id
                    WHERE progress.RowNum = 1
                    GROUP BY file_id
                )AS progress ON progress.file_id = file.id
            )file
            GROUP BY file.quotation_business_id
        )AS file ON file.quotation_business_id =  quotation.quotation_business_id
        WHERE quotation.quotation_business_id = :quotation_business_id {$query}
       
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row_key => $row) {
                foreach ($row as $key => $value) {
                    if ($this->isJson($value)) {
                        $result[$row_key][$key] = json_decode($value, true);
                    }
                }
            }
            return $result;
        }else{
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
        // return $data['quotation_business_id'];
    }

    public function getMailHistoryUnordered($data)
    {
        $values = [
            "cur_page" => 1,
            "size" => 10,
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];

        $query = "";
        if (!empty($data['id'])) {
            $query = "AND RTRIM(LTRIM([COPTB].[TB201])) LIKE '%{$data['id']}%'";
        }
        if (!empty($data['order_id'])) {
            $query .= "AND '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) LIKE '%{$data['order_id']}%'";
        }
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT history.row_num 'no', history.報價數量, history.報價單價, history.報價金額, history.客戶全名, history.客戶圖號, history.材質, history.鍍鈦, history.客戶圖片, history.幣別, history.TB001, history.TB002, history.TB003
                FROM (
                    SELECT TOP {$length} RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) as 報價編號,
                    [COPTA].[TA003] as 報價日期,
                    CAST([COPTB].[TB007] AS DECIMAL(18,0)) as 報價數量,
                    CAST([COPTB].[TB009] AS DECIMAL(18,2)) as 報價單價,
                    CAST([COPTB].[TB010] AS DECIMAL(18,2)) as 報價金額,
                    [COPTA].[TA006] as 客戶全名,
                    [COPTB].[TB201] as 客戶圖號,
                    [CMSXB].XB002 as 材質,
                    [CMSXC].[XC002] as 鍍鈦,
                    [COPTA].[TA004] as 客戶圖片,
                    [COPTA].[TA007] as 幣別,
                    [COPTB].[TB001] as TB001,
                    [COPTB].[TB002] as TB002,
                    [COPTB].[TB003] as TB003,
                    ROW_NUMBER()OVER (ORDER BY [COPTA].[TA003] DESC) AS row_num
                    FROM [MIL].[dbo].[COPTA]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTB.TB204
                    WHERE NOT EXISTS (
                        SELECT *
                        FROM [MIL].[dbo].[COPTD]
                        WHERE [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    ) {$query}
                )history
                WHERE history.row_num > {$start}
            "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result['data'] = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $result;
        // $result = $this->getCustomerCodes();
        // $row = json_encode($result);
        // $params = [];
        // $query = "";
        // if (!empty($data['id'])) {
        //     $query = "AND file.id = :id";
        //     $params["id"] = $data['id'];
        // }
        // if (!empty($data['order_name'])) {
        //     // $query = "AND file.order_name = :order_name";
        //     // $params["order_name"] = $data['order_name'];
        //     $query = "AND file.order_name LIKE '%{$data['order_name']}%'";
        // }
        // $sql = "SELECT file.id AS \"報價編號\",order_name AS \"客戶圖號\",customer.outresourcer as \"客戶全名\"
        //         ,to_char(quotation.update_time, 'YYYY-MM-DD') AS \"報價日期\",file.deadline AS \"交貨日\"
        //         ,quotation.cost AS 報價金額, quotation.num AS \"數量\",quotation.discount AS \"折扣\"
        //         ,quotation.descript AS \"報價註記\",file.outsourcer AS \"外包廠商\",file.outsourcer_amount AS \"訂單金額\",'' AS \"單價\"
        //     FROM public.file
        //     LEFT JOIN (
        //         SELECT \"客戶代號\" AS customer ,\"客戶名稱\" AS outresourcer
        //         FROM json_to_recordset(
        //             '{$row}'
        //         ) as setting_customer_code(\"客戶代號\" text,\"客戶名稱\" text)
        //     ) customer ON TRIM(customer.customer) = TRIM(file.customer)
        //     LEFT JOIN (
        //         SELECT *,
        //         ROW_NUMBER() OVER(PARTITION BY file_id ORDER BY update_time DESC) as r
        //         FROM public.quotation
        //     ) AS quotation ON quotation.file_id = file.id AND quotation.r = 1
        //     WHERE quotation.cost IS NOT NULL {$query}
        //     ORDER BY quotation.update_time DESC NULLS LAST, file.id DESC
        // ";
        // $stmt = $this->db->prepare($sql);
        // $stmt->execute($params);
        // return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    

    function imapFetchSubjects($data)
    {
        $values = [
            'date' => date('d.m.Y'),
            'keyword' => ''
        ];
        foreach(array_keys($values) as $key){
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $cm = new Webklex\PHPIMAP\ClientManager(__DIR__.DIRECTORY_SEPARATOR.'imap.php');

        /** @var \Webklex\PHPIMAP\Client $client */
        $client = $cm->account('default');

        //Connect to the IMAP Server
        $client->connect();

        //Get all Mailboxes
        /** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
        $folder = $client->getFolder('INBOX');
        //Get all Messages of the current Mailbox $folder
        /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
        $since = date('d.m.Y',strtotime($values['date']));
        $before = date('d.m.Y', strtotime($since . ' +1 day'));

        if($values['keyword'] != ''){
            $messages = $folder->query()->text($values['keyword'])->since($since)->before($before)->all()->get();
        }else{
            $messages = $folder->query()->since($since)->before($before)->all()->get();

        }

        $result = [];
        // var_dump($messages);
        /** @var \Webklex\PHPIMAP\Message $message */
        foreach($messages as $message){
            // var_dump(imap_rfc822_parse_headers($message->getHeader()->raw) -> find("test"));
            
            
            $subject_charset = 'utf-8';
            $subject = imap_rfc822_parse_headers($message->getHeader()->raw)->subject;
            $mailCharset = mb_detect_encoding($subject, array($subject_charset));
            $subject = mb_convert_encoding($subject, 'utf-8', $mailCharset);
            $result[] = [
                // "subject" => json_encode(imap_rfc822_parse_headers($message->getHeader()->raw)->subject),
                "subject" => mb_decode_mimeheader(imap_rfc822_parse_headers($message->getHeader()->raw)->subject),
                "uid" => $message->getUid(),
                "quotataion_business_id"=>$this->get_quotation_business_by_uid(['uid'=>$message->getUid()]),
                "attachments" => $message->getAttachments()->count(),
                "text" => $message->getTextBody(),
                "html" => $message->getHtmlBody()
            ];
        }
        return $result;
    }

    private function get_quotation_business_by_uid($data){
        $values = [
            'uid' => 0
        ];
        foreach($values as $key=>$value){
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $sql = "SELECT quotation.quotation_business_id
            FROM quotation_business.quotation
            WHERE quotation.mail_id = :uid
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)) return ["status"=>"failure"];
        return $stmt->rowCount()!==0?$stmt->fetchColumn(0):null;
    }

    function imapFetchContents($data)
    {


        $values = [
            'uid' => 0
        ];
        foreach($values as $key=>$value){
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $cm = new Webklex\PHPIMAP\ClientManager(__DIR__.DIRECTORY_SEPARATOR.'imap.php');

        /** @var \Webklex\PHPIMAP\Client $client */
        $client = $cm->account('default');

        //Connect to the IMAP Server
        $client->connect();

        //Get all Mailboxes
        /** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
        $folder = $client->getFolder('INBOX');
        //Get all Messages of the current Mailbox $folder
        /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
        // $since = date('d.m.Y',strtotime($values['date']));
        // $before = date('d.m.Y', strtotime($since . ' +1 day'));
        $messages = $folder->query()->whereUid($values['uid'])->all()->get();
        $result = [];
        /** @var \Webklex\PHPIMAP\Message $message */
        foreach ($messages as $key => $message) {
            $files = $message->getAttachments();
            $file_names = [];
            foreach ($files as $file) {
                $new_file_name = $this->transformFileName($file->getName());
                $file_names[] = $new_file_name;
                $file->save($this->container->upload_directory . DIRECTORY_SEPARATOR, $new_file_name);
            }
            
            $subject_charset = 'utf-8';
            $subject = imap_rfc822_parse_headers($message->getHeader()->raw)->subject;
            $mailCharset = mb_detect_encoding($subject, array($subject_charset));
            $subject = mb_convert_encoding($subject, 'utf-8', $mailCharset);


           

            $result = [
                // "subject" => json_encode(imap_rfc822_parse_headers($message->getHeader()->raw)->subject),
                "subject" => mb_decode_mimeheader(imap_rfc822_parse_headers($message->getHeader()->raw)->subject),
                "uid" => $message->getUid(),
                "attachments" => $message->getAttachments()->count(),
                "text" => $message->getTextBody(),
                "html" => $message->getHtmlBody(),
                "file_names" => $file_names
            ];
        }
        return $result;
        // $overview = imap_fetch_overview($imap, $index,FT_UID);
        // $subjectArr = imap_mime_header_decode($overview[0]->subject);
        // $subject_charset = 'utf-8';
        // $subject = '';
        // if (is_array($subjectArr)) {
        //     foreach ($subjectArr as $subjectObj) {
        //         $subject_charset = $subjectObj->charset!=='default'?$subjectObj->charset:$subject_charset;
        //         $subject .= trim($subjectObj->text);
        //     }
        //     $mailCharset = mb_detect_encoding($subject, array($subject_charset));
        //     $subject = mb_convert_encoding($subject, 'utf-8', $mailCharset);
        // } else {
        //     $subject = $subjectArr;
        // }

        // // if (str_contains(strtolower($subject), '=?utf-8?b?')) {
        // //     $subject = trim(strstr($subject, '?B?'), '?B?');
        // //     $subject = substr($subject, 0, strpos($subject, '?='));
        // //     $subject = base64_decode($subject);
        // // }
        // $file_names = [];
        // $structure = json_decode(json_encode(imap_fetchstructure($imap, $index,FT_UID)), true);

        // // var_dump($structure);

        // switch ($structure['type']) {
        //     case 0:  /* text */
        //         switch ([$structure['type'], $structure['subtype']]) {
        //             case [0, 'HTML']:
        //             case [0, 'PLAIN']:
        //                 $contents = imap_fetchbody($imap, $index, 1,FT_UID);
        //                 // $contents = trim(strstr($contents, '</head>'), '</head>');
        //                 // $contents = substr($contents, 0, strpos($contents, '</html>'));
        //                 break;
        //         }
        //         break;
        //     case 1:  /* multipart */
        //         foreach ($structure['parts'] as $key => $value) {
        //             switch ([$key, $value['type'], $value['subtype']]) {
        //                 case [0, 0, 'HTML']:
        //                 case [0, 0, 'PLAIN']:  /* system email format */
        //                     $contents = imap_fetchbody($imap, $index, 1,FT_UID);
        //                     switch ($value['encoding']) {
        //                         case 3:  /* Base64 */
        //                             $contents = imap_base64($contents);
        //                             goto encode;
        //                         case 1:
        //                         case 2:
        //                         case 0:
        //                             // $contents = imap_binary($contents);
        //                             encode:
        //                             $parameter = $value['parameters'];
        //                             $contents_charset = 'utf-8';
        //                             foreach ($parameter as $parameter_key => $parameter_value) {
        //                                 if(array_key_exists('attribute',$parameter_value))
        //                                     if(in_array($parameter_value['attribute'],['charset'])){
        //                                         $contents_charset = $parameter_value['value'];
        //                                     }
        //                             }
        //                             $mailCharset = mb_detect_encoding($contents, array($contents_charset));
        //                             $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
        //                             // $contents = imap_base64($contents);
        //                             // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                                    
        //                             break;
        //                         case 4:  /* Quoted-Printable */
        //                             $contents = quoted_printable_decode($contents);
        //                             $parameter = $value['parameters'];
        //                             $contents_charset = 'utf-8';
        //                             foreach ($parameter as $parameter_key => $parameter_value) {
        //                                 if(array_key_exists('attribute',$parameter_value))
        //                                     if(in_array($parameter_value['attribute'],['charset'])){
        //                                         $contents_charset = $parameter_value['value'];
        //                                     }
        //                             }
        //                             $mailCharset = mb_detect_encoding($contents, array($contents_charset));
        //                             $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
        //                             // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
        //                             // $contents = substr($contents, 0, strpos($contents, 'This product includes GeoLite2'));
        //                             break;
        //                     }
        //                     break;
        //                 case [0, 1, 'RELATED']:
        //                     $contents = imap_fetchbody($imap, $index, '1',FT_UID);
        //                     break;
        //                 //     switch ($value['encoding']) {
        //                 //         case 3:  /* Base64 */
        //                 //             $contents = imap_base64($contents);
        //                 //             goto encode;
        //                 //         case 1:
        //                 //         case 2:
        //                 //         case 0:
        //                 //             // $contents = imap_binary($contents);
        //                 //             encode:
        //                 //             $parameter = $value['parameters'];
        //                 //             $contents_charset = 'utf-8';
        //                 //             foreach ($parameter as $parameter_key => $parameter_value) {
        //                 //                 if(array_key_exists('attribute',$parameter_value))
        //                 //                     if(in_array($parameter_value['attribute'],['charset'])){
        //                 //                         $contents_charset = $parameter_value['value'];
        //                 //                     }
        //                 //             }
        //                 //             $mailCharset = mb_detect_encoding($contents, array($contents_charset));
        //                 //             $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
        //                 //             // $contents = imap_base64($contents);
        //                 //             // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                                    
        //                 //             break;
        //                 //         case 4:  /* Quoted-Printable */
        //                 //             $contents = quoted_printable_decode($contents);
        //                 //             // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
        //                 //             // $contents = substr($contents, 0, strpos($contents, 'This product includes GeoLite2'));
        //                 //             break;
        //                 //     }
        //                 //     break;
        //                 case [0, 1, 'ALTERNATIVE']:  /* outer email format */
        //                     foreach ($value['parts'] as $key2 => $value2) {
        //                         switch ([$key2, $value2['type'], $value2['subtype']]) {
        //                             case [0, 0, 'HTML']:
        //                             case [0, 0, 'PLAIN']:  /* text only */
        //                                 $contents = imap_fetchbody($imap, $index, 1.1,FT_UID);
        //                                 switch ($value2['encoding']) {
        //                                     case 3:  /* Base64 */
        //                                         $contents = imap_base64($contents);
        //                                         goto encode2;
        //                                     case 1:  /* Base64 */
        //                                         // $contents = imap_8bit($contents);
        //                                         goto encode2;
        //                                     case 2:  /* Base64 */
        //                                     case 0:  /* Base64 */
        //                                         // $contents = imap_binary($contents);
        //                                         encode2:
        //                                         $parameter = $value2['parameters'];
        //                                         $contents_charset = 'utf-8';
        //                                         foreach ($parameter as $parameter_key => $parameter_value) {
        //                                             if(array_key_exists('attribute',$parameter_value))
        //                                                 if(in_array($parameter_value['attribute'],['charset'])){
        //                                                     $contents_charset = $parameter_value['value'];
        //                                                 }
        //                                         }
        //                                         $mailCharset = mb_detect_encoding($contents, array($contents_charset));
        //                                         $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
        //                                         // $contents = imap_base64($contents);
        //                                         // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                                                
        //                                         break;
        //                                     case 4:  /* Quoted-Printable */
        //                                         $contents = quoted_printable_decode($contents);
        //                                         $parameter = $value2['parameters'];
        //                                         $contents_charset = 'utf-8';
        //                                         foreach ($parameter as $parameter_key => $parameter_value) {
        //                                             if(array_key_exists('attribute',$parameter_value))
        //                                                 if(in_array($parameter_value['attribute'],['charset'])){
        //                                                     $contents_charset = $parameter_value['value'];
        //                                                 }
        //                                         }
        //                                         $mailCharset = mb_detect_encoding($contents, array($contents_charset));
        //                                         $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
        //                                         // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
        //                                         // $contents = substr($contents, 0, strpos($contents, 'This product includes GeoLite2'));
        //                                         break;
        //                                 }
        //                             break;
        //                         }
        //                     }
        //                     break;
        //                 case [true, 3, true]:  /* application */
        //                 case [true, 4, true]:  /* audio */
        //                 case [true, 5, true]:  /* image */
        //                 case [true, 6, true]:  /* video */
        //                 case [true, 7, true]:  /* model */
        //                 case [true, 8, true]:  /* other */
        //                     $file_body = imap_fetchbody($imap, $index, ($key + 1),FT_UID);
        //                     switch ($value['encoding']) {
        //                         case 3:  /* Base64 */
        //                             $file_body = imap_base64($file_body);
        //                             break;
        //                     }
        //                     $parameter = array_reduce([$value['dparameters'],$value['parameters']],function($all,$current){
        //                         return is_null($current)?$all:array_merge($all,$current);
        //                     },[]);
        //                     $file_name = '';
        //                     foreach ($parameter as $key => $value) {
        //                         if(array_key_exists('attribute',$value))
        //                             if(in_array($value['attribute'],['filename','name'])){
        //                                 $file_name = imap_utf8($value['value']);
        //                             }
        //                     }
        //                     // if (str_contains($file_name, '=?UTF-8?B?')) {  /* e.g.: =?UTF-8?B?5ZyWLmpwZw==?= */
        //                     //     $file_name = trim(strstr($file_name, '?B?'), '?B?');
        //                     //     $file_name = substr($file_name, 0, strpos($file_name, '?='));
        //                     //     $file_name = base64_decode($file_name);
        //                     // }

        //                     $new_file_name = $this->transformFileName($file_name);
        //                     $file_names[] = $new_file_name;
        //                     file_put_contents($this->container->upload_directory . DIRECTORY_SEPARATOR . $new_file_name, $file_body);
        //             }
        //         }
        //         break;
        // }
        // // $flattenedParts = flattenParts($structure->parts);

        // // $contents = $this->getPartsContent($imap, $index, $structure  );

        // // $result = [
        // //     "subject" => $subject,
        // //     "contents" => $contents['contents'],
        // //     "file_names" => $contents['file_names'],
        // //     "uid" => $overview[0]->uid,
        // // ];
        // $result = [
        //     "subject" => $subject,
        //     "contents" => $contents,
        //     "file_names" => $file_names,
        //     "uid" => $overview[0]->uid,
        // ];
        // return $result;
    }

    public function getPartsContent($imap, $index, $structure ){
        // if()
        
        // var_dump($structure);
        // var_dump($structure['parameters']);
        $file_names = [];
        $boolalter = false;
         switch ($structure['type']) {
            case 0:  /* text */
                switch ([$structure['type'], $structure['subtype']]) {
                    case [0, 'HTML']:
                    case [0, 'PLAIN']:
                        $contents = imap_fetchbody($imap, $index, 1,FT_UID);
                        // $contents = trim(strstr($contents, '</head>'), '</head>');
                        // $contents = substr($contents, 0, strpos($contents, '</html>'));
                        break;
                }
                break;
            case 1:  /* multipart */
                foreach ($structure['parts'] as $key => $value) {
                    // array_key_exists('first', $search_array)
                    // var_dump(array_key_exists('parts', $value) );
                    // var_dump($value['type'], $value['subtype']);
                    if(array_key_exists('parts', $value)){
                        $boolalter = true;
                        $tmpparts = $value;

                    }

                   

                    switch ([$value['type'], $value['subtype']]) {
                        case [ 0, 'HTML']:
                        case [ 0, 'PLAIN']:  /* system email format */
                            // var_dump("inHTML/PLAIN");

                            // $contents = imap_fetchbody($imap, $index, 1,FT_UID);
                            $contents = imap_fetchbody($imap, $index, 1.1);
                            switch ($value['encoding']) {
                                case 3:  /* Base64 */
                                    $contents = imap_base64($contents);
                                    goto encode;
                                case 1:
                                case 2:
                                case 0:
                                    // $contents = imap_binary($contents);
                                    encode:
                                    $parameter = $value['parameters'];
                                    $contents_charset = 'utf-8';
                                    foreach ($parameter as $parameter_key => $parameter_value) {
                                        if(array_key_exists('attribute',$parameter_value))
                                            if(in_array($parameter_value['attribute'],['charset'])){
                                                $contents_charset = $parameter_value['value'];
                                            }
                                    }
                                    $mailCharset = mb_detect_encoding($contents, array($contents_charset));
                                    $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
                                    // $contents = imap_base64($contents);
                                    // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                                    
                                    break;
                                case 4:  /* Quoted-Printable */
                                    // var_dump($value);
                                    $contents = quoted_printable_decode($contents);
                                    $parameter = $value['parameters'];
                                    $contents_charset = 'us-ascii';
                                    foreach ($parameter as $parameter_key => $parameter_value) {
                                        if(array_key_exists('attribute',$parameter_value))
                                            if(in_array($parameter_value['attribute'],['charset'])){
                                                $contents_charset = $parameter_value['value'];
                                            }
                                    }
                                    $mailCharset = mb_detect_encoding($contents, array($contents_charset));
                                    $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
                                    $contents = html_entity_decode($contents, ENT_QUOTES, "UTF-8");
                                    // $contents  = utf8_decode(imap_utf8($contents ));
                                    // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                                    // $contents = substr($contents, 0, strpos($contents, 'This product includes GeoLite2'));
                                    break;
                            }
                            break;
                        case [ 1, 'RELATED']:
                            // var_dump("inRELATED");
                            $contents = imap_fetchbody($imap, $index, '1',FT_UID);
                            break;
                        //     switch ($value['encoding']) {
                        //         case 3:  /* Base64 */
                        //             $contents = imap_base64($contents);
                        //             goto encode;
                        //         case 1:
                        //         case 2:
                        //         case 0:
                        //             // $contents = imap_binary($contents);
                        //             encode:
                        //             $parameter = $value['parameters'];
                        //             $contents_charset = 'utf-8';
                        //             foreach ($parameter as $parameter_key => $parameter_value) {
                        //                 if(array_key_exists('attribute',$parameter_value))
                        //                     if(in_array($parameter_value['attribute'],['charset'])){
                        //                         $contents_charset = $parameter_value['value'];
                        //                     }
                        //             }
                        //             $mailCharset = mb_detect_encoding($contents, array($contents_charset));
                        //             $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
                        //             // $contents = imap_base64($contents);
                        //             // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                                    
                        //             break;
                        //         case 4:  /* Quoted-Printable */
                        //             $contents = quoted_printable_decode($contents);
                        //             // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                        //             // $contents = substr($contents, 0, strpos($contents, 'This product includes GeoLite2'));
                        //             break;
                        //     }
                        //     break;
                        case[1,'ALTERNATIVE']:  /* outer email format */
                            // $boolalter = true;
                            // $tmpparts = $value['parts'];
                            // break;
                            // var_dump('indefault');
                            // var_dump($value);
                            // foreach ($value['parts'] as $key2 => $value2) {
                            // switch ([$value['type'], $value['subtype']]) {
                            //     case [ 0, 'HTML']:
                            //     case [ 0, 'PLAIN']:  /* text only */
                            $contents = imap_fetchbody($imap, $index, 1.1,FT_UID);
                            switch ($value['encoding']) {
                                case 3:  /* Base64 */
                                    $contents = imap_base64($contents);
                                    goto encode2;
                                case 1:  /* Base64 */
                                    // $contents = imap_8bit($contents);
                                    goto encode2;
                                case 2:  /* Base64 */
                                case 0:  /* Base64 */
                                    // $contents = imap_binary($contents);
                                    encode2:
                                    $parameter = $value['parameters'];
                                    $contents_charset = 'utf-8';
                                    foreach ($parameter as $parameter_key => $parameter_value) {
                                        if(array_key_exists('attribute',$parameter_value))
                                            if(in_array($parameter_value['attribute'],['charset'])){
                                                $contents_charset = $parameter_value['value'];
                                            }
                                    }
                                    $mailCharset = mb_detect_encoding($contents, array($contents_charset));
                                    $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
                                    // $contents = imap_base64($contents);
                                    // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                                    
                                    break;
                                case 4:  /* Quoted-Printable */
                                    $contents = quoted_printable_decode($contents);
                                    $parameter = $value['parameters'];
                                    $contents_charset = 'utf-8';
                                    foreach ($parameter as $parameter_key => $parameter_value) {
                                        if(array_key_exists('attribute',$parameter_value))
                                            if(in_array($parameter_value['attribute'],['charset'])){
                                                $contents_charset = $parameter_value['value'];
                                            }
                                    }
                                    $mailCharset = mb_detect_encoding($contents, array($contents_charset));
                                    $contents = mb_convert_encoding($contents, 'utf-8', $mailCharset);
                                    // $contents = str_replace(["\r\n", "\n", "\r"], '', $contents);
                                    // $contents = substr($contents, 0, strpos($contents, 'This product includes GeoLite2'));
                                    break;
                            }
                            //     break;
                            // }
                            // }
                            break;
                        case [ 3, true]:  /* application */
                        case [ 4, true]:  /* audio */
                        case [ 5, true]:  /* image */
                        case [ 6, true]:  /* video */
                        case [ 7, true]:  /* model */
                        case [ 8, true]:  /* other */
                        default:
                            // var_dump("infile");
                            // var_dump($value['subtype']);
                            $file_body = imap_fetchbody($imap, $index, ($key + 1),FT_UID);
                            switch ($value['encoding']) {
                                case 3:  /* Base64 */
                                    $file_body = imap_base64($file_body);
                                    break;
                            }
                            $parameter = array_reduce([$value['dparameters'],$value['parameters']],function($all,$current){
                                return is_null($current)?$all:array_merge($all,$current);
                            },[]);
                            $file_name = '';
                            foreach ($parameter as $key => $value) {
                                if(array_key_exists('attribute',$value))
                                    if(in_array($value['attribute'],['filename','name'])){
                                        $file_name = imap_utf8($value['value']);
                                    }
                            }
                            // if (str_contains($file_name, '=?UTF-8?B?')) {  /* e.g.: =?UTF-8?B?5ZyWLmpwZw==?= */
                            //     $file_name = trim(strstr($file_name, '?B?'), '?B?');
                            //     $file_name = substr($file_name, 0, strpos($file_name, '?='));
                            //     $file_name = base64_decode($file_name);
                            // }

                            $new_file_name = $this->transformFileName($file_name);
                            $file_names[] = $new_file_name;
                            // var_dump($new_file_name);
                            file_put_contents($this->container->upload_directory . DIRECTORY_SEPARATOR . $new_file_name, $file_body);
                            $contents='';
                            break;
                    }
                }
                break;
        }
        // var_dump($contents);
        // $contents = "test";
        if($boolalter){
            // var_dump('in');
            $tmpresult = $this->getPartsContent($imap, $index, $tmpparts);
            $result = array(
                "contents" => $contents .$tmpresult ['contents'],
                "file_names" => array_merge($file_names,$tmpresult ['file_names'])
            );
            // return $contents.$this->getPartsContent($imap, $index, $tmpparts);
            return $result;
        }else{
            $result = array(
                "contents" => $contents,
                "file_names" => $file_names
            );
            return $result;
        }
        

    }

    public function getMailBusiness($data)
    {
        $values = [
            "cur_page" => 1,
            "size" => 10,
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];

        $query = "";
        if (!empty($data['id'])) {
            $query = "WHERE RTRIM(LTRIM([COPTD].[TD201])) LIKE '%{$data['id']}%' OR  RTRIM(LTRIM([COPTB].[TB201])) LIKE '%{$data['id']}%'";
        }
        if (!empty($data['order_id'])) {
            ($query == '') ? $query .= 'WHERE' : $query .= 'AND';
            $query .= " '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) LIKE '%{$data['order_id']}%'";
        }
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT history.row_num 'no', history.報價單單別單號序號, history.訂單單別單號序號, history.報價日期, history.報價數量, history.報價單價, history.報價金額, history.訂單數量, history.訂單單價,
                history.訂單金額, history.客戶全名, history.客戶圖號, history.報價材質 as '(報價)材質', history.廠內材質 as '(廠內)材質', history.客戶圖片, history.幣別, history.規格, 
                history.報價單圖面版次, history.訂單圖面版次, history.匯率, history.總數量, history.價格條件, history.付款條件, history.訂單單據日期,
                history.交貨日, history.確認者, history.客戶確認, history.材積單位, history.交易條件, history.總包裝數量, history.鍍鈦種類, history.報價單單據日期,
                history.TB001, history.TB002, history.TB003
                FROM (
                    SELECT TOP {$length}
                    RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002]))+'-'+[COPTB].[TB003] as 報價單單別單號序號,
                    RTRIM(LTRIM([COPTC].[TC001]))+'-'+RTRIM(LTRIM([COPTC].[TC002]))+'-'+[COPTD].[TD003] as 訂單單別單號序號,
                    CAST([COPTB].[TB007] AS DECIMAL(18,0))  as 報價數量,
                    CAST([COPTB].[TB010] AS DECIMAL(18,2)) as 報價金額,
                    CAST([COPTD].[TD008] AS DECIMAL(18,0))  as 訂單數量,
                    CAST([COPTD].[TD011] AS DECIMAL(18,2)) as 訂單單價,
                    CAST([COPTD].[TD012] AS DECIMAL(18,2)) as 訂單金額,
                    [CMSXB].XB002 as 報價材質,
                    [newCMSXB].XB002 as 廠內材質,
                    [COPTC].[TC004] as 客戶圖片,
                    [COPTD].[TD006]as 規格,
                    [COPTD].[TD201] as 客戶圖號,
                    [COPTB].[TB211] as 報價單圖面版次,
                    [COPTD].[TD214] as 訂單圖面版次,
                    [COPTA].[TA003] as 報價日期,
                    [COPTA].[TA006] as 客戶全名,
                    [COPTA].[TA007] as 幣別,
                    CAST([COPTA].[TA008] AS DECIMAL(18,2)) as 匯率,
                    CAST([COPTB].[TB009] AS DECIMAL(18,2)) as 報價單價,
                    [COPTA].[TA025] as 總數量,
                    [COPTA].[TA010] as 價格條件,
                    [COPTA].[TA011] as 付款條件,
                    [COPTC].[TC039] as 訂單單據日期,
                    [COPTA].[TA013] as 報價單單據日期,
                    [COPTA].[TA014] as 交貨日,
                    [COPTA].[TA015] as 確認者,
                    [COPTA].[TA016] as 客戶確認,
                    [COPTA].[TA028] as 材積單位,
                    -- [COPTA].[TA029] as 總毛重,
                    -- [COPTA].[TA030] as 總材積,
                    [COPTA].[TA031] as 交易條件,
                    [COPTA].[TA032] as 總包裝數量,
                    [CMSXC].[XC002] as 鍍鈦種類,
                    [COPTB].[TB001] as TB001,
                    [COPTB].[TB002] as TB002,
                    [COPTB].[TB003] as TB003,
                    ROW_NUMBER()OVER (ORDER BY [COPTC].[TC039] DESC,[COPTA].[TA013] DESC) AS row_num
                    FROM  [MIL].[dbo].[COPTD]
                    LEFT JOIN  [MIL].[dbo].[COPTC] ON [COPTC].[TC001] = [COPTD].[TD001] AND [COPTC].[TC002] = [COPTD].[TD002]
                    INNER JOIN [MIL].[dbo].[COPTB] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    LEFT JOIN [MIL].[dbo].[COPTA] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXB] AS newCMSXB ON newCMSXB.XB001 = COPTD.TD205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                    {$query}
                    ORDER BY [COPTC].[TC039] DESC,[COPTA].[TA013] DESC
                )history
                WHERE history.row_num > {$start}
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }
    function transformFileName($file_name)
    {
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        return $filename;
    }
    public function getAllMailModuleUrl()
    {
        $sql = "SELECT progress.module_id, module.name module_name,
         JSON_AGG(
            JSON_BUILD_OBJECT(
                'id', progress.id,
                'name', progress.name,
                'url', progress.url
            )
            ORDER BY progress.id
        ) progrsses
        FROM setting.progress
        LEFT JOIN setting.module ON progress.module_id = module.id
        WHERE progress.module_id != 1
        GROUP BY progress.module_id, module.name
        ORDER BY progress.module_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
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
    
    function getWeight($data){
        $values = [
            "file_id" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $sql = "SELECT file.weight
            FROM file
            WHERE file.id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            $result = $stmt->fetchColumn(0);
            return $this->isJson($result)?json_decode($result,true):$result;
        }else{
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }

    function patchWeight($data){
        $values = [
            "file_id" => 0,
            "weight" => ""
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $sql = "UPDATE public.file
            SET weight = :weight
            WHERE file.id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return [
                'status' => 'success',
            ];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
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

    function mailNameParse($data)
    {
        $home = new Home($this->container->db);
        $recogUrl = 'http://mil_python:8090/CustomerPlan?fileName=' . $data['file_name'] . '&rotate=0';
        $file = $data['file_name'];

        $tmep_file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $file;
        // Load
        // $source = imagecreatefromjpeg($tmep_file);
        // if (!$source) {
        //     $source = imagecreatefrompng($tmep_file);
        // }
        $source = $this->compressImage($tmep_file);
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
        return $result;
    }
    public function post_quotation($datas)
    {
        $values = [
            "uid" => null,
            "type" => 1
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $datas)) {
                $values[$key] = $datas[$key];
            }
        }
        if(is_null($values['uid']) ){
            return null;
        }
        $sql = "INSERT INTO quotation_business.quotation(mail_id,type)
                VALUES (:uid,:type)
                ON CONFLICT (mail_id) 
                DO UPDATE SET urgent_id = EXCLUDED.urgent_id, type = EXCLUDED.type
                RETURNING quotation_business_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // var_dump($result[0]['quotation_business_id']);
        return $result[0]['quotation_business_id'];
    }
    public function quotationPatchFile($datas)
    {
        // exit(0);
        $values = [
            "FileName" => null,
            "ClientName" => null,
            "quotation_business_id" => null,
            "deadline" => null,
            "order_name" => null,
            "customer" => null,
            "file_id" => null,
            "itemno" =>null,
            "parent" => 0
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $datas)) {
                if ($key === 'FileName') {
                    $values['ClientName'] = $datas[$key];
                }
                $values[$key] = $datas[$key];
            }
        }

        $sql = "UPDATE public.file
            SET \"FileName\"=:FileName, \"ClientName\"=:ClientName, quotation_business_id=:quotation_business_id, deadline=:deadline, order_name=:order_name, customer=:customer,itemno=:itemno, parent = :parent
            WHERE public.file.id = :file_id
        ";
        $stmt = $this->db->prepare($sql);

        
        if ($stmt->execute($values)) {
            
            $result =  ['status' => 'success'];
        } else {
            return ['status' => 'failed', 'error_info' => $stmt->errorInfo(), 'api' => 'quotationPatchFile'];
        }
        $values = [
            "quotation_business_id" => null,
            "customer" => null,
            "customer_order_id" => null,
            "deadline" => null,
            "overall_comment" => null
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $datas)) {
                $values[$key] = $datas[$key];
            }
        }
        $sql = "UPDATE quotation_business.quotation
            SET customer=:customer, customer_order_id=:customer_order_id,deadline=:deadline,overall_comment=:overall_comment
            
            WHERE quotation_business_id= :quotation_business_id
        ";
         $stmt = $this->db->prepare($sql);

        
         if ($stmt->execute($values)) {
             
             $result =  ['status' => 'success'];
         } else {
             return ['status' => 'failed', 'error_info' => $stmt->errorInfo(), 'api' => 'quotationPatchFile'];
         }

    }
    public function postQuotationFileContent($datas)
    {
        $sql = "INSERT INTO public.file_comment(
            file_id, module_id, comment)
            VALUES (:file_id , $this->quotation_module_id , :comment)
            ON CONFLICT DO NOTHING
        ";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':file_id', $datas['file_id'], PDO::PARAM_INT);
        $stmt->bindValue(':comment', $datas['comment'], PDO::PARAM_STR);
        if ($stmt->execute()) {
            return ['status' => 'success'];
        } else {
            return ['status' => 'failed', 'error_info' => $stmt->errorInfo(), 'api' => 'postQuotationFileContent'];
        }
    }
    public function postQuotationAttachFile($datas)
    {
        foreach ($datas['file_name_additional'] as $file_name) {
            $values = [
                "file_id" => null,
                "file_name_additional" => $file_name,
            ];
            foreach ($values as $key => $value) {
                if (array_key_exists($key, $datas) && $key !== 'file_name_additional') {
                    $values[$key] = $datas[$key];
                }
            }
            $sql = "INSERT INTO quotation_business.attach_file(
                file_id, file_name, upload_time)
                VALUES (:file_id , :file_name_additional , NOW())
            ";
            $stmt = $this->db->prepare($sql);
            if ($stmt->execute($values)) {
                return ['status' => 'success'];
            } else {
                return ['status' => 'failed', 'error_info' => $stmt->errorInfo(), 'api' => 'postQuotationAttachFile'];
            }
        }
    }
    public function postQuotationProgress($datas)
    {
        foreach ($datas['progress_noneed'] as $row) {
            $values = [
                "progress_id" => $row,
                "file_id" => null,
                "later" => false,
            ];
            foreach ($values as $key => $value) {
                if (array_key_exists($key, $datas)) {
                    $values[$key] = $datas[$key];
                }
            }
            $sql = "INSERT INTO public.progress(
                progress_id, file_id, later, update_time)
                VALUES (:progress_id, :file_id , :later , NOW())
            ";
            $stmt = $this->db->prepare($sql);
            if ($stmt->execute($values)) {
                return ['status' => 'success'];
            } else {
                return ['status' => 'failed', 'error_info' => $stmt->errorInfo()];
            }
        }
    }

    public function insertFile($datas)
    {
        $values = [
            "order_name" => null,
            "quotation_business_id" => null,
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $datas)) {
                $values[$key] = (string)$datas[$key];
            }
        }

        $sql = "INSERT INTO public.file(\"FileName\", \"ClientName\",order_name,quotation_business_id)
            VALUES('', '', :order_name, :quotation_business_id)
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            $id = $this->db->lastInsertId();
            return ['status' => 'success', 'file_id' => $id];
        } else {
            return ['status' => 'failed', 'api' => 'insertFile'];
        }
    }
    public function quotationPatchData($datas)
    {
        $values = [
            "deadline" => null,
            "urgent_id" => null,
            "file_name" => null,
            "customer" => null,
            "customer_order_id" => null,
            "quotation_business_id" => null,
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $datas)) {
                $values[$key] = $datas[$key];
            }
        }

        $sql = "UPDATE quotation_business.quotation
            SET deadline=:deadline, urgent_id=:urgent_id, file_name=:file_name, customer=:customer, customer_order_id=:customer_order_id
            WHERE quotation_business_id=:quotation_business_id
            RETURNING urgent_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return ['status' => 'success'];
        } else {
            return ['status' => 'failed', 'error_info' => $stmt->errorInfo(), 'api' => 'quotationPatchData'];
        }
    }
    public function sendNotify($datas)
    {
        $home = new Home($this->db);
        $notify = new Notify($this->db);

        $module_data = [];
        $module_id_trace = array();
        $module_id_group['module']  = array();
        $file_id_array = array();
        $quotation_business_id_array = array();
        foreach ($datas as $row_index => $row) {
            $moduleresult = $home->getModuleUrl($row);
            foreach ($moduleresult as $module_index => $module) {
                !in_array($module['module_id'],$module_id_group['module'])&&array_push($module_id_group['module'], $module['module_id']);
                foreach ($module as $key => $value) {
                    if ($key !== 'module_id') {
                        $key !== 0 ? $module_data[$module['module_id']][$key] = $value : null;
                        $key === 'url' && $module_data[$module['module_id']]['url'] = $module['module_url'];
                    } else {
                        if (array_search($module[$key], $module_id_trace) === false) {
                            array_push($module_id_trace, $module[$key]);
                        }
                        if (array_search($row['file_id'], $file_id_array) === false) {
                            array_push($file_id_array, $row['file_id']);
                        }
                        if (array_search($row['quotation_business_id'], $quotation_business_id_array) === false) {
                            array_push($quotation_business_id_array, $row['quotation_business_id']);
                        }
                        $module_data[$module[$key]]['file_id'] = $file_id_array;
                        $module_data[$module[$key]]['quotation_business_id'] = $quotation_business_id_array;
                        $module_data[$module[$key]]['deadline'] = $row['deadline'];

                        $message = "請{部門名稱}部門於{回饋期限}前，完成此報價的回饋資訊 <p>連結如下： {部門連結}</p>";
                        $module_data[$module[$key]]['finish'] = $module_id_group['module'];
                        $module_data[$module[$key]]['module'] = $module_id_group['module'];
                        $module_data[$module[$key]]['notify'] = 1;
                        $module_data[$module[$key]]['url'] = $module['module_url'];
                        $module_data[$module[$key]]['name'] = $module['name'];
                        $module_data[$module[$key]]['chatID'] = $module['chatID'];
                        $module_data[$module[$key]]['message'] = $message;
                        $module_data[$module[$key]]['id'] = $row['file_id'];
                    }
                }
            }
        }
        foreach ($module_data as $moudule_id => $module_row) {
            $users = $home->getUserByModule($module_id_group);
            if (isset($module_row['deadline'])) {
                foreach ($module_row['file_id'] as $key => $value) {
                    $module_row_tmp = $module_row;
                    $module_row_tmp['file_id'] = $value;
                    $notify->postBusinessNotify($module_row_tmp); // file_id notify
                }
                $this->quotationSendBusinessNotify($module_row);
            } else {
                $this->quotationSendNotify($module_row);
            }
        }

        // {
        //     1:
        //         {
        //             file_id:[1,2,3],
        //             url: 'name',
        //             name: 'name',
        //             chatID: '123',
        //         }
        //     
        // }
        $departmentUrl = '';
        $departmentName = '';

        // foreach ($module_data as $key => $value) {
        //     $message = `請{部門名稱}部門於{回饋期限}前，完成此報價的回饋資訊
        //     <p>連結如下： {部門連結}</p>`;
        //     $departmentUrl = "http://{$_SERVER['HTTP_HOST']}{$value['url']}?id={$data['id']}&file_id_dest={$data['id']}";
        //     // var_dump($departmentUrl);
        //     // var_dump($departmentName);

        //     // $data['content']
        //     // $tmpDeadline = date_format($data['deadline'], 'd/m/Y H:i:s');
        //     // var_dump($data['deadline']);
        //     // $content = str_replace("{回饋期限}",$data['deadline'],$data['content']);
        //     $message = str_replace("{部門名稱}", $value['name'], $message);
        //     $message = str_replace("{部門連結}", $departmentUrl, $message);
        //     // var_dump($content);

        //     // return $content;

        //     $notify = new Notify($this->container->db);
        //     $access_tokens = $notify->getAccessToken($data, $value['name']);
        //     $module_information = $notify->getModuleInformation($data);
        //     if (!$access_tokens) {
        //         $response = $response->withStatus(500);
        //         return $response;
        //     }
        //     foreach ($access_tokens as $key => $access_token) {
        //         if (is_null($access_token['access_token'])) continue;
        //         $ch = curl_init();
        //         // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        //         curl_setopt($ch, CURLOPT_URL, "https://notify-api.line.me/api/notify");
        //         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //             "Authorization: Bearer {$access_token['access_token']}"
        //         ));
        //         curl_setopt($ch, CURLOPT_POST, 1);
        //         // In real life you should use something like:
        //         curl_setopt(
        //             $ch,
        //             CURLOPT_POSTFIELDS,
        //             http_build_query([
        //                 "message" => $message
        //             ])
        //         );
        //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //         $head = curl_exec($ch);
        //         $result = json_decode($head, true);
        //     }
        // }
        // return $result;
    }

    public function quotationSendBusinessNotify($data)
    {
        $home = new Home($this->container->db);
        $result = [];
        $departmentUrl = '';
        $departmentName = '';
        if ($data['name'] == '生管') {
            return;
        }
        if (!array_key_exists('other', $data)) {
            $data['other'] = $data['quotation_business_id'];
        }

        $tmpidresult = $this->getTmpid($data);
        $data['other'] = [];
        $tmpidArr = [];
        foreach ($tmpidresult as $key => $value) {
            array_push($data['other'], $value["quotation_business_id"]);
            array_push($tmpidArr, $value["tmpid"]);
        }
        // var_dump(count($data['other']));
        $sentcount = intval(ceil(count($data['other']) / 5));
        $allcount = 1;
        for ($i = 0; $i < $sentcount; $i++) {
            $departmentUrl = '';
            $tmpindex = $i * 5;
            for ($j = 0; $j < 5; $j++) {
                if ($tmpindex + $j < count($data['other'])) {
                    $departmentUrl .= " \n ({$tmpidresult[$tmpindex +$j]['type_name']}單號{$tmpidArr[$allcount - 1]})  http://{$_SERVER['HTTP_HOST']}{$data['url']}{$data['other'][$tmpindex +$j]} ,";;
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
            $message = str_replace("{部門名稱}", $data['name'], $message);
            $message = str_replace("{部門連結}", $departmentUrl, $message);
            $message = str_replace("{回饋期限}", $data['deadline'], $message);
            $message = str_replace("<p>", "\n", $message);
            $message = str_replace("</p>", "\n", $message);
            // var_dump($content);

            // return $content;

            $notify = new Notify($this->container->db);
            $access_tokens = $notify->getAccessToken($data, $data['name']);
            $module_information = $notify->getModuleInformation($data);
            if (!$access_tokens) {
                $response = ['error_info' => '500'];
                return $response;
            }

            foreach ($access_tokens as $key => $access_token) {
                if (is_null($access_token['access_token'])) continue;
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
                curl_setopt(
                    $ch,
                    CURLOPT_POSTFIELDS,
                    http_build_query([
                        "message" => $message
                    ])
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $head = curl_exec($ch);
                $result = json_decode($head, true);
            }
        }
        return $result;
    }

    public function quotationSendNotify($data)
    {
        $departmentUrl = '';
        $departmentName = '';

        foreach ($data as $key => $value) {
            $message = $data['message'];
            $departmentUrl = "http://{$_SERVER['HTTP_HOST']}{$value['url']}{$data['id']}";
            // var_dump($departmentUrl);
            // var_dump($departmentName);

            // $data['content']
            // $tmpDeadline = date_format($data['deadline'], 'd/m/Y H:i:s');
            // var_dump($data['deadline']);
            // $content = str_replace("{回饋期限}",$data['deadline'],$data['content']);
            $message = str_replace("{部門名稱}", $value['name'], $message);
            $message = str_replace("{部門連結}", $departmentUrl, $message);
            // var_dump($content);

            // return $content;

            $notify = new Notify($this->container->db);
            $access_tokens = $notify->getAccessToken($data, $value['name']);
            $module_information = $notify->getModuleInformation($data);
            if (!$access_tokens) {
                $response = ['error_info' => '500'];
                return $response;
            }
            foreach ($access_tokens as $key => $access_token) {
                if (is_null($access_token['access_token'])) continue;
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
                curl_setopt(
                    $ch,
                    CURLOPT_POSTFIELDS,
                    http_build_query([
                        "message" => $message
                    ])
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $head = curl_exec($ch);
                $result = json_decode($head, true);
            }
        }
        return $result;
    }
    public function getStuff($data){
        $sql =  "SELECT *
            FROM public.stuff
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute()){
            return [
                "status" => "failure"
            ];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function getMaterial($data){
        $material_cost = $this->getMaterialCost($data);
        $material_cost = json_encode($material_cost);
        $material_outer = $this->getMaterialOuter($data);
        $material_outer = json_encode($material_outer);
        $sql = "SELECT *
            FROM(
                SELECT common_material.id material_id,material_cost.price material_price,material_cost.name material_name,material_outer.value material_code,
                    ROW_NUMBER() OVER (PARTITION BY material_cost.name) row_num
                FROM json_to_recordset('{$material_cost}') as material_cost(name text,price integer)
                LEFT JOIN json_to_recordset('{$material_outer}') AS material_outer(value text,label text) ON material_outer.label LIKE '%' || material_cost.name || '%'
                INNER JOIN (
                    SELECT TRIM(\"name\") \"name\", MIN(id) id
                    FROM public.common_material
                    GROUP BY TRIM(\"name\")
                ) common_material ON material_outer.label = common_material.name
            )dt
            WHERE dt.row_num = 1
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute()){
            return [
                "status" => "failure"
            ];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function getMaterialOuter($data){
        $sql = "SELECT  LTRIM (RTRIM (XB001) ) as value,XB002 as label
            FROM [MIL].[dbo].[CMSXB]
            GROUP BY XB001,XB002
            ORDER BY XB002
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute()){
            return [
                "status" => "failure"
            ];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function getWeightRecord($data){
        $values = [
            "file_id"=>0,
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $sql = "SELECT stuff_id, material_id, per_price, total_weight, total_price, file_id, weight_record_id, COALESCE(weight,'[]') weight
            FROM quotation_business.weight_record
            WHERE file_id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return [
                "status" => "failure"
            ];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($result as $index => $row){
            foreach ($row as $key => $value) {
                if($this->isJson($value))
                    $result[$index][$key] = json_decode($value,true);
            }
        }   
        return $result;
    }
    public function postWeightRecord($data){
        $values = [
            "stuff_id"=>0,
            "material_id"=>0,
            "per_price"=>0,
            "total_weight"=>0,
            "total_price"=>0,
            "file_id" => 0,
            "weight" => "{}"
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $sql = "INSERT INTO quotation_business.weight_record(stuff_id, material_id, per_price, total_weight, total_price, file_id, weight)
            VALUES (:stuff_id, :material_id, :per_price, :total_weight, :total_price, :file_id, :weight)
            RETURNING weight_record_id;
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return [
                "status" => "failure"
            ];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function deleteWeightRecord($data){
        $values = [
            "weight_record_id"=>0,
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $sql = "DELETE FROM quotation_business.weight_record
            WHERE weight_record_id = :weight_record_id
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return [
                "status" => "failure"
            ];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function getMaterialCost($data){
        return json_decode("[
            {
                \"name\": \"SKD61\",
                \"price\": 500
            },
            {
                \"name\": \"SKD11\",
                \"price\": 700
            },
            {
                \"name\": \"DC53\",
                \"price\": 600
            },
            {
                \"name\": \"M42\",
                \"price\": 1300
            },
            {
                \"name\": \"SKH9\",
                \"price\": 1000
            },
            {
                \"name\": \"SKH55\",
                \"price\": 1100
            }
        ]",true);
    }
    public function search_customer_code($data){
        
        $values = [
            "customer" => null
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key] = $data[$key];
        }
        // var_dump($values);
        if(is_null($values['customer']))
            return $data;
        // var_dump($values['customer']);
        
        foreach (explode(" ",$values['customer']) as $key => $value) {

            $sql = "SELECT LTRIM(RTRIM([MA001]))
                FROM(
                    SELECT *,ROW_NUMBER() OVER ( PARTITION BY MA001 ORDER BY [MA002] DESC) row_num
                    FROM(
                        SELECT [MA001],[MA002],[MA003]
                        FROM [MIL].[dbo].[COPMA]
                        UNION (
                            SELECT COPTA.TA005, COPTA.TA006, COPTA.TA006
                            FROM [MIL].[dbo].[COPTA]
                            GROUP BY COPTA.TA005,COPTA.TA006
                        )
                    )dt
                )dt
                WHERE dt.row_num=1 AND MA001 != '' AND ( LOWER([MA001]) LIKE '%' + LOWER(?) + '%' OR LOWER([MA002]) LIKE '%' + LOWER(?) + '%' OR LOWER([MA003]) LIKE '%' + LOWER(?) + '%' )
                ORDER BY MA001
            ";
            // var_dump(array_fill(0,3,$value));

            $stmt = $this->db_sqlsrv->prepare($sql);
            if(!$stmt->execute(array_fill(0,3,$value)))
                // var_dump($stmt->errorInfo());
                return $data;
            $fetch = $stmt->fetchColumn(0);
            $fetch!==FALSE&&$data["customer"] = $fetch;
        }
        return $data;
    }
}
