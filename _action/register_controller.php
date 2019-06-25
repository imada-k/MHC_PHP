<?php

/**
 * Registered POS Database.
 *
 * @author Katsuhiro Masaki <hiro@digitaljet.co.jp>
 */
class RegisterController extends BaseController
{

    /**
     * コンストラクタ
     */
    function RegisterController()
    {
        $this->isDb = true;
        $this->isDbRegister = false;
    }

    /**
     * 登録
     */
    function register()
    {

        /*
        //取得データの解析
        ob_start();
        var_dump($_REQUEST);
        $out = ob_get_contents();
        ob_end_clean();
        LogController::log($out);
        */

        //パラメータから登録する残数を取得
        //テーブル番号
        $jda_tbmcd = '';
        if (isset($_REQUEST['jda_tbmcd'])) {
            $jda_tbmcd = $_REQUEST['jda_tbmcd'];
        }

        //商品コード
        $jda_smcd = array();
        if (isset($_REQUEST['jda_smcd'])) {
            $jda_smcd = $_REQUEST['jda_smcd'];
        }
        //サブ商品
        $jda_smcd_sub1 = array();
        if (isset($_REQUEST['jda_smcd_sub1'])) {
            $jda_smcd_sub1 = $_REQUEST['jda_smcd_sub1'];
        }
        //商品名
        $jda_smnmj = array();
        if (isset($_REQUEST['jda_smnmj'])) {
            $jda_smnmj = $_REQUEST['jda_smnmj'];
        }

        //数量
        $jda_suryo = array();
        if (isset($_REQUEST['jda_suryo'])) {
            $jda_suryo = $_REQUEST['jda_suryo'];
        }

        //単価
        $jda_tanka = array();
        if (isset($_REQUEST['jda_tanka'])) {
            $jda_tanka = $_REQUEST['jda_tanka'];
        }

        //登録日時
        $jda_ins_date = array();
        if (isset($_REQUEST['jda_ins_date'])) {
            $jda_ins_date = $_REQUEST['jda_ins_date'];
        }

        //再送信フラグ
        $retrans = array();
        if (isset($_REQUEST['retrans'])) {
            $retrans = $_REQUEST['retrans'];
        }

        //JD_SEQ
        $jd_seq = array();
        if (isset($_REQUEST['jd_seq'])) {
            $jd_seq = $_REQUEST['jd_seq'];
        }

        $is_staff_call = false;
        $staff_call_cd = false;
        if (isset($_REQUEST['is_staff_call'])) {
            $is_staff_call = $_REQUEST['is_staff_call'];
        }

        $last_order = false;
        if (isset($_REQUEST['last_order'])) {
            $last_order = intval($_REQUEST['last_order']) === 1;
        }

        $jda_set_menus = array();
        if (isset($_REQUEST['jda_set_menus'])) {
            $jda_set_menus = $_REQUEST['jda_set_menus'];
        }

        $jda_km_cd = '';
        if (isset($_REQUEST['jda_km_cd'])) {
            $jda_km_cd = $_REQUEST['jda_km_cd'];
        }

        //店舗コード
        $tm_cd = TM_CD;

        if ($is_staff_call) {
            //POSの状態をチェック
            $sql = 'select POS_FLG FROM POS_STATE WHERE POS_TMCD = ?;';
            $this->db->query($sql, array($tm_cd));
            $row = $this->db->result_data();
            $row = array_shift($row);
            if (!isset($row['POS_FLG']) || intval($row['POS_FLG']) !== 0) {
                $this->data = array();
                $this->valid = true;
                return;
            }

            $sql = 'select SETUP_SELF_KBN04_001';
            $sql .= ' FROM SETUP_SELF WHERE SETUP_SELF_KEY = 1 AND SETUP_SELF_TMCD = ?';
            $this->db->query($sql, array($tm_cd));
            $settings = $this->db->result_data();
            $settings = array_shift($settings);
            if (!empty($settings['SETUP_SELF_KBN04_001'])) {
                $staff_call_cd = $settings['SETUP_SELF_KBN04_001'];
            }
        }

        //グループ化されていないかチェック
        $order_target_tbmcd = $jda_tbmcd;
        $sql = <<<EOT
SELECT
  TBL_YOBI_KBN4
FROM
  TBL
WHERE 
  TBL_TMCD = ?
AND 
  TBL_TBMCD = ?
EOT;
        $this->db->query($sql, array($tm_cd, $jda_tbmcd));
        $result = $this->db->result_data();
        if (count($result) > 0) {
            $row = array_shift($result);
            if (!empty($row['TBL_YOBI_KBN4'])) {
                $order_target_tbmcd = $row['TBL_YOBI_KBN4'];
            }
        }

        //テーブル情報の取得
        $sql = <<<EOT
		select 
			JD_ORDER_MMCD
			,JD_NINZU
			,JD_MAN_NINZU
			,JD_WOMAN_NINZU
			,JD_XM11_CD
		from
			JD
		where
			JD_TMCD = ?
			and
			JD_TBMCD = ?
		order by 
			JD_INS_DATE DESC,
			JD_GYO DESC
		;
EOT;
        $this->db->query($sql, array($tm_cd, $order_target_tbmcd));
        $result = $this->db->result_data();

        if (count($result) > 0 || $is_staff_call) {
            $row = array_shift($result);

            $jda_order_mmcd = '99999';
            $jda_ninzu = '';
            $jda_man_ninzu = '';
            $jda_woman_ninzu = '';
            $jda_xm11_cd = '';
            if (count($result) > 0 && $jda_tbmcd === $order_target_tbmcd) {
                $jda_ninzu = $row['JD_NINZU'];
                $jda_man_ninzu = $row['JD_MAN_NINZU'];
                $jda_woman_ninzu = $row['JD_WOMAN_NINZU'];
                $jda_xm11_cd = $row['JD_XM11_CD'];
            }

            //残数チェック
            $rd_suryos = array();
            $rd_smcds = array();
            for ($i = 0; $i < count($jda_smcd); $i++) {
                if (in_array($jda_smcd[$i], $rd_smcds) === false) {
                    $rd_smcds[] = $jda_smcd[$i];
                }
                if (empty($rd_suryos[$jda_smcd[$i]])) {
                    $rd_suryos[$jda_smcd[$i]] = 0;
                }
                $rd_suryos[$jda_smcd[$i]] += $jda_suryo[$i];
            }

            $sql = <<<EOT
      select
        SM_NMJ,
        SM_SELF_SMNMJ,
        RD_SMCD,
        RD_ZAN
      from
        RD
      inner join
        SM on(
        RD.RD_SMCD = SM.SM_CD
      )
      where
        RD_SMCD in (
EOT;
            foreach ($rd_smcds as $smcd) {
                $sql .= '?,';
            }
            $sql = substr($sql, 0, strlen($sql) - 1);
            $sql .= ');';
            $this->db->query($sql, $rd_smcds);
            $result = $this->db->result_data();

            $rd_checks = array();

            if (count($result) > 0) {
                foreach ($result as $row) {
                    if (!empty($rd_suryos[$row['RD_SMCD']])) {
                        if ($rd_suryos[$row['RD_SMCD']] > intval($row['RD_ZAN'])) {
                            $rd_checks[] = array(
                                'identifier' => $row['RD_SMCD'],
                                'quantity' => $row['RD_ZAN']
                            );
                        }
                    }
                }
            }

            if (!empty($rd_checks)) {
                $this->data['quantity_check'] = $rd_checks;
                $this->valid = true;
                return;
            }

            $sql = <<<EOT
insert into 
    JDA
(
    JDA_KBN
,	JDA_TBMCD
,	JDA_ORDER_MMCD
,	JDA_NINZU
,	JDA_MAN_NINZU
,	JDA_WOMAN_NINZU
,	JDA_XM11_CD
,	JDA_GYO
,	JDA_SMCD
,	JDA_SMCD_SUB1
,	JDA_SMNMJ
,	JDA_SURYO
,	JDA_TANKA
,	JDA_INS_DATE
,   JDA_YOBI_KBN10
,   JDA_YOBI_KBN4
,   JDA_YOBI_KBN5
) VALUES (
    '1'
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,	?
,   ?
);
EOT;

            $sqls = array();
            $parameters = array();
            $parent_index = null;
            $parent_key = null;

            for ($i = 0; $i < count($jda_smcd); $i++) {

                $row_count = $i + 1;

                $sqls[] = $sql;
                $parameters[] = $jda_tbmcd;
                $parameters[] = $jda_order_mmcd;
                $parameters[] = $jda_ninzu;
                $parameters[] = $jda_man_ninzu;
                $parameters[] = $jda_woman_ninzu;
                $parameters[] = $jda_xm11_cd;
                $parameters[] = $row_count;
                $parameters[] = ($staff_call_cd !== false) ? $staff_call_cd : $jda_smcd[$i];
                $parameters[] = $jda_smcd_sub1[$i];
                $parameters[] = $jda_smnmj[$i];
                $parameters[] = $jda_suryo[$i];
                $parameters[] = $jda_tanka[$i];
                $parameters[] = $jda_ins_date[$i];
                $parameters[] = $jda_km_cd;

                if (!isset($jda_set_menus[$i]) || intval($jda_set_menus[$i]) !== 2) {
                    $parent_index = null;
                    $parent_key = null;
                }
                $parameters[] = $parent_key;
                $parameters[] = $parent_index;

                if (!isset($jda_set_menus[$i]) || intval($jda_set_menus[$i]) === 1) {
                    $this->db->query(implode(PHP_EOL, $sqls), $parameters);
                    $ident_sql = 'SELECT IDENT_CURRENT(\'JDA\') AS ID';
                    $this->db->query ($ident_sql);
                    $result = $this->db->result_data ();
                    $sqls = array();
                    $parameters = array();

                    if (count($result) > 0) {
                        $parent_key = $jda_smcd[$i];
                        $parent_index = $result[0]['ID'];

                        $sqls[] = 'UPDATE JDA SET JDA_YOBI_KBN4 = ?, JDA_YOBI_KBN5 = ? WHERE JDA_SEQ = ?;';
                        $parameters[] = $parent_key;
                        $parameters[] = $parent_index;
                        $parameters[] = $parent_index;
                    }
                }
            }

            if (!empty($sqls)) {
                $this->db->query(implode(PHP_EOL, $sqls), $parameters);
            }

            $this->registerBisms();

            //▼Ver1.3.11
            //ログ出力
            //ひとまずは卓番のみの出力
            //再送信をチェックする
            LogController::log('卓番：' . $jda_tbmcd . 'に対する処理' . (($retrans === '1') ? ' 再送信データ' : ''));
            //▲Ver1.3.11
        }

        if ($last_order) {
            $tableController = new TableController();
            $tableController->init();
            $tableController->checkout($jda_tbmcd);
            $tableController->release();
            $this->data['last_order'] = '1';
        }

        /*
        //処理終了後に実行ファイルをキックする
        //その際にテーブル番号をセット
        $exe_path = KICK_EXE;
        $exe_path = str_replace('{tblcd}', $jda_tbmcd, $exe_path);
        LogController::log($exe_path);

        //処理実行
        $shell = new COM("WScript.Shell");
        $shell->Run($exe_path, 0, false);
        unset($shell);
        */

        $this->valid = true;
    }

    function registerBisms() {
        $bisms = array();
        if (isset($_REQUEST['bisps']) && is_array($_REQUEST['bisps'])) {
            $bisms = $_REQUEST['bisps'];
        }
        $km_cd = '';
        if (isset($_REQUEST['jda_km_cd'])) {
            $km_cd = $_REQUEST['jda_km_cd'];
        }

        if (empty($km_cd) || empty($bisms)) {
            return;
        }

        //当日
        $ymd = date('Ymd');

        //ダブリを防ぐためにまずは検索
        $sql = <<<EOT
SELECT
BISM_LOG_KBN,
BISM_LOG_BIMCD,
BISM_LOG_CD
FROM
BISM_LOG
WHERE
BISM_LOG_KMCD = ?
AND
BISM_LOG_YMD = ?
EOT;
        $this->db->query($sql, array($km_cd, $ymd));
        $results = $this->db->result_data();
        foreach ($results as $row) {
            $check = "{$row['BISM_LOG_KBN']}-{$row['BISM_LOG_BIMCD']}-{$row['BISM_LOG_CD']}";
            if (in_array($check, $bisms)) {
                unset($bisms[array_search($check, $bisms)]);
            }
        }

        if (empty($bisms)) {
            return;
        }

        //分解
        $bisps = array();
        foreach ($bisms as $bisp) {
            $conditions = explode('-', $bisp);
            if (count($conditions) !== 3) {
                continue;
            }
            $bisps[] = $conditions;
        }

        //何故か名前を取得しないといけないので取得
        $sql = <<<EOT
			select
			    BIM_KBN
			,	BIM_CD
			,	BIM_NMJ
			from
				BIM
			where
				BIM_DEL_KBN <> '1'
			order by
				BIM_HYOJI,
				BIM_CD
EOT;
        $this->db->query($sql);

        $bim_names = $this->db->result_data();

        $sql = <<<EOT
			select
			    BISM_KBN
			,	BISM_BIMCD
			,	BISM_CD
			,	BISM_NMJ
			from
				BISM
			where
				BISM_DEL_KBN <> '1'
			order by
				BISM_HYOJI,
				BISM_CD
EOT;
        $this->db->query($sql);
        $bism_names = $this->db->result_data();

        //登録
        $sql = <<<EOT
INSERT INTO
BISM_LOG
(
BISM_LOG_KMCD,
BISM_LOG_YMD,
BISM_LOG_KBN,
BISM_LOG_BIMCD,
BISM_LOG_BIMNMJ,
BISM_LOG_CD,
BISM_LOG_NMJ,
BISM_LOG_USE_PCNAME,
BISM_LOG_DEL_KBN,
BISM_LOG_SOSIN_FLG,
BISM_LOG_INS_DATE
) values (
?,
?,
?,
?,
?,
?,
?,
0,
0,
0,
getdate()
)
EOT;
        foreach ($bisps as $bisp) {
            $bimnmj = '';
            foreach ($bim_names as $bim_name) {
                if ($bim_name['BIM_KBN'] == $bisp[0] && $bim_name['BIM_CD'] == $bisp[1]) {
                    $bimnmj = $bim_name['BIM_NMJ'];
                    break;
                }
            }
            $nmj = '';
            foreach ($bism_names as $bism_name) {
                if ($bism_name['BISM_KBN'] == $bisp[0] && $bism_name['BISM_BIMCD'] == $bisp[1] && $bism_name['BISM_CD'] == $bisp[2]) {
                    $nmj = $bism_name['BISM_NMJ'];
                    break;
                }
            }
            $params = array(
                $km_cd,
                $ymd,
                $bisp[0],
                $bisp[1],
                $bimnmj,
                $bisp[2],
                $nmj
            );
            $this->db->query($sql, $params);
        }
    }
}

?>