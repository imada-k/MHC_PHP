<?php

/**
 * Get Order history.
 *
 * @author Katsuhiro Masaki <hiro@digitaljet.co.jp>
 */
class KmController extends BaseController
{
    var $ken_columns = array(
        'KEN_0101',
        'KEN_0102',
        'KEN_0103',
        'KEN_0104',
        'KEN_0105',
        'KEN_0106',
        'KEN_0201',
        'KEN_0202',
        'KEN_0203',
        'KEN_0204',
        'KEN_0205',
        'KEN_0206',
        'KEN_0207',
        'KEN_0208',
        'KEN_0301',
        'KEN_0302',
        'KEN_0303',
        'KEN_0304',
        'KEN_0401',
        'KEN_0402',
        'KEN_0501',
        'KEN_0502',
        'KEN_0601',
        'KEN_0602',
        'KEN_0603',
        'KEN_0604',
        'KEN_0605',
        'KEN_0606',
        'KEN_0607',
        'KEN_0608',
        'KEN_0701',
        'KEN_0801',
        'KEN_0802',
        'KEN_0803',
        'KEN_0804',
        'KEN_0805',
        'KEN_0806',
        'KEN_0807',
        'KEN_0808',
        'KEN_0809',
        'KEN_0810',
        'KEN_0901',
        'KEN_0902',
        'KEN_0903',
        'KEN_0904',
        'KEN_1001',
        'KEN_1002',
        'KEN_1101',
        'KEN_1102',
        'KEN_1103',
        'KEN_1201',
        'KEN_1202',
        'KEN_1203',
        'KEN_1204',
        'KEN_1205',
        'KEN_1206',
        'KEN_1207',
        'KEN_1208',
        'KEN_1209',
        'KEN_1210',
        'KEN_1211',
        'KEN_1212',
        'KEN_1213',
        'KEN_1214',
        'KEN_1215',
        'KEN_1301',
        'KEN_1302',
        'KEN_1401',
        'KEN_1402',
        'KEN_1403',
        'KEN_1404'
    );

    /**
     * コンストラクタ
     */
    function KmController()
    {
        $this->isDb = true;
    }

    function login()
    {
        $tel = "";
        if (isset($_REQUEST["tel"])) {
            $tel = $_REQUEST["tel"];
            $tel = trim(mb_convert_kana($tel, 'as', 'UTF-8'));
            $tel = preg_replace('/[^0-9]/', '', $tel);
        }
        $birthday = "";
        if (isset($_REQUEST["birthday"])) {
            $birthday = $_REQUEST["birthday"];
            $birthday = trim(mb_convert_kana($birthday, 'as', 'UTF-8'));
            $birthday = preg_replace('/[^0-9]/', '', $birthday);
        }
        //存在チェック
        $select_sql = <<<EOT
select 
KM_CD,
KM_NMJ1,
KM_NMJ2,
KM_ADR1,
KM_TEL1,
KM_EMAIL,
KM_BIRTHDAY,
KM_SEX,
KM_YOBI_KOMOKU1
    FROM
      KM
where 
KM_TEL1 = ?
and 
KM_BIRTHDAY = ?
EOT;
        $this->db->query($select_sql, array($tel, $birthday));
        $result = $this->db->result_data();
        if (!empty($result)) {
            $km = array();
            $row = array_shift($result);
            $km['code'] = $row['KM_CD'];
            $km['name_1'] = $row['KM_NMJ1'];
            $km['name_2'] = $row['KM_NMJ2'];
            $km['address'] = $row['KM_ADR1'];
            $km['tel'] = $row['KM_TEL1'];
            $km['email'] = $row['KM_EMAIL'];
            $km['birthday'] = $row['KM_BIRTHDAY'];
            if (!empty($this->data['km']['birthday'])) {
                $km['birthday'] = substr($km['birthday'], 0, 4) . '/' .
                    substr($km['birthday'], 4, 2) . '/' .
                    substr($km['birthday'], 6);
            }
            $km['sex'] = $row['KM_SEX'];
            $km['password'] = $row['KM_YOBI_KOMOKU1'];
            $this->data['km'] = $km;

            // 商品履歴
            $this->historyOrders($row['KM_CD']);

        } else {
            $this->data['message'] = '情報が見つかりませんでした';
        }
        $this->valid = true;
    }

