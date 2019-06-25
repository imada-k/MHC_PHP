<?php

/**
 * Table infomation getter
 *
 * @author Katsuhiro Masaki <hiro@digitaljet.co.jp>
 */
class TableController extends BaseController
{

    /**
     * コンストラクタ
     */
    function TableController()
    {
        $this->isDb = true;
    }

    /**
     * 検索
     */
    function select()
    {

        //店舗コード
        $parameters = array(
            TM_CD
        );

        //端末側が持っている卓の状態
        $clientTableExist = false;
        if (isset($_REQUEST['clientTableExist'])) {
            $clientTableExist = intval($_REQUEST['clientTableExist']) === 1;
        }

        $setting = array();

        //テーブル番号がわたってきているかチェック
        $tableNo = false;
        $key = "";
        if (isset($_REQUEST['key']) && is_numeric($_REQUEST['key'])) {
            $tableNo = $_REQUEST['key'] * 1;
            $parameters[] = $tableNo;
            $key = "AND TBL_TBMCD = ?";

            //グループ化されていないかチェック
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
            $this->db->query($sql, array(TM_CD, $tableNo));
            $result = $this->db->result_data();
            if (count($result) > 0) {
                $row = array_shift($result);
                if (!empty($row['TBL_YOBI_KBN4'])) {
                    $parameters[] = $row['TBL_YOBI_KBN4'];
                    $key = "AND (TBL_TBMCD = ? OR TBL_YOBI_KBN4 = ?)";
                }
            }

            $sql = 'select ';
            for ($i = 1; $i <= 5; $i++) {
                $sql .= sprintf('SETUP_SELF_KBN06_%03d,', $i);
            }
            $sql = substr($sql, 0, -1);
            $sql .= ',SETUP_SELF_KBN99_001' . PHP_EOL;
            $sql .= ',SETUP_SELF_KBN99_002' . PHP_EOL;
            $sql .= ',SETUP_SELF_KBN99_012' . PHP_EOL;
            $sql .= ' ,CNT1 ';
        	$sql .= ' ,CNT2 ';
        	$sql .= ' FROM SETUP_SELF ';
        	$sql .= ' cross join ( ';
			$sql .= ' select count(*) as CNT1 ';
			$sql .= ' from BIM ';
			$sql .= ' where ';
			$sql .= ' BIM_KBN = 1 and ISNULL(BIM_YOBI_KBN1,0) = 0 and BIM_DEL_KBN <> 1 ';
			$sql .= ' ) as BIM_WK1 ';
        	$sql .= ' cross join ( ';
			$sql .= ' select count(*) as CNT2 ';
			$sql .= ' from BIM ';
			$sql .= ' where ';
			$sql .= ' BIM_KBN = 2 and ISNULL(BIM_YOBI_KBN1,0) = 0 and BIM_DEL_KBN <> 1 ';
			$sql .= ' ) as BIM_WK2 ';
        	$sql .= ' WHERE SETUP_SELF_KEY = 1 AND SETUP_SELF_TMCD = ?';

            $this->db->query($sql, array(TM_CD));
            $settings = $this->db->result_data();
            $settings = array_shift($settings);

            if ($settings !== NULL) {
                $setting['displayLimit'] = $settings['SETUP_SELF_KBN06_001'];
                $setting['allYouCanEatLimit'] = $settings['SETUP_SELF_KBN06_002'];
                $setting['limitAlertTime'] = $settings['SETUP_SELF_KBN06_003'];
                $setting['timeOverProcess'] = $settings['SETUP_SELF_KBN06_004'];
                $setting['displayLastOrderMessage'] = $settings['SETUP_SELF_KBN06_005'];
                $setting['displayAccountPrice'] = $settings['SETUP_SELF_KBN99_001'];
                $setting['hideCheckout'] = $settings['SETUP_SELF_KBN99_002'];
                $setting['updateTableTime'] = intval($settings['SETUP_SELF_KBN99_012']);
                $setting['countBimKbn1'] = $settings['CNT1'];
                $setting['countBimKbn2'] = $settings['CNT2'];
            }
        }

        //テーブル情報の取得
        $sql = <<<EOT
			select
				TBL_TBMCD
			,	TBL_FLG
			,	TBL_START_TIME
			,	TBL_NINZU
			,	TBL_MAN_NINZU
			,	TBL_WOMAN_NINZU
			,	TBL_XM11_CD
			,	TBL_ORDER_KINGAKU
			,	TBL_FREE
			,	TBL_DFREE
			,	TBL_YOYAKU_START_TIME
			,	TBL_YOYAKU_NINZU
			, TBL_FREE_START_TIME
			, TBL_YOBI_KBN3
			, TBL_YOBI_KBN4
			, TBL_YOBI_KBN6 as allYouCanEatLimit
			, TBL_YOBI_KBN7
			from
				TBL
			where
				TBL_TMCD = ?
				{$key}
			order by
				TBL_TBMCD ASC
EOT;
        $this->db->query($sql, $parameters);

        $result = $this->db->result_data();

        $exist = 0;

        $tblFlgChanged = false;

        $forCount = count($result);
        for ($i = 0; $i < $forCount; $i++) {
            $elapsedMinute = 0;
            if ((intval($result[$i]['TBL_FLG']) === 2 || intval($result[$i]['TBL_FLG']) === 5) &&
                (empty($result[$i]['TBL_YOBI_KBN4']) || intval($result[$i]['TBL_TBMCD']) === intval($result[$i]['TBL_YOBI_KBN4']))) {
                $exist = 1;
                $elapsedMinute = $this->keikaJikan(empty($result[$i]['TBL_FREE_START_TIME']) ? $result[$i]['TBL_START_TIME'] : $result[$i]['TBL_FREE_START_TIME']);
            }

            $result[$i]['elapsedMinute'] = $elapsedMinute;
            if (isset($setting['displayLimit'])) {
                $result[$i]['displayLimit'] = (intval($setting['displayLimit']) === 1
                    && intval($result[$i]['TBL_FREE']) === 1) ? 1 : 0;
                $updateTableTime = $setting['updateTableTime'];
                if (empty($result[$i]['allYouCanEatLimit'])) {
                    $result[$i]['allYouCanEatLimit'] = $setting['allYouCanEatLimit'];
                } else {
                    if ($updateTableTime > 0) {
                        $updateTableTime += $result[$i]['allYouCanEatLimit'];
                    }
                    $result[$i]['allYouCanEatLimit'] += $setting['allYouCanEatLimit'];
                }
                $result[$i]['limitAlertTime'] = $setting['limitAlertTime'];
                $result[$i]['displayLastOrderMessage'] = $setting['displayLastOrderMessage'];
                $result[$i]['displayAccountPrice'] = intval($setting['displayAccountPrice']) === 0;
                $result[$i]['hideCheckout'] = intval($setting['hideCheckout']) === 1;

                if ($updateTableTime > 0 && $elapsedMinute >= $updateTableTime && intval($result[$i]['TBL_YOBI_KBN7']) !== 1 && intval($result[$i]['TBL_YOBI_KBN7']) !== 9) {
                    $updateSql = 'UPDATE TBL SET TBL_YOBI_KBN7 = 1 WHERE TBL_TMCD = ? AND TBL_TBMCD = ?';
                    $this->db->query($updateSql, array(TM_CD, $result[$i]['TBL_TBMCD']));
                }

                if ($result[$i]['allYouCanEatLimit'] > 0 && $result[$i]['displayLimit'] === 1 && $result[$i]['TBL_FLG'] == "2") {
                    $limit = intval($result[$i]['allYouCanEatLimit']) - $elapsedMinute;
                    if ($limit < 0) {
                        if (intval($setting['timeOverProcess']) === 1) {
                            if (empty($result[$i]['TBL_YOBI_KBN4']) || intval($result[$i]['TBL_TBMCD']) === intval($result[$i]['TBL_YOBI_KBN4'])) {
                                //チェックアウト処理
                                $this->checkout();
                            }
                            $result[$i]['TBL_FLG'] = '5';
                            $result[$i]['TBL_YOBI_KBN1'] = '1';
                        } elseif (intval($setting['timeOverProcess']) === 2) {
                            //時間切れカテゴリ切替指示
                            $result[$i]['timeoutChangeMenuCategory'] = 1;
                        }
                    }
                }
                $result[$i]['categoryLimited'] = $result[$i]['TBL_YOBI_KBN3'];
            }

            if ($clientTableExist === false && $exist === 1 && $result[$i]['TBL_TBMCD'] * 1 === $tableNo) {
                //フラグが変わったので商品情報更新
                $tblFlgChanged = true;
            }
        }
        foreach ($result as $index => $item) {
            if (intval($item['TBL_YOBI_KBN4']) !== intval($item['TBL_TBMCD'])) {
                foreach ($result as $parent_item) {
                    if (intval($item['TBL_YOBI_KBN4']) === intval($parent_item['TBL_TBMCD'])) {
                        $target_table_no = $item['TBL_TBMCD'];
                        $result[$index] = $parent_item;
                        $result[$index]['TBL_TBMCD'] = $target_table_no;
                        break;
                    }
                }
            }
        }

        $this->data['exist'] = $exist;
        $this->data['tables'] = $result;

        if ($tblFlgChanged) {
            $itemController = new ItemController();
            $itemController->init();
            $itemController->select();
            $itemController->release();
            $this->data = array_merge($this->data, $itemController->data);
        }

        $this->getFreeName();

        $this->valid = true;
    }

