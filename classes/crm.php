<?php

use \Psr\Container\ContainerInterface;
use Slim\Http\UploadedFile;
use Stichoza\GoogleTranslate\GoogleTranslate;

class CRM
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

    public function get_all_meet($data)
    {
        $result = [];

        $values = [
            "cur_page" => 1,
            "size" => 10
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];

        $condition = "TRUE ";
        $condition_values = [
            "input" => " AND (meet.meet.name LIKE '%' || :input || '%' OR meet.meet_type.name LIKE '%' || :input || '%' OR system.user.name LIKE '%' || :input || '%' OR
                modify.modify_user_name LIKE '%' || :input || '%' OR (to_char(modify.modify_time, 'YYYY-MM-DD') LIKE '%' || :input || '%') OR 
                (to_char(meet.meet.meet_date, 'YYYY-MM-DD') LIKE '%' || :input || '%'))",
            "meet_type_id" => " AND meet.meet.meet_type_id = :meet_type_id",
            "time_start" => " AND (modify.modify_time >= :time_start OR (modify.modify_time IS null AND meet.meet.meet_date >= :time_start))",
            "time_end" => " AND (modify.modify_time <= :time_end OR (modify.modify_time IS null AND meet.meet.meet_date <= :time_end))"
        ];

        foreach ($condition_values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $condition .= $condition_values[$key];
                $condition_values[$key] = $data[$key];
            } else {
                unset($condition_values[$key]);
            }
        }

        $sql = "SELECT *
            FROM
            (
                SELECT meet.meet.id meet_id, meet.meet.name meet_name, meet.meet_type.id meet_type_id, meet.meet_type.name meet_type_name,CASE WHEN meet.meet.meet_date IS null THEN '1901-01-01' ELSE to_char(meet.meet.meet_date, 'YYYY-MM-DD') END meet_date, 
					system.user.name recorder_name, modify.modify_user_id, modify.modify_user_name, 
                    to_char(modify.modify_time, 'YYYY-MM-DD') modify_time, 
                    ROW_NUMBER() OVER (ORDER BY meet.meet.id DESC) AS row_num
                FROM meet.meet
                LEFT JOIN system.user ON system.user.id = meet.meet.recorder_user_id
                LEFT JOIN meet.meet_type ON meet.meet_type.id = meet.meet.meet_type_id
                LEFT JOIN (
                    SELECT meet.modify_meet_record.meet_id, meet.modify_meet_record.modify_time, 
                        meet.modify_meet_record.modify_user_id, system.user.name modify_user_name
                    FROM meet.modify_meet_record
                    LEFT JOIN system.user ON system.user.id = meet.modify_meet_record.modify_user_id
                ) modify ON modify.meet_id = meet.meet.id AND modify.modify_time IN (
                        SELECT MAX(meet.modify_meet_record.modify_time) modify_time
                        FROM meet.modify_meet_record
                        GROUP BY meet.modify_meet_record.meet_id)
                WHERE {$condition}
                GROUP BY meet.meet.id, meet.meet.name, meet.meet_type.id, meet.meet_type.name, system.user.name, modify.modify_user_id, 
					modify.modify_user_name, modify.modify_time
                ORDER BY meet.meet.id DESC
                LIMIT {$length}
            ) discuss
            WHERE discuss.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($condition_values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(meet.meet.id)
                FROM meet.meet
                LEFT JOIN system.user ON system.user.id = meet.meet.recorder_user_id
                LEFT JOIN meet.meet_type ON meet.meet_type.id = meet.meet.meet_type_id
                LEFT JOIN (
                    SELECT meet.modify_meet_record.meet_id, meet.modify_meet_record.modify_time, 
                        meet.modify_meet_record.modify_user_id, system.user.name modify_user_name
                    FROM meet.modify_meet_record
                    LEFT JOIN system.user ON system.user.id = meet.modify_meet_record.modify_user_id
                ) modify ON modify.meet_id = meet.meet.id AND modify.modify_time IN (
                        SELECT MAX(meet.modify_meet_record.modify_time) modify_time
                        FROM meet.modify_meet_record
                        GROUP BY meet.modify_meet_record.meet_id)
                WHERE {$condition}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($condition_values);
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function get_meets($data)
    {
        $result = [];
        //取得排序column
        $ordering = [
            "id" => 1
        ];
        if (array_key_exists("order_column", $data)) {
            $ordering['id'] = $data['order_column'];
        }
        $sql = "SELECT \"column\"
                FROM meet.column_order
                WHERE id = :id
            ";
        $stmt = $this->db->prepare($sql);
        $ordering_column = "";
        $stmt->execute($ordering);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $value) {
            $ordering_column = $value['column'];
        }

        //取得升、降冪排列
        $ordering = "ASC";
        if (array_key_exists("ordering", $data)) {
            $ordering = $data['ordering'];
        }

        $values = [
            "cur_page" => 1,
            "size" => 10,
            "row_size" => 5
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];
        $sql = "SELECT *
            FROM
            (
                SELECT meet.meet.id, meet.meet.name, meet.meet.recorder_user_id, system.user.name recorder_name, 
                    meet.meet.meet_date, meet.meet_type.name,ROW_NUMBER() OVER (ORDER BY {$ordering_column} {$ordering}) AS row_num
                FROM meet.meet
                LEFT JOIN system.user ON meet.meet.recorder_user_id = system.user.id
                LEFT JOIN meet.meet_type ON meet.meet_type.id = meet.meet.meet_type_id

                LIMIT {$length}
            ) meets
            WHERE meets.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reverse = [];
        $reverse_temp = [];
        foreach ($result['data'] as $key => $value) {
            array_push($reverse_temp, $value);
            if ($key !== 0 && ($key + 1) % $values['row_size'] == 0) {
                array_push($reverse, $reverse_temp);
                $reverse_temp = [];
            }
            if ($key === count($result['data']) - 1 && count($reverse_temp) !== 0) {
                for ($i = 0; $i < $values['row_size']; $i++) {
                    if (!array_key_exists($i, $reverse_temp)) {
                        $reverse_temp[$i] = [];
                    }
                }
                array_push($reverse, $reverse_temp);
            }
        }
        $result['data'] = $reverse;

        $sql = "SELECT COUNT(*)
            FROM meet.meet
            LEFT JOIN system.user ON meet.meet.recorder_user_id = system.user.id
            LEFT JOIN meet.meet_type ON meet.meet_type.id = meet.meet.meet_type_id
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_meet($datas)
    {
        $value = "";
        $values = [];
        foreach ($datas as $key => $data) {
            $value .= "(:name_{$key}, :recorder_user_id_{$key}, :meet_type_id_{$key} , :meet_date_{$key}),";
            $tmp = [
                "name" => "",
                "recorder_user_id" => null,
                "meet_type_id" => 10,
                "meet_date" => null
            ];
            foreach ($tmp as $tmp_key => $tmp_value) {
                $tmp[$tmp_key . "_{$key}"] = $tmp_value;
                if (array_key_exists($tmp_key, $data)) {
                    $tmp[$tmp_key . "_{$key}"] = $data[$tmp_key];
                }
                unset($tmp[$tmp_key]);
            }
            $values = array_merge($tmp, $values);
        }
        $value = rtrim($value, ",");

        $sql = "INSERT INTO meet.meet(name, recorder_user_id, meet_type_id , meet_date)
                VALUES {$value}
                RETURNING id";
        $stmt = $this->db->prepare($sql);

        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function post_meet_participant($datas)
    {
        $value = "";
        $values = [];

        foreach ($datas as $key => $data) {
            if (array_key_exists('participant', $data)) {
                foreach ($data['participant'] as $participant_key => $participant) {
                    $value .= "(:meet_id_{$key}_{$participant_key}, :user_id_{$key}_{$participant_key}),";
                    $tmp = [
                        "meet_id_{$key}_{$participant_key}" => $data['meet_id'],
                        "user_id_{$key}_{$participant_key}" => $participant
                    ];
    
                    $values = array_merge($tmp, $values);
                }
            }
        }
        $value = rtrim($value, ",");

        if($value == "") {
            return;
        }

        $sql = "INSERT INTO meet.participant(meet_id, user_id)
                VALUES {$value}
                ON CONFLICT (meet_id, user_id) DO NOTHING";
        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute($values);

        return $result;
    }

    public function patch_meet_participant($data)
    {
        $participant_set = "";
        $participant_value = [];

        foreach ($data['participant'] as $key => $participant) {
            $participant_set .= ":participant_{$key},";
            $participant_value["participant_{$key}"] = $participant;
        }

        $participant_set = rtrim($participant_set, ",");
        $participant_value["meet_id"] = $data['meet_id'];

        $sql = "DELETE FROM meet.participant 
                WHERE meet.participant.meet_id = :meet_id AND meet.participant.user_id NOT IN ($participant_set)";
        $stmt = $this->db->prepare($sql);
        $delete_result = $stmt->execute($participant_value);

        if ($delete_result) {
            $datas[0] = $data;

            if ($this->post_meet_participant($datas)) {
                $result = [
                    "status" => "success",
                ];
            } else {
                $result = [
                    "status" => "failed",
                ];
            }
        } else {
            $result = [
                "status" => "failed",
            ];
        }

        return $result;
    }

    public function patch_meet($data)
    {
        $value = "";
        $values = [
            "id" => 1,
            "name" => "",
            "recorder_user_id" => null,
            "meet_date" => null,
            "meet_type_id" => 1
        ];

        foreach ($values as $col_name => $col_val) {
            if (array_key_exists($col_name, $data)) {
                $value .= "{$col_name} = :{$col_name},";
                $values[$col_name] = $data[$col_name];
            } else {
                unset($values[$col_name]);
            }
        }
        $value = rtrim($value, ",");

        $sql = "UPDATE meet.meet
                SET {$value}
                WHERE meet.meet.id = :id";
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

    public function delete_meet($data)
    {
        $value = "";
        $values = [];
        foreach ($data["meet_id"] as $key => $id) {
            $value .= " meet.meet.id = :id_{$key} OR";
            $values["id_{$key}"] = $id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.meet 
                WHERE {$value}";
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

    public function get_meet_participant($data)
    {
        $result = [];

        $values = [
            "cur_page" => 1,
            "size" => 10
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];
        $sql = "SELECT *
            FROM
            (
                SELECT meet.participant.id, system.user.id user_id, system.user.name user_name, 
                    system.user.email user_email,ROW_NUMBER() OVER (ORDER BY system.user.id) AS row_num
                FROM meet.meet
                LEFT JOIN meet.participant ON meet.meet.id = meet.participant.meet_id
                LEFT JOIN system.user ON meet.participant.user_id = system.user.id
                WHERE meet.meet.id = :meet_id
                LIMIT {$length}
            ) meets
            WHERE meets.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':meet_id', $data['meet_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM meet.meet
                LEFT JOIN meet.participant ON meet.meet.id = meet.participant.meet_id
                LEFT JOIN system.user ON meet.participant.user_id = system.user.id
                WHERE meet.meet.id = :meet_id
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':meet_id', $data['meet_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function get_complaint($data)
    {

        $condition = "";
        $condition_values = [
            "input" => " AND (meet.complaint.complaint_file_name LIKE '%' || :input || '%' OR meet.complaint.subject LIKE '%' || :input || '%' OR system.user.name LIKE '%' || :input || '%' OR
                meet.complaint.complaint_record LIKE '%' || :input || '%' OR (to_char(meet.complaint.complaint_date, 'YYYY-MM-DD') LIKE '%' || :input || '%') OR 
                (to_char(meet.complaint.edit_date, 'YYYY-MM-DD') LIKE '%' || :input || '%') OR edit_user.name LIKE '%' || :input || '%')",
            "time_start" => " AND (meet.complaint.edit_date >= :time_start OR (meet.complaint.edit_date IS null AND meet.complaint.complaint_date >= :time_start))",
            "time_end" => " AND (meet.complaint.edit_date <= :time_end OR (meet.complaint.edit_date IS null AND meet.complaint.complaint_date <= :time_end))"
        ];

        foreach ($condition_values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $condition .= $condition_values[$key];
                $condition_values[$key] = $data[$key];
            } else {
                unset($condition_values[$key]);
            }
        }
        
        $no_serverside = 0;

        if (array_key_exists('size', $data) && $data['size'] == $no_serverside) {
            $sql = "SELECT meet.complaint.complaint_id, meet.complaint.complaint_file_name, meet.complaint.subject,
                        meet.complaint.complaint_date, meet.complaint.complaint_record, \"user\".name, meet.complaint.edit_date, edit_user.name edit_user_name
                    FROM meet.complaint
                    LEFT JOIN system.\"user\" ON system.\"user\".id = meet.complaint.user_id
                    LEFT JOIN (
                        SELECT id, name FROM system.\"user\" 
                    ) edit_user ON meet.complaint.edit_user_id = edit_user.id
					LEFT JOIN meet.meet ON meet.meet.id = meet.complaint.meet_id
					WHERE (meet.meet.meet_type_id IS NULL) $condition
                    ORDER BY meet.complaint.complaint_date DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($condition_values);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }

        $result = [];

        $values = [
            "cur_page" => 1,
            "size" => 10
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];
        $sql = "SELECT *
            FROM
            (
                SELECT meet.complaint.complaint_id, meet.complaint.complaint_file_name, subject, meet.complaint.complaint_date,
                    meet.complaint.complaint_record, system.\"user\".name, meet.complaint.edit_date, edit_user.name edit_user_name,
                    ROW_NUMBER() OVER (ORDER BY meet.complaint.complaint_id) AS row_num
                FROM meet.complaint
                LEFT JOIN system.\"user\" ON system.\"user\".id = meet.complaint.user_id
                LEFT JOIN (
                    SELECT id, name FROM system.\"user\" 
                ) edit_user ON complaint.edit_user_id = edit_user.id
                LEFT JOIN meet.meet ON meet.meet.id = meet.complaint.meet_id
				WHERE (meet.meet.meet_type_id IS NULL) $condition
                ORDER BY meet.complaint.complaint_date DESC
                LIMIT {$length}
            ) meets
            WHERE meets.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($condition_values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM meet.complaint
                LEFT JOIN system.\"user\" ON system.\"user\".id = meet.complaint.user_id
                LEFT JOIN (
                    SELECT id, name FROM system.\"user\" 
                ) edit_user ON complaint.edit_user_id = edit_user.id
                LEFT JOIN meet.meet ON meet.meet.id = meet.complaint.meet_id
				WHERE (meet.meet.meet_type_id IS NULL) $condition
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($condition_values);
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function get_today_complaint($data)
    {
        $result = [];

        $values = [
            "cur_page" => 1,
            "size" => 10
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];

        $condition = " IN (
            SELECT to_char(meet.meet.meet_date, 'YYYY-MM-DD')
            FROM meet.meet
            WHERE meet.meet.id = :meet_id)";

        if ($data['meet_id'] == 0) {
            $condition = " = to_char(NOW(), 'YYYY-MM-DD')";
        }

        $sql = "SELECT *
            FROM
            (
                SELECT meet.complaint.complaint_id, meet.complaint.subject, meet.complaint.img_id, 
                    meet.complaint.content, crm.customer.user_id customer_id, meet.complaint.complaint_date,
                    meet.complaint.complaint_customer_id customer_code, STRING_AGG(CAST(meet.attach_file.id as text), ',') file_id,
                    ROW_NUMBER() OVER (ORDER BY meet.complaint.complaint_id) AS row_num
                FROM meet.complaint
                LEFT JOIN crm.customer ON crm.customer.code = meet.complaint.complaint_customer_id
                LEFT JOIN meet.attach_file ON meet.attach_file.complaint_id = meet.complaint.complaint_id
                LEFT JOIN meet.meet ON meet.meet.id = meet.complaint.meet_id
				WHERE (meet.meet.meet_type_id IS NULL)
                    AND to_char(meet.complaint.complaint_date, 'YYYY-MM-DD') $condition
                GROUP BY meet.complaint.complaint_id, meet.complaint.subject, meet.complaint.img_id, 
                    meet.complaint.content, crm.customer.user_id, meet.complaint.complaint_date, 
                    crm.customer.code
                LIMIT {$length}
            ) meets
            WHERE meets.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);

        if ($data['meet_id'] != 0) {
            $stmt->bindValue(':meet_id', $data['meet_id'], PDO::PARAM_INT);
        }
        $stmt->execute();
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM meet.complaint
                LEFT JOIN meet.meet ON meet.meet.id = meet.complaint.meet_id
				WHERE (meet.meet.meet_type_id IS NULL)
                    AND to_char(meet.complaint.complaint_date, 'YYYY-MM-DD') $condition
            ";
        $stmt = $this->db->prepare($sql);
        if ($data['meet_id'] != 0) {
            $stmt->bindValue(':meet_id', $data['meet_id'], PDO::PARAM_INT);
        }
        $stmt->execute();
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_complaint($datas)
    {
        $value = "";
        $values = [];
        foreach ($datas as $key => $data) {
            $value .= "(:complaint_file_name_{$key}, :complaint_record_{$key}, 
                :complaint_content_original_{$key}, :delivery_meet_content_id_{$key}, :subject_{$key}
                , :content_{$key}, :note_{$key}, :meet_id_{$key}),";

            $tmp = [
                "complaint_file_name" => null,
                "complaint_record" => null,
                "complaint_content_original" => null,
                'delivery_meet_content_id' => null,
                'subject' => null,
                'content' => null,
                'note' => null,
                'meet_id' => null
            ];
            foreach ($tmp as $tmp_key => $tmp_value) {
                $tmp[$tmp_key . "_{$key}"] = $tmp_value;
                if (array_key_exists($tmp_key, $data)) {
                    $tmp[$tmp_key . "_{$key}"] = $data[$tmp_key];
                }
                unset($tmp[$tmp_key]);
            }
            $values = array_merge($tmp, $values);
        }
        $value = rtrim($value, ",");

        $sql = "INSERT INTO meet.complaint(complaint_file_name, complaint_record, complaint_content_original, 
                    delivery_meet_content_id, subject, content, note, meet_id)
                VALUES {$value}
                RETURNING complaint_id";
        $stmt = $this->db->prepare($sql);

        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function patch_complaint($data)
    {
        $value = "";
        $values = [
            'complaint_id' => 1,
            'complaint_file_name' => '',
            'complaint_record' => '',
            'complaint_content_original' => '',
            'delivery_meet_content_id' => null,
            'subject' => null,
            'content' => null,
            'note' => null,
        ];


        foreach ($values as $col_name => $col_val) {
            if (array_key_exists($col_name, $data)) {
                $value .= "{$col_name} = :{$col_name},";
                $values[$col_name] = $data[$col_name];
            } else {
                unset($values[$col_name]);
            }
        }
        $value = rtrim($value, ",");

        $sql = "UPDATE meet.complaint
                SET {$value}
                WHERE meet.complaint.complaint_id = :complaint_id";
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

    public function delete_complaint($data)
    {
        $complaint_ids = [];
        if (array_key_exists("complaint_id", $data)) {
            $complaint_ids = $data["complaint_id"];
        }
        $value = "";
        $values = [];
        foreach ($complaint_ids as $key => $complaint_id) {
            $value .= " meet.complaint.complaint_id = :complaint_id_{$key} OR";
            $values["complaint_id_{$key}"] = $complaint_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.complaint
                WHERE {$value}";
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

    public function get_sale_meet($data)
    {
        $sql = "SELECT meet.meet.name meet_name, meet.meet_type.id meet_type_id, meet.meet_type.name meet_type_name, participant.user_id participant, meet.meet.recorder_user_id,  recorder.name recorder_name, 
                    to_char(meet.meet.meet_date, 'YYYY-MM-DD') meet_date,
                    modify.modify_user_id, modify.modify_user_name,  
                    to_char(modify.modify_time, 'YYYY-MM-DD') modify_time,
                    meet.complaint.complaint_record meet_record, meet.complaint.complaint_file_name, 
                    meet.complaint.complaint_content_original, 
                    customer_img.file_id customer_img, factory_img.file_id factory_img
                FROM meet.meet
                LEFT JOIN meet.meet_type ON meet.meet_type.id = meet.meet.meet_type_id
                LEFT JOIN (
                    SELECT meet.participant.meet_id, STRING_AGG(CAST(meet.participant.user_id as text), ',') user_id
                    FROM meet.participant
                    GROUP BY meet.participant.meet_id
                )participant ON participant.meet_id = meet.meet.id
                LEFT JOIN (
                    SELECT meet.modify_meet_record.meet_id, meet.modify_meet_record.modify_time, 
                        meet.modify_meet_record.modify_user_id, system.user.name modify_user_name
                    FROM meet.modify_meet_record
                    LEFT JOIN system.user ON system.user.id = meet.modify_meet_record.modify_user_id
                ) modify ON modify.meet_id = meet.meet.id AND modify.modify_time IN (
                        SELECT MAX(meet.modify_meet_record.modify_time) modify_time
                        FROM meet.modify_meet_record
                        GROUP BY meet.modify_meet_record.meet_id)
                LEFT JOIN system.user AS recorder ON recorder.id = meet.meet.recorder_user_id
                LEFT JOIN meet.complaint ON meet.complaint.meet_id = meet.meet.id
                LEFT JOIN (
                    SELECT meet.attach_file.complaint_id, STRING_AGG(CAST(meet.attach_file.id as text), ',') file_id
                    FROM meet.attach_file
                    LEFT JOIN meet.file_image_type ON meet.file_image_type.attach_file_id = meet.attach_file.id
                    WHERE meet.file_image_type.image_type_id = 1
                    GROUP BY meet.attach_file.complaint_id
                )customer_img ON customer_img.complaint_id = meet.complaint.complaint_id
                LEFT JOIN (
                    SELECT meet.attach_file.complaint_id, STRING_AGG(CAST(meet.attach_file.id as text), ',') file_id
                    FROM meet.attach_file
                    LEFT JOIN meet.file_image_type ON meet.file_image_type.attach_file_id = meet.attach_file.id
                    WHERE meet.file_image_type.image_type_id = 2
                    GROUP BY meet.attach_file.complaint_id
                )factory_img ON factory_img.complaint_id = meet.complaint.complaint_id
                WHERE meet.meet.id = :meet_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':meet_id', $data['meet_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function get_sale_meet_complaint_id($data)
    {
        $sql = "SELECT meet.complaint.complaint_id
                FROM meet.complaint
                WHERE meet.complaint.meet_id = :meet_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':meet_id', $data['meet_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn(0);

        if (!$result && array_key_exists('meet_id', $data)) {
            $sql = "INSERT INTO meet.complaint(meet_id, complaint_date)
                    VALUES (:meet_id, NOW())
                    RETURNING complaint_id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':meet_id', $data['meet_id'], PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchColumn(0);
        }

        return $result;
    }

    public function get_attach_file($data)
    {
        $sql = "SELECT meet.attach_file.id, meet.attach_file.complaint_id, meet.attach_file.name, meet.attach_file.content
                FROM meet.attach_file
                WHERE meet.attach_file.complaint_id = :complaint_id
                ORDER BY id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':complaint_id', $data['complaint_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function get_attach_file_by_id($data)
    {
        $sql = "SELECT meet.attach_file.name
                FROM meet.attach_file
                WHERE id=:id
                ORDER BY id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_attach_file($data)
    {
        $sql = "INSERT INTO meet.attach_file(complaint_id, name, content)
                VALUES (:complaint_id, :name, :content)
                RETURNING id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);

        if (!array_key_exists('complaint_id', $data)) {
            $stmt->bindValue(':complaint_id', null, PDO::PARAM_BOOL);
        } else {
            $stmt->bindValue(':complaint_id', $data['complaint_id'], PDO::PARAM_INT);
        }
        if (!array_key_exists('content', $data)) {
            $stmt->bindValue(':content', null, PDO::PARAM_BOOL);
        } else {
            $stmt->bindValue(':content', $data['content'], PDO::PARAM_STR);
        }

        $stmt->execute();
        $result = $stmt->fetchColumn(0);

        return $result;
    }

    public function delete_attach_file($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $file_id) {
            $value .= " meet.attach_file.id = :id_{$key} OR";
            $values["id_{$key}"] = $file_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.attach_file
                WHERE {$value}";
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

    public function break_attach_file_link($data)
    {
        $values = [
            "id" => null,
            "complaint_id" => null
        ];

        $values['id'] = $data['id'];

        $sql = "UPDATE meet.attach_file
                SET complaint_id = :complaint_id
                WHERE meet.attach_file.id = :id";
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

    public function post_file_image_type($data)
    {
        $sql = "INSERT INTO meet.file_image_type(attach_file_id, image_type_id)
                VALUES (:attach_file_id, :image_type_id)
                ";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':attach_file_id', $data['attach_file_id'], PDO::PARAM_INT);
        $stmt->bindValue(':image_type_id', $data['image_type'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            $result = [
                "status" => "success"
            ];
        } else {
            $result = [
                "status" => "failed"
            ];
        }

        return $result;
    }

    public function upload_file($data)
    {
        $uploadedFiles = $data['files'];
        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['inputFile'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->moveUploadedFile($this->container->upload_directory, $uploadedFile);
            $result = array(
                'file_name' => $filename
            );
        } else {
            $result = array(
                'status' => 'failed'
            );
        }
        return $result;
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

    public function upload_delivery_meet_content_file($data)
    {
        $uploadedFiles = $data['files'];
        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['inputFile'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->moveUploadedFile($this->container->upload_directory, $uploadedFile);
            $filepath = $this->container->upload_directory . $filename;
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'jpg') {
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
            }
            $result = array(
                'status' => 'success',
                'delivery_meet_content_file_name' => $filename,
                'listAll' => false
            );
        } else {
            $result = array(
                'status' => 'failed'
            );
        }
        return $result;
    }

    public function decompress_delivery_meet_content_file($data)
    {
        $home = new Home($this->db);
        $files_picture = [];
        $files = '[]';
        if (pathinfo($data['delivery_meet_content_file_name'], PATHINFO_EXTENSION) === 'msg' || pathinfo($data['delivery_meet_content_file_name'], PATHINFO_EXTENSION) === 'zip') {
            if(pathinfo($data['delivery_meet_content_file_name'], PATHINFO_EXTENSION) === 'zip'){
                $files = [[$data['delivery_meet_content_file_name']]];
            }else if(pathinfo($data['delivery_meet_content_file_name'], PATHINFO_EXTENSION) === 'msg'){
                $files = $this->getMessageParse([$data['delivery_meet_content_file_name']]);
            }
            foreach ($files as $file) {
                $files = $file;
            }
            $files_temp = $files;
            foreach ($files as $file_name) {
                if (pathinfo($file_name, PATHINFO_EXTENSION) === 'zip') {

                    $zip = new ZipArchive;
                    $path = $this->container->upload_directory . DIRECTORY_SEPARATOR . $file_name;

                    if ($zip->open($path) === TRUE) {

                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $zip_file = $zip->getNameIndex($i);
                            $extension = pathinfo($zip_file, PATHINFO_EXTENSION);
                            $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
                            $filename = sprintf('%s%s.%0.8s', $basename, $i, $extension);
                            $clean_file_name = $filename;
                            $zip_file_info = pathinfo($zip_file);
                            copy("zip://" . $path . "#" . $zip_file, $this->container->upload_directory . DIRECTORY_SEPARATOR . $clean_file_name);
                            
                            $files_temp[$filename] = $clean_file_name;
                        }
                        $zip->close();
                    }
                } else {
                    $files_temp[$file_name] = $file_name;
                }
            }
            $files = $files_temp;
            $files_picture = array_filter($files, function ($file) {
                return in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'png', 'jpeg']);
            });
            if ($data['listAll']) {
                $files_picture = array_filter($files, function ($file) {
                    return !in_array(pathinfo($file, PATHINFO_EXTENSION), ['pdf', 'PDF']);
                });
            }
            $files = json_encode(array_values(array_filter($files, function ($file) {
                return in_array(pathinfo($file, PATHINFO_EXTENSION), ['pdf', 'PDF']);
            })));
        } else if (strtolower(pathinfo($data['delivery_meet_content_file_name'], PATHINFO_EXTENSION)) === 'pdf') {
            $files = json_encode([$data['delivery_meet_content_file_name']]);
        } else if (in_array(pathinfo($data['delivery_meet_content_file_name'], PATHINFO_EXTENSION), ['jpg', 'png', 'jpeg'])) {
            $files_picture[] = [$data['delivery_meet_content_file_name']];
        }
        $recogUrl = "http://mil_python:8090/pdfSplit?Files={$files}";
        $result = $home->http_response($recogUrl);
        $result = json_decode($result, true);
        $result = is_null($result) ? [] : $result;
        $result += $files_picture;
        return $result;
    }

    //Move the uploaded file from register to the correct directory.
    function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        return $filename;
    }

    public function insert_delivery_meet_content_file($data)
    {
        $value = '';
        if (array_key_exists('files', $data)) {
            foreach ($data['files'] as $files) {
                foreach ($files as $sequence => $file) {
                    $values["delivery_meet_content_id_" . $sequence] = $data['delivery_meet_content_id'];
                    $values["delivery_meet_content_file_name_" . $sequence] = $file;
                    $value .= ",(:delivery_meet_content_id_{$sequence},:delivery_meet_content_file_name_{$sequence})";
                }
            }
        }
        if (empty($values)) {
            return ["status" => "failed"];
        }
        $value = ltrim($value, ',');
        $sql = "INSERT INTO meet.delivery_meet_content_file(
            delivery_meet_content_id, delivery_meet_content_file_name)
            VALUES {$value} 
            RETURNING delivery_meet_content_file_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert_complaint_file($data)
    {
        $value = '';
        $sequence = 0;
        if (array_key_exists('files', $data)) {
            foreach ($data['files'] as $files) {
                if (gettype($files) === 'array') {
                    foreach ($files as $file) {
                        $values["complaint_id_" . $sequence] = $data['complaint_id'];
                        $values["name_" . $sequence] = $file;
                        $value .= ",(:complaint_id_{$sequence},:name_{$sequence})";
                        $sequence++;
                    }
                } else {
                    $values["complaint_id_" . $sequence] = $data['complaint_id'];
                    $values["name_" . $sequence] = $files;
                    $value .= ",(:complaint_id_{$sequence},:name_{$sequence})";
                    $sequence++;
                }
            }
        }
        if (empty($values)) {
            return ["status" => "failed"];
        }
        $value = ltrim($value, ',');
        $sql = "INSERT INTO meet.attach_file(
            complaint_id, name)
            VALUES {$value}
            RETURNING id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_delivery_meet_content_file($data)
    {
        $sql = "SELECT delivery_meet_content_file_name 
            FROM meet.delivery_meet_content_file
            WHERE delivery_meet_content_file_id = :delivery_meet_content_file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $file) {
            return $this->container->upload_directory . '/' . $file['delivery_meet_content_file_name'];
        }
    }

    function get_user_module($data)
    {
        $sql = "SELECT user_module.user_id, user_module.user_name, user_module.user_email, 
                    STRING_AGG(user_module.module_name, ',') module_name
                FROM 
                (
                    SELECT system.user.id user_id, system.user.uid user_uid, system.user.name user_name, 
                        system.user.email user_email, setting.module.id module_id, setting.module.name module_name
                    FROM system.user
                    LEFT JOIN system.user_modal ON system.user_modal.uid = system.user.id
                    LEFT JOIN setting.module ON setting.module.id = system.user_modal.module_id
                    ORDER BY setting.module.id
                )  user_module
				WHERE user_module.user_id = :user_id
                GROUP BY user_module.user_id, user_module.user_name, user_module.user_email
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $data['user_id']);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    function get_all_user_module()
    {
        $sql = "SELECT user_module.user_id, user_module.user_name, user_module.user_email, 
                    STRING_AGG(user_module.module_name, ',') module_name
                FROM 
                (
                    SELECT system.user.id user_id, system.user.uid user_uid, system.user.name user_name, 
                        system.user.email user_email, setting.module.id module_id, setting.module.name module_name
                    FROM system.user
                    LEFT JOIN system.user_modal ON system.user_modal.uid = system.user.id
                    LEFT JOIN setting.module ON setting.module.id = system.user_modal.module_id
                    ORDER BY setting.module.id
                )  user_module
                GROUP BY user_module.user_id, user_module.user_name, user_module.user_email
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    function get_all_module()
    {
        $sql = "SELECT setting.module.id, setting.module.name
                FROM setting.module
                ORDER BY setting.module.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    function get_all_user($data)
    {
        $sql = "SELECT id, name
                FROM system.user
                ORDER BY id
        ";

        if (array_key_exists('module_id', $data)) {
            $sql = "SELECT system.user.id, system.user.name
                    FROM system.user
                    LEFT JOIN system.user_modal ON system.user_modal.uid = system.user.id
                    LEFT JOIN setting.module ON setting.module.id = system.user_modal.module_id
                    WHERE setting.module.id = :module_id
                    ORDER BY setting.module.id
                    ";
        }

        $stmt = $this->db->prepare($sql);
        if (array_key_exists('module_id', $data)) {
            $stmt->bindValue(':module_id', $data['module_id']);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    function get_user_participant($data)
    {
        $sql = "SELECT id, name
                FROM system.user
                WHERE system.user.uid LIKE '99%'
                ORDER BY id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    function get_image_type()
    {
        $sql = "SELECT id, name
                FROM meet.image_type
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    function get_frequent_user($data)
    {
        $frequent_group_id = "1";

        if (array_key_exists('frequent_group_id', $data)) {
            $frequent_group_id = ":frequent_group_id";
        }

        $sql = "SELECT meet.frequent_participant.id AS value, system.user.name AS label
                FROM meet.frequent_participant
                LEFT JOIN system.user ON system.user.id = meet.frequent_participant.id
                WHERE meet.frequent_participant.frequent_group_id = $frequent_group_id
                GROUP BY meet.frequent_participant.id, system.user.uid, system.user.name, system.user.email, meet.frequent_participant.order
                ORDER BY meet.frequent_participant.order
        ";
        $stmt = $this->db->prepare($sql);

        if (array_key_exists('frequent_group_id', $data)) {
            $stmt->bindValue(':frequent_group_id', $data['frequent_group_id']);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function post_frequent_user($data)
    {
        $value = "";
        $values = [];

        foreach ($data['participant'] as $order => $participant) {
            $value .= "(:id_{$order}, :frequent_group_id_{$order}, :order_{$order}),";
            $tmp = [
                "frequent_group_id_{$order}" => $data['frequent_group_id'],
                "id_{$order}" => $participant,
                "order_{$order}" => $order
            ];

            $values = array_merge($tmp, $values);
        }

        $value = rtrim($value, ",");

        $sql = "INSERT INTO meet.frequent_participant(id, frequent_group_id, \"order\")
                VALUES {$value}
                ON CONFLICT (id, frequent_group_id) DO UPDATE SET \"order\" = EXCLUDED.\"order\"";
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

    public function patch_frequent_user($data)
    {
        $participant_set = "";
        $participant_value = [];

        foreach ($data['participant'] as $key => $participant) {
            $participant_set .= ":participant_{$key},";
            $participant_value["participant_{$key}"] = $participant;
        }

        $participant_set = rtrim($participant_set, ",");
        $participant_value["frequent_group_id"] = $data['frequent_group_id'];

        $sql = "DELETE FROM meet.frequent_participant 
                WHERE meet.frequent_participant.frequent_group_id = :frequent_group_id AND meet.frequent_participant.id NOT IN ($participant_set)";
        $stmt = $this->db->prepare($sql);
        $delete_result = $stmt->execute($participant_value);

        if ($delete_result) {
            if ($this->post_frequent_user($data)) {
                $result = [
                    "status" => "success",
                ];
            } else {
                $result = [
                    "status" => "failed",
                ];
            }
        } else {
            $result = [
                "status" => "failed",
            ];
        }

        return $result;
    }

    function get_frequent_group($data)
    {
        $module_sql = "SELECT system.user_modal.module_id
                FROM system.user_modal
                WHERE system.user_modal.uid = :id AND system.user_modal.module_id = 7";
        $stmt = $this->db->prepare($module_sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();
        $module = $stmt->fetchColumn(0);

        if($module == 7) {
            $sql = "SELECT meet.frequent_group.frequent_group_id, meet.frequent_group.frequent_group_name, STRING_AGG(user_name.user, ',') AS participant, meet.frequent_group.founder_id, system.user.name founder_name
                    FROM meet.frequent_group
                    LEFT JOIN (
                        SELECT meet.frequent_participant.frequent_group_id,CONCAT(meet.frequent_participant.id, '*', system.user.name) AS user
                        FROM meet.frequent_participant 
                        LEFT JOIN system.user ON system.user.id = meet.frequent_participant.id
                    )user_name ON user_name.frequent_group_id = meet.frequent_group.frequent_group_id
                    LEFT JOIN system.user ON system.user.id = meet.frequent_group.founder_id
                    WHERE meet.frequent_group.frequent_group_name LIKE '%' || :search || '%'
                    GROUP BY meet.frequent_group.frequent_group_id, meet.frequent_group.frequent_group_name, meet.frequent_group.founder_id, system.user.name
                    ORDER BY meet.frequent_group.frequent_group_id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':search', $data['search'], PDO::PARAM_STR);
            $stmt->execute();
        }
        else {
            $sql = "SELECT meet.frequent_group.frequent_group_id, meet.frequent_group.frequent_group_name, STRING_AGG(user_name.user, ',') AS participant, meet.frequent_group.founder_id, system.user.name founder_name
                    FROM meet.frequent_group
                    LEFT JOIN (
                        SELECT meet.frequent_participant.frequent_group_id,CONCAT(meet.frequent_participant.id, '*', system.user.name) AS user
                        FROM meet.frequent_participant 
                        LEFT JOIN system.user ON system.user.id = meet.frequent_participant.id
                    )user_name ON user_name.frequent_group_id = meet.frequent_group.frequent_group_id
                    LEFT JOIN system.user ON system.user.id = meet.frequent_group.founder_id
                    WHERE  meet.frequent_group.founder_id = :id AND meet.frequent_group.frequent_group_name LIKE '%' || :search || '%'
                    GROUP BY meet.frequent_group.frequent_group_id, meet.frequent_group.frequent_group_name, meet.frequent_group.founder_id, system.user.name
                    ORDER BY meet.frequent_group.frequent_group_id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $stmt->bindValue(':search', $data['search'], PDO::PARAM_STR);
            $stmt->execute();
        }
        
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function post_frequent_group($data)
    {

        $sql = "INSERT INTO meet.frequent_group(frequent_group_name, founder_id)
                VALUES (:frequent_group_name, :founder_id)
                RETURNING frequent_group_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':frequent_group_name', $data['frequent_group_name'], PDO::PARAM_STR);
        $stmt->bindValue(':founder_id', $data['founder_id'], PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchColumn(0);
        return $result;
    }

    public function patch_frequent_group($data)
    {
        if (array_key_exists('frequent_group_name', $data) || array_key_exists('founder_id', $data)) {

            $condition = '';

            if (array_key_exists('frequent_group_name', $data)) {
                $condition = 'frequent_group_name = :frequent_group_name,';
            }
            if (array_key_exists('founder_id', $data)) {
                $condition .= 'founder_id = :founder_id,';
            }

            $condition = rtrim($condition, ',');

            $sql = "UPDATE meet.frequent_group
                    SET $condition
                    WHERE meet.frequent_group.frequent_group_id = :frequent_group_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':frequent_group_id', $data['frequent_group_id'], PDO::PARAM_INT);

            if (array_key_exists('frequent_group_name', $data)) {
                $stmt->bindValue(':frequent_group_name', $data['frequent_group_name'], PDO::PARAM_STR);
            }
            if (array_key_exists('founder_id', $data)) {
                $stmt->bindValue(':founder_id', $data['founder_id'], PDO::PARAM_INT);
            }

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
        } else {
            return;
        }
    }

    public function delete_frequent_group($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $frequent_group_id) {
            $value .= " meet.frequent_group.frequent_group_id = :id_{$key} OR";
            $values["id_{$key}"] = $frequent_group_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.frequent_group
                WHERE {$value}";
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

    function get_user($data)
    {
        $sql = "SELECT id, name
                FROM system.user
                WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    function post_user($data)
    {
        $values = [
            "name" => null,
            "email" => null,
            "gender" => null,
            "editor_id" => null,
            "country" => null
        ];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $sql = "INSERT INTO system.user(uid, name, email , gender, editor_id, edit_time, country)
                VALUES (nextval('system.tmp_uid_seq'::regclass), :name, :email, :gender, :editor_id, NOW(), :country)
                ON CONFLICT
                DO NOTHING
        ";
        $stmt = $this->db->prepare($sql);

        if ($stmt->execute($values)) {
            $result['status'] = 'success';
        } else {
            $result['status'] = 'failed';
        }
        return $result;
    }

    function delete_user($datas)
    {
        $value = "";
        $values = [];
        $values["editor_id"] = $datas['editor_id'];
        foreach ($datas['user'] as $key => $id) {
            $value .= " system.user.id = :id_{$key} OR";
            $values["id_{$key}"] = $id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM system.user
                WHERE ({$value}) AND system.user.editor_id = :editor_id";
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
    
    function get_meet_type()
    {
        $sql = "SELECT id, name
                FROM meet.meet_type
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    function post_meet_type($data)
    {
        $sql = "INSERT INTO meet.meet_type(id, name)
                VALUES (nextval('meet.meet_type_id_seq'::regclass), :name)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            $result['status'] = 'success';
        } else {
            $result['status'] = 'failed';
        }
        return $result;
    }

    function delete_meet_type($datas)
    {
        $value = "";
        $values = [];
        foreach ($datas['meet_type_id'] as $key => $id) {
            $value .= " meet.meet_type.id = :id_{$key} OR";
            $values["id_{$key}"] = $id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.meet_type
                WHERE {$value}";
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

    function get_column_order()
    {
        $sql = "SELECT id, name, \"column\"
                FROM meet.column_order
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function get_order($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT TD206 AS hardness, [CMSXB].XB002 AS material, [CMSXC].[XC002] AS titanium_plating,
                                [COPTC].[TC006] AS customer_code, [COPTC].[TC025] AS number,[COPTC].[TC014] AS factory_delivery_date
                            FROM [MIL].[dbo].COPTD
                            LEFT JOIN [MIL].[dbo].COPTC ON COPTC.TC001 = COPTD.TD001 AND COPTD.TD002 = COPTC.TC002
                            LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTD.TD205
                            LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                            WHERE COPTD.TD001 = {$data['coptd_td001']} AND COPTD.TD002 = {$data['coptd_td002']}
                                AND COPTD.TD003 = {$data['coptd_td003']}
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

    public function get_quotation($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT TB206 AS hardness, [CMSXB].XB002 AS material, [CMSXC].[XC002] AS titanium_plating, 
                                [COPTA].[TA006] AS customer_code, [COPTA].[TA025] AS number,[COPTA].[TA014] AS factory_delivery_date
                            FROM [MIL].[dbo].COPTB
                            LEFT JOIN [MIL].[dbo].COPTA ON COPTA.TA001 = COPTB.TB001 AND COPTB.TB002 = COPTA.TA002
                            LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                            LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTB.TB204
                            WHERE COPTB.TB001 = {$data['coptb_tb001']} AND COPTB.TB002 = {$data['coptb_tb002']}
                                AND COPTB.TB003 = {$data['coptb_tb003']}
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

    public function post_complaint_fk($datas)
    {
        $value = "";
        $values = [];

        foreach ($datas as $key => $data) {
            foreach ($data['fk'] as $fk_key => $fk_value) {
                $value .= "(:complaint_id_{$key}_{$fk_key}, :complaint_fk_{$key}_{$fk_key}, :value_{$key}_{$fk_key}),";
                $tmp = [
                    "complaint_id_{$key}_{$fk_key}" => $data['complaint_id'],
                    "complaint_fk_{$key}_{$fk_key}" => $fk_key,
                    "value_{$key}_{$fk_key}" => $fk_value
                ];

                $values = array_merge($tmp, $values);
            }
        }
        $value = rtrim($value, ",");

        $sql = "INSERT INTO meet.complaint_fk(complaint_id, complaint_fk, value)
                VALUES {$value}";
        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute($values);

        return $result;
    }

    public function get_delivery_meet_content_id($data)
    {
        $sql = "SELECT meet.delivery_meet_content.delivery_meet_content_id
                FROM meet.delivery_meet_content
                LEFT JOIN meet.complaint ON meet.complaint.delivery_meet_content_id = meet.delivery_meet_content.delivery_meet_content_id
                LEFT JOIN meet.meet ON meet.meet.id = meet.complaint.meet_id
                WHERE meet.meet.id = :meet_id
                GROUP BY meet.delivery_meet_content.delivery_meet_content_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':meet_id', $data['meet_id']);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function get_modify_meet_record($data)
    {
        $result = [];

        $values = [
            "cur_page" => 1,
            "size" => 10
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];

        $condition = "TRUE ";
        $condition_values = [
            "meet_type_id" => " AND meet.meet.meet_type_id = :meet_type_id",
            "meet_id" => " AND meet.meet.id = :meet_id"
        ];

        foreach ($condition_values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $condition .= $condition_values[$key];
                $condition_values[$key] = $data[$key];
            } else {
                unset($condition_values[$key]);
            }
        }

        $sql = "SELECT *
            FROM
            (
                SELECT meet.modify_meet_record.id modify_meet_record_id, meet.meet.id meet_id, meet.meet.name meet_name, 
                    meet.meet.meet_type_id, meet.modify_meet_record.modify_user_id, system.user.name modify_user_name, 
                    meet.modify_meet_record.modify_time, 
                    ROW_NUMBER() OVER (ORDER BY meet.modify_meet_record.id) AS row_num
                FROM meet.modify_meet_record
                LEFT JOIN meet.meet ON meet.meet.id = meet.modify_meet_record.meet_id
                LEFT JOIN system.user ON system.user.id = meet.modify_meet_record.modify_user_id
                WHERE {$condition}
                GROUP BY meet.modify_meet_record.id, meet.meet.id, meet.meet.name, 
                    meet.meet.meet_type_id, meet.modify_meet_record.modify_user_id, system.user.name, 
                    meet.modify_meet_record.modify_time
                ORDER BY meet.modify_meet_record.id, meet.modify_meet_record.modify_time DESC
                LIMIT {$length}
            ) discuss
            WHERE discuss.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($condition_values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM meet.modify_meet_record
                LEFT JOIN meet.meet ON meet.meet.id = meet.modify_meet_record.meet_id
                LEFT JOIN system.user ON system.user.id = meet.modify_meet_record.modify_user_id
                WHERE {$condition}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($condition_values);
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_modify_meet_record($data)
    {
        $values = [
            "meet_id" => null,
            "modify_user_id" => null
        ];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $sql = "INSERT INTO meet.modify_meet_record(meet_id, modify_user_id, modify_time)
                VALUES (:meet_id, :modify_user_id, NOW())
                ";
        $stmt = $this->db->prepare($sql);

        if ($stmt->execute($values)) {
            $result['status'] = 'success';
        } else {
            $result['status'] = 'failed';
        }

        return $result;
    }

    public function delete_modify_meet_record($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $id) {
            $value .= " meet.modify_meet_record.id = :id_{$key} OR";
            $values["id_{$key}"] = $id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.modify_meet_record
                WHERE {$value}";
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

    public function get_discuss($data)
    {
        $result = [];

        $values = [
            "cur_page" => 1,
            "size" => 10
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];
        $sql = "SELECT *
            FROM
            (
                SELECT meet.discuss.discuss_id, meet.discuss.discuss_name, meet.discuss.discuss_content, 
                to_char(meet.discuss.create_date, 'YYYY-MM-DD') create_date, meet.discuss.is_over, ROW_NUMBER() OVER (ORDER BY meet.discuss.discuss_id) AS row_num
                FROM meet.discuss
                ORDER BY meet.discuss.discuss_id
                LIMIT {$length}
            ) discuss
            WHERE discuss.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM meet.discuss
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_discuss($data)
    {
        $values = [
            "discuss_name" => "",
            "discuss_content" => null
        ];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $sql = "INSERT INTO meet.discuss(
            discuss_name, discuss_content, create_date, is_over)
            VALUES (:discuss_name, :discuss_content, NOW(), false)
            RETURNING discuss_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function patch_discuss_over($data)
    {
        $sql = "UPDATE meet.discuss
                SET is_over = :is_over
                WHERE meet.discuss.discuss_id = :discuss_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':discuss_id', $data['discuss_id'], PDO::PARAM_INT);
        $stmt->bindValue(':is_over', $data['is_over'], PDO::PARAM_BOOL);

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

    public function delete_discuss($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $discuss_id) {
            $value .= " meet.discuss.discuss_id = :id_{$key} OR";
            $values["id_{$key}"] = $discuss_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.discuss
                WHERE {$value}";
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

    public function get_tracking($data)
    {
        $result = [];

        $values = [
            "cur_page" => 1,
            "size" => 10
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $condition = "TRUE ";
        $condition_values = [
            "input" => " AND (system.user.name LIKE '%' || :input || '%' OR user_module.module_name LIKE '%' || :input || '%' OR meet.tracking.name LIKE '%' || :input || '%' OR
                meet.tracking.content LIKE '%' || :input || '%' OR (to_char(meet.tracking.create_date, 'YYYY-MM-DD') LIKE '%' || :input || '%') OR (to_char(meet.tracking.complete_date, 'YYYY-MM-DD') LIKE '%' || :input || '%'))",
            "complete" => " AND meet.tracking.is_complete = :complete",
            "time_start" => " AND meet.tracking.create_date >= :time_start",
            "time_end" => " AND meet.tracking.create_date <= :time_end"
        ];

        foreach ($condition_values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $condition .= $condition_values[$key];
                $condition_values[$key] = $data[$key];
            } else {
                unset($condition_values[$key]);
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];
        $sql = "SELECT *
            FROM
            (
                SELECT meet.tracking.id track_id, meet.tracking.person_in_charge_id, 
                    system.user.name person_in_charge_name, user_module.module_name, meet.tracking.name, 
                    meet.tracking.content,to_char(meet.tracking.create_date, 'YYYY-MM-DD') AS create_date, 
                    meet.tracking.is_complete, to_char(meet.tracking.complete_date, 'YYYY-MM-DD') complete_date, 
                    ROW_NUMBER() OVER (ORDER BY meet.tracking.id) AS row_num
                FROM meet.tracking
                LEFT JOIN system.user ON system.user.id = meet.tracking.person_in_charge_id
                LEFT JOIN (
                    SELECT system.user.id user_id, STRING_AGG(setting.module.name, ',') module_name
                    FROM system.user
                    LEFT JOIN system.user_modal ON system.user_modal.uid = system.user.id
                    LEFT JOIN setting.module ON setting.module.id = system.user_modal.module_id
                    GROUP BY system.user.id
                ) user_module ON user_module.user_id = meet.tracking.person_in_charge_id
                WHERE $condition
                ORDER BY meet.tracking.id
                LIMIT {$length}
            ) tracking
            WHERE tracking.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        if (array_key_exists('complete', $data)) {
            $stmt->bindValue(':complete', $data['complete'], PDO::PARAM_BOOL);
        }
        $stmt->execute($condition_values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM meet.tracking
                LEFT JOIN system.user ON system.user.id = meet.tracking.person_in_charge_id
                LEFT JOIN (
                    SELECT system.user.id user_id, STRING_AGG(setting.module.name, ',') module_name
                    FROM system.user
                    LEFT JOIN system.user_modal ON system.user_modal.uid = system.user.id
                    LEFT JOIN setting.module ON setting.module.id = system.user_modal.module_id
                    GROUP BY system.user.id
                ) user_module ON user_module.user_id = meet.tracking.person_in_charge_id
                WHERE $condition
            ";
        $stmt = $this->db->prepare($sql);
        if (array_key_exists('complete', $data)) {
            $stmt->bindValue(':complete', $data['complete'], PDO::PARAM_BOOL);
        }
        $stmt->execute($condition_values);
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_tracking($data)
    {
        $values = [
            "complaint_id" => null,
            "person_in_charge_id" => null,
            "name" => "",
            "content" => ""
        ];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $sql = "INSERT INTO meet.tracking(
            complaint_id, person_in_charge_id, name, content, create_date, is_complete)
            VALUES (:complaint_id, :person_in_charge_id, :name, :content, NOW(), false)
            RETURNING id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function patch_tracking_complete($data)
    {
        $sql = "UPDATE meet.tracking
                SET is_complete = :is_complete, complete_date = :complete_date
                WHERE meet.tracking.id = :tracking_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tracking_id', $data['tracking_id'], PDO::PARAM_INT);
        $stmt->bindValue(':is_complete', $data['complete'], PDO::PARAM_BOOL);

        if ($data['complete'] == "true") {
            $stmt->bindValue(':complete_date', "NOW()");
        } else {
            $stmt->bindValue(':complete_date', null);
        }

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

    public function delete_tracking($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $tracking_id) {
            $value .= " meet.tracking.id = :id_{$key} OR";
            $values["id_{$key}"] = $tracking_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.tracking
                WHERE {$value}";
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

    public function get_tracking_process($data)
    {
        $result = [];

        $values = [
            "cur_page" => 1,
            "size" => 10
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $condition = "TRUE ";
        $condition_values = [
            "meet_id" => " AND meet.tracking_process.meet_id = :meet_id",
            "tracking_id" => " AND meet.tracking_process.tracking_id = :tracking_id"
        ];

        foreach ($condition_values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $condition .= $condition_values[$key];
                $condition_values[$key] = $data[$key];
            } else {
                unset($condition_values[$key]);
            }
        }

        $length = $values['cur_page'] * $values['size'];
        $start = $length - $values['size'];
        $sql = "SELECT *
            FROM
            (
                SELECT meet.tracking_process.id, meet.tracking.id tracking_id, meet.tracking.name tracking_name, meet.meet.id meet_id, meet.meet.name meet_name, 
                    to_char(meet.tracking_process.date, 'YYYY-MM-DD') tracking_process_date, 
                    meet.tracking_process.conclusion, ROW_NUMBER() OVER (ORDER BY meet.tracking_process.id) AS row_num
                FROM meet.tracking_process
                LEFT JOIN meet.meet ON meet.meet.id = meet.tracking_process.meet_id
                LEFT JOIN meet.tracking ON meet.tracking.id = meet.tracking_process.tracking_id
                WHERE $condition
                ORDER BY meet.tracking_process.id
                LIMIT {$length}
            ) tracking_processes
            WHERE tracking_processes.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($condition_values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM meet.tracking_process
                LEFT JOIN meet.meet ON meet.meet.id = meet.tracking_process.meet_id
                LEFT JOIN meet.tracking ON meet.tracking.id = meet.tracking_process.tracking_id
                WHERE $condition
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($condition_values);
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_tracking_process($data)
    {
        $values = [
            "meet_id" => null,
            "tracking_id" => null,
            "conclusion" => ""
        ];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        $sql = "INSERT INTO meet.tracking_process(
            meet_id, tracking_id, date, conclusion)
            VALUES (:meet_id, :tracking_id, NOW(), :conclusion)
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

    public function delete_tracking_process($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $tracking_process_id) {
            $value .= " meet.tracking_process.id = :id_{$key} OR";
            $values["id_{$key}"] = $tracking_process_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM meet.tracking_process
                WHERE {$value}";
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

    public function post_customer_delivery_date($data)
    {
        $sql = "INSERT INTO meet.delivery_meet_content(customer_expected_delivery_date)
                VALUES (:customer_expected_delivery_date)
                RETURNING delivery_meet_content_id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':customer_expected_delivery_date', $data['customer_expected_delivery_date']);
        $stmt->execute();
        $delivery_meet_content_id = $stmt->fetchColumn(0);

        return $delivery_meet_content_id;
    }

    public function patch_customer_delivery_date($data)
    {
        $sql = "UPDATE meet.delivery_meet_content
                SET customer_expected_delivery_date = :customer_expected_delivery_date
                WHERE meet.delivery_meet_content.delivery_meet_content_id = :delivery_meet_content_id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':customer_expected_delivery_date', $data['customer_expected_delivery_date']);
        $stmt->bindValue(':delivery_meet_content_id', $data['delivery_meet_content_id'], PDO::PARAM_INT);

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
    public function get_complaint_report($data)
    {
        $sql = "SELECT complaint.complaint_id, complaint.complaint_date meet_date, complaint.complaint_customer_id, complaint.content, img_id file_id FROM meet.complaint 
            LEFT JOIN meet.meet ON complaint.meet_id = meet.id 
            LEFT JOIN meet.delivery_meet_content ON complaint.delivery_meet_content_id = delivery_meet_content.delivery_meet_content_id
            LEFT JOIN meet.attach_file ON complaint.complaint_id = attach_file.complaint_id 
            WHERE complaint.complaint_id = :complaint_id
            GROUP BY complaint.complaint_id, complaint.complaint_date, complaint.complaint_customer_id, complaint.content
        ";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':complaint_id', $data['complaint_id']);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $result = [
                "status" => "failed",
            ];
        }
        return $result;
    }

    public function get_pdf_split($data)
    {
        $home = new Home($this->db);
        $files = json_encode([$data]);
        $recogUrl = "http://127.0.0.1:8090/pdfSplit?Files={$files}";
        $result = $home->http_response($recogUrl);
        $result = json_decode($result, true);
        return $result;
    }

    public function getMessageParseText($data)
    {
        $home = new Home($this->db);
        $cutUrl = 'http://127.0.0.1:8090/messageParse?Files=' . json_encode($data);
        $text = $home->http_response($cutUrl);

        if ($this->isJson($text)) {
            $result = json_decode($text, true);
        }

        return $result;
    }

    public function translate($data)
    {
        $translation = new GoogleTranslate(); // Translates into English.
        $translation->setSource(); // Detect language automatically.
        $translation->setTarget($data['language']); // Translate.
        return $translation->translate($data['content_for_translate']);
    }

    public function getMessageParse($data)
    {
        $home = new Home($this->db);
        $cutUrl = 'http://mil_python:8090/messageParse?Files=' . json_encode($data);
        $result = $home->http_response($cutUrl);
        if ($this->isJson($result)) {
            $result = json_decode($result, true);
        }
        foreach ($result as &$row) {
            if (array_key_exists("Attachments", $row)) {
                $row = $row['Attachments'];
            } else {
                $row = [];
            }
        }
        return $result;
    }
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    public function createtPosition($params)
    {
        $sql = "INSERT INTO position (canvas_width, canvas_height, draw_type, brush_color)
                VALUES (:canvas_width, :canvas_height, :draw_type, :brush_color)
                RETURNING position_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':canvas_width', $params['canvas_width']);
        $stmt->bindValue(':canvas_height', $params['canvas_height']);
        $stmt->bindValue(':draw_type', $params['draw_type']);
        $stmt->bindValue(':brush_color', $params['brush_color']);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return ["status" => "failed"];
        }
    }
    public function createtPoint($position_id, $index, $point_list)
    {
        $sql = "INSERT INTO point (position_id, x, y, index)
                VALUES (:position_id, :x, :y, :index);
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':position_id', $position_id);
        $stmt->bindValue(':x', $point_list[0]);
        $stmt->bindValue(':y', $point_list[1]);
        $stmt->bindValue(':index', $index);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function readDeliveryMeetContentPosition($params)
    {
        $sql = "SELECT delivery_meet_content_position_id, x, y, canvas_width, canvas_height
                FROM meet.delivery_meet_content_position
                LEFT JOIN position ON position.position_id = delivery_meet_content_position.position_id
                LEFT JOIN point ON point.position_id = position.position_id 
                WHERE delivery_meet_content_position.delivery_meet_content_file_id = :delivery_meet_content_file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':delivery_meet_content_file_id', $params['delivery_meet_content_file_id']);
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = ["status" => "failed"];
        }
        return $result;
    }
    public function createDeliveryMeetContentPosition($delivery_meet_content_file_id, $position_id)
    {
        $sql = "INSERT INTO meet.delivery_meet_content_position (delivery_meet_content_file_id, position_id)
                VALUES (:delivery_meet_content_file_id, :position_id)
                RETURNING delivery_meet_content_position_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':delivery_meet_content_file_id', $delivery_meet_content_file_id);
        $stmt->bindValue(':position_id', $position_id);
        if ($stmt->execute()) {
            return [
                "status" => "success",
                "delivery_meet_content_position_id" => $stmt->fetch(PDO::FETCH_ASSOC)["delivery_meet_content_position_id"]
            ];
        } else {
            return ["status" => "failed"];
        }
    }
    public function postFilePaint($data)
    {
        $sql = "INSERT INTO meet.delivery_meet_content_file_paint(
            delivery_meet_content_file_id, file_id)
            VALUES (:delivery_meet_content_file_id, :file_id);
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':delivery_meet_content_file_id', $data['delivery_meet_content_file_id']);
        $stmt->bindValue(':file_id', $data['file_id']);
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
    public function deleteFilePaint($data)
    {
        $sql = "DELETE FROM meet.delivery_meet_content_file_paint
            WHERE delivery_meet_content_file_id = :delivery_meet_content_file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':delivery_meet_content_file_id', $data['delivery_meet_content_file_id']);
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
    public function getFilePaint($data)
    {
        $sql = "SELECT file_id
            FROM meet.delivery_meet_content_file_paint
            WHERE delivery_meet_content_file_id = :delivery_meet_content_file_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':delivery_meet_content_file_id', $data['delivery_meet_content_file_id']);
        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $result = [
                "status" => "failed",
            ];
        }
        return $result;
    }
    public function postComplaintContent($data)
    {
        $sql = "UPDATE meet.complaint
            SET subject=:subject, content=:content, img_id = :img_id, complaint_customer_id = :complaint_customer_id, 
            edit_date = :edit_date, edit_user_id = :edit_user_id
            WHERE complaint_id = :complaint_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':complaint_id', $data['complaint_id']);
        $stmt->bindValue(':content', $data['content']);
        $stmt->bindValue(':subject', $data['subject']);
        $stmt->bindValue(':complaint_customer_id', $data['complaint_customer_id']);
        $stmt->bindValue(':img_id', $data['img_id']);
        $stmt->bindValue(':edit_date', $data['edit_date']);
        $stmt->bindValue(':edit_user_id', $data['edit_user_id']);
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
    public function getComplaintContent($data)
    {
        $sql = "SELECT subject, complaint_customer_id, img_id, complaint.content,COALESCE(attach_file.files,'[]') files, user_id, complaint_date, \"user\".name, edit_date, edit_user_id, edit_user.name edit_user_name
            FROM meet.complaint
            LEFT JOIN meet.delivery_meet_content ON complaint.delivery_meet_content_id = delivery_meet_content.delivery_meet_content_id
            LEFT JOIN (
                SELECT attach_file.complaint_id,JSON_AGG(JSON_BUILD_OBJECT('file_id',attach_file.id))files
                FROM meet.attach_file
                GROUP BY attach_file.complaint_id
            )attach_file ON attach_file.complaint_id = complaint.complaint_id
            LEFT JOIN system.\"user\" ON complaint.user_id = \"user\".id
            LEFT JOIN (
                SELECT id, name FROM system.\"user\" 
            ) edit_user ON complaint.edit_user_id = edit_user.id
            WHERE complaint.complaint_id = :complaint_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':complaint_id', $data['complaint_id']);
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
    public function getImage($data)
    {
        $sql = "SELECT name
            FROM meet.attach_file
            WHERE id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($files as $file) {
            return $this->container->upload_directory . '/' . $file['name'];
        }
    }
    public function postNewComplaint($data)
    {
        $sql = "INSERT INTO meet.complaint(
            subject, content, img_id, complaint_customer_id, user_id, complaint_date, edit_user_id, edit_date)
            VALUES (:subject, :content, :img_id, :complaint_customer_id, :user_id, :complaint_date, :edit_user_id, :edit_date)
            RETURNING complaint_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':subject', $data['subject']);
        $stmt->bindValue(':content', $data['content']);
        $stmt->bindValue(':complaint_customer_id', $data['complaint_customer_id']);
        $stmt->bindValue(':img_id', $data['img_id']);
        $stmt->bindValue(':user_id', $data['user_id']);
        $stmt->bindValue(':complaint_date', $data['complaint_date']);
        $stmt->bindValue(':edit_user_id', $data['edit_user_id']);
        $stmt->bindValue(':edit_date', $data['edit_date']);
        if ($stmt->execute()) {
            $result = [
                "status" => "success",
                "complaint_id" => $stmt->fetch(PDO::FETCH_ASSOC)['complaint_id']
            ];
        } else {
            $result = [
                "status" => "failed",
            ];
        }
        return $result;
    }

    public function read_complaint_position($params)
    {
        $sql = "SELECT attach_file_position.position_id, canvas_width, canvas_height,
            attach_file_position.attach_file_position_code, draw_type, brush_color,
            COALESCE(JSON_AGG(point.* ORDER BY point.index),'[]') point_list, \"drawRectArea\"
            FROM meet.attach_file_position
            LEFT JOIN position ON position.position_id = attach_file_position.position_id
            LEFT JOIN point ON point.position_id = position.position_id 
            WHERE attach_file_position.attach_file_id = :attach_file_id
            GROUP BY attach_file_position.position_id, canvas_width, canvas_height,attach_file_position.attach_file_position_code, draw_type, brush_color, \"drawRectArea\"
            ORDER BY attach_file_position.position_id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':attach_file_id', $params['attach_file_id']);
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

    public function create_complaint_position($params)
    {
        $values = [
            "attach_file_id" => 0,
            "position_id" => 0,
            "drawRectArea" => 0,
            'attach_file_position_code' => ''
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$params)){
                $values[$key] = intval($params[$key]);
            }
        }
        $sql = "INSERT INTO meet.attach_file_position (attach_file_id, position_id, \"drawRectArea\", attach_file_position_code)
                VALUES (:attach_file_id, :position_id, :drawRectArea, :attach_file_position_code)
                RETURNING attach_file_position_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return [
                "status" => "success",
                "attach_file_position_id" => $stmt->fetch(PDO::FETCH_ASSOC)["attach_file_position_id"]
            ];
        } else {
            return ["status" => "failed",];
        }
    }

    public function post_attach_file_paint($data)
    {
        $sql = "INSERT INTO meet.attach_file_paint(
            attach_file_id, file_id)
            VALUES (:attach_file_id, :file_id);
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':attach_file_id', $data['attach_file_id']);
        $stmt->bindValue(':file_id', $data['file_id']);
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

    public function delete_attach_paint($data)
    {
        $sql = "DELETE FROM meet.attach_file_paint
            WHERE attach_file_id = :attach_file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':attach_file_id', $data['attach_file_id']);
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

    public function get_attach_file_paint($data)
    {
        $sql = "SELECT attach_file_paint.file_id,file.file_name
            FROM meet.attach_file_paint
            LEFT JOIN phasegallery.file ON attach_file_paint.file_id = file.file_id
            WHERE attach_file_id = :attach_file_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':attach_file_id', $data['attach_file_id']);
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $index => $row) {
                foreach ($row as $key => $value) {
                    if($key==='file_name'){
                        $filepath = $this->container->upload_directory.DIRECTORY_SEPARATOR.$value;
                        $type = pathinfo($filepath, PATHINFO_EXTENSION);
                        $data = file_get_contents($filepath);
                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        $result[$index][$key] = $base64;
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

    public function updateDeliveryMeetContentPosition($data)
    {
        $values = [
            "attach_file_id" => 0,
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
        $stmt_array = [$values['attach_file_id']];
        array_merge($stmt_array,array_map(function($position){
                return $position['position_id'];
        },$position),$stmt_array);
        $stmt_string = "";
        if(count($stmt_array)!==0){
            $stmt_string = implode(',',array_fill(0,count($position),'?'));
            $stmt_string = " AND  position_id NOT IN ({$stmt_string})";
        }

        $sql = "DELETE FROM \"position\"
            WHERE position_id IN (
                SELECT position_id
                FROM meet.attach_file_position
                WHERE attach_file_id = ? {$stmt_string}
            );
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute($stmt_array)) {
            return [
                "status" => "failure"
            ];
        }
        $sql = "DELETE FROM meet.attach_file_position
            WHERE attach_file_id = ? {$stmt_string}
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
                $position_['position_id'] = $this->createtPosition($position_)['position_id'];
                foreach ($position_['point_list'] as $key => $value) {
                    $this->createtPoint($position_['position_id'], $key, $value);
                }
                $position_['attach_file_id'] = $values['attach_file_id'];
                $result = $this->create_complaint_position($position_);
                $result['position_id'] = $position_['position_id'];
            }else{
                $sql = "UPDATE meet.attach_file_position
                    SET attach_file_position_code = :attach_file_position_code
                    WHERE position_id = :position_id;
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':attach_file_position_code', $position_['attach_file_position_code']);
                $stmt->bindValue(':position_id', $position_['position_id']);
                if (!$stmt->execute()) {
                    return ["status" => "failed"];
                }
            }
        }
        /*  */
        return $values;
    }


    public function deleteDeliveryMeetContentPosition($params)
    {
        $sql = "DELETE FROM meet.attach_file_position
                WHERE position_id = :position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':position_id', $params['position_id']);
        $stmt->execute();
        $sql = "DELETE FROM point
            WHERE position_id = :position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':position_id', $params['position_id'], PDO::PARAM_INT);
        $stmt->execute();
        $sql = "DELETE FROM position
            WHERE position_id = :position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':position_id', $params['position_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function insertComplaintForm($params)
    {
        $sql = "INSERT INTO meet.complaint_form(
            complaint_id, bad_count, current_situation, problem, improve_strategy, internal_tracking, 
            external_tracking, current_count, current_order, shipping_date, shipping_count, order_num, item_number)
            VALUES (:complaint_id, :bad_count, :current_situation, :problem, :improve_strategy, 
            :internal_tracking, :external_tracking, :current_count, :current_order, :shipping_date, :shipping_count, 
            :order_num, :item_number) ON CONFLICT (complaint_id) 
            DO UPDATE SET bad_count = :bad_count, current_situation = :current_situation, problem = :problem, 
            improve_strategy = :improve_strategy, internal_tracking = :internal_tracking, external_tracking = :external_tracking, 
            current_count = :current_count, current_order = :current_order, shipping_date = :shipping_date, 
            shipping_count = :shipping_count, order_num = :order_num, item_number = :item_number;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':complaint_id', $params['complaint_id'], PDO::PARAM_INT);
        $stmt->bindParam(':bad_count', $params['bad_count'], PDO::PARAM_INT);
        $stmt->bindParam(':current_situation', $params['current_situation'], PDO::PARAM_STR);
        $stmt->bindParam(':problem', $params['problem'], PDO::PARAM_STR);
        $stmt->bindParam(':improve_strategy', $params['improve_strategy'], PDO::PARAM_STR);
        $stmt->bindParam(':internal_tracking', $params['internal_tracking'], PDO::PARAM_STR);
        $stmt->bindParam(':external_tracking', $params['external_tracking'], PDO::PARAM_STR);
        $stmt->bindParam(':current_count', $params['current_count'], PDO::PARAM_INT);
        $stmt->bindParam(':current_order', $params['current_order'], PDO::PARAM_INT);
        $stmt->bindParam(':shipping_date', $params['shipping_date'], PDO::PARAM_STR);
        $stmt->bindParam(':shipping_count', $params['shipping_count'], PDO::PARAM_STR);
        $stmt->bindParam(':order_num', $params['order_num'], PDO::PARAM_STR);
        $stmt->bindParam(':item_number', $params['number'], PDO::PARAM_STR);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            var_dump($stmt->errorInfo());
            return ["status" => "fail"];
        }
    }
    public function readComplaintForm($params)
    {
        $sql = "SELECT complaint.complaint_customer_id customer_id, complaint.img_id, complaint.content complaint_content, complaint.note,
                    complaint_form.item_number number, complaint_form.shipping_date, complaint_form.order_num,
                    complaint_form.shipping_count, complaint_form.bad_count, complaint_form.current_situation, complaint_form.current_count,
                    complaint_form.current_order, complaint_form.problem, complaint_form.improve_strategy,
                    complaint_form.internal_tracking, complaint_form.external_tracking, complaint.complaint_date::DATE fill_in_date
                FROM meet.complaint
                LEFT JOIN meet.complaint_form ON complaint_form.complaint_id = complaint.complaint_id
                LEFT JOIN meet.meet ON meet.id = complaint.meet_id
                WHERE complaint.complaint_id = :complaint_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':complaint_id', $params['complaint_id']);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return ["status" => "failed"];
        }
    }
    public function deleteComplaintForm($body)
    {
        $sql = "DELETE FROM meet.complaint_form
                WHERE complaint_id = :complaint_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':complaint_id', $body['complaint_id']);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
    public function deleteAttachFile($data)
    {
        $sql = "DELETE FROM meet.attach_file
                WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $data['file_id']);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "failed"];
        }
    }
}