    function historyOrders($km_cd)
    {
        $ymd = date('Ymd');

        $sql = <<<EOT
select TOP 100
  UD_DENPYO_YMD,
  UD_SMNMJ
FROM
  UD
WHERE
  UD_KMCD = ?
  AND
  UD_DENPYO_YMD < ?
  AND
  UD_TANKA <> 0
ORDER BY
  UD_DENPYO_YMD DESC
EOT;
        $this->db->query($sql, array($km_cd, $ymd));
        $results = $this->db->result_data();
        $target_ymd = false;
        $orders = array();
        foreach ($results as $result) {
            if ($target_ymd === false) {
                $target_ymd  = $result['UD_DENPYO_YMD'];
            }
            if ($target_ymd !== $result['UD_DENPYO_YMD']) {
                break;
            }
            $orders[] = $result['UD_SMNMJ'];
        }
        if (!empty($target_ymd)) {
            $this->data['history_ymd'] = substr($target_ymd, 0, 4) . '/' .
                substr($target_ymd, 4, 2) . '/' .
                substr($target_ymd, 6);
        } else {
            $this->data['history_ymd'] = '';
        }
        $this->data['history_orders'] = $orders;

        $sql = <<<EOT
select top 100
  BISM_LOG_KBN,
  BISM_LOG_BIMNMJ,
  BISM_LOG_NMJ
from
  BISM_LOG
where 
  BISM_LOG_KMCD = ?
and 
  BISM_LOG_YMD = ?
EOT;

        $this->db->query($sql, array($km_cd, $target_ymd));
        $results = $this->db->result_data();
        $orders = array();
        foreach ($results as $result) {
            $kbn_nmj = intval($result['BISM_LOG_KBN']) === 1 ? '症状' : '願望';
            $orders[] = "{$kbn_nmj} - ({$result['BISM_LOG_BIMNMJ']}){$result['BISM_LOG_NMJ']}";
        }
        $this->data['history_bisms'] = $orders;
    }

    function kens()
    {
        if (empty($_REQUEST["km_cd"]) || empty($_REQUEST["ymd"])) {
            return;
        }
        $km_cd = $_REQUEST["km_cd"];
        $ymd = str_replace('/', '', $_REQUEST['ymd']);

        $sql_column = implode(',', $this->ken_columns);

        $sql = <<<EOT
select TOP 2
  KEN_YMD,
  {$sql_column}
FROM
  KEN
WHERE
  KEN_KMCD = ?
  AND
  KEN_YMD <= ?
ORDER BY
  KEN_YMD DESC
EOT;
        $this->db->query($sql, array($km_cd, $ymd));
        $results = $this->db->result_data();
        $ken = array();
        $ken_ymd = '';
        $ken_old = array();
        $ken_old_ymd = '';
        foreach ($results as $result) {
            if (intval($ymd) === intval($result['KEN_YMD'])) {
                $ken_ymd = $result['KEN_YMD'];
                $ken_ymd = substr($ken_ymd, 0, 4) . '/' .
                substr($ken_ymd, 4, 2) . '/' .
                substr($ken_ymd, 6);
                foreach ($this->ken_columns as $column) {
                    $ken[$column] = $this->resetValue($result[$column]);
                }
            } elseif (empty($ken_old_ymd)) {
                $ken_old_ymd = $result['KEN_YMD'];
                $ken_old_ymd = substr($ken_old_ymd, 0, 4) . '/' .
                    substr($ken_old_ymd, 4, 2) . '/' .
                    substr($ken_old_ymd, 6);
                foreach ($this->ken_columns as $column) {
                    $ken_old[$column] = $this->resetValue($result[$column]);
                }
            }
        }
        if (!empty($ken_ymd)) {
            $this->data['ken_ymd'] = $ken_ymd;
            $this->data['ken'] = $ken;
        }
        if (!empty($ken_old_ymd)) {
            $this->data['ken_old_ymd'] = $ken_old_ymd;
            $this->data['ken_old'] = $ken_old;
        }
        $this->valid = true;
    }

