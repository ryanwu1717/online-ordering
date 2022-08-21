<?php

use \Psr\Container\ContainerInterface;

class Video
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

    //Get video which is in schedual queue.
    public function get_video_in_queue()
    {
        $sql = "SELECT video_file_name
                FROM video.video_schedual_queue
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) 
        {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        else 
        {
            $result = ['status' => "failed"];
            var_dump($stmt->errorInfo());
        }
        return $result;
    }
    //Update video file name after transform the video which is not mp4.
    public function update_video_file_name($new_filename, $filename)
    {
        $values = [];
        $values["old_video_file_name"] = $filename;
        $values["new_video_file_name"] = $new_filename;
        $sql = "UPDATE video.video
                SET video_file_name = :new_video_file_name
                WHERE video.video.video_file_name = :old_video_file_name
                RETURNING video.video.id";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ["video_id" => $stmt->fetchAll(PDO::FETCH_ASSOC),
                       "status" => "success"
            ];
        } 
        else 
        {
            $result = ['status' => "failed"];
            var_dump($stmt->errorInfo());
        }
        return $result;
    }
    //Update video file name after transform the video which is not mp4.
    public function update_video_thumbnail_file_name($new_thumbnail_filename, $video_id)
    {
        $values = [];
        $values["new_video_thumbnail_file_name"] = $new_thumbnail_filename;
        $values["video_id"] = $video_id;
        $sql = "UPDATE video.video
                SET video_thumbnail_file_name = :new_video_thumbnail_file_name
                WHERE video.video.id = :video_id";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ['status' => "success"];
        } 
        else 
        {
            $result = ['status' => "failed"];
            var_dump($stmt->errorInfo());
        }
        return $result;
    }
    //Update video file name after transform the video which is not mp4.
    public function delete_schedual_queue($filename)
    {
        $values = [];
        $values["video_file_name"] = $filename;
        $sql = "DELETE FROM video.video_schedual_queue
                WHERE video.video_schedual_queue.video_file_name = :video_file_name";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ['status' => "success"];
        } 
        else 
        {
            $result = ['status' => "failed"];
            var_dump($stmt->errorInfo());
        }
        return $result;
    }

    //Insert video which is not mp4 and is large size into schedual queue. 
    public function video_schedual_queue($video_file_name, $video_id)
    {
        $sql = "INSERT INTO video.video_schedual_queue(video_file_name, video_id)
                VALUES (:video_file_name, :video_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_file_name', $video_file_name, PDO::PARAM_STR);
        $stmt->bindValue(':video_id', $video_id, PDO::PARAM_INT);
        if ($stmt->execute()) 
        {
            $video_file_name = ['status' => "success"];
        } 
        else 
        {
            $video_file_name = ['status' => "failed"];
            var_dump($stmt->errorInfo());
        }
        return $video_file_name;
    }

    //Preview the uploaded video.
    public function preview_video_or_file($data)
    {
        $sql = "INSERT INTO video.file_table (file_name)
                VALUES (:file_name)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_name', $data['fileName'], PDO::PARAM_STR);
        $stmt->execute();
        $video_id = array(
            'file_id' => $this->db->lastInsertId(),
            'status' => "success"
        );
        return $video_id;
    }

    //Get preview the uploaded video.
    public function get_preview_video_or_file($data)
    {
        $sql = "SELECT CASE WHEN file_name IS NULL THEN '-' 
                            ELSE file_name
                        END AS file_name
                FROM video.file_table
                WHERE file_id = :file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function get_order_processes_id($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $fk_key => $fk_value) {
            $value .= " (public.video_order_processes_fk.order_processes_fk = :order_processes_fk_{$fk_key} AND public.video_order_processes_fk.fk_value = :fk_value_{$fk_key}) OR";
            $values["order_processes_fk_{$fk_key}"] = $fk_value["fk_key"];
            $values["fk_value_{$fk_key}"] = $fk_value["fk_value"];
        }
        $values['count'] = count($data);
        $value = rtrim($value, "OR");

        $sql = "SELECT order_processes_id
                FROM(
                    SELECT public.video_order_processes_fk.order_processes_id
                    FROM public.video_order_processes_fk
                    WHERE {$value}
                )dt
                GROUP BY order_processes_id
                HAVING COUNT(*) = :count";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        if($stmt->rowCount() == 1){
            $result = $stmt->fetchColumn(0);
            return $result;
        }
        else{
            return null;
        }
    }

    public function get_video_id_by_order_processes_id($data)
    {
        $sql = "SELECT video.video_order_processes.video_id
                FROM video.video_order_processes
                WHERE video.video_order_processes.order_processes_id = :order_processes_id
                ORDER BY video.video_order_processes.video_id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_processes_id', $data, PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() == 1){
            $result = $stmt->fetchColumn(0);
            return $result;
        }
        else{
            return null;
        }
    }

    //Get the new video_id after getting order_processes_id.
    public function get_new_video_id()
    {
        $result = [];
        $temp_column = [
            "video_user_id" => null,
            "sfcta_ta001" => null,
            "sfcta_ta002" => null,
            "sfcta_ta003" => null,
            "sfcta_ta004" => null,
            "note" => "",
            "remark" => "",
            "video_file_name" => null,
            "video_thumbnail_file_name" => null,
            "video_type" => null,
            "video_name" => "",
        ];

        $sql = "INSERT INTO video.video (video_user_id, sfcta_ta001, sfcta_ta002, sfcta_ta003, sfcta_ta004, note, remark, 
                                    video_file_name, video_thumbnail_file_name, video_type, video_name)
                VALUES (:video_user_id, :sfcta_ta001, :sfcta_ta002, :sfcta_ta003, :sfcta_ta004, :note, :remark, :video_file_name, :video_thumbnail_file_name, :video_type, :video_name)
                RETURNING video.video.id";
        $stmt = $this->db->prepare($sql);
        if($stmt->execute($temp_column))
        {
            $result = array(
                'video_id' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'status' => "success"
            );
            return $result;
        } 
        else 
        {
            array_push($result, ["status" => "failed"]);
            return $result;
        }
    }

    //Insert new videos.
    public function insert_video($data)
    {
        $statement = "";
        $values = [];
        $result = [];
        $count = 0;
        foreach($data as $key => $value)
        {
            $statement .= "(:video_user_id_{$key}, :sfcta_ta001_{$key}, :sfcta_ta002_{$key}, :sfcta_ta003_{$key}, :sfcta_ta004_{$key}, :note_{$key}, :remark_{$key}, :video_file_name_{$key}, :video_thumbnail_file_name_{$key}, :video_type_{$key}, :video_name_{$key}, :update_user_id_{$key}),";
            $temp_column = [
                "video_user_id" => null,
                "sfcta_ta001" => null,
                "sfcta_ta002" => null,
                "sfcta_ta003" => null,
                "sfcta_ta004" => null,
                "note" => "",
                "remark" => "",
                "video_file_name" => null,
                "video_thumbnail_file_name" => null,
                "video_type" => null,
                "video_name" => "",
                "update_user_id" => null,
            ];
            foreach($temp_column as $temp_key => $temp_value)
            {
                $temp_column[$temp_key."_{$key}"] = $temp_value;
                if(array_key_exists($temp_key, $value)){
                    $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                }
                unset($temp_column[$temp_key]);
            }
            // $values = array_merge($temp_column, $values);
            $values = array_merge($temp_column, $values);
            $count++;
            
            if($count == 50)
            {
                $statement = rtrim($statement, ",");
                $sql = "INSERT INTO video.video (video_user_id, sfcta_ta001, sfcta_ta002, sfcta_ta003, sfcta_ta004, note, remark, 
                                            video_file_name, video_thumbnail_file_name, video_type, video_name, update_user_id)
                        VALUES {$statement} 
                        RETURNING video.video.id";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach($ids as $id_key => $id_value){
                        array_push($result, $id_value);
                    }
                } 
                else 
                {
                    array_push($result, ["status" => "failed"]);
                    return $result;
                }
                $statement = "";
                $values = [];
                $count = 0;
            }
        }
        // return $values;
        if($count != 0)
        {
            $statement = rtrim($statement, ",");
            $sql = "INSERT INTO video.video (video_user_id, sfcta_ta001, sfcta_ta002, sfcta_ta003, sfcta_ta004, note, remark, 
                                        video_file_name, video_thumbnail_file_name, video_type, video_name, update_user_id)
                    VALUES {$statement} 
                    RETURNING video.video.id";
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($values))
            {
                $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach($ids as $id_key => $id_value){
                    array_push($result, $id_value);
                }
            } 
            else 
            {
                var_dump($stmt->errorInfo());
                array_push($result, ["status" => "failed"]);
                return $result;
            }
        }
        return $result;
    }

    //Insert the video_id of each video and get the returning order_processes_id after getting every returning video_id.
    public function insert_video_id($video_id)
    {
        $statement = "";
        $values = [];
        foreach($video_id as $key => $value){
            $statement .= "(:video_id_{$key}),";
            $values["video_id_{$key}"] = $value['id'];
        }
        $statement = rtrim($statement, ",");

        $sql = "INSERT INTO video.video_order_processes(video_id)
                VALUES {$statement}
                RETURNING video.video_order_processes.order_processes_id";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ["status" => "Insert video_ids success",];
            $order_processes_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result['order_processes_ids'] = $order_processes_ids;
        } 
        else 
        {
            $result = ["status" => "Insert video_ids failed",];
            var_dump($stmt->errorInfo());
        }
        return $result;
    }

    //Insert the foreign key of each video after getting every returning order_processes_id.
    public function insert_video_foreign_key($data, $order_processes_id)
    {
        $statement = "";
        $values = [];
        $result = [];
        foreach($order_processes_id['order_processes_ids'] as $key => $value)
        {
            if(array_key_exists('foreign_key', $data[$key]))
            {   
                // return $data[$key]['foreign_key'];
                foreach($data[$key]['foreign_key'] as $foreign_key_key => $foreign_key_value)
                {    
                    $statement .= "(:order_processes_id_{$key}, :order_processes_fk_{$key}, :fk_value_{$key}),";
                    $values["order_processes_id_{$key}"] = $value['order_processes_id'];
                    $values["order_processes_fk_{$key}"] = $foreign_key_key;
                    $values["fk_value_{$key}"] = $foreign_key_value;
                    // $temp_column = [
                    //     "order_processes_id" => null,
                    //     "order_processes_fk" => "",
                    //     "fk_value" => "",
                    // ];
                    // foreach($temp_column as $temp_key => $temp_value)
                    // {
                    //     $temp_column[$temp_key."_{$key}"] = $temp_value;
                    //     if(array_key_exists($temp_key, $value)){
                    //         $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                    //     }
                    //     unset($temp_column[$temp_key]);
                    // }
                    // $values = array_merge($temp_column, $values);
                }
                $statement = rtrim($statement, ",");
                $sql = "INSERT INTO public.video_order_processes_fk(order_processes_id, order_processes_fk, fk_value)
                        VALUES {$statement}";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    $result = ["status" => "Insert foreign_keys success",];
                } 
                else 
                {
                    $result = ["status" => "Insert foreign_keys failed"];
                    var_dump($stmt->errorInfo());
                }
                $statement = "";
                $values = [];
            }
        }
        return $result;
    }

    // public function insert_video($data)
    // {
    //     $sql = "INSERT INTO video.video (video_user_id, video_file_name, video_type, video_name)
    //             VALUES (:video_user_id, :video_file_name, :video_type, :video_name) 
    //             RETURNING video.video.id";
    //     $stmt = $this->db->prepare($sql);
    //     $stmt->bindValue(':video_user_id', $data['user_id'], PDO::PARAM_INT);
    //     $stmt->bindValue(':video_file_name', $data['file_name'], PDO::PARAM_STR);
    //     $stmt->bindValue(':video_type', $data['video_type'], PDO::PARAM_INT);
    //     $stmt->bindValue(':video_name', $data['video_name'], PDO::PARAM_STR);
    //     if($stmt->execute())
    //     {
    //         $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     } 
    //     else 
    //     {
    //         array_push($result, ["status" => "failed"]);
    //         return $result;
    //     }
    //     return $result;
    // }

    //Delete the uploaded files with the file_ids.
    public function delete_upload_file($data)
    {
        $conditions = "(";
        $values = [];
        foreach($data as $key => $value)
        {
            if(array_key_exists('file_id',$value)){
                $conditions .= ":file_id_{$key},";
                $values["file_id_{$key}"] = $value['file_id'];
            }
        }
        if(count($values)===0){
            $result = ["status" => "successfully deleted",];
            return $result;
        }
        $conditions = rtrim($conditions, ",");
        $conditions .= ")";
        $sql = "DELETE FROM video.file_table WHERE video.file_table.file_id IN {$conditions}";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ["status" => "successfully delete",];
        } 
        else 
        {
            $result = ["status" => "delete failed",];
        }
        return $result;
    }

    //Delete the uploaded tape files with the file_ids.
    public function delete_upload_file_for_tape($data)
    {
        $sql = "DELETE FROM video.file_table WHERE video.file_table.file_id = :file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id'], PDO::PARAM_INT);
        if ($stmt->execute()) 
        {
            $result = ["status" => "successfully dalete",];
        } 
        else 
        {
            $result = ["status" => "delete failed",];
        }
        return $result;
    }

    public function get_video_file_name($data)
    {
        foreach($data as $key => $value){
            if($key==='id'){
                $sql = "SELECT CASE WHEN video.video.id IS NULL THEN 0 ELSE video.video.id END AS id, 
                                CASE WHEN video.video.video_user_id IS NULL THEN 0 ELSE video.video.video_user_id END AS video_user_id, 
                                CASE WHEN video.video.sfcta_ta001 IS NULL THEN '-' ELSE video.video.sfcta_ta001 END AS sfcta_ta001, 
                                CASE WHEN video.video.sfcta_ta002 IS NULL THEN '-' ELSE video.video.sfcta_ta002 END AS sfcta_ta002, 
                                CASE WHEN video.video.sfcta_ta003 IS NULL THEN '-' ELSE video.video.sfcta_ta003 END AS sfcta_ta003, 
                                CASE WHEN video.video.sfcta_ta004 IS NULL THEN '-' ELSE video.video.sfcta_ta004 END AS sfcta_ta004, 
                                CASE WHEN video.video.note IS NULL THEN '-' ELSE video.video.note END AS note, 
                                CASE WHEN video.video.remark IS NULL THEN '-' ELSE video.video.remark END AS remark, 
                                CASE WHEN video.video.video_file_name IS NULL THEN '-' ELSE video.video.video_file_name END AS video_file_name, 
                                CASE WHEN video.video.video_thumbnail_file_name IS NULL THEN '-' ELSE video.video.video_thumbnail_file_name END AS video_thumbnail_file_name, 
                                CASE WHEN video.video.last_update_time IS NULL THEN '2022-1-1 00:00:00' ELSE video.video.last_update_time END AS last_update_time,
                                CASE WHEN video.description.id IS NULL THEN 0 ELSE video.description.id END AS description_id, 
                                CASE WHEN video.description.description_user_id IS NULL THEN 0 ELSE video.description.description_user_id END AS description_user_id, 
                                CASE WHEN video.description.description_time IS NULL THEN '-' ELSE video.description.description_time END AS description_time, 
                                CASE WHEN video.description.description_content IS NULL THEN '-' ELSE video.description.description_content END AS description_content, 
                                CASE WHEN video.description.tape_file_name IS NULL THEN '-' ELSE video.description.tape_file_name END AS tape_file_name, 
                                CASE WHEN video.description.last_update_time IS NULL THEN '2022-1-1 00:00:00' ELSE video.description.last_update_time END AS description_last_update_time
                        FROM video.video
                        LEFT JOIN video.description ON video.description.video_id = video.video.id
                        WHERE video.video.id = :video_id
                        GROUP BY video.video.id, video.video.video_user_id, video.video.sfcta_ta001, video.video.sfcta_ta002, 
                                video.video.sfcta_ta003, video.video.sfcta_ta004, video.video.note, video.video.remark, 
                                video.video.video_file_name, video.video.video_thumbnail_file_name, video.video.last_update_time,
                                video.description.id, video.description.description_user_id, video.description.description_time, 
                                video.description.description_content, video.description.tape_file_name, video.description.last_update_time
                        ORDER BY video.description.description_time";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':video_id', $value, PDO::PARAM_INT);
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return $data;
            }
        }
        return [];
    }

    //Upload video thumbnail with the video_id.
    public function upload_video_thumbnail($data)
    {
        // foreach($data as $key => $value){
            $sql = "UPDATE video.video
                    SET video_thumbnail_file_name = :thumbnail_filename
                    WHERE video.video.id = :video_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':thumbnail_filename', $data['thumbnail_filename'], PDO::PARAM_STR);
            $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
            if ($stmt->execute()) 
            {
                $result = ["status" => "success",];
            } 
            else 
            {
                $result = ["status" => "failed",];
            }
            return $result;
        // }
    }

    //Upload file with the video_id.
    public function upload_video($data)
    {
        $sql = "UPDATE video.video
                SET video_user_id = :video_user_id, note = :note, video_file_name = :fileName, last_update_time = NOW()
                WHERE video.video.id = :video_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':note', $data['note'], PDO::PARAM_STR);
        $stmt->bindValue(':fileName', $data['fileName'], PDO::PARAM_STR);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->execute();
        return $data['video_id'];
    }

    //Update the videos with the video_ids.
    public function update_video($data)
    {
        $statement = "";
        $values = [];
        $result = [];
        $count = 0;
        foreach($data as $key => $value)
        {
            $statement .= "(:video_id_{$key}, :update_user_id_{$key}, :sfcta_ta001_{$key}, :sfcta_ta002_{$key}, :sfcta_ta003_{$key}, :sfcta_ta004_{$key}, :note_{$key}, :remark_{$key}, :video_file_name_{$key}, :video_thumbnail_file_name_{$key}, :video_type_{$key}, :video_name_{$key}),";
            $temp_column = [
                "video_id" => null,
                "update_user_id" => null,
                "sfcta_ta001" => null,
                "sfcta_ta002" => null,
                "sfcta_ta003" => null,
                "sfcta_ta004" => null,
                "note" => "",
                "remark" => "",
                "video_file_name" => null,
                "video_thumbnail_file_name" => null,
                "video_type" => null,
                "video_name" => "",
            ];
            foreach($temp_column as $temp_key => $temp_value)
            {
                $temp_column[$temp_key."_{$key}"] = $temp_value;
                if(array_key_exists($temp_key, $value)){
                    $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                }
                unset($temp_column[$temp_key]);
            }
            $values = array_merge($temp_column, $values);
            $count++;

            if($count == 50)
            {
                $statement = rtrim($statement, ",");
                $sql = "UPDATE video.video as video
                        SET update_user_id = CAST(change.update_user_id AS INTEGER), sfcta_ta001 = change.sfcta_ta001, sfcta_ta002 = change.sfcta_ta002, 
                            sfcta_ta003 = change.sfcta_ta003, sfcta_ta004 = change.sfcta_ta004, note = change.note, 
                            remark = change.remark, video_file_name = change.video_file_name, 
                            video_thumbnail_file_name = change.video_thumbnail_file_name, last_update_time = NOW(), 
                            video_type = CAST(change.video_type AS INTEGER), video_name = change.video_name
                        FROM 
                        (
                        VALUES {$statement}
                        ) AS change(id, update_user_id, sfcta_ta001, sfcta_ta002, sfcta_ta003, sfcta_ta004, note, remark, video_file_name, video_thumbnail_file_name, video_type, video_name) 
                        WHERE CAST(change.id AS INTEGER) = video.id
                        RETURNING video.id";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach($ids as $id_key => $id_value){
                        array_push($result, $id_value);
                    }
                } 
                else 
                {
                    var_dump($stmt->errorInfo());
                    array_push($result, ["status" => "failed"]);
                    return $result;
                }
                $statement = "";
                $values = [];
                $count = 0;
            }
        }
        if($count != 0)
        {
            $statement = rtrim($statement, ",");
            $sql = "UPDATE video.video as video
                    SET update_user_id = CAST(change.update_user_id AS INTEGER), sfcta_ta001 = change.sfcta_ta001, sfcta_ta002 = change.sfcta_ta002, 
                        sfcta_ta003 = change.sfcta_ta003, sfcta_ta004 = change.sfcta_ta004, note = change.note, 
                        remark = change.remark, video_file_name = change.video_file_name, 
                        video_thumbnail_file_name = change.video_thumbnail_file_name, last_update_time = NOW(), 
                        video_type = CAST(change.video_type AS INTEGER), video_name = change.video_name
                    FROM 
                    (
                    VALUES {$statement}
                    ) AS change(id, update_user_id, sfcta_ta001, sfcta_ta002, sfcta_ta003, sfcta_ta004, note, remark, video_file_name, video_thumbnail_file_name, video_type, video_name) 
                    WHERE CAST(change.id AS INTEGER) = video.id
                    RETURNING video.id";
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($values))
            {
                $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach($ids as $id_key => $id_value){
                    array_push($result, $id_value);
                }
            } 
            else 
            {
                var_dump($stmt->errorInfo());
                array_push($result, ["status" => "failed"]);
                return $result;
            }
            $statement = "";
            $values = [];
            $count = 0;
        }
        return $result;
    }

    public function get_multiple_video_file_name($data)
    {
        $conditions = "(";
        $values = [];
        foreach($data as $key => $value)
        {
            $conditions .= ":video_id_{$key},";
            $values["video_id_{$key}"] = $value['video_id'];
        }
        $conditions = rtrim($conditions, ",");
        $conditions .= ")";
        $sql = "SELECT CASE WHEN video.video.video_file_name IS NULL THEN '-' ELSE video.video.video_file_name END AS video_file_name, 
                        CASE WHEN video.video.video_thumbnail_file_name IS NULL THEN '-' ELSE video.video.video_thumbnail_file_name END AS video_thumbnail_file_name
                FROM video.video
                WHERE video.video.id IN {$conditions}
                GROUP BY video.video.video_file_name, video.video.video_thumbnail_file_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    //Delete the specific video with the video_id.
    public function delete_video($data)
    {
        $conditions = "(";
        $values = [];
        foreach($data as $key => $value)
        {
            $conditions .= ":video_id_{$key},";
            $values["video_id_{$key}"] = $value['video_id'];
        }
        $conditions = rtrim($conditions, ",");
        $conditions .= ")";
        $sql = "DELETE FROM video.video WHERE video.video.id IN {$conditions}";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
        }
        return $result;
    }

    //Get the information of the specific video with the video_id.
    public function get_video_information($data)
    {
        $sql = "SELECT CASE WHEN video.video.id IS NULL THEN 0 ELSE video.video.id END AS id, 
                        CASE WHEN video.video.video_user_id IS NULL THEN 0 ELSE video.video.video_user_id END AS video_user_id, 
                        CASE WHEN system.user.name IS NULL THEN '-' ELSE system.user.name END AS name, 
                        CASE WHEN video.video.sfcta_ta001 IS NULL THEN '-' ELSE video.video.sfcta_ta001 END AS sfcta_ta001, 
                        CASE WHEN video.video.sfcta_ta002 IS NULL THEN '-' ELSE video.video.sfcta_ta002 END AS sfcta_ta002, 
                        CASE WHEN video.video.sfcta_ta003 IS NULL THEN '-' ELSE video.video.sfcta_ta003 END AS sfcta_ta003, 
                        CASE WHEN video.video.sfcta_ta004 IS NULL THEN '-' ELSE video.video.sfcta_ta004 END AS sfcta_ta004, 
                        CASE WHEN video.video.note IS NULL THEN '-' ELSE video.video.note END AS note, 
                        CASE WHEN video.video.remark IS NULL THEN '-' ELSE video.video.remark END AS remark, 
                        CASE WHEN video.video.video_file_name IS NULL THEN '-' ELSE video.video.video_file_name END AS video_file_name, 
                        CASE WHEN video.video.video_thumbnail_file_name IS NULL THEN '-' ELSE video.video.video_thumbnail_file_name END AS video_thumbnail_file_name, 
                        CASE WHEN video.video.last_update_time IS NULL THEN '2022-1-1 00:00:00' 
                            ELSE  to_char(video.video.last_update_time, 'YYYY-MM-DD HH24:MI:SS')
                        END AS last_update_time,
                        CASE WHEN video.video.video_type IS NULL THEN 0 ELSE video.video.video_type END AS video_type_id,
                        CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS video_type_name,
                        CASE WHEN video.video.video_name IS NULL THEN '-' ELSE video.video.video_name END AS video_name
                -- 		,video.description.id, video.description.description_user_id, video.description.description_time, 
                -- 		video.description.description_content, video.description.tape_file_name, video.description.last_update_time
                FROM video.video
                -- LEFT JOIN video.description ON video.description.video_id = video.video.id
                LEFT JOIN system.user ON system.user.id = video.video.video_user_id
                LEFT JOIN video.video_type ON video.video_type.id = video.video.video_type
                WHERE video.video.id = :video_id
                GROUP BY video.video.id, video.video.video_user_id, system.user.name, video.video.sfcta_ta001, video.video.sfcta_ta002, 
                        video.video.sfcta_ta003, video.video.sfcta_ta004, video.video.note, video.video.remark, 
                        video.video.video_file_name, video.video.video_thumbnail_file_name, video.video.last_update_time,
                        video.video.video_type, video.video_type.name, video.video.video_name
                -- 		,video.description.id, video.description.description_user_id, video.description.description_time, 
                -- 		video.description.description_content, video.description.tape_file_name, video.description.last_update_time
                -- ORDER BY video.description.description_time
                ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    //Get the file name of the specific video with the video_id.
    // public function get_specific_video_file_name($data)
    // {
    //     $sql = "SELECT video.video.video_file_name
    //             FROM video.video
    //             WHERE video.video.id = :id";
    //     $stmt = $this->db->prepare($sql);
    //     $stmt->bindValue(':id', $data['video_id'], PDO::PARAM_INT);
    //     $stmt->execute();
    //     $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $data;
    // }

    //Upload the tape or mp3 description of the specific video.
    public function upload_description_tape($data)
    {
        $sql = "INSERT INTO video.description (video_id, description_user_id, description_time, tape_file_name)
                VALUES (:video_id, :description_user_id, :description_time, :tape_file_name)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->bindValue(':description_user_id', $data['description_user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':description_time', $data['description_time'], PDO::PARAM_STR);
        $stmt->bindValue(':tape_file_name', $data['tape_file_name'], PDO::PARAM_STR);
        $stmt->execute();
        $description_id = array(
            'description_id' => $this->db->lastInsertId(),
            'status' => "success"
        );
        return $description_id;
    }

    //Upload the text description description of the specific video.
    public function upload_description($data)
    {
        $statement = "";
        $values = [];
        $result = [];
        $count = 0;
        foreach($data as $key => $value)
        {
            $statement .= "(:video_id_{$key}, :description_user_id_{$key}, :description_time_{$key}, :description_content_{$key}),";
            $temp_column = [
                "video_id" => null,
                "description_user_id" => null,
                "description_time" => "",
                "description_content" => "",
            ];
            foreach($temp_column as $temp_key => $temp_value)
            {
                $temp_column[$temp_key."_{$key}"] = $temp_value;
                if(array_key_exists($temp_key, $value)){
                    $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                }
                unset($temp_column[$temp_key]);
            }
            $values = array_merge($temp_column, $values);
            $count++;

            if($count == 50)
            {
                $statement = rtrim($statement, ",");
                $sql = "INSERT INTO video.description (video_id, description_user_id, description_time, description_content)
                        VALUES {$statement}
                        RETURNING video.description.id";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach($ids as $id_key => $id_value){
                        array_push($result, $id_value);
                    }
                } 
                else 
                {
                    array_push($result, ["status" => "failed"]);
                    return $result;
                }
                $statement = "";
                $values = [];
                $count = 0;
            }
        }
        if($count != 0)
        {
            $statement = rtrim($statement, ",");
            $sql = "INSERT INTO video.description (video_id, description_user_id, description_time, description_content)
                    VALUES {$statement}
                    RETURNING video.description.id";
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($values))
            {
                $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach($ids as $id_key => $id_value){
                    array_push($result, $id_value);
                }
            } 
            else 
            {
                var_dump($stmt->errorInfo());
                array_push($result, ["status" => "failed"]);
                return $result;
            }
        }
        return $result;
    }

    //Update the tape or mp3 description of the specific video with the description_id.
    public function update_description_tape($data)
    {
        $sql = "UPDATE video.description
                SET description_user_id = :description_user_id, description_time = :description_time, tape_file_name = :tape_file_name,
                    last_update_time = NOW()
                WHERE video.description.id = :description_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':description_user_id', $data['description_user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':description_time', $data['description_time'], PDO::PARAM_STR);
        $stmt->bindValue(':tape_file_name', $data['tape_file_name'], PDO::PARAM_STR);
        $stmt->bindValue(':description_id', $data['description_id'], PDO::PARAM_INT);
        if ($stmt->execute()) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
        }
        var_dump($stmt->errorInfo());
        return $result;
    }

    //Update the text description description of the specific video with the description_id.
    public function update_description($data)
    {
        $statement = "";
        $values = [];
        $result = [];
        $count = 0;
        foreach($data as $key => $value)
        {
            $statement .= "(:description_id_{$key}, :video_id_{$key}, :description_user_id_{$key}, :description_time_{$key}, 
                            :description_content_{$key}),";
            $temp_column = [
                "description_id" => null,
                "video_id" => null,
                "description_user_id" => null,
                "description_time" => "",
                "description_content" => "",
            ];
            foreach($temp_column as $temp_key => $temp_value)
            {
                $temp_column[$temp_key."_{$key}"] = $temp_value;
                if(array_key_exists($temp_key, $value)){
                    $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                }
                unset($temp_column[$temp_key]);
            }
            $values = array_merge($temp_column, $values);
            $count++;

            if($count == 50)
            {
                $statement = rtrim($statement, ",");
                $sql = "UPDATE video.description as description
                        SET video_id = CAST(change.video_id AS INTEGER), description_user_id = CAST(change.description_user_id AS INTEGER), 
                            description_time = change.description_time, description_content = change.description_content, 
                            last_update_time = NOW()
                        FROM 
                        (
                        VALUES {$statement}
                        ) AS change(id, video_id, description_user_id, description_time, description_content) 
                        WHERE CAST(change.id AS INTEGER) = description.id";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    array_push($result, ["status" => "Update 50 rows successfully"]);
                } 
                else 
                {
                    array_push($result, ["status" => "failed"]);
                    return $result;
                }
                $statement = "";
                $values = [];
                $count = 0;
            }
        }
        if($count != 0)
        {
            $statement = rtrim($statement, ",");
            $sql = "UPDATE video.description as description
                    SET video_id = CAST(change.video_id AS INTEGER), description_user_id = CAST(change.description_user_id AS INTEGER), 
                        description_time = change.description_time, description_content = change.description_content, 
                        last_update_time = NOW()
                    FROM 
                    (
                    VALUES {$statement}
                    ) AS change(id, video_id, description_user_id, description_time, description_content) 
                    WHERE CAST(change.id AS INTEGER) = description.id";
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($values))
            {
                array_push($result, ["status" => "Update rows successfully"]);
            } 
            else 
            {
                array_push($result, ["status" => "failed"]);
                var_dump($stmt->errorInfo());
                return $result;
            }
            $statement = "";
            $values = [];
            $count = 0;
        }
        return $result;
    }

    //Delete the tape or mp3 description or the text description description of the specific video with the description_id.
    public function delete_description($data)
    {
        $conditions = "(";
        $values = [];
        foreach($data as $key => $value)
        {
            $conditions .= ":description_id_{$key},";
            $values["description_id_{$key}"] = $value['description_id'];
        }
        $conditions = rtrim($conditions, ",");
        $conditions .= ")";
        $sql = "DELETE FROM video.description WHERE video.description.id IN {$conditions}";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
        }
        return $result;
    }

    //Get the information of the specific video, like the file name of video clip description, text description, etc.
    public function get_specific_video_description($data)
    {
        $sql = "SELECT 	CASE WHEN video.description.id IS NULL THEN 0 ELSE video.description.id END AS description_id, 
                        CASE WHEN video.description.description_user_id IS NULL THEN 0 ELSE video.description.description_user_id END AS description_user_id, 
                        CASE WHEN video.description.description_time IS NULL THEN '-' ELSE video.description.description_time END AS description_time, 
                        CASE WHEN video.description.description_content IS NULL THEN '-' ELSE video.description.description_content END AS description_content, 
                        CASE WHEN video.description.tape_file_name IS NULL THEN '-' ELSE video.description.tape_file_name END AS tape_file_name, 
                        CASE WHEN video.description.last_update_time IS NULL THEN '2022-1-1 00:00:00' 
                            ELSE to_char(video.description.last_update_time, 'YYYY-MM-DD HH24:MI:SS')
                        END AS description_last_update_time
                FROM video.description
                WHERE video.description.video_id = :video_id
                ORDER BY video.description.description_time ASC, video.description.id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    //Get the languages for the user so that they can choose which language they want or get the google translate codes for translation.
    public function languages($data)
    {
        if(array_key_exists('language_chosen', $data))
        {
            $sql = "SELECT CASE WHEN video.language.id IS NULL THEN 0 ELSE video.language.id END AS id, 
                            CASE WHEN video.language.google_translate_code IS NULL THEN '-' ELSE video.language.google_translate_code END AS google_translate_code, 
                            CASE WHEN video.language.language_name IS NULL THEN '-' ELSE video.language.language_name END AS language_name
                    FROM video.language
					WHERE video.language.id IN (:language_chosen)";
        }
        else
        {
            $sql = "SELECT CASE WHEN video.language.id IS NULL THEN 0 ELSE video.language.id END AS id, 
                            CASE WHEN video.language.google_translate_code IS NULL THEN '-' ELSE video.language.google_translate_code END AS google_translate_code, 
                            CASE WHEN video.language.language_name IS NULL THEN '-' ELSE video.language.language_name END AS language_name
                    FROM video.language";
        }
        $stmt = $this->db->prepare($sql);
        if(array_key_exists('language_chosen', $data))
        {
            $stmt->bindValue(':language_chosen', $data['language_chosen'], PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    //Get the information of the specific video with the video_id.
    public function get_video_note($data)
    {
        $sql = "SELECT CASE WHEN video.video.id IS NULL THEN 0 ELSE video.video.id END AS id, 
                        CASE WHEN video.video.note IS NULL THEN '-' ELSE video.video.note END AS note, 
                        CASE WHEN video.video.remark IS NULL THEN '-' ELSE video.video.remark END AS remark
                FROM video.video
                WHERE video.video.id = :video_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    //Get the translations for checking whether there is any translation for the specific video.
    public function translations($data)
    {
        $sql = "SELECT COUNT(video.note.language_id) AS counting
                FROM video.note
                LEFT JOIN video.language ON video.language.id = video.note.language_id
                WHERE video.note.video_id = :video_id AND video.note.language_id IN (1,2)
                GROUP BY video.note.language_id, video.language.google_translate_code, video.language.language_name, 
                        video.note.note_content";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    //Delete the selected google translation of the specific video first so as to insert new translation after deleting them.
    public function delete_translation($data)
    {
        $sql = "DELETE FROM video.note WHERE video.note.video_id = :video_id AND video.note.language_id NOT IN (1,2)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        // $stmt->bindValue(':language_chosen', $data['language_chosen'], PDO::PARAM_STR);
        if ($stmt->execute()) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
        }
        return $result;
    }

    //Insert the new translations after google translation.
    public function insert_translation($data)
    {
        $sql = "INSERT INTO video.note(video_id, language_id, note_content) 
                VALUES (:video_id, :language_id, :note_content)
                ON CONFLICT (video_id, language_id)
                DO UPDATE SET note_content = :note_content
                WHERE video.note.video_id = :video_id AND video.note.language_id = :language_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->bindValue(':language_id', $data['language'], PDO::PARAM_INT);
        foreach($data['translation'] as $key => $value){
            $stmt->bindValue(':note_content', $value, PDO::PARAM_STR);
        }
        if ($stmt->execute()) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
            var_dump($stmt->errorInfo());
        }
        return $result;
    }

    //Insert the new translations after google translation.
    // public function insert_translation($data)
    // {
    //     $sql = "INSERT INTO video.note(video_id, language_id, note_content) 
    //             VALUES (:video_id, :language_id, :note_content)
    //             ON CONFLICT (:video_id, :language_id)
    //             DO UPDATE SET note_content = :note_content
    //             WHERE video.note.video_id = :video_id AND video.note.language_id = :language_id";
    //     $stmt = $this->db->prepare($sql);
    //     $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
    //     $stmt->bindValue(':language_id', $data['language'], PDO::PARAM_INT);
    //     $stmt->bindValue(':note_content', $data['translation'], PDO::PARAM_STR);
    //     if ($stmt->execute()) 
    //     {
    //         $result = ["status" => "success",];
    //     } 
    //     else 
    //     {
    //         $result = ["status" => "failed",];
    //     }
    //     return $result;
    // }

    //Get the translations for the specific video.
    public function get_translations($data)
    {
        $sql = "SELECT CASE WHEN video.video.id IS NULL THEN 0 ELSE video.video.id END AS id, 
                        CASE WHEN video.video.note IS NULL THEN '-' ELSE video.video.note END AS note, 
                        CASE WHEN video.note.language_id IS NULL THEN 0 ELSE video.note.language_id END AS language_id, 
                        CASE WHEN video.note.note_content IS NULL THEN '-' ELSE video.note.note_content END AS note_content, 
                        CASE WHEN video.language.language_name IS NULL THEN '-' ELSE video.language.language_name END AS language_name
                FROM video.video
                LEFT JOIN video.note ON video.note.video_id = video.video.id
                LEFT JOIN video.language ON video.language.id = video.note.language_id
                WHERE video.note.video_id = :video_id AND video.note.language_id IN (1,2)
                GROUP BY video.video.id, video.video.note, video.note.language_id, video.note.note_content, video.language.language_name
";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

     //Get the sfcta_ta of the specific video.
    public function get_sfcta_ta($data)
    {
        $sql = "SELECT	CASE WHEN video.video.sfcta_ta001 IS NULL THEN '-' ELSE video.video.sfcta_ta001 END AS sfcta_ta001, 
                        CASE WHEN video.video.sfcta_ta002 IS NULL THEN '-' ELSE video.video.sfcta_ta002 END AS sfcta_ta002, 
                        CASE WHEN video.video.sfcta_ta003 IS NULL THEN '-' ELSE video.video.sfcta_ta003 END AS sfcta_ta003, 
                        CASE WHEN video.video.sfcta_ta004 IS NULL THEN '-' ELSE video.video.sfcta_ta004 END AS sfcta_ta004
                FROM video.video
                WHERE video.video.id = :video_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    //Get the Microsoft_SQL of the sfcta_ta pictures. 
    public function get_sfcta_ta_picture_Microsoft_SQL($data)
    {
        foreach($data as $key => $value)
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
                    ['sql' =>
                    "SELECT [MIL].[dbo].[MOCTA].[TA001], [MIL].[dbo].[MOCTA].[TA002], [MIL].[dbo].[COPTC].[TC004], [MIL].[dbo].[COPTD].[TD201]
                    FROM [MIL].[dbo].[SFCTA]
                    LEFT JOIN [MIL].[dbo].[MOCTA] ON [MIL].[dbo].[MOCTA].[TA001] = [MIL].[dbo].[SFCTA].[TA001] AND 
                                [MIL].[dbo].[MOCTA].[TA002] = [MIL].[dbo].[SFCTA].[TA002]
                    LEFT JOIN [MIL].[dbo].[COPTD] ON [MIL].[dbo].[COPTD].[TD001] = [MIL].[dbo].[MOCTA].[TA026] AND
                                [MIL].[dbo].[COPTD].[TD002] = [MIL].[dbo].[MOCTA].[TA027] AND 
                                [MIL].[dbo].[COPTD].[TD003] = [MIL].[dbo].[MOCTA].[TA028]
                    LEFT JOIN [MIL].[dbo].[COPTC] ON [MIL].[dbo].[COPTC].[TC001] = [MIL].[dbo].[COPTD].[TD001] AND 
                                [MIL].[dbo].[COPTC].[TC002] = [MIL].[dbo].[COPTD].[TD002]
                    WHERE [MIL].[dbo].[SFCTA].[TA001] = {$value['sfcta_ta001']} AND [MIL].[dbo].[SFCTA].[TA002] = {$value['sfcta_ta002']}
                    GROUP BY [MIL].[dbo].[MOCTA].[TA001], [MIL].[dbo].[MOCTA].[TA002], [MIL].[dbo].[COPTC].[TC004], [MIL].[dbo].[COPTD].[TD201]
                    ORDER BY [MIL].[dbo].[MOCTA].[TA001], [MIL].[dbo].[MOCTA].[TA002], [MIL].[dbo].[COPTC].[TC004], [MIL].[dbo].[COPTD].[TD201]"]
                )
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $head = curl_exec($ch);
            $result = json_decode($head, true);
            // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $result;
        }
    }

    //Combine the returned Microsoft SQL result and Postgresql and select the file name of the picture of the specific video out.
    // public function get_sfcta_ta_picture_file_name($data)
    // {
    //     $file_name = json_encode($data['microsoft_sql']);
    //     $sql = "SELECT video.video.id, mil.coptc_tc004, mil.coptd_td201
    //             FROM video.video
    //             LEFT JOIN
    //             (
    //                 SELECT mocta_ta001, mocta_ta002, coptc_tc004, coptd_td201
    //                 FROM json_to_recordset(
    //                     '{$file_name}'
    //                 ) as setting_titanizing(mocta_ta001 text, mocta_ta002 text, coptc_tc004 text, coptd_td201 text)
    //             )AS mil ON mil.mocta_ta001 = video.video.sfcta_ta001 AND mil.mocta_ta002 = video.video.sfcta_ta002
    //             WHERE video.video.id = :video_id
    //             GROUP BY video.video.id, mil.coptc_tc004, mil.coptd_td201
    //             ORDER BY mil.coptc_tc004, mil.coptd_td201";
    //     $stmt = $this->db->prepare($sql);
    //     $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
    //     $stmt->execute();
    //     return $stmt->fetchAll(PDO::FETCH_ASSOC);
    // }

    //Get the video types.
    public function get_video_type()
        {
        //    $sql = "SELECT CASE WHEN video.video_type.id IS NULL THEN 0 ELSE video.video_type.id END AS id, 
        //                   CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS name
        //            FROM video.video_type
        //            ORDER BY 
        //                 CASE video.video_type.name
        //                     WHEN '大活動' THEN 1
        //                     WHEN '加工' THEN 2
        //                     WHEN '員工教育訓練' THEN 3
        //                     ELSE 4
        //                 END";
        $sql = "SELECT CASE WHEN video.video_type.id IS NULL THEN 0 ELSE video.video_type.id END AS id, 
                        CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS name,
                        CASE WHEN video.video_type.order IS NULL THEN 0 ELSE video.video_type.order END AS order
                FROM video.video_type
                ORDER BY video.video_type.order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    //Get the video types with order.
    public function get_video_type_order()
        {
        //    $sql = "SELECT CASE WHEN video.video_type.id IS NULL THEN 0 ELSE video.video_type.id END AS id, 
        //                   CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS name
        //            FROM video.video_type
        //            ORDER BY 
        //                 CASE video.video_type.name
        //                     WHEN '大活動' THEN 1
        //                     WHEN '加工' THEN 2
        //                     WHEN '員工教育訓練' THEN 3
        //                     ELSE 4
        //                 END";
        $sql = "SELECT * FROM(
                    SELECT CASE WHEN video.video_type.id IS NULL THEN 0 ELSE video.video_type.id END AS id, 
                            CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS name,
                            ROW_NUMBER() OVER (ORDER BY video.video_type.order ASC) AS order
                    FROM video.video_type
                    WHERE video.video_type.name != '其他'
                    ORDER BY video.video_type.order
                ) AS without_other
                
                UNION ALL
                
                SELECT * FROM(
                    SELECT CASE WHEN video.video_type.id IS NULL THEN 0 ELSE video.video_type.id END AS id, 
                            CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS name,
                            (SELECT COUNT(*) AS total FROM video.video_type) AS order
                    FROM video.video_type
                    WHERE video.video_type.name = '其他'
                ) AS with_other";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    //Get the video_id by foreign keys first.
   public function get_video_id_by_foreign_key($data)
   {
       $values = [];
       $conditions = "(";
        foreach ($data['foreign_key'] as $key => $value) {
            if($value != "" || $value != null){
                $conditions .= "(:{$key}, :{$key}_value),";
                $values[$key] = $key;
                $values["{$key}_value"] = $value;
            }
        }
        $conditions = rtrim($conditions, ",");
        $conditions .= ")";

        $sql = "SELECT CASE WHEN video.video_order_processes.video_id IS NULL THEN 0 ELSE video.video_order_processes.video_id END AS video_id
                FROM video.video_order_processes
                LEFT JOIN public.video_order_processes_fk ON public.video_order_processes_fk.order_processes_id = video.video_order_processes.order_processes_id
                WHERE (public.video_order_processes_fk.order_processes_fk, public.video_order_processes_fk.fk_value) IN {$conditions}
                GROUP BY video.video_order_processes.video_id
                ORDER BY video.video_order_processes.video_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
   }
    
    //Get the Microsoft_SQL of the mw001 and mw002 or manufacturing_id. 
    public function get_groups_of_videos_Microsoft_SQL()
    {
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        // curl_setopt($ch, CURLOPT_URL, "http://localhost/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                    ['sql' =>
                    "SELECT [MW001], [MW002]
                FROM [MIL].[dbo].[CMSMW]"]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }

    //Get the videos of the groups by server-side.
     public function get_groups_of_videos($groups, $data, $foreign_key, $multiple_line = true, $garbage = false)
     {
        //Initialize current page and size.
        $result = [];
        $values = [];
        $Value = [
           'cur_page' => 1,
           'size' => 5,
           'row_size' => 5
        ];
        //Changing current page.
        foreach ($data as $key => $value) {
            $Value[$key] = $value;
        }
        $length = $Value['size'] * $Value['cur_page'];
        $start = $length - $Value['size'];
        $values["length"] = $length;
        $values["start"] = $start;

        $garbage_condition = " AND video.video.in_garbage IS FALSE";
        $garbage_column = "";
        $garbage_column_for_size = "";
        if($garbage){
            $garbage_condition = " AND video.video.in_garbage IS TRUE";
            $garbage_column = ",CASE WHEN video.video.delete_user_id IS NULL THEN 0 ELSE video.video.delete_user_id END AS delete_user_id, 			
                                CASE WHEN delete_user.name IS NULL THEN '-' ELSE delete_user.name END AS delete_user_name,
                                CASE WHEN video.video.insert_garbage_time IS NULL THEN '--' 
                                     ELSE to_char(video.video.insert_garbage_time, 'YYYY-MM-DD HH24:MI:SS')
                                END AS insert_garbage_time,
                                to_char((video.video.insert_garbage_time + '30 days'), 'YYYY-MM-DD HH24:MI:SS') AS delete_expiration_date,
                                CONCAT('還剩', to_char(((video.video.insert_garbage_time + '30 days') - NOW()), 'DD'), '天，影片將永久刪除') AS remaining_days";
            $garbage_column_for_size = ", delete_user.name, video.video.insert_garbage_time";
        }

        // $conditions = "WHERE video.video.last_update_time IS NOT NULL ";
        $conditions = "WHERE TRUE ";
        $order_by = "";
        $order_by_first = " DESC";
        $order_by_last = " DESC";
        $order_by_video_type = " ASC";
        $order_by_video_name = " ASC";
        $order_by_views = " DESC";
        
        if(!empty($foreign_key)){
            $conditions .= " AND video.video.id IN (";
            foreach($foreign_key as $key => $value){
                // if(array_key_exists('video_id', $value)){
                $conditions .= ":video_{$key},";
                $values["video_{$key}"] = $value['video_id'];
                // }
            }
            $conditions = rtrim($conditions, ",");
            $conditions .= ")";
        }

        if(array_key_exists('videoType', $data)){
            $conditions .= " AND video.video.video_type = :video_type ";
            $values["video_type"] = $data['videoType'];
        }
        if(array_key_exists('manufacturing_id', $data)){
            $conditions .= " AND mil.cmsmw_mw001 IN ( ";
            foreach((array)$data["manufacturing_id"] as $key => $value)
            {
                $conditions .= ":manufacturing_id_{$key},";
                $values["manufacturing_id_{$key}"] = $value;
            }
            $conditions = rtrim($conditions, ",");
            $conditions .= ")";
        }
        if(array_key_exists('text', $data)){
            $conditions .= " AND (mil.cmsmw_mw002 LIKE '%' || :text || '%' OR 
                                    video.video.video_name LIKE '%' || :text || '%' OR 
                                    video.video_type.name LIKE '%' || :text || '%' OR 
                                    system.user.name LIKE '%' || :text || '%' OR 
                                    CAST(video.video.last_update_time AS TEXT) LIKE '%' || :text || '%' OR 
                                    CAST(video.video.first_insert_time AS TEXT) LIKE '%' || :text || '%')";
            $values["text"] = $data['text'];
        }
        if(array_key_exists('time_order_first', $data)){
            $order_by_first = $data['time_order_first'];
            $order_by = "ORDER BY selection.first_insert_time {$order_by_first}";
        }
        if(array_key_exists('time_order_last', $data)){
            $order_by_last = $data['time_order_last'];
            $order_by = "ORDER BY selection.last_update_time {$order_by_last}";
        }
        if(array_key_exists('time_order_video_type', $data)){
            $order_by_video_type = $data["time_order_video_type"];
            $order_by = "ORDER BY selection.video_type {$order_by_video_type}";
        }
        if(array_key_exists('time_order_video_name', $data)){
            $order_by_video_name = $data["time_order_video_name"];
            $order_by = "ORDER BY selection.video_name {$order_by_video_name}";
        }
        if(array_key_exists('order_views', $data)){
            $order_by_video_name = $data["order_views"];
            $order_by = "ORDER BY selection.views {$order_by_views}";
        }
        if($order_by === ""){
            $order_by .= "ORDER BY selection.video_id DESC";
        }
        else{
            $order_by .= " , selection.video_id DESC";
        }

        $id = json_encode($groups);
        
        //The method of implementing server-side by sql.
        //Select the total datas in current page, then select the top 5(for example) result outside the sql inside.
        $sql = "SELECT *
                FROM
                (
                    SELECT '/develop/videos/thumbnail/' || video.video.id as src, 
                            CASE WHEN video.video.id IS NULL THEN 0 ELSE video.video.id END AS video_id, 
                            CASE WHEN video.video.video_type IS NULL THEN 0 ELSE video.video.video_type END AS video_type, 
                            CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS video_type_name, 
                            CASE WHEN mil.cmsmw_mw001 IS NULL THEN '-' ELSE mil.cmsmw_mw001 END AS cmsmw_mw001, 
							CASE WHEN mil.cmsmw_mw002 IS NULL THEN '-' ELSE mil.cmsmw_mw002 END AS cmsmw_mw002,
							CASE WHEN video.video.video_name IS NULL THEN '-' ELSE video.video.video_name END AS video_name, 
							CASE WHEN video.video.video_user_id IS NULL THEN 0 ELSE video.video.video_user_id END AS video_user_id, 
                            CASE WHEN system.user.name IS NULL THEN '-' ELSE system.user.name END AS user_name,
                            CASE WHEN video.video.sfcta_ta001 IS NULL THEN '-' ELSE video.video.sfcta_ta001 END AS sfcta_ta001, 
							CASE WHEN video.video.sfcta_ta002 IS NULL THEN '-' ELSE video.video.sfcta_ta002 END AS sfcta_ta002, 
							CASE WHEN video.video.sfcta_ta003 IS NULL THEN '-' ELSE video.video.sfcta_ta003 END AS sfcta_ta003, 
							CASE WHEN video.video.sfcta_ta004 IS NULL THEN '-' ELSE video.video.sfcta_ta004 END AS sfcta_ta004, 			
                            CASE WHEN video.video.update_user_id IS NULL THEN 0 ELSE video.video.update_user_id END AS update_user_id, 			
                            CASE WHEN update_user.name IS NULL THEN '-' ELSE update_user.name END AS update_user_name,
                            CASE WHEN video.video.last_update_time IS NULL THEN '--' 
                                 ELSE to_char(video.video.last_update_time, 'YYYY-MM-DD HH24:MI:SS')
                            END AS last_update_time,
                            CASE WHEN video.video.first_insert_time IS NULL THEN '--' 
                                 ELSE to_char(video.video.first_insert_time, 'YYYY-MM-DD HH24:MI:SS')
                            END AS first_insert_time,
							CASE WHEN video.video.video_file_name IS NULL THEN '-' ELSE video.video.video_file_name END AS video_file_name, 
							CASE WHEN video.video.video_thumbnail_file_name IS NULL THEN '-' ELSE video.video.video_thumbnail_file_name END AS video_thumbnail_file_name,
                            ROW_NUMBER() OVER (ORDER BY video.video.first_insert_time DESC, video.video.video_type DESC) AS row_num,
                            CASE WHEN video.video.remark IS NULL THEN '-' ELSE video.video.remark END AS remark,			
                            CASE WHEN video.video_views.views IS NULL THEN 0 ELSE video.video_views.views END AS views
                            {$garbage_column}
                    FROM video.video
                    LEFT JOIN
                    (
                        SELECT cmsmw_mw001, cmsmw_mw002
                        FROM json_to_recordset(
                            '{$id}'
                        ) as setting_titanizing(cmsmw_mw001 text, cmsmw_mw002 text)
                    )AS mil ON mil.cmsmw_mw001 = video.video.sfcta_ta004
                    LEFT JOIN video.video_type ON video.video_type.id = video.video.video_type
                    LEFT JOIN system.user ON system.user.id = video.video.video_user_id 	
                    LEFT JOIN system.user update_user ON update_user.id = video.video.update_user_id 	
                    LEFT JOIN system.user delete_user ON delete_user.id = video.video.delete_user_id 	
                    LEFT JOIN video.video_views ON video.video_views.video_id = video.video.id
                    {$conditions} {$garbage_condition}
                    GROUP BY video.video.id, video.video.video_type, video.video_type.name, mil.cmsmw_mw001, mil.cmsmw_mw002, video.video.id, video.video.video_name, 
                            video.video.video_user_id, video.video.sfcta_ta001, video.video.sfcta_ta002, video.video.sfcta_ta003, video.video.sfcta_ta004, 
                            video.video.last_update_time, video.video.video_file_name, system.user.name, video.video.video_thumbnail_file_name, video.video.first_insert_time, 
                            video.video.update_user_id, update_user.name, video.video_views.views {$garbage_column_for_size}
                    LIMIT :length
                ) AS selection
                WHERE selection.row_num > :start
                {$order_by}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if($multiple_line){
            $revserse = [];
            $revserse_temp = [];
            foreach ($result['data'] as $key => $data_value) {
                array_push($revserse_temp,$data_value);
                if($key!==0 && ($key+1)%$Value['row_size']==0){
                    array_push($revserse,$revserse_temp);
                    $revserse_temp = [];
                }
                if($key === count($result['data'])-1 && count($revserse_temp)!==0){
                    for ($i=0; $i < $Value['row_size']; $i++) { 
                        if(!array_key_exists($i,$revserse_temp)){
                            $revserse_temp[$i] = [];
                        }
                    }
                    array_push($revserse,$revserse_temp);
                }
            }
            
            $result['data'] = $revserse;
        }

        unset($values["length"]);
        unset($values["start"]);

        //The method of implementing server-side by sql.
        //Select the total datas in current page, then select the top 5(for example) result outside the sql inside.
        $sql = "SELECT COUNT(*) total
                FROM
                (
                    SELECT video.video.id video_id, video.video.video_type, video.video_type.name, mil.cmsmw_mw001, mil.cmsmw_mw002, video.video.id, video.video.video_name, 
                            video.video.video_user_id, video.video.sfcta_ta001, video.video.sfcta_ta002, video.video.sfcta_ta003, video.video.sfcta_ta004, 
                            video.video.last_update_time, video.video.video_file_name, system.user.name, video.video.video_thumbnail_file_name, video.video.first_insert_time,
                            video.video.remark, video.video.update_user_id, update_user.name, video.video_views.views {$garbage_column_for_size}
                    FROM video.video
                    LEFT JOIN
                    (
                        SELECT cmsmw_mw001, cmsmw_mw002
                        FROM json_to_recordset(
                            '{$id}'
                        ) as setting_titanizing(cmsmw_mw001 text, cmsmw_mw002 text)
                    )AS mil ON mil.cmsmw_mw001 = video.video.sfcta_ta004
                    LEFT JOIN video.video_type ON video.video_type.id = video.video.video_type
                    LEFT JOIN system.user ON system.user.id = video.video.video_user_id 
                    LEFT JOIN system.user update_user ON update_user.id = video.video.update_user_id 
                    LEFT JOIN system.user delete_user ON delete_user.id = video.video.delete_user_id 	
                    LEFT JOIN video.video_views ON video.video_views.video_id = video.video.id
                    {$conditions} {$garbage_condition}
                    GROUP BY video.video.id,video.video.video_type, video.video_type.name, mil.cmsmw_mw001, mil.cmsmw_mw002, video.video.id, video.video.video_name, 
                            video.video.video_user_id, video.video.sfcta_ta001, video.video.sfcta_ta002, video.video.sfcta_ta003, video.video.sfcta_ta004, 
                            video.video.last_update_time, video.video.video_file_name, system.user.name, video.video.video_thumbnail_file_name, video.video.first_insert_time,
                            video.video.remark, video.video.update_user_id, update_user.name, video.video_views.views {$garbage_column_for_size}
                )AS count";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
     }

     //Get the top three videos.
     public function get_top_three_videos($groups)
     {
        $id = json_encode($groups);
        // $sql = "SELECT *
        $sql = "SELECT  '/develop/videos/thumbnail/' || video.video.id as src, 
                        CASE WHEN video.video.id IS NULL THEN 0 ELSE video.video.id END AS video_id, 
                        CASE WHEN video.video.video_type IS NULL THEN 0 ELSE video.video.video_type END AS video_type, 
                        CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS name, 
                        CASE WHEN mil.cmsmw_mw001 IS NULL THEN '-' ELSE mil.cmsmw_mw001 END AS cmsmw_mw001, 
                        CASE WHEN mil.cmsmw_mw002 IS NULL THEN '-' ELSE mil.cmsmw_mw002 END AS cmsmw_mw002, 
                        CASE WHEN video.video.id IS NULL THEN 0 ELSE video.video.id END AS id, 
                        CASE WHEN video.video.video_name IS NULL THEN '-' ELSE video.video.video_name END AS video_name, 
                        CASE WHEN video.video.video_user_id IS NULL THEN 0 ELSE video.video.video_user_id END AS video_user_id, 
                        CASE WHEN video.video.sfcta_ta001 IS NULL THEN '-' ELSE video.video.sfcta_ta001 END AS sfcta_ta001, 
                        CASE WHEN video.video.sfcta_ta002 IS NULL THEN '-' ELSE video.video.sfcta_ta002 END AS sfcta_ta002, 
                        CASE WHEN video.video.sfcta_ta003 IS NULL THEN '-' ELSE video.video.sfcta_ta003 END AS sfcta_ta003, 
                        CASE WHEN video.video.sfcta_ta004 IS NULL THEN '-' ELSE video.video.sfcta_ta004 END AS sfcta_ta004, 
                        CASE WHEN video.video.last_update_time IS NULL THEN '2022-1-1 00:00:00' ELSE video.video.last_update_time END AS last_update_time, 
                        CASE WHEN video.video.video_file_name IS NULL THEN '-' ELSE video.video.video_file_name END AS video_file_name, 
                        CASE WHEN video.video.video_thumbnail_file_name IS NULL THEN '-' ELSE video.video.video_thumbnail_file_name END AS video_thumbnail_file_name,
                        ROW_NUMBER() OVER (ORDER BY video.video.last_update_time DESC, video.video.video_type DESC) AS row_num,
                        CASE WHEN system.user.name IS NULL THEN '-' ELSE system.user.name END AS user_name
                FROM video.video
                LEFT JOIN
                (
                    SELECT cmsmw_mw001, cmsmw_mw002
                    FROM json_to_recordset(
                        '{$id}'
                    ) as setting_titanizing(cmsmw_mw001 text, cmsmw_mw002 text)
                )AS mil ON mil.cmsmw_mw001 = video.video.sfcta_ta004
                LEFT JOIN video.video_type ON video.video_type.id = video.video.video_type
                LEFT JOIN system.user ON system.user.id = video.video.video_user_id 
                WHERE video.video.last_update_time IS NOT NULL
                -- GROUP BY video.video.video_type, video.video_type.name, mil.cmsmw_mw001, mil.cmsmw_mw002, video.video.idㄝ, video.video.video_name, 
                --         video.video.video_user_id, video.video.sfcta_ta001, video.video.sfcta_ta002, video.video.sfcta_ta003, video.video.sfcta_ta004, 
                --         video.video.last_update_time, video.video.video_file_name, system.user.name, video.video.video_thumbnail_file_name
                -- ORDER BY video.video.last_update_time DESC, video.video.video_type DESC
                LIMIT 3";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
     }

     //Get the thumbnail of the video.
     public function get_video_thumbnail($data)
     {
        $sql = "SELECT CASE WHEN video_thumbnail_file_name IS NULL THEN '-' ELSE video_thumbnail_file_name END AS video_thumbnail_file_name
                FROM video.video
                WHERE video.video.id = :video_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
     }

     //Get upload user name.
     public function get_upload_user_name($data)
     {
        $sql = "SELECT CASE WHEN id IS NULL THEN 0 ELSE id END AS id, 
                        CASE WHEN name IS NULL THEN '-' ELSE name END AS name
                 FROM system.user
                 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
     }

     //Get the video types.
    public function get_video_type_no_order()
    {
        $sql = "SELECT CASE WHEN video.video_type.id IS NULL THEN 0 ELSE video.video_type.id END AS id, 
                        CASE WHEN video.video_type.name IS NULL THEN '-' ELSE video.video_type.name END AS name
                FROM video.video_type
                ORDER BY video.video_type.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
   }
    //Insert video type.
    public function insert_video_type($data)
    {
        $statement = "";
        $values = [];
        $result = [];
        $count = 0;

        $sql = "SELECT MAX(video.video_type.order) AS order
                FROM video.video_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $video_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $order_counter = 0;
        foreach($video_type as $key => $value){
            $order_counter = $value["order"];
        }

        foreach($data as $key => $value)
        {
            $order_counter += 1;
            $statement .= "(:video_type_name_{$key}, :video_type_order_{$key}),";
            $temp_column = [
                "video_type_name" => "",
                "video_type_order" => $order_counter
            ];
            foreach($temp_column as $temp_key => $temp_value)
            {
                $temp_column[$temp_key."_{$key}"] = $temp_value;
                if(array_key_exists($temp_key, $value)){
                    $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                }
                unset($temp_column[$temp_key]);
            }
            // $values = array_merge($temp_column, $values);
            $values = array_merge($temp_column, $values);
            $count++;
            
            if($count == 50)
            {
                $statement = rtrim($statement, ",");
                $sql = "INSERT INTO video.video_type (name, \"order\")
                        VALUES {$statement} 
                        RETURNING video.video_type.id";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach($ids as $id_key => $id_value){
                        array_push($result, $id_value);
                    }
                } 
                else 
                {
                    array_push($result, ["status" => "failed"]);
                    return $result;
                }
                $statement = "";
                $values = [];
                $count = 0;
            }
        }
        // return $values;
        if($count != 0)
        {
            $statement = rtrim($statement, ",");
            $sql = "INSERT INTO video.video_type (name, \"order\")
                    VALUES {$statement} 
                    RETURNING video.video_type.id";
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($values))
            {
                $ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach($ids as $id_key => $id_value){
                    array_push($result, $id_value);
                }
            } 
            else 
            {
                var_dump($stmt->errorInfo());
                array_push($result, ["status" => "failed"]);
                return $result;
            }
        }
        return $result;
   }
   //Delete video type.
   public function delete_video_type($data)
   {
        $conditions = "(";
        $values = [];
        foreach($data as $key => $value)
        {
            $conditions .= ":video_type_id_{$key},";
            $values["video_type_id_{$key}"] = $value['video_type_id'];
        }
        $conditions = rtrim($conditions, ",");
        $conditions .= ")";
        $sql = "DELETE FROM video.video_type WHERE id IN {$conditions}";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
            var_dump($stmt->errorInfo());
        }
        return $result;
    }
    //Update video type.
    public function patch_video_type($data)
    {
        $statement = "";
        $values = [];
        $result = [];
        $count = 0;
        foreach($data as $key => $value)
        {
            $statement .= "(:video_type_id_{$key}, :video_type_name_{$key}, :video_type_order_{$key}),";
            $temp_column = [
                "video_type_id" => 0,
                "video_type_name" => "",
                "video_type_order" => $key+1
            ];
            foreach($temp_column as $temp_key => $temp_value)
            {
                $temp_column[$temp_key."_{$key}"] = $temp_value;
                if(array_key_exists($temp_key, $value)){
                    $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                }
                unset($temp_column[$temp_key]);
            }
            $values = array_merge($temp_column, $values);
            $count++;

            if($count == 50)
            {
                $statement = rtrim($statement, ",");
                $sql = "UPDATE video.video_type as video_type
                        SET name = change.video_type_name, \"order\" = CAST(change.video_type_order AS INTEGER)
                        FROM 
                        (
                            VALUES {$statement}
                        ) AS change(video_type_id, video_type_name, video_type_order) 
                        WHERE CAST(change.video_type_id AS INTEGER) = video_type.id";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    array_push($result, ["status" => "Update 50 rows successfully"]);
                } 
                else 
                {
                    array_push($result, ["status" => "failed"]);
                    var_dump($stmt->errorInfo());
                    return $result;
                }
                $statement = "";
                $values = [];
                $count = 0;
            }
        }
        if($count != 0)
        {
            $statement = rtrim($statement, ",");
            $sql = "UPDATE video.video_type as video_type
                    SET name = change.video_type_name, \"order\" = CAST(change.video_type_order AS INTEGER)
                    FROM 
                    (
                        VALUES {$statement}
                    ) AS change(video_type_id, video_type_name, video_type_order) 
                    WHERE CAST(change.video_type_id AS INTEGER) = video_type.id";
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($values))
            {
                array_push($result, ["status" => "Update rows successfully"]);
            } 
            else 
            {
                array_push($result, ["status" => "failed"]);
                var_dump($stmt->errorInfo());
                return $result;
            }
            $statement = "";
            $values = [];
            $count = 0;
        }
        return $result;
    }
    //Update video type name.
    public function patch_video_type_name($data)
    {
        $statement = "";
        $values = [];
        $result = [];
        $count = 0;
        foreach($data as $key => $value)
        {
            $statement .= "(:video_type_id_{$key}, :video_type_name_{$key}),";
            $temp_column = [
                "video_type_id" => 0,
                "video_type_name" => ""
            ];
            foreach($temp_column as $temp_key => $temp_value)
            {
                $temp_column[$temp_key."_{$key}"] = $temp_value;
                if(array_key_exists($temp_key, $value)){
                    $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                }
                unset($temp_column[$temp_key]);
            }
            $values = array_merge($temp_column, $values);
            $count++;

            if($count == 50)
            {
                $statement = rtrim($statement, ",");
                $sql = "UPDATE video.video_type as video_type
                        SET name = change.video_type_name
                        FROM 
                        (
                            VALUES {$statement}
                        ) AS change(video_type_id, video_type_name) 
                        WHERE CAST(change.video_type_id AS INTEGER) = video_type.id";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    array_push($result, ["status" => "Update 50 rows successfully"]);
                } 
                else 
                {
                    array_push($result, ["status" => "failed"]);
                    var_dump($stmt->errorInfo());
                    return $result;
                }
                $statement = "";
                $values = [];
                $count = 0;
            }
        }
        if($count != 0)
        {
            $statement = rtrim($statement, ",");
            $sql = "UPDATE video.video_type as video_type
                    SET name = change.video_type_name
                    FROM 
                    (
                        VALUES {$statement}
                    ) AS change(video_type_id, video_type_name) 
                    WHERE CAST(change.video_type_id AS INTEGER) = video_type.id";
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($values))
            {
                array_push($result, ["status" => "Update rows successfully"]);
            } 
            else 
            {
                array_push($result, ["status" => "failed"]);
                var_dump($stmt->errorInfo());
                return $result;
            }
            $statement = "";
            $values = [];
            $count = 0;
        }
        return $result;
    }
    //Update video_garbage.
    public function update_video_garbage($data)
    {
        $statement = "";
        $values = [];
        $result = [];
        $count = 0;

        $garbage_condition = 'true';
        if(array_key_exists("delete", $data)){
            $garbage_condition = 'true';
        }
        if(array_key_exists("reduction", $data)){
            $garbage_condition = 'false';
        }

        foreach($data["data"] as $key => $value)
        {
            $statement .= "(:video_id_{$key}, :delete_user_id_{$key}),";
            $temp_column = [
                "video_id" => 0,
                "delete_user_id" => 0
            ];
            foreach($temp_column as $temp_key => $temp_value)
            {
                $temp_column[$temp_key."_{$key}"] = $temp_value;
                if(array_key_exists($temp_key, $value)){
                    $temp_column[$temp_key."_{$key}"] = $value[$temp_key];
                }
                unset($temp_column[$temp_key]);
            }
            $values = array_merge($temp_column, $values);
            $count++;

            if($count == 50)
            {
                $statement = rtrim($statement, ",");
                $sql = "UPDATE video.video as video
                        SET in_garbage = {$garbage_condition}, insert_garbage_time = NOW(), delete_user_id = CAST(change.delete_user_id AS INTEGER) 
                        FROM 
                        (
                            VALUES {$statement}
                        ) AS change(video_id, delete_user_id) 
                        WHERE CAST(change.video_id AS INTEGER) = video.id";
                $stmt = $this->db->prepare($sql);
                if($stmt->execute($values))
                {
                    array_push($result, ["status" => "Update 50 rows successfully"]);
                } 
                else 
                {
                    array_push($result, ["status" => "failed"]);
                    var_dump($stmt->errorInfo());
                    return $result;
                }
                $statement = "";
                $values = [];
                $count = 0;
            }
        }
        if($count != 0)
        {
            $statement = rtrim($statement, ",");
            $sql = "UPDATE video.video as video
                    SET in_garbage = {$garbage_condition}, insert_garbage_time = NOW(), delete_user_id = CAST(change.delete_user_id AS INTEGER) 
                    FROM 
                    (
                        VALUES {$statement}
                    ) AS change(video_id, delete_user_id) 
                    WHERE CAST(change.video_id AS INTEGER) = video.id";
            $stmt = $this->db->prepare($sql);
            if($stmt->execute($values))
            {
                array_push($result, ["status" => "Update rows successfully"]);
            } 
            else 
            {
                array_push($result, ["status" => "failed"]);
                var_dump($stmt->errorInfo());
                return $result;
            }
            $statement = "";
            $values = [];
            $count = 0;
        }
        return $result;
    }
    //Get videos that put in trash and over 30 days.
    public function get_garbage_videos_for_crontab()
    {
        $sql = "SELECT id video_id, in_garbage, insert_garbage_time
                FROM video.video
                WHERE in_garbage IS TRUE AND (insert_garbage_time + '30 days') < NOW()
                ORDER BY id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    //Insert or update video_views.
    public function video_views($data)
    {
        $sql = "INSERT INTO video.video_views(video_id, views)
                VALUES (:video_id, 1)
                ON CONFLICT (video_id)
                DO UPDATE SET views = video.video_views.views + 1
                WHERE video.video_views.video_id = :video_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':video_id', $data['video_id'], PDO::PARAM_INT);
        if ($stmt->execute()) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
            var_dump($stmt->errorInfo());
        }
        return $result;
    }

    public function get_video_of_delete_video_type($data)
    {
        $conditions = "";
        $values = [];
        foreach($data as $key => $value){
            $conditions .= ":video_type_id_{$key},";
            $values["video_type_id_{$key}"] = $value["video_type_id"];
        }
        $conditions = rtrim($conditions, ",");
        $sql = "SELECT id video_id
                FROM video.video
                WHERE video_type IN ($conditions)
                ORDER BY id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function update_video_type($data)
    {
        $conditions = "";
        $values = [];
        foreach($data as $key => $value){
            $conditions .= ":video_id_{$key},";
            $values["video_id_{$key}"] = $value["video_id"];
        }
        $conditions = rtrim($conditions, ",");
        $sql = "UPDATE video.video
                SET video_type = 0
                WHERE video.video.id IN ($conditions)";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
            // var_dump($stmt->errorInfo());
        }
        return $result;
    }

    //Delete videos which are in the garbage.
    public function delete_video_garbage()
    {
        $sql = "DELETE FROM video.video WHERE in_garbage IS TRUE";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) 
        {
            $result = ["status" => "success",];
        } 
        else 
        {
            $result = ["status" => "failed",];
            // var_dump($stmt->errorInfo());
        }
        return $result;
    }
}

function isJson($string)
{
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}
