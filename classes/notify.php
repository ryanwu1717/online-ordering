<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;

class Notify
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getNotifyFinishModule($data){
        $sql = "SELECT * FROM public.notify_finish
        WHERE file_id = :file_id AND finish = :finish;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id',$data['file_id']);
        $stmt->bindValue(':finish',$data['finish']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotifyFinish($data){
        $sql = "SELECT * FROM public.notify_finish
            WHERE file_id = :file_id AND notify = :notify;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id',$data['file_id']);
        $stmt->bindValue(':notify',$data['notify']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }
    public function postBusinessNotify($data){
        $sql="DELETE FROM public.notify_finish
            WHERE file_id = :file_id AND finish = :finish;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id',$data['file_id']);
        $stmt->bindValue(':finish',$data['notify']);
        $stmt->execute();
        $sql="DELETE FROM public.notify_finish
        WHERE file_id = :file_id AND notify = :notify;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id',$data['file_id']);
        $stmt->bindValue(':notify',$data['notify']);
        $stmt->execute();

        $addVal = "({$data['file_id']},1,2),({$data['file_id']},1,3),({$data['file_id']},1,4),({$data['file_id']},1,5),";
        foreach($data['finish'] as $key => $value){
            if($value == 5){
                $sql="DELETE FROM public.notify_finish
                WHERE file_id = :file_id AND notify=:notify AND finish = :finish;";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':file_id',$data['file_id']);
                $stmt->bindValue(':notify',$value);
                $stmt->bindValue(':finish','4');
                $stmt->execute();
                $addVal.=" ({$data['file_id']},{$value},4),";

            }else{
                $addVal.=" ({$data['file_id']},{$value},{$data['notify']}),";

            }
        }
        $addVal = substr($addVal, 0, -1);
        $sql="INSERT INTO public.notify_finish(
            file_id,notify, finish)
            VALUES {$addVal};";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $ack=array(
            'status' => 'success'
        );
        return $ack;
    }

    public function postNotifyFinish($data){

        $sql="DELETE FROM public.notify_finish
            WHERE file_id = :file_id AND notify = :notify;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id',$data['file_id']);
        $stmt->bindValue(':notify',$data['notify']);
        $stmt->execute();

        $addVal = '';
        foreach($data['finish'] as $key => $value){
            $addVal.=" ({$data['file_id']},{$data['notify']},{$value}),";
        }
        $addVal = substr($addVal, 0, -1);
        $sql="INSERT INTO public.notify_finish(
            file_id,notify, finish)
            VALUES {$addVal};";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $ack=array(
            'status' => 'success'
        );
        return $ack;
    }

    public function getCallback($data)
    {
        $sql = "SELECT *
            FROM setting.token
            WHERE state = :state
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':state',$data['state']);
        $stmt->execute();
        if($stmt->rowCount()>0){
            return true;
        }else{
            return false;
        }
    }
    public function setAccessToken($data){
        $sql = "UPDATE setting.token
            SET access_token = :access_token, update_time = NOW()
            WHERE state = :state
            RETURNING update_time
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':state',$data['state']);
        $stmt->bindValue(':access_token',$data['access_token']);
        $stmt->execute();
        if($stmt->rowCount()>0){
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }else{
            return false;
        }
    }

    public function getModuleInformation($data){
        $query = "WHERE ";
        foreach ($data['module'] as $key => $value) {
            $query .= "module.id = '{$value}' OR ";
        }
        $query = RTRIM($query,"OR ");
        $sql = "SELECT access_token
            FROM setting.token
            LEFT JOIN setting.module ON module.id = token.module_id
            {$query}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        if($stmt->rowCount()>0 && $query != "WHERE "){
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }else{
            return false;
        }
    }

    public function getAccessToken($data,$module_name){

        $query = "WHERE ";
        foreach ($data['module'] as $key => $value) {
            $query .= "module.id = '{$value}' OR ";
        }
        $query = RTRIM($query,"OR ");
        $sql = "SELECT access_token
            FROM setting.token
            LEFT JOIN setting.module ON module.id = token.module_id AND module.name = '{$module_name}'
            {$query}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        if($stmt->rowCount()>0 && $query != "WHERE "){
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }else{
            return false;
        }
    }
    

    public function setState($data)
    {

        $state = hash('sha512', serialize([$data['module_id'],time()]));

        $sql = "INSERT INTO setting.token (module_id,state)
            VALUES(:module_id,:state)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':state',$state);
        $stmt->bindValue(':module_id',$data['module_id']);
        $stmt->execute();
        return $state;
    }

    public function getModule($data)
    {
        $sql = "SELECT module.id,module.name
            FROM setting.module
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getToken($data)
    {
        $sql = "SELECT module.name, to_char(token.update_time, 'YYYY年MM月DD日 AMHH12:MI:SS') update_time,access_token
            FROM setting.token
            LEFT JOIN setting.module ON token.module_id = module.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}