    function resetValue($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        $value = $value * 10.0;
        $value = $value / 10.0;
        return strval($value);
    }

    function create()
    {
        $name_1 = "";
        if (isset($_REQUEST["name_1"])) {
            $name_1 = $_REQUEST["name_1"];
        }
        $name_2 = "";
        if (isset($_REQUEST["name_2"])) {
            $name_2 = $_REQUEST["name_2"];
        }
        $tel = "";
        if (isset($_REQUEST["tel"])) {
            $tel = $_REQUEST["tel"];
            $tel = trim(mb_convert_kana($tel, 'as', 'UTF-8'));
            $tel = preg_replace('/[^0-9]/', '', $tel);
        }
        $email = "";
        if (isset($_REQUEST["email"])) {
            $email = $_REQUEST["email"];
        }
        $sex = "";
        if (isset($_REQUEST["sex"])) {
            $sex = $_REQUEST["sex"];
        }
        $birthday = "";
        if (isset($_REQUEST["birthday"])) {
            $birthday = $_REQUEST["birthday"];
            $birthday = trim(mb_convert_kana($birthday, 'as', 'UTF-8'));
            $birthday = preg_replace('/[^0-9]/', '', $birthday);
        }
        $address = "";
        if (isset($_REQUEST["address"])) {
            $address = $_REQUEST["address"];
        }
        $password = "";
        if (isset($_REQUEST["password"])) {
            $password = $_REQUEST["password"];
        }

        //存在チェック
        $select_sql = <<<EOT
select 
    KM_CD
    FROM
      KM
where 
KM_TEL1 = ?
and 
KM_BIRTHDAY = ?
EOT;
        $this->db->query($select_sql, array($tel, $birthday));
        $result = $this->db->result_data();

        if (empty($result)) {
            // KM_CDの採番のために最大値取得
            $newCd = $this->getNewCode();

            $sql = <<<EOT
insert into
KM
(
KM_CD,
KM_NMJ1,
KM_NMJ2,
KM_ADR1,
KM_TEL1,
KM_EMAIL,
KM_BIRTHDAY,
KM_SEX,
KM_YOBI_KOMOKU1,
KM_INS_DATE
) values (
?,
?,
?,
?,
?,
?,
?,
?,
?,
GETDATE()
)
EOT;
            $this->db->query($sql, array(
                $newCd,
                $name_1,
                $name_2,
                $address,
                $tel,
                $email,
                $birthday,
                $sex,
                $password
            ));

            //ファイル作成
            $this->createInsertFile();

            $this->db->query($select_sql, array($tel, $birthday));
            $result = $this->db->result_data();

            if (empty($result)) {
                $this->data['message'] = '登録に失敗しました';
            } else {
                $this->data['km'] = array_shift($result);
                if (!empty($this->data['km']['birthday'])) {
                    $this->data['km']['birthday'] = substr($this->data['km']['birthday'], 0, 4) . '/' .
                        substr($this->data['km']['birthday'], 4, 2) . '/' .
                        substr($this->data['km']['birthday'], 6);
                }
            }

        } else {
            $this->data['message'] = '既にお客様は登録されています';
        }
        $this->valid = true;
    }

