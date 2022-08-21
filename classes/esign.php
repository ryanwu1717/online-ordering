<?php
use Slim\Http\UploadedFile;
use \Psr\Container\ContainerInterface;

class Esign
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

    function uploadContentFile($directory, $uploadedFiles,$content_id){
        $uploadedFile = $uploadedFiles['inputFile'];
        // return $uploadedFile->getClientFilename();
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
            $sql="INSERT INTO  esign.content_file
                    ( content_id, user_id, filename, fileclientname)
                SELECT  :content_id, :user_id, :filename, :fileclientname
                -- WHERE  NOT EXISTS (
                --             SELECT content_id, user_id
                --             FROM  esign.content_file 
                --             WHERE content_id = :content_id AND user_id =:user_id
                --         );
                ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':content_id', $content_id);
            $stmt->bindValue(':filename', $filename);
            $tmp  = explode('/', $uploadedFile->getClientFilename());
            $tmpfile = end($tmp);
            $stmt->bindParam(':fileclientname', $tmpfile, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $_SESSION['id']);
            $stmt->execute();

            // $sql ="UPDATE esign.content_file
            // SET filename=:filename, fileclientname=:fileclientname
            // WHERE content_id = :content_id AND user_id =:user_id;";
            // $stmt = $this->db->prepare($sql);
            // $stmt->bindValue(':content_id', $content_id);
            // $stmt->bindValue(':filename', $filename);
            // $tmp  = explode('/', $uploadedFile->getClientFilename());
            // $tmpfile = end($tmp);
            // $stmt->bindParam(':fileclientname', $tmpfile, PDO::PARAM_STR);
            // $stmt->bindValue(':user_id', $_SESSION['id']);
            // $stmt->execute();

            // $sql="SELECT *
            // FROM esign.content_file
            // WHERE  content_id = :content_id AND user_id =:user_id;
            // ";
            // $stmt = $this->db->prepare($sql);
            // $stmt->bindValue(':content_id', $content_id);
            // $stmt->bindValue(':user_id', $_SESSION['id']);
            // $returnfile =  $stmt->fetchAll(PDO::FETCH_ASSOC);
            // $file_id = $returnfile[0]['id'];

            $result = array(
                'status' => 'success',
                'name' => $filename,
                'id' => $this->db->lastInsertId()
            );
        }else {
            $result = array(
                'status' => 'failed'
            );
        }
        return $result;
    }

    public function patchQuestionFeedback($data){
        $data = $data['feedbackObj'];
        $sql = "UPDATE esign.receiver
        SET  agree=:agree, reason=:reason,sign_timestamp=NOW()
        WHERE question_id=:question_id AND  user_id=:user_id
        RETURNING *";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':agree', $data['agree']);
        $stmt->bindValue(':reason', $data['reason']);
        $stmt->bindValue(':question_id', $data['question_id']);
        $stmt->bindValue(':user_id', $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $receiver_id =   $result[0]['id'];
        $sql = "INSERT INTO esign.receiver_history(
            receiver_id, sign_timestamp, agree, reason)
            VALUES (:receiver_id, NOW(), :agree, :reason);";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':receiver_id', $receiver_id);
        $stmt->bindValue(':agree', $data['agree']);
        $stmt->bindValue(':reason', $data['reason']);
        $stmt->execute();

        foreach($data['content'] AS $key => $value){
            
            if($value['type'] == 'radio' || $value['type'] == 'checkbox' ){
                $content_value="";
                foreach($value['value'] AS $tmpkey => $tmpvalue){
                    $content_value.="{$tmpvalue},";
                }
                $content_value = substr_replace($content_value, "", -1);

            }else{
                $content_value = $value['value'];
            }
            // var_dump($content_value);

            $sql ="	INSERT INTO   esign.receiver_feedback(content_id, receiver_id, value)
            SELECT  :content_id, :receiver_id, :content_value
            WHERE  NOT EXISTS (
                SELECT content_id, receiver_id
                FROM   esign.receiver_feedback
                WHERE content_id = :content_id AND receiver_id =:receiver_id
            );";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':content_id', $value['content_id']);
            $stmt->bindValue(':receiver_id', $receiver_id);
            $stmt->bindValue(':content_value', $content_value);
            $stmt->execute();


            $sql="UPDATE esign.receiver_feedback
            SET value=:content_value
            WHERE content_id = :content_id AND receiver_id =:receiver_id;";
             $stmt = $this->db->prepare($sql);
             $stmt->bindValue(':content_id', $value['content_id']);
             $stmt->bindValue(':receiver_id', $receiver_id);
             $stmt->bindValue(':content_value', $content_value);
             $stmt->execute();
        }

        return ;

    }

    public function getQuestionContent($data){
        $sql = "SELECT content.id,content.question_id, content.component_id, content.sequence, content.title,component.type,
        concat('[', array_to_string(ARRAY_AGG(content_option.name),','),']') as option
            FROM esign.content
            LEFT JOIN esign.component ON content.component_id = component.id
            LEFT JOIN (
                SELECT content_id  ,json_build_object('id',(id),'name',name) as name
                FROM esign.content_option
                GROUP BY content_id ,id,name 
            )AS content_option ON content_option.content_id = content.id
            WHERE question_id = :question_id
            
            GROUP BY  content.id,content.question_id, content.component_id, content.sequence, content.title,component.type
            ORDER BY sequence asc;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':question_id', $data['question_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getdetaildataTable(){
        $sql = "SELECT question.*,receiver.*,user_modal.module_id,receiver.user_id AS receiver_user,tmpuser.name AS receiver_name
        FROM esign.question
        LEFT JOIN esign.receiver ON question.id = receiver.question_id
        LEFT JOIN (
            SELECT uid, array_to_string(array_agg(distinct \"name\"),',') AS module_id
            FROM   system.user_modal
            LEFT JOIN setting.module ON user_modal.module_id = module.id
            GROUP  BY 1
        )AS user_modal ON user_modal.uid = receiver.user_id
        LEFT JOIN system.user AS tmpuser ON tmpuser.id = receiver.user_id
        WHERE receiver.user_id IS NOT NULL;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getlistdataTable(){
        // $sql = "SELECT question.*,urgent_type.name,user_modal.module_id,process.name as process_id
        // FROM esign.question
        // LEFT JOIN esign.urgent_type ON urgent_type.id = question.urgent_type_id
        // LEFT JOIN esign.process ON process.id = question.process_id
        // LEFT JOIN (
        //     SELECT uid, array_to_string(array_agg(distinct \"name\"),',') AS module_id
        //     FROM   system.user_modal
        //     LEFT JOIN setting.module ON user_modal.module_id = module.id
        //     GROUP  BY 1
        // )AS user_modal ON user_modal.uid = question.user_id;";
        $sql = "SELECT listtable.*
            FROM esign.receiver
            LEFT JOIN (
                SELECT question.*,urgent_type.name,user_modal.module_id,process.name as process_id
                FROM esign.question
                LEFT JOIN esign.urgent_type ON urgent_type.id = question.urgent_type_id
                LEFT JOIN esign.process ON process.id = question.process_id
                LEFT JOIN (
                    SELECT uid, array_to_string(array_agg(distinct \"name\"),',') AS module_id
                    FROM   system.user_modal
                    LEFT JOIN setting.module ON user_modal.module_id = module.id
                    GROUP  BY 1
                )AS user_modal ON user_modal.uid = question.user_id
            )AS listtable ON listtable.id = receiver.question_id
            WHERE receiver.user_id=:user_id
            ;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $_SESSION['id']);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    }

    public function getQuestionSequence($data){
        $sql ="SELECT question_id, user_id, sign_timestamp, sequence, comment, id, agree, result
            FROM esign.receiver
            WHERE question_id = :question_id
            ORDER BY sequence ;";
         $stmt = $this->db->prepare($sql);
         $stmt->bindValue(':question_id', $data['question_id']);
          $stmt->execute();
          return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReceiverSequence($data)
    {
        $sql = "SELECT esign.receiver.question_id, user_id, sign_timestamp, sequence, esign.receiver.user_id, agree, reason, system.user.name, max.max, min.min
                FROM esign.receiver
                LEFT JOIN system.user
                ON system.user.id = esign.receiver.user_id
                LEFT JOIN(
                    SELECT question_id, MAX(sequence) as max
                    FROM esign.receiver
                    GROUP BY question_id 
                )as max
                ON max.question_id = esign.receiver.question_id
                LEFT JOIN(
                    SELECT question_id, MIN(sequence) as min
                    FROM esign.receiver
                    WHERE esign.receiver.agree = false
                    GROUP BY question_id
                )as min
                ON min.question_id = esign.receiver.question_id
                WHERE esign.receiver.question_id = :question_id
                ORDER BY sequence ASC";

         $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':question_id', $data['question_id']);
         $stmt->execute();
         return $stmt->fetchAll(PDO::FETCH_ASSOC);
       
     }
 


    public function postQuestion($data){
        // $_SESSION['id'] =7;
        $contentdata = $data['content'];
        $data = $data['info'];
        // $_SESSION['id'] = 7;
        $sql = "INSERT INTO esign.question(title, publish_date, user_id, urgent_type_id)
                SELECT :title, :publish_date, :user_id,id
                FROM esign.urgent_type
                WHERE name=:urgent_type_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':publish_date', $data['publish_date']);
        $stmt->bindValue(':user_id', $_SESSION['id'] );
        $stmt->bindValue(':urgent_type_id', $data['urgent_type_id']);
        $stmt->execute();

        $question_id = $this->db->lastInsertId();
        // return $data['receiverArr'];
        // $question_id =1;
        $receiverArr = array(
            
        );
        
        foreach ($data['receiverArr'] as $key => $value) {
            if (array_key_exists($value, $receiverArr)) {
                $receiverArr[$value] .= ",'{$key}'";
            }else{
                $receiverArr[$value] = "'{$key}'";
            }
        }
        $tmpStr = "";
        foreach ( $receiverArr as $key => $value ){
            $tmpStr .=" SELECT {$question_id} ,id , {$key} FROM system.\"user\" WHERE name IN ({$value}) UNION";
        }
        $tmpStr = substr_replace($tmpStr, "", -5);
        $sql = "INSERT INTO esign.receiver(question_id, user_id, sequence)  {$tmpStr}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        foreach ($contentdata as $key => $value) {
            // var_dump($key,$value);
            if(isset($value['title'])){
                $tmptitle = $value['title'];
            }else{
                $tmptitle='';
            }
            $sql = "INSERT INTO esign.content(question_id, component_id, sequence, title)
            SELECT :question_id,id,:sequence,:title
            FROM esign.component
            WHERE type = :type";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':question_id', $question_id );
            $stmt->bindValue(':type', $value['type']);
            $stmt->bindValue(':sequence', $key+1 );
            $stmt->bindValue(':title',  $tmptitle);
            $stmt->execute();
            if(isset($value['content_option'])){
                $content_id = $this->db->lastInsertId();
                $tmpStr = '';
                foreach ($value['content_option'] as $content_optionkey => $content_optionvalue) {
                    $tmpStr .="('{$content_optionvalue}',{$content_id}),";
                }
                $tmpStr = substr_replace($tmpStr, "", -1);
                $sql = "INSERT INTO esign.content_option(
                    name, content_id) VALUES {$tmpStr};";
                    var_dump($sql);
                 $stmt = $this->db->prepare($sql);
                 $stmt->execute();
            }
        }

        return ;
    }
    private function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }
   
}
?>