    public function checkout($tbmcd = false)
    {
        //店舗コード
        $parameters = array(
            TM_CD
        );

        //テーブル番号がわたってきているかチェック
        if ($tbmcd === false && isset($_REQUEST['key']) && is_numeric($_REQUEST['key'])) {
            $tbmcd = $_REQUEST['key'];
        }
        $tbmcd = intval($tbmcd);

        if ($tbmcd > 0) {
            $parameters[] = $tbmcd;
            $key = "AND TBL_TBMCD = ?";

            //グループ化されていないかチェック
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
            $this->db->query($sql, array(TM_CD, $tbmcd));
            $result = $this->db->result_data();
            if (count($result) > 0) {
                $row = array_shift($result);
                if (!empty($row['TBL_YOBI_KBN4'])) {
                    $parameters[] = $row['TBL_YOBI_KBN4'];
                    $key = "AND (TBL_TBMCD = ? OR TBL_YOBI_KBN4 = ?)";
                }
            }

            $sql = <<<EOT
			UPDATE
				TBL
			SET
				TBL_FLG = '5'
				,TBL_YOBI_KBN1 = '1'
				,TBL_YOBI_KBN5 = '1'
			WHERE
				TBL_TMCD = ?
				{$key}
			;
EOT;
            $this->db->query($sql, $parameters);

            $this->valid = true;
        }
    }

