<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class report
{
    protected $container;
    protected $db;

    public function __construct()
    {
        global $container;
        $this->container = $container;
        $this->db = $container->db;
        $this->db_sqlsrv = $container->db_sqlsrv;
    }

    public function convertDateFormat($params)
    {
        $params['start'] = str_replace('-', '', $params['start']);
        $params['end'] = str_replace('-', '', $params['end']);
        return $params;
    }

    public function readCURLConnection($sql)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(["sql" => $sql])
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        curl_close($ch);
        return $result;
    }

    public function readExportStaffProductivity($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => '',
            'uid' => '',
            'line' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
            else {
                unset($bind_values[$key]);
            }
        }

        $line_condition = "";
        $uid_condition = "";

        if (isset($bind_values['line'])) {
            $line_condition = "AND SFCTB.TB005 = :line";
        }

        if (isset($bind_values['uid'])) {
            $uid_condition = "AND SFCTC.CREATOR = :uid";
        }

        $sql = "SELECT CMSMV3.MV001 工號, CMSMV3.MV002 姓名, SFCTB.TB015 單據日期, COUNT(SFCTB.TB002) 移轉單單號,
                    COUNT(SFCTB.TB001) 移轉單單別, SFCTB.TB005 移出部門, SFCTB.TB006 移出部門名稱, CAST(SUM(SFCTC.TC041) AS INT) 移轉數量,
                    CAST(SUM(SFCTC.TC014) AS INT) 驗收數量, CAST(SUM(SFCTC.TC037) AS INT) 不良數量, CAST(SUM(SFCTC.TC016) AS INT) 報廢數量
                FROM SFCTB
                INNER JOIN SFCTC ON (SFCTB.TB001 = SFCTC.TC001 AND SFCTB.TB002 = SFCTC.TC002)
                RIGHT OUTER JOIN MOCTA ON (
                    MOCTA.TA001 = SFCTC.TC004
                        AND MOCTA.TA002 = SFCTC.TC005
                        AND SFCTC.TC004 = MOCTA.TA001
                        AND SFCTC.TC005 = MOCTA.TA002
                )
                INNER JOIN CMSMV CMSMV3 ON (SFCTC.CREATOR = CMSMV3.MV001)
                WHERE SFCTB.TB013 IN ('Y')
                    AND SFCTB.TB015 BETWEEN :date_begin AND :date_end
                    AND SFCTC.TC013 NOT IN ('5', '6') {$uid_condition} {$line_condition}
                GROUP BY CMSMV3.MV001, CMSMV3.MV002, SFCTB.TB015, SFCTB.TB005, SFCTB.TB006
                ORDER BY CMSMV3.MV001, SFCTB.TB006
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT SFCTB.TB015 單據日期, COUNT(SFCTB.TB002) 移轉單單號, CAST(SUM(SFCTC.TC041) AS INT) 移轉數量
                FROM SFCTB
                INNER JOIN SFCTC ON (SFCTB.TB001 = SFCTC.TC001 AND SFCTB.TB002 = SFCTC.TC002)
                RIGHT OUTER JOIN MOCTA ON (
                    MOCTA.TA001 = SFCTC.TC004
                        AND MOCTA.TA002 = SFCTC.TC005
                        AND SFCTC.TC004 = MOCTA.TA001
                        AND SFCTC.TC005 = MOCTA.TA002
                )
                INNER JOIN CMSMV CMSMV3 ON (SFCTC.CREATOR = CMSMV3.MV001)
                WHERE SFCTB.TB013 IN ('Y')
                    AND SFCTB.TB015 BETWEEN :date_begin AND :date_end
                    AND SFCTC.TC013 NOT IN ('5', '6') {$uid_condition} {$line_condition}
                GROUP BY SFCTB.TB015
                ORDER BY SFCTB.TB015
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result['sum'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readItemCategoryAnalysis($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }

        $sql = "SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, MOCTA.TA026 訂單單別, MOCTA.TA027 訂單單號, MOCTA.TA028 訂單序號,
                    NULL AS '訂單變數', COPTD.TD004 品號, CAST(COPTD.TD008 AS INT) \"訂單數量(無聚總)\", NULL AS '製令變數',
                    SUBSTRING(COPTD.TD004, 9, 2) \"品號切割9-10碼\", COPTD.TD005 品名,
                    (MOCTA.TA001 +'-'+MOCTA.TA002) \"製令單別單號\",MOCTA.TA001 製令單別,MOCTA.TA002 製令單號, CAST(MOCTA.TA015 AS INT) 預計產量
                FROM MOCTA
                RIGHT OUTER JOIN COPTD ON COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028
                INNER JOIN COPTC ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
                WHERE COPTC.TC003 BETWEEN :date_begin AND :date_end
                    AND MOCTA.TA011 NOT IN ('y')
                AND (
                    MOCTA.TA001 IS NULL
                        OR MOCTA.TA001 NOT IN ('5202', '5205', '5198', '5199', '5207')
                )
                ORDER BY COPTC.TC003, MOCTA.TA028, COPTD.TD004
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readCilentOrder($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => '',
            "customer_id" => '',
            "customer_name" => '',
            "key_word" => ''
        ];

        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }

        $condition = "";
        $condition_values = [
            "customer_id" => " AND COPTC.TC004 = {$bind_values['customer_id']}",
            "customer_name" => " AND COPMA.MA002 = '{$bind_values['customer_name']}'",
            "key_word" => " AND (COPTD.TD004 = '{$bind_values['key_word']}' OR COPTD.TD201 = '{$bind_values['key_word']}')"
        ];

        foreach ($condition_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $condition .= $value;
                $condition_values[$key] = $params[$key];
            } else {
                if($key != 'date_begin' && $key != 'date_end') {
                    unset($bind_values[$key]);
                }
                unset($condition_values[$key]);
            }
        }

        $sql = "SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, COPMA.MA002 客戶簡稱, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號, COPTD.TD003 訂單序號, 
                    (COPTD.TD001 + '-' + COPTD.TD002 + '-' + COPTD.TD003) 訂單單別單號序號,
                    COPTD.TD004 品號, COPTD.TD201 客戶圖號, COPTD.TD008 訂單數量,
                    COPTD.TD011 單價, COPTD.TD012 金額, COPTC.TC008 交易幣別
                FROM PURMA
                INNER JOIN MOCTA ON (MOCTA.TA032 = PURMA.MA001)
                RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                INNER JOIN COPMA ON (COPTC.TC004 = COPMA.MA001)
                WHERE COPTC.TC003 BETWEEN '{$bind_values['date_begin']}' AND '{$bind_values['date_end']}'
                    AND COPTC.TC027 IN ('Y') $condition
                ORDER BY COPMA.MA002, COPTC.TC003, (COPTD.TD001 + '-' + COPTD.TD002 + '-' + COPTD.TD003)
        ";
        return $this->readCURLConnection($sql);
    }

    public function exportCilentOrderOne($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => ''
        ];

        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }

        $sql = "SELECT tmp.訂單日期, COUNT(tmp.品號) 次數
                FROM (
                    SELECT LEFT(COPTC.TC003, 6) 訂單日期, COPTD.TD004 品號
                    FROM PURMA
                    INNER JOIN MOCTA ON (MOCTA.TA032 = PURMA.MA001)
                    RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                    INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                    INNER JOIN COPMA ON (COPTC.TC004 = COPMA.MA001)
                    WHERE COPTC.TC003 BETWEEN '{$bind_values['date_begin']}' AND '{$bind_values['date_end']}'
                        AND COPTC.TC027 IN ('Y') AND COPTD.TD004 = '001'
                )tmp
                GROUP BY tmp.訂單日期, tmp.品號
                ORDER BY tmp.訂單日期, tmp.品號
                
        ";
        return $this->readCURLConnection($sql);
    }

    public function exportCilentOrderOther($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => ''
        ];

        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }

        $sql = "SELECT LEFT(COPTC.TC003, 6) 訂單日期, COUNT(COPTD.TD004) 次數
                FROM PURMA
                INNER JOIN MOCTA ON (MOCTA.TA032 = PURMA.MA001)
                RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                INNER JOIN COPMA ON (COPTC.TC004 = COPMA.MA001)
                WHERE COPTC.TC003 BETWEEN '{$bind_values['date_begin']}' AND '{$bind_values['date_end']}'
                    AND COPTC.TC027 IN ('Y') AND COPTD.TD004 != '001'
                GROUP BY LEFT(COPTC.TC003, 6), COPTD.TD004
                ORDER BY LEFT(COPTC.TC003, 6)
        ";
        return $this->readCURLConnection($sql);
    }

    public function createExportCilentOrderSpreadsheet($db_data)
    {
        $spreadsheet = new Spreadsheet();
        
        $row_num = 1;
        $next_row_num = 2;

        foreach ($db_data as $key => $values) {
            $thead_year = [$key];
            $spreadsheet->getActiveSheet()->fromArray($thead_year, NULL, "A{$row_num}");  /* write thead */

            $row_num += 1;
            $next_row_num = $row_num + 1;
            
            /* thead */
            $thead_top = ['月份', '總計數', '月度占比', '接單次數', ''];
            $thead_btm = ['', '', '', '次數', '品號'];
            $spreadsheet->getActiveSheet()->mergeCells("A{$row_num}:A{$next_row_num}");  /* merge cell */
            $spreadsheet->getActiveSheet()->mergeCells("B{$row_num}:B{$next_row_num}");
            $spreadsheet->getActiveSheet()->mergeCells("C{$row_num}:C{$next_row_num}");
            $spreadsheet->getActiveSheet()->mergeCells("D{$row_num}:E{$row_num}");
            $spreadsheet->getActiveSheet()->fromArray($thead_top, NULL, "A{$row_num}");  /* write thead */
            $spreadsheet->getActiveSheet()->fromArray($thead_btm, NULL, "A{$next_row_num}");
            
            $row_num += 2;
            $next_row_num = $row_num + 1;

            /* tbody */
            foreach($values as $key => $value) {
                if($key % 3 == 0) {
                    $merge_tmp = $row_num + 2;
                    $spreadsheet->getActiveSheet()->mergeCells("A{$row_num}:A{$merge_tmp}");
                    $spreadsheet->getActiveSheet()->mergeCells("B{$row_num}:B{$merge_tmp}");
                    $tbody_row = [$value['月份'], $value['總計數'], $value['月度佔比'], $value['次數'], $value['品號']];
                    $spreadsheet->getActiveSheet()->fromArray($tbody_row, NULL, "A{$row_num}");
                }
                else {
                    $tbody_row = ['', '', $value['月度佔比'], $value['次數'], $value['品號']];
                    $spreadsheet->getActiveSheet()->fromArray($tbody_row, NULL, "A{$row_num}");
                }
                
                $row_num += 1;
                $next_row_num = $row_num + 1;
            }

            $thead_year = [''];
            $spreadsheet->getActiveSheet()->fromArray($thead_year, NULL, "A{$row_num}");  /* write thead */

            $row_num += 1;
            $next_row_num = $row_num + 1;
        }

        return $spreadsheet;
    }
        

    public function readProductItem($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        $sql = "SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號,
                    COPTD.TD003 訂單序號, CAST(COPTD.TD008 AS INT) 訂單數量, COPTC.TC008 交易幣別, CAST(COPTD.TD012 AS INT) 金額, COPTD.TD004 品號,
                    SUBSTRING(COPTD.TD004, 9, 2) \"品號切割9-10碼\", SUBSTRING(COPTD.TD004, 17, 1) 品號切割第17碼,
                    COPTD.TD005 品名, MOCTA.TA001 製令單別, MOCTA.TA002 製令單號, CAST(MOCTA.TA015 AS INT) 預計產量
                FROM MOCTA
                RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                WHERE COPTC.TC003 BETWEEN :date_begin AND :date_end
                    AND MOCTA.TA011 NOT IN ('y')
                    AND (
                        MOCTA.TA001 IS NULL
                            OR MOCTA.TA001 NOT IN ('5202','5205','5198','5199','5207')
                    )
                ORDER BY COPTD.TD003, COPTC.TC003, COPTC.TC004, COPTD.TD002
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readExportProductItem($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        $sql = "SELECT tmp.組合品名, SUM(tmp.金額) 訂單金額, 
                    COUNT(tmp.組合品名) 訂單筆數, SUM(tmp.訂單數量) 訂單數量
                FROM (
                    SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號,
                        COPTD.TD003 訂單序號, CAST(COPTD.TD008 AS INT) 訂單數量, COPTC.TC008 交易幣別, CAST(COPTD.TD012 AS INT) 金額, COPTD.TD004 品號,
                        SUBSTRING(COPTD.TD004, 9, 2) \"品號切割9-10碼\", SUBSTRING(COPTD.TD004, 17, 1) 品號切割第17碼,
                        MOCTA.TA001 製令單別, MOCTA.TA002 製令單號, CAST(MOCTA.TA015 AS INT) 預計產量, 
                        COPTD.TD005 品名, (SUBSTRING(COPTD.TD004, 9, 2) + '-' + COPTD.TD005) 組合品名
                    FROM MOCTA
                    RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                    INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                    WHERE COPTC.TC003 BETWEEN :date_begin AND :date_end
                        AND MOCTA.TA011 NOT IN ('y')
                        AND (
                            MOCTA.TA001 IS NULL
                                OR MOCTA.TA001 NOT IN ('5202','5205','5198','5199','5207')
                        )
                )tmp
                WHERE tmp.組合品名 IN ('00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', 
                    '05-嵌入件', '06-前沖棒', '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', 
                    '11-沖棒固定塊', '12-公牙', '13-夾子', '14-零件', '15-棘輪', '16-PIN', 
                    '17-通孔管', '18-其他')
                GROUP BY tmp.組合品名
                ORDER BY tmp.組合品名
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT SUM(tmp.金額) 訂單金額, COUNT(tmp.組合品名) 訂單筆數, SUM(tmp.訂單數量) 訂單數量
                FROM (
                    SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號,
                        COPTD.TD003 訂單序號, CAST(COPTD.TD008 AS INT) 訂單數量, COPTC.TC008 交易幣別, CAST(COPTD.TD012 AS INT) 金額, COPTD.TD004 品號,
                        SUBSTRING(COPTD.TD004, 9, 2) \"品號切割9-10碼\", SUBSTRING(COPTD.TD004, 17, 1) 品號切割第17碼,
                        MOCTA.TA001 製令單別, MOCTA.TA002 製令單號, CAST(MOCTA.TA015 AS INT) 預計產量, 
                        COPTD.TD005 品名, (SUBSTRING(COPTD.TD004, 9, 2) + '-' + COPTD.TD005) 組合品名
                    FROM MOCTA
                    RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                    INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                    WHERE COPTC.TC003 BETWEEN :date_begin AND :date_end
                        AND MOCTA.TA011 NOT IN ('y')
                        AND (
                            MOCTA.TA001 IS NULL
                                OR MOCTA.TA001 NOT IN ('5202','5205','5198','5199','5207')
                        )
                )tmp
                WHERE tmp.組合品名 IN ('00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', 
                    '05-嵌入件', '06-前沖棒', '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', 
                    '11-沖棒固定塊', '12-公牙', '13-夾子', '14-零件', '15-棘輪', '16-PIN', 
                    '17-通孔管', '18-其他')
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result['total'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readExportProductItemFixedCategory($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        $sql = "SELECT tmp.組合品名, SUM(tmp.金額) 訂單金額, 
                    COUNT(tmp.組合品名) 訂單筆數, SUM(tmp.訂單數量) 訂單數量
                FROM (
                    SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號,
                        COPTD.TD003 訂單序號, CAST(COPTD.TD008 AS INT) 訂單數量, COPTC.TC008 交易幣別, CAST(COPTD.TD012 AS INT) 金額, COPTD.TD004 品號,
                        SUBSTRING(COPTD.TD004, 9, 2) \"品號切割9-10碼\", SUBSTRING(COPTD.TD004, 17, 1) 品號切割第17碼,
                        COPTD.TD005 品名, MOCTA.TA001 製令單別, MOCTA.TA002 製令單號, CAST(MOCTA.TA015 AS INT) 預計產量, 
                        (SUBSTRING(COPTD.TD004, 9, 2) + '-' + COPTD.TD005) 組合品名
                    FROM MOCTA
                    RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                    INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                    WHERE COPTC.TC003 BETWEEN :date_begin AND :date_end
                        AND MOCTA.TA011 NOT IN ('y')
                        AND (
                            MOCTA.TA001 IS NULL
                                OR MOCTA.TA001 NOT IN ('5202','5205','5198','5199','5207')
                        )
                        AND SUBSTRING(COPTD.TD004, 17, 1) IN (4, 5)
                )tmp
                WHERE tmp.組合品名 IN ('00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', 
                    '05-嵌入件', '06-前沖棒', '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', 
                    '11-沖棒固定塊', '12-公牙', '13-夾子', '14-零件', '15-棘輪', '16-PIN', 
                    '17-通孔管', '18-其他')
                GROUP BY tmp.組合品名
                ORDER BY tmp.組合品名
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT SUM(tmp.金額) 訂單金額, COUNT(tmp.組合品名) 訂單筆數, SUM(tmp.訂單數量) 訂單數量
                FROM (
                    SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號,
                        COPTD.TD003 訂單序號, CAST(COPTD.TD008 AS INT) 訂單數量, COPTC.TC008 交易幣別, CAST(COPTD.TD012 AS INT) 金額, COPTD.TD004 品號,
                        SUBSTRING(COPTD.TD004, 9, 2) \"品號切割9-10碼\", SUBSTRING(COPTD.TD004, 17, 1) 品號切割第17碼,
                        COPTD.TD005 品名, MOCTA.TA001 製令單別, MOCTA.TA002 製令單號, CAST(MOCTA.TA015 AS INT) 預計產量, 
                        (SUBSTRING(COPTD.TD004, 9, 2) + '-' + COPTD.TD005) 組合品名
                    FROM MOCTA
                    RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                    INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                    WHERE COPTC.TC003 BETWEEN :date_begin AND :date_end
                        AND MOCTA.TA011 NOT IN ('y')
                        AND (
                            MOCTA.TA001 IS NULL
                                OR MOCTA.TA001 NOT IN ('5202','5205','5198','5199','5207')
                        )
                        AND SUBSTRING(COPTD.TD004, 17, 1) IN (4, 5)
                )tmp
                WHERE tmp.組合品名 IN ('00-業務', '01-切刀', '02-模組', '03-模仁', '04-模殼', 
                    '05-嵌入件', '06-前沖棒', '07-後沖棒', '08-通孔沖棒', '09-套管', '10-墊塊', 
                    '11-沖棒固定塊', '12-公牙', '13-夾子', '14-零件', '15-棘輪', '16-PIN', 
                    '17-通孔管', '18-其他')
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result['total'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function createExportProductItemFixedCategorySpreadsheet($db_data, $group)
    {
        $spreadsheet = new Spreadsheet();
        
        $first_row = 1;
        $second_row = 2;
        $third_row = 3;
        $fourth_row = 4;
        $fifth_row = 5;
        $sixth_row = 6;

        $thead_top = ['材料別:全部','','','材料別:4-整體碳化鎢、5-鎢+鋼模組','',''];
        $thead_bottom = ['訂單筆數','訂單數量','訂單金額(原幣)','訂單筆數','訂單數量','訂單金額(原幣)'];
        foreach ($db_data as $values) {
            $sum_date = [$values[0]['date']];
            $sum_num = [$values[0]['訂單筆數'],$values[0]['訂單數量'],$values[0]['訂單金額'],$values[0]['訂單筆數碳化鎢'],$values[0]['訂單數量碳化鎢'],$values[0]['訂單金額碳化鎢']];
            $sum_percent = ['','','',$values[1]['訂單筆數碳化鎢'],$values[1]['訂單數量碳化鎢'],$values[1]['訂單金額碳化鎢']];

            $spreadsheet->getActiveSheet()->fromArray($sum_date, NULL, "A{$first_row}");
            $spreadsheet->getActiveSheet()->fromArray($thead_top, NULL, "A{$second_row}");
            $spreadsheet->getActiveSheet()->fromArray($thead_bottom, NULL, "A{$third_row}");
            $spreadsheet->getActiveSheet()->fromArray($sum_num, NULL, "A{$fourth_row}");
            $spreadsheet->getActiveSheet()->fromArray($sum_percent, NULL, "A{$sixth_row}");
            
            $spreadsheet->getActiveSheet()->mergeCells("A{$first_row}:F{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("A{$second_row}:C{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("D{$second_row}:F{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("A{$fourth_row}:A{$fifth_row}");
            $spreadsheet->getActiveSheet()->mergeCells("B{$fourth_row}:B{$fifth_row}");
            $spreadsheet->getActiveSheet()->mergeCells("C{$fourth_row}:C{$fifth_row}");
            $spreadsheet->getActiveSheet()->mergeCells("D{$fourth_row}:D{$fifth_row}");
            $spreadsheet->getActiveSheet()->mergeCells("E{$fourth_row}:E{$fifth_row}");
            $spreadsheet->getActiveSheet()->mergeCells("F{$fourth_row}:F{$fifth_row}");
            
            $group_thead_top = [];
            $group_thead_botton_1 = [];
            $group_thead_botton_2 = [];
            $tbody_num = [];
            $tbody_percent = [];
            foreach($group as $key => $group_name) {
                array_push($group_thead_top, $group_name,'','','','','');
                array_push($group_thead_botton_1, '材料別:全部','','','材料別:4-整體碳化鎢、5-鎢+鋼模組','','');
                array_push($group_thead_botton_2, '訂單筆數','訂單數量','訂單金額(原幣)','訂單筆數','訂單數量','訂單金額(原幣)');
                array_push($tbody_num, $values[0]["訂單筆數{$key}"],$values[0]["訂單數量{$key}"],$values[0]["訂單金額{$key}"],$values[0]["訂單筆數碳化鎢{$key}"],$values[0]["訂單數量碳化鎢{$key}"],$values[0]["訂單金額碳化鎢{$key}"]);
                array_push($tbody_percent, $values[1]["訂單筆數{$key}"],$values[1]["訂單數量{$key}"],$values[1]["訂單金額{$key}"],$values[1]["訂單筆數碳化鎢{$key}"],$values[1]["訂單數量碳化鎢{$key}"],$values[1]["訂單金額碳化鎢{$key}"]);
            }
            
            $spreadsheet->getActiveSheet()->fromArray($group_thead_top, NULL, "G{$first_row}");
            $spreadsheet->getActiveSheet()->fromArray($group_thead_botton_1, NULL, "G{$second_row}");
            $spreadsheet->getActiveSheet()->fromArray($group_thead_botton_2, NULL, "G{$third_row}");
            $spreadsheet->getActiveSheet()->fromArray($tbody_num, NULL, "G{$fourth_row}");
            $spreadsheet->getActiveSheet()->fromArray($tbody_percent, NULL, "G{$fifth_row}");

            $spreadsheet->getActiveSheet()->mergeCells("G{$first_row}:L{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("M{$first_row}:R{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("S{$first_row}:X{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("Y{$first_row}:AD{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AE{$first_row}:AJ{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AK{$first_row}:AP{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AQ{$first_row}:AV{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AW{$first_row}:BB{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BC{$first_row}:BH{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BI{$first_row}:BN{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BO{$first_row}:BT{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BU{$first_row}:BZ{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CA{$first_row}:CF{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CG{$first_row}:CL{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CM{$first_row}:CR{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CS{$first_row}:CX{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CY{$first_row}:DD{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("DE{$first_row}:DJ{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("DK{$first_row}:DP{$first_row}");

            $spreadsheet->getActiveSheet()->mergeCells("G{$second_row}:I{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("J{$second_row}:L{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("M{$second_row}:O{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("P{$second_row}:R{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("S{$second_row}:U{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("V{$second_row}:X{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("Y{$second_row}:AA{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AB{$second_row}:AD{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AE{$second_row}:AG{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AH{$second_row}:AJ{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AK{$second_row}:AM{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AN{$second_row}:AP{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AQ{$second_row}:AS{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AT{$second_row}:AV{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AW{$second_row}:AY{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AZ{$second_row}:BB{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BC{$second_row}:BE{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BF{$second_row}:BH{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BI{$second_row}:BK{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BL{$second_row}:BN{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BO{$second_row}:BQ{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BR{$second_row}:BT{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BU{$second_row}:BW{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BX{$second_row}:BZ{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CA{$second_row}:CC{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CD{$second_row}:CF{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CG{$second_row}:CI{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CJ{$second_row}:CL{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CM{$second_row}:CO{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CP{$second_row}:CR{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CS{$second_row}:CU{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CV{$second_row}:CX{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("CY{$second_row}:DA{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("DB{$second_row}:DD{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("DE{$second_row}:DG{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("DH{$second_row}:DJ{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("DK{$second_row}:DM{$second_row}");
            $spreadsheet->getActiveSheet()->mergeCells("DN{$second_row}:DP{$second_row}");

            $first_row += 7;
            $second_row = $first_row + 1;
            $third_row = $first_row + 2;
            $fourth_row = $first_row + 3;
            $fifth_row = $first_row + 4;
            $sixth_row = $first_row + 5;
        }

        return $spreadsheet;
    }

    public function createExportProductItemSpreadsheet($db_data, $group)
    {
        $spreadsheet = new Spreadsheet();
        
        $first_row = 1;
        $second_row = 2;
        $third_row = 3;
        $fourth_row = 4;

        $thead = ['訂單筆數','訂單數量','訂單金額(原幣)'];
        foreach ($db_data as $values) {
            $sum_date = [$values[0]['date']];
            $sum_num = [$values[0]['訂單筆數'],$values[0]['訂單數量'],$values[0]['訂單金額']];

            $spreadsheet->getActiveSheet()->fromArray($sum_date, NULL, "A{$first_row}");
            $spreadsheet->getActiveSheet()->fromArray($thead, NULL, "A{$second_row}");
            $spreadsheet->getActiveSheet()->fromArray($sum_num, NULL, "A{$third_row}");
            
            $spreadsheet->getActiveSheet()->mergeCells("A{$first_row}:C{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("A{$third_row}:A{$fourth_row}");
            $spreadsheet->getActiveSheet()->mergeCells("B{$third_row}:B{$fourth_row}");
            $spreadsheet->getActiveSheet()->mergeCells("C{$third_row}:C{$fourth_row}");
            
            $group_thead_top = [];
            $group_thead_botton = [];
            $tbody_num = [];
            $tbody_percent = [];
            foreach($group as $key => $group_name) {
                array_push($group_thead_top, $group_name,'','');
                array_push($group_thead_botton, '訂單筆數','訂單數量','訂單金額(原幣)');
                array_push($tbody_num, $values[0]["訂單筆數{$key}"],$values[0]["訂單數量{$key}"],$values[0]["訂單金額{$key}"]);
                array_push($tbody_percent, $values[1]["訂單筆數{$key}"],$values[1]["訂單數量{$key}"],$values[1]["訂單金額{$key}"]);
            }
            
            $spreadsheet->getActiveSheet()->fromArray($group_thead_top, NULL, "D{$first_row}");
            $spreadsheet->getActiveSheet()->fromArray($group_thead_botton, NULL, "D{$second_row}");
            $spreadsheet->getActiveSheet()->fromArray($tbody_num, NULL, "D{$third_row}");
            $spreadsheet->getActiveSheet()->fromArray($tbody_percent, NULL, "D{$fourth_row}");

            $spreadsheet->getActiveSheet()->mergeCells("D{$first_row}:F{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("G{$first_row}:I{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("J{$first_row}:L{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("M{$first_row}:O{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("P{$first_row}:R{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("S{$first_row}:U{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("V{$first_row}:X{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("Y{$first_row}:AA{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AB{$first_row}:AD{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AE{$first_row}:AG{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AH{$first_row}:AJ{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AK{$first_row}:AM{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AN{$first_row}:AP{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AQ{$first_row}:AS{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AT{$first_row}:AV{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AW{$first_row}:AY{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("AZ{$first_row}:BB{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BC{$first_row}:BE{$first_row}");
            $spreadsheet->getActiveSheet()->mergeCells("BF{$first_row}:BH{$first_row}");

            $first_row += 5;
            $second_row = $first_row + 1;
            $third_row = $first_row + 2;
            $fourth_row = $first_row + 3;
        }

        return $spreadsheet;
    }

    public function readProductItemFixedCategory($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        $sql = "SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號,
                    COPTD.TD003 訂單序號, CAST(COPTD.TD008 AS INT) 訂單數量, COPTC.TC008 交易幣別, CAST(COPTD.TD012 AS INT) 金額, COPTD.TD004 品號,
                    SUBSTRING(COPTD.TD004, 9, 2) \"品號切割9-10碼\", SUBSTRING(COPTD.TD004, 17, 1) 品號切割第17碼,
                    COPTD.TD005 品名, MOCTA.TA001 製令單別, MOCTA.TA002 製令單號, CAST(MOCTA.TA015 AS INT) 預計產量
                FROM MOCTA
                RIGHT OUTER JOIN COPTD ON (COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028)
                INNER JOIN COPTC ON (COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002)
                WHERE COPTC.TC003 BETWEEN :date_begin AND :date_end
                    AND MOCTA.TA011 NOT IN ('y')
                    AND (
                        MOCTA.TA001 IS NULL
                            OR MOCTA.TA001 NOT IN ('5202','5205','5198','5199','5207')
                    )
                    AND SUBSTRING(COPTD.TD004, 17, 1) IN (4, 5)
                ORDER BY COPTD.TD003, COPTC.TC003, COPTD.TD004
        ";

        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readTurnoverChange($params)
    {
        $bind_values = [
            'start' => '',
            'end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        $sql = "SELECT COPTC.TC003 訂單日期, COPTC.TC004 客戶代號, COPMA.MA002 客戶簡稱, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號, COPTD.TD003 訂單序號,
                COPTD.TD004 品號, COPTD.TD201 客戶圖號, CAST(COPTD.TD008 AS INT) 訂單數量, 
                (CASE WHEN ((COPTD.TD012 - FLOOR(COPTD.TD012)) > 0) THEN CAST(COPTD.TD012 AS DECIMAL(18,1)) ELSE CAST(COPTD.TD012 AS INT) END) AS 金額, 
                COPTC.TC008 交易幣別
                FROM
                PURMA INNER JOIN MOCTA ON (MOCTA.TA032=PURMA.MA001)
                RIGHT OUTER JOIN COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
                INNER JOIN COPTC ON (COPTD.TD001=COPTC.TC001 and COPTD.TD002=COPTC.TC002)
                INNER JOIN COPMA ON (COPTC.TC004=COPMA.MA001)
                
                WHERE
                (
                    COPTC.TC003 BETWEEN '{$bind_values['start']}' AND '{$bind_values['end']}'
                    AND
                    COPTC.TC027 IN ('Y')
                )
        ";
        return $this->readCURLConnection($sql);
    }

    public function readOuterSecantComparison($params)
    {
        $sql = "SELECT SFCTB.TB015 單據日期, SFCTB.TB001 移轉單單別, SFCTB.TB002 移轉單單號, SFCTC.TC003 移轉單序號, SFCTB.TB005 移出部門,
                SFCTB.TB006 移出部門名稱, SFCTC.TC007 移出製程, CMSMW.MW002 製程名稱, SFCTC.TC004 製令單別, SFCTC.TC005 製令單號, 
                CAST(SFCTC.TC041 AS INT) 移轉數量, CAST(SFCTC.TC014 AS INT) 驗收數量, CAST(SFCTC.TC016 AS INT) 報廢數量, 
                CAST(SFCTC.TC037 AS INT) 不良數量, CAST(SFCTC.TC042 AS INT) 驗退數量
                FROM
                SFCTB INNER JOIN SFCTC ON (SFCTB.TB001=SFCTC.TC001 and SFCTB.TB002=SFCTC.TC002)
                INNER JOIN CMSMW ON (SFCTC.TC007=CMSMW.MW001)
                WHERE
                (
                    SFCTB.TB013 IN ('Y')
                    AND
                    SFCTB.TB015 BETWEEN :date_begin AND :date_end
                    AND
                    SFCTB.TB005 IN ('A')
                    AND
                    SFCTC.TC001 IN ('4220','4221','4222','4311')
                    AND
                    SFCTC.TC013 NOT IN ('5','6')
                )
                ORDER BY SFCTB.TB015 ASC
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->bindValue(':date_begin', $params['date_begin'], PDO::PARAM_INT);
        $stmt->bindValue(':date_end', $params['date_end'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readOrderCurrencyStatistics($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }

        $sql = "SELECT COPTC.TC003 訂單日期, COPTD.TD001 訂單單別, COPTD.TD002 訂單單號, COPTD.TD003 訂單序號,
                    COPTC.TC004 客戶代號, COPTD.TD004 品號, COPTD.TD201 客戶圖號, CAST(COPTD.TD008 AS INT) 訂單數量 ,  
                    COPMA.MA002 客戶簡稱, 
                    (CASE WHEN ((COPTD.TD011 - FLOOR(COPTD.TD011)) > 0) THEN CAST(COPTD.TD011 AS DECIMAL(18,1)) ELSE CAST(COPTD.TD011 AS INT) END) AS 訂單單價, 
                    (CASE WHEN ((COPTD.TD012 - FLOOR(COPTD.TD012)) > 0) THEN CAST(COPTD.TD012 AS DECIMAL(18,1)) ELSE CAST(COPTD.TD012 AS INT) END) AS 訂單金額, 
                    COPTC.TC008 交易幣別
                FROM
                    PURMA INNER JOIN MOCTA ON (MOCTA.TA032=PURMA.MA001)
                    RIGHT OUTER JOIN COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
                    INNER JOIN COPTC ON (COPTD.TD001=COPTC.TC001 and COPTD.TD002=COPTC.TC002)
                    INNER JOIN COPMA ON (COPTC.TC004=COPMA.MA001)
                    
                WHERE
                (
                    COPTC.TC003 BETWEEN '{$bind_values['date_begin']}' AND '{$bind_values['date_end']}'
                    AND
                    COPTC.TC027 IN ('Y')
                )
                ORDER BY COPTC.TC003, COPTD.TD001, COPTD.TD002, COPTD.TD003
        ";
        return $this->readCURLConnection($sql);
    }

    public function readBusinessInventory($params)
    {
        $bind_values = [
            'start' => '',
            'end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        $sql = "SELECT COPTC.TC004 客戶代號, [COPTA].[TA003] as 報價日期, CAST([COPTD].[TD008] AS DECIMAL(18,0)) as 報價數量, 
                [COPTA].[TA028] as 單位, CAST([COPTD].[TD011] AS DECIMAL(18,2)) as 報價單價, [COPTA].[TA002] as 報價單號, PURMA.MA002 外注商, 
                外注單價, 業務專用備註, COPTD.TD020 訂單備註事項, COPTD.TD002 訂單單號, COPTD.TD201 客戶圖號, COPTC.TC012 客戶單號, 
                COPTB.TB205 材質名稱, COPTB.TD206 硬度, [COPTB].[TB211] as 報價單圖面版次, [COPTD].[TD214] as 訂單圖面版次, COPTB.TB204 鍍鈦名稱, 
                [COPTD].[TD006] as 規格, COPTD.TD005 品名, CAST([COPTD].[TD008] AS DECIMAL(18,0)) as 訂單數量, 訂單已交數, 
                CAST([COPTD].[TD011] AS DECIMAL(18,2)) as 訂單單價, CAST([COPTD].[TD012] AS DECIMAL(18,2)) as 訂單金額, 工序名稱, [COPTB].[TB204] as 鍍鈦種類, 
                COPTC.TC015 訂單單頭備註, COPMA.MA002 客戶簡稱, COPTD.TD020 訂單單身備註, COPTC.TC008 交易幣別, 預定納期, 代理訂單號碼, MOCTA.TA002 製令單號, 
                COPTB.TB007 製造數量, 驗收日期, 入庫數量, 加印文字內容, [COPTD].[TD214] as 圖號序號, 印LOGO, 附圖, 採購單別-單號-序號, 品號, 品質注意事項, 客戶原始圖號, 
                品號運輸方式名稱, 目前工序, 銷貨數量, 銷貨單價, 銷貨金額, Invoice No, 銷貨日期, 大提單號, 小提單號, 銷貨單別-單號-序號, 出貨通知單單號, 單據日期, 數量, 分批交期,
                報價金額
        FROM
            PURMA INNER JOIN MOCTA ON (MOCTA.TA032=PURMA.MA001)
            RIGHT OUTER JOIN COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
            INNER JOIN COPTC ON (COPTD.TD001=COPTC.TC001 and COPTD.TD002=COPTC.TC002)
            INNER JOIN COPMA ON (COPTC.TC004=COPMA.MA001)
            
        WHERE
        (
            COPTC.TC003 BETWEEN '{$bind_values['start']}' AND '{$bind_values['end']}'
            AND
            COPTC.TC027 IN ('Y')
        )
        ORDER BY COPTC.TC003, COPTD.TD001, COPTD.TD002, COPTD.TD003
        ";
        return $this->readCURLConnection($sql);
    }

    public function readAddDelivery($params)
    {
        $bind_values = [
            'start' => '',
            'end' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
        }
        $sql = "SELECT DATEPART(WK, \"預計生產完成日\")-1 AS \"週數\",\"預計生產完成日\",\"盤數\",\"訂單數量\",COALESCE(\"現場完成量\",0) \"現場完成量\",COALESCE(\"外注完成量\",0) \"外注完成量\"
                FROM(
                    SELECT 
                        \"預計生產完成日\",
                        COUNT(*) \"盤數\",
                        SUM(\"訂單數量\") \"訂單數量\"
                    FROM(
                        SELECT
                        COPTD.TD215 \"預計生產完成日\",
                        SUM(COPTD.TD008) \"訂單數量\"
                        FROM
                        MIL.dbo.COPTC 
                        INNER JOIN MIL.dbo.COPTD ON (COPTC.TC001=COPTD.TD001 and COPTC.TC002=COPTD.TD002)
                        WHERE
                        (
                        COPTD.TD215  BETWEEN {$bind_values['start']}  AND {$bind_values['end']})
                        AND
                        COPTD.TD001  NOT IN  ( '2230','2240','2250','2270'  )
                        AND
                        COPTD.TD016  IN  ( 'N'  )
                        GROUP BY COPTD.TD215,COPTD.TD001 ,COPTD.TD002,COPTD.TD003
                    ) a
                    GROUP BY \"預計生產完成日\"
                )a
                LEFT JOIN (
                    SELECT XD001 TA014,
                        SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5201' OR SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)='5206' THEN XD017 ELSE 0 END) \"外注完成量\",
                        SUM(CASE WHEN SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5201' AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)!='5206' THEN XD017 ELSE 0 END) \"現場完成量\"
                    FROM MIL.dbo.MOCXD
                    WHERE (MOCXD.XD001 BETWEEN {$bind_values['start']} AND {$bind_values['end']}) AND SUBSTRING(XD004,1, CHARINDEX('-', XD004) - 1)  NOT IN  ( '5202','5205','5207'  )
                    GROUP BY MOCXD.XD001
                )b ON a.\"預計生產完成日\" = b.TA014
                
                ORDER BY \"預計生產完成日\" ASC
        ";
        return $this->readCURLConnection($sql);
    }

    public function createSpreadsheet($db_data)
    {
        $spreadsheet = new Spreadsheet();
        $thead = array_keys($db_data[0]);
        $spreadsheet->getActiveSheet()->fromArray($thead, NULL, 'A1');
        foreach ($db_data as $key => $value) {
            $row = $key + 2;
            $tbody_row = [];
            foreach ($value as $key2 => $value2) {
                $tbody_row[] = $value2;
            }
            $spreadsheet->getActiveSheet()->fromArray($tbody_row, NULL, "A{$row}");
        }
        return $spreadsheet;
    }

    public function createOrderProductCategorySpreadsheet($db_data)
    {
        $spreadsheet = new Spreadsheet();
        $thead = array_keys($db_data[0]);
        $spreadsheet->getActiveSheet()->fromArray($thead, NULL, 'A1');
        foreach ($db_data as $key => $value) {
            $row = $key + 2;
            $spreadsheet->getActiveSheet()->fromArray($value, NULL, "A{$row}");
        }
        return $spreadsheet;
    }

    public function readExportItemCategoryAnalysis($request_year)
    {
        /* year total */
        $sql = "SELECT COUNT(*) count, CAST(SUM(COPTD.TD008) AS INT) sum, CAST(SUM(MOCTA.TA015) AS INT) predict_sum
                FROM MOCTA
                RIGHT OUTER JOIN COPTD ON COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028
                INNER JOIN COPTC ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
                WHERE SUBSTRING(COPTC.TC003, 1, 4) = :request_year
                    AND MOCTA.TA011 NOT IN ('y')
                    AND (
                        MOCTA.TA001 IS NULL
                            OR MOCTA.TA001 NOT IN ('5202', '5205', '5198', '5199', '5207')
                    )
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->bindValue(':request_year', $request_year, PDO::PARAM_INT);
        $stmt->execute();
        $year_total = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

        $sql = "SELECT COUNT(tmp.discs)
                FROM(
                    SELECT DISTINCT (COPTD.TD001 + COPTD.TD002 + COPTD.TD003) discs
                    FROM MOCTA
                    RIGHT OUTER JOIN COPTD ON COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028
                    INNER JOIN COPTC ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
                    WHERE SUBSTRING(COPTC.TC003, 1, 4) = :request_year
                        AND MOCTA.TA011 NOT IN ('y')
                        AND (
                            MOCTA.TA001 IS NULL
                                OR MOCTA.TA001 NOT IN ('5202', '5205', '5198', '5199', '5207')
                            )
                )tmp
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->bindValue(':request_year', $request_year, PDO::PARAM_INT);
        $stmt->execute();
        $year_total['discs_count'] = $stmt->fetchColumn(0);

        /* each item */
        $sql = "SELECT COUNT(*) count, CAST(SUM(COPTD.TD008) AS INT) sum,
                    CAST(ROUND((COUNT(*) * 100 / CAST(:year_total_count AS FLOAT)), 2) AS VARCHAR) + '%' count_percent,
                    CAST(ROUND((SUM(COPTD.TD008) * 100 / CAST(:year_total_sum AS FLOAT)), 2) AS VARCHAR) + '%' sum_percent
                FROM MOCTA
                RIGHT OUTER JOIN COPTD ON COPTD.TD001 = MOCTA.TA026 AND COPTD.TD002 = MOCTA.TA027 AND COPTD.TD003 = MOCTA.TA028
                INNER JOIN COPTC ON COPTD.TD001 = COPTC.TC001 AND COPTD.TD002 = COPTC.TC002
                WHERE SUBSTRING(COPTC.TC003, 1, 4) = :request_year
                    AND MOCTA.TA011 NOT IN ('y')
                    AND (
                        MOCTA.TA001 IS NULL
                            OR MOCTA.TA001 NOT IN ('5202', '5205', '5198', '5199', '5207')
                    )
                    AND COPTD.TD005 IN ('模組', '模仁', '模殼')
                GROUP BY COPTD.TD005
                ORDER BY COPTD.TD005
        ";

        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->bindValue(':year_total_count', $year_total['count'], PDO::PARAM_INT);
        $stmt->bindValue(':year_total_sum', $year_total['sum'], PDO::PARAM_INT);
        $stmt->bindValue(':request_year', $request_year, PDO::PARAM_INT);
        $stmt->execute();
        $each_item = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'year_total' => $year_total,
            'each_item' => $each_item
        ];
    }

    public function get_report_type()
    {
        $sql = "SELECT report.report_type.id, report.report_type.name
                FROM report.report_type
            ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function get_report($data)
    {
        $condition = "";
        if (array_key_exists('type_id', $data)) {
            $condition = "WHERE report.report_type.id = :type_id";
        }

        $sql = "SELECT report.report.id, report.report_type.id report_type_id, report.report_type.name report_type_name, report.report.name, report.report.url
                FROM report.report
                LEFT JOIN report.report_type ON report.report.type_id = report.report_type.id
                $condition
            ";
        $stmt = $this->db->prepare($sql);

        if (array_key_exists('type_id', $data)) {
            $stmt->bindValue(':type_id', $data['type_id'], PDO::PARAM_INT);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
    public function convertSpreadsheetColumnCharacter($col_num)
    {
        $remainder = ($col_num - 1) % 26;
        $character = chr(65 + $remainder);
        $quotient = intval(($col_num - 1) / 26);
        if ($quotient > 0) {
            return $this->convertSpreadsheetColumnCharacter($quotient) . $character;
        } else {
            return $character;
        }
    }

    public function createExportStaffProductivitySpreadsheet($db_data)
    {
        $spreadsheet = new Spreadsheet();
        /* thead */
        foreach ($db_data[0] as $key => $value) {   /* splice thead data */
            $dates[] = $value['date'];
            $weekdays[] = '(' . $value['weekdays'] . ')';
        }
        $thead_top = ['工號', '姓名', '線別', '日期', '總和', '天數', '平均值'];
        $thead_btm = ['', '', '', '星期', '', '', ''];
        array_splice($thead_top, 4, 0, $dates);
        array_splice($thead_btm, 4, 0, $weekdays);
        $spreadsheet->getActiveSheet()->mergeCells("A1:A2");  /* merge cell */
        $spreadsheet->getActiveSheet()->mergeCells("B1:B2");
        $spreadsheet->getActiveSheet()->mergeCells("C1:C2");
        for ($i = 1; $i <= 3; $i++) {
            $col_char = $this->convertSpreadsheetColumnCharacter(4 + sizeof($db_data[0]) + $i);  /* D + length + i */
            $spreadsheet->getActiveSheet()->mergeCells("{$col_char}1:{$col_char}2");
        }
        $spreadsheet->getActiveSheet()->fromArray($thead_top, NULL, 'A1');  /* write thead */
        $spreadsheet->getActiveSheet()->fromArray($thead_btm, NULL, 'A2');
        /* tbody */
        foreach ($db_data[1] as $key => $value) {
            $row_num = 3 + $key;
            $next_row_num = 3 + $key + 1;
            if ($key % 2 === 0) {  /* merge cell */
                $spreadsheet->getActiveSheet()->mergeCells("A{$row_num}:A{$next_row_num}");
                $spreadsheet->getActiveSheet()->mergeCells("B{$row_num}:B{$next_row_num}");
                $spreadsheet->getActiveSheet()->mergeCells("C{$row_num}:C{$next_row_num}");
            }
            $type_data = array_fill(0, sizeof($db_data[0]), '');
            foreach ($value as $key2 => $value2) {
                if (!(ctype_alpha($key2))) {  /* non alphabet keys */
                    $type_data[$key2 - 1] = $value2;
                }
                $tbody_row = [$value['uid'], $value['name'], $value['line'], $value['type'], $value['total'], $value['days'], $value['avg']];
                array_splice($tbody_row, 4, 0, $type_data);
                $spreadsheet->getActiveSheet()->fromArray($tbody_row, NULL, "A{$row_num}");
            }
        }
        return $spreadsheet;
    }

    public function createExportOrderCurrencyStatisticsSpreadsheet($db_data, $year_list)
    {
        $spreadsheet = new Spreadsheet();
        foreach ($db_data as $key => $value) {
            $year_month_list = [];
            foreach ($year_list as $key2 => $value2) {
                $year_month_list[] = $value2 . '/' . $value['key'];
            }
            /* thead */
            $thead = ['幣別', '同期相比%'];
            array_splice($thead, 1, 0, $year_month_list);
            $thead_row_num = 1 + (8 * $key);
            $spreadsheet->getActiveSheet()->fromArray($thead, NULL, "A{$thead_row_num}");
            /* tbody */
            foreach ($value['expandedData'] as $key2 => $value2) {
                $month_data = array_fill(0, sizeof($year_month_list), '');
                foreach ($value2 as $key3 => $value3) {
                    if (!(ctype_alpha($key3))) {  /* non alphabet keys */
                        $month_data[$key3 - 1] = $value3;
                    }
                }
                $tbody_row = [$value2['type'], $value2['compare']];
                array_splice($tbody_row, 1, 0, $month_data);
                $tbody_row_num = $thead_row_num + 1 + $key2;
                $spreadsheet->getActiveSheet()->fromArray($tbody_row, NULL, "A{$tbody_row_num}");
            }
        }
        return $spreadsheet;
    }

    public function createExportItemCategoryAnalysisSpreadsheet($db_data, $year_list)
    {
        $spreadsheet = new Spreadsheet();

        foreach ($db_data as $key => $value) {
            /* thead */
            $thead_top = ["{$year_list[$key]}/01/01 ~ {$year_list[$key]}/12/31", '', '', '', '02-模組', '', '03-模仁', '', '04-模殼', ''];
            $thead_btm = ['訂單計數', '訂單數量', '製令盤數', '預計產量', '訂單計數', '訂單數量', '訂單計數', '訂單數量', '訂單計數', '訂單數量'];
            $thead_top_row_num = $key * 5 + 1;  /* row num */
            $thead_btm_row_num = $thead_top_row_num + 1;
            $spreadsheet->getActiveSheet()->mergeCells("A{$thead_top_row_num}:D{$thead_top_row_num}");
            $spreadsheet->getActiveSheet()->mergeCells("E{$thead_top_row_num}:F{$thead_top_row_num}");
            $spreadsheet->getActiveSheet()->mergeCells("G{$thead_top_row_num}:H{$thead_top_row_num}");
            $spreadsheet->getActiveSheet()->mergeCells("I{$thead_top_row_num}:J{$thead_top_row_num}");
            $spreadsheet->getActiveSheet()->fromArray($thead_top, NULL, "A{$thead_top_row_num}");
            $spreadsheet->getActiveSheet()->fromArray($thead_btm, NULL, "A{$thead_btm_row_num}");
            /* tbody */
            $tbody_row_normal = [
                $value[0]['訂單計數'], $value[0]['訂單數量'], $value[0]['製令盤數'], $value[0]['預計產量'],
                $value[0]['訂單計數1'], $value[0]['訂單數量1'], $value[0]['訂單計數2'], $value[0]['訂單數量2'], $value[0]['訂單計數3'], $value[0]['訂單數量3']
            ];
            $tbody_row_percentage = [
                $value[1]['訂單計數'], $value[1]['訂單數量'], $value[0]['製令盤數'], $value[1]['預計產量'],
                $value[1]['訂單計數1'], $value[1]['訂單數量1'], $value[1]['訂單計數2'], $value[1]['訂單數量2'], $value[1]['訂單計數3'], $value[1]['訂單數量3']
            ];
            $tbody_normal_row_num = $thead_btm_row_num + 1;  /* row num */
            $tbody_percentage_row_num = $tbody_normal_row_num + 1;
            $spreadsheet->getActiveSheet()->mergeCells("A{$tbody_normal_row_num}:A{$tbody_percentage_row_num}");
            $spreadsheet->getActiveSheet()->mergeCells("B{$tbody_normal_row_num}:B{$tbody_percentage_row_num}");
            $spreadsheet->getActiveSheet()->mergeCells("C{$tbody_normal_row_num}:C{$tbody_percentage_row_num}");
            $spreadsheet->getActiveSheet()->mergeCells("D{$tbody_normal_row_num}:D{$tbody_percentage_row_num}");
            $spreadsheet->getActiveSheet()->fromArray($tbody_row_normal, NULL, "A{$tbody_normal_row_num}");
            $spreadsheet->getActiveSheet()->fromArray($tbody_row_percentage, NULL, "A{$tbody_percentage_row_num}");
        }
        return $spreadsheet;
    }

    public function readStaffProductivity($params)
    {
        $bind_values = [
            'date_begin' => '',
            'date_end' => '',
            'uid' => '',
            'line' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
            else {
                unset($bind_values[$key]);
            }
        }

        $line_condition = "";
        $uid_condition = "";

        if (isset($bind_values['uid'])) {
            $uid_condition = "AND SFCTC.CREATOR = :uid";
        }

        if (isset($bind_values['line']))  {
            $line_condition = "AND SFCTB.TB005 = :line";
        }

        $sql = "SELECT CMSMV3.MV001 工號, CMSMV3.MV002 姓名, SFCTB.TB015 單據日期, SFCTB.TB002 移轉單單號,
                    SFCTB.TB001 移轉單單別, SFCTB.TB005 移出部門, SFCTB.TB006 移出部門名稱, CAST(SFCTC.TC041 AS INT) 移轉數量,
                    CAST(SFCTC.TC014 AS INT) 驗收數量, CAST(SFCTC.TC037 AS INT) 不良數量, CAST(SFCTC.TC016 AS INT) 報廢數量,
                    ROW_NUMBER() OVER (ORDER BY SFCTB.TB015) AS row_num
                FROM SFCTB
                INNER JOIN SFCTC ON (SFCTB.TB001 = SFCTC.TC001 AND SFCTB.TB002 = SFCTC.TC002)
                RIGHT OUTER JOIN MOCTA ON (
                    MOCTA.TA001 = SFCTC.TC004
                        AND MOCTA.TA002 = SFCTC.TC005
                        AND SFCTC.TC004 = MOCTA.TA001
                        AND SFCTC.TC005 = MOCTA.TA002
                )
                INNER JOIN CMSMV CMSMV3 ON (SFCTC.CREATOR = CMSMV3.MV001)
                WHERE SFCTB.TB013 IN ('Y')
                    AND SFCTB.TB015 BETWEEN :date_begin AND :date_end
                    AND SFCTC.TC013 NOT IN ('5', '6') {$uid_condition} {$line_condition}
                ORDER BY SFCTB.TB015
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readStaffProductivityCount($params) {
        $bind_values = [
            'date_begin' => '',
            'date_end' => '',
            'uid' => '',
            'line' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
            else {
                unset($bind_values[$key]);
            }
        }

        $line_condition = "";
        $uid_condition = "";

        if (isset($bind_values['uid'])) {
            $uid_condition = "AND SFCTC.CREATOR = :uid";
        }

        if (isset($bind_values['line']))  {
            $line_condition = "AND SFCTB.TB005 = :line";
        }


        $sql = "SELECT COUNT(*) total_num
                FROM SFCTB
                INNER JOIN SFCTC ON (SFCTB.TB001 = SFCTC.TC001 AND SFCTB.TB002 = SFCTC.TC002)
                RIGHT OUTER JOIN MOCTA ON (
                    MOCTA.TA001 = SFCTC.TC004
                        AND MOCTA.TA002 = SFCTC.TC005
                        AND SFCTC.TC004 = MOCTA.TA001
                        AND SFCTC.TC005 = MOCTA.TA002
                )
                INNER JOIN CMSMV CMSMV3 ON (SFCTC.CREATOR = CMSMV3.MV001)
                WHERE SFCTB.TB013 IN ('Y')
                    AND SFCTB.TB015 BETWEEN :date_begin AND :date_end
                    AND SFCTC.TC013 NOT IN ('5', '6') {$uid_condition} {$line_condition}
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readAllStaffProductivityUser($params) {
        $bind_values = [
            'line' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
            else {
                unset($bind_values[$key]);
            }
        }

        $line_condition = "";

        if (isset($bind_values['line']))  {
            $line_condition = "AND SFCTB.TB005 = :line";
        }

        $sql = "SELECT * 
                FROM ( 
                    SELECT TOP 10000000 CMSMV3.MV001 工號, CMSMV3.MV002 姓名
                    FROM SFCTB
                    INNER JOIN SFCTC ON (SFCTB.TB001 = SFCTC.TC001 AND SFCTB.TB002 = SFCTC.TC002)
                    RIGHT OUTER JOIN MOCTA ON (
                        MOCTA.TA001 = SFCTC.TC004
                            AND MOCTA.TA002 = SFCTC.TC005
                            AND SFCTC.TC004 = MOCTA.TA001
                            AND SFCTC.TC005 = MOCTA.TA002
                    )
                    INNER JOIN CMSMV CMSMV3 ON (SFCTC.CREATOR = CMSMV3.MV001)
                    WHERE SFCTB.TB013 IN ('Y')
                        AND SFCTC.TC013 NOT IN ('5', '6') {$line_condition}
                    ORDER BY SFCTB.TB015 DESC
                )tmp
                GROUP BY tmp.工號, tmp.姓名
        ";

        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function readAllStaffProductivity($params) {
        $bind_values = [
            'date_begin' => '',
            'date_end' => '',
            'uid' => '',
            'line' => ''
        ];
        foreach ($bind_values as $key => $value) {
            if (array_key_exists($key, $params)) {
                $bind_values[$key] = $params[$key];
            }
            else {
                unset($bind_values[$key]);
            }
        }

        $line_condition = "";
        $uid_condition = "";

        if (isset($bind_values['uid'])) {
            $uid_condition = "AND SFCTC.CREATOR = :uid";
        }

        if (isset($bind_values['line']))  {
            $line_condition = "AND SFCTB.TB005 = :line";
        }

        $sql = "SELECT CMSMV3.MV001 工號, CMSMV3.MV002 姓名, SFCTB.TB015 單據日期, SFCTB.TB002 移轉單單號,
                    SFCTB.TB001 移轉單單別, SFCTB.TB005 移出部門, SFCTB.TB006 移出部門名稱, CAST(SFCTC.TC041 AS INT) 移轉數量,
                    CAST(SFCTC.TC014 AS INT) 驗收數量, CAST(SFCTC.TC037 AS INT) 不良數量, CAST(SFCTC.TC016 AS INT) 報廢數量
                FROM SFCTB
                INNER JOIN SFCTC ON (SFCTB.TB001 = SFCTC.TC001 AND SFCTB.TB002 = SFCTC.TC002)
                RIGHT OUTER JOIN MOCTA ON (
                    MOCTA.TA001 = SFCTC.TC004
                        AND MOCTA.TA002 = SFCTC.TC005
                        AND SFCTC.TC004 = MOCTA.TA001
                        AND SFCTC.TC005 = MOCTA.TA002
                )
                INNER JOIN CMSMV CMSMV3 ON (SFCTC.CREATOR = CMSMV3.MV001)
                WHERE SFCTB.TB013 IN ('Y')
                    AND SFCTB.TB015 BETWEEN :date_begin AND :date_end
                    AND SFCTC.TC013 NOT IN ('5', '6') {$uid_condition} {$line_condition}
                ORDER BY SFCTB.TB015, CMSMV3.MV001
        ";
        
        $stmt = $this->db_sqlsrv->prepare($sql);
        $stmt->execute($bind_values);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function get_order_processes_100 ($data){
        $sql = "SELECT *
            FROM(
                SELECT TOP 1000
                    COPTD.TD001,COPTD.TD002,COPTD.TD003,COPTD.TD004,COPTD.TD005,
                    STUFF((
                        SELECT SFCTA.TA003 [製程順序],[CMSMW].[MW002] [製程名稱]
                        FROM [MIL].[dbo].[MOCTA]
                        LEFT JOIN [MIL].[dbo].SFCTA ON SFCTA.TA001 = MOCTA.TA001 AND SFCTA.TA002 = MOCTA.TA002
                        LEFT JOIN MIL.dbo.CMSMW ON CMSMW.MW001 = SFCTA.TA004
                        WHERE CMSMW.MW002 IS NOT NULL AND MOCTA.TA026 = COPTD.TD001 AND MOCTA.TA027 = COPTD.TD002 AND MOCTA.TA028 = COPTD.TD003 AND MOCTA.TA001 NOT IN ('5202', '5205', '5198', '5199', '5207')
                        ORDER BY SFCTA.TA003 ASC
                    FOR XML PATH),1,0,''
                    )processes
                FROM [MIL].[dbo].[COPTD]
                WHERE TD005 = '模仁' AND COPTD.TD002 LIKE '11105_____' AND (COPTD.TD004 LIKE '_101003003015003____' OR COPTD.TD004 LIKE '_101084003018001____' OR COPTD.TD004 LIKE '_203007003015001____' OR COPTD.TD004 LIKE '_101005003009003____' OR COPTD.TD004 LIKE '_102001003013017____' OR COPTD.TD004 LIKE '_203007003022002____' OR COPTD.TD004 LIKE '_101005003009004____' OR COPTD.TD004 LIKE '_102001003015051____' OR COPTD.TD004 LIKE '_204003103020010____' OR COPTD.TD004 LIKE '_101009003022006____' OR COPTD.TD004 LIKE '_102001003018021____' OR COPTD.TD004 LIKE '_205002003018018____' OR COPTD.TD004 LIKE '_101009003022007____' OR COPTD.TD004 LIKE '_102001003020216____' OR COPTD.TD004 LIKE '_205002003018020____' OR COPTD.TD004 LIKE '_101009003022008____' OR COPTD.TD004 LIKE '_102001003022050____' OR COPTD.TD004 LIKE '_205002003025012____' OR COPTD.TD004 LIKE '_101009003022009____' OR COPTD.TD004 LIKE '_102001003025110____' OR COPTD.TD004 LIKE '_208001003025052____' OR COPTD.TD004 LIKE '_101009003031011____' OR COPTD.TD004 LIKE '_102001003025111____' OR COPTD.TD004 LIKE '_208001003031140____' OR COPTD.TD004 LIKE '_101016003028008____' OR COPTD.TD004 LIKE '_102001003025112____' OR COPTD.TD004 LIKE '_208001003038075____' OR COPTD.TD004 LIKE '_101018003019022____' OR COPTD.TD004 LIKE '_102001003027005____' OR COPTD.TD004 LIKE '_208001003050074____' OR COPTD.TD004 LIKE '_101018003038051____' OR COPTD.TD004 LIKE '_102001003027012____' OR COPTD.TD004 LIKE '_208001003053013____' OR COPTD.TD004 LIKE '_101021003014001____' OR COPTD.TD004 LIKE '_102001003030171____' OR COPTD.TD004 LIKE '_208001003055055____' OR COPTD.TD004 LIKE '_101027003020029____' OR COPTD.TD004 LIKE '_102001003032002____' OR COPTD.TD004 LIKE '_208001003055151____' OR COPTD.TD004 LIKE '_101038003019011____' OR COPTD.TD004 LIKE '_102001003035005____' OR COPTD.TD004 LIKE '_208001003112005____' OR COPTD.TD004 LIKE '_101042003014004____' OR COPTD.TD004 LIKE '_102001003035057____' OR COPTD.TD004 LIKE '_208004003020023____' OR COPTD.TD004 LIKE '_101061003044002____' OR COPTD.TD004 LIKE '_102001003035110____' OR COPTD.TD004 LIKE '_208014003011004____' OR COPTD.TD004 LIKE '_101071003012012____' OR COPTD.TD004 LIKE '_102001003035111____' OR COPTD.TD004 LIKE '_208014003027004____' OR COPTD.TD004 LIKE '_101071003012016____' OR COPTD.TD004 LIKE '_102001003036006____' OR COPTD.TD004 LIKE '_208014003030007____' OR COPTD.TD004 LIKE '_101071003012019____' OR COPTD.TD004 LIKE '_102001003037002____' OR COPTD.TD004 LIKE '_208026003044020____' OR COPTD.TD004 LIKE '_101071003012023____' OR COPTD.TD004 LIKE '_102001003040041____' OR COPTD.TD004 LIKE '_208026003065005____' OR COPTD.TD004 LIKE '_101073003031018____' OR COPTD.TD004 LIKE '_201003003031019____' OR COPTD.TD004 LIKE '_210001003035006____' OR COPTD.TD004 LIKE '_101073003079002____' OR COPTD.TD004 LIKE '_201018003088001____' OR COPTD.TD004 LIKE '_101084003010001____' OR COPTD.TD004 LIKE '_203000003040008')
                ORDER BY COPTD.TD002 DESC
            )dt
            WHERE processes IS NOT NULL
            ORDER BY TD004
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute()){
            return ["status"=>"failure"];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $key_result => $value) {
            $tmpvalue = $value['processes'];
            $tmpArrs = [];
            $xml = simplexml_load_string("<a>$tmpvalue</a>");
            if ($tmpvalue == "") {
                $result[$key_result]['processes'] = $tmpArrs;
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
            $result[$key_result]['processes'] = $tmpArrs;
            Endquotation:
        }
        return $result;
    }
    public function createExportTurnoverChangeSpreadsheet($db_data, $params)
    {
        $spreadsheet = new Spreadsheet();
        $tmp_arr = [];
        $now = $params['start'];
        foreach ($db_data as $key => $value) {
            array_push($tmp_arr, [$now, '', '', '', '']);
            array_push($tmp_arr, ['幣別', '第一季', '第二季', '第三季', '第四季']);
            array_push($tmp_arr, ['', '01-03', '04-06', '07-09', '10-12']);
            foreach ($value as $key_2 => $value_2) {
                array_push($tmp_arr, [$value_2['currency'], $value_2['Q1'], $value_2['Q2'], $value_2['Q3'], $value_2['Q4']]);
            }
            $now++;
        }
        $spreadsheet->getActiveSheet()->fromArray($tmp_arr, NULL, "A1");
        return $spreadsheet;
    }

    public function createExportOuterSecantComparisonSpreadsheet($db_data, $params)
    {
        $spreadsheet = new Spreadsheet();
        $tmp_arr = [];
        $now = $params['start'];
        $tmp_date = [''];
        foreach($db_data[0] as $key => $value){
            array_push($tmp_date, $value);
        }
        $date_count = count($tmp_date) - 1;
        array_push($tmp_date, "近{$date_count}月平均量");
        foreach($db_data[1] as $key => $value){
            array_push($tmp_arr, [$value[0]['company']]);
            array_push($tmp_arr, $tmp_date);
            foreach ($value as $key_ => $value_) {
                $tmp_data = [];
                array_push($tmp_data, $value_['type']);
                for($j=1; $j <= count($tmp_date)-2; $j++){
                    array_push($tmp_data, $value_[$j]);
                }
                array_push($tmp_data, $value_['avg']);
                array_push($tmp_arr, $tmp_data);
            }
        }
        $spreadsheet->getActiveSheet()->fromArray($tmp_arr, NULL, "A1");
        return $spreadsheet;
    }
}