    function create_ken()
    {
        if (empty($_REQUEST['km_cd']) || !is_numeric($_REQUEST['km_cd']) || empty($_REQUEST['ymd'])) {
            return;
        }
        $ymd = str_replace('/', '', $_REQUEST['ymd']);
        $km_cd = $_REQUEST['km_cd'];
        $datas = $_REQUEST;

        $key_sets = array();
        foreach ($this->ken_columns as $column) {
            if (!empty($datas[$column])) {
                $key_sets[$column] = $datas[$column];
            }
        }

        // 当日データの存在チェック
        $sql = 'SELECT KEN_KMCD FROM KEN WHERE KEN_KMCD = ? AND KEN_YMD = ?';
        $this->db->query($sql, array($km_cd, $ymd));
        $results = $this->db->result_data();
        if (empty($results)) {
            // 新規登録
            $sql_column = implode(',', $this->ken_columns);
            $sql_keys = array();
            foreach ($this->ken_columns as $_) {
                $sql_keys[] = '?';
            }
            $sql_key = implode(',', $sql_keys);
            $sql = <<<EOT
insert into
KEN (
KEN_KMCD,
KEN_YMD,
{$sql_column},
KEN_SOSIN_FLG,
KEN_INS_DATE
) values (
?,
?,
{$sql_key},
0,
getdate()
)
EOT;
            $values = array($km_cd, $ymd);
            foreach ($this->ken_columns as $column) {
                if (!empty($datas[$column])) {
                    $values[] = $datas[$column];
                } else {
                    $values[] = '';
                }
            }

            $this->db->query($sql, $values);
        } else {

            // 更新
            $sql_column = array();
            $values = array();
            foreach ($this->ken_columns as $ken_column) {
                $sql_column[] = "{$ken_column} = ?";
                if (!empty($datas[$ken_column])) {
                    $values[] = $datas[$ken_column];
                } else {
                    $values[] = '';
                }
            }
            $values[] = $km_cd;
            $values[] = $ymd;
            $sql_column = implode(',', $sql_column);
            $sql = <<<EOT
update KEN set
{$sql_column},
KEN_SOSIN_FLG = 0,
KEN_UPD_DATE = getdate()
where 
KEN_KMCD = ?
and
KEN_YMD = ?
EOT;
            $this->db->query($sql, $values);
        }

        $this->createHealthInsertFile();
        $this->calc_ken($km_cd, $key_sets);

        $this->valid = true;
    }

