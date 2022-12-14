<?php

use \Psr\Container\ContainerInterface;

class Business
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

    public function postQuotationSQLSever($data){
        $values = [
            "uid" => ""
        ];
        $home = new Home($this->container->db);
        $users = $home->getUserUID();
        foreach ($users as $user) {
            foreach ($values as $key => $value) {
                if(array_key_exists($key,$user)){
                    $values[$key] = $user[$key];
                }
            }
        }

        $now = (date("Y")-1911) .date("md");
        $now = str_pad($now, 7, '0', STR_PAD_LEFT);
        // $now = '0970912';
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT TOP 1000 *
                  FROM [MIL].[dbo].[COPTA]
                  WHERE  [TA002] LIKE '%{$now}%';
                "]
            )
        );
        // -- WHERE '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) = {$data['order_name']}

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

       


        $tmpcount  = count($result) +1;
        $now .= str_pad($tmpcount, 3, '0', STR_PAD_LEFT);
        $tmpCount=0;
        foreach($data AS $key => $value){
            if($key == 0){
                // var_dump($key);
                curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt(
                    $ch,
                    CURLOPT_POSTFIELDS,
                    http_build_query(
                        ['sql' => "INSERT INTO [MIL].[dbo].[COPTA] ([COMPANY],[CREATOR],[CREATE_DATE],[TA001],[TA002],[TA004],[TA005],[TA007],[TA013])
                        VALUES ('MIL', '090001',  getdate(),'2110','{$now}','{$value['customercode']}','{$values['uid']}','{$value['currency']}',convert(varchar, getdate(), 112));
                        "]
                    )
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $head = curl_exec($ch);
                curl_close($ch);

            }
            if($value['itemno'] == ''){
                continue;
            }
            $tmpStr =str_pad($tmpCount+1, 4, '0', STR_PAD_LEFT);
            $tmpCount++;
            $tmpcost =  intval($value['num']) * intval($value['cost']);
            // var_dump($tmpStr);
            // var_dump($value);

            curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                /* TB009?????? TB010?????? ???????????? ?????? */
                http_build_query(
                    ['sql' => "INSERT INTO [MIL].[dbo].[COPTB] ([COMPANY],[CREATOR],[USR_GROUP],[CREATE_DATE],[MODIFIER],[MODI_DATE],[FLAG],[TB001],[TB002],[TB003],[TB004],[TB007],[TB008],[TB009],[TB010],[TB201],[TB204],[TB205],[TB206])
                    VALUES ('MIL', 'nknu','101000', convert(varchar, getdate(), 112),'nknu',convert(varchar, getdate(), 112),2,'2110','{$now}','{$tmpStr}','{$value['itemno']}','{$value['num']}','PCS','{$value['cost']}','{$tmpcost}','{$value['order_name']}','{$value['origin_titanizing']}','{$value['origin_material']}','{$value['hardness']}');
                    "]
                )
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $head = curl_exec($ch);
            curl_close($ch);
            // return;

        }


        return $tmpcount;

        return $now;
    }

    public function getMaterialMatch($datas){
        foreach ($datas as $key => $data) {
            $sql = "SELECT \"sID\"
                FROM public.\"MaterialMatch\"
                WHERE \"sMaterial\" = :material;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':material', $data);
            $stmt->execute();
            $result = $stmt->fetchAll();
            return $result;
        }
    }

    public function getRFIDProcessDetail($data){
        $query = "";
        if ($data['order_name'] != '') {
            $query = "WHERE '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) = '{$data['order_name']}'";
        }

        if ($data['item_num'] != '') {
            if($query == ''){
                $query.="WHERE ";
            }else{
                $query.="AND ";
            }
            $query .= "[COPTD].[TD004] = '{$data['item_num']}'";
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
                    '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) AS order_name,
                    [SFCTA].[TA003] AS process_order,
                    [SFCTA].[MW002] as process_name,
                    [SFCTA].[TA032] AS process_status,
                    [SFCTA].[TA006] AS process_machine,
                    [SFCTA].[TA010] AS input_number,
                    [SFCTA].[TA011] AS finish_number,
                    [SFCTA].[TA032] AS finish_code,
                    (SELECT COUNT(*)
                    FROM   [MIL].[dbo].[SFCTA]
                    WHERE [TA032] = 'n' OR [TA032] = 'N'
                    ) AS all_count,
                    CASE
                        WHEN  count_table.count IS null THEN 0
                        ELSE count_table.count
                    END AS tmp_count,
                    [COPTD].[TD004]

                    FROM [MIL].[dbo].[COPTA]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    INNER JOIN  [MIL].[dbo].[COPTD] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                    LEFT JOIN [MIL].[dbo].[COPTC] ON COPTC.TC001 = COPTD.TD001 AND COPTD.TD002 = COPTC.TC002
                    LEFT JOIN [MIL].[dbo].[MOCTA] ON COPTD.TD001 =  MOCTA.TA026 AND COPTD.TD002 =  MOCTA.TA027 AND COPTD.TD003 =  MOCTA.TA028
                    LEFT JOIN (
                        SELECT [SFCTA].[TA001], [SFCTA].[TA002], [SFCTA].[TA032], [SFCTA].[TA003], [SFCTA].[TA004], [SFCTA].[TA010], [SFCTA].[TA011], [SFCTA].[TA006],[CMSMW].[MW002] 
                        FROM [MIL].[dbo].[SFCTA]
                        LEFT JOIN CMSMW ON CMSMW.MW001=SFCTA.TA004
                    )AS [SFCTA] ON [SFCTA].[TA001] = [MOCTA].[TA001] AND [SFCTA].[TA002] = [MOCTA].[TA002]
                    LEFT JOIN (
                        SELECT [TA001],[TA002],[TA032], COUNT(*) AS count
                        FROM [MIL].[dbo].[SFCTA]
                        WHERE [TA032] = 'n' OR [TA032] = 'N'
                        GROUP BY [TA001],[TA002],[TA032]
                    )AS count_table ON count_table.[TA001] = [MOCTA].[TA001] AND count_table.[TA002] = [MOCTA].[TA002]
                    {$query}

                  
                    


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

    public function getRFIDOrderDetail($data){
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
                    '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) AS order_name,
                    [COPTD].[TD004] AS item_no,
                    [COPTD].[TD005] AS artifact_name,
                    [COPTD].[TD008] AS artifact_num,
                    [COPTC].[TC013] AS completion_time,
                    CASE
                        WHEN [SFCTA].count IS null THEN 'ready'
                        ELSE 'waiting'
                    END AS processing_status


                    FROM [MIL].[dbo].[COPTA]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    INNER JOIN  [MIL].[dbo].[COPTD] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                    LEFT JOIN [MIL].[dbo].[COPTC] ON COPTC.TC001 = COPTD.TD001 AND COPTD.TD002 = COPTC.TC002
                    LEFT JOIN [MIL].[dbo].[MOCTA] ON COPTD.TD001 =  MOCTA.TA026 AND COPTD.TD002 =  MOCTA.TA027 AND COPTD.TD003 =  MOCTA.TA028
                    LEFT JOIN (
                        SELECT [TA001],[TA002],[TA032], COUNT(*) AS count
                        FROM [MIL].[dbo].[SFCTA]
                        WHERE [TA032] = 'n' OR [TA032] = 'N'
                        GROUP BY [TA001],[TA002],[TA032]
                    )AS [SFCTA] ON [SFCTA].[TA001] = [MOCTA].[TA001] AND [SFCTA].[TA002] = [MOCTA].[TA002]
                    WHERE '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) = '{$data['order_name']}'

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

    public function getRFIDProcessSummary($data){
        $query = "";
        if ($data['line_type'] != '') {
            $query = "AND [SFCTA].[TA006] = '{$data['line_type']}'";
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
                    '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) AS order_name,
                    CASE
                        WHEN count_table.count IS null THEN 'ready'
                        ELSE 'waiting'
                    END AS processing_status,
                    [SFCTA].[TA006] AS process_machine,
                    RTRIM(LTRIM([MOCTA].[TA001]))+'-'+RTRIM(LTRIM([MOCTA].[TA002])) AS makeorder_name,
                    [COPTD].[TD008] AS artifact_num,
                    [COPTD].[TD005] AS artifact_name


                    FROM [MIL].[dbo].[COPTA]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    INNER JOIN  [MIL].[dbo].[COPTD] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                    LEFT JOIN [MIL].[dbo].[COPTC] ON COPTC.TC001 = COPTD.TD001 AND COPTD.TD002 = COPTC.TC002
                    LEFT JOIN [MIL].[dbo].[MOCTA] ON COPTD.TD001 =  MOCTA.TA026 AND COPTD.TD002 =  MOCTA.TA027 AND COPTD.TD003 =  MOCTA.TA028
                    LEFT JOIN (
                        SELECT [SFCTA].[TA001], [SFCTA].[TA002], [SFCTA].[TA032], [SFCTA].[TA003], [SFCTA].[TA004], [SFCTA].[TA010], [SFCTA].[TA011], [SFCTA].[TA006],[CMSMW].[MW005]
                        FROM [MIL].[dbo].[SFCTA]
                        LEFT JOIN [MIL].[dbo].[CMSMW] ON CMSMW.MW001=SFCTA.TA004

                    )AS [SFCTA] ON [SFCTA].[TA001] = [MOCTA].[TA001] AND [SFCTA].[TA002] = [MOCTA].[TA002]
                    LEFT JOIN (
                        SELECT [TA001],[TA002],[TA032], COUNT(*) AS count
                        FROM [MIL].[dbo].[SFCTA]
                        WHERE [TA032] = 'n' OR [TA032] = 'N'
                        GROUP BY [TA001],[TA002],[TA032]
                    )AS count_table ON count_table.[TA001] = [MOCTA].[TA001] AND count_table.[TA002] = [MOCTA].[TA002]


                    WHERE RTRIM(LTRIM([MOCTA].[TA001]))+'-'+RTRIM(LTRIM([MOCTA].[TA002])) IS NOT NULL {$query}
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

    public function getRFIDProcessState($data){
        $tquery = "";
        $query = "";

        if($data['type'] == 'N'){
            $tquery ="WHERE (t.[TA032] = 'n' OR t.[TA032] = 'N')";
            $query ="WHERE ([SFCTA].[TA032] = 'n' OR [SFCTA].[TA032] = 'N')";
        }else{
            $tquery ="WHERE (t.[TA032] = 'y' OR t.[TA032] = 'Y')";
            $query ="WHERE ([SFCTA].[TA032] = 'y' OR [SFCTA].[TA032] = 'Y')";
        }

        if($data['dateStart'] != '' || $data['dateEnd'] != '' ){
            $tmpStr='';
            if($query == ''){
                $tmpStr=" WHERE ";
            }else{
                $tmpStr=" AND ";
            }

            
            if ($data['dateStart'] == '') {
                $dateStart = 'CURRENT_TIMESTAMP';
            } else {
                $dateStart = "'{$data['dateStart']}'";
            }
            if ($data['dateEnd'] == '') {
                $dateEnd = 'CURRENT_TIMESTAMP';
            } else {
                $dateEnd = "'{$data['dateEnd']}'";
            }
            $tquery .="{$tmpStr} CAST(MOCTA.TA003 AS DATETIME)  BETWEEN {$dateStart} AND {$dateEnd}";
            $query .="{$tmpStr} CAST(MOCTA.TA003 AS DATETIME)  BETWEEN {$dateStart} AND {$dateEnd}";
        }
        if(isset($data['process'])){
            if(count($data['process']) > 0){
                $tmpStr='';
                if($query == ''){
                    $tmpStr=" WHERE ";
                }else{
                    $tmpStr=" AND ";
                }

                $tmpArr = "(";
                foreach($data['process'] AS $key=> $value){
                    $tmpArr .="'{$value}',";
                }
                $tmpArr = substr_replace($tmpArr, ")", -1);

                $tquery .="{$tmpStr} t.[TA004] IN {$tmpArr}";
                $query .="{$tmpStr} [SFCTA].[TA004] IN {$tmpArr}";
            }
        }
        
        if(isset($data['station'])){
            if(count($data['station']) > 0){
                $tmpStr='';
                if($query == ''){
                    $tmpStr=" WHERE ";
                }else{
                    $tmpStr=" AND ";
                }

                $tmpArr = "(";
                foreach($data['station'] AS $key=> $value){
                    $tmpValue = preg_replace('/\s+/', '', $value);
                    $tmpArr .="'{$tmpValue}',";
                }
                $tmpArr = substr_replace($tmpArr, ")", -1);

                $tquery .="{$tmpStr} REPLACE(t.[TA006], ' ', '') IN {$tmpArr}";
                $query .="{$tmpStr} REPLACE([SFCTA].[TA006], ' ', '') IN {$tmpArr}";
            }
        }
        

        if(isset($data['productionLine'])){
            if(count($data['productionLine']) > 0){
                $tmpStr='';
                if($query == ''){
                    $tmpStr=" WHERE ";
                }else{
                    $tmpStr=" AND ";
                }

                $tmpArr = "(";
                foreach($data['productionLine'] AS $key=> $value){
                    $tmpValue = preg_replace('/\s+/', '', $value);
                    $tmpArr .="'{$tmpValue}',";
                }
                $tmpArr = substr_replace($tmpArr, ")", -1);

                $tquery .="{$tmpStr} REPLACE(t.[TA006], ' ', '') IN {$tmpArr}";
                $query .="{$tmpStr} REPLACE([SFCTA].[TA006], ' ', '') IN {$tmpArr}";
            }
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
                ['sql' => "SELECT tmptable.*, STUFF((
                    SELECT  RTRIM(LTRIM(t.[TA001]))+'-'+RTRIM(LTRIM(t.[TA002]))  AS makeorder_name,t.[TA010] AS num
                    FROM [MIL].[dbo].[SFCTA] t
                    LEFT JOIN [MIL].[dbo].[MOCTA] ON t.[TA001]=[MOCTA].[TA001] AND t.[TA002]=[MOCTA].[TA002]
                    {$tquery} AND (t.[TA032] = 'y' OR t.[TA032] = 'Y') AND t.[TA004]=tmptable.process_code AND t.[TA006]=tmptable.machine_code
                    FOR XML AUTO),1,0,''
                    ) AS list
                FROM(SELECT 
                    [CMSMD].[MD002] AS machine_name,
                    [SFCTA].[TA006] AS machine_code,
                    [CMSMW].[MW002] AS process_name,
                    [SFCTA].[TA004] AS process_code,
                    SUM([SFCTA].[TA010]) AS num,
                    [SFCTA].[TA004] AS process_machine
                    -- [MOCTA].[TA003] AS time,
                   
                    FROM [MIL].[dbo].[MOCTA]
                    LEFT JOIN [MIL].[dbo].[SFCTA] ON [SFCTA].[TA001]=[MOCTA].[TA001] AND [SFCTA].[TA002]=[MOCTA].[TA002]
                    LEFT JOIN [MIL].[dbo].[CMSMD] ON [CMSMD].[MD001]=[SFCTA].[TA006]
                    LEFT JOIN [MIL].[dbo].[CMSMW] ON CMSMW.MW001=SFCTA.TA004
                    {$query}
                    GROUP BY [CMSMD].[MD002],[SFCTA].[TA006],[CMSMW].[MW002],[SFCTA].[TA004],[SFCTA].[TA004]
                ) AS tmptable
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // foreach ($results as $key_results => $result) {
        if(isset($result)){
            foreach ($result as $key_result => $value) {
                // var_dump($value['list']);
                $tmpvalue = $value['list'];
                $tmpArrs=[];

                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                // var_dump($xml)/;

                if ($tmpvalue == "") {
                    continue;
                }
               

                foreach ($xml->t as $t) {
                    // var_dump($t);
                    $tmpArr=[];
                    foreach ($t->attributes() as $a => $b) {
                        
                        foreach ((array)$b[0] as $c => $d) {
                            // var_dump($a,$d);
                            $tmpArr[$a] = $d;
                        }

                    }
                    array_push($tmpArrs, (array)$tmpArr);


                    // break;
                }
                // var_dump($tmpArrs);


                $result[$key_result]['list'] = $tmpArrs;

            }
        }
            
        // }
        // return ;
        return $result;
    }

    public function getRFIDOrderInformation($data){
        $query = "";
        if ($data['customerCode'] != '') {
            $query = "WHERE RTRIM(LTRIM([COPTC].[TC004])) LIKE '%{$data['customerCode']}%'";
        }
        if ($data['keyword'] != '') {
            $checkquery = ($query == '')?'WHERE':'AND';
            $query .= "{$checkquery} RTRIM(LTRIM([COPTA].[TA006])) LIKE '%{$data['keyword']}%'";

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
                    '-' + RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) AS order_name,
                    [COPTA].[TA003] AS offer_date,
                    [COPTA].[TA006] AS customer_name,
                    [COPTD].[TD008] AS artifact_num,
                    [COPTC].[TC004] AS customer_code,
                    [COPTD].[TD004] AS item_no,
                    CASE
                        WHEN [SFCTA].count IS null THEN '?????????'
                        ELSE '?????????'
                    END AS processing_status

                    FROM [MIL].[dbo].[COPTA]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    INNER JOIN  [MIL].[dbo].[COPTD] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                    LEFT JOIN [MIL].[dbo].COPTC ON COPTC.TC001 = COPTD.TD001 AND COPTD.TD002 = COPTC.TC002
                    LEFT JOIN [MIL].[dbo].[MOCTA] ON COPTD.TD001 =  MOCTA.TA026 AND COPTD.TD002 =  MOCTA.TA027 AND COPTD.TD003 =  MOCTA.TA028
                    LEFT JOIN (
                        SELECT [TA001],[TA002],[TA032], COUNT(*) AS count
                        FROM [MIL].[dbo].[SFCTA]
                        WHERE [TA032] = 'n' OR [TA032] = 'N'
                        GROUP BY [TA001],[TA002],[TA032]
                    )AS [SFCTA] ON [SFCTA].[TA001] = [MOCTA].[TA001] AND [SFCTA].[TA002] = [MOCTA].[TA002]


                    {$query}
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
    public function getRFIDProcessNmaes(){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT CMSMD.MD001 \"????????????\",CMSMD.MD002 \"????????????\",
                    Stuff((
                        SELECT MW001 \"????????????\",MW002 \"????????????\",MW005
                        FROM [MIL].[dbo].CMSMW t
                        WHERE t.MW005 = CMSMD.MD001
                        FOR XML AUTO),1,0,''
                    )\"??????\",
                    Stuff((
                        SELECT MX001 \"????????????\",MX003 \"????????????\",MX002
                        FROM [MIL].[dbo].CMSMX t
                        WHERE t.MX002 = CMSMD.MD001
                        FOR XML AUTO),1,0,''
                    )\"??????\"
                    FROM [MIL].[dbo].CMSMD
                    WHERE CMSMD.MD001 NOT IN ('C', 'E')
                    GROUP BY CMSMD.MD001,CMSMD.MD002
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $results = json_decode($head, true);
        foreach ($results as $key_results => $result) {
            foreach ($result as $key_result => $value) {
                $xml = simplexml_load_string("<a>$value</a>");
                if ($value == "" || ($key_result == "????????????" || $key_result == "????????????")) {
                    continue;
                }
                $result[$key_result] = [];
                foreach ($xml->t as $t_index=>$t) {
                    $result[$key_result][] = [];
                    foreach ($t->attributes() as $a => $b) {
                        $result[$key_result][count($result[$key_result])-1][$a] = $b;
                    }
                }
            }
            $results[$key_results] = $result;
        }
        curl_close($ch);
        $processes = $this->getProcessesFkWithKey();
        foreach($results as $key => $value){
            if(!is_null($value['??????'])){
                foreach($value['??????'] as $key2 => $value2){
                    $results[$key]['??????'][$key2]['processes_id'] = $processes[trim($value2['????????????'][0], " ")];
                }
            }
        }
        return $results;
    }

    public function getallLinetype(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT TOP 1000 MD001 AS id , MD002 AS name
               FROM MIL.[dbo].CMSMD
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        return $result;
    }
   
    public function getallMachine(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT TOP 1000 MX001 AS id , MX003 AS name
               FROM MIL.[dbo].CMSMX
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        return $result;

    }
    public function getallProcess(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT MW001 AS id , MW002 AS name
                    FROM MIL.[dbo].CMSMW
                    INNER JOIN [MIL].[dbo].CMSMD ON CMSMW.MW005 = CMSMD.MD001
                    WHERE CMSMD.MD001 NOT IN ('C', 'E')
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        return $result;
    }

    public function getTrendCost($data){
        $ch = curl_init();
         // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
         curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
         curl_setopt($ch, CURLOPT_POST, 1);
         // In real life you should use something like:
         curl_setopt(
             $ch,
             CURLOPT_POSTFIELDS,
             http_build_query(
                 ['sql' => "SELECT   ????????????,SUM(??????????????????*????????????) ????????????
                 FROM(
                     SELECT [PURTD].[TD008] as ??????????????????,
                             [PURTD].[TD010] as ??????????????????,
                             [PURTD].TD012,
                             [MOCTA].TA015 as ????????????,
                             COPTD.TD002 as ????????????,
                             [BOMMD].MD003,
                             ROW_NUMBER() OVER(PARTITION BY COPTD.TD002,BOMMD.MD003 ORDER BY PURTD.TD012 DESC) as row_number2
                     FROM (
                         SELECT ROW_NUMBER() OVER(PARTITION BY TD201 ORDER BY TD002 DESC) as row_number1,*
                         FROM MIL.[dbo].COPTD
                     )COPTD
                     LEFT JOIN MIL.[dbo].[MOCTA] ON COPTD.TD001=MOCTA.TA026 
                                     and COPTD.TD002=MOCTA.TA027
                                     and COPTD.TD003=MOCTA.TA028
                     LEFT JOIN MIL.[dbo].[BOMMD]
                     ON MOCTA.TA006=BOMMD.MD001
                     INNER JOIN (
                         SELECT ROW_NUMBER() OVER(PARTITION BY TD004 ORDER BY TD012 DESC) as row_number,*
                         FROM MIL.[dbo].[PURTD]
                     )[PURTD]
                     ON BOMMD.MD003=PURTD.TD004 AND COPTD.TD002 < PURTD.TD012
                     WHERE TD201 = {$data['order_name']}
                     GROUP BY [PURTD].[TD008],
                             [PURTD].[TD010],
                             [PURTD].TD012,
                             [MOCTA].TA015,
                             COPTD.TD002,
                             [BOMMD].MD003
                 ) a
                 WHERE row_number2 = 1
                 GROUP BY ????????????
                 "]
             )
         );
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
         $head = curl_exec($ch);
         $result = json_decode($head, true);
         // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch);
         return $result;
        return $data['order_name'];
    }

    public function getCustomerCode($data)
    { 
        $sql = "SELECT customer 
        FROM public.file
        WHERE id=:file_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->execute();
        return  $stmt->fetchAll();
    }

    public function getOriginMaterial(){
         // return 'test';
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
                 SUM([PURTD].[TD010]*[MOCTA].TA015) as ???????????? , COPTD.TD201 as ????????????
                FROM MIL.[dbo].COPTD
                LEFT JOIN MIL.[dbo].[MOCTA] ON COPTD.TD001=MOCTA.TA026 
                                and COPTD.TD002=MOCTA.TA027
                                and COPTD.TD003=MOCTA.TA028
                LEFT JOIN MIL.[dbo].[BOMMD]
                ON MOCTA.TA006=BOMMD.MD001
                INNER JOIN (
                SELECT ROW_NUMBER() OVER(PARTITION BY TD004 ORDER BY TD012 DESC) as row_number,*
                FROM MIL.[dbo].[PURTD]
                )[PURTD]
                ON BOMMD.MD003=PURTD.TD004 AND row_number = 1
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

    public function getCC()
    {
        // return 'test';
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT LTRIM (RTRIM (MA001) ) as value,MA002 as label
                FROM [MIL].[dbo].[COPMA] 
                GROUP BY MA001,MA002
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

    public function getCustomerCodes()
    {
        // return 'test';
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT [MA001] as \"????????????\",[MA003] as \"????????????\",[MA002] as \"????????????\"
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
                WHERE dt.row_num=1 AND MA001 != ''
                ORDER BY MA001
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

    public function postBusinessHardness($data){
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT *
                    FROM [MIL].[dbo].[COPTD]
                    WHERE [TD206] = '{$data['hardness']}'
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);


        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if(count($result) == 1){
            $ack = array(
                'status' => 'exist',
                'label'=>$data['hardness'],
                'value'=>preg_replace('/\s+/', '', $result[0]['TD206'])
            );
            return $ack;
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
                ['sql' => "SELECT  MAX([XC001]) AS maxnum
                    FROM [MIL].[dbo].[COPTD]
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        $tmpNum = strval(str_pad(intval($result[0]['maxnum'])+1, 3, '0', STR_PAD_LEFT));
        $strNum = $tmpNum.' ';
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "INSERT INTO [MIL].[dbo].[COPTD] ([COMPANY],[CREATOR],[USR_GROUP],[CREATE_DATE],[MODIFIER],[MODI_DATE],[FLAG],[XC001],[TD206],[XC003])
                VALUES ('MIL', 'nknu', '101000', GETDATE(),'','','1','{$strNum}','{$data['hardness']}','');
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);

        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // var_dump($tmpNum);
        
        $ack = array(
            'status' => 'success',
            'label'=>$data['hardness'],
            'value'=>$tmpNum

        );
        return $ack;
    }

    public function postBusinessTitanizing($data){
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT *
                    FROM [MIL].[dbo].[CMSXC]
                    WHERE [XC002] = '{$data['titanizing']}'
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);


        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if(count($result) == 1){
            $ack = array(
                'status' => 'exist',
                'label'=>$data['titanizing'],
                'value'=>preg_replace('/\s+/', '', $result[0]['XC001'])
            );
            return $ack;
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
                ['sql' => "SELECT  MAX([XC001]) AS maxnum
                    FROM [MIL].[dbo].[CMSXC]
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        $tmpNum = strval(str_pad(intval($result[0]['maxnum'])+1, 3, '0', STR_PAD_LEFT));
        $strNum = $tmpNum.' ';
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "INSERT INTO [MIL].[dbo].[CMSXC] ([COMPANY],[CREATOR],[USR_GROUP],[CREATE_DATE],[MODIFIER],[MODI_DATE],[FLAG],[XC001],[XC002],[XC003])
                VALUES ('MIL', 'nknu', '101000', GETDATE(),'','','1','{$strNum}','{$data['titanizing']}','');
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);

        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // var_dump($tmpNum);
        
        $ack = array(
            'status' => 'success',
            'label'=>$data['titanizing'],
            'value'=>$tmpNum

        );
        return $ack;
    }
    public function postBusinessMaterial($data){
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "http://10.0.1.21/sql");
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT *
                    FROM [MIL].[dbo].[CMSXB]
                    WHERE [XB002] = '{$data['material']}'
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);


        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if(count($result) == 1){
            $ack = array(
                'status' => 'exist',
                'label'=>$data['material'],
                'value'=>preg_replace('/\s+/', '', $result[0]['XB001'])
            );
            return $ack;
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
                ['sql' => "SELECT  MAX([XB001]) AS maxnum
                    FROM [MIL].[dbo].[CMSXB]
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        $tmpNum = strval(str_pad(intval($result[0]['maxnum'])+1, 3, '0', STR_PAD_LEFT));
        $strNum = $tmpNum.' ';
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "INSERT INTO [MIL].[dbo].[CMSXB] ([COMPANY],[CREATOR],[USR_GROUP],[CREATE_DATE],[MODIFIER],[MODI_DATE],[FLAG],[XB001],[XB002],[XB003])
                VALUES ('MIL', 'nknu', '101000', GETDATE(),'','','1','{$strNum}','{$data['material']}','');
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);

        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // var_dump($tmpNum);
        
        $ack = array(
            'status' => 'success',
            'label'=>$data['material'],
            'value'=>$tmpNum

        );
        return $ack;

    }

    public function postCustomerCode($data)
    {
        $sql = " UPDATE public.file
        SET customer =:customer
        WHERE id=:file_id;";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':file_id', $data['file_id']);
        $stmt->bindValue(':customer', $data['customer']);
        $stmt->execute();
    }

    public function getModuleUrl($data)
    {
        $insertValue = "(";
        foreach ($data['module'] as $key => $value) {
            $insertValue .= " {$value},";
        }
        $insertValue = substr_replace($insertValue, ")", -1);
        $sql = "SELECT progress.url,module.name
        FROM(SELECT MIN(id)as id, module_id
            FROM setting.progress
            WHERE module_id in {$insertValue}
            GROUP BY  module_id) AS minTable
        LEFT JOIN setting.progress on minTable.id = progress.id
        LEFT JOIN setting.module on module.id = progress.module_id
             
             ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }
    public function getOrderDetail($data)
    {
        $query = "";
        if (!empty($data['id'])) {
            $query = "WHERE RTRIM(LTRIM([COPTD].[TD201])) LIKE '%{$data['id']}%'";
        }
        if (!empty($data['order_id'])) {
            ($query == '')?$query.=' WHERE':$query.=' AND';
            $query .= " RTRIM(LTRIM([COPTD].[TD001]))+'-'+RTRIM(LTRIM([COPTD].[TD002]))+'-'+RTRIM(LTRIM([COPTD].[TD003])) LIKE '%{$data['order_id']}%'";
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
                ['sql' => "SELECT
                    RTRIM(LTRIM([COPTD].[TD001]))+'-'+RTRIM(LTRIM([COPTD].[TD002])) as ????????????????????????,
                    [COPTD].[TD201] as ????????????,
                    [COPTD].[TD214] as ??????????????????,
                    [COPTA].[TA003] as ????????????,
                    [COPTA].[TA006] as ????????????,
                    [COPTC].[TC008] as ??????,
                    CAST([COPTA].[TA008] AS DECIMAL(18,2)) as ??????,
                    CAST([COPTD].[TD011] AS DECIMAL(18,2)) as ????????????,
                    [COPTD].[TD008] as ?????????,
                    CAST([COPTD].[TD008] AS DECIMAL(18,0))  as ????????????,
                    [COPTA].[TA010] as ????????????,
                    [COPTA].[TA011] as ????????????,
                    [COPTA].[TA013] as ????????????,
                    [COPTA].[TA014] as ?????????,
                    [COPTA].[TA015] as ?????????,
                    [COPTA].[TA016] as ????????????,
                    [COPTA].[TA028] as ????????????,
                    -- [COPTA].[TA029] as ?????????,
                    -- [COPTA].[TA030] as ?????????,
                    [COPTA].[TA031] as ????????????,
                    [COPTA].[TA032] as ???????????????,
                    [CMSXC].[XC002] as ??????,
                    COALESCE(DATEDIFF(week,COPTC.TC039 ,COPTD.TD013),0) AS ????????????
                    FROM [MIL].[dbo].[COPTD] 
                    LEFT JOIN [MIL].[dbo].[COPTC] ON [COPTD].[TD001] = [COPTC].[TC001] AND [COPTD].[TD002] = [COPTC].[TC002]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    LEFT JOIN [MIL].[dbo].[COPTA] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                    {$query}
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
    public function getItemNO($data)
    {
        $query = "WHERE TD004 != '001' ";
        if ($data['picture_num'] != '') {
            $checkquery = ($query == '')?'WHERE':'AND';
            // $query = "WHERE COPTD.TD201 LIKE '%' || {$data['picture_num']} || '%' ";
            $query .= "{$checkquery} RTRIM(LTRIM([COPTD].[TD201])) LIKE '%{$data['picture_num']}%' ";
        }
        if ($data['customer_id'] != '') {
            $checkquery = ($query == '')?'WHERE':'AND';
            // $query = " COPTC.TC004 LIKE '%' || {$data['customer_id']} || '%' ";
            $query .= "{$checkquery} RTRIM(LTRIM([COPTC].[TC004])) LIKE '%{$data['customer_id']}%'";

        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => " SELECT '001' AS ??????, ''  AS  ??????,'' AS  ????????????,'' AS  ??????,''  AS  ??????,''  AS ??????, ''AS TC004, 1 AS rownum
                            UNION
                            SELECT '002' AS ??????, ''  AS  ??????,'' AS  ????????????,'' AS  ??????,''  AS  ??????,''  AS ??????, ''AS TC004, 1 AS rownum
                "]
            )
        );
        /* 

                            SELECT *
                            FROM(
                                SELECT TOP 1000 TD004 AS ??????,TD206  AS  ??????,TD201 AS  ????????????,TD202 AS  ??????,
                                                        [CMSXB].XB002  AS  ??????,
                                                        [CMSXC].[XC002]  AS ??????,[COPTC].[TC004],ROW_NUMBER() OVER ( PARTITION BY TD004 ORDER BY TD004 ,TD206 DESC,TD201,TD202 DESC,[CMSXB].XB002 DESC,[CMSXC].[XC002] DESC,[COPTC].[TC004])rownum
                                FROM [MIL].[dbo].COPTD
                                LEFT JOIN [MIL].[dbo].COPTC ON COPTC.TC001 = COPTD.TD001 AND COPTD.TD002 = COPTC.TC002
                                LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTD.TD205
                                LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                                {$query}
                                GROUP BY TD004 ,TD206,TD201,TD202,[CMSXB].XB002,[CMSXC].[XC002],[COPTC].[TC004]
                            )dt
                            WHERE dt.rownum = 1
                            ORDER BY ?????? ASC
         */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }
    public function getItemNOReact($data)
    {
        $values = [
            'picture_num' => '',
            'customer_id' => '',
            'cur_page' => 1,
            'size' => 10,
            'row_size' => 5
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $query = '';
        if ($values['picture_num'] != '') {
            // $query = "WHERE COPTD.TD201 LIKE '%' || {$data['picture_num']} || '%' ";
            $query = "WHERE RTRIM(LTRIM([COPTD].[TD201])) LIKE '%{$values['picture_num']}%' ";
        }
        if ($values['customer_id'] != '') {
            $checkquery = ($query == '')?'WHERE':'AND';
            // $query = " COPTC.TC004 LIKE '%' || {$data['customer_id']} || '%' ";
            $query .= "{$checkquery} RTRIM(LTRIM([COPTC].[TC004])) LIKE '%{$values['customer_id']}%'";
        }
        $length = $values['size'] * $values['cur_page'];
        $start = $length - $values['size'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT *
                FROM(
                    SELECT TOP {$length} *,ROW_NUMBER() OVER(ORDER BY ?????? ASC) row_num
                    FROM(
                        SELECT *
                        FROM(
                            SELECT TOP 1000 TD004 AS ??????,TD206  AS  ??????,TD201 AS  ????????????,TD202 AS  ??????,
                                [CMSXB].XB002  AS  ??????, [CMSXC].[XC002]  AS ??????,[COPTC].[TC004],
                                ROW_NUMBER() OVER ( PARTITION BY TD004 ORDER BY TD004 ,TD206 DESC,TD201,TD202 DESC,[CMSXB].XB002 DESC,[CMSXC].[XC002] DESC,[COPTC].[TC004])rownum
                            FROM [MIL].[dbo].COPTD
                            LEFT JOIN [MIL].[dbo].COPTC ON COPTC.TC001 = COPTD.TD001 AND COPTD.TD002 = COPTC.TC002
                            LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTD.TD205
                            LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                            {$query}
                            GROUP BY TD004 ,TD206,TD201,TD202,[CMSXB].XB002,[CMSXC].[XC002],[COPTC].[TC004]
                        )dt
                        WHERE dt.rownum = 1
                    )dt
                )dt
                WHERE dt.row_num > {$start}
                ORDER BY ?????? ASC
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result['data'] = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

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
                    FROM(
                        SELECT *
                        FROM(
                            SELECT TOP 1000 TD004 AS ??????,TD206  AS  ??????,TD201 AS  ????????????,TD202 AS  ??????,
                                [CMSXB].XB002  AS  ??????, [CMSXC].[XC002]  AS ??????,[COPTC].[TC004],
                                ROW_NUMBER() OVER ( PARTITION BY TD004 ORDER BY TD004 ,TD206 DESC,TD201,TD202 DESC,[CMSXB].XB002 DESC,[CMSXC].[XC002] DESC,[COPTC].[TC004])rownum
                            FROM [MIL].[dbo].COPTD
                            LEFT JOIN [MIL].[dbo].COPTC ON COPTC.TC001 = COPTD.TD001 AND COPTD.TD002 = COPTC.TC002
                            LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTD.TD205
                            LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                            {$query}
                            GROUP BY TD004 ,TD206,TD201,TD202,[CMSXB].XB002,[CMSXC].[XC002],[COPTC].[TC004]
                        )dt
                        WHERE dt.rownum = 1
                    )dt
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
        return $result;
    }
    public function getBusiness($data)
    {
        $query = "";
        if (!empty($data['id'])) {
            $query = "WHERE RTRIM(LTRIM([COPTD].[TD201])) LIKE '%{$data['id']}%' OR  RTRIM(LTRIM([COPTB].[TB201])) LIKE '%{$data['id']}%'";
        }
        if (!empty($data['order_id'])) {
            ($query == '')?$query.='WHERE':$query.='AND';
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
                    RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002]))+'-'+[COPTB].[TB003] as ???????????????????????????,
                    RTRIM(LTRIM([COPTC].[TC001]))+'-'+RTRIM(LTRIM([COPTC].[TC002]))+'-'+[COPTD].[TD003] as ????????????????????????,
                    [COPTA].[TA003] as ????????????,
                    CAST([COPTB].[TB007] AS DECIMAL(18,0))  as ????????????,
                    CAST([COPTB].[TB009] AS DECIMAL(18,2)) as ????????????,
                    CAST([COPTB].[TB010] AS DECIMAL(18,2)) as ????????????,
                    CAST([COPTD].[TD008] AS DECIMAL(18,0))  as ????????????,
                    CAST([COPTD].[TD011] AS DECIMAL(18,2)) as ????????????,
                    CAST([COPTD].[TD012] AS DECIMAL(18,2)) as ????????????,
                    [COPTA].[TA006] as ????????????,
                    [COPTB].[TB201] as ????????????,
                    [CMSXB].XB002 as ??????,
                    [newCMSXB].XB002 as ????????????,
                    [CMSXC].[XC002] as ??????,
                    [COPTC].[TC004] as ????????????,
                    [COPTA].[TA007] as ??????,
                    [COPTD].[TD006]as ??????,
                    [COPTD].[TD201] as ????????????,
                    [COPTB].[TB211] as ?????????????????????,
                    [COPTD].[TD214] as ??????????????????,
                    [COPTA].[TA003] as ????????????,
                    [COPTA].[TA006] as ????????????,
                    [COPTA].[TA007] as ??????,
                    CAST([COPTA].[TA008] AS DECIMAL(18,2)) as ??????,
                    CAST([COPTB].[TB009] AS DECIMAL(18,2)) as ????????????,
                    [COPTA].[TA025] as ?????????,
                    [COPTA].[TA010] as ????????????,
                    [COPTA].[TA011] as ????????????,
                    [COPTC].[TC039] as ??????????????????,
                    [COPTA].[TA013] as ?????????????????????,
                    [COPTA].[TA014] as ?????????,
                    [COPTA].[TA015] as ?????????,
                    [COPTA].[TA016] as ????????????,
                    [COPTA].[TA028] as ????????????,
                    -- [COPTA].[TA029] as ?????????,
                    -- [COPTA].[TA030] as ?????????,
                    [COPTA].[TA031] as ????????????,
                    [COPTA].[TA032] as ???????????????,
                    [CMSXC].[XC002] as ??????
                    FROM  [MIL].[dbo].[COPTD]
                    LEFT JOIN  [MIL].[dbo].[COPTC] ON [COPTC].[TC001] = [COPTD].[TD001] AND [COPTC].[TC002] = [COPTD].[TD002]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    LEFT JOIN [MIL].[dbo].[COPTA] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXB] AS newCMSXB ON newCMSXB.XB001 = COPTD.TD205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTD.TD204
                    {$query}
                    ORDER BY [COPTC].[TC039] DESC,[COPTA].[TA013] DESC
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
    public function getBusinessUnordered($data)
    {

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
                ['sql' => "SELECT TOP 1000
                    RTRIM(LTRIM([COPTA].[TA001]))+'-'+RTRIM(LTRIM([COPTA].[TA002])) as ????????????,
                    [COPTA].[TA003] as ????????????,
                    CAST([COPTB].[TB007] AS DECIMAL(18,0))  as ????????????,
                    CAST([COPTB].[TB009] AS DECIMAL(18,2)) as ????????????,
                    CAST([COPTB].[TB010] AS DECIMAL(18,2)) as ????????????,
                    [COPTA].[TA006] as ????????????,
                    [COPTB].[TB201] as ????????????,
                    [CMSXB].XB002 as ??????,
                    [CMSXC].[XC002] as ??????,
                    [COPTA].[TA004] as ????????????,
                    [COPTA].[TA007] as ??????
                    FROM [MIL].[dbo].[COPTA]
                    LEFT JOIN [MIL].[dbo].[COPTB] ON [COPTB].[TB001] = [COPTA].[TA001] AND [COPTB].[TB002] = [COPTA].[TA002]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTB.TB205
                    LEFT JOIN [MIL].[dbo].[CMSXC] ON CMSXC.XC001 = COPTB.TB204
                    WHERE NOT EXISTS (
						SELECT *
						FROM [MIL].[dbo].[COPTD]
						WHERE [COPTD].[TD017] = [COPTB].[TB001] AND [COPTD].[TD018] = [COPTB].[TB002] AND [COPTD].[TD019] = [COPTB].[TB003]
                    ) {$query}
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
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
        // $sql = "SELECT file.id AS \"????????????\",order_name AS \"????????????\",customer.outresourcer as \"????????????\"
        //         ,to_char(quotation.update_time, 'YYYY-MM-DD') AS \"????????????\",file.deadline AS \"?????????\"
        //         ,quotation.cost AS ????????????, quotation.num AS \"??????\",quotation.discount AS \"??????\"
        //         ,quotation.descript AS \"????????????\",file.outsourcer AS \"????????????\",file.outsourcer_amount AS \"????????????\",'' AS \"??????\"
        //     FROM public.file
        //     LEFT JOIN (
        //         SELECT \"????????????\" AS customer ,\"????????????\" AS outresourcer
        //         FROM json_to_recordset(
        //             '{$row}'
        //         ) as setting_customer_code(\"????????????\" text,\"????????????\" text)
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
    public function getComponentsByMIL($data)
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
                "SELECT TOP 200 [TB005] name
                    FROM [MIL].[dbo].[COPTB]
                    WHERE [TB005] != ''
                    GROUP BY [TB005]
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
    public function getComponents($data)
    {
        $row = json_encode($data);
        $sql = "SELECT component.id, REPLACE(COALESCE(component.name,mil.name),'\"','???') \"name\"
            FROM (
                SELECT *
                FROM public.component
                WHERE component.name != ''
            )component
            FULL OUTER JOIN json_to_recordset(
                '{$row}'
            ) as mil(name text) ON mil.name = component.name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function getProcessCount($data)
    {
        $values = [
            'is_finish' => 'Y'
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $query = "";

        // if (!empty($data['id'])) {
        //     $query = "AND RTRIM(LTRIM([COPTB].[TA001]))+'-'+LTRIM(RTRIM([COPTB].[TA002])) LIKE '%{$data['id']}%'";
        // }
        // if ($data['start'] != '' || $data['end'] != '') {
            // AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
            if ($data['start'] == '') {
                $starttime = 'GETDATE()';
            } else {
                $starttime = "CONVERT(DATETIME, '{$data['start']}')";
            }
            if ($data['end'] == '') {
                $endtime = 'GETDATE()';
            } else {
                $endtime = "CONVERT(DATETIME, '{$data['end']}')";
            }
            $query .= "AND ([SFCTA].[TA009] BETWEEN CONVERT(NVARCHAR,{$starttime},112) AND CONVERT(NVARCHAR,{$endtime},112)) ";
        // }
        $selectquery='';
        $typequery='';
        $groupbyquery='';
        if ($data['type'] == 'history') {
            $selectquery=',[SFCTA].[TA007] AS ????????????';
            $groupbyquery=',[SFCTA].[TA007]';

            $typequery=' AND SFCTA.TA005=2';
        }else if ($data['type'] == 'temperary') {
            $selectquery=',[SFCTA].[TA007] AS ????????????';
            $groupbyquery=',[SFCTA].[TA007]';

            $typequery=' AND SFCTA.TA005=2';
        }else if ($data['type'] == 'inside') {
            $typequery=' AND SFCTA.TA005=1';
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
                ['sql' => "SELECT [SFCTA].[TA004] as ????????????, [CMSMW].[MW002] as ????????????,count(*) AS count{$selectquery}
                FROM [MIL].[dbo].[CMSMW],[MIL].[dbo].[COPTD],[MIL].[dbo].[MOCTA],[MIL].[dbo].[SFCTA]
                WHERE CMSMW.MW001=SFCTA.TA004
                and COPTD.TD001=MOCTA.TA026 
                and COPTD.TD002=MOCTA.TA027
                and COPTD.TD003=MOCTA.TA028
                and SFCTA.TA001=MOCTA.TA001 
                and SFCTA.TA002=MOCTA.TA002  
                AND MOCTA.TA001=SFCTA.TA001 
                and MOCTA.TA002=SFCTA.TA002
                AND SFCTA.TA032='{$values['is_finish']}'
                    {$typequery}
                    {$query}
                GROUP BY [CMSMW].[MW002],[SFCTA].[TA004]{$groupbyquery}
                
                "]
                /* [SFCTA].[TA003] as ????????????, [SFCTA].[TA008] as ????????????, [SFCTA].[TA009] as ????????????, [SFCTA].[TA030] as ????????????
                    , [SFCTA].[TA031] as ????????????,[COPTB].[TB204] as ????????????,[COPTB].[TB205] as ???????????? */
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }
    function getmodifyprocessOutsourcerCount($data)
    {
        $values = [
            'is_finish' => 'Y'
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $query = "";

        // if (!empty($data['id'])) {
        //     $query = "AND RTRIM(LTRIM([COPTB].[TA001]))+'-'+LTRIM(RTRIM([COPTB].[TA002])) LIKE '%{$data['id']}%'";
        // }
        // if ($data['start'] != '' || $data['end'] != '') {
            // AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
            if ($data['start'] == '') {
                $starttime = 'GETDATE()';
            } else {
                $starttime = "CONVERT(DATETIME, '{$data['start']}')";
            }
            if ($data['end'] == '') {
                $endtime = 'GETDATE()';
            } else {
                $endtime = "CONVERT(DATETIME, '{$data['end']}')";
            }
            $query .= "AND ([SFCTA].[TA009] BETWEEN CONVERT(NVARCHAR,{$starttime},112) AND CONVERT(NVARCHAR,{$endtime},112)) ";
        // }
        $selectquery='';
        $typequery='';
        $groupbyquery='';
        if ($data['type'] == 'history') {
            $selectquery=',[SFCTA].[TA007] AS ????????????';
            $groupbyquery=',[SFCTA].[TA007]';

            $typequery=' AND SFCTA.TA005=2';
        }else if ($data['type'] == 'temperary') {
            // $selectquery=',[SFCTA].[TA007] AS ????????????';
            $groupbyquery=',[SFCTA].[TA007]';

            $typequery=' AND SFCTA.TA005=2';
        }else if ($data['type'] == 'inside') {
            $typequery=' AND SFCTA.TA005=1';
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
                ['sql' => "SELECT [SFCTA].[TA007] as ????????????, [CMSMW].[MW002] as ????????????,count(*) AS count,COUNT(CASE WHEN SFCTA.TA032='N' THEN 1 END) AS unfinish,COUNT(CASE WHEN SFCTA.TA032='Y' THEN 1 END) AS finish {$selectquery}
                FROM [MIL].[dbo].[CMSMW],[MIL].[dbo].[SFCTA]
                WHERE CMSMW.MW001=SFCTA.TA004
                    {$typequery}
                    {$query}
                GROUP BY [CMSMW].[MW002],[SFCTA].[TA007]{$groupbyquery}
                
                "]
                /* [SFCTA].[TA003] as ????????????, [SFCTA].[TA008] as ????????????, [SFCTA].[TA009] as ????????????, [SFCTA].[TA030] as ????????????
                    , [SFCTA].[TA031] as ????????????,[COPTB].[TB204] as ????????????,[COPTB].[TB205] as ???????????? */
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }
    function getAllProcessCount($data)
    {
        $values = [
            'is_finish' => 'Y'
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = $data[$key];
            }
        }
        $query = "";

        // if (!empty($data['id'])) {
        //     $query = "AND RTRIM(LTRIM([COPTB].[TA001]))+'-'+LTRIM(RTRIM([COPTB].[TA002])) LIKE '%{$data['id']}%'";
        // }
        // if ($data['start'] != '' || $data['end'] != '') {
            // AND (:start BETWEEN quotation.update_time AND quotation.deadline OR :end BETWEEN quotation.update_time AND quotation.deadline)
            if ($data['start'] == '') {
                $starttime = 'GETDATE()';
            } else {
                $starttime = "CONVERT(DATETIME, '{$data['start']}')";
            }
            if ($data['end'] == '') {
                $endtime = 'GETDATE()';
            } else {
                $endtime = "CONVERT(DATETIME, '{$data['end']}')";
            }
            $query .= "AND ([SFCTA].[TA009] BETWEEN CONVERT(NVARCHAR,{$starttime},112) AND CONVERT(NVARCHAR,{$endtime},112)) ";
        // }
        $selectquery='';
        $typequery='';
        $groupbyquery='';
        if ($data['type'] == 'history') {
            $selectquery=',[SFCTA].[TA007] AS ????????????';
            $groupbyquery=',[SFCTA].[TA007]';

            $typequery=' AND SFCTA.TA005=2';
        }else if ($data['type'] == 'temperary') {
            $selectquery=',[SFCTA].[TA007] AS ????????????';
            $groupbyquery=',[SFCTA].[TA007]';

            $typequery=' AND SFCTA.TA005=2';
        }else if ($data['type'] == 'inside') {
            $typequery=' AND SFCTA.TA005=1';
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
                ['sql' => "SELECT [SFCTA].[TA004] as ????????????, [CMSMW].[MW002] as ????????????,count(*) AS count,COUNT(CASE WHEN SFCTA.TA032='N' THEN 1 END) AS unfinish,COUNT(CASE WHEN SFCTA.TA032='Y' THEN 1 END) AS finish {$selectquery}
                FROM [MIL].[dbo].[CMSMW],[MIL].[dbo].[COPTD],[MIL].[dbo].[MOCTA],[MIL].[dbo].[SFCTA]
                WHERE CMSMW.MW001=SFCTA.TA004
                and COPTD.TD001=MOCTA.TA026 
                and COPTD.TD002=MOCTA.TA027
                and COPTD.TD003=MOCTA.TA028
                and SFCTA.TA001=MOCTA.TA001 
                and SFCTA.TA002=MOCTA.TA002  
                AND MOCTA.TA001=SFCTA.TA001 
                and MOCTA.TA002=SFCTA.TA002
                    {$typequery}
                    {$query}
                GROUP BY [CMSMW].[MW002],[SFCTA].[TA004]{$groupbyquery}
                
                "]
                /* [SFCTA].[TA003] as ????????????, [SFCTA].[TA008] as ????????????, [SFCTA].[TA009] as ????????????, [SFCTA].[TA030] as ????????????
                    , [SFCTA].[TA031] as ????????????,[COPTB].[TB204] as ????????????,[COPTB].[TB205] as ???????????? */
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }
    function getProcess($data)
    {
        $query = "";
        if (!empty($data['id'])) {
            $query = "WHERE RTRIM(LTRIM([COPTB].[TA001]))+'-'+LTRIM(RTRIM([COPTB].[TA002])) LIKE '%{$data['id']}%'";
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
                ['sql' => "SELECT [SFCTA].[TA004] as ????????????, [CMSMW].[MW002] as ????????????
                FROM [MIL].[dbo].[CMSMW],[MIL].[dbo].[COPTB],[MIL].[dbo].[COPTD],[MIL].[dbo].[MOCTA],[MIL].[dbo].[SFCTA]
                WHERE CMSMW.MW001=SFCTA.TA004
                and COPTD.TD001=MOCTA.TA026 
                and COPTD.TD002=MOCTA.TA027
                and COPTD.TD003=MOCTA.TA028 
                and COPTD.TD002=COPTB.TB002
                and COPTD.TD003=COPTB.TB003
                and COPTD.TD004=COPTB.TB004
                and SFCTA.TA001=MOCTA.TA001 
                and SFCTA.TA002=MOCTA.TA002  
                AND MOCTA.TA001=SFCTA.TA001 
                and MOCTA.TA002=SFCTA.TA002
                    {$query}
                "]
                /* [SFCTA].[TA003] as ????????????, [SFCTA].[TA008] as ????????????, [SFCTA].[TA009] as ????????????, [SFCTA].[TA030] as ????????????
                    , [SFCTA].[TA031] as ????????????,[COPTB].[TB204] as ????????????,[COPTB].[TB205] as ???????????? */
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }
    function getProcessByComponentName($data)
    {
        $query = "";
        foreach ($data['result'] as $key => $value) {
            $query = "WHERE RTRIM(LTRIM([COPTB].[TB001]))+'-'+LTRIM(RTRIM([COPTB].[TB002])) LIKE '%{$value['order_serial']}%'";
            if ($query == "") {
                continue;
            }
            $query = "";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
            curl_setopt($ch, CURLOPT_POST, 1);
            // In real life you should use something like:
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query(
                    ['sql' => "SELECT TOP 6 [COPTB].TB005 as ????????????,[SFCTA].[TA003] as ????????????, [SFCTA].[TA004] as ????????????, [CMSMW].[MW002] as ????????????
                    FROM [MIL].[dbo].[CMSMW],[MIL].[dbo].[COPTB],[MIL].[dbo].[COPTD],[MIL].[dbo].[MOCTA],[MIL].[dbo].[SFCTA]
                    WHERE CMSMW.MW001=SFCTA.TA004
                    and COPTD.TD001=MOCTA.TA026 
                    and COPTD.TD002=MOCTA.TA027
                    and COPTD.TD003=MOCTA.TA028 
                    and COPTD.TD002=COPTB.TB002
                    and COPTD.TD003=COPTB.TB003
                    and COPTD.TD004=COPTB.TB004
                    and SFCTA.TA001=MOCTA.TA001 
                    and SFCTA.TA002=MOCTA.TA002  
                    AND MOCTA.TA001=SFCTA.TA001 
                    and MOCTA.TA002=SFCTA.TA002
                        {$query}
                    "]
                )
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $head = curl_exec($ch);
            $result = json_decode($head, true);
            $data['result'][$key]['processes'] = $result;
            // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $query = "";
        }
        return $data;
    }
    function getMaterialStuffByOrderSerial($data)
    {
        
        $query = "";
        
        foreach ($data['result'] as $key => $value) {
            $query = "WHERE COPTD.TD201 LIKE '%{$value['order_name']}%'";
            if ($query == "") {
                continue;
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
            curl_setopt($ch, CURLOPT_POST, 1);
            // In real life you should use something like:
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query(
                    ['sql' => "SELECT TOP 4 XB002 as ??????
                    FROM [MIL].[dbo].[COPTD]
                    LEFT JOIN [MIL].[dbo].[CMSXB] ON CMSXB.XB001 = COPTD.TD205
                    {$query}
                    GROUP BY XB002
                    "]
                )
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $head = curl_exec($ch);
            $result = json_decode($head, true);
            $data['result'][$key]['material'] = $result;
            // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
            curl_setopt($ch, CURLOPT_POST, 1);
            // In real life you should use something like:
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query(
                    ['sql' => "SELECT TOP 4 [MOCTB].TB012 as ??????,[MOCTB].TB004 as ????????????
                    FROM [MIL].[dbo].[COPTD]
                    LEFT JOIN [MIL].[dbo].[MOCTB] ON MOCTB.TB001 = COPTD.TD001 AND MOCTB.TB002 = COPTD.TD002
                    {$query}
                    GROUP BY [MOCTB].TB012,[MOCTB].TB004
                    "]
                )
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $head = curl_exec($ch);
            $result = json_decode($head, true);
            $data['result'][$key]['stuff'] = $result;
            // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
            curl_setopt($ch, CURLOPT_POST, 1);
            // In real life you should use something like:
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query(
                    ['sql' => "SELECT TOP 1000 SUM([PURTD].[TD010]*[MOCTA].TA015) as ???????????? 
                   FROM MIL.[dbo].COPTD
                   LEFT JOIN MIL.[dbo].[MOCTA] ON COPTD.TD001=MOCTA.TA026 
                                   and COPTD.TD002=MOCTA.TA027
                                   and COPTD.TD003=MOCTA.TA028
                   LEFT JOIN MIL.[dbo].[BOMMD]
                   ON MOCTA.TA006=BOMMD.MD001
                   INNER JOIN (
                   SELECT ROW_NUMBER() OVER(PARTITION BY TD004 ORDER BY TD012 DESC) as row_number,*
                   FROM MIL.[dbo].[PURTD]
                   )[PURTD]
                   ON BOMMD.MD003=PURTD.TD004 AND row_number = 1
                   WHERE  TD201 = '{$value['order_name']}' AND row_number1=1
                    "]
                )
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $head = curl_exec($ch);
            $result = json_decode($head, true);

            
            $data['result'][$key]['origin'] = $result;
            // $data['result'][$key]['name'] = $result;
            // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $query = "";
        }
        return $data;
    }
    function getOrderByFile($data)
    {
        $sql = "SELECT order_name,file.id
            FROM public.file
            INNER JOIN (
                SELECT MAX(file.id) id
                FROM public.file
                WHERE file.order_name IS NOT NULL
                GROUP BY file.order_name
            )file_max ON file_max.id = file.id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function getTitanizing($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT LTRIM (RTRIM (XC001) )as value,XC002 as label
                    FROM [MIL].[dbo].[CMSXC]
                    GROUP BY XC001,XC002
                    ORDER BY XC002
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        return $result;
    }
    function getHardness($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT [TB206] as \"label\", [TB206] as value
                    FROM [MIL].[dbo].[COPTB]
                    GROUP BY [TB206]
                    ORDER BY [TB206]
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        return $result;
    }
    function getMaterial($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT  LTRIM (RTRIM (XB001) ) as value,XB002 as label
                    FROM [MIL].[dbo].[CMSXB]
                    GROUP BY XB001,XB002
                    ORDER BY XB002
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        return $result;
    }
    function getYear($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => "SELECT year([TA003]) as \"label\", year([TA003]) as value
                    FROM [MIL].[dbo].[COPTA]
                    GROUP BY year([TA003])
                    ORDER BY year([TA003])
                "]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        return $result;
    }
    public function getOriginMaterialSupplier($data)
    {
        if ($data['size'] < 0) {
            $length = '';
            $start = 0;
            $limit = '';
        } else {
            $length = $data['cur_page'] * $data['size'];
            $start = $length - $data['size'];
            $limit = " LIMIT {$length}";
        }
        $condition = '';
        if (array_key_exists('type', $data)) {
            $condition .= 'AND supplier_number = :type ';
        }
        if (array_key_exists('number', $data)) {
            $condition .= 'AND origin_material_number = :number ';
        }
        if (array_key_exists('name', $data)) {
            $condition .= 'AND origin_material_name = :name ';
        }
        if (array_key_exists('standard', $data)) {
            $condition .= 'AND standard = :standard ';
        }
        if($condition != ''){
            $condition = substr_replace($condition, 'WHERE', strpos($condition, 'AND'), strlen('AND'));
        }
        $condition .= $limit;
        $sql = "SELECT * FROM(
                SELECT origin_material_supplier_id id, supplier_number as type, origin_material_number as number, origin_material_name as name, specification, count, standard, note, file_id qr_code, ROW_NUMBER() OVER (ORDER BY origin_material_supplier_id) row_num
                FROM origin_material_supplier
                LEFT JOIN origin_material ON origin_material_supplier.origin_material_id = origin_material.origin_material_id
                LEFT JOIN supplier ON origin_material_supplier.supplier_id = supplier.supplier_id
                {$condition}
            ) OMS
            WHERE row_num > {$start}
        ";
        $stmt = $this->container->db->prepare($sql);
        if (array_key_exists('type', $data)) {
            $stmt->bindParam(':type', $data['type'], PDO::PARAM_STR);
        }
        if (array_key_exists('number', $data)) {
            $stmt->bindParam(':number', $data['number'], PDO::PARAM_STR);
        }
        if (array_key_exists('name', $data)) {
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        }
        if (array_key_exists('standard', $data)) {
            $stmt->bindParam(':standard', $data['standard'], PDO::PARAM_STR);
        }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        for($i=0; $i < count($result); $i++){
            $result[$i]['qr_code'] = '/3DConvert/PhaseGallery/order_image/' . $result[$i]['qr_code'];
        }
        return $result;
    }

    public function getProcessesFkWithKey()
    {
        $sql = "SELECT TRIM(processes_fk_value), processes_id FROM public.processes_fk
            ORDER BY processes_id ASC, processes_fk_key ASC 
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } else {
            var_dump($stmt->errorInfo());
            return ["status" => "failed"];
        }
    }
}
