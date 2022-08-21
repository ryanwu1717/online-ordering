<?php

use \Psr\Container\ContainerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Home
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

    public function postLock($data)
    {
        $tmpStr = "";
        if ($data['lock'] == "true") {

            $tmpStr = " NOW() + interval '1 hour' ";
        } else {
            $tmpStr = " NOW() ";
        }
        $sql = "UPDATE public.file
        SET lock = {$tmpStr}
        WHERE id = :file_id ;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return;
    }


    public function cloneFile($data, $is_old_quotation = true)
    {

        // return ;
        // postFile
        if ($is_old_quotation) {
            $sql = "INSERT INTO public.file(
                \"ClientName\", \"FileName\",upload_time, order_serial, order_name, multiple, deadline, outsourcer, outsourcer_amount, customer, delivery_date, itemno, delivery_week,fk)
                VALUES ( :ClientName,:FileName,:upload_time,:order_serial,:order_name,:multiple, :deadline, :outsourcer, :outsourcer_amount, :customer, :delivery_date, :itemno, '',:fk::jsonb)
                RETURNING id;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':ClientName', $data['ClientName']);
            $stmt->bindValue(':FileName', $data['FileName']);
            $stmt->bindValue(':order_serial', $data['order_serial']);
            $stmt->bindValue(':order_name', $data['order_name']);
            $stmt->bindValue(':multiple', $data['multiple']);
            $stmt->bindValue(':deadline', $data['deadline']);
            $stmt->bindValue(':outsourcer', $data['outsourcer']);
            $stmt->bindValue(':outsourcer_amount', $data['outsourcer_amount']);
            $stmt->bindValue(':customer', $data['customer']);
            $stmt->bindValue(':delivery_date', $data['delivery_date']);
            $stmt->bindValue(':itemno', $data['itemno']);

            if ($data['is_outer']) {
                $stmt->bindValue(':upload_time', date("Y-m-d H:i:s"));
            } else {
                $stmt->bindValue(':upload_time', $data['upload_time']);
            }
            if ($data['is_outer']) {
                $stmt->bindValue(':fk', json_encode($data['fk']));
            } else {
                $stmt->bindValue(':fk', null);
            }

            // $stmt->execute();
            if ($stmt->execute()) {
                $result  = ['status' => 'success'];
                var_dump($result);
            } else {
                var_dump($stmt->errorInfo());
            }
            $id = $this->db->lastInsertId();
            $data['file_id'] =  $id;
        } else {

            
            $id = $data['file_id'];
            $quotationbusiness = new Quotationbusiness($this->db);

            $result = $quotationbusiness->quotationPatchFile($data);

        }

        

        $this->postMaterial($data);
        $this->postTitanizing($data);

        // postQuotation
        foreach ($data['quotation'] as $key => $value) {
            // var_dump($key,$value);
            $value['file_id'] = $id;
            $quotation = array(
                'tmpArr' => $value
            );
            $this->postQuotation($quotation);
        }

        // postmodifyprocess
        $sql = "INSERT INTO public.component (name) values ('');";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $component_id = $this->db->lastInsertId();
        foreach ($data['process'] as $key => $value) {
            $data['process'][$key]['component_id'] = $component_id;
            $data['process'][$key]['process_id'] = null;
        }
        $process = array(
            "id" => $id,
            "arr" => $data['process']
        );
        $this->postmodifyprocess($process);


        //addprogress
        // $sql = "INSERT INTO public.progress(file_id, progress_id, update_time, later) 
        // VALUES (:file_id, :progress_id , NOW() ,true )
        //         ,(:file_id2, :progress_id2 , NOW() ,true )";
        // $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':file_id',  $id, PDO::PARAM_INT);
        // $stmt->bindValue(':progress_id', 11, PDO::PARAM_INT);
        // $stmt->bindValue(':file_id2',  $id, PDO::PARAM_INT);
        // $stmt->bindValue(':progress_id2', 1, PDO::PARAM_INT);
        // $stmt->execute();

        $ack = array(
            "id" => $id,
            "progress_id" => 11
        );
        $this->setProgress($id, 11);
        return $ack;
    }

    public function getFile_commentCanvas($data)
    {
        $sql = "SELECT file_id, module_id, comment, canvas,time
        FROM public.file_comment
        WHERE file_id = :file_id AND module_id = :module_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);;
    }

    public function getFile_commentTextbox($data)
    {
        $sql = "SELECT * FROM public.textbox
        WHERE file_id = :file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);;
    }

    public function postFile_commentTextbox($data)
    {
        $sql = "DELETE FROM public.textbox
        WHERE file_id = :file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();


        $tmpStr = '';
        foreach ($data['canvas'] as $key => $value) {
            $tmpStr .= "({$data['file_id']},'{$value['mark']}','{$value['canvas']}','{$value['x']}','{$value['y']}','{$value['width']}','{$value['height']}'),";
        }
        $tmpStr = substr_replace($tmpStr, "", -1);
        $sql = "INSERT INTO public.textbox(file_id, mark, canvas,x,y,width,height)
        VALUES {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return;
    }


    public function postFile_commentCanvas($data)
    {
        $sql = "INSERT INTO   public.file_comment( file_id, module_id, canvas)
		SELECT  :file_id, :module_id, :canvas
		WHERE  NOT EXISTS (
			SELECT file_id, module_id
			FROM public.file_comment
			WHERE file_id = :file_id AND module_id =:module_id
		);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->bindValue(':canvas', $data['canvas']);
        $stmt->execute();

        $sql = "UPDATE public.file_comment
        SET canvas=:canvas, time = NOW()
        WHERE file_id = :file_id AND module_id =:module_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->bindValue(':canvas', $data['canvas']);
        $stmt->execute();
        return;
    }

    public function getFile_comment($data)
    {
        if (isset($data['module_id'])) {
            $module_id =  $data['module_id'];
        } else {
            $module_id = 0;
        }
        $sql = "SELECT module.name AS module_name ,module.name ,file_comment.file_id, file_comment.module_id, file_comment.comment,TO_CHAR(file_comment.time,'YYYY-MM-DD HH24:MI:SS') \"time\"
            FROM (
                SELECT module_id id, module.name \"name\"
                FROM setting.progress
                LEFT JOIN setting.module ON module.id = progress.module_id
                GROUP BY module_id ,module.name
            )module
            LEFT JOIN public.file_comment ON  module.id = file_comment.module_id AND file_comment.file_id = :file_id
            WHERE module.id != :module_id
            ORDER BY CASE WHEN module.id!=3 THEN -1 ELSE 0 END,module.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':module_id', $module_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function postFile_comment($data)
    {
        $sql = "INSERT INTO   public.file_comment( file_id, module_id, comment)
		SELECT  :file_id, :module_id, :comment
		WHERE  NOT EXISTS (
			SELECT file_id, module_id
			FROM public.file_comment
			WHERE file_id = :file_id AND module_id =:module_id
		);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->bindValue(':comment', $data['comment']);
        $stmt->execute();

        $sql = "UPDATE public.file_comment
        SET comment=:comment , time=NOW()
        WHERE file_id = :file_id AND module_id =:module_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->bindValue(':comment', $data['comment']);
        $stmt->execute();
        return;
    }

    public function getCurrency()
    {
        $sql = "SELECT currency_code, currency_name, currency_symbol
        FROM public.currency;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);;
    }

    public function postBusinessHardness($data)
    {
        $sql = "INSERT INTO public.common_hardness(name)
            VALUES (:hardness);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':hardness', $data['hardness']);
        $stmt->execute();
        // $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = array(
            'label' => $data['hardness'],
            'status' => "success",
            'value' => $this->db->lastInsertId()
        );
        return $result;
    }

    public function postBusinessTitanizing($data)
    {
        $sql = "INSERT INTO public.common_titanizing(name)
            VALUES (:titanizing);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':titanizing', $data['titanizing']);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return;
    }


    public function postBusinessMaterial($data)
    {
        $sql = "INSERT INTO public.common_material(name)
            VALUES (:material);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':material', $data['material']);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return;
    }

    public function deleteCommonHardness($data)
    {
        $sql = "UPDATE public.common_hardness
        SET common = false
        WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['hardness']);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function deleteCommonTitanizing($data)
    {
        $sql = "DELETE FROM public.common_titanizing
        WHERE name=:name;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $data['titanizing']);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }


    public function deleteCommonMaterial($data)
    {
        $sql = "DELETE FROM public.common_material
        WHERE name=:name;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $data['material']);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function getCommonHardness()
    {
        $sql = "SELECT name AS label , id::text as value , common
        FROM public.common_hardness;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function getCommonTitanizing()
    {
        $sql = "SELECT name
        FROM public.common_titanizing;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function getCommonMaterial()
    {
        $sql = "SELECT name
        FROM public.common_material;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function insertCNNResult($data)
    {
        // var_dump($data['CNN'][0]);
        // return;['process_id' => $processresult['process_id'], 'CNN' => $CNNPartSuggestion, 'crops' => $crops]
        if ($data['CNN'] !== null) {
            if (array_key_exists(0, $data['CNN'])) {
                foreach ($data['CNN'][0] as $key => $value) {
                    $sql = "SELECT crop.id,crop. \"fileID\", name, component_id, x, y, width, height
                    FROM public.crop
                    LEFT JOIN (
                        SELECT file.id AS \"fileID\", annotation.id,file.\"FileName\"
                        FROM public.annotation
                        LEFT JOIN public.file  ON  file.\"ClientName\"   LIKE '%' || left(split_part(annotation.name ,'_',3),-4)  || '%' 
                        GROUP BY file.id, annotation.id,file.\"FileName\"
                    )AS annotation ON  annotation.\"fileID\" = crop.\"fileID\"
                    WHERE  annotation.id = :match_id AND annotation.\"FileName\" IS NOT NULL;";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindValue(':match_id', $value);
                    $stmt->execute();
                    $tmpresult = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $tmpStr = '';

                    foreach ($tmpresult as $matchkey => $matchvalue) {

                        foreach ($data['crops'] as $cropkey => $cropvalue) {
                            $tmpcrop = $cropvalue['file_name'];
                            $tmpStr .= "({$data['process_id']},'{$matchvalue['name']}','{$data['CNN'][1][$key]}','{$cropvalue['file_name']}'),";
                        }
                    }
                    // var_dump($tmpStr);
                    if ($tmpStr == "") {
                        continue;
                    }
                    $tmpStr = substr_replace($tmpStr, "", -1);
                    $sql = "INSERT INTO public.result(process_id, filename, confidence, source)
                    VALUES {$tmpStr}";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                }
            }
        }
        return;
    }

    public function patchCrops($data)
    {
        $tmpStr = '(';
        foreach ($data['array'] as $key => $value) {
            $tmpStr .= "{$value},";
        }
        $tmpStr = substr_replace($tmpStr, ")", -1);
        $sql = "DELETE FROM public.crop WHERE id IN {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function addnewUser($data)
    {
        $column = "";
        $values = "";
        $key_array = ["uid", "name", "email", "gender", "editor_id", "edit_time", "country"];
        foreach ($key_array as $key => $value) {
            if (isset($data[$value])) {
                $column .= "{$value},";
                $values .= ":{$value},";
            }
        }
        $column = rtrim($column, ",");
        $values = rtrim($values, ",");
        $sql = "INSERT INTO system.user ({$column})
            VALUES ({$values})
            ON CONFLICT
            DO NOTHING
            RETURNING id
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($key_array as $key => $value) {
            if (isset($data[$value])) {
                $stmt->bindValue(":{$value}", $data[$value]);
            }
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }


    public function getDelivery_date($data)
    {
        $sql = "SELECT delivery_date FROM  public.file
        WHERE id=:file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return  $stmt->fetchAll();
    }

    public function patchDelivery_week($data)
    {
        $sql = "UPDATE public.file
        SET  delivery_week=:delivery_week
        WHERE id=:file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':delivery_week', $data['delivery_week']);
        $stmt->execute();
        return;
    }

    public function patchRotate($data)
    {
        $tmprotate = $data['rotate'];
        if ($data['rotate'] < 0) {
            $tmprotate += 360;
        }
        $sql = "UPDATE public.file
        SET  rotate=:rotate
        WHERE id=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
        $stmt->bindValue(':rotate', $tmprotate);
        $stmt->execute();
        return;
    }

    public function patchDelivery_date($data)
    {
        $query='';
        $bindvalue='';
        $tmpvalue='';
        foreach ($data as $key => $value) {
            if($key != "file_id"){
                $query = "{$key} = :{$key}";
                $bindvalue = ":{$key}";
                $tmpvalue = $value;
            }
        }
        $sql = "UPDATE public.file
        SET  {$query}
        WHERE id=:file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue($bindvalue, $tmpvalue);
        if ($stmt->execute()) {

            return [
                "status" => "success"
            ];
        }else{
            return [
                "status" => "failed"
            ];
        }
    }

    public function getOtherProcess_cost($data)
    {
        if (!isset($data['other'])) {
            return [];
        }
        $tmpStr = "(";
        foreach ($data['other'] as $key => $value) {
            $tmpStr .= "'{$value}',";
        }
        $tmpStr = substr_replace($tmpStr, ")", -1);
        $sql = "SELECT *
            FROM public.process_cost
            WHERE  file_id IN {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return  $stmt->fetchAll();
        return $tmpStr;
    }

    public function getProcess_cost($data)
    {
        $sql = "SELECT *
             FROM public.process_cost
            WHERE  file_id=:file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if($stmt->rowCount()==0){
            $sql = "SELECT process_cost.name,process_cost.cost,:file_id file_id
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
        foreach ($data['arr'] as $key => $value) {
            $tmpStr .= "('{$value['name']}','{$value['cost']}',{$data['file_id']}),";
        }
        $tmpStr = substr_replace($tmpStr, "", -1);
        $sql = "INSERT INTO public.process_cost(
            name, cost, file_id)
            VALUES  {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return ["status" => "success"];
    }

    public function getTech_width($data)
    {

        // $_SESSION['id'] = 7;
        $sql = "SELECT * 
        FROM system.width 
        WHERE  user_id = :user_id AND progress_id = :progress_id;";
        $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':user_id',  $_SESSION['id']);
        $stmt->bindValue(':progress_id', $data['progress_id']);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function postTech_width($data)
    {

        // $_SESSION['id'] = 7;
        $sql = "INSERT INTO system.width (user_id, progress_id, width)
        VALUES (:user_id , :progress_id , :width)
        ON CONFLICT (user_id, progress_id) DO UPDATE
        SET width = :width;";
        $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':user_id',  $_SESSION['id']);
        $stmt->bindValue(':progress_id', $data['progress_id']);
        $stmt->bindValue(':width', $data['tech_width']);
        $stmt->execute();
        return;
    }

    public function patchOrderName($data)
    {
        $sql = "UPDATE public.file
        SET  order_name=:order_name
        WHERE id=:file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':order_name', $data['order_name']);
        $stmt->execute();
        return;
    }

    public function getUserByModule($data)
    {
        $tmpStr = "(";
        if (empty($data['module'])) {
            return [];
        }
        foreach ($data['module'] as $key => $value) {
            $tmpStr .= "'{$value}',";
        }
        $tmpStr = substr_replace($tmpStr, ")", -1);
        $sql = "SELECT user_modal.uid, user_modal.module_id,\"user\".email,module.name AS module_name,\"user\".name
        FROM system.user_modal
        LEFT JOIN system.\"user\" ON \"user\".id = user_modal.uid
        LEFT JOIN setting.module ON module.id = user_modal.module_id
        WHERE user_modal.module_id IN {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getFileByProcess_mapping_id($data)
    {
        $sql = "SELECT file.order_name
        FROM public.process_mapping
        LEFT JOIN public.file ON file.id = process_mapping.crop_id
        WHERE process_mapping.id=:id;";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['process_mapping_id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getFileCustomer($data)
    {
        $sql = "SELECT customer 
        FROM public.file
        WHERE id=:id";

        $stmt = $this->db->prepare($sql);
        // var_dump($data);
        $stmt->bindValue(':id', $data->id);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getOtherLogo($data)
    {
        $sql = "SELECT file_id, name, value, type
        FROM public.logo
        WHERE file_id != :file_id AND type = 'logo';
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['id']);
        $stmt->execute();
        return  $stmt->fetchAll();
    }

    public function postLogo($data, $name)
    {
        $tmpname  = $name->name;
        $sql = "INSERT INTO public.logo (file_id, name, type, value)
       VALUES (:file_id, :name, :type, :value)
       ON CONFLICT (file_id,type) DO UPDATE
       SET name=:name ,value = :value
       WHERE logo.file_id=:file_id AND logo.type = :type
       ;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['id']);
        $stmt->bindValue(':name', $tmpname);
        $stmt->bindValue(':type', $data['type']);
        $stmt->bindValue(':value', $data['value']);
        $stmt->execute();
    }

    public function getMaterial($data)
    {
        $customer_label = array(
            "SKH9-舊程式" => "MIL60",
            "SKH55-舊程式" => "MIL60",
            "M42-舊程式" => "MIL60S",
            "DC53-標準程式" => "AS DWG",
            "榮剛-SKD11-標準程式" => "AS DWG",
            "SKD61-標準程式" => "AS DWG",
            "榮剛-SKD61-標準程式" => "AS DWG",
            "能登TR25C" => "G50",
            "能登TR20C" => "G40",
            "能登TR15C" => "G30",
            "能登DR17C" => "G30",
            "能登DR14C" => "G20",
            "能登DR11C" => "G15",
            "MIL-TIP" => "MIL-TIP",
        );
        $tmparr = [];
        foreach ($customer_label as $key => $value) {
            $arr = array(
                "label" => $key,
                "value" => $value
            );
            array_push($tmparr, $arr);
        }
        $tmparr = json_encode($tmparr);

        $business = new Business($this->db);

        $result = $business->getMaterial([]);
        $row = json_encode($result);

        $sql = "SELECT material.*,setting.*,COALESCE(customer.value, setting.label) AS customer_label
        FROM public.material
        LEFT JOIN (
            SELECT label, trim(value) AS value
            FROM json_to_recordset(
                '{$row}'
            ) as setting_material(label text,value text)
        )AS setting ON setting.value = material.material_id
        LEFT JOIN (
            SELECT label, trim(value) AS value
            FROM json_to_recordset(
                '{$tmparr}'
            ) as tmplabel(label text,value text)
        )AS customer ON customer.label = setting.label
        WHERE file_id=:file_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return  $stmt->fetchAll();
    }

    public function getTitanizing($data)
    {
        $customer_label = array(
            "TiN" => "TiN",
            "ALTiN" => "TiAlN",
            "ALCrN" => "AlCrN",
            "TiCN" => "TiCN",
            "CrN" => "CrN",
            "中日滲氮" => "Nitriding",
            "真空氮化" => "Nitriding",
            "染黑處理" => "Black Oxide",
            "火焰硬化" => "Flame Hardening",
        );
        $tmparr = [];
        foreach ($customer_label as $key => $value) {
            $arr = array(
                "label" => $key,
                "value" => $value
            );
            array_push($tmparr, $arr);
        }
        $tmparr = json_encode($tmparr);

        $business = new Business($this->db);
        $result = $business->getTitanizing([]);
        $row = json_encode($result);
        $sql = "SELECT titanizing.*,setting.*,COALESCE(customer.value, setting.label) AS customer_label
        FROM public.titanizing
        LEFT JOIN (
            SELECT label, trim(value) AS value
            FROM json_to_recordset(
                '{$row}'
            ) as setting_titanizing(label text,value text)
        )AS setting ON setting.value = titanizing.titanizing_id
        LEFT JOIN (
            SELECT label, trim(value) AS value
            FROM json_to_recordset(
                '{$tmparr}'
            ) as tmplabel(label text,value text)
        )AS customer ON customer.label = setting.label
        WHERE file_id=:file_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return  $stmt->fetchAll();
    }

    public function getHardness($data)
    {
        $business = new Business($this->db);
        $result = $business->getHardness([]);
        $row = json_encode($result);
        $sql = "SELECT hardness.*, COALESCE(setting.value, '') || COALESCE(common.id::varchar(255), '') AS value ,  COALESCE(setting.label, '') || COALESCE(common.name, '') AS label
        FROM public.hardness
        LEFT JOIN (
            SELECT label, trim(value) AS value
            FROM json_to_recordset(
                '{$row}'
            ) as setting_hardness(label text,value text)
        )AS setting ON setting.value = hardness.hardness_id
        LEFT JOIN public.common_hardness AS common ON common.id ::varchar(255) = hardness.hardness_id
        WHERE file_id=:file_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return  $stmt->fetchAll();
    }
    public function postTitanizing($data)
    {
        $sql = "DELETE FROM public.titanizing
        WHERE file_id=:file_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();

        if (!isset($data['titanizing'])) {
            return;
        }

        $inputStr = '';
        foreach ($data['titanizing'] as $key => $value) {
            $inputStr .= " ({$data['file_id']},'{$value}'),";
        }
        $inputStr = substr_replace($inputStr, "", -1);

        $sql = "INSERT INTO public.titanizing(file_id, titanizing_id)
        VALUES {$inputStr}
        ON CONFLICT (file_id,titanizing_id) 
        DO NOTHING;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }
    public function postHardness($data)
    {
        $sql = "DELETE FROM public.hardness
        WHERE file_id=:file_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();

        if (!isset($data['hardness'])) {
            return;
        }

        $inputStr = '';
        foreach ($data['hardness'] as $key => $value) {
            $inputStr .= " ({$data['file_id']},'{$value}'),";
        }
        $inputStr = substr_replace($inputStr, "", -1);

        $sql = "INSERT INTO public.hardness(file_id, hardness_id)
        VALUES {$inputStr}
        ON CONFLICT (file_id, hardness_id) 
        DO NOTHING;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    public function postMaterial($data)
    {
        $sql = "DELETE FROM public.material
        WHERE file_id=:file_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();

        if (!isset($data['material'])) {
            return;
        }

        $inputStr = '';
        foreach ($data['material'] as $key => $value) {
            $inputStr .= " ({$data['file_id']},'{$value}'),";
        }
        $inputStr = substr_replace($inputStr, "", -1);

        $sql = "INSERT INTO public.material(file_id, material_id)
        VALUES {$inputStr}
        ON CONFLICT (file_id, material_id) 
        DO NOTHING;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $data['material'];
    }

    public function getCardAuthority($data)
    {
        $sql = "SELECT card_authority.*,card.name
        FROM system.card_authority
        LEFT JOIN system.card ON card.id = card_authority.card_id
        WHERE module_id = :module_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function updateCardAuthority($data)
    {
        $sql = "DELETE FROM system.card_authority
        WHERE module_id=:module_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->execute();

        if (!isset($data['urlArr'])) {
            return;
        }

        $inputStr = '';
        foreach ($data['urlArr'] as $key => $value) {
            $inputStr .= " ({$data['module_id']},'{$value}'),";
        }
        $inputStr = substr_replace($inputStr, "", -1);

        $sql = "INSERT INTO system.card_authority(
            module_id, card_id)
            VALUES {$inputStr}
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        // return $data;
    }

    public function getUrlAuthority($data)
    {
        $sql = "SELECT *
        FROM system.authority
        WHERE module_id = :module_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateUrlAuthority($data)
    {
        $sql = "DELETE FROM system.authority
        WHERE module_id=:module_id
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->execute();

        if (!isset($data['urlArr'])) {
            return;
        }

        $inputStr = '';
        foreach ($data['urlArr'] as $key => $value) {
            $inputStr .= " ({$data['module_id']},'{$value}'),";
        }
        $inputStr = substr_replace($inputStr, "", -1);

        $sql = "INSERT INTO system.authority(
            module_id, progress_id)
            VALUES {$inputStr}
        ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        // return $data;
    }

    public function getAllCard()
    {
        $sql = "SELECT id, name, chinese_name
        FROM system.card
        ORDER BY id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllModuleUrl()
    {
        $sql = "SELECT id, name, module_id, url
        FROM setting.progress;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOutsourcerLimittime($data)
    {
        $tmpStr = "";

        if ($data['start'] != '') {
            $tmpStr .= " AND file.deadline > '{$data['start']}'";
        }
        if ($data['end'] != '') {
            $tmpStr .= " AND file.deadline < '{$data['end']}'";
        }


        $sql = "SELECT outsourcer.name,COUNT(*)

        FROM public.file
        LEFT JOIN system.outsourcer ON outsourcer.id = file.outsourcer
		WHERE outsourcer.name IS NOT NULL {$tmpStr}
		GROUP BY outsourcer.name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function getCommentOutsourcerHistory($data)
    {
        $tmpStr = "";

        if ($data['start'] != '') {
            $tmpStr .= " AND update_date > '{$data['start']}'";
        }
        if ($data['end'] != '') {
            $tmpStr .= " AND update_date < '{$data['end']}'";
        }

        $sql = "SELECT outsourcer_vendor  AS name ,COUNT(*)
        FROM public.comment_process
        
		WHERE outsourcer_vendor IS NOT NULL {$tmpStr}
		GROUP BY outsourcer_vendor
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function getAllmodifyprocess($data)
    {
        //  LEFT JOIN (
        //         SELECT \"客戶代號\" AS code ,\"客戶名稱\" AS name
        //             FROM json_to_recordset(
        //                 '{$customer_code}'
        //             ) as setting_customer_code(\"客戶代號\" text,\"客戶名稱\" text)
        //     ) AS customer_code  ON trim(file.customer) = trim(customer_code.code)
        // if ($data['start'] != '') {
        //     $startquery="'{$data['start']}'";
        // } else {
        //     $startquery = "NOW()";
        // }
        // if ($data['end'] != '') {
        //     $endquery="'{$data['start']}'";
        // } else {
        //     $endquery="NOW()";
        // }
        $sql = "SELECT  modify_process.name,COUNT(*),modify_process.code
            FROM public.modify_process
            LEFT JOIN public.file ON file.id = modify_process.file_id
            LEFT JOIN (
                SELECT *,ROW_NUMBER() OVER(Partition by file_id ORDER BY update_time DESC) AS row_id
                FROM public.quotation
            ) quotation ON quotation.file_id = file.id AND row_id = 1
            WHERE  modify_process.name != ''
            GROUP BY modify_process.name,modify_process.code;
        ";
        // -- WHERE  modify_process.name != '' AND ({$startquery}BETWEEN quotation.update_time AND quotation.deadline OR {$endquery} BETWEEN quotation.update_time AND quotation.deadline)

        $stmt = $this->db->prepare($sql);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOutsourcerHistory()
    {
        $sql = "SELECT outsourcer.name,COUNT(*)
        FROM public.file
        LEFT JOIN system.outsourcer ON outsourcer.id = file.outsourcer
		WHERE outsourcer.name IS NOT NULL
		GROUP BY outsourcer.name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOutsourcerCount($data)
    {
        $tmpStr = "";

        if ($data['start'] != '') {
            $tmpStr .= " AND file.deadline > '{$data['start']}'";
        }
        if ($data['end'] != '') {
            $tmpStr .= " AND file.deadline < '{$data['end']}'";
        }



        $sql = "SELECT outsourcer.name,COUNT(*)
        FROM public.file
        LEFT JOIN system.outsourcer ON outsourcer.id = file.outsourcer
        WHERE outsourcer.name=:outsourcer AND outsourcer.name IS NOT NULL {$tmpStr}
        GROUP BY  outsourcer.name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':outsourcer', $data['outsourcer']);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOutsourcer($data)
    {
        $sql = "SELECT outsourcer.name,outsourcer.amount outsourcer_amount
        FROM public.outsourcer
        WHERE outsourcer.file_id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['file_id']);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insertOutsourcer($data)
    {
        $sql = "DELETE FROM public.outsourcer
        WHERE file_id = :file_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();

        $tmpStr = "";
        foreach ($data['outsourcers'] as $key => $value) {
            $tmpStr .= "(:file_id{$key},:name{$key},:amount{$key}),";
        }
        $tmpStr = substr_replace($tmpStr, "", -1);

        $sql = "INSERT INTO public.outsourcer(
            file_id, name, amount)
            VALUES {$tmpStr}";
        $stmt = $this->db->prepare($sql);


        foreach ($data['outsourcers'] as $key => $value) {
            $stmt->bindValue(":file_id{$key}", $data['file_id']);
            $stmt->bindValue(":name{$key}", $value['name']);
            $stmt->bindValue(":amount{$key}", $value['amount']);
        }
        $stmt->execute();
        return;
    }

    public function updateOutsourcer($data)
    {
        foreach ($data['outsourcers'] as $key => $value) {
            $sql = "INSERT INTO system.outsourcer(name)
            SELECT :name
            WHERE NOT EXISTS (
                SELECT *
                FROM system.outsourcer
                WHERE name = :name  ); ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':name', $value['name']);
            $stmt->execute();
        }
        $this->insertOutsourcer($data);
        return;
        // $sql = "UPDATE public.file 
        //     SET outsourcer_amount=:outsourcer_amount, 
        //         outsourcer = OtherTable.id 
        //     FROM (
        //         SELECT id 
        //         FROM system.outsourcer
        //         WHERE name=:name) AS OtherTable
        //     WHERE  file.id = :id";
        // $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':id', $data['file_id']);
        // $stmt->bindValue(':name', $data['name']);
        // $stmt->bindValue(':outsourcer_amount', $data['amount']);
        // $stmt->execute();
    }

    public function getOutsourcerList()
    {
        $sql = "SELECT id, name, code, note,module_id
        FROM public.common_outsourcer;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOutsourcerSetting()
    {
        $sql = "SELECT id, name
        FROM system.outsourcer;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOutsourcerCost()
    {
        /* 
SELECT
    CMSMW.MW001 製程代號,
    CMSMW.MW002 製程名稱,
    STUFF((
        SELECT TOP 5
            [MOCMA].[MA002] 製程代號
            ,[MOCMA].[MA003] 加工廠商
            ,PURMA.MA002 廠商簡稱
            ,[MOCMA].[MA012] 生效日
            ,[MOCMA].[MA010] 幣別
            ,[MOCMA].[MA005] 單價
        FROM [MIL].[dbo].[MOCMA]
        LEFT JOIN [MIL].[dbo].[PURMA] ON [PURMA].MA001 = MOCMA.MA003
        WHERE MOCMA.MA002 = CMSMW.MW001
        ORDER BY [MOCMA].[MA012] DESC
        FOR XML PATH),1,0,''
    )cost
FROM MIL.dbo.CMSMW
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
                ['sql' => "SELECT
                        CMSMW.MW001 製程代號,
                        CMSMW.MW002 製程名稱,
                        STUFF((
                            SELECT TOP 5
                                [MOCMA].[MA002] 製程代號
                                ,[MOCMA].[MA003] 加工廠商
                                ,PURMA.MA002 廠商簡稱
                                ,[MOCMA].[MA012] 生效日
                                ,[MOCMA].[MA010] 幣別
                                ,[MOCMA].[MA005] 單價
                            FROM [MIL].[dbo].[MOCMA]
                            LEFT JOIN [MIL].[dbo].[PURMA] ON [PURMA].MA001 = MOCMA.MA003
                            WHERE MOCMA.MA002 = CMSMW.MW001
                            ORDER BY [MOCMA].[MA012] DESC
                            FOR XML PATH),1,0,''
                        )cost
                    FROM MIL.dbo.CMSMW
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        if (isset($result)) {
            foreach ($result as $key_result => $value) {
                $tmpvalue = $value['cost'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['cost'] = $tmpArrs;
                    goto Endquotation;
                }
                foreach ($xml as $t) {
                    $tmpArr = [];
                    foreach ($t as $a => $b) {
                        $tmpArr[$a] = '';
                        foreach ((array)$b as $c => $d) {
                            $tmpArr[$a] = $d;
                        }
                    }
                    $tmpArrs[] = $tmpArr;
                }
                $result[$key_result]['cost'] = $tmpArrs;
                Endquotation:
            }
        }
        return $result;
    }

    public function postFinishSuggestionVal($data)
    {
        $sql = 'INSERT INTO public.suggestion_value(suggest_id, process_mapping_id, val)
        SELECT :suggest_id, :process_mapping_id, :val
        WHERE NOT EXISTS (
                        SELECT *
                        FROM public.suggestion_value
                        WHERE suggest_id = :suggest_id AND process_mapping_id = :process_mapping_id );';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':suggest_id', $data['suggest_id']);
        $stmt->bindValue(':process_mapping_id', $data['process_mapping_id']);
        $stmt->bindValue(':val', $data['val']);
        $stmt->execute();



        $sql = "UPDATE public.suggestion_value
        SET  val=:val
        WHERE suggest_id = :suggest_id AND process_mapping_id = :process_mapping_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':suggest_id', $data['suggest_id']);
        $stmt->bindValue(':process_mapping_id', $data['process_mapping_id']);
        $stmt->bindValue(':val', $data['val']);
        $stmt->execute();
    }

    public function getFinishSuggestionVal($data)
    {
        $sql = "SELECT suggestion_value.* 
        FROM public.suggestion_value
        WHERE suggestion_value.suggest_id IN 
            (SELECT id 
             FROM public.suggestion
            WHERE file_id=:file_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getFinishSuggestion($data)
    {
        $sql = 'SELECT * FROM public.suggestion
        WHERE file_id=:file_id  
        order by  id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function postFinishSuggestion($data)
    {
        // $sql = 'DELETE FROM public.suggestion
        // WHERE file_id=:file_id AND type=:type; ';
        // $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':file_id', $data['file_id']);
        // $stmt->bindValue(':type', $data['type']);
        // $stmt->execute();

        // $inputStr = '';
        // foreach($data['thArr'] as $key => $value){
        //     $inputStr.=" ({$data['file_id']},'{$data['type']}','{$value}'),";
        // }
        // $inputStr = substr_replace($inputStr ,"", -1);

        $sql = "INSERT INTO public.suggestion(
            file_id, type)
            VALUES (:file_id,:type);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':type', $data['type']);
        $stmt->execute();
        $ack = array(
            'suggest_id' => $this->db->lastInsertId(),
        );

        return $ack;
    }

    public function patchFinishSuggestion($data)
    {
        $sql = "UPDATE public.suggestion
        SET  title=:title
        WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
        $stmt->bindValue(':title', $data['title']);
        $stmt->execute();
    }

    public function deleteFinishSuggestion($data)
    {

        $sql = "DELETE FROM public.suggestion_value
        WHERE suggest_id=:suggest_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':suggest_id', $data['id']);
        $stmt->execute();

        $sql = "DELETE FROM public.suggestion
        WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
    }

    public function getRegistration()
    {
        $sql = 'SELECT * 
        FROM system."user"
        LEFT JOIN system.user_modal on user_modal.uid = "user".id
        WHERE id=:id;
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $_SESSION['id']);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserUID()
    {
        $sql = 'SELECT * 
        FROM system."user"
        WHERE id=:id;
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $_SESSION['id']);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function patchPassword($data)
    {
        $ack = array(
            'status' => 'success',
            'message' => '修改成功'
        );
        $sql = 'SELECT * 
        FROM system."user"
        WHERE id=:id;
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $_SESSION['id']);
        $stmt->execute();
        $userinfo = $stmt->fetchAll();
        $username = $userinfo[0]['uid'];
        $username = 'nknu';
        $ldap = $this->container->ldap;
        $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", "(&(uid={$username})(userpassword={$data['oldpassword']}))");
        // $sr=ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw","(uid={$username})");
        $info = ldap_get_entries($ldap['conn'], $sr);
        // var_dump($info);
        if ($info['count'] != 1) {
            $ack = array(
                'status' => 'failed',
                'message' => '原始密碼錯誤'
            );
            return $ack;
        }
        if ($data['password'] != $data['password1']) {
            $ack = array(
                'status' => 'failed',
                'message' => '密碼需與密碼確認相同'
            );
            return $ack;
        }

        $userdata = array();
        $userdata["userpassword"] = $data['password'];
        $dn = "uid=$username,cn=users,dc=mil,dc=com,dc=tw";
        $ldap = $this->container->ldap;
        ldap_modify($ldap['conn'], $dn, $userdata);


        return $ack;
    }

    public function patchRegistration($data)
    {
        $sql = 'DELETE FROM system.user_modal
        WHERE uid=:id; ';
        $stmt = $this->db->prepare($sql);
        // return $_SESSION['id'];
        $stmt->bindValue(':id', $_SESSION['id']);
        $stmt->execute();
        $inputStr = '';
        foreach ($data['moduleArr'] as $key => $value) {
            $inputStr .= " ({$_SESSION['id']},{$value}),";
        }
        $inputStr = substr_replace($inputStr, "", -1);
        $sql = "INSERT INTO system.user_modal(
            uid, module_id)
            VALUES {$inputStr};";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $sql = 'UPDATE system."user"
        SET email=:email
        WHERE id=:id;
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $_SESSION['id']);
        // $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();


        $sql = 'SELECT * 
        FROM system."user"
        WHERE id=:id;
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $_SESSION['id']);
        $stmt->execute();
        $userinfo = $stmt->fetchAll();
        $username = $userinfo[0]['uid'];
        $userdata = array();
        $userdata["mail"] = $data['email'];
        $dn = "uid=$username,cn=users,dc=mil,dc=com,dc=tw";
        $ldap = $this->container->ldap;
        ldap_mod_replace($ldap['conn'], $dn, $userdata);

        $ack = array(
            'status' => 'success',
        );

        return $ack;
    }

    public function getModuleSetting()
    {
        $sql = "SELECT id, name, color
        FROM setting.module;";
        $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':file_id', $data['id']);
        $stmt->execute();
        return $stmt->fetchAll();
    }



    public function getmodifyprocessOutsourcerHistory($data)
    {
        $sql = "SELECT  modify_process.outsourcer,modify_process.name,COUNT(*)
        FROM public.modify_process
        LEFT JOIN public.file ON file.id = modify_process.file_id
        LEFT JOIN (
            SELECT *,ROW_NUMBER() OVER(Partition by file_id ORDER BY update_time DESC) AS row_id
            FROM public.quotation
        ) quotation ON quotation.file_id = file.id AND row_id = 1
        WHERE  modify_process.outsourcer != '' AND  modify_process.outsourcer IS NOT NULL  
                -- AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
        GROUP BY modify_process.outsourcer,modify_process.name;";
        $stmt = $this->db->prepare($sql);
        // if ($data['start'] != '') {
        //     $stmt->bindValue(':start', $data['start']);
        // } else {
        //     $stmt->bindValue(':start', 'NOW()');
        // }
        // if ($data['end'] != '') {
        //     $stmt->bindValue(':end', $data['end']);
        // } else {
        //     $stmt->bindValue(':end', 'NOW()');
        // }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getmodifyprocessOutsourcerTemperary($data)
    {
        $sql = "SELECT  modify_process.outsourcer,modify_process.name,COUNT(*)
        FROM public.modify_process
        LEFT JOIN public.file ON file.id = modify_process.file_id
        LEFT JOIN (
            SELECT *,ROW_NUMBER() OVER(Partition by file_id ORDER BY update_time DESC) AS row_id
            FROM public.quotation
        ) quotation ON quotation.file_id = file.id AND row_id = 1
        WHERE  modify_process.outsourcer != '' AND  modify_process.outsourcer IS NOT NULL
                -- AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
        GROUP BY modify_process.outsourcer,modify_process.name;";
        $stmt = $this->db->prepare($sql);

        // if ($data['start'] != '') {
        //     $stmt->bindValue(':start', $data['start']);
        // } else {
        //     $stmt->bindValue(':start', 'NOW()');
        // }
        // if ($data['end'] != '') {
        //     $stmt->bindValue(':end', $data['end']);
        // } else {
        //     $stmt->bindValue(':end', 'NOW()');
        // }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function postmodifyprocessOutsourcer($data)
    {
        $sql = "DELETE FROM public.modify_process
        WHERE file_id = :file_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['id']);
        $stmt->execute();
        // return $data;


        $tmpStr = "";
        foreach ($data['arr'] as $key => $value) {
            $tmpStr .= "(:id{$key},:component_id{$key},:process_id{$key},:num{$key},:code{$key},:name{$key},:mark{$key},:cost{$key},:outsourcer{$key},:outsourcer_cost{$key}),";
        }
        $tmpStr = substr_replace($tmpStr, "", -1);
        $sql = "INSERT INTO public.modify_process(file_id, component_id, process_id, num, code, name, mark, cost,outsourcer,outsourcer_cost)
        VALUES {$tmpStr}";
        // return $sql;
        $stmt = $this->db->prepare($sql);
        foreach ($data['arr'] as $key => $value) {
            $stmt->bindValue(":id{$key}", $data['id']);
            $stmt->bindValue(":component_id{$key}", $data['component_id']);
            $stmt->bindValue(":process_id{$key}", $data['process_id']);
            $stmt->bindValue(":num{$key}", $value['num']);
            $stmt->bindValue(":code{$key}", $value['code']);
            $stmt->bindValue(":name{$key}", $value['name']);
            $stmt->bindValue(":mark{$key}", $value['mark']);
            $stmt->bindValue(":cost{$key}", $value['cost']);
            $stmt->bindValue(":outsourcer{$key}", $value['outsourcer']);
            $stmt->bindValue(":outsourcer_cost{$key}", $value['outsourcer_cost']);
        }
        $stmt->execute();


        return;
        // $sql = "DELETE FROM public.modify_process
        // WHERE file_id = :file_id; AND component_id=:component_id AND process_id=:process_id";
        // $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':file_id', $data['id']);
        // $stmt->bindValue(':component_id', $data['component_id']);
        // $stmt->bindValue(':process_id', $data['process_id']);
        // $stmt->execute();
        // return ;
        // $data = $data['tmpArr'];

        // $sql = "INSERT INTO public.modify_process(file_id, component_id, process_id, num, code, name, mark, cost,outsourcer)
        // SELECT :file_id, :component_id, :process_id, :num, :code, :name, :mark, :cost,:outsourcer
        // WHERE NOT EXISTS (
        //     SELECT *
        //     FROM public.modify_process
        //     WHERE file_id = :file_id AND component_id = :component_id AND process_id =:process_id
        // )";
        // $stmt = $this->db->prepare($sql);
        // foreach ($data as $key => $value) {
        //     $tmpBind = ":{$key}";
        //     $stmt->bindValue($tmpBind, $value);
        // }
        // $stmt->execute();

        // $sql="UPDATE public.modify_process
        // SET num=:num, code=:code, name=:name, mark=:mark, cost=:cost, outsourcer=:outsourcer
        // WHERE file_id = :file_id AND component_id = :component_id AND process_id =:process_id;";
        //  $stmt = $this->db->prepare($sql);
        //  foreach ($data as $key => $value) {
        //      $tmpBind = ":{$key}";
        //      $stmt->bindValue($tmpBind, $value);
        //  }
        //  $stmt->execute();


    }

    public function postmodifyprocess($data)
    {
        $sql = "DELETE FROM public.modify_process
        WHERE file_id = :file_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['id']);
        $stmt->execute();

        $inputStr = "";
        $tmpStr = "";
        foreach ($data['arr'] as $key => $value) {
            if (!isset($data['arr'][$key]['process_id'])) $data['arr'][$key]['process_id'] = null;
            $value['process_id'] = $data['arr'][$key]['process_id'];
            $inputStr .= " ({$data['id']},{$value['component_id']},{$value['process_id']},'{$value['num']}','{$value['code']}','{$value['name']}','{$value['mark']}','{$value['cost']}'),";
            $tmpStr .= "(:id{$key},:component_id{$key},:process_id{$key},:num{$key},:code{$key},:name{$key},:mark{$key},:cost{$key},:outsourcer{$key},:outsourcer_cost{$key}),";
        }
        $inputStr = substr_replace($inputStr, "", -1);
        $tmpStr = substr_replace($tmpStr, "", -1);
        $sql = "INSERT INTO public.modify_process(file_id, component_id, process_id, num, code, name, mark, cost,outsourcer,outsourcer_cost)
        VALUES {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        foreach ($data['arr'] as $key => $value) {
            $stmt->bindValue(":id{$key}", $data['id']);
            $stmt->bindValue(":component_id{$key}", $value['component_id']);
            $stmt->bindValue(":process_id{$key}", $value['process_id']);
            $stmt->bindValue(":num{$key}", $value['num']);
            $stmt->bindValue(":code{$key}", $value['code']);
            $stmt->bindValue(":name{$key}", $value['name']);
            $stmt->bindValue(":mark{$key}", $value['mark']);
            $stmt->bindValue(":cost{$key}", $value['cost']);
            $stmt->bindValue(":outsourcer{$key}", $value['outsourcer']);
            $stmt->bindValue(":outsourcer_cost{$key}", $value['outsourcer_cost']);
        }
        $stmt->execute();

        $ack = array(
            'status' => 'success',
        );
        return $ack;
    }

    

    public function getmodifyprocess($data)
    {
        $sql = " SELECT file_id, component_id, process_id, num, code, name, mark, cost,outsourcer,deadline,outsourcer_cost
        FROM public.modify_process
        WHERE file_id = :file_id
        ORDER BY num :: INTEGER;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }

    public function checkclosedeadline()
    {
        $sql = "SELECT * 
        FROM(
            SELECT file.id,file.deadline,progress_table.*,
                ROW_NUMBER() OVER(
                        PARTITION BY progress_table.module_id
                     ORDER BY  file.id
                )
            FROM public.file AS file
            LEFT JOIN (
                SELECT tmptable.file_id,tmptable.id,module.name AS module_name,tmptable.name,tmptable.url,module.id AS module_id,module.color,progress.later,CASE 
                    WHEN progress.later IS false THEN '不需要' || tmptable.name 
                    WHEN MAX(progress.update_time) IS NULL THEN '待' || tmptable.name 
                    ELSE '已' || tmptable.name 
                    END
                FROM (SELECT file.id AS file_id , setting_progress.*
                FROM setting.progress setting_progress  
                CROSS JOIN file
                GROUP BY file.id , setting_progress.id 
                ORDER BY file.id)AS tmptable
                LEFT JOIN setting.module ON module.id = tmptable.module_id
                LEFT JOIN(
                    SELECT file.id AS file_id, progress.progress_id,progress.update_time,progress.later
                    FROM file
                    LEFT JOIN progress ON file.id = progress.file_id
        
                )progress ON tmptable.id = progress.progress_id AND progress.file_id = tmptable.file_id
        
                GROUP BY tmptable.id,module.name,tmptable.name,tmptable.url,module.id,module.color,progress.later,tmptable.file_id
                ORDER BY tmptable.file_id,tmptable.id
        
        
            )AS progress_table ON file.id =progress_table.file_id 
            WHERE NOW() > deadline  AND deadline > NOW() - interval '1 day' AND progress_table.case ::text LIKE '待%'
        ) AS outtable
        WHERE row_number = 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        // $home->getState($data);


        return $result;
    }

    public function getModuleUrl($data)
    {
        if (isset($data['progress'])) {
            $insertValue = "id in ( ";
            foreach ($data['progress'] as $key => $value) {
                $insertValue .= " {$value},";
            }
            $insertValue = substr_replace($insertValue, ")", -1);
        } else if (isset($data['module'])) {
            $insertValue = "module_id in ( ";
            foreach ($data['module'] as $key => $value) {
                $insertValue .= " {$value},";
            }
            $insertValue = substr_replace($insertValue, ")", -1);
        } else {
            return [];
        }

        $sql = "SELECT minTable.module_id, progress.url,module.name,module.\"chatID\",module_url.module_url
        FROM(SELECT MIN(id)as id, module_id
            FROM setting.progress
            WHERE {$insertValue}
            GROUP BY module_id) AS minTable
        LEFT JOIN setting.progress on minTable.id = progress.id
        LEFT JOIN setting.module on module.id = progress.module_id
		LEFT JOIN json_to_recordset('[{\"module_url\":\"/quotationModule/out/\",\"module_id\":1},{\"module_url\":\"/quotationModule/develop/\",\"module_id\":2},{\"module_url\":\"/quotationModule/tech/\",\"module_id\":4},{\"module_url\":\"/quotationModule/order/productionManagement/\",\"module_id\":5}]')
			module_url (module_url text, module_id integer) ON module_url.module_id = module.id
             
             ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getNextUrl($data)
    {
        $sql = " SELECT setting_progress.id ,setting_progress.module_id
            FROM setting.progress setting_progress
            WHERE :url LIKE '%' || setting_progress.url || '?%'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':url', $data['url']);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if (count($result) == 1) {
            $nextUrlID = $result[0]['id'] + 1;
            $tmpUrlmodule = $result[0]['module_id'];
            $sql = " SELECT setting_progress.id ,setting_progress.url
            FROM setting.progress setting_progress
            WHERE  setting_progress.id = :id AND  setting_progress.module_id = :module_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $nextUrlID);
            $stmt->bindValue(':module_id', $tmpUrlmodule);
            $stmt->execute();
            $nextUrlresult = $stmt->fetchAll();

            if (count($nextUrlresult) == 0) {
                $nextUrl = '/';
            } else {
                $nextUrl = $nextUrlresult[0]['url'];
            }
            $ack = array(
                'status' => 'success',
                'url' => $nextUrl
            );

            return $ack;
        } else {
            $ack = array('status' => 'failed');
            return $ack;
        }
    }

    public function getFileInfo($data)
    {
        $business = new Business($this->db);
        $result = $business->getCustomerCodes();
        $customer_code = json_encode($result);
        $sql = "SELECT * ,to_char(file.upload_time, 'YYYY年MM月DD日 HH24:MI:SS') upload_time,customer_outer_code.customer_outer_name
            FROM public.file
            LEFT JOIN (
                SELECT \"客戶代號\" AS customer_outer_code ,\"客戶名稱\" AS customer_outer_name
                    FROM json_to_recordset(
                        '{$customer_code}'
                    ) as setting_customer_code(\"客戶代號\" text,\"客戶名稱\" text)
            ) AS customer_outer_code  ON trim(file.customer) = trim(customer_outer_code.customer_outer_code)
            WHERE file.id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['file_id'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateCustomerSend($data)
    {
        $sql = "UPDATE public.file
        SET  custom_material =:custom_material , custom_titanizing = :custom_titanizing
        WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['file_id'], PDO::PARAM_INT);
        $stmt->bindValue(':custom_material', $data['material']);
        $stmt->bindValue(':custom_titanizing', $data['titanizing']);
        $stmt->execute();

        $ack = array('status' => 'success');
        return $ack;
    }

    public function updateItemno($data)
    {
        $sql = "UPDATE public.file
        SET  itemno=:itemno
        WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['file_id'], PDO::PARAM_INT);
        $stmt->bindValue(':itemno', $data['itemno']);
        $stmt->execute();

        $ack = array('status' => 'success');
        return $ack;
    }

    public function updateDeadline($data)
    {
        $sql = "UPDATE public.file
        SET  deadline=:deadline
        WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        $stmt->bindValue(':deadline', $data['deadline']);
        $stmt->execute();

        $ack = array('status' => 'success');
        return $ack;
    }

    public function deleteFalseProgress($data)
    {
        // return $this->getProgressByModule($data['module_id']);

        // $allProgress = $this->getProgressByModule($data['module_id']);
        // $insertValue = "(";
        // foreach ($allProgress as $key => $value) {
        //     $insertValue .= " progress_id = {$value['id']} OR";
        // }
        // $insertValue = substr_replace($insertValue, ")", -2);
        $sql = "DELETE FROM public.progress
            WHERE file_id = :file_id AND progress_id = :progress_id AND later=false ;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['id'], PDO::PARAM_INT);
        $stmt->bindValue(':progress_id', $data['progress_id'], PDO::PARAM_INT);
        $stmt->execute();
        $ack = array('status' => 'success');
        return $ack;
    }

    public function getFalseProgress($data)
    {

        $sql = "SELECT * 
        FROM
        (
            SELECT progress_id,file_id,update_time, later,ROW_NUMBER () OVER (PARTITION BY progress_id,file_id ORDER BY update_time) FROM public.progress
            WHERE file_id = :file_id
            GROUP BY progress_id,file_id,update_time,later
        ) tmptable
        WHERE tmptable.row_number=1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
        $ack = array('status' => 'success');
        return $ack;
    }

    public function addFalseProgress($data)
    {
        // return $this->getProgressByModule($data['module_id']);

        // $allProgress = $this->getProgressByModule($data['module_id']);
        // $insertValue = "";
        // foreach ($allProgress as $key => $value) {
        //     $insertValue .= " ({$data['id']},{$value['id']},NOW(),false),";
        // }
        // $insertValue = substr_replace($insertValue, "", -1);
        $sql = "INSERT INTO public.progress(file_id, progress_id, update_time, later) 
        VALUES (:file_id, :progress_id , NOW() ,false )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['id'], PDO::PARAM_INT);
        $stmt->bindValue(':progress_id', $data['progress_id'], PDO::PARAM_INT);
        $stmt->execute();
        $ack = array('status' => 'success');
        return $ack;
    }
    public function getProgressByModule($module_id)
    {
        $sql = 'SELECT id, name, module_id, url,show
        FROM setting.progress
        WHERE module_id=:module_id;
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }


    public function getEachQuotation($data, $file_id)

    {
        if (!isset($data)) {
            return array();
        }
        if (count($data) == 0) {
            return array();
        }
        $business = new Business($this->db);
        $result = $business->getMaterial([]);
        $material = json_encode($result);
        $result = $business->getTitanizing([]);
        $titanizing = json_encode($result);
        $result = $business->getHardness([]);
        $hardness = json_encode($result);
        $result = $business->getCC();
        $customerCode = json_encode($result);


        $tmpStr = "(";
        foreach ($data as $key => $value) {
            $tmpStr .= "'{$value}',";
        }
        $tmpStr = substr_replace($tmpStr, ")", -1);

        /* order_name.order_name,quotation.file_id,COALESCE(hardness.hardness,'') AS hardness,COALESCE(titanizing.titanizing,'') AS titanizing,COALESCE(material.material,'') AS material,quotation.cost, quotation.discount, quotation.descript, to_char(quotation.update_time, 'YYYY/MM/DD HH24:MI:SS') update_time, to_char(quotation.deadline, 'YYYY/MM/DD HH24:MI:SS') deadline,quotation.delivery_week, */
        $sql = " SELECT  CASE WHEN order_name.itemno IS NULL THEN '' ELSE order_name.itemno END AS itemno, 
            CASE WHEN  quotation.currency = '' THEN 'USD' ELSE quotation.currency END AS currency,
            CASE WHEN  quotation.num = '' THEN '0' ELSE quotation.num END AS num,
            order_name.order_name,quotation.file_id,COALESCE(hardness.hardness,'') AS hardness,COALESCE(order_name.custom_titanizing,'') AS titanizing,COALESCE(titanizing.origin_value,'') AS origin_titanizing,COALESCE(order_name.custom_material,'') AS material,COALESCE(material.origin_value,'') AS origin_material,quotation.cost, quotation.discount, quotation.descript, to_char(quotation.update_time, 'YYYY/MM/DD HH24:MI:SS') update_time, to_char(quotation.deadline, 'YYYY/MM/DD HH24:MI:SS') deadline,quotation.delivery_range,
            (
                SELECT setting.label
                FROM public.file 
                LEFT JOIN (
                    SELECT label, trim(value) AS value
                    FROM json_to_recordset(
                        '{$customerCode}'
                    ) as setting_customer(label text,value text)
                )AS setting ON setting.value = trim(file.customer)
                WHERE file.id= :file_id
            ) AS customer,
            (
                SELECT trim(file.customer)
                FROM public.file 
                WHERE file.id= :file_id
            ) AS customercode
        
        
            FROM
            (
                SELECT ROW_NUMBER() OVER (PARTITION BY file_id ORDER BY update_time) as RowNum, *
                FROM public.quotation
                WHERE quotation.file_id IN {$tmpStr}
            ) AS quotation
            LEFT JOIN(
                SELECT file_id, string_agg(setting.label, ',') as material,MAX(origin_value) origin_value
                FROM  public.material
                LEFT JOIN (
                    SELECT label, trim(value) AS value,value AS origin_value
                        FROM json_to_recordset(
                            '{$material}'
                        ) as setting_material(label text,value text)
                    )AS setting ON setting.value = material.material_id
                    WHERE file_id IN {$tmpStr}
                    GROUP BY file_id
            )as material ON material.file_id = quotation.file_id
             LEFT JOIN(
                SELECT file_id, string_agg(setting.label, ',') as titanizing,MAX(origin_value) origin_value
                FROM public.titanizing
                LEFT JOIN (
                    SELECT label, trim(value) AS value,value AS origin_value
                    FROM json_to_recordset(
                        '{$titanizing}'
                    ) as setting_titanizing(label text,value text)
                )AS setting ON setting.value = titanizing.titanizing_id
                    WHERE file_id IN {$tmpStr}
                    GROUP BY file_id
            )as titanizing ON titanizing.file_id = quotation.file_id
            LEFT JOIN(
                SELECT file_id, string_agg(setting.label, ',') as hardness
                FROM public.hardness
                LEFT JOIN (
                    SELECT label, trim(value) AS value
                    FROM json_to_recordset(
                        '{$hardness}'
                    ) as setting_hardness(label text,value text)
                )AS setting ON setting.value = hardness.hardness_id
                    WHERE file_id IN {$tmpStr}
                    GROUP BY file_id
            )as hardness ON hardness.file_id = quotation.file_id
            LEFT JOIN public.file AS order_name on order_name.id = quotation.file_id
            WHERE  RowNum = 1
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $file_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuotation($data)
    {
        $sql = "SELECT cost, num, discount, descript, to_char(update_time, 'YYYY/MM/DD HH24:MI:SS') update_time, to_char(deadline, 'YYYY/MM/DD HH24:MI:SS') deadline,delivery_week,delivery_range,currency.currency_code
            FROM public.quotation
            LEFT JOIN public.currency ON currency.currency_name = quotation.currency
            WHERE file_id = :file_id
            ORDER BY update_time DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function postQuotation($data)
    {

        $data = $data['tmpArr'];
        // if($data['deadline'] == 'null'){
        //     $deadline = 'null';
        // }else{
        //     $deadline = "'{$data['deadline']}'";

        // }
        $sql = "INSERT INTO public.quotation(file_id, cost, num, discount, descript, update_time, delivery_range,currency)
            VALUES(:file_id, :cost,:num,:discount, :descript,NOW(),:delivery_range,:currency)
        ";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':file_id', $data['file_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cost', $data['cost'], PDO::PARAM_STR);
        $stmt->bindValue(':num', $data['num'], PDO::PARAM_STR);
        $stmt->bindValue(':discount', $data['discount'], PDO::PARAM_STR);
        $stmt->bindValue(':descript', $data['descript'], PDO::PARAM_STR);
        $stmt->bindValue(':delivery_range', $data['delivery_range'], PDO::PARAM_STR);
        $stmt->bindValue(':currency', $data['currency'], PDO::PARAM_INT);

        $stmt->execute();
        $ack = array('status' => 'success');
        return $ack;
    }

    public function getClassificationNum($data)
    {
        $sql = "UPDATE public.file
        SET order_name=:order_name
        WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_name', $data['order'], PDO::PARAM_STR);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_STR);
        $stmt->execute();
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    public function insertOrderSerial($data)
    {
        $sql = "UPDATE public.file
        SET order_name=:order_name
        WHERE id=:id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_name', $data['order'], PDO::PARAM_STR);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_STR);
        $stmt->execute();
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    public function stopProcess($data)
    {
        $processStr = '(';
        foreach ($data['id'] as $key => $value) {
            $processStr .= " {$value},";
        }
        $processStr = substr($processStr, 0, -1);
        $processStr .= " )";

        $sql = "UPDATE public.process
        SET status_id = 3
        WHERE id in {$processStr};";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    function insertComponent($data)
    {
        $data = $data['data'];
        $ack = [];
        foreach ($data as $key => $value) {
            $sql = 'INSERT INTO public.component(name)
                VALUES(:name)
            ;';
            /* $sql = 'INSERT INTO public.component(name)
                SELECT :name
                WHERE NOT EXISTS (
                    SELECT *
                    FROM public.component
                    WHERE name = :name
                )
            ;'; */
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':name', $key);
            $stmt->execute();
            $component_id = $this->db->lastInsertId();

            /* $sql ="SELECT component.id
                FROM public.component
                WHERE name = :name
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':name',$key);
            $stmt->execute();
            $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $component_id = null;
            foreach ($components as $key => $component) {
                $component_id = $component['id'];
            } */
            if (is_null($component_id)) {
                continue;
            }
            array_push($ack, $component_id);
            foreach ($value as $componentkey => $componentvalue) {
                $sql = 'UPDATE public.crop
                SET  component_id=:component_id
                WHERE  id= :id;';
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':component_id', $component_id, PDO::PARAM_STR);
                $stmt->bindValue(':id', $componentvalue, PDO::PARAM_STR);
                $stmt->execute();
            }
        }
        return $ack;
    }

    function deleteTextrecog($data)
    {
        $id =  $data['id'];
        $valData = $data['value'];
        $infoData = $data['info'];
        // return $valData;

        foreach ($infoData as $key => $value) {
            // var_dump($value['name']);
            // var_dump($valData[$key]);
            $sql = 'INSERT INTO public.modify("fileID", x, y, width, height)
                SELECT :id, :x, :y, :width, :height
                WHERE NOT EXISTS(
                    SELECT *
                    FROM public.modify
                    WHERE "fileID" = :id AND x = :x AND y=:y AND width=:width AND height = :height
                )
                
                ;
            ';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            // $stmt->bindValue(':modify', $valData[$key], PDO::PARAM_STR);
            $stmt->bindValue(':x', $value['x'], PDO::PARAM_STR);
            $stmt->bindValue(':y', $value['y'], PDO::PARAM_STR);
            $stmt->bindValue(':width', $value['width'], PDO::PARAM_STR);
            $stmt->bindValue(':height', $value['height'], PDO::PARAM_STR);
            $stmt->execute();
            //  return $value['x'];

            $sql = 'UPDATE public.modify
            SET isdelete= true
            WHERE "fileID" = :id AND x = :x AND y=:y AND width=:width AND height = :height;
            ';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':x', $value['x'], PDO::PARAM_STR);
            $stmt->bindValue(':y', $value['y'], PDO::PARAM_STR);
            $stmt->bindValue(':width', $value['width'], PDO::PARAM_STR);
            $stmt->bindValue(':height', $value['height'], PDO::PARAM_STR);
            $stmt->execute();
        }
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    function getModifyTextrecog($data)
    {

        $sql = 'SELECT "fileID", modify, x, y, width, height, id , isdelete
        FROM public.modify
        WHERE "fileID" = :id;
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }
    public function getProcessIdForMatch($file_id)
    {
        $sql = "UPDATE public.process
            SET status_id = 3
            WHERE file_id = :file_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $file_id);
        $stmt->execute();

        $sql = "SELECT \"FileName\" filename
            FROM public.file
            WHERE file.id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $file_id, PDO::PARAM_STR);
        $stmt->execute();
        $cropArr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = count($cropArr);


        $sql = 'INSERT INTO public.process(file_id, "timestamp", total)
            VALUES (:file_id, NOW(), :total);
            ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $file_id, PDO::PARAM_STR);
        $stmt->bindValue(':total', $total, PDO::PARAM_STR);
        $stmt->execute();
        $tmpID = $this->db->lastInsertId();

        $ack = array(
            'process_id' => $tmpID,
            'components' => $cropArr,
            'file_id' => $file_id

        );
        return $ack;
    }

    public function insertTextrecog($data)
    {
        $id =  $data['id'];
        $valData = @$data['value'];
        if (is_null($valData))
            $valData = [];
        $infoData = @$data['info'];
        if (is_null($infoData))
            $infoData = [];
        // return $valData;

        foreach ($infoData as $key => $value) {
            // var_dump($value['name']);
            // var_dump($valData[$key]);
            $sql = 'INSERT INTO public.modify("fileID",modify, x, y, width, height)
                VALUES(:id,:modify , :x, :y, :width, :height)
                ON CONFLICT ("fileID", x, y, width, height)
                DO UPDATE 
                SET "modify"=:modify
            ';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':modify', $valData[$key], PDO::PARAM_STR);
            $stmt->bindValue(':x', $value['x'], PDO::PARAM_STR);
            $stmt->bindValue(':y', $value['y'], PDO::PARAM_STR);
            $stmt->bindValue(':width', $value['width'], PDO::PARAM_STR);
            $stmt->bindValue(':height', $value['height'], PDO::PARAM_STR);
            $stmt->execute();
        }
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    public function postTextrecog($data)
    {
        $id =  $data['id'];
        $valData = $data['value'];
        $infoData = $data['info'];
        $widthRadio = $data['widthRadio'];
        $heightRadio = $data['heightRadio'];
        // return $valData;

        foreach ($infoData as $key => $value) {
            // var_dump($value['name']);
            // var_dump($valData[$key]);
            $sql = 'INSERT INTO public.modify("fileID", x, y, width, height)
                SELECT :id, :x, :y, :width, :height
                WHERE NOT EXISTS(
                    SELECT *
                    FROM public.modify
                    WHERE "fileID" = :id AND x = :x AND y=:y AND width=:width AND height = :height
                )
                
                ;
            ';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            // $stmt->bindValue(':modify', $valData[$key], PDO::PARAM_STR);

            $tmpx = floor($value['x'] / $widthRadio);
            $tmpy = floor($value['y'] / $heightRadio);
            $tmpwidth = floor($value['width'] / $widthRadio);
            $tmpheight = floor($value['height'] / $heightRadio);
            $stmt->bindValue(':x', $tmpx, PDO::PARAM_STR);
            $stmt->bindValue(':y', $tmpy, PDO::PARAM_STR);
            $stmt->bindValue(':width', $tmpwidth, PDO::PARAM_STR);
            $stmt->bindValue(':height', $tmpheight, PDO::PARAM_STR);
            $stmt->execute();
        }
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    public function getProcessIdByFileId($data)
    {

        $sql = "SELECT component_id,id
        FROM public.crop
        WHERE \"fileID\" = :file_id AND component_id IS NOT NULL;
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id'], PDO::PARAM_STR);
        $stmt->execute();
        $component_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($component_ids as $key => $component_id) {
            $sql = 'SELECT MAX(process.id)id
                FROM public.process
                WHERE component_id = :component_id;
                ';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':component_id', $component_id['component_id'], PDO::PARAM_STR);
            $stmt->execute();

            $process_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($process_ids as $key => $process_id) {
                array_push(
                    $result,
                    array(
                        'process_id' => $process_id['id'],
                        'components' => $component_id['component_id'],
                        'crop_id' => $component_id['id']
                    )
                );
            }
        }
        return $result;
    }

    public function getProcessId($component_id)
    {


        $sql = 'SELECT name filename
        FROM public.crop
        WHERE component_id = :component_id;
            ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':component_id', $component_id, PDO::PARAM_STR);

        $stmt->execute();
        $cropArr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = count($cropArr);

        $sql = "UPDATE public.process
        SET status_id = 3
        WHERE component_id = :component_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':component_id', $component_id, PDO::PARAM_STR);
        $stmt->execute();

        $sql = 'INSERT INTO public.process(component_id, "timestamp", total,status_id)
            VALUES (:component_id, NOW(), :total, 3);
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':component_id', $component_id, PDO::PARAM_STR);
        $stmt->bindValue(':total', $total, PDO::PARAM_STR);
        $stmt->execute();
        $tmpID = $this->db->lastInsertId();

        $ack = array(
            'process_id' => $tmpID,
            'components' => $cropArr,
            'component_id' => $component_id

        );
        return $ack;
    }

    public function insertResultMatch($data)
    {
        $process_id = -1;
        $finish = 0;
        $total = 0;
        foreach ($data['data'] as $key => $value) {
            $process_id = $value['process_id'];
            $finish = $value['finish'];
            $total = $value['total'];
            // return $value['confidence'];
            $sql = "INSERT INTO public.result(process_id, filename, confidence)
                VALUES (:process_id, :filename, :confidence);
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':process_id', $value['process_id'], PDO::PARAM_STR);
            $stmt->bindValue(':filename', $value['filename'], PDO::PARAM_STR);
            $stmt->bindValue(':confidence', $value['confidence'], PDO::PARAM_STR);

            $stmt->execute();
            // $result = $stmt->fetchAll();
        }

        $sql = "UPDATE public.process
            SET finish = :finish, total = :total
            WHERE process.id = :process_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $process_id, PDO::PARAM_STR);
        $stmt->bindValue(':finish', $finish, PDO::PARAM_STR);
        $stmt->bindValue(':total', $total, PDO::PARAM_STR);
        $stmt->execute();

        $sql = "SELECT status.name status
            FROM public.process
            LEFT JOIN status ON process.status_id = status.id
            WHERE process.id = :process_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $process_id, PDO::PARAM_STR);
        $stmt->execute();
        foreach ($stmt->fetchAll() as $key => $value) {
            return $value;
        }
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    public function getComponentMatch($data)
    {
        $sql = "WITH result AS (
                SELECT JSON_AGG(JSON_BUILD_OBJECT('crop_id',crop.id,'crop_img',CONCAT('/file/',crop_id),'confidence',result.confidence,'source',crop_org.id)) crop_ids,AVG(result.confidence) AVG,component.name,file.order_name,crop.\"fileID\",crop.\"fileID\" id,JSON_AGG(DISTINCT JSONB_BUILD_OBJECT('id', crop_org.id)) org_ids,comment_process.comment ,CONCAT('/file/',crop.\"fileID\") AS img, CASE WHEN comment_process.comment IS NULL THEN 0 ELSE 1 END AS checked,comment_process.material,comment_process.stuff,comment_process.process,comment_process.outsourcer_cost,comment_process.outsourcer_comment,file.order_serial
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
                WHERE result.process_id = :process_id
                GROUP BY component.name,crop.\"fileID\",file.order_name,comment_process.comment,comment_process.material,comment_process.stuff,comment_process.process,comment_process.outsourcer_cost,comment_process.outsourcer_comment,file.order_serial
                ORDER BY MAX(result.confidence) DESC
            )
            SELECT result.id,result.img,result.checked,result.crop_ids,result.avg,result.name,result.org_ids,result.\"fileID\",result.order_name,result.comment,result.material,result.stuff,result.process,result.outsourcer_cost,result.outsourcer_comment,result.order_serial
            FROM result
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
    public function getProcessStatus($data)
    {
        $sql = "SELECT CASE WHEN finish=total OR NOW()-file.upload_time > '1 days'::interval THEN 'stop' ELSE status.name END status
            FROM public.process
            LEFT JOIN status ON process.status_id = status.id
            LEFT JOIN crop ON process.component_id = crop.component_id
            LEFT JOIN file ON file.id = crop.\"fileID\"
            WHERE process.id = :process_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id'], PDO::PARAM_STR);
        $stmt->execute();
        foreach ($stmt->fetchAll() as $key => $value) {
            return $value;
        }
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    public function getResultMatch($data)
    {
        $sql = "SELECT file.id,result.confidence,file.order_name,file_mapping.id file_mapping_id,JSON_AGG(JSON_BUILD_OBJECT('comment',comment.comment,'module_name',module.name)) \"comment\"
            FROM result
            INNER JOIN file ON file.\"FileName\" = result.filename
            LEFT JOIN process ON result.process_id = process.id
            LEFT JOIN file_mapping ON file_mapping.file_id = process.file_id AND file.id = file_mapping.file_id_destination
            LEFT JOIN comment ON file_mapping.id = comment.file_mapping_id
            LEFT JOIN setting.module ON comment.module_id = module.id
            WHERE process_id = :process_id AND result.confidence >= :threshold
            GROUP BY file.id,result.confidence,file.order_name,file_mapping.id
            ORDER BY result.confidence DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id'], PDO::PARAM_STR);
        $stmt->bindValue(':threshold', $data['threshold'], PDO::PARAM_STR);
        $stmt->execute();
        $orderresult = $stmt->fetchAll();
        $result = [];

        if (!isset($data['year']) && !isset($data['material']) && !isset($data['titanizing'])) {
            foreach ($orderresult as $key => $value) {
                array_push($result, $value);
                if (count($result) >= $data['amount']) {
                    return $result;
                }
            }
            return $orderresult;
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

        // return $tmpStr;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT [COPTB].[TB201] AS name
                FROM [MIL].[dbo].[COPTC],[MIL].[dbo].[COPTD],[MIL].[dbo].[COPTB]
                WHERE COPTC.TC001=COPTD.TD001 
                    and COPTC.TC002 = COPTD.TD002
                    and COPTD.TD002=COPTB.TB002
                    and COPTD.TD003=COPTB.TB003
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
                            return $result;
                        }
                        break;
                    }
                }
            }
        }

        return $result;
    }
    public function insertResult($data)
    {
        $process_id = -1;
        foreach ($data['data'] as $key => $value) {
            $process_id = $value['process_id'];
            $finish = $value['finish'];
            $total = $value['total'];
            // return $value['confidence'];
            $sql = "INSERT INTO public.result(process_id, filename, confidence, source)
                VALUES (:process_id, :filename, :confidence, :source);
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':process_id', $value['process_id'], PDO::PARAM_STR);
            $stmt->bindValue(':filename', $value['filename'], PDO::PARAM_STR);
            $stmt->bindValue(':confidence', $value['confidence'], PDO::PARAM_STR);
            $stmt->bindValue(':source', $value['source'], PDO::PARAM_STR);

            $stmt->execute();
        }


        $sql = "UPDATE public.process
            SET finish = :finish, total = :total
            WHERE process.id = :process_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $process_id, PDO::PARAM_STR);
        $stmt->bindValue(':finish', $finish, PDO::PARAM_STR);
        $stmt->bindValue(':total', $total, PDO::PARAM_STR);
        $stmt->execute();

        $sql = "SELECT status.name status
            FROM public.process
            LEFT JOIN status ON process.status_id = status.id
            WHERE process.id = :process_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $process_id, PDO::PARAM_STR);
        $stmt->execute();
        foreach ($stmt->fetchAll() as $key => $value) {
            return $value;
        }
        $ack = array(
            'status' => 'success'
        );
        return $ack;
    }

    public function getBase64_encode($file){
        if(!is_null($file)){
            $type = pathinfo($file, PATHINFO_EXTENSION);
            return 'data:image/' . $type  . ';base64,' . base64_encode(file_get_contents($this->container->upload_directory. DIRECTORY_SEPARATOR.$file));
        }
    }

    public function postsimilaritypic($data){

        // $home = new Home($this->db);
        $fileName = $data['FileName'];
        $type = pathinfo($fileName, PATHINFO_EXTENSION);
        $org_src = 'data:image/' . $type  . ';base64,' . base64_encode(file_get_contents($this->container->upload_directory. DIRECTORY_SEPARATOR  .$fileName));
        // $result = $home->http_response($recogUrl);
        // $result = json_decode($result,true);

        // $cropfileStr = '';
        // foreach ($result['Crop_file'] as $key => $crop) {
        //     $cropfileStr .= "%22../uploads/Crop/{$crop}%22,";
        // }
        // $cropfileStr = substr_replace($cropfileStr, "", -1);

        // $values = [
        //     "customer" => "1010150",
        //     "item_type" => "07"
        // ];
        // foreach ($values as $key => $value) {
        //     array_key_exists($key,$data)&&$values[$key]=$data[$key];
        // }

        // $curl_recognition = "http://mil_python:8090/recognition/{$values['customer']}/{$values['item_type']}?top_k=10&crops={%22paths%22:[{$cropfileStr}]}";
        // $result = $home->http_response($curl_recognition);
        // $result = json_decode($result);
        $result = [
            [
                "101015007066003",
                "101015007064002",
                "101015007298002",
                "101015007099002",
                "101015007211005",
                "101015007047002",
                "101015007210002",
                "101015007124005",
                "101015007106003",
                "101015007073002"
            ],
            [
                28.700462341308594,
                26.055721282958984,
                23.87960433959961,
                22.74443817138672,
                21.286890029907227,
                20.97995376586914,
                20.48426055908203,
                19.639057159423828,
                18.778255462646484,
                18.54570770263672
            ]
        ];
        $images = [];
        foreach ($result as $key => $row) {
            foreach ($row as $key =>$value) {
                // $base64 = $this->container->recognition_directory. DIRECTORY_SEPARATOR  ."images". DIRECTORY_SEPARATOR. substr($value,0,7) .DIRECTORY_SEPARATOR;/*  . "2080140" .DIRECTORY_SEPARATOR */
                // // $files  = scandir($base64);
                // $files = preg_grep('/'.$value.'(.*)\.(gif|jpe?g|tiff?|png|webp|bmp)$/i', scandir($base64));
                // $file = array_pop($files);
                // if(!is_null($file)){
                //     $type = pathinfo($file, PATHINFO_EXTENSION);
                //     $images[] = 'data:image/' . $type  . ';base64,' . base64_encode(file_get_contents($this->container->recognition_directory. DIRECTORY_SEPARATOR  ."images". DIRECTORY_SEPARATOR. substr($value,0,7) .DIRECTORY_SEPARATOR.$file));
                // }else
                    $images[] = '';
            }
            break;
        }
        $result[] = $images;
        $result = array_map(function($itemno,$similarity,$src){
            return ["itemno"=>$itemno,'similarity'=>$similarity,"src"=>$src];
        },$result[0],$result[1],$result[2]);
        $result = json_decode('[{"org_src":"'.$org_src.'","itemno":"101015007311002","similarity":22.716510772705078,"src":"data:image/jpg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAkjBnUDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAoopGZUUsxAUDJJPAFAC0UUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQBBFeQT3VxbRuTLblRKNpAUsMjnGDx6VPRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRUMcMiTySNdSyK3SJgm1PphQfzJoAmooooAKKKKACiiigAqjrRVdC1BnXeotpCVzjI2njNXqo60duhag25lxbSHcvUfKeRQA/TXke0bzEkQrNKirJGEO1ZGC4AJG3aBg9SMEgE4q3WP4Z8v+yp/KkjkX+0L3JQYAP2qXI+6vIOQeOoPLfeOxQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVV1NVfSrxW27TA4O5goxtPUkED6kEVaqvfukWnXMkhYIsTsxXbkAA9N3H58etAGL4IMreGi0zK0hv74lhs5/0qXk7Plz6475710Vc94JjaHw0I2QoVvbwbTbiD/l5l/gHAHp69e9dDQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVDduEsp3LbQsbEtvCY467j0+vapqq6m/laVeSA42wO2eOMKf73H58UAYfgGKOHwlHHFIsii8vMlU2AH7VLkAexyPwrpq5rwFLBceEIJ7Z43t5rm6liaJY1Xa1xIRxGSo4Prn1wciuloAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACikLKCASAWOBk9TS0AFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABUF7IIrC4kO/CRM3yfe4B6e9T1W1GJZ9Mu4mkEavC6lyu4KCpGcd/pQBk+DIBa+HFhCIgS7uxtSNkH/HxJ2bn/AB6jit+sDwaip4cUKCM3d2xz3JuJCT9xOCTkfKOvfqd+gAooooAKKKKACiiuZ8EeKf8AhKNGmlnaAX9tczQXUMII8orK6oCCSclAp69+3SgDpqKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKgvQDYXALBQYm5PQcGp6gvVRrC4WVkWMxMGZ8bQMHJOQRj6gj2oAyvCLtJ4djZxAHNxc58iJYlJ89+dqkjPqepOSeSa3K5vwHcG78IwXJdXEtxdOrrKZAym4kIIYs2eMfxH8OldJQAUUUUAFFFFABXlvwI1Z9R8ESwvcyzfZ7mTaJINmwO7P9/o5JJJx0yBXqVeQ/s92k1t4KuZH1O2uYp5xJHbwzl2tuoKuv8AATjOO4waAPXqKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKiuQTazAAk7G4C7j09O/0qWq9+A2nXKssbAxOCsmdp4PBxzj6UAYfgJ1fwZZMlpJaJvmCW8iBGiXzXwpUcDAwMD9etdJXO+BIGtvA+kQuzMUgwMo6hRk4VQ/zbQMBScZUAgAcDoqACiiigAooooAK8d/Z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RQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUV5/8H/Fes+MvB9xqeteWZ1vXhjaOLYrRhEPHr8xYZ9vagD0CiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPN/jo9snwrv/ALRC0jNLEsODja+8fMfw3ev9a6jwJ/yTzw1/2CrX/wBFLXM/HG1F18LdQJWdvKkjlHkxh8Ybq3IwvPJ7eldN4E/5J54a/wCwVa/+iloA6CiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigApFYMMjPUjkYpaKACiiigAooooAKKKKACs/Xv+Re1P/r0l/wDQDWhWfr3/ACL2p/8AXpL/AOgGgCTSVCaNYqvRbeMDgj+EdjyPxq5VbTkMemWiMCCsKAg7s/dH94A/mAfWrNABRRRQAUUUUAZPii2S88J6vbyNIqSWcoJjcq33D0IrhfgD/wAkvg/6+5v5ivQNe/5F7U/+vSX/ANANef8AwB/5JfB/19zfzFAHqFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAHl/if/AJOA8Df9el3/AOipK9QrzPxSqj47+A3x8xt70E7weBC/8PUdTyeD26GvTKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiss+ItMXxIvh9p5BqbQG4WEwSYMY4LB9u3rx161qUAFFFFABRRRQAUUUUAFFFFABXGav8VvBeharcaZqesPbXtu22WJ7OclTjI5CYIIIII4IOa7OuM8cfDPQPHFpIbm2jttTIHl6hFH+8XBH3sEbxgYw2cA8YoApf8Lt+Hn/AEMP/klcf/G68/8AhF8QfB/gzw3qWk6pr/I1SV7eT7JM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AUUUUAFFFFABVDXCBoGpEqGAtZeD0Pymr9UdaBOhagAm8m2kwvPzfKeOKAJrBNmnWyBVXbEg2qhQDgcBW5H0PIqxUNogjsoEVWULGoAbORx3ySfzJqagAooooAKKKKAMTxhLPD4P1aW2juJJ1tmMa2zSLIWxxtMfzA+4rjfgO0j/AA1RpXLyNezlmLbix3cnPf611HxCRZPh14hRpY4g1hMN8rbVB2nHP6VzPwIRE+GFvtJDm6n8yMqR5bb8beTzxg/jjtQB6XRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB5l4oCf8L58CEY8z7NeZ/eAnHlPj5eo78ng9uhr02vI/EIB/aV8It/pOfsEy/OgEWBFMfkPc8nPpx+HrlABRRRQAV5N8KHhl+IfxLkiiSE/wBpojRIWIyrzAvkk8sckjoCeMDAr1mvJfhIZU8efEqCQyKF1fzBGy4A3STfMPqAPwAoA9aooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArxvxz4v8AG2neMoNNku4vC+gO7bNYNqt0rqBwWByFySBg7eo/H2Sq2oafZ6rYTWN/bR3NrOu2SKVcqwoA5nwj4C0Dw7O+r2cs2pahdorNqd3N58kgI6q3YHPUdRjrVnxl4F0jxvpYsdRMsC+aspmthGJSVDADcytx8x6c++Mg8Y/hDxD8NLyXV/CMt1rGj4kM2gTTv+7U8r5P3ssMY6FiOOc5HZeCvHejeOdLF1pk4FxGq/abVs74GI6HIGR1ww4OPXIoA3zYWbFS1pASshlUmMcOTksPfPfrViiigAooooAQ7sjBAGecjqKWiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAoopGZUUsxAUDJJPAFAHnHwQuvtvw/e72bPP1C4k25zt3NnGfxr0ivNvgcsKfD1ktyDCt/cCMhsgru4578V6T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UUAFFFFABRRRQB5Cr3C/tPuIFlMbaRicoBgJgEFs9twXp3x2zXr1eOteQW37UaRTQtI91pflQsGx5bhC+4+vyow/4FXsVABRRRQAV5X8JLZV8VfEW6EMoaTXZYzKfuMFeQhR7jeSfZlr1SvP/hZ/zOv/AGNd9/7JQB6BRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRXDeBviCfGUmv3jWIstFsJVS2vJWwJlAYuxJ4GAFPsG5oA7miuXk8d6SvjLSPDcXmXEuq2jXVvdQlWhZQGIwwPOQjnI9vXjqKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArx/xBpfhyX4hXV5onxA/wCEd8RyuiTwoFaJ2UHO9DtDE/L1OMg8Enj2CvLfGmo+BND1xrIeFtP17xVqEiMtkbdHd2bgF3ZSE45/I4xzQBgrN4T1C8t38afFGPxDBbbZIbREFtBuxkM6xk72Ge5yOQe4r22GWOeGOaJg8cihlYdCCMg14V4e0Dwj58upeP8AWPC32y2d1g0i1nt44rNAWJVkjxvbJPBBPA5J6e6QeT9ni+z+X5GweX5eNu3HGMcYxQBJRRRQAUUUUAFFFFABRUc88Nrby3FxLHDBEheSSRgqooGSSTwABzmpKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKqaodukXp88wYgc+cM5j+U/Nxzx14q3VPViBo18WMYH2eTJkBKj5T1A5x9KAPO/gD/yS+D/AK+5v5ivUK8v+AP/ACS+D/r7m/mK9QoAKKKKAOC+NAB+EevAqW+WE4Az/wAto+ev+fet3wJ/yTzw1/2CrX/0UtYHxpnkg+FOs+XcCAuI0LZYFgZFyo2/3hxzxgnNdD4IUJ4A8OKrq4Gl2wDrnDful5GQD+YoA3qKKKACiiigAooooAKKKKAPM/EGkXfgLxLP400C1luNMustrunQkszc5+0RqSBuHOfYn1JHXXPi/SU8G3Xii0uY7zT4Ld51aJvv7Qfl56Ek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uPf4KeBfKkjt9LuLZZFKv5N9N8w9wXINcz4q+EMdvbaFpXhNr2BhfPI00j5jtVMZ3SFgA4cEJtAbBPWgD2eiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDwX9o+5azu/CNyiRO0T3ThZo1kRseTwysCCPYivbtJl8/RrGby44/Mt422RjCrlQcAdhXh37SqeZN4TTj5muhy4Uf8se54H1Ne6acQdMtCpJHkpgllY/dHdeD9Rx6UAWaKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPNraKSP9o28aQDbJ4aDR4JPy+eg/DkGvSa83t7gzftGXaEqfJ8NBBgEY/fq3Pr96vSKACiiigAry39n993wwjG1htvJhkjr0OR+f6V6lXkf7OrMfhzdAzeYBqcoC5P7seXH8vP4njj5vXNAHrlFFFABRRWX4h8Q6Z4X0ebVdXuVt7WPjJ5Z2PRVHcn0/oKANSiqGi6vb69o1rqtokyW10nmRCaMoxU9Dg9iOR6gg06LV9Pm1efSYruN7+3iWWaBTlkVs4J9M46denqKALtFFFABRRRQBT1ZmXRr5lm8lhbyES5I2Hafm45468c14RpngWHWvgLPrKaLYS+IZYJJorhQzySIr/MTycyFQ+MdyOh6e86m0CaVeNdIz24gcyovVk2nIHI7e9eUfDvWRovwq17xKotbLSVaeXTLMNjyVTKqrFiSzu+ByTkkAdgADkPiF4dtvD+l/DSP7FaWWosIo71FiHmySKIskuBztZmz83JYYzjj6Qr51bxH4jtNd8Ha145TTNT03XObe0Nqkn2ZCIisq5HyuTIucHoPXGPoqgAoorn/HGtnw74I1fVVjkkeC3O0RuEbc3yghj0wSD68cZOBQBxnxQ0jUdU+IHw5exs5p47bUXmndFysSK8DEseg4VuvXHFep1434N1NPhn8HYvEOqXVzqwvZElVIZSyoHACqN+NuAPm9/XFbFp8Q7rXviX4csNEuLSXQL/TGvJx8pmVwJBtbBO0ghBj/AHuTjgA9MooooAKKKKACiiigAooooAKKKKACuH+JfjTVfBWm2F1pekf2i1zceQykMdpI+UfKOpPAHeu4rg/ij4r1jw1pemW2gLbHU9WvBZQtOC3l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7l39OvHOPXv65HoSqqKFUAKBgADgCgBaKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA8J+FYVfjn432pvLSXhZ9v+qIuvu5x/EOeD/CeOOPdq8V+ElzMvxX+I1qttugkv5JHn3gbGWeUKu3qdwdjnts969qoAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPIdALD9pfxWAt2VNlDkxn90P3UP+s/p+P4evV4vpBth+1Jr/mtOJzZxiEJjYf3ERbf36DjHevaKACiiigDP17/AJF7U/mVf9El5YuAPkPUx/OP+A/N6c15/wDAH/kl8H/X3N/MV3ficZ8J6yPKeX/QZ/3aQiVn+Q8BDw5P909elcV8CP8AkldiRb+Tmeb5uf3vzkbufy44+WgD0qiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiivOviP8W9O8AXFtYpZHUtRlG97dZvLEUfYs2G5J6DHTJJHGQD0WivmrUP2kvEMl0W03RNMt7fAxHcmSZ89/mVkH6VFbftIeKFuY2utJ0eW3DAyJEkqMy9wGLsAffB+lAH01RXiek/tI6HcZXVtEvrJi4Cm3kW4XHcsTsIx6AGvW9G17SvENhHfaRfw3du6hg0bcgHPUHlTweCAeDQBo0UUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFeRfCO3ktfH/xGhmt7eB1vYMx2+fLAJmIxn2Of8K9dryD4P2J03x38RLQxxxlLuD5InZ1GTMeC3J696APX6KKKACoba7tr2Iy2txFPGHZC8ThgGU4YZHcEEEdiKmrzP4KtC+jeJ2t7eK2hPiO6McELKUjXbHhVK/KQBwMcelAHplFFFABXlfwU+aHxZK0MUUj63NuCyEv9GUkkYycE8nnOcV6pXk/wMQLY+JzugJbWJSQpPmDthu2OOPx9qAPWKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArzX45v5fw73+Wsu2/tz5bJvDfN0K98+nevSq8v8Aj9/yS+f/AK+4f5mgD1CiiigAooooAKKKKACiiigAooooA8h+Eklz/wAJ/wDEWLzoDaf2vK3lZ/eB/Nk+bH90jjnuBjvXr1eN/CSJP+Fl/EWXfbeZ/acq7MnzcedJz6bf1zj8fZKACiqWk6tZa5pcGpadMZrScExuUZCcEg8MARyD1FXaACiiigAooooAKKKKACiiigAooooAKKKKACiiigAopCyggEgFjgZPU0tABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFc/47/wCSeeJf+wVdf+imroK5/wAd/wDJPPEv/YKuv/RTUAZnwmtktPhZ4fjSOSMG2MmJEKEl2ZicEngliQe4IOBnA7OuQ+FtvFa/C/w9HDbvbobRZCjuHJZiWZsgnhiSwHUA4IBGB19ABRRRQB578Yt//CJWG3bj+17Tdn039vxxXoVeefGNWPhLTyuMDV7Qtknpv/xx1r0OgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKZNEs8MkTlwrqVJRyjAEY4YEEH3ByKIolhjCKXIBJ+dyx5OepJNAD6KKKACiiigAooooAKKKKACiiigAooooAKKKKAPBvgneNqHxI8W3c97HLcSqW5tkWSceaR5hZSQmOAUBw24Hnbk+814v8IVeD4mfEOGEKlp9vcFBAwAZZ5QoVwdoABPykZORjhTn2igAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA8a0Qf8AGUfiQ7Jj/oEY3ITtH7qD72B044zjnHfFey147ol/PB+034mso1iMVzZxGUucMNsMRG3nnk8jB4+hr2KgAooooAwvGsMlx4D8RQwxvJLJplyiIikszGJgAAOpNcn8CYvL+FVgfLCl5pmLCQNv+c84H3emMH0z3rqvHLFPh94kZSQw0q6IIPIPlNXMfA2LyvhXp3Em15JXBeEJnLnOME7hnOGPPtgCgD0aiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACvkT4qQDTvip4hi1J7maO4QvESQ7DcgeMBnX5VVtoO3ooKA9a+u65Hx58PNI8fafHDfboLqE5hu4gN6DPK+4Poe/NAGR4b1zQtP8AD/hax8U3mkvrWpacLeB0hyk0JxiPfjaA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fDfw2Y5mlH9nQDcxzg7BlfwOR+FdNQAUUUUAeX/H7/kl8/8A19w/zNeoV5h8fMD4Yy7gSv2uHIBwcZNen0AFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVg+N1D+APEas6oDpdyC7Zwv7puTgE/kK3q5/x3/wAk88S/9gq6/wDRTUAeefs5RQjwNqEqRgStfssjgsd2EUgYIwMbu3rzXsdeNfs5WN3b+DdQuZoJEt7q73QOx+WQKu1io9iMZ9sdq9loAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPKfhLHN/wl3xDlM+YDrkyiHYOG8x8tu69MDHtXq1eU/DOc2nxI+IOkNFImdQ+2DzlKud7MTgDI28gg5BIIOOTj1agAooooA5/x3/yTzxL/ANgq6/8ARTVznwRS9T4WaZ9taQ7mkaHzDnEW47cc9OuM4/LFdH47/wCSeeJf+wVdf+imrP8AhX/yS7w9/wBeg/maAOwooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK8v+B3/IveIv+xguf/QI69Qry34FMX8M+IGZGQnX7klGxlfkj4OCR+RoA9SooooAK8w+B12l94a16eG4muLdteuWgkmZixQrGwJ3EnJzk5JOScknmvT68t+ATNN8PJrqW4jmnudRmmmYNlt5Cg7/APaOAfoR60AepUUUUAFef/B//kULz/sK3f8A6Mr0CvP/AIP/APIoXn/YVu//AEZQB6BRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFeV/tBGUfDIiNVZTexCQk42rhuR+OB+NeqV5J+0NcJH4AsrdnO641KNdgkCblCOTnIPHT6Eg+xAPW6KKKACiiigAooooAKKKKACiiigDz/4P/8AIoXn/YVu/wD0ZXoFcB8IQB4SvQGDAatd8jof3hrv6ACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDy39oDTHv/hhJcrIqiwvIblgR98HMWB+MoP4V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aAPWrS7gv7KC8tZVlt541likXo6MMgj6g1NWD4I2f8IB4c8vds/su227uuPKXGa3qACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK5L4nh2+GPiIIwU/YnOSwXjuMn2zx36V1tcv8R7c3fw48QQqYwTZSEGRgq8DPJJAHTuaAIfhcFX4YeHQrbh9jU5xjnnI/A8V0ep6nY6Np02oaldR2tpCAZJpThVyQB+ZIH41y/wwmjg+FOgSzSJHGtoCzuwAAyepNeCfF/4nHxnqEenaVLcRaNbD5432jzpgx+f5SQVxjHPqaAPqqCeG6t4ri3ljmglQPHJGwZXUjIII4II5zUlQWVnb6dYW9jaRiK2tolhijBJCoowBz6ACp6APIf2jCB8PLPLyDOpxgKuME+XIefyP4169XkX7Rf/ACTq05A/4mcfUDn93L6/0/lmvXaACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK5/x3/wAk88S/9gq6/wDRTV0Fc/47/wCSeeJf+wVdf+imoA4T9ncxN8O7toy5kbU5TKCoUBvLj4XHbG305yMcV63Xj/7OQx4A1Ha2U/tWTblcH/VRdefpXsFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB5P8MHnn+I/xCku7WRp01EoLuR/m8sMwSPB5xtVSD0xj2r1ivH/AIbxC2+M3xBi3qS0qyY8pATuct1Xpjd9TwTyOPYKACqdnqtjqFzd29pcxzTWcnlXCL1jfGcH8KuV8val491HwJ8b9eubdy9hNe7by2Y/LImByPRhzg/0JFAH0D47/wCSeeJf+wVdf+imrP8AhX/yS7w9/wBeg/maXxHremeIPhZ4jv8ASb2G8tW0q6AkibIB8luD3B5HB5pPhX/yS7w9/wBeg/maAOwooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK8r+BsjnRvEsZhYRrrs7LLkYYlUyuOvGAfT5hjvXqleWfAmMQ+F9fiDOwTXrhdztuY4SLknu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Vtcm2uLi2kiinAyYmZSA34E5/CsrwL4afwh4M07QpLhbh7UPulUYDFnZzgf8CxQB0VFFFAGH4zunsvBOt3KPEjR2UrBpgxQfKeoXn8q5n4IBh8ItFLNuBM5UYxtHnyce/OT+NdP4we2j8G6w15cPbwfZJN0qSFCvynGCORziuY+CCBfhFopBbLmcnLE/8t5Bxnp06D696APQqKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAryj4omRfiP8NwkqYfUXzFOV8o4aLnDcb8MQvfJGOcZ9Xryn4oOI/iX8NCRbEG/mH+kqSvJhHGP4uflPZtpoA9WooooAKKKKACiiigAooooAKqapEZ9IvYQruZIHXan3jlSMD3q3UVzbx3drNbS5McyNG2Dg4IwaAPNvgLG8XwzjjkRkdLydWVhgggjIIr06vOvgrbx2ngWa2iyI4dSuY1ycnAfAr0WgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACuP+Kn/ACS7xD/16H+YrsK4/wCKn/JLvEP/AF6H+YoAm+GkUkXw08OLKrBjYRsAzbuCMj9COO3Suqrl/hu8L/DXw4YN2z+z4QdxJ+YKA3X/AGs11FABRRRQB5D+0WwTwBprMiuBq8RKNnDfupeDgg/ka9dVldQykFSMgg8EV5B+0d/yTzT/APsKx/8AoqWvWLCIwadbQt5mY4kU+YQW4AHOCRn6E0AWKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAryP8AaHdI/AulvIJCi6xEWEb7GI8qXOGwcH3wceleuV5F+0SofwHpisHKnV4gQg+YjypenvQB67RRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAeWfCBPL1rx6nlmMDXZcKc8Dc2OvPT1r1OvLfg84n1LxxdRxNHFLrkuAzEnOSTnPPfPPrXqVABRRRQB518cZ5IfhTqmx0Ad4UYNHv3AyLwOMA98+3HOK2NYdYvhFfStFH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AOG8N/DsWurxeJPEuo3Gs6/jerzHENqxGGESDgemfYEAGu7oooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPLbxJV/aQ053uI3jbRnCRq+WjwWzkdsnn3/CvUq8sujB/wANIWCxLaiX+yHMpjUiQnnG89DwBjvjr2r1OgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKrXen2t95f2mISeWwZQScAgg/jyBVmgAooooAKKKKACiiigAooooAKKKKACiiigDyn9oOXZ8NgnmxL5l5ENjnDPjJ+X6d/avVq8f/aLhEngOyclsxX6sACgHKsMnPzHr/D689sewUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABXP+O/8AknniX/sFXX/opq6Cuf8AHf8AyTzxL/2Crr/0U1ACeAlC/Dvw0Bn/AJBVseTn/lktdDXP+BP+SeeGv+wVa/8Aopa6CgAooooAKKKKACiiigAooooAKKKKACiiigAooqGO7tpbqa1juInuIArSxK4Lxhs7Sw6jODjPXBoAmoqCa9tbe5t7ea4jjnuWKwRswDSEKWIUd8AE0t3d29haTXd3NHBbwoXklkbCoo6kmgCaim+Ynl+ZvXZjduzxj1zTBcwNLJEJ4zJEVEiBxlC33cjtnt60AS0VUk1TT4ZVilv7VJGAKo0ygkHpgZ71TvfFOgadYyXt1rFlHbREB5POUgEnA6UAa9FYGoeN/C+lTSQ32vWEEscSTMjTDOxsbTgdc5B+hB6Vnf8AC1PA3/QzWP8A30f8KAOworkT8UfA4jWT/hJtPwSQB5nPGO3Udevfn0NI3xS8DKxU+JrDIOOHJH54oA6+iuP/AOFqeBv+hmsf++j/AIUf8LU8Df8AQzWP/fR/woA7CiuP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QAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB4r+0lu/wCER0jEeV+3nL+XnB8tsDd2zzx3x7V7VXi37R2x/DOiwswV3vjtYqMD5CDluoHPb057Y9poAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPEv2j2kXRPD7RZ8wXrFcDJzt44r22vG/j86xW/hWR/N2rqe4+TJ5b4AH3Wwdp9Dg4NeyUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQByWm6d4otviTqt7c35n8N3NogghZx+6mUjAVewwXye/Gegx1tFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB498eREV8IidisJ1UeYRjIXjPXjp68V7DXl/xZ/5GH4e/9jBB/wChpXqFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRSFVJBIBKnIyOhpaACiiigAooooAKKKKACiiigAooooAKKKKAPL/iz/wAjD8Pf+xgg/wDQ0r1CvL/iz/yMPw9/7GCD/wBDSvUKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAKl7cXcDQfZbE3Su4EpEqoY1/vc9fpVuiigAooooAKKKKACiiigAooooAKKKKACiiigDzH4rxu/iD4fFUZgPEEGSBnHzKf5A/lXp1FFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQA07/MXG3Zg59c8Y/r+lOoooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAopFYMoYZwRnkYP5UtABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB/9k="},{"org_src":"data:image/png;base64,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","itemno":"101015007311002","similarity":22.716510772705078,"src":"data:image/jpg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAkjBnUDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAoopGZUUsxAUDJJPAFAC0UUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQBBFeQT3VxbRuTLblRKNpAUsMjnGDx6VPRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRUMcMiTySNdSyK3SJgm1PphQfzJoAmooooAKKKKACiiigAqjrRVdC1BnXeotpCVzjI2njNXqo60duhag25lxbSHcvUfKeRQA/TXke0bzEkQrNKirJGEO1ZGC4AJG3aBg9SMEgE4q3WP4Z8v+yp/KkjkX+0L3JQYAP2qXI+6vIOQeOoPLfeOxQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVV1NVfSrxW27TA4O5goxtPUkED6kEVaqvfukWnXMkhYIsTsxXbkAA9N3H58etAGL4IMreGi0zK0hv74lhs5/0qXk7Plz6475710Vc94JjaHw0I2QoVvbwbTbiD/l5l/gHAHp69e9dDQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVDduEsp3LbQsbEtvCY467j0+vapqq6m/laVeSA42wO2eOMKf73H58UAYfgGKOHwlHHFIsii8vMlU2AH7VLkAexyPwrpq5rwFLBceEIJ7Z43t5rm6liaJY1Xa1xIRxGSo4Prn1wciuloAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACikLKCASAWOBk9TS0AFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABUF7IIrC4kO/CRM3yfe4B6e9T1W1GJZ9Mu4mkEavC6lyu4KCpGcd/pQBk+DIBa+HFhCIgS7uxtSNkH/HxJ2bn/AB6jit+sDwaip4cUKCM3d2xz3JuJCT9xOCTkfKOvfqd+gAooooAKKKKACiiuZ8EeKf8AhKNGmlnaAX9tczQXUMII8orK6oCCSclAp69+3SgDpqKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKgvQDYXALBQYm5PQcGp6gvVRrC4WVkWMxMGZ8bQMHJOQRj6gj2oAyvCLtJ4djZxAHNxc58iJYlJ89+dqkjPqepOSeSa3K5vwHcG78IwXJdXEtxdOrrKZAym4kIIYs2eMfxH8OldJQAUUUUAFFFFABXlvwI1Z9R8ESwvcyzfZ7mTaJINmwO7P9/o5JJJx0yBXqVeQ/s92k1t4KuZH1O2uYp5xJHbwzl2tuoKuv8AATjOO4waAPXqKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKiuQTazAAk7G4C7j09O/0qWq9+A2nXKssbAxOCsmdp4PBxzj6UAYfgJ1fwZZMlpJaJvmCW8iBGiXzXwpUcDAwMD9etdJXO+BIGtvA+kQuzMUgwMo6hRk4VQ/zbQMBScZUAgAcDoqACiiigAooooAK8d/Z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RQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUV5/8H/Fes+MvB9xqeteWZ1vXhjaOLYrRhEPHr8xYZ9vagD0CiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPN/jo9snwrv/ALRC0jNLEsODja+8fMfw3ev9a6jwJ/yTzw1/2CrX/wBFLXM/HG1F18LdQJWdvKkjlHkxh8Ybq3IwvPJ7eldN4E/5J54a/wCwVa/+iloA6CiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigApFYMMjPUjkYpaKACiiigAooooAKKKKACs/Xv+Re1P/r0l/wDQDWhWfr3/ACL2p/8AXpL/AOgGgCTSVCaNYqvRbeMDgj+EdjyPxq5VbTkMemWiMCCsKAg7s/dH94A/mAfWrNABRRRQAUUUUAZPii2S88J6vbyNIqSWcoJjcq33D0IrhfgD/wAkvg/6+5v5ivQNe/5F7U/+vSX/ANANef8AwB/5JfB/19zfzFAHqFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAHl/if/AJOA8Df9el3/AOipK9QrzPxSqj47+A3x8xt70E7weBC/8PUdTyeD26GvTKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiss+ItMXxIvh9p5BqbQG4WEwSYMY4LB9u3rx161qUAFFFFABRRRQAUUUUAFFFFABXGav8VvBeharcaZqesPbXtu22WJ7OclTjI5CYIIIII4IOa7OuM8cfDPQPHFpIbm2jttTIHl6hFH+8XBH3sEbxgYw2cA8YoApf8Lt+Hn/AEMP/klcf/G68/8AhF8QfB/gzw3qWk6pr/I1SV7eT7JM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AUUUUAFFFFABVDXCBoGpEqGAtZeD0Pymr9UdaBOhagAm8m2kwvPzfKeOKAJrBNmnWyBVXbEg2qhQDgcBW5H0PIqxUNogjsoEVWULGoAbORx3ySfzJqagAooooAKKKKAMTxhLPD4P1aW2juJJ1tmMa2zSLIWxxtMfzA+4rjfgO0j/AA1RpXLyNezlmLbix3cnPf611HxCRZPh14hRpY4g1hMN8rbVB2nHP6VzPwIRE+GFvtJDm6n8yMqR5bb8beTzxg/jjtQB6XRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB5l4oCf8L58CEY8z7NeZ/eAnHlPj5eo78ng9uhr02vI/EIB/aV8It/pOfsEy/OgEWBFMfkPc8nPpx+HrlABRRRQAV5N8KHhl+IfxLkiiSE/wBpojRIWIyrzAvkk8sckjoCeMDAr1mvJfhIZU8efEqCQyKF1fzBGy4A3STfMPqAPwAoA9aooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArxvxz4v8AG2neMoNNku4vC+gO7bNYNqt0rqBwWByFySBg7eo/H2Sq2oafZ6rYTWN/bR3NrOu2SKVcqwoA5nwj4C0Dw7O+r2cs2pahdorNqd3N58kgI6q3YHPUdRjrVnxl4F0jxvpYsdRMsC+aspmthGJSVDADcytx8x6c++Mg8Y/hDxD8NLyXV/CMt1rGj4kM2gTTv+7U8r5P3ssMY6FiOOc5HZeCvHejeOdLF1pk4FxGq/abVs74GI6HIGR1ww4OPXIoA3zYWbFS1pASshlUmMcOTksPfPfrViiigAooooAQ7sjBAGecjqKWiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAoopGZUUsxAUDJJPAFAHnHwQuvtvw/e72bPP1C4k25zt3NnGfxr0ivNvgcsKfD1ktyDCt/cCMhsgru4578V6T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UUAFFFFABRRRQB5Cr3C/tPuIFlMbaRicoBgJgEFs9twXp3x2zXr1eOteQW37UaRTQtI91pflQsGx5bhC+4+vyow/4FXsVABRRRQAV5X8JLZV8VfEW6EMoaTXZYzKfuMFeQhR7jeSfZlr1SvP/hZ/zOv/AGNd9/7JQB6BRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRXDeBviCfGUmv3jWIstFsJVS2vJWwJlAYuxJ4GAFPsG5oA7miuXk8d6SvjLSPDcXmXEuq2jXVvdQlWhZQGIwwPOQjnI9vXjqKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArx/xBpfhyX4hXV5onxA/wCEd8RyuiTwoFaJ2UHO9DtDE/L1OMg8Enj2CvLfGmo+BND1xrIeFtP17xVqEiMtkbdHd2bgF3ZSE45/I4xzQBgrN4T1C8t38afFGPxDBbbZIbREFtBuxkM6xk72Ge5yOQe4r22GWOeGOaJg8cihlYdCCMg14V4e0Dwj58upeP8AWPC32y2d1g0i1nt44rNAWJVkjxvbJPBBPA5J6e6QeT9ni+z+X5GweX5eNu3HGMcYxQBJRRRQAUUUUAFFFFABRUc88Nrby3FxLHDBEheSSRgqooGSSTwABzmpKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKqaodukXp88wYgc+cM5j+U/Nxzx14q3VPViBo18WMYH2eTJkBKj5T1A5x9KAPO/gD/yS+D/AK+5v5ivUK8v+AP/ACS+D/r7m/mK9QoAKKKKAOC+NAB+EevAqW+WE4Az/wAto+ev+fet3wJ/yTzw1/2CrX/0UtYHxpnkg+FOs+XcCAuI0LZYFgZFyo2/3hxzxgnNdD4IUJ4A8OKrq4Gl2wDrnDful5GQD+YoA3qKKKACiiigAooooAKKKKAPM/EGkXfgLxLP400C1luNMustrunQkszc5+0RqSBuHOfYn1JHXXPi/SU8G3Xii0uY7zT4Ld51aJvv7Qfl56Ek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uPf4KeBfKkjt9LuLZZFKv5N9N8w9wXINcz4q+EMdvbaFpXhNr2BhfPI00j5jtVMZ3SFgA4cEJtAbBPWgD2eiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDwX9o+5azu/CNyiRO0T3ThZo1kRseTwysCCPYivbtJl8/RrGby44/Mt422RjCrlQcAdhXh37SqeZN4TTj5muhy4Uf8se54H1Ne6acQdMtCpJHkpgllY/dHdeD9Rx6UAWaKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPNraKSP9o28aQDbJ4aDR4JPy+eg/DkGvSa83t7gzftGXaEqfJ8NBBgEY/fq3Pr96vSKACiiigAry39n993wwjG1htvJhkjr0OR+f6V6lXkf7OrMfhzdAzeYBqcoC5P7seXH8vP4njj5vXNAHrlFFFABRRWX4h8Q6Z4X0ebVdXuVt7WPjJ5Z2PRVHcn0/oKANSiqGi6vb69o1rqtokyW10nmRCaMoxU9Dg9iOR6gg06LV9Pm1efSYruN7+3iWWaBTlkVs4J9M46denqKALtFFFABRRRQBT1ZmXRr5lm8lhbyES5I2Hafm45468c14RpngWHWvgLPrKaLYS+IZYJJorhQzySIr/MTycyFQ+MdyOh6e86m0CaVeNdIz24gcyovVk2nIHI7e9eUfDvWRovwq17xKotbLSVaeXTLMNjyVTKqrFiSzu+ByTkkAdgADkPiF4dtvD+l/DSP7FaWWosIo71FiHmySKIskuBztZmz83JYYzjj6Qr51bxH4jtNd8Ha145TTNT03XObe0Nqkn2ZCIisq5HyuTIucHoPXGPoqgAoorn/HGtnw74I1fVVjkkeC3O0RuEbc3yghj0wSD68cZOBQBxnxQ0jUdU+IHw5exs5p47bUXmndFysSK8DEseg4VuvXHFep1434N1NPhn8HYvEOqXVzqwvZElVIZSyoHACqN+NuAPm9/XFbFp8Q7rXviX4csNEuLSXQL/TGvJx8pmVwJBtbBO0ghBj/AHuTjgA9MooooAKKKKACiiigAooooAKKKKACuH+JfjTVfBWm2F1pekf2i1zceQykMdpI+UfKOpPAHeu4rg/ij4r1jw1pemW2gLbHU9WvBZQtOC3l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7l39OvHOPXv65HoSqqKFUAKBgADgCgBaKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA8J+FYVfjn432pvLSXhZ9v+qIuvu5x/EOeD/CeOOPdq8V+ElzMvxX+I1qttugkv5JHn3gbGWeUKu3qdwdjnts969qoAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPIdALD9pfxWAt2VNlDkxn90P3UP+s/p+P4evV4vpBth+1Jr/mtOJzZxiEJjYf3ERbf36DjHevaKACiiigDP17/AJF7U/mVf9El5YuAPkPUx/OP+A/N6c15/wDAH/kl8H/X3N/MV3ficZ8J6yPKeX/QZ/3aQiVn+Q8BDw5P909elcV8CP8AkldiRb+Tmeb5uf3vzkbufy44+WgD0qiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiivOviP8W9O8AXFtYpZHUtRlG97dZvLEUfYs2G5J6DHTJJHGQD0WivmrUP2kvEMl0W03RNMt7fAxHcmSZ89/mVkH6VFbftIeKFuY2utJ0eW3DAyJEkqMy9wGLsAffB+lAH01RXiek/tI6HcZXVtEvrJi4Cm3kW4XHcsTsIx6AGvW9G17SvENhHfaRfw3du6hg0bcgHPUHlTweCAeDQBo0UUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFeRfCO3ktfH/xGhmt7eB1vYMx2+fLAJmIxn2Of8K9dryD4P2J03x38RLQxxxlLuD5InZ1GTMeC3J696APX6KKKACoba7tr2Iy2txFPGHZC8ThgGU4YZHcEEEdiKmrzP4KtC+jeJ2t7eK2hPiO6McELKUjXbHhVK/KQBwMcelAHplFFFABXlfwU+aHxZK0MUUj63NuCyEv9GUkkYycE8nnOcV6pXk/wMQLY+JzugJbWJSQpPmDthu2OOPx9qAPWKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArzX45v5fw73+Wsu2/tz5bJvDfN0K98+nevSq8v8Aj9/yS+f/AK+4f5mgD1CiiigAooooAKKKKACiiigAooooA8h+Eklz/wAJ/wDEWLzoDaf2vK3lZ/eB/Nk+bH90jjnuBjvXr1eN/CSJP+Fl/EWXfbeZ/acq7MnzcedJz6bf1zj8fZKACiqWk6tZa5pcGpadMZrScExuUZCcEg8MARyD1FXaACiiigAooooAKKKKACiiigAooooAKKKKACiiigAopCyggEgFjgZPU0tABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFc/47/wCSeeJf+wVdf+imroK5/wAd/wDJPPEv/YKuv/RTUAZnwmtktPhZ4fjSOSMG2MmJEKEl2ZicEngliQe4IOBnA7OuQ+FtvFa/C/w9HDbvbobRZCjuHJZiWZsgnhiSwHUA4IBGB19ABRRRQB578Yt//CJWG3bj+17Tdn039vxxXoVeefGNWPhLTyuMDV7Qtknpv/xx1r0OgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKZNEs8MkTlwrqVJRyjAEY4YEEH3ByKIolhjCKXIBJ+dyx5OepJNAD6KKKACiiigAooooAKKKKACiiigAooooAKKKKAPBvgneNqHxI8W3c97HLcSqW5tkWSceaR5hZSQmOAUBw24Hnbk+814v8IVeD4mfEOGEKlp9vcFBAwAZZ5QoVwdoABPykZORjhTn2igAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA8a0Qf8AGUfiQ7Jj/oEY3ITtH7qD72B044zjnHfFey147ol/PB+034mso1iMVzZxGUucMNsMRG3nnk8jB4+hr2KgAooooAwvGsMlx4D8RQwxvJLJplyiIikszGJgAAOpNcn8CYvL+FVgfLCl5pmLCQNv+c84H3emMH0z3rqvHLFPh94kZSQw0q6IIPIPlNXMfA2LyvhXp3Em15JXBeEJnLnOME7hnOGPPtgCgD0aiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACvkT4qQDTvip4hi1J7maO4QvESQ7DcgeMBnX5VVtoO3ooKA9a+u65Hx58PNI8fafHDfboLqE5hu4gN6DPK+4Poe/NAGR4b1zQtP8AD/hax8U3mkvrWpacLeB0hyk0JxiPfjaA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fDfw2Y5mlH9nQDcxzg7BlfwOR+FdNQAUUUUAeX/H7/kl8/8A19w/zNeoV5h8fMD4Yy7gSv2uHIBwcZNen0AFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVg+N1D+APEas6oDpdyC7Zwv7puTgE/kK3q5/x3/wAk88S/9gq6/wDRTUAeefs5RQjwNqEqRgStfssjgsd2EUgYIwMbu3rzXsdeNfs5WN3b+DdQuZoJEt7q73QOx+WQKu1io9iMZ9sdq9loAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPKfhLHN/wl3xDlM+YDrkyiHYOG8x8tu69MDHtXq1eU/DOc2nxI+IOkNFImdQ+2DzlKud7MTgDI28gg5BIIOOTj1agAooooA5/x3/yTzxL/ANgq6/8ARTVznwRS9T4WaZ9taQ7mkaHzDnEW47cc9OuM4/LFdH47/wCSeeJf+wVdf+imrP8AhX/yS7w9/wBeg/maAOwooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK8v+B3/IveIv+xguf/QI69Qry34FMX8M+IGZGQnX7klGxlfkj4OCR+RoA9SooooAK8w+B12l94a16eG4muLdteuWgkmZixQrGwJ3EnJzk5JOScknmvT68t+ATNN8PJrqW4jmnudRmmmYNlt5Cg7/APaOAfoR60AepUUUUAFef/B//kULz/sK3f8A6Mr0CvP/AIP/APIoXn/YVu//AEZQB6BRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFeV/tBGUfDIiNVZTexCQk42rhuR+OB+NeqV5J+0NcJH4AsrdnO641KNdgkCblCOTnIPHT6Eg+xAPW6KKKACiiigAooooAKKKKACiiigDz/4P/8AIoXn/YVu/wD0ZXoFcB8IQB4SvQGDAatd8jof3hrv6ACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDy39oDTHv/hhJcrIqiwvIblgR98HMWB+MoP4V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aAPWrS7gv7KC8tZVlt541likXo6MMgj6g1NWD4I2f8IB4c8vds/su227uuPKXGa3qACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK5L4nh2+GPiIIwU/YnOSwXjuMn2zx36V1tcv8R7c3fw48QQqYwTZSEGRgq8DPJJAHTuaAIfhcFX4YeHQrbh9jU5xjnnI/A8V0ep6nY6Np02oaldR2tpCAZJpThVyQB+ZIH41y/wwmjg+FOgSzSJHGtoCzuwAAyepNeCfF/4nHxnqEenaVLcRaNbD5432jzpgx+f5SQVxjHPqaAPqqCeG6t4ri3ljmglQPHJGwZXUjIII4II5zUlQWVnb6dYW9jaRiK2tolhijBJCoowBz6ACp6APIf2jCB8PLPLyDOpxgKuME+XIefyP4169XkX7Rf/ACTq05A/4mcfUDn93L6/0/lmvXaACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK5/x3/wAk88S/9gq6/wDRTV0Fc/47/wCSeeJf+wVdf+imoA4T9ncxN8O7toy5kbU5TKCoUBvLj4XHbG305yMcV63Xj/7OQx4A1Ha2U/tWTblcH/VRdefpXsFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB5P8MHnn+I/xCku7WRp01EoLuR/m8sMwSPB5xtVSD0xj2r1ivH/AIbxC2+M3xBi3qS0qyY8pATuct1Xpjd9TwTyOPYKACqdnqtjqFzd29pcxzTWcnlXCL1jfGcH8KuV8val491HwJ8b9eubdy9hNe7by2Y/LImByPRhzg/0JFAH0D47/wCSeeJf+wVdf+imrP8AhX/yS7w9/wBeg/maXxHremeIPhZ4jv8ASb2G8tW0q6AkibIB8luD3B5HB5pPhX/yS7w9/wBeg/maAOwooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK8r+BsjnRvEsZhYRrrs7LLkYYlUyuOvGAfT5hjvXqleWfAmMQ+F9fiDOwTXrhdztuY4SLknuaAPU6KKKACvJ/wBnkKPhtLgYJ1CXPA5O1PQfTrk++MAesVQ0fRNM0CwFjpNlDZ2oYv5cS4BY9SfU/wCAoAv0UUUARzyNFbyyIqsyIWCs20EgdCe31rz34KTG58ByzsqqZdRuXKq4cDL5wGHB+o613eqTfZ9IvZsE+XA74GMnCk9wR+YP0NeffAaORfhdaySJtEtzO6HGARuxkfiCPwoA9MooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACvJP2grBtQ8H6NbwpH9pm1mKCJnIUAvHIMFj0GcZ7ce1et15n8Yop54vCEKRRSxSeIbZDHKxVWchguWXkD73QH/EA9MooooAKKKKACiiigAooooAKKKKAPN/g7DL/ZOvXTx2apcazctGbeLYxAbndwOM52jsPyHpFef/AAf/AORQvP8AsK3f/oyvQKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDy747eH7nWPA8V3Zkedp8/mMMMxaNkZCqqoJLFmQeg5JIHNUvhP8UL7xBcW/hrV9FktrqCAot2gIRzGB8rK33W24PBOfQCu08feOrHwDoKaleQSXLyyiGGCNgpdsEkknoABycHqPWvIrjXPjB4us7nVtGtprDTJ0W+t4oiN2wApsRiMtnYX298gjqMgHY/HDxX4k8M6JZx6IsccF8XhnuQC0ikjAVRjC5yeeuRxjvf8Ag34CuvBHhiZtSwNT1B1lmjViREoHyoecbhliSPXHOAa85tfFnxV8FWthq2t297qGl3CrNdRXMGHgAZwULYyhKqGyRgbh3zXufhXxRp3jHw/BrOmGQW8pZSkuBJGynBVgCcHv16EHvQBtUUV478Q/BXjzVPH8WueG9RZLZLZI0CXIgaMjdleh3feJyR/ER2oA0/jB4O1zXbfTNd8OTXD6vpEwkgtlaNVCnlnXK5Z8rHwWxhTgZPPn93F8QfibFpPhzW9Mv4Lc3KXN1dXWltaLBsDqQr5IfKv3UfNgYxk1R16X4u+HNV0fTdQ8QyrcavP5FqEuFYF9yLycccutafiTSPin4R0m+17UfFDTW0FvCJXimO5i0iqY14+XlydwwTtFAH0HZWcGn2FvZWyBLe3iWKJB0VVGAPyFT1jeETnwXoR8l4M6db/unJLJ+7X5STySOnNbNABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFZniOGOfwzqsU0aSRtaShkdQQRsPUGtOs/Xv+Re1P8A69Jf/QDQB8l6142mvvA+g+E7K9m/s6GyzdQ+Tud7rzXIUsf4ANhG08BuhIwOHlWRJWSYOsiHYyuCCpHGDnpjGK9U8ReC3vfhf4d8ZaVZHbBaLBqVsEdA6o3Em0Y+U4JYjGc7uuTXml+sClRGIFkUsGWAsykZ3A7mJz94rjjhB1JJoA+w/wDhangb/oZrH/vo/wCFb2ieINJ8R2T3mj30V5bpIYmkiOQHABI/Jh+dYuk+FPC+raNY6ld+H/D95c3dvHPLcx6dHtmd1DFxuXdgkk888810dhp1lpdotpp9nb2dspJWG3iWNAScnCgAdaAPKf2jVJ+HdiQCQuqxk4HQeVLXr9eRftF7P+FdWm4An+049uSeD5cv9M9a9doAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArC8bbD4D8ReYQE/sy53EgkY8ps9Oa3a5/x3/yTzxL/wBgq6/9FNQBwf7Ou3/hXVyFXDf2jIWO1hn5I+54PTt7d+T65XlX7PgYfDP5pnkBvpdqsGAjGF+UZ4Izk/Lxlj3zXqtABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB5J4EluZ/jl48lvIpIpdsaIrx7d0akKrdehVVIPfOeOlesTTR28Mk00iRxRqXd3YBVUDJJJ6AV478N2mb43ePjPE8T7yArszEqJMKcsScFcEDoAcDAwK9gubaC8tZrW5iSW3mRo5Y3GVdSMEEdwQaAKa6/ozqGXV7AqRkEXKYI/OvkT4jJb3PxC8Q3cUiG1a9cJNCQ6lwucYHqcDPbnrX1Ja/DfwVab/L8L6U27GfNtlk6em4HH4V8u/EDTrf8A4Wh4ghTybS0hui8mzYuxCVBKISu9vmztHJ5PQEgAqeGvEN74O1C6DtLc6VdRXFrPBHIfIuyY2QHkYYBmU5xnH1r6m+Fqsvwv8PBgQfsinkdiTivnPwn4NfVdBm8R6zpzW3hnTrC5YyiVh9ruACqlQXyG3MgyBsJi2nnNfRnwtZm+F/h4sST9kUcnsCcUAdfRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAV5f8Dv8AkXvEX/YwXP8A6BHXqFeX/A7/AJF7xF/2MFz/AOgR0AeoUUUUAFcV8Kde1jxN4As9W1to5LmaWUJKmB5iByASoACkEFcDPCg5yTXa15/8Ev8AkkOhf9vH/pRJQB6BRRRQBU1QZ0i9HP8AqH6SCM/dP8R4X6npXn/wHUD4VWRAQbp5idobJ+c9c8Z+nGMd813muMV0DUmGMi1lPIyPuntXFfA4Rf8ACptJMQwxefzBkn5vNYd+nAHTigD0SiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK87+Kh/0vwMpl2qfE9odgTJYjdg5zxjp/wL2r0SvPfiomZPBD7m48U2Q254Od3P6fqaAPQqKKKACiiigAooooAKKKKACmTeYYZPJKCXadhcEruxxnHan0UAebfBO1Fv4NvpHCG6m1W5a4kUYDuGC59hx0r0mvMfhC3k3njTTkX91aa7OiNuOW+ZuSM7R0H3QK9OoAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPCvjxPDceKPCmk6pOLfR5HMs0uzODuCnnI4wfUYzmvcLaKGC1hhtwBBGirGAcjaBgc9+K4P4u+BLzx14Ygt9NkjW/tJxNGspwsikEMuex5BB9sd815zb/ET4m+AdMtU8SeHjd2YcqJZUVCsaALsDRDC+u5gc54oA961i1W90S/tHQyJPbSRsgQOWDKRjaSAevQmvGP2aZZzo2vxM0/2dbiJkDL+7DlW3bT3bAXI7AL61z0njL4ofEHSYLPTlSzcyrJHJpqTwvOvIJ84ExhFLDcCykHAwSDXuvgvwnZ+C/C9ro9mFJQb55QMGaUgbnP1xx6AAdqAOgrG1zxZoPhu2nn1bVbW28hVZ42kBk+bO0BB8xJ2tjA52n0NbNeM+JPhBN4i+J+q+JdU1f8As3R2ELxyW7hZmKxKh+Y8JgrnPOQfyAOC1zxm/jbxDpvivWInTwxpmrx2sVou0Ogdd5ZmBBJPlAkdBjAPc+kftEOw+HdpGgcmbU4kwpIz+7kPQdenT6elQa3q/wAIJfDtr4Llv7ZdO3t5ctmSwgkUY8wyAH5jk/MdwPOeKb4M+FXw1l1WDVNG1qXWntCJfs73cUiqf4S6KgYcjIB4OO9AHqPhyCe18L6Tb3KhbiKyhSUBNoDBADx257Vp0UUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVjeLWdPCGsMl3HaMLSQ+fKoZUG05yCR9OtbNc749iE/gLXIjEswazk/dtG8m7j+6nzfl060AUPhaqv8LPD6sAVNoAQRwRk14r8YPhbeeHPP1nRmkk8PvL5slqHJ+ySNgFsHqpwOeo4B4ANe1/Cv8A5Jd4e/69B/M11ssMc8TRTRpJGwwyOoII9waAMfwa5l8D+H5GiSFm023Yxou1UJjXgDsB6Vt0UUAeR/tFFR8ObXdGrE6nFtJbG0+XJyPXjIx757V65XkX7RcbP8OrRgrkJqcbEquQB5co59Bz19ceteu0AFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVg+Nyg8AeIzIrMg0u53BWwSPKbODg4/I1vVz3j1lX4d+JSxAH9lXI5PcxNigDj/gKinwDdXircIL3U57jbMpwOFXCuTmQfLy3Hzbh2yfUa4H4KoY/hFoQJUkic/KwPWeQ9vr07dK76gAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA8Q+EqsfjF8Qmwdou5gTjjP2h/8AA17fXkPwltD/AMJ/8Q73ysD+15ovM808/vXONmO3rnvjFevUAFfOOn/D6H4kfF3xJqM0+NDs78rNtJV5nHGwdwODk/l1yPo6o44IYXkeKKNGkO52VQCx9T60AcV8S7KLT/g/rVlp6C2ggshHGkSMQqAqNuF5xjjPQdTxmrPwr/5Jd4e/69B/M1B8X2kX4Ua+YzGG8lAfMXcMGRQex5xnB7HB461Z+F0bx/DDw6siMpNmrYYY4OSD+IINAHXUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFeX/AAO/5F7xF/2MFz/6BHXqFeX/AAO/5F7xF/2MFz/6BHQB6hRRRQAVT0vSrHRNNi07TbaO2s4c+XDH91ckscfiSfxq5XCfBu5nvPhTotxdTyTzubgvJK5ZmPnydSeTQB3dFFFAGfr3/Ivan/16S/8AoBrjfgggX4RaKQWy5nJyxP8Ay3kHGenToPr3rste/wCRe1P/AK9Jf/QDXI/BZVX4SaGVaNtwmJKLjJ85+vA5HQn279aAO+ooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACvPPip5nneCMFPK/4Smy3Ag7t2Wxj2+9n8K9Drzz4qIpm8EOYwXHimyAkwMqCWyPXnA/L6UAeh0UUUAFFFFABRRRQAUUUUAFFFFAHk/wXTZqPjhAqrt1uQbVQoBy3AVuR9DyK9YryL4HMGm8ZMNmDq7n5BherdB6V67QAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAeU/F6L4gi702fwbJefZiPKnS0Ybt7N8pIPb1boO+KydHvPi5pmva5LdRf2tHbhEiimiaOGdmdVLQkAYwMk9sA+1eheMPiH4f8AA/2ddYmlEtwC0cUMe9iB3/pXi2qfEDxl8TtRXRNJ+x6Po1zFvnZJ0cxwFgpaaTPyEMDhQEYhgMNkEgGVqPxC+J+jaLNbXGpxPbrIYxfxtFI5+b+FweRwcHHSvcPhNP4muvAkFz4peR7yWV3hMq7ZPJONu8YHOdxH+yVrF8C/Dzwf4IVmvb/Tb/VgRumuGj/cMAAyoDyOc89e3rXp0E8NzCs1vLHLE33XjYMp7cEUASV8/eND4j8b/GbUPAkOtyWmjmKLzIgPlEYiSUnb/ExZvX0zwK+ga+e9fnm+HPx7uPFGo6Xd/wBg3pVBeKpKAyRgMcgHkMrnZw2BkcYyAeiXXwZ8G3OgxaQllLbwpPHO8sMmJZWRNnzMQeCDyFA554NebeM/CuqfCOAan4cvL99Fkuo3dUnMbQybs7WKj542VdvzcZI6k17na+KNCvdLOp22r2ctkoy0yzDavXr6dDx7V4t8R/GEHxI8RaN4G8O3aSWVxcrJc3UbkLJgZCjAPCjcTkHkKe1AHuml6hDq2kWWpW+fIu4Enjz12uoYfoat1FbW8dpaw20WRHCixrk5OAMCpaACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKz9e/5F7U/+vSX/wBANaFZ+vf8i9qf/XpL/wCgGgDn/hX/AMku8Pf9eg/ma7CuP+Ff/JLvD3/XoP5muwoAKKKKAPIv2i3Vfh1aA78tqcYG1sDPlynnjkcdOOcHtivXa8h/aMQt8O7Ij+HVIyeD/wA8pR/WvXqACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK5z4gRRzfDrxKsihlGmXDAH1WNiD+BANdHXJ/E10T4aa/5l81irWjIZhGX+8Qu0gAnDZ2kgcBie1AGX8Ev+SQ6F/28f8ApRJXoFcH8GvLHwq0ZIpIJETzl3QFtp/evk4YAg5/+txiu8oAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPKvhKZ/+Es+IQ2R/Zv7dmIbcd+/e/GMYxjHOfwr1WvL/hN/yMPxC/7GCf8A9DevUKACiiigDhPjLbyXPwl16OPG4JFIcnssyMf0BrW+HtvHa/D3QYogQgso2AMqy4yM/eUAHr2rG+NUjx/CLXWR2UkQLlTjgzxgj8QSK6vw5DHB4Z0qKGNI41tIgqIoAA2DoBQBp0UUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUxZo2leJZEMiAF0DDKg9MjtnBp9ABRRRQAUUUUAFFFFABXl/wO/wCRe8Rf9jBc/wDoEdeoV5f8Dv8AkXvEX/YwXP8A6BHQB6hRUVzOtrazXDK7rEjOVQZYgDOAPWsXwh4w0nxtoo1TSHk8oOY5IplCyRsOcMASOhB4JHNAG/XnvwQkR/hFoqq6sUM6sAc7T58hwfTgg/jXoVef/BL/AJJDoX/bx/6USUAegUUUUAZ+vf8AIvan/wBekv8A6Aa4/wCCl0138KNHLeVmLzYsRnsJGxuGODj654Peu01aXyNGvpcbtlvI2M4zhSa89+Abs/wttlY5CXMyr7Ddn+ZNAHp1FFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAV598VGUN4JXI3HxVYkDPOPn/xFeg15t8V0gOp+A3aRBcDxLahIyfmZS3zED0BC5+o9aAPSaKKKACiiigAooooAKKKKACiiigDx/wCBH/M3/wDYVb+tewV5N8DLdo7PxROykCXV5Ap3DBA9uo5J6/h3r1mgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKpata3l7pc9vp+ovp124Hl3SRLKYyCCflYEHIBHPrQBdooooAKKKKAPLvin4c8Ia1rvh+bxH4jTSZo3KrGzhfPj+8Ru/5Z8j7547dSK4HxD4E8EP4h1vUNa+IKBIprcxrJdfarlo9i71cH53J4Clc7QOc9B694r+HWleL9dstU1KR2+xwSRRwFAyFm6OQeuDzg5BxzkV5B4++Cul+E/BN5rKa3PK9mFEcbwIu8tIBgkcn7x65x9KAIfFPgX4YRSPeR+OZLDz0iZIpIXupNzqJfMZR85Vo3XB4APcn5R7V8ONF0bQvBVnBoOpSajYS5mW5aTcGY4DbR/ANwPy9jnPOa8oi+DXhzWPAVj4ohvL2KRtMF5cw2yo3my7NzhAcBfmBAA4ruvgbrd1rfw2gF3JbubGY2UfkoVKxoibQ+eC2G6jgjGec0Aek15b4z+IngG7m1bwv4hs7m8ktHVFhFrvM0rKR+5OchxuI3Hb14JzXqVcRffCrw1qWva1ql3FM51eBIbmFZCq/K6uWBHIJMcf/fJ9TQB5He6B8EpRBdw+Kbq1smURG3hWVneRWyZGDIWGVbaPlA9OQa63w94v+DnhSfzPDY339wY4B5dtO0r5IXhpQAo5ycEZx3OBWVF4f0ib9o46W3hvTLbTksWJtWt0eKb5TiQJnapJI7fw9MnNT/GTRbTw1ZeHYdA0ixtrO51hbi4jgVY3kmUYjA5Hy4aT2GR0zyAe6UUiklQSpUkdD1FLQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABWfr3/Ivan/16S/+gGtCs/Xv+Re1P/r0l/8AQDQBz/wr/wCSXeHv+vQfzNdhXH/Cv/kl3h7/AK9B/M12FABRRRQB5H+0Uit8ObUk8pqcRHzAc+XIO/XqeBz36A165Xk/7Qe6PwDYXK+UTbarDLslTcr4SQYIIIPXkHjGa9YoAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArifi6FPwp1/fcLAPIX52LDJ8xcL8vPzH5fTnnjNdtXA/GoIfhFru9mUYg5Vc8+fHjuO+P/r9KAJfg9e3WofCrRbq9uZrm4cTBpZpC7sBNIBknngAD6Cu5rgfgqVb4RaEVXaMTjGc8+fJk/iea76gAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA8v+E3/ACMPxC/7GCf/ANDevUK8v+E3/Iw/EL/sYJ//AEN69QoAKKKKAOH+MOz/AIVRr/mR7x5ScYzz5iYPUdDg/h36V1Gg/wDIvaZ/16Rf+gCuc+LEV1P8LdfjtIxJMYAdpjL/ACh1LcAHkLkg9uvGM1f+H6FPh9oCm5W5Iso8zLKZA3HZiB9MY4xjnFAHSUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB4R4a07xLe+LvH+u+G9USHUrbVpIPsVwgkhu0R2wjMx3LwMDBGOnA6d34e+JcNzcJpninTZ/DerFAQl8QsE5Jx+6kOAT/s9ewzg1m/ClNniP4gjcrf8AE/mOVOerMcV23ibwxpPi7R20vWLfzrYuJFwdrI46Mp7Hkj6EjvQBsUV5Qvhjxt8N9KLeFdUfxFp0RP8AxKL6Ib41xy0cgYHI24CAY+YkAk11Xgr4haL4z0+Fre5jh1PYPtFg7YkifncAD94cHkdsZx0oA62iiigAooooAK8v+B3/ACL3iL/sYLn/ANAjr1CvL/gd/wAi94i/7GC5/wDQI6AOv8Xa7JoumOI9L1a7E0MuZ9OjRzbYX7zbnXHXIx/dPSvA/DdhqB8GWWueFdF11fFaNIF1W1SNre5QyHcswaRtxwSfuj5gB2zX0zPBHc28tvMu6KVCjrnGQRgjivO/gtcpL4T1O1hs7a1gsdXntokgUjKgIQWJJLN82Mk9APSgDU0/xH4huvhzqura1oraLqtrbTFYxMvzlIt3mKXBCAtnAbcBjnIqn8Ev+SQ6F/28f+lEldV4pJHhHWirMpFhPhlhEpH7tuQh4f8A3T16VynwRUD4RaIeeTcE5P8A03koA9BooooAz9e/5F7U/wDr0l/9ANcL8CE2fCmwbEY3zzt8ucn5yPmz347cYx3zXea0jS6FqEaDLNbSKB6kqa4T4EtGfhXYxpGFeOedJWBBDt5hOePYgfhQB6TRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFeSfF2Vv+E4+G0IMOw6yrMCE8wHzIQME/MF+9nHBIGeQtet15D8ZVktfFHw/wBZkgZrCy1ULNIOcMzxsoCg7icROeBjgeuKAPXqKKKACiiigAooooAKKKKACiiigDzP4K2gh8P61c4YNPrFxnKoMhSBkEcnv9704GOT6ZXnXwbt44vC+pTLv3zatdF8uSMh8DAJwOPTrXotABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVxPxV8HT+N/BT6bZ7ft0VxHPbb32puB2tuODxsd/xxXK/FDwb451PxTDrfhvWfs1rHaeU6rdtamIA7mDMpG5T1znjHsK5S++H3xHuvE1lqEEsgvWtvNNwmrTm3hQEg24dnaUlsBsh8fvCBgDIAPYpvDVzb/CybwvbzC4ul0Z7CORztDv5JQH2GfyFZ/wj0LWvDnw+s9N1xY45kd5IYU+9FG53bX/2txc+2QO1ecv8Mfi3GFaHxxdO4KHD6nOF+5ub16MNo45yDxzj2DwS2rt4L0ldet5LfVI4BFcLLL5jsyEqHZuclgAx/wB6gDfoorw/Wfhj40vPGvijVtE1aKxF1Iq2s880iPtcRvI0bJloyCix5xyu4cCgDU+JPgnXj4rj8ZeGRczXiWbQyQ29x5UiyAfu3Xs65I3IeCFxznjJvfC/j3xp4n8PL4sSCy02yihmeS3uMB5iCeUPBkyCCFGBngkYzy0Om/EbUPiJdeEbzxtfQ6ilo0qTQ3M6wsQoZQMbODnBYKcc8HFN8WaJ4q8Laj4di8Va+2qC81RJYLEXE88AEZGWLyMGUjzQoAHIyScjkA+naKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKz9e/wCRe1P/AK9Jf/QDWhWF40u3sfBOt3UZAaKylbJiMgA2nOVBBxjvnjr2oAzPhX/yS7w9/wBeg/ma7CuP+Ff/ACS7w9/16D+ZrsKACiiigDyv9oKFZfhkXYsDFexOuDjJwy8+vDGvVK8v+P3/ACS+f/r7h/ma9QoAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArz/42/wDJIdd/7d//AEojr0CvP/jb/wAkh13/ALd//SiOgA+CX/JIdC/7eP8A0okr0CuB+CqGP4RaECVJInPysD1nkPb69O3Su+oAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPL/AITf8jD8Qv8AsYJ//Q3r1CvL/hN/yMPxC/7GCf8A9DevUKACiiigDivi5LND8KvEDQK7OYFUhDg7S6hj9ACSfatLwBbz2nw98PwXCusyWEIZXbJHyjj/AOt26VmfF2MSfCnX1KKwECthgT0kU549MZrptB/5F7TP+vSL/wBAFAGhRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAHmnwtlM3ib4gMxQka7IvyDAwCw/Pjn3r0uvPPhtpV7pviDxy91aXUEVzrUk0DzwlBKpLHcvZl54I/TpXodABXO+JvA/h/xZGx1PT4nuhGyRXSjbLFnoQw54OCM10VFAHmH9peMvhwoXV0uvFmhZ+W8tkH2y1UcASL/wAtM5HzZz94k9BXd+H/ABFpXinSI9U0e7W5tHJXcAVKsDgqynBB+vYg9CDWpXAa38MbYXLav4Puj4e10y+a88JcxXA5PlyR7tu0tgng9OhoA7+ivNrL4jal4euk0vx9pT2Eoby11a2UvZzcqAxb+DO4Zz0746V6LBPDdQJPbyxzQyDckkbBlYeoI60ASV5R8ARIvg/WllR0kGtzhleTewPlxZBb+I+/evV68k/Z62/8IPquxHRP7Zm2q4AYDy4uDgAZ+goA9bryv4Gzxto3iW3DfvU12d2XHQMqAH/x0/lW78RfFOqaLBp+i+HbV5/EOstJFZH5dsWwAu53ccAg4PHUnpg3tHi0jwjp1xZg2EWrSwtql7aW0uDLJtAkkSNjlUJTAwABj1zQBpeKtp8H63vKBPsE+4uHK48tuuz5sf7vPpzXLfBL/kkOhf8Abx/6USVPoPjMeOvh1qusW+nXliBFPCiffdysedyYxu5JAwRyp5FQfBL/AJJDoX/bx/6USUAegUUUUARzwR3NvLbzLuilQo65xkEYI4rgvg0kcHgI2kUaJHa391CpA5YCUkFj3POM+gFdjf65pml3+n2N9exQXOoSNFaRucGVgMkD9OvcgdSAeP8Ag/8A8ihef9hW7/8ARlAHoFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAV5T8dbM3Gi+G3QWZkGtwxqL3AhO5HP7wn+D5Rn1FerV5/wDFPwrq/iy10K30+3tLy0t9RSe8tLlzGsigEAlxyAAWBABJ3A44wQD0CiiigAooooAKKKKACiiigAooooA5zwb4Vbwlp15Zf2g95HPey3Ue6IJ5Qc528E5+p/IV0dY/iTV7/RdOjutP0S51eQyhHgtnVXVSCd/PXkAY989q5ZviD4jCnb8ONbLY4BkjAzQB6DRXlXwUXxRpGhN4c17Qbq0t7UvLbXUm0LtZgfLwOSdzO2fTj0r1WgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDwbxAtz4s+OWreHtP126tIpdJktZfKO6PcY8MrIwwR83OOfQgik1j4bfEqHwhpXh2y1xbuC3kY/uZvJWJAOFZj878k7R0AHTph3ii+HgD47QeJNavI57K/tn27IyrQxgBQuFB3HIwM4z3Ir0m0+KHg69uXgh1y1ylp9sZ2cBQnORn++MZK9cUAeG6t4B+IHhjwTq2pat4ja2s4DE5t1u5JDKSwUYI+7yw+vfGM19CeEb2SfwHoV9e3BeSTTLeaeaVuSTEpZmJ/Ek15l8UPiTo2tfDFINFnW4uNbfyordo8yhFkwxK5ypyoxnr2r1DRdDFr4J0/w/qSRTiLTo7K5VSSkmIwjAZwcHn0oA2VZXUMpBUjIIPBFeZ+NfjRo/haa1g06CPWZJ2wZYLyMQR4yGDOu4hx8pIK9GzntXpFtbQWdrDa20SRW8KLHFGgwqKBgADsABXhPgiH4U6b4h13bcx3jB7h/NuIyLSG2LcRDcxWTsMkEtjj3AOZstc1a0+J9r8QvEUi2ekz6hJab45mdWjEZx5aj5pIgCp3AbSfTNdJ8StW0zxx8T/Bmi6RqtncxwTFnnt281VZmQkbgdp4jHAPGea6vX/FHws8b+HVttW1SxWNg6QOwC3FthgCUOCUzsU+jDGQRxTPh3bfDTwpeC38O68L3UNTYxq80+93C4+QBVCjB55GTk8kdAD1WiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACuS+J6hvhj4iB2f8eTn5zgf/AK/T3rra5P4nHHwy8RfPGv8AoT8uu4fTGDyegPY4OR1oAj+Ff/JLvD3/AF6D+ZrsK4/4V/8AJLvD3/XoP5muwoAKKKKAPL/j9/yS+f8A6+4f5mvUK8s/aBZ1+GLBYywa9hDEEDYPmOffkAcetep0AFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFADXjSVCkiK6nqrDINOoooAKKKKACiiigAooooAKKKKACiiigAooooAK8/8Ajb/ySHXf+3f/ANKI69Arz/42/wDJIdd/7d//AEojoAf8F1CfCPQQpBG2Y8NnkzSGu9rgfgqEHwi0LYzMMT8suOfPkz3PfP8A9bpXfUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAHl/wm/5GH4hf9jBP/wChvXqFeV/B4xnWfHphfzIv7dl2Pv37hubB3HOfr3r1SgAooooA4b4xXLWnwn1+RVDFoo48H0eVFJ/8erqdB/5F7TP+vSL/ANAFYnxMkii+GviBpls2Q2jLi7YrHk4A6AndkjaB1baMjqJPh0J1+HWgLdJcJMLKMOtwSXBA75AOPQdhjr1oA6eiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDi/in4qv/AAd4HuNV0xYjdiWOJDKu5V3Hk478Vgaj461XwJ4ptbPxXqVve2V/YNJbx2tt+/S5Xb8hCk/KxLKrY5Ppgmr3xwuoLX4XagJ7RLjznjiTcceWxbhxweRj26157daRYfD2zjj8dWGneJbPWp/Jj1kXLPcwQBFC7UZSQAOQUbuBn7tAHrXgzx9Y+LmubI201hrNkSL3T5gSYSG2/fwAwz+PtXL694m8YXXxafwloGpaZZQiyW5DXcO4k45C92POcegJ7V2nhfwTofhCKVdKtSJZj+8uJW3yuOwLnnA9Kx7LwvqKfGfUvE8sUa6a2lJawuXBZ5Cyk4A6ABSCTj7wxnnABxNj4p+It7Z+LLka5pKx+G5Zo58WJJl8oMTt5H909a9K+H+s3niDwJpOrX8olurqIvIwh8oZ3EYC+gxjPfGe9Z2vaJpvhPwX4z1HTbeUTX8FzeXGHdt0rI3IwcqMknjGOeeOIvg1G8Xwl0FXkSQlJWyjhhgzOQMjuAQCOxGO1AHd0UUUAFFFFAEN3aW9/aTWl3DHPbzIUkikXKup6givDfih4L1nwj4fuJPBVzqEeiXu6LUdMjlLpGHIwY0IJUMSQ2DnkDpnHvFFAHgfws+NKLHp3hXxDbskse21t74MqrgcKsoYjGAMbgSTxxnJPS/ADDeCdVlVw6S6zO6t5m8kbI+pODnjuAT1xzXoup+HtE1qSOTVdH0+/eMbUa6tklKj0BYHFeb/ALPUSw+A9RVPM2/2vNjeuDgRxD+ntzmgDpfHug6pc3eieJdDAm1TQZnkS0d0RbmKQKsqb24Q7QcN2574xxP/AAj/AIo+IHjCDxs2n3Hhv7BYtBZRvMrXEkymQq+1027CXwQ2MjkHBrQ+JFtP4h+KvgvwzOLq30uZJ5ppo5WVbkAbnh+UjoIgCc5Al7Y543UfDclj8Qb3wTDr+sLoUFjLqVvbpcFTC4jO1AxySo/D+pAO1+H+hax4T+DGtJrFkq3kiXd0LZlMzEeXgB0zgklT8oIyCOhJrY+CX/JIdC/7eP8A0okrN8Ga9ca58Br3UNbuWu5Vs71biWaJm3KN/UZXf8uB8pHpkEHGj8EST8ItE+UjBuME9/38lAHoNFFFAEUltBNLFLLBG8kRJjdkBKE9cHtXCfB//kULz/sK3f8A6MrvpC4jYxqrOAdoZsAntk4OPyNeffBlpG8FXLSoI5Dqd0WQNuCnfyM9/rQB6HRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVT1TVbDRbB77U7uK0tUKq0srbVBJAHP1Iq5XL+OovCkmlWUnjCSBLCG9jlhE5OxpgrbQw/iGN2VPBoAtT+NvDFsti02vaeiX4LWrmddsgBwSD068c9+Kxfh18QE8Y+G7q6v0gtdR06Ro7+KJsouOQ68k7SAe/VW64zWjZ/DrwZZWwgj8MaS6hmYNNaJI3zMWxuYE4GcDngYHanCHwdoPh/U761ttLs9LMRF7LYQqoZQDw3lDJIDHA6/Nx1oAil+JfgmKNZG8UaYQwUgLOGPOcZA5HQ5HbjOMiue0j4qW9x8R7jw7eX2iy6dcxJJpd5ZXBcuxbaIpOSPMOTx8uMdDuFdDpvgnwLNpdrJY+HNDuLRol8mb7JHLvTHB3kEtx3JJPeodDh+H99qt7p+iafobXumzpJOlvZRqYpVztYEKASpLDIztJI4NAHYUUUUAFFFFABVHWdQfSdHu7+Oyub54Iy621sm6SU9lUVerP1zWrDw7o1zq2pytFZ24BkdY2cjJAHCgnqRQBxt38XNO0+CzuNR8PeI9Pt55zDI97prx+VxlT3DbjwACTwePV9l8V7C48R6Zo17oGv6O+os0cE2q2gt0ZxjCjLHOcgcdyo78cR4pfxb8VPCVzqEOh/ZdDtyJ7GydBLc3sivtyeVKLtZvu88HBbPNuee4+LHjPwjqGn6ZdadFocovL831uUKHzEIjRs4fdsOOBgDPfAAO3+JfjOfwPoenajBB5wm1OG3mUDLeUdzvtH94hCo/3q2YfE1pqPhKfxBoySajEkEssUMaMJJXQH92FIyGJG3GOtcj8Vr2NdW8BabskaefxHbXClVyoWM4bJ7H94PwB9Kg1nQ9f8CeIpfEHg6yudXs9UuJJdV0l5wAJGwRJFxled2evUDGMbQDpfBHjJvFVrcwX2nyaXrNiypeWEx+ZNyhlcA87WB4z6fierrzfwPo2taj451bxxrukLo0t1aJZW1iHV32Agu0hCgk5RcE84OOgWvSKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPNfFnjLwBqup3XhnXbGfUprLc8ypYySeRtG5mDKNy4A5I4x1OM15jr3hz4U/aINZm8TXMdtqtzHNHaW6b5LWHYd6uMllBbGCRkAAAHk0nhPR9F8X/FHXdEuIAmnyT3Vyi42XcTBghUy8MVYM3yZccHgctV343eFdJ0Q+CbCztpI7cebavKi75WjDR7QSASxG58DB6mgB3hnUfg/4M0nz2mbxJfpPuM50yQMgJ+XCyYRduOuck9PQfQOnX9vqmmWmoWjl7a6hSeJiCCUYBgcHpwRXgXxX+GPhvw34GOt6at8bsSQxK1zMzYTGACrDIwAAB2xivaPBCGPwB4cRipK6XbAlWDDiJehHB+ooA3q+abDw38Fr/VjLF4j1EW7NzbSQyqkIYqqlpSmFUMfvM2PmAJGMn6RubmCztZrq5lSK3hRpJZHOFRQMkk9gAK+a/gz4L8OeM/D/iPTtU2m8aSEJNFt86KMHdujLKduSME46cUAFtpfwRW9uoJtW1GTDyujneqKqD7oYD5s7SVPU5A64rofCSfDnU/HdvrllNq8t3LdxwWha0MVvA6xYVCVGCWVT1OTgn1NU5vC/h6z+O+i+Fl0m0GkWtsZEhZIyZZGVmJlZ/ml5AwpJwBwAM03WtP03Rv2i9A03SLCCythJHM8cCBVaRg2TgdsYwOgycdaAPoaiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACuV+JYJ+GniPCSP8A6BJwmc9OvHYdT7A11Vcr8S4lm+GniNW6Cwkb72OQMjse4/H1HUAEPwr/AOSXeHv+vQfzNdhXH/Cv/kl3h7/r0H8zXYUAFFFFAHkv7RBP/Ct4MOyj+0YsgOF3fJJwQevrgeme1etV5H+0Um74c2p+X5dTiPIP/POQcY+vfj8cV65QAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABXn/xt/5JDrv/AG7/APpRHXoFef8Axt/5JDrv/bv/AOlEdAFr4RfYv+FU6B9g83yfIbd5uM+b5jeZ07b9+PbFdtXEfCCWOb4UaA0WdohdTly/IkYHkgHqDx0HQEgA129ABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB5N8FQwv/G4ZQjDWpMqI9gBy3G3+H6dq9Zrxv4LM03irx1P5lzOram5+0Z2RSEu5z5eeGPXpwDivZKACiiigDkviexX4Y+IiEtX/wBCcYuWwnPcf7Y6qO7Bau+BpPN8CaE5kaQmyiBZpxMSQoBy44asT4yxrJ8JdeV9+AkTfJjORMhHXtkc+1dD4Qtza+DtGgNvBblLOIGOAkoPlHTIHXr079+tAG1RRRQAUUUUAFFFFABRRRQAUUUUAFMmEjQyCF0SUqQjOpZQ2OCQCMj2yPqKfRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAVdQ02w1a1NrqVlbXluSGMVzEsiEjocMCKiutD0m+a0a70uyuGszm1MtujmA8fcyPl+6vT0HpV+igArh/FHgLVPEWuNf2/jXW9Kt/KSNbSxlZEBGctwwznI7dup7dxXj3i77f40+LcXgW51R7LQY7L7TPFbzGKW5+X7meQ/JU7SPuqx6igCbVPh1q2iaRe6q/wAR/FsiWcDzMiTNIzBVJwFLYJ+pH1HWt74LwyQ/CPQUljeNiszgOpBKtNIQfoQQR6g1zHiL4O6D4Y8P33iHw5eahpuraVA97BOJt4zGpYqQezAEfjzkZB9D8C67J4m8EaRq8tuYJbiAeZHs2jcpKkqOykqSPYigDoaKKKAKGmXt3em9+16bJZCC6eGHzJFbz4xjEox0BycA88VfoooAKKKKACvKf2fWEnw9uZQu0yanM5HniQDKp0HVPoxJP3s4YV6tXk/7PNtJB8NZZHjZFuNQlkQlSN4CouRnrypGR6Y6g0AP+Nknhu3stGutYutUt9TjmYaa+mf65DlC7DJA4AUdQcsMe3N6oPBOlTX+j38PivxHqVkPM1HW7XMssC4ZSjy7wVjC7sr09ckcdd450TU7Dx9oPjjTdMvNVi06KaO7tIJtz7WQopijPf52JA64HA61w/grxIdG0nxbottpWrXtzqd1PLpoTTnUyl0wN5/hAwM5PAyaAPYtFt9A13wFbWWlkTaBc2JtYwrEExbShUnqGHIPfIPervh3QLHwvoVto2mrItnb7vLEjbm+ZixyfqxrnfBujap4K+E1vp8qwNqllaTyhPmdBIzPIFO0ZOCwB256HGeKvfDvxJeeLvAmm65fxwR3V15u9IFIQbZXQYBJPRR3oA6iiiigCG72iynLpHIvltlJPusMdDwePwP0rz74IYb4diVUjWOW+uHjEQwm3fj5QQCBxxkCu71Z/L0a+c7flt5D8xIH3T128/lzXB/AuNV+F1nKg2ie4nk2BGCp+8IwCc7h8vXPt1BoA9IooooAKKKKACiiigAooooAKKKKACiiigAooooAK8r+PEMFx4V0GG6eNLeTXbdJWkfaoQpKCSewx3r1SvOfjJcfYfDWl6l/ZN3qH9n6rBeBYXCorJnHmZViVO7HA6kcjoQDK0/WLv4ffaPC/jovN4anLW+m6sVZ0MbA/uJSMlTt4GfRuSBkcbc28el6z8RL3wg8cXhtNFWJZtOmJgM58o4znaXAMvK52huxJz9B6jptlq9hJY6jaxXVpLjfDMoZWwQRkH3AP4Vk6r4d0iHwZqmk2+lwRWElvKWtbZBGpJBPAUcHP9KAPL/D9prvw+0Lw34i0eO+1Tw1daZBJqmmRuZZIpHXe00SngDLDIGOhz/eXN1D/hHNT+KHgq7+HUcUkz3LXOoy2YdWWHzFDmQEjblfMyCASCOu4V6f8KZ1m+G+kqmlzaasSvEIJmLH5XILZPPJyeg5JA4xXV2VjaabaLa2NtFbW6lmWKFAqqWYscAcDJJP40AWKKKKACiiigArmPiJqdxo/wAPtbv7SVIriG2JRnRXGSQMFWBBznGCO9dPXKfEu1t734ca7b3VxDbwtbZM06MyIQQQSFBbqB0BI60AeYaxq/xG0fwj4f8AED+OVkTWZLSOOFdKgBjM0Zc5OOduMe/tWzdar418F/EDwpZ694oOtWGr3M1sYYbCKEj7qoxwM/ekRjg8BSOe9PRvGfwxh8E6N4e1/wASDVzpjJKksllcrh0JKYwmcKDtA7gYI7VY1XXvDnxF+Ing5/D3itFutNuXl+zG0nRpl+V3AcqAMpGwIJx+dAHUfFe3vr7QdNsNK8QR6LqdxqMaW0r3cluZCVZdgKKWbO4ccDoc9j12lrd2WgWS6vcxyXkFqgu7gHCNIqje+SBxkE9B+FeW/GpI7zxR8PtMkV1W61XaZopCkiDfCp2kdD8+c9QVGK1tN1i5tLWTwL8QWeOW9iltbTU2kAiv4SNoG/8Ahlw2MEAng9SMgHo8E8NzCs1vLHLE33XjYMp7cEVJXjfw0jXRPiv4n8L6FLcyeGrK3VmDv5qR3X7sEbsfKf8AWArnnYfTj2SgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDxfwfo+swfH/AMSav9hnXS5fOt2uTH8hP7tgMn6DkZ9K1/jB4L1fxVc+FrjR8Gazvij5XIjV9p81ufur5YyOp3Vy918Nvi1da1qN/b+Lo7UXNyzhf7RnTKhvlO1E2gYAGPTimeIdK+LukWj69rHjfSLeHTUfbIDtDh1AOFWHDE/dG4ZB6YzkgHdfGTRNT1/4eSWGmWkl5eG4iby4hyQDycE113hrTpdH8K6Rpk7K01nZQ28hXoWRApx+Ir5h0rxX8W/FGqtbabf6tPPuTzfLiCRxbhlS5ChUBAJGcZxX1lQBn6816nh7U20yJZb8Wkpto2AIeXYdgIPHJx1rxj9nbS763uPEd3eb7dg8cL2jw+WVfls4428HGAP5CvbdS1CDSdKvNSuiwt7SB55SoyQiKWOB34FYHh3xz4X8QaHc67YXkMMEaq94ZwI3hOMASe/GOpHHBNAHIR+BtVT9oA+IrqJr3SntjNFPJgi3fbsEeCeo6jA/iz1BrO8W+EfEE/x90HW7C0EtkwjZ59pZIVTIbf02kj7vPJ6ZwRXa3XxZ8EWUNnNca3tS8gNxBi1mYvGGZS2AmRyjdcdK8tu/EFhqf7Sek3+ly2moWkyRQCbbvQEx5JU9mGR06Hg9xQB9DUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVyfxObb8MvER8zZ/oTjOM9eMfj0/GusrkfiiUHww8ReYrMPsbcK2OeMdj3x9fbrQAnwtVl+F/h4MCD9kU8jsScV19cf8K/+SXeHv+vQfzNdhQAUUUUAeR/tFHHw5tfnVc6nFwc/N+7k4GPz5449cV65Xk/7QxYfDaLBwDqEWeTyNr+h+nXI9s4I9YoAKKKKACiiigAooooAKKKKACiiigAooooAKpalpseppbLJPcQ/Z7mO4UwSbCxQ5Ct6qehHcVdooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACvP8A42/8kh13/t3/APSiOvQK8k/aJCH4cW25owRqUW0PuyTsk+7jjOM/e4xnvigDqfhRs/4Vb4e2LGo+zdEDgZ3HP3+c5zntnO35cV2Vcx8Oby3vvht4bmtpBJGunQwlgCMOiBHHPoysPwrp6ACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDyH4MaebXxB45fNzCE1V4Rau4KKAzEH3bHGQcEV69Xk3wViaG/wDG8TxmJk1qRShIJUgtxkAA49hivWaACiiigDifi6kr/CnXxD5e7yFJ3jI2iRS344zj3xXTaD/yL2mf9ekX/oArn/ip/wAku8Q/9eh/mK6DQf8AkXtM/wCvSL/0AUAaFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAHN+OfCf/CaeGZNG+3yWO+WOTzkTeRtbOMZH8+Dg15VqE2gz/EvRNA07xxqd3DqF08mqOL8Y82ONDbKsiqFzvQDAJOcDg4rvfizqVvaeEVsZfEDaJJqNwlutysbP8pPzg45UY6n8O9clexfBu48N2+jWmt2mnfZ5UnivbR9tyJVzhzIVJY8nr+GMDABo+B9bvpPjJ4p0G51e8urWytx9mgnlLgAFAzdBzlh+fcCsb4o3WiWPxI06+ii8R2niC0iSX7Tp1ms0c8OcEYMinoWUkcfNg5rsfhyng6C71UaBr7a1qd3J9pvLm4kDzFegBIVflGfTqT7AdRf+I9B0zXLTTb+/trfUbiJ3gSXglBjd83QdOhIzt74oA8d8XfFDw94+0VNJjsvFkFol2j3gtbKNjNGoOYiRL8uSQcnP3RxXs/h25sbvw5p02mW8lvYmBVggliMbRoBgKVPIxjH4cEjmvKvhNr2lp4q8fT3E9pYLNqPmRpNOiHYGk7A7eOMkZznkng17BY31tqdhb31lMs1rcRrLFIvRlIyDQBYooooAKKKKACiiigArzH4BiAfC22MMlszm5mMwhTayvu6SHJ3Nt2nPHylRjjJ9Oryn9n1ox8Pbm3SGJHt9TmiklifcJ22od+enQheOMKD3oAofEnxd4m8P/FPS4tCeS6hh0prqfSy6ok6bn3kZOWfamcAEgKSMgtXT6t4h1Dxf8Ov7b8Ca5BZXUWJpfNjSTACbnhfIYKwDA5APKgdDmjVvB+pX3xp0LxVEsC6dYWDwzO0h3sxEoCquO3mA547+gzk3/wANrXXNev8AW/B3i5dJhvAYNRisEWeOaQE7s4cBWweRjqSe5yAalrr8/jf4Iajq80MFpNfaZeoU8zEaY8xASzdB8uST0qL4G+d/wqXSfNIKb5/KwOi+c/8AXdXW6X4Y0nSPC0fhu3t92mLA0DRSHd5itnfuP+0WYn69qf4b8P2PhXw/a6LpvmfZLYNs81tzfMxY5P1Y0AatFFFAGfr3/Ivan/16S/8AoBriPgXGyfCfTGZ3YPLOyhicKPNYYHtkE8dya7fXv+Re1P8A69Jf/QDXH/BL/kkOhf8Abx/6USUAegUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFU9S1bTdGt1uNU1C0sYGcIsl1MsSlsE4BYgZwCcexq5WF4v0jw/q/hu6XxNbJPpdqpupd28GMICSwKfNkDPTkgkd6AGf8J34P/wChr0P/AMGMP/xVcz8NPiQPE+g6rLrs9jbXmkz7LqaOQLAYznY4YnbglWHU/dB7imDw98JobfSb/wCwaL5OpvFHYswyszKCFG0/XDZHJxuyQK7GLwn4dt7G8srfQtNt7W8QJcxQWqRrKBnAbaBnGTj0zQBX/wCE78H/APQ16H/4MYf/AIquO0D4rW938S9S8N6he2D2c7oNIubWVHjfjlGdWI3McYHHOR1IFW73wp8JdIW403ULfw7aSSoiSJc3SJKoVABgs25CRgkggknJySSel0zw54OuILW/0rRtCkhR/Otri1tYSquCPnRlHXKjkf3R6UAdDRRRQAUUUUAFNkjSWNo5EV0cFWVhkEHqCKdWX4ksL7VPDeoWOmXzWN9PAyQXKkgxt2ORyPqOR1oA434o+FL7UfDNlD4W0WCa8i1OG5eKJo4AVRX5JJUHkgY989qg8YWtvD8WPh5AqpaWzTX0uYJDEXlES4DbcZyQo6ncGKkY69b4ItddsvCFjb+Jbj7Rq6eZ9ok3h92ZGK8jr8pUVc1Xw5pGt3enXepWMdxcabOLi0kJIMTjByMEZ5AODxwOOBQBzfxE0vw0Do/inxJqFzaJoFx59uIWXEshZGCFSpLZMY4GOM/Uc94k8c+EvFOippniPw9r6Wl7OosWfTpAZ24KPC2M7juxgckEjkHnV+NPhXUfFngL7PpUfm3VncrdiEfelCq6lV98PnHfGOprj7u81b4u6v4SEGiX2nR6VOtxqN1cq0UQkwpZY1DEn7p2k889uaAO++GknhWPRrvTfC9rParZXBju4bqIpOJCM5cNz7c/3cdq7avMfAWi6vefEXxP411bTLnSheJHa2tpMwJZFVAXYDviNMc4+ZhzjNenUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAeN+OfjVbQ6fqWm+GftMeqxSeXFfzpGludjDzChkPzkfdxjvkZFchpFmni3UUk+JHirR7q1ZN6smvQoYWxwvlIdueTkiu1uPgDpD3lxeW2uahb3FxJKzvsjbCuc7V4+XHIyOTk9OlFx8BLK/wBOS01HxTrd4LZFSyE0gZLVeAQqHIwQFGBjGBQBraNrXww+H2nTCw8Q6fi6nDTypefa5pXOcFtpY4HPOAB35PPpFfNHj/4W+EPh9Z2V9d32tXcdxOIxBH5QLAAFstgY6Hseo9M19KQyebDHJtK71DbSQSMj1BI/ImgCvqgsG0m7TVGhXT3hdbkzsFj8sghtxPAGM14v4e+E/wAO/EcEb6J4iuLqNZme7hhnwZo9wKxsh+ZVGBg9TnOemPatQ0+01XT7iwvoEntbhDHLE/RlP+etfPHi/wCEk3w40mLxPomuzhbKRGupeUnUNIFHlBflPDrkN/dznB20AdZefs96VcXU7w69fW0EkkjJBHGu2NWOdg9un5Vi6J4U0nw/+0Hp+jQLI8Fpp/nxmaVmLT7Pmk5PU8kgceg4qxoXxQ8aaTouma9r+n/2t4eubQvNcWlsyS2xSTyssxwjFiAccZzkYA53fDeseAfHfxNXXbE3o1q2tgsUdzGBFOoz+8QEE7l6Zyv0PWgD1miiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACuP+Kn/ACS7xD/16H+YrsK4/wCKn/JLvEP/AF6H+YoAPhX/AMku8Pf9eg/ma7CuQ+FpB+F/h7Cgf6IvA+prr6ACiiigDyn9oQsPhouApBv4s5QtgYboR93tyeO3UivVq8k/aIcR/D6xk+TemqxMgdNwJ8uTseOmeuRXrdABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFeRftFyvH8OrRVIxJqcatkA8eXKfw5A6V67Xj/AO0d/wAk80//ALCsf/oqWgD0DwJ/yTzw1/2CrX/0UtdBWD4IKHwB4cMasqHS7baGbJA8pcZOBn8hW9QAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAeT/AAWtvsd941t9yt5esMAVcNxzjkcV6xXkvwTz9t8bbomhP9syZjYAFOW4IAAGPYD6CvWqACiiigDivi4Zx8KvEH2dUL+QuQ/TbvXd+O3OPeul0H/kXtM/69Iv/QBXPfFZlX4XeISxAH2Ujk9ywxXQ6D/yL2mf9ekX/oAoA0KKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPFP2kjH/wiujhiA/20lRtyT8hzz2H8+K6/wAZ6Nrc3jnwNq2i2cU0GnXM8V1vOBFFKiqzYyOiK+Pfbwc4qb4j+IvDHhmz0u+8Sad9u23gNsqxB2jcAkuM8ccd/T0rndA8UeOfiPbz3Oi3GjaBpTS/u7ni7vEQA4zHkpksMHdtwDwDjJAK3ge48z9oDxwm4cwrxkAnaUXp1PXr/iK7bxB8NvCPinU/7S1nSBc3ewRmQXEseVHThGA79ai8IeAYvDGo3ur3WrXmq6zfgC6vLg7Q4GMAIOABjjk4HA4rsKAPP/8AhSXw8/6F7/yduP8A45Xb6fp9ppWn29hYwJBa26COKJOiqP8APWpppY4IZJpWCRxqWZj0AAyTUVjfW2p2FvfWUyzWtxGssUi9GUjINAFiiiigAooooAKKKKACvKvgLD9n8J65DkHy9duEyM4OEiHck/mT9TXqteVfAWQS+E9ckDlw+u3Dbyu0tlIucdvpQB6rXkvwNCCPxd5ckrJ/bMgXem0Y7HqeT3Hbjk16le39nptsbm+u4LWAEKZZ5Aigk4AyeOTxXFfC/wAKap4Utdci1QBWvNSe5iCTB02EDBx2bjn6D0oA6zX7q8svDmqXenxiW9gtJZLeMruDSKhKjHfJArB+GPiLUPFXgHT9Y1QobudpQ5SPYpCyMowM+gro7u3tda0e4tWaOa0vIHiYqdysjAqeQeeDXFfBFQPhFoh55NwTk/8ATeSgD0GiiigDP17/AJF7U/8Ar0l/9ANcf8Ev+SQ6F/28f+lEldhr3/Ivan/16S/+gGuP+CX/ACSHQv8At4/9KJKAPQKKKKACiiigAooooAKKKKACiiigAooooAKKKKACvMPjo6x+DdMa4F0dNGrwf2gtuSC1vtfcCR2JwOeM7favT65D4k+KbXwn4RkubnTBqbXcos4bNkDJK7hiA45yuFPGDngd80AcJ8bX0HUPDHhyx0+408vJq8aw/ZtjFYyrbyoHGMshPYkrmq9hBDpXxt8VWOlJHax2Xh6QuY0KtJIwikL5DY3ksp3AAYXGOM1JNHovwvttF1u7+HcSavqM8kUn2a4My2sob92ELlgC6nI24xgjtXS+HfiNY6t4z1LT7rwrdaXqtnYtNdzTLH5ixJtIUkfMR84IHPX8aAPKLWDwRcfAx76++zS+KpzNEsjs7XDziQFQPX5Gj9uee9avh8/2L408AWWjxQ2/iRovs+uWFuxWIw7RlpRuA84Rguw5y69yADsaW03jDxA3jbwz8OrJmdgy3uq3bKZJVJXeiAlMjYvzAZ3Z5zmuk0rxVHovjGztfFPgy10PWdYJii1S0CSpcsxXCNIo3Ak4BBJ5CnoQQAen0UUUAFFFFABRRRQAUUUyaWOCGSaVgkcalmY9AAMk0Acz4x8f6L4FNj/bIugl6ZBG8MW8KU253c8feGPxrmJPj74Ij3Ay6huXOV+ykHPpya7Ky1Hwx45sHNu+n6zaQSjcrosqpIBkHDDg4PB+tWLrwt4evrh7i70HS7idwVeSWzjdmBOSCSMnkk0AcZ8HfiDeeO9Dvhqnl/2lYzKHMUe1WjcEofrlXHHYD1r0mqtpplhYPK9nZW1s0uPMaGJUL46ZwOcZP51aoAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA8Sj+Od7pUN0fEOjRreDUWtk02DdHPFEFyHbcSHy2F4wOCfQVXvv2hnl82PSNEtxLAwd/tt2E3RqCZAAQvz8AKASSf4TjB9tlsLOe4S4mtIJJ48bJHjBZcHIwTyOeaoS6J4e1WUyyadp1zJDOWZ/KRiso65P97k5z60AfOPxL8fy/EaDSdPh0WWxjF6wgupZGaOYNhVI+QY7kjnHv2+pFVUUKoAUDAAHAFePfHPQzdW3hd4bOWS2i1BLeVI93lIjEAAoPlGegOPQZr2KgCOeeK1t5bieRY4YkLyOxwFUDJJ/CvHPiv8Q/CuueA9f0HTdZt7i/2QSIqHKSASxudj/dYgdQDng8cHHb/FJtNT4baw2rpdvY7I/NW0kVJT+9TG0tkdcZyDxng15D4a8SfDTRPDcC3+hXipDeC8s555I5ZrieInAZodvC7+A42kMetAEHhrxN48/wCEN0rwj4WsJftFtfyW9xqcMCXVqsbEMEMo3ocGQliMYATGcmu/+Hvwn1Dwv4kfxBrGsQXF2wci2s4RHCrSAbyBgAdB0UdPyyU+OHh/wqINHXwfqelwxBWNt5aRGNXAckJnuWY9s9eM8dV4M+LujeNfEEmkWtpd2swgE0RuAP3nALDAzjGepPPagD0KiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACuP8Aip/yS7xD/wBeh/mK7CuP+Kn/ACS7xD/16H+YoAd8LnaT4YeHWY5Is1X8BkD9BXXVyfwx8n/hWXh3yPM2fYkzvxnd/F07bs49sV1lABRRRQB5H+0OobwLpalVYHWIgVZwgP7qXqx6fXtXrleR/tDgHwLpYYRkf2xFkSEhT+6l6kc4+leuUAFFFFABRRRQAUUUUAZ97Y3lzqenXVvqcttBbO5uLZY1ZbpWXABJ5Xa2CCPcdwRoUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABXj/wC0d/yTzT/+wrH/AOipa9grx/8AaO/5J5p//YVj/wDRUtAHqul6dBpGkWWmWxc29nAlvEXOW2ooUZPrgVbrG8IzSXPgvQp5mmaWTTrd3ady8hYxqSWY9W9T3NbNABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB5T8GhcjVPHP21Nl0dbkMq+jEsTXq1eX/CWH7PrvjyP7M1sP7bdlhbHyA5IHy8dCOleoUAFFFFAHGfFmKOb4WeIFkUMotgwB9VZSD+BANdHoP/ACL2mf8AXpF/6AK534rpv+FviEbmX/Rs5U46MDiui0H/AJF7TP8Ar0i/9AFAGhRRRQAUUUUAFFFYepatq1r4o0bT7XR5LjTrvzftd8G4t9qkqMe5xyfwyaANyiiigAooooAKKKKACiiigAooooAKKKKACioL2GW5sLiCC4e2mliZEnQAtExGAwB4JB559Ki0qwOl6Va2Ju7m7MEYQ3F1IXlkI/iZj1JoAuUUUUAFFFFABRRRQBh+KfEuheGdNW48QzeTZTP5W5rZ5kJxnB2qcdO/pXmPii2+E3iyB5HS7tLtwCt3Z6TdI3TAyBFhh07dutdT8YvF6+E/BEvlxxyXt832e3EqblQ4yXwQQSvUA98dcVnePtb8SeHdH8H6Fpuq2w8QajeRW7S/ZyI5Qu0MT12ruZNwxkgnGBkUAN+EviHXb7UtZ0fU9Sn1Kxstv2C8urKSKWaPOMszAZ7cNliSeSBXQ+J/FfijRtWa10jwPcazarCshukvViBJOCoXaSSOPfvjAzWJ8LvFetXOr614P8TXEV3q2kEEXcWSJkJwckgfdJXnAyD04yb/AI7+Icuh30Xhzw5YPqnii6XMVsFOyFSCd7ngHp0z7kgYyAU08fePZHVR8LLoFgSN2qoBwM9SnH+PHWrfwS/5JDoX/bx/6USVi3HjX4heC4IbrxfoFpe6Urr9q1DTpQTErdtnX5T1OMHpnkGu48C6bo+k+CtNs9AvZL3SlRnt7iRwzOHdnOSABwWIxgEYweaAOiooooAKKKKACiiigAryj4A+ePB+tfaiTcf23P5pIAO7y4s9OOvpxXq9eTfs+zi58F6xOu/bLrUzjectgxxHk+tAFb4jQrd/GTwXZa+6t4YnRikMrjypLpd/DL35aAfNwQxHdqv+N/iZB4f8dHwtfmFNKvNM/eXWDutpm8wAt6rgLwBnnPtXdeJ/C+leL9GbS9XgMtuW3qVOGjfBAZT2OCfzqtoPgjQvD1tJFbWzTyTRGG4nu3Msk6ZJ2uTwRzjGOlAHFfs9x3Ufw4m+0GQwtqMptmbO1o9qAlM/w7w/4575q98CZfM+FOnr5br5c065ZiQ37wnIB6DnGB3BPUmumtNE0/wV4MvbTS5JLW1t4p7hZGZWMRO5yRvIXA7A4HHPc1znwNhSL4U6ayTNIJJZ2w38H71lxjtwAfxoA9GooooAz9e/5F7U/wDr0l/9ANcf8Ev+SQ6F/wBvH/pRJXYa9/yL2p/9ekv/AKAa4/4Jf8kh0L/t4/8ASiSgD0CiiigAooooAKKKKACiiigAooooAKKKKACiiigArkPiP4Y1DxP4cgTSJ0h1XT72K/sjI2EMseeGOD2Y49wMnGa6+igDx/xDY+PviENNsL3wlbaJaW2oRX4uZtTSXhFYFCqAnJ3Eg4GMYPrWo/gjVn+LfiTWYZYorLU9EW2S4eHcI5GKIV2hgXwIi2cjG5RXplc14k8aWXhjXPD+mXcE0j61cm2iePbiNsqASCckEuo46c+2QDiNDtfiR4A0G28Oab4Z03W7W2RzFeRX4i5aVmw6ybTnBPA45HJwRVy28MeMfGHinRNb8YRafpunaXK1zBpdrO8kplB/dmQg7DjG7cp6cEfMceoUUAFFFFABRRRQAUUUUAFVdTAOlXgMkcYMD5eRQyr8p5IPUe1Wqoa4QNA1IlQwFrLweh+U0Aecfs/wxxfD2UpbxqTeyr9oQD/SAMYb+9xyPmANerV5f8Af+SXwf9fc38xXqFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABVXUr5NL0q81CSKWVLWB52jhXc7hVLEKOMk44FWqKAPmDRvBE/jDSo7rwz45uPNSIiWC+kkjcytJL5anBKqSiD5QWIxnoRW7a/AHxNEqFfGPkGYCW4CCTiUj5ujfNzxuOCeuB0rV8f/B3RdL02TxF4amvNIu7APOUtFeZpG6qEG4FDuwMg4A5xxXG6JrXxci8K6XPp1pfXGl2FywJgxJcTfOCY5Fyz4HIHy8BucjGADV0/wCF3xNht7W1m1CBNNh1CO5NjHdnb9/eWHGOD2z1Oe2a+iq8g8O/HixvcQa5o13Y3LyxpF5CmRHV22gksF27TweucHGelev0AQ3dpb39pNaXcMc9vMhSSKRcq6nqCK4LXvBfwy8M6TLqWraLpltbIDgvwZGwTsUE/MxAOAOeK0Pij4k1rwp4Jn1TQ7NLi4SVFkdxuEEZzmQr35wPbdnoK8f8HeD/ABL8Vb+z1bxVqF7NoMBWT/SMp9pOeY41VsBc7hvGDzgewBvfBmRNe8Q69eajLa6kuor9tRJdkssGJXj2yAqSrbVUgBsBccc8WNPu4br9qK7SGIRi3sTA2CpBKxrzwfcDHUY5x0rI13wb428A+OBr3hSO71SK52wwojGTy41ACxTrjLJsUANkYwOc4z0Pwq8CTWHiCXxH4k1cyeKpYi8th5qF4Y343SgZOTgY6Ae56AHsdFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFcn8TohN8M/EKNLHEPsbHfJnHHOOAeuMfU11lcn8ToTP8M/EMavGp+xscyOFHHOMn6fnQBH8K/8Akl3h7/r0H8zXYVx/wr/5Jd4e/wCvQfzNdhQAUUUUAeRftEosngPTEbeFbV4gdi7mx5UvQZGT7Zr12vN/jFai907wnaERET+JbOMiaISIdwkHzIfvDnkd+lekUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAV4/+0d/yTzT/wDsKx/+ipa9grx/9o7/AJJ5p/8A2FY//RUtAHpnhqVJ/CukTRWbWccllCy2rMWMIKAhCTycdMnnitSsnwtF5HhHRYTJFIY7CBd8JYo2I1GVLfMR6Z59a1qACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDzj4aKq+KfH4RXUf20xw6BTnBzwAOM9D3HPOc16PXnnw6WNfFvj4RoEX+1wSAu3kpkn8Tk575zXodABRRRQBx/xU/5Jd4h/69D/ADFdBoP/ACL2mf8AXpF/6AK5/wCKn/JLvEP/AF6H+YrlLT44eGNJ0rTrW6staWVbJSf9DABKoMgZYZ6dRx70Aet0V5a/x20BJkiXQPErmQbo9tinzgYyQC4JxkVLL8btFjlZF8O+KJVB4dNPXDfTLg/pQB6bRXl7fHDSTDKyeGfE/mKmUR7FQHPpkOceufQHvgHOHx5UkgeC9bJU4OF6H8qAPYaK8f8A+F7/APUla5/3x/8AWo/4Xv8A9SVrn/fH/wBagD2CivH/APhe/wD1JWuf98f/AFqP+F7/APUla5/3x/8AWoA9gorx/wD4Xv8A9SVrn/fH/wBarWn/ABmu9UuhbWfgTXJZiCwXAXge5AoA9Worzq4+I/iC2gaRvhzrpwQAAyHJJwBxk9SO1ZF349+JqiyFt8Pm3pGReiQkh5MdYyG+Vc5PO70z3oA9corxV/HvxgMACeArZZcj5mjcrjHPHmDvk9enHPWof+E6+NP/AEI9j/4DSf8Ax6gD3CivFIPGfxsuG2p4J0wHIH7yNkHPu0wqaLxT8cJgpXwXo43LuG87ePfM4wfbrQB7LRXl3gD4i+INZ8aah4V8Vadp1lqNvB5y/Y5QQCCuUPzuC2GB4PG05Hp6jQAUUUUAFFFFABRRRQAUUUUAcJ8VfC0PiXw3E4u47bUbGYT2LTSIsTS/3X3ggggHj29Mg43h+HVNR8X23inxjrHh8T2VpLBZWNndYSGRyMyZLHll3Kevb0rpfiFYeDbvRoZ/Gixmzt3LRb53jO8joNjAk4rzs+IdIaSNoPgVdS2zruEo0dMkEAqQBGQcg+vHvQB1nwu0x49S8U6zf3unT6rqV8Hlt7K5jnFtGMlFLKMg8sOeoUHrmsfxnJrHgb4pxeN7fSptT0e5sRZ33kJl4FDLkjnrkKRng8jI+8Ot+Huo+GNUsL248PaLFo9wkoh1C0FosEkUqj7rgAZxk4/Hoc1y3jfUfE3ij4jWvgjw/eDS47RI9Sub4NltqkYyvG4BiuFzgnrx0AJtc+KMXiLSE0rwdpt/fanqkSJDJPp5+zRbiA4lL4Bwm7JAZfcjNdn4E8ON4S8EaVokknmS20RMrZyPMZi7gHA4DMQPYCuW8QeF/Feg6Q+saN461Ga4sIJJprfU0SWKfauSBtUbOA3XdzjkYzXX+DvEUfizwjpuuRps+1RZdMcLICVcDPYMrAHuKANyiiigAooooAKKKKACvKPgDJLL4P1qScuZn1udnLxiNtxjizlRwpz2HSvV68t+BSLF4Z8QRoMKuv3KgegCR0AepUUUUAZniOV4PC+rTRuY3jspmVxMISpCEg+YeE/3u3WuR+CKqPhFohAALG4JwOp8+Sux15xF4e1OQhiFtJWO1wp4Q9CWXH13D6jrXD/ApVX4UaaVMRLSzltnUHzGHze+MfhigD0iiiigDP17/kXtT/69Jf8A0A1x/wAEv+SQ6F/28f8ApRJXYa9/yL2p/wDXpL/6Aa4/4Jf8kh0L/t4/9KJKAPQKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAryL4vBf+E8+GZ2OWGrgBwflA82DgjHU8Y57Hr29dryL4vAf8J58Mzxu/tcY9cebB7/AE7fl3APXaKKKACiiigAooooAKKKKACqWsbBol/5gBT7NJuBJAxtOenNXap6skcmjXyTS+VE1vIHk27tg2nJwOuPSgDzv4A/8kvg/wCvub+Yr1CvMvgOqJ8NUSOQSIt7OFcAgMN3BweRmvTaACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiorm5gs7Wa6uZUit4UaSWRzhUUDJJPYACgDwD4m/GDQPFvgO60XRftJurq4RHjuICCY0cNuUgkclVxnnGeAcVkeGbD4uTWVp4a04XOiWc0Rf5rSO2EcYbYzs4USByw6feI56c0eF/ifPoWh2drpPgiy/ss3e4Sb5G/wBIz03uMGXbtIAJOCv4dDJ+0Hf6bqixar4ftDbtvytreK8seCQA2CQDkcg460Aa3gb4N3Nvq6eIPGd7JfapBLugi84ypwch2ZsliTk44xnP09krxrS/2h9H1LVrSxOh38f2mZIQ4dW2liADjv1r2WgDI8SeJtJ8JaSdT1m6Fva71jB2lizHoAByTgE8dgT2qZdf0Z0R11ewZHUOrC5QhlIyCOeQQc1neNfB9n448PNo97c3NvEZBKHtyAdwBxnIORznHHTqK8buP2Z7lfK+zeKYpMyASeZZFNqdyMOcn24B9RQB0XjH42WTxap4d8KW2oXevMTbWs8MKyRM2cMyFX3EhdxUhTyBwRWt8LPAGs+H7i61/wAV3rX2t3cCRKZZmmktkBJZC5JBz8nTpt4JzXmlrJrfwX1GDVdW8K29w9wwtPtUWxYxCi7QEKA7ZX27yW+8B0zu2/Qmh+JdE8S27T6LqlrfIgBcQyAtHnONy9Vzg4yBnFAGrRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABXH/ABU/5Jd4h/69D/MV2Fcf8VP+SXeIf+vQ/wAxQA/4Yf8AJMfDv70Sf6EnzDPHtz6dPw44rra5L4YSGX4Y+HWKouLJFwi4HHGfrxk+prraACiiigDz/wCKf/Mlf9jXY/8As9egV5n8aheNo3hgadNFBfHxHai3lm+4km2TazcHgHBPBr0ygAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAa5ZVBVdxyBjOOM8n8BzTqRiQOFJ5HApaACiiigAooooAKKKKACiiigAooooAKKKKACvH/ANo7/knmn/8AYVj/APRUtewV4/8AtHf8k80//sKx/wDoqWgD07w8+oSeGdKk1YMNSazhN2GUKfNKDfkDgfNngcVpVS0dQuiWCqrootowFebzmA2jgvk7z/tZOetXaACiiigAooooAKKKKACiiigAooooAKKKKACiiigAqpYala6kLk2ru32ad7eXdGyYkX7wG4DI9xkHsat0UAFFFFABRRRQB578O9//AAlvj7zNuf7XH3fTZx+mK9CrzT4Wzx3PiXx/JCqKn9tuo2AAEjIJ49SM575r0ugAooooA4/4qf8AJLvEP/Xof5itW2W9fwPCmmyJHftpqi2dxlVl8v5SQeoBxWV8VP8Akl3iH/r0P8xVm9FsfhdcC8mkgtToredLEMuieT8zKO5AyRQB554B+LHiG71U+HNX0afXLq3nMU2qaXtdFy2FLgBUCjkb9wGB0JyTufEDWPFKeNdL0Pw7ri6YbjT7i52m1jmM0kYJCqGBOTjHHbJwcV4j4eXT7LVFe61PxXoeiQyrLAsSM7zPjLSggBY8BFY/K5wuMnGa9M+NVlNPqGhXVlpN3qV19kkSO7acxRR8cMdu0+Zkhh8wHBG09gC38OfFfjW88cRaLr1+98p00XV7DLp6272EhJ2xkhVySuw8j+LGOCa9lrxf4OWQ0/XLtU0jUnmntFN3qt/fRsxcYARYlzhT1BLE8YrJ+K+mazfePL7yP7XdG0+MWIsb1EVZQTkOhOdv3jxg9+c0Ae/UV5r8G11SPRdUjvPtwsUuwLJL66S4lQbAXBdQP4jnoOv1r0qgAoqhpOtabrtrJc6XeR3UEczwM8ZyA6HDD3+vQggjIIp19q1jp2n3V/c3AFtaAmdo1MhTGMgqoJzyOMUAXaKpaZq1lrOkwapp8xns508yKQIwLL/ukA/hisTw/wDEPwv4kthJZ6tbRSkuDa3MqxTrtJyShOcYGc+n40AdRWdoeu6Z4k0tNS0i7S6s3ZlWRQRypIIwQCOR6eh6GvH/ABjq2uRfFK60TStd1+7EqJcSWultGXslCcjYyhWzlCPmXAJzkmt/4ASRt8Mo0WSNnS7l3qrAlckYyB09ee1AHpV9f2emWcl5f3cFpax43zTyCNFyQBljwMkgfjSWGo2WqWi3en3lveWzEhZreVZEJBwcMCR1ry743aJdaj/wjeoQwwX1tp120lzpslwIjdITHwM8HhSCeSA+cda4nw5LANZ8Yxx6DLottfeHJymk2s4mExRCCQ4U7TgnHGMnv0IB79rmu6f4dsFvNRlaOF5UhUqhYl3OFHHvWlXyXpPhl5tU/sWz0HxHBrFle2t29rLcpNDHEQpZpAETa+GBHBOOOMHP1pQB4f4ML3H7THiyRpZAY7SQYDZ3ANCoBznjocew7cV7hXiPhRI1/aa8TfZUn2/Y3M3msFwSYskADlckYHuD2r26gAooooAKKKKACiiigAooooA8o+O2harqvhvT7/TLNLoaXO11PGwDfIB1Kn7w45HpW9H8WvAUehf2hDrtqttERGLdVKzDoMCIgMQM9QMcHnipfiK3jdLDT38ERxy3K3Ia5jcxjfGBkAlyOCeDgg/rXmXi/VvFPhoQeIdRs/Bena85BigCeff7OVRVAUgbRuBcNg8DPAFAHZfCJ77V7rxP4seA2+la5drLYRPIGfCF0diB0yQB/wABPbBJ4w0TVtJ8c/8ACX+GtX0lNTlshaz6fqjhUlj3DBU5BHKg9RyvU5xWh8Ln8YXlnqOpeKIEsYLl0Fhpoh8o2yLuDfLjIByvXn5Se/PI33h7QvGn7Q+s6ZrNmbu3tdIRzGZHjxJmIg5Qgn5ZPpzQBLf6z468ZacNB1AaD4fs5wsd9fxamkkjLuyTEqSZUEDaVbOQ3XGcep+GtJ0/Q/Den6ZpbI9nbQhI5FwfM9XOOCWOSSO5Ncp/wpL4ef8AQvf+Ttx/8crsNE0TTvDmjwaTpNv9nsYN3lxb2fbuYseWJJ5JPJoA0KKKKACiiigAooooAK8o+AJz4P1o7i3/ABO5/mOcn93FzySfzJ+pr1evK/gPn/hFdeyGB/t24yGYsR8kXUkDP1wPoKAPVKKKKAKWsBDol+JAjRm2k3B2ZVI2nOSvzAe459K4b4Gh/wDhVOmszNtaWcopXAQea3CnPIzk59SfSuz8RuYvC+rSC4FsVspm88jIiwh+bGR069R061xvwNUD4T6UwhMe55jnfkP+9YbgMnHTGOOQT3oA9FooooAz9e/5F7U/+vSX/wBANcf8Ev8AkkOhf9vH/pRJXYa9/wAi9qf/AF6S/wDoBrj/AIJf8kh0L/t4/wDSiSgD0CiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK8k+LsjDxx8NYhjY2sqx+Y5yJIccZwep5xx6jJz63XlXxbdB4s+HUZSMu2uxsHLHeAHjyAM4IORk4OMDkZ5APVaKKKACiiigAooooAKKKKACq9/EJ9OuYWXcJInUrtJzkEYwCCfwI+oqxUF67R2Fw6oZGWJiEEe8scHjbkbvpkZoA87+BSCL4ciMK6Bb6ddrrtYYboRk4Ptk16XXmPwFfzPhnG+1V3Xk52qMAcjgV6dQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVS1hbJ9Ev11JC9g1tILlQrMWi2neML8x4zwOfSrtNkjSWNo5EV0cFWVhkEHqCKAPHf2dYIZ/h9dtLFHIYtYkeMuoOxvJiGR6HBIz7mvV5dH0yeVpZtOs5JGOWd4FJJ9yRWZ4O8HaX4H0P8AsnSfPaFpWmkknfc8jnAycAAcBRwB09ck9BQBz1r4F8L2V8t7baLaxXS3TXiyqCGErDBPXp/s9B2FdDRRQAUUUUAVr/T7PVLKWyv7aO4tpVKvHIuQQRiuI8HfCbSfBfie71eyuZZo5IhFbQzLlrcdXw+ecn2GPevQKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACuP8Aip/yS7xD/wBeh/mK7CuT+JzRJ8M/EJmjaRPsbDar7Tnsc4PQ4P4UAN+FzBvhh4dIGP8AQ1Hbtkdq66uP+Ff/ACS7w9/16D+ZrsKACiiigDyb9oFIZPBejpcTi3gbWoRJMY/MEa+XLltv8WBzjvXq8brLGsiHKsAwPqDXkf7RZI8AaaQ+wjV4sNz8v7qXnivXI5EljWSN1dHAZWU5BB6EGgB1FFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFACMwUZOeoHAzQrBlDDOCM8jB/KlooAKKKKACiiigAooooAKKKKACiiigAooooAK8f/aO/5J5p/wD2FY//AEVLXsFeQftGqX+H2nKoJY6tGAAOSfKloA9XsLOLTtOtrGDd5NtEkMe45O1QAM/gKsUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAHlPwbcy6r47kKSIW1yVisgIYZZuCCTg/ia9WryT4KbW1Pxy8YMcDay+yIptKDc/bJxwQMZ4xXrdABRRRQBx/xU/5Jd4h/69D/ADFWb2wuNU+F1xp9oge5utFaCJSQAXaHaBk9OSKwfjjJHH8KdU3vIrM8KoEfbuJkXg+oxk49q6/TLuCw8I2d5dSrFbwWCSyyN0RFjBJP0AoA+c/C/gTWXj0iLSfDOu6X4kt5993q94TBBCnmHmMN987CARzn5uCDmul+POoXGn+KPCSJqQi8pWcyXVussUbblXzWTaQxAycBOMccnjvU+L/hvUbiK18PLeazdSSImyC1kRV3MASzOoxgbj77cVifGeHxKEt7m0kvR4XW2dNYWzuI432EgHh/vcHoAc8g4zmgDI+Glwl78W9T1GHVbSCO90+KQWAs3gkmUIoV8MgUZAEnyEghx2zjI+LFro17421HRjFomjubVbuXUrlXM9xKeQiHO0ZwAcjpnntWj8FodCk8TzTx69c6vc2toLPT/NsJIRDCMuwzkqOWIAJzjnvgSfE+d/DHjXUNV1nS2uvDet2UWnyyQXCLOGX5iFBOf4eeMcjkGgDoPgvCksOvapANNtILq5RV0vT5VlS12JtyWGclhzwcHk9SQOX+Jt1quieOZZvP8bx6TJ5R36feyLA8jHmNOMJngYBPPaul+DEsGpX/AIz1u0WQ2l/qpaCRkKAoNxCgdMqHwce3tXnvxHsbm2+JF/PqEAuNPuLmJYdPbXESV2YjEqxqSyLweqnG7PPYA9T+CumzaV4HntptOu7A/wBoTMkV5bNDMUO3aXyBuOMDcABxjsa8l13U9D0zxr4s03RrrX5tV1a5mg+S+Flbx3LSv97BG9VJIG4gYZuOQR7D8ILyC68GzxxPdGW1v5redJ7v7SqSLgkRSfxR4II92PJ61474o8PQeLvE/j2bw94bDyaZPmWd7wxssmW81vLJIfc0cpABXqDj+GgD6C8F2DaN4H0exkmjma2tERngUbSQOcbcg+mR1696+YdVtf7T8a6jf+LPD+vRzalckWdrp8C20jt2yjoScjb2yxyck5r6a8Najp998P7HULVBY2Ulj5mIIhH5I2/NtVQQMHPABr5n0tmbTfFmvWdzeXskMhjbUW1f7PdG3Z1UFkZW37+B168dcZAPRNUk8QX3xfvrrwjaanHP9khj15bZ7b5W2KQiSS5TeOFPU/KSO+O/+E/hm48KeBLaxvtO+w6g0jvcqZUkLtnAbcnH3QMDJwK8t/s7Wdf+I2sR+Gdd1O3t7y30mRp0fZPPatHEDMWODlRycYJJIx1Fej/BXVL3V/hzBc397dXs4uZk8+6kLuwDcZJyeh9TQBR+MHhq812XQruHw9HrFpp4upLpZb37OqKUXAOGDHkBvl5+THeuB8JadqmmJc+L9K8Px6HpZ0C8nDWmpGVpGCsUJWRmYEFQR8pHTPPFdL8d7XUNS1vwdpem3SwXF491EoecxJIT5OEJB7ngDuSKzvAemXEXiC20bWtEsrSHxD4ekjb7KJYZY0BO4MjkhWOSTtCjkH2oA4jwv/afh+/g1Z01SLUGNn9qu01NMTW1yQ0YCGJjyFXOX4x09PrKubm8C6FPoNpo7W8n2a18jY4c+Ywh/wBWGfqQMnjoM8YrpKAPFPBhB/aX8X4WMf6C/EfT70H6+vvmva68W8HqU/aX8XAxyI32BiRI+4nLQEEcDAIIwOw717TQAUUUUAFFFFABRRRQAUUUUAcv451rxFoujrJ4b0FtWvJi0fEmBAccOV6sM9sj615T4SsfHOkajfeJNX8AXeua3P8A6y7utTgi2qMMBHFtJGMDp6YAGMV79RQBy3gvxB4g16K9bXvDMmiNE48gPMH81Tn2ByuBk9DnipbLwZZWXj7UvF63E7Xt9bJbGJiNiKNucd+di/r610lRXNzBZ273F1PHBAgy8krhVUe5PAoAlooooAKKKKACiiigAooooAK8s+AkcsXgnUklcHbq84VAQPLAVPl2jhecnGB1969Tryn4AAL4F1FQFUrq8wKeWY2X5I+GU9D7AnAwM8UAerUUUUAYfjSUQ+BfEMrRRyhNMuWMcmdr4iY4OCDg+xFc38E3D/CLQ+VJXz1IGOP38n9MV2esWc+oaJf2Vtcm2uLi2kiinAyYmZSA34E5/CsrwL4afwh4M07QpLhbh7UPulUYDFnZzgf8CxQB0VFFFAGH4zunsvBOt3KPEjR2UrBpgxQfKeoXn8q5n4IBh8ItFLNuBM5UYxtHnyce/OT+NdP4we2j8G6w15cPbwfZJN0qSFCvynGCORziuY+CCBfhFopBbLmcnLE/8t5Bxnp06D696APQqKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAryj4omRfiP8NwkqYfUXzFOV8o4aLnDcb8MQvfJGOcZ9Xryn4oOI/iX8NCRbEG/mH+kqSvJhHGP4uflPZtpoA9WooooAKKKKACiiigAooooAKqapEZ9IvYQruZIHXan3jlSMD3q3UVzbx3drNbS5McyNG2Dg4IwaAPNvgLG8XwzjjkRkdLydWVhgggjIIr06vOvgrbx2ngWa2iyI4dSuY1ycnAfAr0WgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACuP+Kn/ACS7xD/16H+YrsK4/wCKn/JLvEP/AF6H+YoAm+GkUkXw08OLKrBjYRsAzbuCMj9COO3Suqrl/hu8L/DXw4YN2z+z4QdxJ+YKA3X/AGs11FABRRRQB5D+0WwTwBprMiuBq8RKNnDfupeDgg/ka9dVldQykFSMgg8EV5B+0d/yTzT/APsKx/8AoqWvWLCIwadbQt5mY4kU+YQW4AHOCRn6E0AWKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAryP8AaHdI/AulvIJCi6xEWEb7GI8qXOGwcH3wceleuV5F+0SofwHpisHKnV4gQg+YjypenvQB67RRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAeWfCBPL1rx6nlmMDXZcKc8Dc2OvPT1r1OvLfg84n1LxxdRxNHFLrkuAzEnOSTnPPfPPrXqVABRRRQB518cZ5IfhTqmx0Ad4UYNHv3AyLwOMA98+3HOK2NYdYvhFfStFH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AOG8N/DsWurxeJPEuo3Gs6/jerzHENqxGGESDgemfYEAGu7oooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPLbxJV/aQ053uI3jbRnCRq+WjwWzkdsnn3/CvUq8sujB/wANIWCxLaiX+yHMpjUiQnnG89DwBjvjr2r1OgAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKrXen2t95f2mISeWwZQScAgg/jyBVmgAooooAKKKKACiiigAooooAKKKKACiiigDyn9oOXZ8NgnmxL5l5ENjnDPjJ+X6d/avVq8f/aLhEngOyclsxX6sACgHKsMnPzHr/D689sewUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABXP+O/8AknniX/sFXX/opq6Cuf8AHf8AyTzxL/2Crr/0U1ACeAlC/Dvw0Bn/AJBVseTn/lktdDXP+BP+SeeGv+wVa/8Aopa6CgAooooAKKKKACiiigAooooAKKKKACiiigAooqGO7tpbqa1juInuIArSxK4Lxhs7Sw6jODjPXBoAmoqCa9tbe5t7ea4jjnuWKwRswDSEKWIUd8AE0t3d29haTXd3NHBbwoXklkbCoo6kmgCaim+Ynl+ZvXZjduzxj1zTBcwNLJEJ4zJEVEiBxlC33cjtnt60AS0VUk1TT4ZVilv7VJGAKo0ygkHpgZ71TvfFOgadYyXt1rFlHbREB5POUgEnA6UAa9FYGoeN/C+lTSQ32vWEEscSTMjTDOxsbTgdc5B+hB6Vnf8AC1PA3/QzWP8A30f8KAOworkT8UfA4jWT/hJtPwSQB5nPGO3Udevfn0NI3xS8DKxU+JrDIOOHJH54oA6+iuP/AOFqeBv+hmsf++j/AIUf8LU8Df8AQzWP/fR/woA7CiuP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QAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB4r+0lu/wCER0jEeV+3nL+XnB8tsDd2zzx3x7V7VXi37R2x/DOiwswV3vjtYqMD5CDluoHPb057Y9poAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAPEv2j2kXRPD7RZ8wXrFcDJzt44r22vG/j86xW/hWR/N2rqe4+TJ5b4AH3Wwdp9Dg4NeyUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQByWm6d4otviTqt7c35n8N3NogghZx+6mUjAVewwXye/Gegx1tFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB498eREV8IidisJ1UeYRjIXjPXjp68V7DXl/xZ/5GH4e/9jBB/wChpXqFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRSFVJBIBKnIyOhpaACiiigAooooAKKKKACiiigAooooAKKKKAPL/iz/wAjD8Pf+xgg/wDQ0r1CvL/iz/yMPw9/7GCD/wBDSvUKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAKl7cXcDQfZbE3Su4EpEqoY1/vc9fpVuiigAooooAKKKKACiiigAooooAKKKKACiiigDzH4rxu/iD4fFUZgPEEGSBnHzKf5A/lXp1FFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQA07/MXG3Zg59c8Y/r+lOoooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAopFYMoYZwRnkYP5UtABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQB/9k="}]',true);
        return $result;
    }

    public function getHistoryPicture($data)
    {
        $files  = scandir($this->container->history_directory);
        foreach ($files as $key => $value) {
            $pos = strpos($value, $data['folder']);
            // var_dump($pos);
            if (is_int($pos)) {
                $base64 = $this->container->history_directory . DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR . $data['name'] . ".jpg";
                // $base64 = $this->container->history_directory.$value."/10-437-200.jpg";
                $ack = array(
                    'status' => 'success',
                    'picture' => $base64
                );
                return $ack;
                break;
            }
        }
        // $base64 = "/file/{$data['id']}";
        // $ack = array(
        //     'picture' => $base64
        // );
        $ack = array(
            'status' => 'failed'
        );
        return $ack;
    }

    public function getDraftingPicture($data)
    {
        $base64 = "/file/{$data['id']}";
        $ack = array(
            'picture' => $base64
        );
        return $ack;
        // $sql = "SELECT *
        //     FROM public.file
        // ";
        // $stmt = $this->db->prepare($sql);
        // $stmt->execute();
        // $result = $stmt->fetchAll();
        // return $result;
    }

    public function getStudent()
    {
        $sql = "SELECT *
            FROM public.file
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }
    public function getCrops($data)
    {
        // return $data['id'];
        $sql = "SELECT crop.\"id\",component_id,component.name, crop.x, crop.y, crop.width, crop.height,crop.name AS file_name
            FROM public.crop
            LEFT JOIN component ON component.id = crop.component_id
            WHERE \"fileID\" = :fileID
            ORDER BY crop.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fileID', $data['id'], PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }
    function getFileByCropId($data)
    {
        $sql = "SELECT \"name\"
        FROM public.crop
        WHERE \"id\" = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }

    public function insertFile($data)
    {
        $sql = "INSERT INTO public.file(
            \"ClientName\", \"FileName\", upload_time)
            VALUES ('' , '' , NOW());
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $id = $this->db->lastInsertId();
        return $id;
    }

    public function uploadFactory($data)
    {
        $sql = "UPDATE public.file
            SET \"FileNameFactory\" = :fileName
            WHERE id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fileName', $data['fileName'], PDO::PARAM_STR);
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        return $data['id'];
    }

    public function upload($data)
    {
        $sql = "UPDATE public.file
            SET \"ClientName\" = :clientFileName, \"FileName\" = :fileName, rotate = :rotate
            WHERE id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':clientFileName', $data['clientFileName'], PDO::PARAM_STR);
        $stmt->bindValue(':fileName', $data['fileName'], PDO::PARAM_STR);
        $stmt->bindValue(':id', $data['id']);
        $stmt->bindValue(':rotate', $data['rotate']);
        $stmt->execute();
        $sql = "DELETE FROM public.crop
            WHERE\"fileID\" = :id
        ";
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        return $data['id'];
    }
    public function getFileInfomation($data)
    {
        $sql = "SELECT file.id,to_char(file.upload_time, 'YYYY年MM月DD日') upload_time,file.order_name,process.process_id,file.order_name
            FROM public.file
            LEFT JOIN (
                SELECT MAX(process.id) process_id,file_id
                FROM process
                GROUP BY file_id
            ) process ON process.file_id = file.id
            WHERE file.id = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getState($data)
    {
        // $sql = "SELECT setting_progress.id,
        //             CASE 
        //                 WHEN progress.later IS false THEN '不需要' || setting_progress.name 
        //             WHEN MAX(progress.update_time) IS NULL THEN '待' || setting_progress.name 
        //             ELSE '已' || setting_progress.name 
        //         END progress,setting_progress.url
        //             ,MAX(progress.update_time) update_time,module.name module_name,module.id module_id,module.color module_color,progress.later
        //     FROM setting.progress setting_progress
        //     LEFT JOIN setting.module ON module.id = setting_progress.module_id
        //     LEFT JOIN(
        //         SELECT progress.progress_id,progress.update_time,progress.later
        //         FROM file
        //         LEFT JOIN progress ON file.id = progress.file_id
        //         WHERE file.id = :file_id
        //     )progress ON setting_progress.id = progress.progress_id
        //     GROUP BY setting_progress.id,module.name,setting_progress.name,setting_progress.url,module.id,module.color,progress.later
        //     ORDER BY setting_progress.id


        // ";
        $values = [
            'user_id' => 0,
            'id' => 0
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $sql = "SELECT allselect.*,user_module.module_name IS NULL redirect
            FROM (
            SELECT ROW_NUMBER() OVER (PARTITION BY setting_progress.id ORDER BY MAX(progress.update_time)) as RowNum,setting_progress.id,
                    CASE 
                        WHEN progress.later IS false THEN '不需要' || setting_progress.name 
                    WHEN MAX(progress.update_time) IS NULL THEN '待' || setting_progress.name 
                    ELSE '已' || setting_progress.name 
                END progress,setting_progress.url
                    ,MAX(progress.update_time) update_time,module.name module_name,module.id module_id,module.color module_color,progress.later
                FROM setting.progress setting_progress
                LEFT JOIN setting.module ON module.id = setting_progress.module_id
                LEFT JOIN(
                    SELECT progress.progress_id,progress.update_time,progress.later
                    FROM file
                    LEFT JOIN progress ON file.id = progress.file_id
                    WHERE file.id = :id
                )progress ON setting_progress.id = progress.progress_id
                GROUP BY setting_progress.id,module.name,setting_progress.name,setting_progress.url,module.id,module.color,progress.later
                ORDER BY setting_progress.id
            ) AS allselect
            LEFT JOIN(
                SELECT STRING_AGG(user_module.module_name, ',') module_name
                FROM 
                (
                    SELECT system.user.id user_id, system.user.uid user_uid, system.user.name user_name, 
                        system.user.email user_email, setting.module.id module_id, setting.module.name module_name
                    FROM system.user
                    LEFT JOIN system.user_modal ON system.user_modal.uid = system.user.id
                    LEFT JOIN setting.module ON setting.module.id = system.user_modal.module_id OR user_modal.module_id = 7
                    ORDER BY setting.module.id
                )  user_module
                WHERE user_module.user_id = :user_id
                GROUP BY user_module.user_id, user_module.user_name, user_module.user_email
            )user_module ON user_module.module_name like '%'|| allselect.module_name ||'%'
            WHERE  RowNum = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /* 
SELECT TOP 1000 
    [MOCMA].[MA002] 製程代號
    ,[MOCMA].[MA003] 加工廠商
    ,PURMA.MA002 廠商簡稱
    ,[MOCMA].[MA012] 生效日
    ,[MOCMA].[MA010] 幣別
    ,[MOCMA].[MA005] 單價
FROM [MIL].[dbo].[MOCMA]
LEFT JOIN [MIL].[dbo].[PURMA] ON [PURMA].MA001 = MOCMA.MA003
ORDER BY MOCMA.MA012 DESC
 */
    public function getStation($data)
    {
        $sql = "SELECT module.id,module.\"name\"
            FROM setting.module
            WHERE \"name\" != '業務' AND \"name\" != :module_name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->execute();
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($modules as $key => $module) {
            $sql = "SELECT process_mapping.crop_id,comment_process.comment \"註記\",comment_process.material \"追加材質成本\",comment_process.stuff \"追加材料成本\",comment_process.process \"追加製程成本\",comment_process.outsourcer_cost \"追加外包成本\"                FROM \"comment_process\"
                LEFT JOIN process_mapping ON process_mapping.id = comment_process.process_mapping_id
                LEFT JOIN setting.module ON module.id = comment_process.module_id
                LEFT JOIN process ON process_mapping.process_id = process.id
                WHERE process.component_id = (
                    SELECT component_id
                    FROM crop
                    WHERE \"fileID\" = :file_id
                    GROUP BY component_id
                ) AND \"comment_process\".module_id = :module_id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':file_id', $data['id']);
            $stmt->bindValue(':module_id', $module['id']);
            $stmt->execute();
            $modules[$key]['station'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $modules;
    }

    public function checkCrop($file_id)
    {
        $sql = "SELECT *
            FROM public.crop
            LEFT JOIN public.file ON crop.name LIKE '%' || file.\"FileName\" || '%' AND file.id = crop.\"fileID\"
            WHERE \"fileID\" = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $file_id);
        $stmt->execute();
        if (count($stmt->fetchAll(PDO::FETCH_ASSOC)) > 0)
            return true;
        return false;
    }


    public function postProcessComment($data)
    {
        $sql = "SELECT process_id, crop_id, confidence, id
            FROM public.process_mapping
            WHERE process_id=:process_id AND crop_id=:crop_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id']);
        $stmt->bindValue(':crop_id', $data['crop_id']);
        $stmt->execute();
        $row =  $stmt->fetchAll();
        $mapping_id = $row[0]['id'];

        $sql = "SELECT id, name, color
        FROM setting.module
        WHERE name = :name;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $data['module_name']);
        $stmt->execute();
        $row =  $stmt->fetchAll();
        $module_id = $row[0]['id'];



        $sql = "INSERT INTO public.comment_process (process_mapping_id, module_id, comment, update_time, process)
        SELECT :process_mapping_id, :module_id, :comment, NOW(), :process
        WHERE NOT EXISTS(
            SELECT *
            FROM comment_process
            WHERE process_mapping_id = :process_mapping_id AND module_id = :module_id
        )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_mapping_id', $mapping_id);
        $stmt->bindValue(':module_id', $module_id);
        $stmt->bindValue(':comment', $data['comment']);
        $stmt->bindValue(':process', $data['process']);
        $stmt->execute();

        $sql = "UPDATE public.comment_process
        SET   comment=:comment, update_time = NOW(),  process=:process
        WHERE process_mapping_id=:process_mapping_id AND module_id=:module_id;
        
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_mapping_id', $mapping_id);
        $stmt->bindValue(':module_id', $module_id);
        $stmt->bindValue(':comment', $data['comment']);
        $stmt->bindValue(':process', $data['process']);
        $stmt->execute();

        return $data['process'];
    }

    public function deleteProcessComment($data)
    {
        $sql = "DELETE FROM public.comment_process
            WHERE process_mapping_id = (
                SELECT process_mapping.id
                FROM process_mapping
                WHERE process_id = :process_id AND crop_id = :crop_id
            ) AND module_id = (
                SELECT module.id
                FROM setting.module
                WHERE \"name\" = :module_name
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id']);
        $stmt->bindValue(':crop_id', $data['crop_id']);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->execute();
    }

    public function getCommentComponent($data)
    {
        $sql = "SELECT module.name module_name,comment_process.comment,comment_process.material,comment_process.stuff
            FROM \"comment_process\"
            LEFT JOIN process_mapping ON process_mapping.id = comment.process_mapping_id
            LEFT JOIN setting.module ON module.id = comment_process.module_id
            WHERE process_mapping.process_id = :process_id AND process_mapping.crop_id = :crop_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id'], PDO::PARAM_STR);
        $stmt->bindValue(':crop_id', $data['crop_id'], PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function postCommentComponent($data)
    {
        $row = [
            'process_id' => 0,
            'crop_id' => 0,
            'confidence' => 0,
            'module_name' => '',
            'comment' => null,
            'material' => null,
            'stuff' => null,
            'process' => null,
            'outsourcer_comment' => null,
            'outsourcer_vendor' => null,
            'outsourcer_cost' => null,
        ];
        foreach ($row as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $row[$key];
            }
        }
        $sql = "INSERT INTO public.process_mapping (process_id,crop_id,confidence)
        SELECT :process_id,:crop_id,:confidence
        WHERE NOT EXISTS(
            SELECT *
            FROM process_mapping
            WHERE process_id = :process_id AND crop_id = :crop_id
        )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id']);
        $stmt->bindValue(':crop_id', $data['crop_id']);
        $stmt->bindValue(':confidence', $data['confidence']);
        $stmt->execute();

        $sql = "INSERT INTO public.comment_process (process_mapping_id,comment,module_id,material,stuff,process,outsourcer_comment,outsourcer_cost,outsourcer_vendor)

            SELECT (
                SELECT process_mapping.id
                FROM process_mapping
                WHERE process_id = :process_id AND crop_id = :crop_id
            ),:comment,(
                SELECT module.id
                FROM setting.module
                WHERE \"name\" = :module_name
            ),:material,:stuff,:process,:outsourcer_comment,:outsourcer_cost,:outsourcer_vendor
            WHERE NOT EXISTS(
                SELECT *
                FROM public.comment_process
                LEFT JOIN public.process_mapping ON comment_process.process_mapping_id = process_mapping.id
                LEFT JOIN setting.module ON comment_process.module_id = module.id
                WHERE process_id = :process_id AND crop_id = :crop_id AND module.\"name\" = :module_name
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id']);
        $stmt->bindValue(':crop_id', $data['crop_id']);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->bindValue(':comment', $data['comment']);
        $stmt->bindValue(':material', $data['material']);
        $stmt->bindValue(':stuff', $data['stuff']);
        $stmt->bindValue(':process', $data['process']);
        $stmt->bindValue(':outsourcer_comment', @$data['outsourcer_comment']);
        $stmt->bindValue(':outsourcer_vendor', @$data['outsourcer_vendor']);
        $stmt->bindValue(':outsourcer_cost', @$data['outsourcer_cost']);

        $stmt->execute();

        $sql = "UPDATE public.comment_process
            SET comment = :comment,material=:material,stuff=:stuff,process=:process,outsourcer_comment = :outsourcer_comment,outsourcer_cost=:outsourcer_cost,outsourcer_vendor=:outsourcer_vendor , update_date=NOW()
            WHERE process_mapping_id = (
                SELECT process_mapping.id
                FROM process_mapping
                WHERE process_id = :process_id AND crop_id = :crop_id
            ) AND module_id = (
                SELECT module.id
                FROM setting.module
                WHERE \"name\" = :module_name
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id']);
        $stmt->bindValue(':crop_id', $data['crop_id']);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->bindValue(':comment', $data['comment']);
        $stmt->bindValue(':material', $data['material']);
        $stmt->bindValue(':stuff', $data['stuff']);
        $stmt->bindValue(':process', $data['process']);
        $stmt->bindValue(':outsourcer_comment', @$data['outsourcer_comment']);
        $stmt->bindValue(':outsourcer_vendor', @$data['outsourcer_vendor']);
        $stmt->bindValue(':outsourcer_cost', @$data['outsourcer_cost']);

        $stmt->execute();
    }
    public function deleteCommentComponent($data)
    {
        $sql = "DELETE FROM public.comment_process
            WHERE process_mapping_id = (
                SELECT process_mapping.id
                FROM process_mapping
                WHERE process_id = :process_id AND crop_id = :crop_id
            ) AND module_id = (
                SELECT module.id
                FROM setting.module
                WHERE \"name\" = :module_name
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':process_id', $data['process_id']);
        $stmt->bindValue(':crop_id', $data['crop_id']);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->execute();
    }
    public function getComment($data)
    {
        $sql = "SELECT module.name module_name,comment.comment
            FROM \"comment\"
            LEFT JOIN file_mapping ON file_mapping.id = comment.file_mapping_id
            LEFT JOIN setting.module ON module.id = comment.module_id
            WHERE file_mapping.file_id = :file_id AND file_mapping.file_id_destination = :file_id_dest
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id'], PDO::PARAM_STR);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest'], PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function patchComment($data)
    {
        $sql = "UPDATE public.file_mapping
            SET update_time = NOW()
            WHERE file_id = :file_id AND file_id_destination = :file_id_dest
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->execute();
    }
    public function postComment($data)
    {
        $sql = "INSERT INTO public.file_mapping (file_id,file_id_destination,confidence)
            SELECT :file_id,:file_id_dest,:confidence
            WHERE NOT EXISTS(
                SELECT *
                FROM file_mapping
                WHERE file_id = :file_id AND file_id_destination = :file_id_dest
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->bindValue(':confidence', $data['confidence']);
        $stmt->execute();


        $sql = "INSERT INTO public.comment (file_mapping_id,comment,module_id)
            SELECT (
                SELECT file_mapping.id
                FROM file_mapping
                WHERE file_id = :file_id AND file_id_destination = :file_id_dest
            ),:comment,(
                SELECT module.id
                FROM setting.module
                WHERE \"name\" = :module_name
            )
            WHERE NOT EXISTS(
                SELECT *
                FROM public.comment
                LEFT JOIN public.file_mapping ON comment.file_mapping_id = file_mapping.id
                LEFT JOIN setting.module ON comment.module_id = module.id
                WHERE file_id = :file_id AND file_id_destination = :file_id_dest AND module.\"name\" = :module_name
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->bindValue(':comment', $data['comment']);
        $stmt->execute();

        $sql = "UPDATE public.comment
            SET comment = :comment
            WHERE file_mapping_id = (
                SELECT file_mapping.id
                FROM file_mapping
                WHERE file_id = :file_id AND file_id_destination = :file_id_dest
            ) AND module_id = (
                SELECT module.id
                FROM setting.module
                WHERE \"name\" = :module_name
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->bindValue(':comment', $data['comment']);
        $stmt->execute();
    }
    public function deleteComment($data)
    {
        $sql = "DELETE FROM public.comment
            WHERE file_mapping_id = (
                SELECT file_mapping.id
                FROM file_mapping
                WHERE file_id = :file_id AND file_id_destination = :file_id_dest
            ) AND module_id = (
                SELECT module.id
                FROM setting.module
                WHERE \"name\" = :module_name
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->bindValue(':module_name', $data['module_name']);
        $stmt->execute();
    }

    public function setCrop($file_id, $orderSerial, $Crop_file, $Bounding_boxes)
    {
        $sql = "DELETE FROM public.crop
            WHERE \"fileID\" = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $file_id);
        $stmt->execute();

        $sql = 'INSERT INTO public.crop( "fileID", name,x,y,width,height) VALUES ';
        foreach ($Crop_file as $index => $value) {
            $values = [
                null,/* x */
                null,/* y */
                null,/* width */
                null,/* height */
            ];
            // var_dump($Bounding_boxes);
            if (isset($Bounding_boxes->$value)) {
                foreach ($Bounding_boxes->$value as $key => $box) {
                    $values[$key] = $box;
                }
            }
            // var_dump($values);
            
            $sql .= " ('{$file_id}','{$value}',{$values[0]},{$values[1]},{$values[2]},{$values[3]}),";
        }
        $sql =  rtrim($sql, ",");
        $sql .= " RETURNING id ";
        // var_dump($sql);
        // return $sql;
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    public function delete($data)
    {
        $sql = "UPDATE process
            set status_id = 3
            WHERE \"id\"=:id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();
    }
    function urlify($key, $val) {
        return 'aValues[' . urlencode($key) . ']=' . urlencode($val);
    }
    function getFilename($data)
    {
        $sql = "SELECT \"FileName\"
        FROM public.file
        WHERE id != :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $result;
    }
    function getCropname($data)
    {
        $sql = "SELECT \"name\"
            FROM public.crop
            WHERE component_id != :id;
        ";
        // $sql = "SELECT \"FileName\" \"name\"
        //     FROM public.crop
        //     LEFT JOIN file ON file.id = crop.\"fileID\"
        //     WHERE component_id != :id
        //     GROUP BY \"FileName\";
        // ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $result;
    }

    function getFileCurrentId($data)
    {
        $sql = "SELECT currval('file_id_seq'::regclass) current_id,to_char(NOW(), 'YYYY年MM月DD日 HH24:MI:SS') now_time
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    function getDOWOrder($data)
    {
        $sql = "SELECT to_char( created, 'DY'::TEXT) \"day\",created \"date\", COALESCE(orders.count,0) count
            FROM(
                SELECT ( NOW() + (s::TEXT || ' day')::INTERVAL )::DATE AS created
                FROM generate_series(-20, 20, 1) AS s
            ) compras
            LEFT JOIN(
                SELECT COUNT(*),upload_time::date upload_time
                FROM public.file
                GROUP BY upload_time::date
            )orders ON orders.upload_time = compras.created
            WHERE created BETWEEN
                NOW()::DATE-EXTRACT(DOW FROM NOW())::INTEGER-5
                AND NOW()::DATE
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function getHistoryOrder($data)
    {
        $sql = "WITH history_order AS(
            SELECT module.name,COUNT(*)
            FROM public.file
            LEFT JOIN (
                SELECT progress.file_id,MAX(progress.progress_id)progress_id
                FROM progress
                GROUP BY progress.file_id
            )progress ON progress.file_id = file.id
            LEFT JOIN setting.progress setting_progress ON setting_progress.id = progress.progress_id
            LEFT JOIN setting.module ON module.id = setting_progress.module_id
            WHERE module.name IS NOT NULL
            GROUP BY module.name
        )";

        $sql .= "SELECT *
            FROM history_order
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function getDailyOrder($data)
    {
        $sql = "WITH total as (
            SELECT COUNT(CASE WHEN progress.progress_id IS NOT NULL THEN TRUE END) total
            FROM setting.progress setting_progress
            LEFT JOIN (
                SELECT file.id,progress.progress_id
                FROM (
                    SELECT progress.file_id,MAX(progress.progress_id) progress_id
                    FROM progress
                    GROUP BY progress.file_id
                )progress
                LEFT JOIN file ON file.id = progress.file_id
                WHERE file.upload_time::date = NOW()::date
            )progress ON setting_progress.id = progress.progress_id
        )";
        $sql .= "SELECT '待' || module.name || '處理' \"name\",COUNT(CASE WHEN progress.progress_id IS NOT NULL THEN TRUE END)::text || '／' || (SELECT total FROM total)::text count
            FROM setting.module
            LEFT JOIN setting.progress setting_progress ON module.id = setting_progress.module_id
            LEFT JOIN (
                SELECT file.id,progress.progress_id
                FROM (
                    SELECT progress.file_id,MAX(progress.progress_id)progress_id
                    FROM progress
                    GROUP BY progress.file_id
                )progress
                LEFT JOIN setting.progress setting_progress ON setting_progress.id = progress.progress_id
                LEFT JOIN file ON file.id = progress.file_id
                WHERE file.upload_time::date = NOW()::date AND setting_progress.name != '完成報價'
            )progress ON setting_progress.id = progress.progress_id
            GROUP BY module.name
            UNION ALL(
                SELECT '已' || setting_progress.name \"name\",COUNT(CASE WHEN progress.progress_id IS NOT NULL THEN TRUE END)::text || '／' || (SELECT total FROM total)::text count
                FROM setting.progress setting_progress
                LEFT JOIN (
                    SELECT file.id,progress.progress_id
                    FROM (
                        SELECT progress.file_id,MAX(progress.progress_id) progress_id
                        FROM progress
                        GROUP BY progress.file_id
                    )progress
                    LEFT JOIN file ON file.id = progress.file_id
                    WHERE file.upload_time::date = NOW()::date
                )progress ON setting_progress.id = progress.progress_id
                WHERE setting_progress.name = '完成報價'
                GROUP BY setting_progress.name
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function getAllOrder($data)
    {

        $sql = "SELECT a.*
        FROM system.authority
        LEFT JOIN(
            SELECT *
        FROM (
            (
                SELECT setting_progress.id,'待' || setting_progress.name \"name\",COUNT(CASE WHEN progress.progress_id IS NOT NULL THEN TRUE END),module.name module_name,module.id module_id,module.color module_color
                FROM setting.progress setting_progress
                LEFT JOIN setting.module ON module.id = setting_progress.module_id
                LEFT JOIN (
                    SELECT file.id,progress.progress_id
                    FROM (
                        SELECT progress.file_id,MAX(progress.progress_id) progress_id
                        FROM progress
                        GROUP BY progress.file_id
                    )progress
                    LEFT JOIN file ON file.id = progress.file_id
                )progress ON setting_progress.id = progress.progress_id
                WHERE setting_progress.name != '完成報價'
                GROUP BY setting_progress.id,setting_progress.name,module.name,module.id,module.color
                ORDER BY setting_progress.id
            )
            UNION ALL(
                SELECT setting_progress.id,'已' || setting_progress.name \"name\",COUNT(CASE WHEN progress.progress_id IS NOT NULL THEN TRUE END),module.name module_name,module.id module_id,module.color module_color
                FROM setting.progress setting_progress
                LEFT JOIN setting.module ON module.id = setting_progress.module_id
                LEFT JOIN (
                    SELECT file.id,progress.progress_id
                    FROM (
                        SELECT progress.file_id,MAX(progress.progress_id) progress_id
                        FROM progress
                        GROUP BY progress.file_id
                    )progress
                    LEFT JOIN file ON file.id = progress.file_id
                )progress ON setting_progress.id = progress.progress_id
                WHERE setting_progress.name = '完成報價'
                GROUP BY setting_progress.id,setting_progress.name,module.name,module.id,module.color
            )
        )a
        
        )as a on a.id = authority.progress_id
        WHERE authority.module_id=:module_id
        ORDER BY a.id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':module_id', $data['module_id']);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function getAllOrderCollapse($data)
    {

        $sql = "SELECT a.module_name,a.module_id, (a.module_id-1)%5+1 AS module_color, JSON_AGG(JSON_BUILD_OBJECT('name',a.name,'count',a.count) ORDER BY a.id ASC) AS \"names\", module_id!=:module_id AS collapse_condition
            FROM (
                (
                    SELECT setting_progress.id,'待' || setting_progress.name \"name\",COUNT(CASE WHEN progress.progress_id IS NOT NULL THEN TRUE END),module.name module_name,module.id module_id,module.color module_color
                    FROM setting.progress setting_progress
                    LEFT JOIN setting.module ON module.id = setting_progress.module_id
                    LEFT JOIN (
                        SELECT file.id,progress.progress_id
                        FROM (
                            SELECT progress.file_id,MAX(progress.progress_id) progress_id
                            FROM progress
                            GROUP BY progress.file_id
                        )progress
                        LEFT JOIN file ON file.id = progress.file_id
                    )progress ON setting_progress.id = progress.progress_id
                    GROUP BY setting_progress.id,setting_progress.name,module.name,module.id,module.color
                    ORDER BY setting_progress.id
                )
            )a
            -- WHERE authority.module_id = :module_id
            GROUP BY a.module_name,a.module_id
            ORDER BY a.module_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':module_id', $data['module_id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $key => $row) {
            foreach ($row as $row_key => $value) {
                if (isJson($value)) {
                    $result[$key][$row_key] = json_decode($value);
                }
            }
        }
        return $result;
    }





    function setProgress($file_id, $progress_id)
    {
        $sql = "INSERT INTO progress(file_id,progress_id)
            VALUES(:file_id,:progress_id)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $file_id);
        $stmt->bindValue(':progress_id', $progress_id);
        $stmt->execute();

        if ($progress_id === 11) {
            $sql = "INSERT INTO progress(file_id,progress_id,later)
                SELECT :file_id, progress.progress_id,false
                FROM (
                    SELECT setting_progress.id progress_id
                    FROM setting.progress setting_progress
                    LEFT JOIN (
                        SELECT *
                        FROM progress
                        WHERE progress.file_id = :file_id
                    )progress ON setting_progress.id = progress.progress_id
                    WHERE file_id IS NULL AND setting_progress.id != 11
                )progress
                ORDER BY progress.progress_id ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':file_id', $file_id);
            $stmt->execute();
        }

        $module_arr = $this->getUser(array('id' => $_SESSION['id']));
        $sql = "SELECT setting_progress.id,setting_progress.url , setting_progress.module_id
            FROM setting.progress setting_progress
            WHERE id = :progress_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':progress_id', $progress_id + 1);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $tmpbool = false;
        foreach ($module_arr as $key => $value) {
            if ($value['module_id'] == @$row[0]['module_id'] || $value['module_id'] == 7) {
                $tmpbool = true;
            }
        }

        if ($tmpbool) {
            return $row;
        } else {
            $row = array(
                "id" => 0,
                "url" => "/"
            );
            return $row;
        }
    }
    function getFinish($data)
    {
        $sql = "SELECT setting_progress.id,setting_progress.url
            FROM setting.progress setting_progress
            WHERE url = '/finish'
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function getProgress($data)
    {
        $sql = "SELECT setting_progress.id
            FROM setting.progress setting_progress
            WHERE :url LIKE '%' || setting_progress.url || '?%'
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':url', $data['url']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function getProgresses($data)
    {
        $sql = "SELECT setting_progress.id,setting_progress.id || '.' || module.name || '-' ||setting_progress.name progress
                    ,MAX(progress.update_time) update_time,JSON_AGG(
                    JSON_BUILD_OBJECT('update_time',to_char(progress.update_time, 'YYYY年MM月DD日 HH24:MI:SS')) ORDER BY progress.update_time
                ) progresses,module.id module_id, module.name module_name
            FROM setting.progress setting_progress
            LEFT JOIN setting.module ON module.id = setting_progress.module_id
            LEFT JOIN(
            SELECT progress.progress_id,progress.update_time
            FROM file
            LEFT JOIN progress ON file.id = progress.file_id
            WHERE file.id = :file_id
            )progress ON setting_progress.id = progress.progress_id
            WHERE setting_progress.module_id != 0
            GROUP BY setting_progress.id,module.name,setting_progress.name,module.id
            ORDER BY setting_progress.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getsameCustomerFiles($data)
    {
        $business = new Business($this->db);
        $result = $business->getCustomerCodes();
        $customer_code = json_encode($result);

        $values = [];
        $query_count = "";
        $query = "";
        if ($data['module_id'] != 0) {
            $query = "WHERE module_id = :module_id";
            $values = ["module_id" => $data['module_id']];
        }
        $sql = "SELECT progress.id
            FROM setting.progress
            {$query}
            ORDER BY progress.id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $progresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($progresses as $key => $progress) {
            $query_count .= " WHEN COUNT(CASE WHEN progress.progress_id = {$progress['id']} THEN TRUE END) = 0 THEN {$progress['id']}";
        }
        if (strlen($query_count) != 0) {
            $query_count = ",CASE {$query_count} END progress_id";
        }
        // if (isset($data['id'])) {
        //     if (!empty($data['id'])) {
        //         $query = "WHERE file.id = :file_id";
        //         $values = ["file_id" => $data['id']];
        //     }
        // }


        $sql = "SELECT file.id,file.id file_id_destination,file.\"ClientName\", file.\"FileName\",to_char(file.upload_time, 'YYYY年MM月DD日 HH24:MI:SS') upload_time, file.order_serial, file.order_name, CASE WHEN setting_progress.name != '完成報價' THEN '待' || setting_progress.name ELSE '已' || setting_progress.name END progress, setting_progress.url,customer_code.name AS customer_code
            FROM public.file
            LEFT JOIN (
                SELECT file_id {$query_count}
                FROM progress
                WHERE progress.later IS TRUE
                GROUP BY file_id
            )progress ON progress.file_id = file.id
            LEFT JOIN setting.progress setting_progress ON setting_progress.id = COALESCE(progress.progress_id,1)
            LEFT JOIN setting.module ON module.id = setting_progress.module_id
            LEFT JOIN (
                SELECT \"客戶代號\" AS code ,\"客戶名稱\" AS name
                    FROM json_to_recordset(
                        '{$customer_code}'
                    ) as setting_customer_code(\"客戶代號\" text,\"客戶名稱\" text)
            ) AS customer_code  ON trim(file.customer) = trim(customer_code.code)
            WHERE file.customer IN(
                SELECT  customer
                FROM public.file
                WHERE id=:id 
                )
            ORDER BY file.id DESC;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getFilesUnordered($data)
    {
        $query = "";
        if ($data['starttime'] != '' || $data['endtime'] != '') {
            // AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
            if ($data['starttime'] == '') {
                $starttime = 'GETDATE()';
            } else {
                $starttime = "CONVERT(DATETIME, '{$data['starttime']}')";
            }
            if ($data['endtime'] == '') {
                $endtime = 'GETDATE()';
            } else {
                $endtime = "CONVERT(DATETIME, '{$data['endtime']}')";
            }

            if ($query != "") {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
            }
            $query .= "  ([COPTA].[TA003] BETWEEN CONVERT(NVARCHAR,{$starttime},112) AND CONVERT(NVARCHAR,{$endtime},112)) ";
        }
        if (!empty('order_name')) {
            if ($query != "") {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
            }
            $query .= " RTRIM(LTRIM([COPTB].[TB201])) LIKE '%{$data['order_name']}%'";
        }
        if (!empty($data['order_id'])) {
            if ($query != "") {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
            }
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
                ['sql' => "SELECT TOP 1000
                    null id,
                    null file_id_destination,
                    '' \"ClientName\", 
                    '' \"FileName\",
                    COALESCE([COPTB].[CREATE_DATE],[COPTA].[TA003]) upload_time, 
                    '' order_serial, 
                    [COPTB].[TB201] order_name,
                    [COPTA].[TA004] customer,
                    STUFF((
                        SELECT TOP 1
                            t.TB001,t.TB002,t.TB003
                        FROM [MIL].[dbo].[COPTB] t
                        WHERE t.TB001 = [COPTB].[TB001] AND t.TB002 = [COPTB].[TB002] AND t.TB003 = [COPTB].[TB003]
                        FOR XML PATH),1,0,''
                    )fk
                    FROM [MIL].[dbo].[COPTA]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTB.TB204
                    {$query}
                    ORDER BY [COPTA].[TA003] DESC
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        if (isset($result)) {
            foreach ($result as $key_result => $value) {
                $tmpvalue = $value['fk'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['fk'] = $tmpArrs;
                    goto Endquotation;
                }
                foreach ($xml as $t) {
                    $tmpArr = [];
                    foreach ($t as $a => $b) {
                        $tmpArr[$a] = '';
                        foreach ((array)$b as $c => $d) {
                            $tmpArr[$a] = $d;
                        }
                    }
                    $tmpArrs = $tmpArr;
                }
                $result[$key_result]['fk'] = $tmpArrs;
                Endquotation:
            }
        }
        $result = json_encode($result);
        return $result;
    }

    function getFiles($data)
    {
        $files = $this->getFilesUnordered($data);
        // var_dump($files);
        // return $files;
        $business = new Business($this->db);
        $result = $business->getCustomerCodes();
        $customer_code = json_encode($result);

        $values = [];
        $query_count = "";
        $query_count_tip = "";
        $query = "";
        $progress_query = "";
        $endprogress = "";
        if ($data['module_id'] != 0) {
            if ($data['module_id'] != 1 && $data['module_id'] != 7) {
                if (array_key_exists('finish', $data)) {
                    if ($data['finish'] != 'true') {
                        $progress_query = " HAVING COUNT(CASE WHEN progress.progress_id = 4 THEN TRUE END) > 0 ";
                    }
                }
                $query = "WHERE module_id = :module_id";
                $values = ["module_id" => $data['module_id']];
            } else {
                if (array_key_exists('finish', $data)) {
                    if ($data['finish'] != 'true') {
                        $progress_query = " HAVING COUNT(CASE WHEN progress.progress_id = 11 THEN TRUE END) = 0 ";
                    }
                }
            }
        }
        $sql = "SELECT progress.id,module_id = 1 as business,url
            FROM setting.progress
            {$query}
            ORDER BY progress.id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $progresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $query = "";
        $values = [];
        $end_progress_id = 0;
        $end_progress_url = "";
        $end_progress_tip = "_tip";
        $last_update_time = "";
        foreach ($progresses as $key => $progress) {
            $last_update_time .= " WHEN progress.progress_id = {$progress['id']} THEN progress.update_time ";
            $end_progress_id = $progress['id'];
            $end_progress_url = $progress['url'];
        }
        $last_update_time = ",MAX(CASE {$last_update_time} END) update_time";
        if ($data['module_id'] == 1 || $data['module_id'] == 7) {
            $end_progress_id = 11;
            $end_progress_tip = "";
            $end_progress_url = "CASE WHEN progress.progress_id>4 THEN '{$progress['url']}' ELSE setting_progress{$end_progress_tip}.url END url";
        } else {
            $end_progress_url = "setting_progress{$end_progress_tip}.url";
        }
        if (count($progresses) > 0) {
            $finish_progress = implode(" OR ", array_map(function ($progress) {
                return "progress.progress_id = {$progress['id']}";
            }, $progresses));
            $count_progresses = count($progresses);
            $query_count = ", CASE WHEN COUNT(CASE WHEN {$finish_progress} THEN TRUE END) >= {$count_progresses}  THEN 12 ";
            $query_count_tip = ",CASE ";
            foreach ($progresses as $key => $progress) {
                $query_count_tip .= " WHEN COUNT(CASE WHEN progress.progress_id = {$progress['id']} AND later IS NOT NULL THEN TRUE END) = 0 THEN {$progress['id']}";
                $query_count .= " WHEN COUNT(CASE WHEN progress.progress_id = {$progress['id']} AND later IS NOT NULL THEN TRUE END) = 0 THEN {$progress['id']}";
            }
            if (array_key_exists('finish', $data)) {
                if ($data['finish'] == 'true') {
                    if ($data['module_id'] != 1 && $data['module_id'] != 7)
                        $query_count .= " WHEN COUNT(CASE WHEN progress.progress_id = {$end_progress_id} THEN TRUE END) = 0  THEN {$end_progress_id} ";
                }
            }
            $query_count .= " END progress_id";
            $query_count_tip .= " ELSE {$end_progress_id} END progress_id_dest";
        }
        if (isset($data['id'])) {
            if (!empty($data['id'])) {
                $query = "WHERE file.id = :file_id";
                $values = ["file_id" => $data['id']];
            }
        }
        if ($data['starttime'] != '' || $data['starttime'] != '') {
            // AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
            if ($data['starttime'] == '') {
                $starttime = 'NOW()';
            } else {
                $starttime = "'{$data['starttime']}'::timestamp";
            }
            if ($data['endtime'] == '') {
                $endtime = 'NOW()';
            } else {
                $endtime = "'{$data['endtime']}'::timestamp";
            }

            if ($query != "") {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
            }
            $query .= "  file.upload_time BETWEEN to_char({$starttime},'YYYYMMDD') AND to_char({$endtime},'YYYYMMDD') ";
        }
        if (array_key_exists('order_name', $data)) {
            if ($query != "") {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
            }
            $values['order_name'] = $data['order_name'];
            $query .= " ( file.order_name LIKE '%' || :order_name ||'%' OR 
                        file.id::varchar  LIKE '%' || :order_name ||'%'  OR
                        tmpfile.tmpid  LIKE '%' || :order_name ||'%'  OR
                        customer_code.name LIKE '%' || :order_name ||'%' ) ";
        }

        if (array_key_exists('finish', $data)) {
            if ($query != "") {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
            }
            $query .= "  (progress.progress_id ";
            if ($data['finish'] == 'true') {
                $query .= "= 12 OR (file.fk IS NOT NULL) {$endprogress})";
            } else {
                $query .= "!= 12 AND (file.fk IS NULL))";
            }
        }
        $sql = "WITH file_outer AS (
                SELECT file.id,file.id file_id_destination,file.\"ClientName\", file.\"FileName\",file.upload_time, file.order_serial, file.order_name,file.customer,file.fk
                FROM json_to_recordset('{$files}')file(id integer,file_id_destination integer,\"ClientName\" text,\"FileName\" text,upload_time text,order_serial text,order_name text,customer text,fk jsonb)
            )
            SELECT file.id,file.id file_id_destination,file.\"ClientName\", file.\"FileName\",file.upload_time, file.order_serial, file.order_name, 
                CASE 
                    WHEN file.id IS NOT NULL AND file.fk IS NULL AND progress.progress_id = 12 THEN module{$end_progress_tip}.name || '：已' || setting_progress{$end_progress_tip}.name 
                    WHEN progress.progress_id_dest != 12 AND file.fk IS NULL THEN module{$end_progress_tip}.name || '：待' || setting_progress{$end_progress_tip}.name
                    ELSE module_tip.name || '：已' || setting_progress_tip.name 
                END progress, {$end_progress_url},customer_code.name AS customer_code,
                tmpfile.tmpid,file.fk,to_char(progress.update_time,'YYYY-MM-DD HH:MI:SS')update_time
            FROM (
                SELECT file.id,file.id file_id_destination,file.\"ClientName\", file.\"FileName\",COALESCE(file.upload_time,file_outer.upload_time) AS upload_time, file.order_serial, file_outer.order_name,COALESCE(file.customer,file_outer.customer) AS customer,file_outer.fk
                FROM file_outer
                LEFT JOIN (
                    SELECT file.id,file.id file_id_destination,file.\"ClientName\", file.\"FileName\",to_char(file.upload_time,'YYYYMMDD') upload_time, file.order_serial, file.order_name,file.customer,file.fk
                    FROM file
                ) file ON file_outer.fk = file.fk
                UNION (
                    SELECT file.id,file.id file_id_destination,file.\"ClientName\", file.\"FileName\",to_char(file.upload_time,'YYYYMMDD') upload_time, file.order_serial, file.order_name,file.customer,file.fk
                    FROM file
                )
            )file
            LEFT JOIN (
                SELECT file_id {$query_count} {$query_count_tip} {$last_update_time}
                FROM (
                    SELECT file_id,progress_id,COALESCE(bool_or(LATER),TRUE) LATER,MAX(update_time) update_time
                    FROM progress
                    GROUP BY file_id,progress_id
                )progress
                GROUP BY file_id
                {$progress_query}
            )progress ON progress.file_id = file.id
            LEFT JOIN setting.progress setting_progress_tip ON setting_progress_tip.id = (CASE WHEN file.fk IS NOT NULL THEN {$end_progress_id} WHEN progress.progress_id_dest IS NULL THEN 0 ELSE progress.progress_id_dest END)
            LEFT JOIN setting.progress setting_progress ON (CASE WHEN setting_progress.id = COALESCE(progress.progress_id,1) THEN TRUE WHEN progress.progress_id = 12 AND setting_progress.id = {$end_progress_id} THEN TRUE END)
            LEFT JOIN setting.module ON module.id = setting_progress.module_id
            LEFT JOIN setting.module module_tip ON module_tip.id = setting_progress_tip.module_id
            LEFT JOIN (
                SELECT \"客戶代號\" AS code ,\"客戶名稱\" AS name
                    FROM json_to_recordset(
                        '{$customer_code}'
                    ) as setting_customer_code(\"客戶代號\" text,\"客戶名稱\" text)
            ) AS customer_code  ON trim(file.customer) = trim(customer_code.code)
            LEFT JOIN (
                SELECT id, to_char(upload_time::timestamp,'YYYYMMDD') || '-' || to_char(ROW_NUMBER () OVER (
                        PARTITION BY to_char(upload_time::timestamp,'DD-MM-YYYY')
                        ORDER BY
                            id ASC
                    ), 'FM0000') AS tmpid
                FROM file 
            )AS tmpfile  ON file.id = tmpfile.id
            {$query}
            ORDER BY  CASE WHEN file.fk IS NULL THEN 1 ELSE 0 END DESC,to_char(file.upload_time::timestamp,'YYYY-MM-DD') DESC,CASE WHEN file.id IS NOT NULL THEN file.id ELSE 0 END DESC
            ;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    function getTmpid($data)
    {
        $tmpStr = '(';
        foreach ($data['other'] as $key => $value) {
            $tmpStr .= "{$value},";
        }
        $tmpStr = substr_replace($tmpStr, ")", -1);
        $sql = "SELECT tmpfile.tmpid, file.*
        FROM public.file
        LEFT JOIN (
            SELECT id, to_char(upload_time::timestamp,'YYYYMMDD') || '-' || to_char(ROW_NUMBER () OVER (
                    PARTITION BY to_char(upload_time::timestamp,'DD-MM-YYYY')
                    ORDER BY
                        id ASC
                ), 'FM0000') AS tmpid
            FROM file 
        )AS tmpfile  ON file.id = tmpfile.id
        WHERE file.id in {$tmpStr}
        order by file.id ASC;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    function getPartsWithBox($data)
    {
        $filename  = $this->getFileById($data);
    }

    function getFileById($data)
    {
        $sql = "SELECT \"FileName\", rotate
        FROM public.file
        WHERE file.id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }

    function getFileFactoryById($data)
    {
        $sql = "SELECT \"FileNameFactory\"
        FROM public.file
        WHERE file.id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }


    function http_response($url, $wait = null)
    {
        $time = microtime(true);
        $expire = $wait;

        // we fork the process so we don't have to wait for a timeout
        // $pid = pcntl_fork();
        // if ($pid == -1) {
        //     die('could not fork');
        // } else if ($pid) {
        // we are the parent
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if (!is_null($wait))
            curl_setopt($ch, CURLOPT_TIMEOUT, $expire);
        // curl_setopt($ch, CURLOPT_HEADER, false);
        // curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // var_dump($httpCode);
        return ($head);

        //     if (!$head) {

        //         return $head;
        //     }
        //     if ($status === null) {
        //         if ($httpCode < 400) {
        //             return TRUE;
        //         } else {
        //             return $head;
        //         }
        //     } elseif ($status == $httpCode) {
        //         return TRUE;
        //     }

        //     return $head;
        //     // pcntl_wait($status); //Protect against Zombie children
        // // } else {
        // //     // we are the child
        // //     while (microtime(true) < $expire) {
        // //         sleep(0.5);
        // //     }
        // //     return FALSE;
        // // }
    }
    public function getProcessTotal($data)
    {
        $result = [];
        $sql = "SELECT COALESCE(component.name,'無') component_name,COALESCE(component_dest.name,'無') component_dest_name
            FROM process
            LEFT JOIN (
                SELECT *,ROW_NUMBER() OVER (PARTITION BY 
                    process_mapping.process_id order by process_mapping.confidence DESC) AS Row_ID
                FROM process_mapping
            )process_mapping ON process.id = process_mapping.process_id
            LEFT JOIN component ON process.component_id = component.id
            LEFT JOIN crop ON crop.component_id = component.id
            LEFT JOIN file_mapping ON crop.\"fileID\" = file_mapping.file_id_destination
            LEFT JOIN crop crop_dest ON crop_dest.id = process_mapping.crop_id
            LEFT JOIN component component_dest ON crop_dest.component_id = component_dest.id
            WHERE file_mapping.file_id = :file_id AND file_mapping.file_id_destination = :file_id_dest AND component_dest.id IS NOT NULL
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->execute();
        $result['process_mapping'] = $stmt->rowCount();
        $sql = "WITH result AS (
                SELECT process.component_id,AVG(result.confidence) confidence,crop_dest.component_id dest_component_id
                FROM result
                LEFT JOIN process ON process.id = result.process_id
                LEFT JOIN component ON process.component_id = component.id
                LEFT JOIN crop ON component.id = crop.component_id
                LEFT JOIN file_mapping ON crop.\"fileID\" = file_mapping.file_id_destination
                INNER JOIN crop crop_dest ON crop.name = result.filename
                WHERE crop_dest.component_id IS NOT NULL AND file_mapping.file_id = :file_id AND file_mapping.file_id_destination = :file_id_dest
                GROUP BY process.component_id,crop_dest.component_id
                ORDER BY AVG(result.confidence) DESC
            ),process_result AS(
                SELECT result.component_id,result.confidence,result.dest_component_id
                FROM(
                    SELECT result.component_id,result.confidence*100/(SELECT MAX(result.confidence) FROM result) confidence,result.dest_component_id,ROW_NUMBER() OVER (PARTITION BY 
                        result.component_id order by result.confidence DESC) AS Row_ID
                    FROM result
                    LEFT JOIN component component_dest ON result.dest_component_id = component_dest.id
                    WHERE result.confidence*100/(SELECT MAX(result.confidence) FROM result)>=0 AND component_dest.name != ''
                    ORDER BY result.confidence*100/(SELECT MAX(result.confidence) FROM result) DESC
                )result
            )
        ";
        $sql .= "SELECT COALESCE(component.name,'無') component_name,COALESCE(component_dest.name,'無') component_dest_name
            FROM process_result
            LEFT JOIN component ON process_result.component_id = component.id
            LEFT JOIN component component_dest ON process_result.dest_component_id = component_dest.id
            WHERE component_dest.id IS NOT NULL AND component_dest.id IS NOT NULL AND component_dest.name != ''
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->execute();
        $result['process_result'] = $stmt->rowCount();
        return $result;
    }
    public function getProcessMapping($data)
    {
        $sql = "SELECT component_mapping.process_mapping_id,order_name,JSON_AGG(JSON_BUILD_OBJECT('order_dest_name',order_dest_name,'confidence',confidence)) order_dest_name
            ,SUM(CASE WHEN comment_process.stuff !~ '^[0-9\.]+$' THEN '0' ELSE comment_process.stuff END::integer) AS stuff
            ,SUM(CASE WHEN comment_process.material !~ '^[0-9\.]+$' THEN '0' ELSE comment_process.material END::integer) AS material
            ,SUM(CASE WHEN comment_process.process !~ '^[0-9\.]+$' THEN '0' ELSE comment_process.process END::integer) AS process
            ,SUM(CASE WHEN comment_process.outsourcer_cost !~ '^[0-9\.]+$' THEN '0' ELSE comment_process.outsourcer_cost END::integer) AS outsourcer
        FROM (
            SELECT process_mapping.id as process_mapping_id,COALESCE(file.order_name,'無') order_name,COALESCE(file_dest.order_name,COALESCE(file.order_name,'無')) order_dest_name,AVG(process_mapping.confidence) confidence
            FROM process
            LEFT JOIN (
                SELECT *,ROW_NUMBER() OVER (PARTITION BY 
                    process_mapping.process_id order by process_mapping.confidence DESC) AS Row_ID
                FROM process_mapping
                WHERE process_mapping.confidence >= :threshold
            )process_mapping ON process.id = process_mapping.process_id
            LEFT JOIN component ON process.component_id = component.id
            LEFT JOIN crop ON crop.component_id = component.id
            LEFT JOIN file ON file.id = crop.\"fileID\"
            LEFT JOIN file file_dest ON file_dest.id = process_mapping.crop_id
            WHERE crop.\"fileID\" = :file_id_dest
                AND Row_ID <= :limit
            GROUP BY file.order_name,file_dest.order_name,process_mapping_id
        ) component_mapping
		LEFT JOIN comment_process ON component_mapping.process_mapping_id = comment_process.process_mapping_id
       
        GROUP BY component_mapping.order_name,component_mapping.process_mapping_id
        ";


        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->bindValue(':threshold', $data['threshold']);
        $stmt->bindValue(':limit', $data['limit']);
        $stmt->execute();
        $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($components) == 0) {
            $addsql  = "SELECT order_name, '[]' as order_dest_name FROM public.file WHERE id = :file_id_dest";
            $stmt = $this->db->prepare($addsql);
            $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
            $stmt->execute();
            $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // $addsql  = "SELECT * FROM public.crop WHERE \"fileID\" = :file_id_dest";
            // $stmt = $this->db->prepare($addsql);
            // $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
            // $stmt->execute();

            // if($stmt->rowCount()==0){
            //     $addsql  = "INSERT INTO public.component( \"name\") VALUES(''); ";
            //     $stmt = $this->db->prepare($addsql);
            //     $stmt->execute();
            //     $component_id = $this->db->lastInsertId();

            //     $addsql  = "INSERT INTO public.crop( \"fileID\", \"name\", component_id)
            //         SELECT  :file_id_dest,file.\"FileName\",:component_id
            //         FROM file
            //         WHERE file.id = :file_id_dest; ";
            //     $stmt = $this->db->prepare($addsql);
            //     $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
            //     $stmt->bindValue(':component_id', $component_id);
            //     $stmt->execute();
            // }

            // $addsql  = "INSERT INTO public.process_mapping( process_id, crop_id, confidence)
            //     SELECT  process.id as process_id ,crop.\"fileID\" as crop_id,  0 as confidence 
            //     FROM public.crop
            //     LEFT JOIN public.process ON process.component_id = crop.component_id
            //     WHERE \"fileID\" = :file_id_dest
            //     limit 1; ";
            // $stmt = $this->db->prepare($addsql);
            // $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
            // $stmt->execute();
            // $tmpprocess_mappind_id = $this->db->lastInsertId();
            // $addsql = "INSERT INTO public.comment_process(
            //     process_mapping_id, module_id, update_time,update_date)
            //     VALUES (:process_mapping_id,  round(  random() * 5 + 1, 2 ) , NOW(), NOW());";
            // $stmt = $this->db->prepare($addsql);
            // $stmt->bindValue(':process_mapping_id', $tmpprocess_mappind_id);
            // $stmt->execute();
            // $sql = "SELECT component_mapping.process_mapping_id,order_name,JSON_AGG(JSON_BUILD_OBJECT('order_dest_name',order_dest_name,'confidence',confidence)) order_dest_name
            //     ,SUM(CASE WHEN comment_process.stuff !~ '^[0-9\.]+$' THEN '0' ELSE comment_process.stuff END::integer) AS stuff
            //     ,SUM(CASE WHEN comment_process.material !~ '^[0-9\.]+$' THEN '0' ELSE comment_process.material END::integer) AS material
            //     ,SUM(CASE WHEN comment_process.process !~ '^[0-9\.]+$' THEN '0' ELSE comment_process.process END::integer) AS process
            //     ,SUM(CASE WHEN comment_process.outsourcer_cost !~ '^[0-9\.]+$' THEN '0' ELSE comment_process.outsourcer_cost END::integer) AS outsourcer
            // FROM (
            //     SELECT process_mapping.id as process_mapping_id,COALESCE(file.order_name,'無') order_name,COALESCE(file_dest.order_name,'無') order_dest_name,AVG(process_mapping.confidence) confidence
            //     FROM process
            //     LEFT JOIN (
            //         SELECT *,ROW_NUMBER() OVER (PARTITION BY 
            //             process_mapping.process_id order by process_mapping.confidence DESC) AS Row_ID
            //         FROM process_mapping
            //         WHERE process_mapping.confidence >= :threshold
            //     )process_mapping ON process.id = process_mapping.process_id
            //     LEFT JOIN component ON process.component_id = component.id
            //     LEFT JOIN crop ON crop.component_id = component.id
            //     LEFT JOIN file ON file.id = crop.\"fileID\"
            //     LEFT JOIN file file_dest ON file_dest.id = process_mapping.crop_id
            //     WHERE crop.\"fileID\" = :file_id_dest AND file_dest.order_name IS NOT NULL
            //         AND Row_ID <= :limit
            //     GROUP BY file.order_name,file_dest.order_name,process_mapping_id
            // ) component_mapping
            // LEFT JOIN comment_process ON component_mapping.process_mapping_id = comment_process.process_mapping_id

            // GROUP BY component_mapping.order_name,component_mapping.process_mapping_id


            // ";

            // $stmt = $this->db->prepare($sql);
            // $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
            // $stmt->bindValue(':threshold', $data['threshold']);
            // $stmt->bindValue(':limit', $data['limit']);
            // $stmt->execute();
            // $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach ($components as $component_key => $component) {
            $dest_components = json_decode($component['order_dest_name'], true);
            foreach ($dest_components as $dest_component_key => $dest_component) {
                $query = "WHERE RTRIM(LTRIM([COPTB].[TB201])) LIKE '%{$dest_component['order_dest_name']}%'";
                $tquery = "WHERE RTRIM(LTRIM(t.[TB201])) LIKE '%{$dest_component['order_dest_name']}%'";
                if ($query == "") {
                    return [];
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
                        ['sql' => "SELECT TOP 4 [COPTB].[TB004] AS 零件名稱,STUFF((
                            SELECT  t.[TB004] AS 零件名稱,t.[TB205] as 材質代號,CAST(t.[TB009] AS DECIMAL(18,2)) as 報價金額
                                , CAST(ISNULL(t.[TB217], '0' ) AS DECIMAL(18,2)) as \"鍍鈦金額\",t.[TB008] as 單位
                                , convert(datetime, [COPTA].[TA013],111) as 日期,'{$dest_component['confidence']}' AS confidence 
                                , CAST(ISNULL(t.[TB210], '0' ) AS DECIMAL(18,2)) as \"包裝金額\", CAST(ISNULL([COPTA].[TA029], '0' ) AS DECIMAL(18,2)) as \"運費\",ISNULL([COPTA].[TA007],'') AS 幣別
                            FROM [MIL].[dbo].[COPTB] t 
                            LEFT JOIN [MIL].[dbo].[COPTA] ON [COPTA].[TA001] = t.[TB001] AND [COPTA].[TA002] = t.[TB002]
                            {$tquery} AND t.[TB004]=[COPTB].[TB004]
                            
                            FOR XML AUTO),1,0,''
                        ) AS 詳細內容
                            FROM [MIL].[dbo].[COPTB]
                            {$query}
                            GROUP BY [COPTB].[TB004]
                        "]
                    )
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $head = curl_exec($ch);
                curl_close($ch);
                $results = json_decode($head, true);
                // var_dump($results);
                if (!empty($results)) {
                    foreach ($results as $key_results => $result) {
                        foreach ($result as $key_result => $value) {
                            $xml = simplexml_load_string("<a>$value</a>");
                            if ($value == "" || $key_result == '零件名稱') {
                                continue;
                            }
                            $result[$key_result] = [];
                            foreach ($xml->t as $t) {
                                $tmpArr = [];
                                foreach ($t->attributes() as $a => $b) {


                                    $tmpArr[$a] = $b;
                                }
                                array_push($result[$key_result], (array)$tmpArr);
                                // foreach ($t->attributes() as $a => $b) {
                                //     $result[$key_result][$a] = $b;
                                // }
                                // break;
                            }
                            // var_dump($result);




                        }
                        $dest_components[$dest_component_key] = $result;
                    }
                }
            }
            $components[$component_key]['order_dest_name'] = $dest_components;

            $sql = "SELECT SUM(CASE WHEN cost !~ '^[0-9\.]+$' THEN '0' ELSE cost END::integer) AS sum 
            FROM public.process_cost
            WHERE file_id=:file_id
            GROUP BY  file_id;";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':file_id', $data['file_id']);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (isset($row[0])) {
                $components[$component_key]['other'] =  $row[0]['sum'];
            } else {
                $components[$component_key]['other'] = 0;
            }
        }
        return $components;
    }


    public function getProcessResult($data)
    {
        $sql = "WITH result AS (
                SELECT file_dest.order_name order_dest_name,file.order_name,crop.\"fileID\",crop.\"fileID\" id,file.order_serial,AVG(result.confidence) confidence
                FROM result
                LEFT JOIN process ON process.id = result.process_id
                LEFT JOIN component ON process.component_id = component.id
                LEFT JOIN crop crop_org ON crop_org.\"name\" = result.source
                INNER JOIN crop ON crop.name = result.filename
                LEFT JOIN file file_dest ON file_dest.id = crop.\"fileID\"
                LEFT JOIN file ON file.id = crop_org.\"fileID\"
                WHERE crop_org.\"fileID\" = :file_id_dest
                GROUP BY file_dest.order_name,crop.\"fileID\",file.order_name,file.order_serial
                ORDER BY AVG(result.confidence) DESC
                LIMIT :limit
            )
            SELECT result.order_name,JSON_AGG(JSON_BUILD_OBJECT('order_dest_name',order_dest_name,'confidence',confidence)) order_dest_name
            FROM result
            WHERE confidence >= :threshold
            GROUP BY result.order_name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id_dest', $data['file_id_dest']);
        $stmt->bindValue(':threshold', $data['threshold']);
        $stmt->bindValue(':limit', $data['limit']);
        $stmt->execute();
        $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // var_dump($components);

        foreach ($components as $component_key => $component) {
            $dest_components = json_decode($component['order_dest_name'], true);
            foreach ($dest_components as $dest_component_key => $dest_component) {
                $query = "WHERE RTRIM(LTRIM([COPTB].[TB201])) LIKE '%{$dest_component['order_dest_name']}%'";
                if ($query == "") {
                    return [];
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
                        ['sql' => "SELECT TOP 4 [COPTB].[TB004] AS 零件名稱,STUFF((
                                    SELECT TOP 4 t.[TB004] AS 零件名稱,t.[TB205] as 材質代號,CAST(t.[TB009] AS DECIMAL(18,2)) as 報價金額
                                        , CAST(ISNULL(t.[TB217], '0' ) AS DECIMAL(18,2)) as \"鍍鈦金額\",t.[TB008] as 單位
                                        , convert(datetime, [COPTA].[TA013],111) as 日期,'{$dest_component['confidence']}' AS confidence 
                                        , CAST(ISNULL(t.[TB210], '0' ) AS DECIMAL(18,2)) as \"包裝金額\", CAST(ISNULL([COPTA].[TA029], '0' ) AS DECIMAL(18,2)) as \"運費\",ISNULL([COPTA].[TA007],'') AS 幣別
                                    FROM [MIL].[dbo].[COPTB] t 
                                    LEFT JOIN [MIL].[dbo].[COPTA] ON [COPTA].[TA001] = t.[TB001] AND [COPTA].[TA002] = t.[TB002]
                                    WHERE t.[TB004]=[COPTB].[TB004]
                                    FOR XML AUTO),1,0,''
                                ) AS 詳細內容
                                
                            FROM [MIL].[dbo].[COPTB]
                            {$query}
                            GROUP BY [COPTB].[TB004]
                        "]
                    )
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $head = curl_exec($ch);
                curl_close($ch);
                $results = json_decode($head, true);
                foreach ($results as $key_results => $result) {
                    foreach ($result as $key_result => $value) {
                        $result[$key_result] = [];
                        $xml = simplexml_load_string("<a>$value</a>");
                        if ($value == "") {
                            continue;
                        }
                        foreach ($xml->t as $t) {
                            foreach ($t->attributes() as $a => $b) {
                                $result[$key_result][$a] = $b;
                            }
                            break;
                        }
                    }
                    $dest_components[$dest_component_key] = $result;
                }
            }
            $components[$component_key]['order_dest_name'] = $dest_components;
        }
        return $components;
    }
    function getUserByUid($data)
    {
        $sql = "SELECT \"user\".id, user_modal.module_id
            FROM system.user
            LEFT JOIN system.user_modal ON user_modal.uid = \"user\".id
            WHERE \"user\".uid = :uid
            ORDER BY user_modal.module_id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $data['uid']);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    function getUserById($data)
    {
        $sql = "SELECT \"user\".id, user_modal.module_id
            FROM system.user
            LEFT JOIN system.user_modal ON user_modal.uid = \"user\".id
            WHERE \"user\".id = :id
            ORDER BY user_modal.module_id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    function getUser($data)
    {
        $sql = "SELECT \"user\".\"name\",user_modal.module_id
            FROM system.user
            LEFT JOIN system.user_modal ON \"user\".id = user_modal.uid
            WHERE id = :id
            ORDER BY user_modal.module_id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    function addUser($data)
    {
        $sql = "INSERT INTO system.user (uid,\"name\")
            VALUES (:uid,:name)
            ON CONFLICT
            DO NOTHING
            RETURNING id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    function getManuProcess($data)
    {
        $query = "";
        foreach ($data['timestamp'] as $key => $timestamp) {
            $query .= "AND CONVERT(DATETIME,'$timestamp',102) BETWEEN [COPTA].[TA013] AND DATEADD(DAY,[COPTA].[TA014],CONVERT(DATETIME,[COPTA].[TA013],102))";
        }
        if ($query == "") {
            return [];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT TOP 100 COUNT(*) as 已處理,[SFCTA].[TA004] as 製程代號, [CMSMW].[MW002] as 製程名稱
                    FROM [MIL].[dbo].[CMSMW],[MIL].[dbo].[COPTA],[MIL].[dbo].[COPTB],[MIL].[dbo].[COPTD],[MIL].[dbo].[MOCTA],[MIL].[dbo].[SFCTA]
                    WHERE CMSMW.MW001=SFCTA.TA004
                    and COPTD.TD001=MOCTA.TA026 
                    and COPTD.TD002=MOCTA.TA027
                    and COPTD.TD003=MOCTA.TA028 
                    and COPTA.TA001=COPTB.TB001 
                    and COPTA.TA002=COPTB.TB002 
                    and COPTD.TD002=COPTB.TB002
                    and COPTD.TD003=COPTB.TB003
                    and COPTD.TD004=COPTB.TB004
                    and SFCTA.TA001=MOCTA.TA001 
                    and SFCTA.TA002=MOCTA.TA002  
                    AND MOCTA.TA001=SFCTA.TA001 
                    and MOCTA.TA002=SFCTA.TA002
                    {$query}
                    GROUP BY [SFCTA].[TA004], [CMSMW].[MW002]
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        return $result;
    }

    public function getMessageFileList($data)
    {
        $values = [
            "type" => "報價",
            "now" => date("Y-m-d")
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $this->container->message_directory .= DIRECTORY_SEPARATOR . $values["type"];
        $files  = scandir($this->container->message_directory);
        $result = [];
        foreach ($files as $key => $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'msg' || pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                if (date("Y-m-d", filemtime($this->container->message_directory . DIRECTORY_SEPARATOR . $file)) === $values['now'])
                    $result[DIRECTORY_SEPARATOR . $values["type"] . DIRECTORY_SEPARATOR . $file] = [
                        "datetime" => date("Y-m-d H:i:s", filemtime($this->container->message_directory . DIRECTORY_SEPARATOR . $file)),
                        "is_locked" => false
                    ];
            }
        }
        $messageLocked = $this->getMessageFileLock($data);
        foreach ($messageLocked as $message) {
            if (array_key_exists($message['file_path'], $result)) {
                $result[$message['file_path']]['is_locked'] = $message['is_locked'];
            }
        }
        if (count($result) > 0) {
            arsort($result);
            return $result;
        }
        $ack = array();
        return $ack;
    }
    public function checkMessageFile($data)
    {
        foreach ($data as $key => $file) {
            if ($key === 'file_name') {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'msg' || pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                    if (file_exists($this->container->message_directory . DIRECTORY_SEPARATOR . $file)) {
                        return [
                            "status" => "success",
                            "file_name" => $this->container->message_directory . DIRECTORY_SEPARATOR . $file
                        ];
                    }
                }
            }
        }
        $ack = array(
            'status' => 'failed'
        );
        return $ack;
    }
    public function copyMessageFile($data)
    {
        foreach ($data as $key => $file) {
            if ($key === 'file_name') {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'msg' || pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                    if (file_exists($file)) {
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
                        $filename = sprintf('%s.%0.8s', $basename, $extension);
                        copy($file, $this->container->upload_directory . DIRECTORY_SEPARATOR . $filename);
                        return [
                            "status" => "success",
                            "file_name" => $filename
                        ];
                    }
                }
            }
        }
        $ack = array(
            'status' => 'failed'
        );
        return $ack;
    }
    public function getMessageQuotation($data)
    {
        $result = [];
        foreach ($data as $key => $file) {
            if ($key === 'file_name') {
                if (file_exists($this->container->upload_directory . DIRECTORY_SEPARATOR . $file)) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'xlsx' || pathinfo($file, PATHINFO_EXTENSION) === 'xls') {
                        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->container->upload_directory . DIRECTORY_SEPARATOR . $file);
                        $worksheet = $spreadsheet->getActiveSheet();
                        // Get the highest row number and column letter referenced in the worksheet
                        $highestRow = $worksheet->getHighestRow(); // e.g. 10
                        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
                        // Increment the highest column letter
                        $highestColumn++;
                        $data = [];
                        $customer = "";
                        $customer_order_id = "";
                        for ($row = 1; $row <= $highestRow; ++$row) {
                            $tmp = [];
                            for ($col = 'A'; $col != $highestColumn; ++$col) {
                                $tmp[] = strval($worksheet->getCell($col . $row)
                                    ->getValue());
                                $str = strval($worksheet->getCell($col . $row)
                                    ->getValue());
                                (strpos($str,"order")!==FALSE||strpos($str,"enquire")!==FALSE)&&$customer_order_id=$str;
                                strpos($str,"customer")!==FALSE&&$customer=$str;
                            }
                            $data[] = $tmp;
                        }
                        return ["customer_order_id"=>$customer_order_id,"data"=>$data,"customer"=>$customer];
                    } else {
                        $tmep_file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $file;
                        // Load
                        $source = $this->compressImage($tmep_file);
                        if (@$data['rotate'] < 0) {
                            @$data['rotate'] += 360;
                        }
                        // Rotate
                        $rotate = imagerotate($source, 360 - intval(@$data['rotate']), imagecolorallocate($source, 255, 255, 255));
                        unlink($tmep_file);
                        // Output
                        imagejpeg($rotate, $tmep_file);
                        $files = json_encode([$file]);
                        $home = new Home($this->db);
                        $recogUrl = "http:/mil_python:8090/orderParse?Files={$files}";
                        $result = $home->http_response($recogUrl);
                        $result = json_decode($result, true);
                        return ["customer_order_id"=>"","data"=>$result,"customer"=>""];
                    }
                }
            }
        }
        $ack = array(
            'status' => 'failed'
        );
        return $ack;
    }
    public function getQuotationColumns($data)
    {
        $result = [
            "order_name" => "客戶圖號",
            "customer" => "客戶全名"
        ];
        return $result;
    }

    public function getMessageImages($data)
    {
        $values = [
            "file_name" => "noImage.png"
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $file = $values['file_name'];
        if (file_exists($this->container->upload_directory . DIRECTORY_SEPARATOR . $file)) {
            $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $file;
        } else {
            $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . "noImage.png";
        }
        $source = $this->compressImage($file, $file, 100);
        imagejpeg($source);
        return;
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
    public function concatImagePath($data)
    {
        $values = [];
        foreach ($data as $key => $row) {
            if (gettype($row) === 'array') {
                foreach ($row as $value) {
                    $values[] = [
                        "src" => "/file/message/image/{$value}",
                        "alt" => "此附件不為圖檔，檔名：" . $key,
                        "file_client_name" => $key,
                        "file_name" => $value
                    ];
                }
            } else {
                $value = $row;
                $values[] = [
                    "src" => "/file/message/image/{$value}",
                    "alt" => "此附件不為圖檔，檔名：" . $key,
                    "file_client_name" => $key,
                    "file_name" => $value
                ];
            }
        }
        return $values;
    }
    function getMessageHistory($data)
    {
        $values = [
            "order_name" => "",
            "order_names" => []
        ];
        if (array_key_exists('order_name', $data))
            $values['order_name'] = $data['order_name'];
        if (array_key_exists('order_names', $data)) {
            $values['order_names'] = $data['order_names'];
        }
        $stmt_string = "";
        if (count($values['order_names']) > 0) {
            $stmt_string = "AND (" . implode(" OR ", array_map(function ($order_name) {
                return "COPTB.TB201 LIKE '%" . str_replace(' ', '%', $order_name) . "%'";
            }, $values['order_names'])) . ")";
        }
        while ($values['order_name'] !== "" || !empty($values['order_names'])) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
            curl_setopt($ch, CURLOPT_POST, 1);
            // In real life you should use something like:
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query(
                    ['sql' => "SELECT TOP 1 COPTB.TB201 \"客戶圖號\",COPTB.TB002+'-'+COPTB.TB003 \"報價單編號\",COPTA.TA004 \"客戶代號\",COPTA.TA003 \"開單日期\",
                        STUFF((
                            SELECT TOP 10
                                RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) as 報價編號,
                                [COPTA].[TA003] as 報價日期,
                                CAST([COPTB].[TB007] AS DECIMAL(18,0))  as 報價數量,
                                CAST([COPTB].[TB009] AS DECIMAL(18,2)) as 報價單價,
                                CAST([COPTB].[TB010] AS DECIMAL(18,2)) as 報價金額,
                                [COPTA].[TA006] as 客戶全名,
                                [COPTB].[TB201] as 客戶圖號,
                                [CMSXB].XB002 as 材質,
                                [CMSXC].[XC002] as 鍍鈦,
                                [COPTA].[TA004] as 客戶圖片,
                                [COPTA].[TA007] as 幣別,
                                COPTB.TB001,
                                COPTB.TB002,
                                COPTB.TB003
                                FROM [MIL].[dbo].[COPTA]
                                LEFT JOIN [MIL].[dbo].[COPTB] t ON t.[TB001] = [COPTA].[TA001] AND t.[TB002] = [COPTA].[TA002]
                                LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                                LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTB.TB204
                                WHERE t.TB201 = [COPTB].[TB201]
                                ORDER BY [COPTA].[TA003] DESC
                            FOR XML PATH),1,0,''
                        )history
                        FROM [MIL].[dbo].[COPTA],[MIL].[dbo].[COPTB]
                        WHERE COPTB.TB201 LIKE '%{$values['order_name']}%' {$stmt_string}
                        and COPTA.TA001=COPTB.TB001
                        and COPTA.TA002=COPTB.TB002
                        ORDER BY COPTA.TA003 DESC
                    "]
                )
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $head = curl_exec($ch);
            $result = json_decode($head, true);
            if (!empty($result) || !empty($values['order_names'])) {
                break;
            } else {
                $array_temp = explode(' ', $values['order_name']);
                array_pop($array_temp);
                $values['order_name'] = implode(" ", $array_temp);
            }
        }
        $result = [];
        if (isset($result)) {
            foreach ($result as $key_result => $value) {
                $tmpvalue = $value['history'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['history'] = $tmpArrs;
                    goto Endprocess;
                }
                foreach ($xml as $t) {
                    $tmpArr = [];
                    foreach ($t as $a => $b) {
                        $tmpArr[$a] = '';
                        foreach ((array)$b as $c => $d) {
                            $tmpArr[$a] = $d;
                        }
                    }
                    array_push($tmpArrs, (array)$tmpArr);
                }
                $result[$key_result]['history'] = $tmpArrs;
                Endprocess:
            }
        }

        return $result;
    }
    public function insertFileByOrderName($data)
    {
        $values = [
            "order_name" => "",
            "file_name" => "",
            "file_client_name" => "",
            "itemno" => ''
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data))
                $values[$key] = $data[$key];
        }
        if ($values["file_client_name"] === "") $values["file_client_name"] = $values["file_name"];
        $sql = "INSERT INTO public.file(
            \"ClientName\", \"FileName\", upload_time, order_name, itemno)
            VALUES (:file_client_name , :file_name , NOW(),:order_name, :itemno)
            RETURNING id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $file_id = $stmt->fetchColumn(0);
        return ["file_id" => $file_id];
    }
    public function getFileByFK($data, $is_outer = true)
    {

        $is_outer = $is_outer ? 1 : 0;
        $values = [
            'TB001' => '',
            'TB002' => '',
            'TB003' => '',
            'ClientName' => '',
            'FileName' => '',
        ];

        foreach ($data as $row_key => &$row) {
            if ($row_key === 'fk' && isJson($row)) {
                $row = json_decode($row, true);
                foreach ($values as $key => $value) {
                    if (array_key_exists($key, $row)) {
                        $values[$key] = $row[$key];
                    }
                }
            }
        }
        $sql = "SELECT TOP 1 
                    STUFF((
                        SELECT  
                            dt.TB001,
                            dt.TB002,
                            dt.TB003
                        FROM [MIL].[dbo].[COPTB] dt
                        WHERE dt.TB001=COPTB.TB001 AND dt.TB002 = COPTB.TB002 AND dt.TB003 = COPTB.TB003
                        FOR XML PATH),1,0,''
                    )fk,
                    STUFF((
                        SELECT  
                            dt.TB205
                        FROM [MIL].[dbo].[COPTB] dt
                        WHERE dt.TB001=COPTB.TB001 AND dt.TB002 = COPTB.TB002 AND dt.TB003 = COPTB.TB003
                        FOR XML PATH),1,0,''
                    )material,
                    STUFF((
                        SELECT  
                            dt.TB204
                        FROM [MIL].[dbo].[COPTB] dt
                        WHERE dt.TB001=COPTB.TB001 AND dt.TB002 = COPTB.TB002 AND dt.TB003 = COPTB.TB003
                        FOR XML PATH),1,0,''
                    )titanizing,
                    STUFF((
                        SELECT  
                            RTRIM(LTRIM(t.[TA001])) AS TA001,
                            RTRIM(LTRIM(t.[TA002])) AS TA002,
                            RTRIM(LTRIM(t.[TA003])) AS num,
                            t.TA004 AS code,
                            CMSMW.MW002 AS name,
                            '' AS mark,
                            '' AS outsourcer,
                            '' AS deadline,
                            '' AS outsourcer_cost,
                            '' AS cost
                        FROM [MIL].[dbo].[SFCTA] t
                        LEFT JOIN [MIL].[dbo].[MOCTA] ON t.[TA001]=[MOCTA].[TA001] AND t.[TA002]=[MOCTA].[TA002]
                        LEFT JOIN [MIL].[dbo].[CMSMW] ON CMSMW.MW001=t.TA004
                        WHERE COPTD.TD001 = [MOCTA].TA026 AND COPTD.TD002 = [MOCTA].TA027 AND COPTD.TD003 = [MOCTA].TA028
                        FOR XML PATH),1,0,''
                    )process,
                    STUFF((
                        SELECT 
                            dt.TB009 AS cost,
                            dt.TB007 AS num,
                            COPTA.TA007 AS currency,
                            0 AS discount,
                            '' AS descript,
                            COPTC.TC039 AS update_time,
                            '' AS deadline,
                            COALESCE(DATEDIFF(week,COPTC.TC039 ,COPTD.TD013),0) AS delivery_range,
                            COALESCE(DATEDIFF(week,COPTC.TC039 ,COPTD.TD013),0) AS delivery_week
                        FROM [MIL].[dbo].[COPTB] dt
                        LEFT JOIN [MIL].[dbo].[COPTD] ON [COPTD].[TD017] = dt.[TB001] AND [COPTD].[TD018] = dt.[TB002] AND [COPTD].[TD019] = dt.[TB003]
                        LEFT JOIN [MIL].[dbo].COPTC ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
                        LEFT JOIN [MIL].[dbo].COPTA ON COPTA.TA001 = dt.TB001 AND COPTA.TA002 = dt.TB002
                        WHERE dt.TB001=COPTB.TB001 AND dt.TB002 = COPTB.TB002 AND dt.TB003 = COPTB.TB003
                        FOR XML PATH),1,0,''
                    )quotation,
                    1 AS multiple,
                    0 AS outsourcer_amount,
                    '' AS order_serial,
                    null AS outsourcer,
                    TB004 AS 品號,
                    TB004 AS itemno,
                    TB206 AS 硬度,
                    TB201 AS 客戶圖號,
                    TB201 AS order_name,
                    TB202 AS 版次,
                    COPTB.TB205 AS 材質,
                    COPTB.TB009 AS 單價,
                    COPTB.TB009 AS cost,
                    COPTB.TB007 AS 數量,
                    COPTB.TB007 AS num,
                    COPTA.TA007 AS 幣別,
                    COPTB.TB204 AS 鍍鈦,
                    [COPTA].[TA003] AS upload_time,
                    [COPTA].[TA004] AS 客戶代號,
                    [COPTA].[TA004] AS customer_order_id,
                    [COPTA].[TA004] AS customer,
                    COPTC.TC039 AS 單據日期,
                    COPTD.TD013 AS 指定日期,
                    COPTD.TD013 AS delivery_date,
                    COPTD.TD013 AS deadline,
                    {$is_outer} AS is_outer,
                    '{$values['ClientName']}' AS ClientName,
                    '{$values['FileName']}' AS FileName,
                    COALESCE(DATEDIFF(week,COPTC.TC039 ,COPTD.TD013),0) AS 交貨週數,
                    COALESCE(DATEDIFF(week,COPTC.TC039 ,COPTD.TD013),0) AS delivery_week
            FROM [MIL].[dbo].[COPTB]
            LEFT JOIN [MIL].[dbo].[COPTD] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
            LEFT JOIN [MIL].[dbo].COPTC ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
            LEFT JOIN [MIL].[dbo].COPTA ON COPTA.TA001 = COPTB.TB001 AND COPTA.TA002 = COPTB.TB002
            WHERE LTRIM(RTRIM(COPTB.TB001)) = LTRIM(RTRIM('{$values['TB001']}')) AND LTRIM(RTRIM(COPTB.TB002)) = LTRIM(RTRIM('{$values['TB002']}')) AND LTRIM(RTRIM(COPTB.TB003)) = LTRIM(RTRIM('{$values['TB003']}'))
        ";
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
        $result = json_decode($head, true);
        curl_close($ch);
        if (isset($result)) {
            foreach ($result as $key_result => $value) {
                $tmpvalue = $value['process'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['process'] = $tmpArrs;
                    goto Endprocess;
                }
                foreach ($xml as $t) {
                    $tmpArr = [];
                    foreach ($t as $a => $b) {
                        $tmpArr[$a] = '';
                        foreach ((array)$b as $c => $d) {
                            $tmpArr[$a] = $d;
                        }
                    }
                    array_push($tmpArrs, (array)$tmpArr);
                }
                $result[$key_result]['process'] = $tmpArrs;
                Endprocess:

                $tmpvalue = $value['quotation'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['quotation'] = $tmpArrs;
                    goto Endquotation;
                }
                foreach ($xml as $t) {
                    $tmpArr = [];
                    foreach ($t as $a => $b) {
                        $tmpArr[$a] = '';
                        foreach ((array)$b as $c => $d) {
                            $tmpArr[$a] = $d;
                        }
                    }
                    array_push($tmpArrs, (array)$tmpArr);
                }
                $result[$key_result]['quotation'] = $tmpArrs;
                Endquotation:

                $tmpvalue = $value['material'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['material'] = $tmpArrs;
                    goto Endmaterial;
                }
                foreach ($xml as $t) {
                    $tmpArr = '';
                    foreach ($t as $a => $b) {
                        foreach ((array)$b as $c => $d) {
                            $tmpArr = $d;
                        }
                    }
                    array_push($tmpArrs, $tmpArr);
                }
                $result[$key_result]['material'] = $tmpArrs;
                Endmaterial:

                $tmpvalue = $value['titanizing'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['titanizing'] = $tmpArrs;
                    goto Endtitanizing;
                }
                foreach ($xml as $t) {
                    $tmpArr = '';
                    foreach ($t as $a => $b) {
                        foreach ((array)$b as $c => $d) {
                            $tmpArr = $d;
                        }
                    }
                    array_push($tmpArrs, $tmpArr);
                }
                $result[$key_result]['titanizing'] = $tmpArrs;
                Endtitanizing:

                $tmpvalue = $value['fk'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['fk'] = $tmpArrs;
                    continue;
                }
                foreach ($xml as $t) {
                    $tmpArr = [];
                    foreach ($t as $a => $b) {
                        $tmpArr[$a] = '';
                        foreach ((array)$b as $c => $d) {
                            $tmpArr[$a] = $d;
                        }
                    }
                    $tmpArrs = (array)$tmpArr;
                }
                $result[$key_result]['fk'] = $tmpArrs;
            }
        }
        return $result;
        /* 
SELECT TOP 1000 TB004 AS 品號,TB206  AS  硬度,TB201 AS  客戶圖號,TB202 AS  版次,
        COPTB.TB205  AS  材質,COPTB.TB009 as 單價,COPTB.TB007 as 數量,COPTA.TA007 as 幣別,
        COPTB.TB204  AS 鍍鈦,[COPTA].[TA004] as 客戶代號,COPTD.TD013 as 指定日期,COPTC.TC039 as 單據日期,DATEDIFF(week,COPTC.TC039 ,COPTD.TD013)交貨週數,
        STUFF((
			SELECT  RTRIM(LTRIM(t.[TA001])) as TA001,RTRIM(LTRIM(t.[TA002])) as TA002,CAST(RTRIM(LTRIM(t.[TA003]))as integer) as TA003,t.[TA010] AS num,CMSMW.MW002 as MW002
			FROM [MIL].[dbo].[SFCTA] t
			LEFT JOIN [MIL].[dbo].[MOCTA] ON t.[TA001]=[MOCTA].[TA001] AND t.[TA002]=[MOCTA].[TA002]
			LEFT JOIN [MIL].[dbo].[CMSMW] ON CMSMW.MW001=t.TA004
			WHERE COPTD.TD001 = [MOCTA].TA026 AND COPTD.TD002 = [MOCTA].TA027 AND COPTD.TD003 = [MOCTA].TA028
			FOR XML AUTO),1,0,''
        )
FROM [MIL].[dbo].[COPTB]
LEFT JOIN [MIL].[dbo].[COPTD] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
LEFT JOIN [MIL].[dbo].COPTC ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
LEFT JOIN [MIL].[dbo].COPTA ON COPTA.TA001 = COPTB.TB001 AND COPTA.TA002 = COPTB.TB002
 */
        /* 
order_serial:1
order_name:gygy
multiple:1
deadline:2022-03-25 01:08:00
outsourcer:12
outsourcer_amount:10
customer:2080010   
delivery_date:2021-08-28 16:20:00
itemno:12080040060790011022
delivery_week:4
material[0]:001
titanizing[0]:001 
quotation[0][cost]:1
quotation[0][num]:1
quotation[0][discount]:1
quotation[0][descript]:qq
quotation[0][update_time]:
quotation[0][deadline]:2022-03-25 01:08:00
quotation[0][delivery_week]:2
quotation[0][currency]:NTD
process[0][num]:1
process[0][code]:1
process[0][name]:QQ
process[0][mark]:22
process[0][cost]:20
process[0][outsourcer]:111
process[0][deadline]:2021-08-28 16:20:00
process[0][outsourcer_cost]:11
quotation[0][process_id]:
*/
    }

    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function readUserDetail($params)
    {
        (!is_null($params['user_id'])) ? $user_id_condition = 'AND user_module.user_id = :user_id' : $user_id_condition = '';
        (!is_null($params['module_id'])) ? $module_id_condition = 'WHERE user_detail.module_id::TEXT LIKE :module_id' : $module_id_condition = '';
        $sql = "SELECT user_detail.*
                FROM
                (
                    SELECT user_module.user_id, user_module.uid, user_module.user_name,
                        user_module.email, user_module.gender, user_module.country,
                        ARRAY_TO_JSON(ARRAY_AGG(user_module.module_id ORDER BY user_module.module_id)) module_id,
                        ARRAY_TO_JSON(ARRAY_AGG(user_module.module_name ORDER BY user_module.module_id)) module_name,
                        editor.editor, user_module.edit_time,
                        ARRAY_TO_JSON(string_to_array(trim(both ',' from user_permission.permission), ',')::int[] ) AS permission
                    FROM 
                    (
                        SELECT \"user\".id user_id, \"user\".uid, \"user\".name user_name,
                            \"user\".email, \"user\".gender, \"user\".country,
                            \"user\".editor_id, \"user\".edit_time, module.id module_id, module.name module_name
                        FROM system.\"user\"
                        LEFT JOIN system.user_modal ON user_modal.uid = \"user\".id
                        LEFT JOIN setting.module ON module.id = user_modal.module_id
                       
                    ) user_module
                    LEFT JOIN (
                        SELECT \"user\".id, \"user\".name editor
                        FROM system.\"user\"
                    ) editor ON editor.id = user_module.editor_id
                    LEFT JOIN(
                        SELECT user_id , 
                            -- ARRAY_TO_JSON(ARRAY_AGG(user_permission.permission_id ORDER BY user_permission.permission_id)) AS permission 
                            array_to_string(ARRAY_AGG(user_permission.permission_id ORDER BY user_permission.permission_id), ',') AS permission 
                        FROM system.user_permission
                        GROUP BY user_permission.user_id
                    ) AS user_permission ON user_permission.user_id = user_module.user_id
                    
                    WHERE user_module.module_id IS NOT NULL {$user_id_condition}
                    GROUP BY user_module.user_id, user_module.uid, user_module.user_name, user_module.email,
                        user_module.gender, user_module.country, editor.editor, user_module.edit_time,user_permission.permission
                    ORDER BY user_module.user_id, user_module.uid, user_module.user_name, user_module.email,
                        user_module.gender, user_module.country, editor.editor, user_module.edit_time
                ) user_detail
               
                
                {$module_id_condition}
        ";
        $stmt = $this->db->prepare($sql);
        if (!is_null($params['user_id'])) {
            $stmt->bindValue(':user_id', $params['user_id'], PDO::PARAM_INT);
        }
        if (!is_null($params['module_id'])) {
            $stmt->bindValue(':module_id', "%{$params['module_id']}%", PDO::PARAM_STR);
        }
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row_key => $row) {
                foreach ($row as $key => $value) {
                    if($key == 'permission')
                    if ($this->isJson($value)) {
                        $result[$row_key][$key] = json_decode($value, true);
                    }
                }
            }
            return $result;
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function readUserDetailEditorName($editor)
    {
        $sql = "SELECT name
                FROM system.\"user\"
                WHERE id = :editor
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':editor', $editor, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'editor' => $stmt->fetchColumn()
            ];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function updateUserDetail($params)
    {
        $bind_values = [
            'user_id' => 0,
            'user_name' => '',
            'email' => '',
            'gender' => '',
            'country' => '',
            'session_user' => 0,
            'edit_time' => ''
        ];
        foreach ($bind_values as $key => $value) {
            array_key_exists($key, $params) && ($bind_values[$key] = $params[$key]);
        }
        $sql = "UPDATE system.\"user\"
                SET name = :user_name, email = :email, gender = :gender, country = :country,
                    editor_id = :session_user, edit_time = :edit_time
                WHERE id = :user_id
                RETURNING editor_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($bind_values)) {
            return [
                'status' => 'success',
                'editor' => $stmt->fetchColumn()
            ];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function createUserModal($user_id, $module_id)
    {
        $bind_values = [
            'user_id' => $user_id === NULL ? 0 : $user_id,
            'module_id' => $module_id === NULL ? 0 : $module_id
        ];
        $sql = "INSERT INTO system.user_modal(uid, module_id)
                VALUES (:user_id, :module_id)
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
    public function deleteUserDetail($params)
    {
        $bind_values = ['user_id' => 0];
        foreach ($bind_values as $key => $value) {
            array_key_exists($key, $params) && ($bind_values[$key] = $params[$key]);
        }
        $sql = "DELETE FROM system.\"user\"
                WHERE id = :user_id
                RETURNING uid
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($bind_values)) {
            $uid = $stmt->fetch(PDO::FETCH_ASSOC)['uid'];
            $ldap = $this->container->ldap;
            $delete = ldap_delete($ldap['conn'], "uid=$uid,cn=users,dc=mil,dc=com,dc=tw");
            return ['status' => 'success'];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function deleteUserModal($params)
    {
        $bind_values = ['user_id' => 0];
        foreach ($bind_values as $key => $value) {
            array_key_exists($key, $params) && ($bind_values[$key] = $params[$key]);
        }
        $sql = "DELETE FROM system.user_modal
                WHERE uid = :user_id
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
    public function getMessageFileLock($data)
    {
        $sql = "SELECT file_path,is_locked
            FROM message.locked_file
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return ["status" => "failed"];
        }
    }
    public function toggleMessageFileLock($data)
    {
        $values = [
            "file_path" => "",
            "is_locked" => true
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $sql = "INSERT INTO message.locked_file(file_path,is_locked)
            VALUES (:file_path,:is_locked)
            ON CONFLICT(file_path)
            DO UPDATE SET is_locked = (locked_file.is_locked+1)%2
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return [
                "status" => "success"
            ];
        } else
            return [
                "status" => "success"
            ];
    }
    public function updatePassword($body)
    {
        $sql = "SELECT uid
                FROM system.\"user\"
                WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $body['user_id']);
        $stmt->execute();
        $uid = $stmt->fetchColumn();
        $ldap = $this->container->ldap;
        $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", "(&(uid={$uid})(userpassword={$body['oldpassword']}))");
        $info = ldap_get_entries($ldap['conn'], $sr);
        if ($info['count'] != 1) {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo(),
                'message' => '原始密碼錯誤'
            ];
        }
        if ($body['password'] != $body['password1']) {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo(),
                'message' => '密碼需與密碼確認相同'
            ];
        }
        if ($body['password'] == $body['oldpassword']) {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo(),
                'message' => '新密碼不可與原始密碼相同'
            ];
        }
        $userdata = array();
        $userdata["userpassword"] = $body['password'];
        $dn = "uid=$uid,cn=users,dc=mil,dc=com,dc=tw";
        $ldap = $this->container->ldap;
        ldap_modify($ldap['conn'], $dn, $userdata);
        return  [
            'status' => 'success',
            'message' => '修改成功'
        ];
    }
    public function readPassword($user_id)
    {
        $sql = "SELECT uid
                FROM system.\"user\"
                WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $user_id);
        $stmt->execute();
        $uid = $stmt->fetchColumn();
        $ldap = $this->container->ldap;
        $sr = ldap_search($ldap['conn'], "cn=users,dc=mil,dc=com,dc=tw", "(&(uid={$uid}))");
        $info = ldap_get_entries($ldap['conn'], $sr);
        return $info[0]['userpassword'][0];
    }
    public function convertSpreadsheetToJson($uploadedFile, $fixed_thead)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadedFile->file);
        $spreadsheet = $spreadsheet->getActiveSheet();
        $highest_row = $spreadsheet->getHighestRow();
        $json_data = [];
        for ($row = 3; $row <= $highest_row; ++$row) {
            $json_row = [];
            for ($col = 'A'; $col <= 'G'; ++$col) {
                if ($col === 'E') {
                    $json_row[$fixed_thead['E']] = $spreadsheet->getCell('E' . $row)->getValue();
                    $json_row[$fixed_thead['E']] = str_replace(',', '', $json_row[$fixed_thead['E']]);
                    $json_row[$fixed_thead['E']] = str_replace(' ', '', $json_row[$fixed_thead['E']]);
                    $json_row[$fixed_thead['E']] = str_split($json_row[$fixed_thead['E']]);
                } else {
                    $json_row[$fixed_thead[$col]] = strval($spreadsheet->getCell($col . $row)->getValue());
                }
            }
            $json_data[] = $json_row;
        }
        return $json_data;
    }
    public function postfix($data)
    {
        $home = new Home($this->db);
        // $fileName = $data['FileName'];
        // $recogUrl = "http://mil_python:8090/CustomerParts?fileName={$fileName}";
        // $result = $home->http_response($recogUrl);
        // $result = json_decode($result,true);
        // $bounding_box = json_encode($result);
        // $recogUrl = "http://mil_python:8090/PartsWithBox?fileName={$fileName}&bounding_box={$bounding_box}";
        // $result = $home->http_response($recogUrl);
        // $result = json_decode($result,true);

        // $cropfileStr = '';
        // foreach ($result['Crop_file'] as $key => $crop) {
        //     $cropfileStr .= "%22../uploads/Crop/{$crop}%22,";
        // }
        // $cropfileStr = substr_replace($cropfileStr, "", -1);

        // $values = [
        //     "customer" => "1010150",
        //     "item_type" => "07"
        // ];
        // foreach ($values as $key => $value) {
        //     array_key_exists($key,$data)&&$values[$key]=$data[$key];
        // }

        // $curl_recognition = "http://mil_python:8090/recognition/{$values['customer']}/{$values['item_type']}?top_k=10&crops={%22paths%22:[{$cropfileStr}]}";
        // $result = $home->http_response($curl_recognition);
        // $result = json_decode($result);
        // var_dump($data['FileName']);
        
        $boolfile = array_key_exists('FileName',$data)&&file_exists($this->container->upload_directory . DIRECTORY_SEPARATOR .$data['FileName'])&&!is_dir($this->container->upload_directory . DIRECTORY_SEPARATOR .$data['FileName']);
        // var_dump(array_key_exists('FileName',$data));
        // var_dump(file_exists($data['FileName']));
        // var_dump($boolfile);
        // $boolfile=false;

        if($boolfile){
            $filename =  $data['FileName'];
        }else{
            $filename = 'sample.jpg';
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
            $filename = sprintf('%s.%0.8s', $basename, $extension);
        }
       
        $values = [
            'file_name' => $filename
        ];
      

        if (array_key_exists('id', $data)) {
            $data['id'] = $data['id'];
        } else if (array_key_exists('file_id', $data)) {
            $data['id'] = $data['file_id'];
        } else {
            return;
        }

        

      

        if($boolfile){
            $recogUrl = "http://mil_python:8090/CustomerParts?fileName={$filename}";
            $result = $this->http_response($recogUrl);
            $result = json_decode($result,true);
            $bounding_box = json_encode($result);
            $recogUrl = "http://mil_python:8090/PartsWithBox?fileName={$filename}&bounding_box={$bounding_box}";
            $result = $this->http_response($recogUrl);
            $result = json_decode($result,true);
            // var_dump($result);

            
            $crop_ids = $this->setCrop($data['id'], '', $result['Crop_file'], (object)$result['Bounding_boxes']); //return img id
            // var_dump($crop_ids);
            $crops = $this->getCrops(['id' => $data['id']]);

            $result = array_merge(array(), $crops); ;
            $cropfileStr = '';
            foreach ($result as $key => $value) {
                $cropfileStr .= "%22../uploads/Crop/{$value['file_name']}%22,";
            }
            $cropfileStr = substr_replace($cropfileStr, "", -1);
            // var_dump($cropfileStr);
            $recogUrl = "http://mil_python:8090/CNNPartFilter?crops={%22paths%22:[{$cropfileStr}]}";
            $CNNPartFilter = $home->http_response($recogUrl);
            // var_dump( $CNNPartFilter );
            $CNNPartFilter = json_decode($CNNPartFilter);
            // $CNNPartFilter = json_encode($CNNPartFilter);
            $crops = [];
            foreach ($CNNPartFilter as $key => $value) {
                if($value->isPart){
                    array_push($crops,$result[$key]);
                }
            }


            $data_id = $data['id'];
            $data += ['data' => ['' => []]];
            $cropfileStr = '';

            foreach ($crops as $key => $crop) {
                array_push($data['data'][''], $crop['id']);
                $cropfileStr .= "%22../uploads/Crop/{$crop['file_name']}%22,";
            }
            $result = $this->insertComponent($data);
            // var_dump($result);
            // var_dump($cropfileStr);
            
            // $cropfileStr = substr_replace($cropfileStr, "", -1);

            $processArr = [];
            foreach ($result as $key => $value) {
                // var_dump($value);
                $processresult = $this->getProcessId($value);
                // var_dump($processresult) ;
                array_push($processArr, $processresult['process_id']);
                // var_dump($processresult);
                // var_dump($cropfileStr);
                $curl_recognition = "http://mil_python:8090/CNNPartSuggestion?top_k=5&crops={%22paths%22:[{$cropfileStr}]}";
                $CNNPartSuggestion = $this->http_response($curl_recognition);
                // var_dump( $CNNPartSuggestion );
                $CNNPartSuggestion = json_decode($CNNPartSuggestion);
                
                $CNNresult = $this->insertCNNResult(['process_id' => $processresult['process_id'], 'CNN' => $CNNPartSuggestion, 'crops' => $crops]);



                return;
            }



        }else{
            $this->setCrop($data['id'], '', $values, (object)[$values['file_name'] => [0, 0, 0, 0]]); //return img id
            $crops = $this->getCrops(['id' => $data['id']]);
            $data_id = $data['id'];
            $data += ['data' => ['' => []]];
            $cropfileStr = '';
    
            foreach ($crops as $key => $crop) {
                array_push($data['data'][''], $crop['id']);
                $cropfileStr .= "%22../uploads/Crop/{$crop['file_name']}%22,";
            }
            $result = $this->insertComponent($data);
            $cropfileStr = substr_replace($cropfileStr, "", -1);
    
            $processArr = [];
            foreach ($result as $key => $value) {
                // var_dump($value);
                $processresult = $this->getProcessId($value);
                // var_dump($processresult) ;
                array_push($processArr, $processresult['process_id']);
                $processresult += [
                    'finish' => 3,
                    'total' => 1,
                    'filename' => $values['file_name'],
                    'confidence' => 0
                ];
                $this->insertResultMatch(['data' => [$processresult]]);
                $this->insertAnnotation([
                    'id' => $data['id'],
                    'name' => $values['file_name']
                ]);
                // var_dump($processresult);
                // var_dump($cropfileStr);
                $curl_recognition = "http://mil_python:8090/CNNPartSuggestion?top_k=5&crops={%22paths%22:[{$cropfileStr}]}";

                $CNNPartSuggestion = $this->http_response($curl_recognition);
                // var_dump( $CNNPartSuggestion );
                // return;

                $CNNPartSuggestion = json_decode($CNNPartSuggestion);

                $CNNresult = $this->insertCNNResult(['process_id' => $processresult['process_id'], 'CNN' => $CNNPartSuggestion, 'crops' => $crops]);
                // var_dump($CNNresult) ;

                return;
                // var_dump($processArr) ;

                // return $processArr;

                // $resultEncode = json_encode($processresult);
                // $curl_recognition = "http://mil_python:8090/compare?data={$resultEncode}";
                // $home->http_response($curl_recognition,1);
            }
            
        }

        
        
    }
    public function insertAnnotation($data)
    {
        $values = [
            'id' => 0,
            'name' => ''
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data))
                $values[$key] = $data[$key];
        }
        $sql = "INSERT INTO public.annotation(
            id, name)
            VALUES (:id, :name)
            ON CONFLICT (id,name)
            DO NOTHING;
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->execute($values);
    }
    function get_custom_id($data)
    {
        $values = [
            "file_id" => 0
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data))
                $values[$key] = $data[$key];
        }
        $sql = "SELECT custom_id
            FROM file
            WHERE id = :file_id
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function patch_custom_id($data)
    {
        $values = [
            "file_id" => 0,
            "custom_id" => ""
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data))
                $values[$key] = $data[$key];
        }
        $sql = "UPDATE public.file
            SET custom_id = :custom_id
            WHERE id = :file_id
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function exportXlsx($data)
    {
        $spreadsheet = new Spreadsheet();
        /* head image */
        $spreadsheet->getActiveSheet()->mergeCells('A1:A3');  /* merge */
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Paid');
        $drawing->setDescription('Paid');
        $drawing->setPath($this->container->upload_directory . DIRECTORY_SEPARATOR . 'mil.png');  /* image path */
        $drawing->setWidthAndHeight(50, 50);
        $drawing->setCoordinates('A1');  /* drawing cell */
        $drawing->getShadow()->setVisible(true);
        $drawing->getShadow()->setDirection(45);
        $drawing->setWorksheet($spreadsheet->getActiveSheet());
        $customer = '';
        $currency = '';
        foreach($data as $key => $value){
            if(array_key_exists('customer',$value))
                $customer = $value['customer'];
            if(array_key_exists('currency',$value))
                $currency = $value['currency'];
            break;
        }
        /* head */
        $spreadsheet->getActiveSheet()->mergeCells('B1:F1');  /* merge */
        $spreadsheet->getActiveSheet()->mergeCells('B2:F2');
        $spreadsheet->getActiveSheet()->mergeCells('B3:F3');
        $spreadsheet->getActiveSheet()->mergeCells('G1:K1');
        $spreadsheet->getActiveSheet()->mergeCells('G2:K2');
        $spreadsheet->getActiveSheet()->mergeCells('G3:K3');
        $spreadsheet->getActiveSheet()->setCellValue('B2', '1,Chang-Tai St.,Hsiao-Kang');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'Major Industries Ltd.');  /* writing cell */
        $spreadsheet->getActiveSheet()->setCellValue('B3', 'Kaohsiung, Taiwan, R.O.C');
        $spreadsheet->getActiveSheet()->setCellValue('G1', 'Tel: 886-7-8716711');
        $spreadsheet->getActiveSheet()->setCellValue('G2', 'Fax: 886-7-8715935');
        $spreadsheet->getActiveSheet()->setCellValue('G3', 'eMail: milmajor@mil.com.tw');
        /* middle */
        $spreadsheet->getActiveSheet()->mergeCells('B4:C4');
        $spreadsheet->getActiveSheet()->mergeCells('E4:F4');
        $spreadsheet->getActiveSheet()->mergeCells('H4:K4');
        $spreadsheet->getActiveSheet()->setCellValue('A4', 'Customer:');  /* writing cell */
        $spreadsheet->getActiveSheet()->setCellValue('B4', $customer);  /* writing cell */
        $spreadsheet->getActiveSheet()->setCellValue('D4', 'enquiry:');
        $spreadsheet->getActiveSheet()->setCellValue('E4', $customer);
        $spreadsheet->getActiveSheet()->setCellValue('G4', 'Date:');
        $spreadsheet->getActiveSheet()->setCellValue('H4', date('Y/m/d'));
        /* body */
        for ($i = 'A'; $i <= 'K'; $i++) {  /* merge */
            if ($i !== 'H' && $i !== 'I') {
                $spreadsheet->getActiveSheet()->mergeCells("{$i}5:{$i}6");
            }
        }
        $body_contents = [
            [
                'Pos.', 'Ident-No.', 'Date of Drawing', 'Description', 'Material',
                'PVD', 'Qty', $currency.'/Pc.', $currency.'/Pc.', 'REMARK', 'Delivery date arriving'
            ],
            [
                '', '', '', '', '', '', '', 'CIF Beckingen', 'UPDATE PRICE', '', ''
            ]
        ];
        foreach($data as $key => $value){
            $body_contents[] = [
                ($key+1),
                empty($value['order_name'])?'':$value['order_name'],
                '',
                '',
                empty($value['material'])?'':$value['material'],
                empty($value['titanizing'])?'':$value['titanizing'],
                empty($value['num'])?'':$value['num'],
                empty($value['cost'])?'':$value['cost'],
                '',
                '',
                '',
            ];
        }
        $spreadsheet->getActiveSheet()->fromArray($body_contents, NULL, 'A5');  /* begining writing cell */
        return $spreadsheet;
    }
    function get_picture_by_order_name($data){
        if(array_key_exists("name",$data)){
            $values = ["order_name"=>$data["name"]];
            $sql = "SELECT file.\"FileName\"
                FROM file
                WHERE order_name=:order_name
                ORDER BY file.id DESC
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->execute($values);
            if($stmt->rowCount()!=0){
                return ["picture"=>$this->container->upload_directory . DIRECTORY_SEPARATOR . $stmt->fetchColumn(0)];
            }
        }else{
            return 'null.null';
        }
    }
}
function isJson($string)
{
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}