    public function getFreeName()
    {
        $parameters = array(
            TM_CD
        );

        $this->data['orderFreeName'] = array();

        //テーブル番号がわたってきているかチェック
        if (isset($_REQUEST['key']) && is_numeric($_REQUEST['key'])) {
            $arr_sm_free = $this->getTableFreeFlg(intval($_REQUEST['key']));

            if (!empty($arr_sm_free)) {
                //Search order
                $tableNo = $arr_sm_free[0];
                $arr_sm_free = join(",", $arr_sm_free[1]);
                if (!empty($arr_sm_free)) {

                    //まずは親CD取得
                    $oya_cds = array();
                    $sql = <<<EOT
            SELECT
              SM_CD,
              SM_FOOD_KBN,
              SM_FREE_OYACD
            FROM
                JD
            INNER JOIN
              SM
            ON (
              JD_SMCD = SM_CD
            )
            WHERE
                JD_TMCD = ?
                AND
                JD_TBMCD = ?
                AND
                JD_GYO <> 0
                AND
                JD_SURYO > 0
                AND
                JD_KBN <> 2
                AND
                SM_FREE IN ({$arr_sm_free})
EOT;
                    $this->db->query($sql, array(TM_CD, $tableNo));
                    $result = $this->db->result_data();

                    foreach ($result as $row) {
                        if (intval($row['SM_FOOD_KBN']) === 1) {
                            $oya_cds[$row['SM_FREE_OYACD']] = $row['SM_FREE_OYACD'];
                        } else {
                            $oya_cds[$row['SM_CD']] = $row['SM_CD'];
                        }
                    }

                    if (!empty($oya_cds)) {
                        $oya_cd = join(",", $oya_cds);
                        $sql = <<<EOT
				select
				  SM_SELF_SMNMJ
				, SM_L_SELF_SMNMJ01
				, SM_L_SELF_SMNMJ02
				, SM_L_SELF_SMNMJ03
				from
				  SM
				left join
				  SM_L
				  ON (
				  SM_CD = SM_L_CD
				  AND
				  SM_L_DEL_KBN = 0
				  )
				where
					SM_CD in ({$oya_cd})
                ORDER BY
                  SM_FOOD_KBN ASC
EOT;
                        $this->db->query($sql);
                        $result = $this->db->result_data();

                        $freeName = array();
                        foreach ($result as $row) {
                            $freeName[] = array(
                                'ja' => $row['SM_SELF_SMNMJ'],
                                'en' => $row['SM_L_SELF_SMNMJ01'],
                                'cn' => $row['SM_L_SELF_SMNMJ02'],
                                'kr' => $row['SM_L_SELF_SMNMJ03']
                            );
                        }
                        if (!empty($freeName)) {
                            $freeNameLangs = array();
                            foreach ($freeName as $value) {
                                foreach ($value as $lang => $item) {
                                    if (empty($freeNameLangs[$lang])) {
                                        $freeNameLangs[$lang] = '';
                                    } else {
                                        $freeNameLangs[$lang] .= ',';
                                    }
                                    $freeNameLangs[$lang] = $item;
                                }
                            }
                            $this->data['orderFreeName'] = $freeNameLangs;
                        }
                    }
                }
            }
        }

        $this->valid = true;
    }

    private function getTableFreeFlg($tableNo)
    {
        $arr_sm_free = array();
        $sql = <<<EOT
SELECT
  TBL_FREE
  ,TBL_DFREE
  ,TBL_YOBI_KBN4
FROM
  TBL
WHERE 
  TBL_TMCD = ?
AND 
  TBL_TBMCD = ?
EOT;
        $this->db->query($sql, array(TM_CD, $tableNo));
        $result = $this->db->result_data();
        if (count($result) > 0) {
            $row = array_shift($result);
            if (!empty($row['TBL_YOBI_KBN4']) && intval($tableNo) !== intval($row['TBL_YOBI_KBN4'])) {
                return $this->getTableFreeFlg($row['TBL_YOBI_KBN4']);
            }

            if ((int)$row['TBL_FREE'] == 1) {
                $arr_sm_free[] = "'1' , '2'";
            }

            if ((int)$row['TBL_DFREE'] == 1) {
                $arr_sm_free[] = "'3'";
            }
        }
        return array($tableNo, $arr_sm_free);
    }
}