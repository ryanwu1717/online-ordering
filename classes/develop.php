<?php

use \Psr\Container\ContainerInterface;
use Slim\Http\UploadedFile;

class develop
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

    public function get_processes_type()
    {
        $sql = "SELECT public.processes_type.processes_type_id, public.processes_type.processes_type_name
                FROM public.processes_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function post_processes_type($datas)
    {
        $value = "";
        $values = [];

        foreach ($datas as $key => $data) {
            $value .= "(:processes_type_name_{$key}),";
            $tmp = [
                "processes_type_name_{$key}" => $data['processes_type_name']
            ];

            $values = array_merge($tmp, $values);
        }

        $value = rtrim($value, ",");

        $sql = "INSERT INTO public.processes_type(processes_type_name)
                VALUES {$value}
                ";
        $stmt = $this->db->prepare($sql);

        if ($stmt->execute($values)) {
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

    public function delete_processes_type($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $processes_type_id) {
            $value .= " public.processes_type.processes_type_id = :processes_type_id_{$key} OR";
            $values["processes_type_id_{$key}"] = $processes_type_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM public.processes_type
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

    public function get_processes($data)
    {
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
                SELECT public.processes.processes_id, public.processes.processes_name, 
                ROW_NUMBER() OVER (ORDER BY public.processes.processes_id) AS row_num
                FROM public.processes

                LIMIT {$length}
            ) processes
            WHERE processes.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM public.processes
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function get_processes_template($data)
    {
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
                SELECT public.processes_template.processes_template_id, public.processes_template.processes_template_name, 
                    public.processes_type.processes_type_name, 
                    ROW_NUMBER() OVER (ORDER BY public.processes_template.processes_template_id) AS row_num
                FROM public.processes_template
                LEFT JOIN public.processes_type ON public.processes_type.processes_type_id = public.processes_template.processes_type_id

                LIMIT {$length}
            ) processes_template
            WHERE processes_template.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM public.processes_template
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_processes_template($datas)
    {
        $value = "";
        $values = [];

        foreach ($datas as $key => $data) {
            $value .= "(:processes_template_name_{$key}, :processes_type_id_{$key}),";
            $tmp = [
                "processes_template_name_{$key}" => $data['processes_template_name'],
                "processes_type_id_{$key}" => $data['processes_type_id']
            ];

            $values = array_merge($tmp, $values);
        }

        $value = rtrim($value, ",");

        $sql = "INSERT INTO public.processes_template(processes_template_name, processes_type_id)
                VALUES {$value}
                ON CONFLICT (processes_template_name)
                DO NOTHING
                RETURNING processes_template_id
                ";
        $stmt = $this->db->prepare($sql);

        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function patch_processes_template($data)
    {
        $value = "";
        $values = [
            'processes_template_id' => '',
            'processes_template_name' => '',
            'processes_type_id' => ''
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

        $sql = "UPDATE public.processes_template
                SET {$value}
                WHERE public.processes_template.processes_template_id = :processes_template_id";
        $stmt = $this->db->prepare($sql);

        if ($stmt->execute($values)) {
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

    public function delete_processes_template($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $processes_template_id) {
            $value .= " public.processes_template.processes_template_id = :processes_template_id_{$key} OR";
            $values["processes_template_id_{$key}"] = $processes_template_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM public.processes_template
                WHERE {$value}";
        $stmt = $this->db->prepare($sql);

        $stmt->execute($values);

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

    public function get_processes_template_processes($data)
    {
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
                SELECT public.processes_template_processes.processes_template_processes_id, 
                    public.processes_template_processes.processes_template_index, 
                    public.processes_template_processes.processes_id, 
                    public.processes.processes_name, 
                    ROW_NUMBER() OVER (ORDER BY public.processes_template_processes.processes_template_id) AS row_num
                FROM public.processes_template_processes
                LEFT JOIN public.processes ON public.processes.processes_id = public.processes_template_processes.processes_id
                WHERE public.processes_template_processes.processes_template_id = :processes_template_id

                LIMIT {$length}
            ) processes_template
            WHERE processes_template.row_num > {$start}
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':processes_template_id', $data['processes_template_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT COUNT(*)
                FROM public.processes_template_processes
                WHERE public.processes_template_processes.processes_template_id = :processes_template_id
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':processes_template_id', $data['processes_template_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result['total'] = $stmt->fetchColumn(0);
        return $result;
    }

    public function post_processes_template_processes($datas)
    {
        $value = "";
        $values = [];

        foreach ($datas as $key => $data) {
            $value .= "(:processes_template_id_{$key}, :processes_id_{$key}, :processes_template_index_{$key}),";
            $tmp = [
                "processes_template_id_{$key}" => $data['processes_template_id'],
                "processes_id_{$key}" => $data['processes_id'],
                "processes_template_index_{$key}" => $data['processes_template_index']
            ];

            $values = array_merge($tmp, $values);
        }

        $value = rtrim($value, ",");

        $sql = "INSERT INTO public.processes_template_processes(processes_template_id, processes_id, processes_template_index)
                VALUES {$value}
                RETURNING processes_template_processes_id";
        $stmt = $this->db->prepare($sql);

        $stmt->execute($values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function delete_processes_template_processes($data)
    {
        $value = "";
        $values = [];
        foreach ($data as $key => $processes_template_processes_id) {
            $value .= " public.processes_template_processes.processes_template_processes_id = :processes_template_processes_id_{$key} OR";
            $values["processes_template_processes_id_{$key}"] = $processes_template_processes_id;
        }
        $value = rtrim($value, "OR");

        $sql = "DELETE FROM public.processes_template_processes
                WHERE {$value}";
        $stmt = $this->db->prepare($sql);

        $stmt->execute($values);

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

    public function postStandardProcesses($params)
    {
        $values = [
            'custom_category' => ''
        ];

        foreach ($values as $key => $data) {
            array_key_exists($key, $params) && ($values[$key] = $params[$key]);
        }

        $sql = "INSERT INTO develop.standard_processes(custom_category)
                VALUES (:custom_category)
                RETURNING standard_processes_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    public function postStandardProcess($params)
    {
        $values = [
            'standard_processes_id' => 0,
            'index' => 0,
            'processes_id' => 0
        ];

        foreach ($values as $key => $data) {
            array_key_exists($key, $params) && ($values[$key] = $params[$key]);
        }

        $sql = "INSERT INTO develop.standard_process(standard_processes_id, index, processes_id)
                VALUES (:standard_processes_id, :index, :processes_id)
                RETURNING standard_process_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function readCustomProcessesOne($params)
    {
        $sql = "SELECT standard_processes.custom_category,
                    JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'index', processes_group.index,
                            'processes_id', processes_group.processes_id,
                            'processes_name', processes_group.processes_name
                        )
                        ORDER BY processes_group.index
                    ) processes
                FROM develop.standard_process
                LEFT JOIN develop.standard_processes ON standard_processes.standard_processes_id = standard_process.standard_processes_id
                LEFT JOIN (
                    SELECT standard_process.standard_process_id, standard_process.index, processes.processes_id, processes.processes_name
                    FROM develop.standard_process
                    LEFT JOIN processes ON processes.processes_id = standard_process.processes_id
                ) processes_group ON processes_group.standard_process_id = standard_process.standard_process_id
                WHERE standard_process.standard_processes_id = :standard_processes_id
                GROUP BY standard_processes.custom_category
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':standard_processes_id', $params['standard_processes_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }

    public function readCustomProcessesAll($data)
    {
        if ($data['size'] <= 0) {
            $length = '';
            $start = '';
            $limit = '';
            $row_number = '';
        } else {
            $length = $data['cur_page'] * $data['size'];
            $start = "WHERE custom_processes.row_number > " . ($length - $data['size']);
            $limit = "LIMIT {$length}";
            $row_number = ", ROW_NUMBER() OVER (ORDER BY standard_processes.standard_processes_id)";
        }
        $sql = "SELECT  custom_processes.standard_processes_id, custom_processes.custom_category, custom_processes.processes
                FROM
                (
                    SELECT standard_processes.standard_processes_id, standard_processes.custom_category,
                        JSON_AGG(
                            JSON_BUILD_OBJECT(
                                'index', processes_group.index,
                                'processes_id', processes_group.processes_id,
                                'processes_name', processes_group.processes_name
                            )
                            ORDER BY processes_group.index
                        ) processes
                        {$row_number}
                    FROM develop.standard_processes
                    LEFT JOIN develop.standard_process ON standard_process.standard_processes_id = standard_processes.standard_processes_id
                    LEFT JOIN (
                        SELECT standard_process.standard_process_id, standard_process.index, processes.processes_id, processes.processes_name
                        FROM develop.standard_process
                        LEFT JOIN processes ON processes.processes_id = standard_process.processes_id
                    ) processes_group ON processes_group.standard_process_id = standard_process.standard_process_id
                    GROUP BY standard_processes.standard_processes_id, standard_processes.custom_category
                    ORDER BY standard_processes.standard_processes_id, standard_processes.custom_category
                    {$limit}
                ) custom_processes
                {$start}
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            if ($data['size'] <= 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $sql = "SELECT COUNT(standard_processes_id)
                    FROM develop.standard_processes
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $total = $stmt->fetchColumn(0);
                return [
                    'data' => $result,
                    'total' => $total
                ];
            }
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }

    public function checkProcessResult($data)
    {
        $home = new Home();
        $values = [
            "id" => 0
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $data['id'] = $values['id'];
        $values['file_id'] = $values['id'];
        $sql = "SELECT *
            FROM result
            LEFT JOIN process ON result.process_id = process.id
            LEFT JOIN crop ON process.component_id = crop.component_id
            WHERE crop.\"fileID\" = :file_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["file_id" => $data['id']]);
        $orderresult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($orderresult) === 0) {
            $sql = "SELECT crop.\"fileID\" id, crop.name file_name
                FROM process
                LEFT JOIN crop ON crop.component_id = process.component_id
                WHERE crop.\"fileID\" = :file_id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(["file_id" => $data['id']]);
            $file_ids = $stmt->fetchAll();
            if (count($file_ids) === 0) {
                goto_get_crop:
                $crops = $home->getCrops(['id' => $data['id']]);
                if (count($crops) !== 0) {
                    $data += ['data' => ['' => []]];
                    foreach ($crops as $key => $crop) {
                        array_push($data['data'][''], $crop['id']);
                    }
                } else {
                    $extension = pathinfo('sample.jpg', PATHINFO_EXTENSION);
                    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
                    $filename = sprintf('%s.%0.8s', $basename, $extension);
                    // foreach ($values as $key => $value) {
                    //     if(array_key_exists($key,$data))
                    //         $values[$key] = $data[$key];
                    // }
                    if (array_key_exists('id', $data)) {
                        $data['id'] = $data['id'];
                    } else if (array_key_exists('file_id', $data)) {
                        $data['id'] = $data['file_id'];
                    } else {
                        return;
                    }
                    $home->setCrop($data['id'], '', ['file_name' => $filename], (object)[$filename => [0, 0, 0, 0]]); //return img id
                    goto goto_get_crop;
                }
            }
            $crops = $home->getCrops(['id' => $data['id']]);
            if (count($crops) !== 0) {
                $data += ['data' => ['' => []]];
                foreach ($crops as $key => $crop) {
                    array_push($data['data'][''], $crop['id']);
                }
            }
            $result = $home->insertComponent($data);
            $processArr = [];
            foreach ($crops as $key => $crop) {
                $values['file_name'] = $crop['file_name'];
            }
            foreach ($result as $key => $value) {
                // var_dump($value);
                $processresult = $home->getProcessId($value);
                // var_dump($processresult) ;
                array_push($processArr, $processresult['process_id']);
                $processresult += [
                    'finish' => 3,
                    'total' => 1,
                    'filename' => $values['file_name'],
                    'confidence' => 0
                ];
                $home->insertResultMatch(['data' => [$processresult]]);
                $home->insertAnnotation([
                    'id' => $data['id'],
                    'name' => $values['file_name']
                ]);

                $row = [
                    'process_id' => intval($processresult['process_id']),
                    "confidence" => 0.00,
                    "comment" => "",
                    "process" => "",
                    "material" => "",
                    "stuff" => "",
                    "module_name" => "技術",
                    'crop_id' => 0,
                    'confidence' => 0,
                ];
                foreach ($crops as $crop) {
                    $row['crop_id'] = $crop['id'];
                    break;
                }
                $home->postCommentComponent($row);

                return;
                // var_dump($processresult);
                // var_dump($cropfileStr);
                // $curl_recognition = "http://mil_python:8090/CNNPartSuggestion?top_k=5&crops={%22paths%22:[{$cropfileStr}]}";
                // $CNNPartSuggestion = $this->http_response($curl_recognition);
                // // var_dump( $CNNPartSuggestion );
                // $CNNPartSuggestion = json_decode($CNNPartSuggestion);

                // $CNNresult = $this->insertCNNResult(['process_id' => $processresult['process_id'], 'CNN' => $CNNPartSuggestion, 'crops' => $crops]);
                // var_dump($CNNresult) ;

                // return;
                // var_dump($processArr) ;

                // return $processArr;

                // $resultEncode = json_encode($processresult);
                // $curl_recognition = "http://mil_python:8090/compare?data={$resultEncode}";
                // $home->http_response($curl_recognition,1);
            }
            // $sql = "SELECT crop.file_id id 
            //     FROM process 
            //     LEFT JOIN crop ON crop.component_id = component.id
            //     WHERE process_id = :process_id
            // ";
            // $stmt = $this->db->prepare($sql);
            // $stmt->bindValue(':process_id', $data['process_id'], PDO::PARAM_STR);
            // $stmt->execute();
            // $file_ids = $stmt->fetchAll();

            // $extension = pathinfo('sample.jpg', PATHINFO_EXTENSION);
            // $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
            // $filename = sprintf('%s.%0.8s', $basename, $extension);
            // $processresult = ['process_id'=>$data['process_id']];
            // $processresult += [
            //     'finish' => 3,
            //     'total' => 1,
            //     'filename' => $filename,
            //     'confidence' => 0
            // ];
            // $this->insertResultMatch(['data'=>[$processresult]]);
            // foreach ($file_ids as $key => $file_id) {
            //     $this->insertAnnotation([
            //         'id'=>$file_id['id'],
            //         'name'=>$filename
            //     ]);
            //     break;
            // }
        }
    }

    public function updateStandardProcesses($params)
    {
        $values = [
            'standard_processes_id' => 0,
            'custom_category' => ''
        ];

        foreach ($values as $key => $data) {
            array_key_exists($key, $params) && ($values[$key] = $params[$key]);
        }

        $sql = "UPDATE develop.standard_processes
            SET custom_category=:custom_category
            WHERE standard_processes_id=:standard_processes_id
            RETURNING standard_processes_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    public function deleteStandardProcesses($params)
    {
        $values = [
            'standard_processes_id' => 0
        ];

        foreach ($values as $key => $data) {
            array_key_exists($key, $params) && ($values[$key] = $params[$key]);
        }

        $sql = "DELETE FROM develop.standard_process
            WHERE standard_processes_id = :standard_processes_id;";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            $result = ["status" => 'success'];
        } else {
            $result = [
                "status" => 'failed',
                "error" => $stmt->errorInfo()
            ];
        }

        return $result;
    }

    public function deleteCustomProcesses($params)
    {
        $values = [
            'standard_processes_id' => 0
        ];

        foreach ($values as $key => $data) {
            array_key_exists($key, $params) && ($values[$key] = $params[$key]);
        }

        $sql = "DELETE FROM develop.standard_processes
            WHERE standard_processes_id = :standard_processes_id;";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            $result = ["status" => 'success'];
        } else {
            $result = [
                "status" => 'failed',
                "error" => $stmt->errorInfo()
            ];
        }

        return $result;
    }
}