    function calc_ken($km_cd, $values) {
        $this->data['cd'] = array();
        $this->data['contents'] = array();

        if (!isset($_REQUEST['category']) || !is_numeric($_REQUEST['category'])) {
            return;
        }
        $select_category = intval($_REQUEST['category']);

        if ( $select_category == 2 ) {
            if (!isset($_REQUEST['smid']) || !is_numeric($_REQUEST['smid'])) {
                return;
            }
            $oya_cd = intval($_REQUEST['smid']);
        } else {
            $oya_cd = 0;
        }
        $oya_content = array();

        // 性別取得
        $sql = 'select KM_SEX from KM where KM_CD = ?';
        $this->db->query($sql, array($km_cd));
        $results = $this->db->result_data();
        if (empty($results)) {
            return;
        }
        $result = array_shift($results);
        if (!empty($result['KM_SEX'])) {

        $m = intval($result['KM_SEX']) === 1 ? 'M' : 'W';

        //条件取得
        $sql = <<<EOT
select
KENNG_ID as ID,
KENNG_{$m}_JOKEN1 as JOKEN1,
KENNG_{$m}_JOKEN2 as JOKEN2
from
KENNG
EOT;
        $this->db->query($sql);
        $ngs = $this->db->result_data();

        // 異常を探す
        $ng_ids = array();
        foreach ($ngs as $ng) {
            // IDからキー生成
            $key = 'KEN_' . sprintf('%04d', $ng['ID']);
            if (!empty($values[$key])) {
                $value = $values[$key] * 1.0;
                if (!is_null($ng['JOKEN1']) && $ng['JOKEN1'] > $value) {
                    if (!in_array($ng['ID'], $ng_ids)) {
                        $ng_ids[] = $ng['ID'];
                    }
                } elseif (!is_null($ng['JOKEN2']) && $ng['JOKEN2'] < $value) {
                    if (!in_array($ng['ID'], $ng_ids)) {
                        $ng_ids[] = $ng['ID'];
                    }
                }
            }
        }

        if (empty($ng_ids)) {
            return;
        }
        //NGになったIDの素材取得
        $sql_in = implode("','", $ng_ids);
        $sql = <<<EOT
select
KENP_SM2CD,
KENP_ID,
KENP_POINT
from
KENP
WHERE
KENP_ID in ('{$sql_in}')
EOT;
        $this->db->query($sql);
        $results = $this->db->result_data();
        $point_results = array();
        foreach ($results as $result) {
            if (empty($point_results[$result['KENP_SM2CD']])) {
                $point_results[$result['KENP_SM2CD']] = 0;
            }
            $point_results[$result['KENP_SM2CD']] += $result['KENP_POINT'];
        }

        // $point_results のキーには商品コード、値にはポイントが入る
        // 商品マスタより取得
        if (!empty($point_results)) {
            $sql = <<<EOT
        select
        SM_CD,
        SM_NMJ,
        SM_IMAGE,
        SM_DETAIL,
        SM_YOBI_KBN1,
        SM_SELF_SMNMJ
        from
        SM
		inner join
			STM on(
			STM.STM_SMCD = SM.SM_CD
		)

        WHERE
        SM_CD in(
EOT;
            $sm2cds = array_keys($point_results);
            // カテゴリがブレンド茶の時のみ親コード取得
            if ($select_category == 2) {
                $sm2cds[] = $oya_cd;
            }

            $sql .= implode(',', $sm2cds) . ')';
            $this->db->query($sql);
            $sms = array();
            $result = $this->db->result_data();
            foreach ($result as $row) {
                if ( $row['SM_CD'] == $oya_cd) {
                    $oya_content = array(
                        'name' => !empty($row['SM_SELF_SMNMJ']) ? $row['SM_SELF_SMNMJ'] : $row['SM_NMJ'],
                        'detail' => $row['SM_DETAIL'],
                        'name_en' => "",
                        'name_cn' => "",
                        'name_kr' => "",
                        'name_cnn' => "",
                        'detail_en' => "",
                        'detail_cn' => "",
                        'detail_kr' => "",
                        'detail_cnn' => "",
                        'price' => 0,
                        'image' => $row['SM_IMAGE']
                    );
                } else {
                    if (empty($sms[$row['SM_CD']])) {
                        $sms[$row['SM_CD']] = array(
                            'point' => 0,
                            'smcd' => $row['SM_CD'],
                            'name' => !empty($row['SM_SELF_SMNMJ']) ? $row['SM_SELF_SMNMJ'] : $row['SM_NMJ'],
                            'detail' => $row['SM_DETAIL'],
                            'name_en' => "",
                            'name_cn' => "",
                            'name_kr' => "",
                            'name_cnn' => "",
                            'detail_en' => "",
                            'detail_cn' => "",
                            'detail_kr' => "",
                            'detail_cnn' => "",
                            'price' => 0,
                            'yobi' => ''
                        );
                    }
                    $sms[$row['SM_CD']]['point'] += $point_results[$row['SM_CD']];
                    $sms[$row['SM_CD']]['yobi'] = $row['SM_YOBI_KBN1'];
                }
            }

            // 店舗別商品マスタより取得
            // 選択カテゴリが異なっていたら、配列より削除
            $smcds = array_keys($sms);
            // カテゴリがブレンド茶の時のみ親コード取得
            if ($select_category == 2) {
                $smcds[] = $oya_cd;
            }
            $smcd_ins = implode(',', $smcds);
            $sql = <<<EOT
			select
				STM_SMCD,
				STM_BAIKA,
				STM_ZEIMAR_KBN,
				STM_CTMCD1
				from
				STM
where STM_SMCD in({$smcd_ins})
EOT;

            $this->db->query($sql);
            $stms = $this->db->result_data();
            foreach ($stms as $stm) {
                $price = $stm['STM_BAIKA'] * (1 + TAX);
                switch (intval($stm['STM_ZEIMAR_KBN'])) {
                    case 0:
                        $price = floor($price);
                        break;
                    case 5:
                        $price = round($price);
                        break;
                    case 9:
                    default:
                        $price = ceil($price);
                        break;
                }
                if ($stm['STM_SMCD'] != $oya_cd) {
                    $sms[$stm['STM_SMCD']]['price'] = $price;
                    // カテゴリがブレンド茶の時は、ブレンドの原料茶も取得
                    if ($select_category === 2) {
                        if (intval($stm['STM_CTMCD1']) !== 2 && intval($stm['STM_CTMCD1']) !== 7) {
                            unset($sms[$stm['STM_SMCD']]);
                        }
                    } else {
                        if (intval($stm['STM_CTMCD1']) !== $select_category) {
                            unset($sms[$stm['STM_SMCD']]);
                        }
                    }
                } else {
                    $oya_content['price'] = $price;
                }
            }
            // この時点で商品がなかったら、終了
            if (empty($sms)) {
                return;
            }

            // 商品マスタ多言語からデータを取得
            $sql = <<<EOT
            select
            SM_L_CD,
            SM_L_SELF_SMNMJ01,
            SM_L_SELF_SMNMJ02,
            SM_L_SELF_SMNMJ03,
            SM_L_YOBI_KOMOKU1,
            SM_L_DETAIL01,
            SM_L_DETAIL02,
            SM_L_DETAIL03,
            SM_L_YOBI_KOMOKU2
            from
            SM_L
            WHERE
            SM_L_CD in(
EOT;
            $smscd = array_keys($sms);
            // カテゴリがブレンド茶の時のみ親コード取得
            if ($select_category == 2) { $smscd[] = $oya_cd; }
            
            $sql .= implode(',', $smscd) . ') order by 
            SM_L_CD ASC
            ';
            
            $this->db->query($sql);
            $result = $this->db->result_data();
            foreach ($result as $row) {
                if ($row['SM_L_CD'] == $oya_cd) {
                    $oya_content['name_en'] = $row['SM_L_SELF_SMNMJ01'];
                    $oya_content['name_cn'] = $row['SM_L_SELF_SMNMJ02'];
                    $oya_content['name_kr'] = $row['SM_L_SELF_SMNMJ03'];
                    $oya_content['name_cnn'] = $row['SM_L_YOBI_KOMOKU1'];
                    $oya_content['detail_en'] = $row['SM_L_DETAIL01'];
                    $oya_content['detail_cn'] = $row['SM_L_DETAIL02'];
                    $oya_content['detail_kr'] = $row['SM_L_DETAIL03'];
                    $oya_content['detail_cnn'] = $row['SM_L_YOBI_KOMOKU2'];
                } else {
                    if ( array_key_exists($row['SM_L_CD'], $sms) !== FALSE) {
                        $smcd = $row['SM_L_CD'];
                        $sms[$smcd]['name_en'] = $row['SM_L_SELF_SMNMJ01'];
                        $sms[$smcd]['name_cn'] = $row['SM_L_SELF_SMNMJ02'];
                        $sms[$smcd]['name_kr'] = $row['SM_L_SELF_SMNMJ03'];
                        $sms[$smcd]['name_cnn'] = $row['SM_L_YOBI_KOMOKU1'];
                        $sms[$smcd]['detail_en'] = $row['SM_L_DETAIL01'];
                        $sms[$smcd]['detail_cn'] = $row['SM_L_DETAIL02'];
                        $sms[$smcd]['detail_kr'] = $row['SM_L_DETAIL03'];
                        $sms[$smcd]['detail_cnn'] = $row['SM_L_YOBI_KOMOKU2'];
                    }
                }
            }

            // 組み合わせを作成
            function getCpattern($source, $m) {
                $n = sizeof($source);
                return ptn($source, $n, array(), 0, $n-$m+1);
            }
            
            function ptn($source, $n, $subset, $begin, $end) {
                $p = array();
                for ( $i = $begin; $i<$end; $i++ ) {
                    $tmp = array_merge( $subset, (array)$source[$i] );
                    if ( $end + 1 <= $n ) {
                        $p = array_merge($p, ptn($source, $n, $tmp, $i + 1, $end + 1));
                    } else {
                        array_push( $p, $tmp );
                    }
                }
                return $p;
            }
            
            $sm2cds = array_keys($sms);
            $results = getCpattern($sm2cds, 3);
            
            foreach ($results as &$row) {
                
                $row = array_merge( $row, array('point' => 0) );
                $row = array_merge( $row, array('yobi' => '') );
                $row = array_merge( $row, array('smcd' => 99999999999) );
                foreach ( $row as $key => $value ) {
                    if ( $key !== 'point' && $key !== 'yobi' && $key !== 'smcd' ) {
                        if ( array_key_exists($value, $sms) !== FALSE) {
                            $row['point'] += $sms[$value]['point'];
                            if ( $row['smcd'] > $value ) {
                                $row['smcd'] = $value;
                                $row['yobi'] = $sms[$value]['yobi'];
                            }
                        }
                    }
                }
            }

            // 比較用の関数
            function cmp($a, $b) {
                if ($a['point'] === $b['point']) {
                    if ($a['yobi'] === $b['yobi']) {
                        if ($a['smcd'] === $b['smcd']) {
                            return 0;
                        }
                        return (intval($a['smcd']) > intval($b['smcd'])) ? 1 : -1;
                    }
                    return ($a['yobi'] < $b['yobi']) ? 1 : -1;
                }
                return ($a['point'] < $b['point']) ? 1 : -1;
            }

            //ソート
            uasort($results, 'cmp');

            $cd = array();
            $contents = array();

            // カテゴリがブレンド茶の時のみ親コード取得
            if ($select_category == 2) {
                foreach ($results as $sm_key => $sm) {
                    $cd[] = $sm_key;
                    $content = array();
                    $content[] = array(
                        'name' => $oya_content['name'],
                        'name_en' => $oya_content['name_en'],
                        'name_cn' => $oya_content['name_cn'],
                        'name_kr' => $oya_content['name_kr'],
                        'name_cnn' => $oya_content['name_cnn'],
                        'detail' => $oya_content['detail'],
                        'detail_en' => $oya_content['detail_en'],
                        'detail_cn' => $oya_content['detail_cn'],
                        'detail_kr' => $oya_content['detail_kr'],
                        'detail_cnn' => $oya_content['detail_cnn'],
                        'price' => $oya_content['price'],
                        'image' => $oya_content['image']
                        ,
                        's0_smcd' => $sms[$sm[0]]['smcd'],
                        's0_name' => $sms[$sm[0]]['name'],
                        's0_name_en' => $sms[$sm[0]]['name_en'],
                        's0_name_cn' => $sms[$sm[0]]['name_cn'],
                        's0_name_kr' => $sms[$sm[0]]['name_kr'],
                        's0_name_cnn' => $sms[$sm[0]]['name_cnn'],
                        's0_detail' => $sms[$sm[0]]['detail'],
                        's0_detail_en' => $sms[$sm[0]]['detail_en'],
                        's0_detail_cn' => $sms[$sm[0]]['detail_cn'],
                        's0_detail_kr' => $sms[$sm[0]]['detail_kr'],
                        's0_detail_cnn' => $sms[$sm[0]]['detail_cnn'],
                        's0_price' => $sms[$sm[0]]['price']
                        ,
                        's1_smcd' => $sms[$sm[1]]['smcd'],
                        's1_name' => $sms[$sm[1]]['name'],
                        's1_name_en' => $sms[$sm[1]]['name_en'],
                        's1_name_cn' => $sms[$sm[1]]['name_cn'],
                        's1_name_kr' => $sms[$sm[1]]['name_kr'],
                        's1_name_cnn' => $sms[$sm[1]]['name_cnn'],
                        's1_detail' => $sms[$sm[1]]['detail'],
                        's1_detail_en' => $sms[$sm[1]]['detail_en'],
                        's1_detail_cn' => $sms[$sm[1]]['detail_cn'],
                        's1_detail_kr' => $sms[$sm[1]]['detail_kr'],
                        's1_detail_cnn' => $sms[$sm[1]]['detail_cnn'],
                        's1_price' => $sms[$sm[1]]['price']
                        ,
                        's2_smcd' => $sms[$sm[2]]['smcd'],
                        's2_name' => $sms[$sm[2]]['name'],
                        's2_name_en' => $sms[$sm[2]]['name_en'],
                        's2_name_cn' => $sms[$sm[2]]['name_cn'],
                        's2_name_kr' => $sms[$sm[2]]['name_kr'],
                        's2_name_cnn' => $sms[$sm[2]]['name_cnn'],
                        's2_detail' => $sms[$sm[2]]['detail'],
                        's2_detail_en' => $sms[$sm[2]]['detail_en'],
                        's2_detail_cn' => $sms[$sm[2]]['detail_cn'],
                        's2_detail_kr' => $sms[$sm[2]]['detail_kr'],
                        's2_detail_cnn' => $sms[$sm[2]]['detail_cnn'],
                        's2_price' => $sms[$sm[2]]['price']
                    );
                    $contents[$sm_key] = $content;
                    if (count($cd) >= 3) {
                        break;
                    }
                }
            } else {
                //ソート
                uasort($sms, 'cmp');
                
                foreach ($sms as $sm_key => $sm) {
                    $cd[] = $sm_key;
                    $content = array();
                    $content[] = array(
                        'name' => $sm['name'],
                        'name_en' => $sm['name_en'],
                        'name_cn' => $sm['name_cn'],
                        'name_kr' => $sm['name_kr'],
                        'name_cnn' => $sm['name_cnn'],
                        'detail' => $sm['detail'],
                        'detail_en' => $sm['detail_en'],
                        'detail_cn' => $sm['detail_cn'],
                        'detail_kr' => $sm['detail_kr'],
                        'detail_cnn' => $sm['detail_cnn'],
                        'price' => $sm['price']
                    );
                    $contents[$sm_key] = $content;
                    if (count($cd) >= 3) {
                        break;
                    }
                }
            }

            $this->data['cd'] = $cd;
            $this->data['contents'] = $contents;
        }
    }
    }

    function getNewCode()
    {
        $sql = 'select MAX(KM_CD) AS MAX_CD from KM';
        $this->db->query($sql);
        $result = $this->db->result_data();
        if (!empty($result)) {
            $row = array_shift($result);
            return intval($row['MAX_CD']) + 1;
        }
        return 1;
    }

    function createInsertFile()
    {
        $sql = <<<EOT
select
SETUP_SELF_KOMOKU99_008
from
  SETUP_SELF
WHERE SETUP_SELF_KEY = 1 AND SETUP_SELF_TMCD = ?
EOT;
        $this->db->query($sql, array(TM_CD));
        $result = $this->db->result_data();

        if (!empty($result)) {
            $row = array_shift($result);
            $dir_path = $row['SETUP_SELF_KOMOKU99_008'];
            if (!empty($dir_path) && !file_exists($dir_path)) {
                mkdir($dir_path, 0777, true);
            }
            $time = date('YmdHis');
            $file_name =  $dir_path . DIRECTORY_SEPARATOR . "km_{$time}.txt";
            touch($file_name);
        }
    }

    function createHealthInsertFile()
    {
        $sql = <<<EOT
select
SETUP_SELF_KOMOKU99_008
from
  SETUP_SELF
WHERE SETUP_SELF_KEY = 1 AND SETUP_SELF_TMCD = ?
EOT;
        $this->db->query($sql, array(TM_CD));
        $result = $this->db->result_data();

        if (!empty($result)) {
            $row = array_shift($result);
            $dir_path = $row['SETUP_SELF_KOMOKU99_008'];
            if (!empty($dir_path) && !file_exists($dir_path)) {
                mkdir($dir_path, 0777, true);
            }
            $time = date('YmdHis');
            $file_name =  $dir_path . DIRECTORY_SEPARATOR . "health_{$time}.txt";
            touch($file_name);
        }
    }

    function kensHistory()
    {
        if (empty($_REQUEST["km_cd"])) {
            return;
        }
        $km_cd = $_REQUEST["km_cd"];

        $sql_column = implode(',', $this->ken_columns);

        $sql = <<<EOT
select
  KEN_YMD,
  {$sql_column}
FROM
  KEN
WHERE
  KEN_KMCD = ?
ORDER BY
  KEN_YMD
EOT;
        $this->db->query($sql, array($km_cd));
        $results = $this->db->result_data();
        foreach ($results as $result) {
	        $ken = array();
            $ken_ymd = $result['KEN_YMD'];
            $ken_ymd = substr($ken_ymd, 0, 4) . '/' .
            substr($ken_ymd, 4, 2) . '/' .
            substr($ken_ymd, 6);
            foreach ($this->ken_columns as $column) {
                $ken[$column] = $this->resetValue($result[$column]);
            }
            $this->data[$ken_ymd] = $ken;
        }
        $this->valid = true;
    }

}