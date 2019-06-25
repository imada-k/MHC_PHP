<?php

/**
 * Item and Category getter
 *
 * @author Katsuhiro Masaki <hiro@digitaljet.co.jp>
 */
class ItemController extends BaseController
{

    /**
     * コンストラクタ
     */
    function ItemController()
    {
        $this->isDb = true;
    }

    /**
     * 検索
     */
    function select()
    {

        $tm_cd = TM_CD;

        //category infomation
        $sql = <<<EOT
select distinct
  CTM_SELF_PCD,
  CTM_SELF_PNAME
from
  CTM_SELF
where
  CTM_SELF_TMCD = ?
order by
  CTM_SELF_PCD asc
EOT;

        $this->db->query($sql, array($tm_cd));
        $dbCtmSelfs = $this->db->result_data();
        $ctmSelfs = array();
        foreach ($dbCtmSelfs as $row) {
            $ctmSelfs[] = array(
                'cd' => $row['CTM_SELF_PCD'],
                'name' => $row['CTM_SELF_PNAME']
            );
        }

        $this->data['ctmSelfs'] = $ctmSelfs;

        //settings for self order
        $sql = 'select ';
        for ($i = 1; $i <= 5; $i++) {
            $sql .= sprintf('SETUP_SELF_KBN01_%03d,', $i);
        }
        for ($i = 0; $i <= 9; $i++) {
            for ($ii = 1; $ii <= 3; $ii++) {
                $sql .= sprintf('SETUP_SELF_KBN02_0%d%d,', $i, $ii);
            }
        }
        for ($i = 1; $i <= 10; $i++) {
            $sql .= sprintf('SETUP_SELF_KBN03_%03d,', $i);
        }
        $sql .= 'SETUP_SELF_KBN05_001,';
        for ($i = 1; $i <= 5; $i++) {
            $sql .= sprintf('SETUP_SELF_KBN06_%03d,', $i);
        }
        for ($i = 1; $i <= 4; $i++) {
            $sql .= sprintf('SETUP_SELF_KBN07_%03d,', $i);
        }
        for ($i = 5; $i <= 13; $i++) {
            $sql .= sprintf('SETUP_SELF_KBN99_%03d,', $i);
        }
        $sql = substr($sql, 0, -1);
        $sql .= ' ,SETUP_SELF_KOMOKU99_002 ';
        $sql .= ' ,SETUP_SELF_KOMOKU99_003 ';
        $sql .= ' ,SETUP_SELF_KOMOKU99_004 ';
        $sql .= ' ,SETUP_SELF_KOMOKU99_005 ';
        $sql .= ' ,SETUP_SELF_KOMOKU99_006 ';
        $sql .= ' ,SETUP_SELF_KOMOKU99_007 ';
        $sql .= ' ,CNT1 ';
        $sql .= ' ,CNT2 ';
        $sql .= ' ,SETUP_SELF_KBN99_018 ';
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

        $this->db->query($sql, array($tm_cd));
        $settings = $this->db->result_data();
        $settings = array_shift($settings);
        $setting = array();
        $recommends = array();
        $rankings = array();

        if ($settings !== null) {
            $setting['orderLimitTotal'] = $settings['SETUP_SELF_KBN01_001'];
            $setting['orderLimitType'] = $settings['SETUP_SELF_KBN01_002'];
            $setting['orderLimitDrink'] = $settings['SETUP_SELF_KBN01_003'];
            $setting['orderLimitFood'] = $settings['SETUP_SELF_KBN01_004'];
            $setting['adImagePath'] = $settings['SETUP_SELF_KBN05_001'];
            $setting['multilingualItem'] = $settings['SETUP_SELF_KBN99_005'];
            $setting['languageEn'] = $settings['SETUP_SELF_KBN99_006'];
            $setting['languageCn'] = $settings['SETUP_SELF_KBN99_007'];
            $setting['languageKr'] = $settings['SETUP_SELF_KBN99_008'];
            $setting['languageCnn'] = $settings['SETUP_SELF_KBN99_018'];
            $setting['useLastOrder'] = $settings['SETUP_SELF_KBN99_010'];
            $setting['lastOrderLimitMinute'] = $settings['SETUP_SELF_KBN99_009'];
            $setting['orderLimitLastOrder'] = $settings['SETUP_SELF_KBN99_011'];
            $setting['checkoutInfoMessage'] = $settings['SETUP_SELF_KOMOKU99_002'];
            $setting['checkoutInfoMessage_en'] = $settings['SETUP_SELF_KOMOKU99_003'];
            $setting['checkoutInfoMessage_cn'] = $settings['SETUP_SELF_KOMOKU99_004'];
            $setting['checkoutInfoMessage_kr'] = $settings['SETUP_SELF_KOMOKU99_005'];
            $setting['hideCheckoutButton'] = $settings['SETUP_SELF_KBN99_013'];
            $setting['topInfoMessage'] = $settings['SETUP_SELF_KOMOKU99_006'];
            $setting['loginInfoMessage'] = $settings['SETUP_SELF_KOMOKU99_007'];
            $setting['checkoutInfoMessage_cn'] = $settings['SETUP_SELF_KOMOKU99_004'];
            $setting['countBimKbn1'] = $settings['CNT1'];
            $setting['countBimKbn2'] = $settings['CNT2'];

            for ($i = 0; $i <= 9; $i++) {
                $smCd = sprintf('SETUP_SELF_KBN02_0%d1', $i);
                if (!empty($settings[$smCd])) {
                    $recommends[$settings[$smCd]] = array(
                        'anotherShowMinuteFrom' => $settings[sprintf('SETUP_SELF_KBN02_0%d2', $i)],
                        'anotherShowMinuteTo' => $settings[sprintf('SETUP_SELF_KBN02_0%d3', $i)]
                    );
                }
            }
            for ($i = 1; $i <= 10; $i++) {
                $key = sprintf('SETUP_SELF_KBN03_%03d', $i);
                if (!empty($settings[$key])) {
                    $rankings[$settings[$key]] = $i;
                }
            }
            if (intval($settings['SETUP_SELF_KBN07_001']) === 1) {
                $ad_urls = array();
                for ($i = 2; $i <= 4; $i++) {
                    $_cd = sprintf('SETUP_SELF_KBN07_%03d', $i);
                    if (!empty($settings[$_cd])) {
                        $ad_urls[] = $settings[$_cd];
                    }
                }
                $setting['adUrls'] = $ad_urls;
            }
        }

        //テーブル番号がわたってきているかチェック
        $table_no = false;
        if (isset($_REQUEST['key']) && is_numeric($_REQUEST['key'])) {
            $table_no = $_REQUEST['key'] * 1;
        }

        //グループ化されていないかチェック
        $order_target_tbmcd = $table_no;
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
        $this->db->query($sql, array($tm_cd, $table_no));
        $result = $this->db->result_data();
        if (count($result) > 0) {
            $row = array_shift($result);
            if (!empty($row['TBL_YOBI_KBN4'])) {
                $order_target_tbmcd = $row['TBL_YOBI_KBN4'];
            }
        }

        $validCategories = array();
        $sql = <<<EOT
    select
      CTM_CHANGE_CD1
    , CTM_CHANGE_CD2
    from
      CTM_CHANGE
    where
      CTM_CHANGE_TMCD = ?
      and
      CTM_CHANGE_KBN = 0
EOT;
        $this->db->query($sql, array($tm_cd));
        $res = $this->db->result_data();
        if (!empty($res)) {
            foreach ($res as $row) {
                $validCategories[$row['CTM_CHANGE_CD1']] = $row['CTM_CHANGE_CD2'];
            }
        }

        //カテゴリーの取得
        $menuCategory = false;
        $conditions = array();
        if (isset($_REQUEST['menu_category']) && is_numeric($_REQUEST['menu_category'])) {
            $menuCategory = $_REQUEST['menu_category'] * 1;
            if ($menuCategory !== false) {
                $conditions[] = $menuCategory;
            }
        }

        if ($menuCategory === false) {
            $sql = <<<EOT
			select
				CTM_CD1
			,	CTM_CD2
			,	CTM_NMJ
			,	CTM_SELF_NMJ
			,   CTM_L_SELF_NMJ01
			,   CTM_L_SELF_NMJ02
			,   CTM_L_SELF_NMJ03
			,   CTM_L_YOBI_KOMOKU1
			from
				CTM
            left join
                CTM_L on(
                CTM_TMCD = CTM_L_TMCD
                and
                CTM_CD1 = CTM_L_CD1
                and
                CTM_CD2 = CTM_L_CD2
                and
                CTM_L_DEL_KBN = 0
            )
			where
				CTM_SELF_HYOJI_KBN = 0
			order by
				CTM_CD1 ASC
			,	CTM_CD2 ASC
EOT;
        } else {
            $sql = <<<EOT
			select
				CTM_SELF_CD1 as CTM_CD1
			,	CTM_SELF_CD2 as CTM_CD2
			,	CTM_SELF_NMJ as CTM_NMJ
			from
				CTM_SELF
			where
				CTM_SELF_PCD = ?
			order by
				CTM_SELF_CD1 ASC
			,	CTM_SELF_CD2 ASC
EOT;
        }
        $this->db->query($sql, $conditions);
        $category = $this->db->result_data();

        $categories = array();

        $index = 1;
        foreach ($category as $row) {
            $data = array();
            $identifier = $this->_convertCategoryIdentifier($row['CTM_CD1'], $row['CTM_CD2']);
            $data['identifier'] = $identifier;
            $data['sortOrder'] = $index++;
            $data['name'] = !empty($row['CTM_SELF_NMJ']) ? $row['CTM_SELF_NMJ'] : $row['CTM_NMJ'];
            $data['name_en'] = $row['CTM_L_SELF_NMJ01'];
            $data['name_cn'] = $row['CTM_L_SELF_NMJ02'];
            $data['name_kr'] = $row['CTM_L_SELF_NMJ03'];
            $data['name_cnn'] = $row['CTM_L_YOBI_KOMOKU1'];
            $data['superItemCategory'] = (!empty($row['CTM_CD2'])) ? $row['CTM_CD1'] : '';
            $data['limited'] = (empty($validCategories) || (isset($validCategories[$row['CTM_CD1']]) && (intval($validCategories[$row['CTM_CD1']]) === intval($row['CTM_CD2']) || intval($validCategories[$row['CTM_CD1']]) === 0))) ? 0 : 1;
            $categories[] = $data;
        }

        $this->data['categories'] = $categories;

        $courses = array();
        $courseFoodKbns = array();

        if ($table_no !== false) {

            $arr_sm_free = array();
            //Search table users
            $sql = <<<EOT
        select
          TBL_NINZU
        from
          TBL
        where
          TBL_TMCD = ?
          AND
          TBL_TBMCD = ?
        order by
          TBL_TBMCD ASC
EOT;
            $this->db->query($sql, array($tm_cd, $order_target_tbmcd));
            $result = $this->db->result_data();

            if (count($result) > 0) {
                foreach ($result as $row) {
                    if (intval($setting['orderLimitDrink']) === 99) {
                        $setting['orderLimitDrink'] = $row['TBL_NINZU'];
                    }
                    if (intval($setting['orderLimitFood']) === 99) {
                        $setting['orderLimitFood'] = $row['TBL_NINZU'];
                    }
                }
            } else {
                if (intval($setting['orderLimitDrink']) === 99) {
                    $setting['orderLimitDrink'] = $row['TBL_NINZU'];
                }
                if (intval($setting['orderLimitFood']) === 99) {
                    $setting['orderLimitFood'] = $row['TBL_NINZU'];
                }
            }

            $sql = <<<EOT
        select
          TBL_FREE
          ,TBL_DFREE
        from
          TBL
        where
          TBL_TMCD = ?
          AND
          TBL_TBMCD = ?
        order by
          TBL_TBMCD ASC
EOT;
            $this->db->query($sql, array($tm_cd, $order_target_tbmcd));
            $result = $this->db->result_data();
            if (count($result) > 0) {
                $item = array_shift($result);

                if ((int)$item['TBL_FREE'] == 1) {
                    $arr_sm_free[] = "'1' , '2'";
                }

                if ((int)$item['TBL_DFREE'] == 1) {
                    $arr_sm_free[] = "'3'";
                }
            }

            if (!empty($arr_sm_free)) {
                //Search order
                $arr_sm_free = join(",", $arr_sm_free);
                $sql = <<<EOT
				select
					JD_SEQ
				,	JD_SMCD
				, SM_FOOD_KBN
				from
					JD
				inner join
				  SM
				on (
				  JD_SMCD = SM_CD
				)
				where
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
					SM_FREE in ({$arr_sm_free})
        ORDER BY
          SM_FOOD_KBN ASC
EOT;

                $this->db->query($sql, array($tm_cd, $order_target_tbmcd));
                $result = $this->db->result_data();

                $orderSmCds = array();
                foreach ($result as $row) {
                    $orderSmCds[] = $row['JD_SMCD'];
                    $courseFoodKbns[$row['SM_FOOD_KBN']] = $row['SM_FOOD_KBN'];
                }

                if (count($orderSmCds) > 0) {
                    $keys = array();
                    for ($i = 0; $i < count($orderSmCds); $i++) {
                        $keys[] = '?';
                    }
                    $keys = implode(',', $keys);

                    $sql = <<<EOT
					select
						SFM_FREE_SMCD
					,	SFM_SMCD
					from
						SFM
					where
					  SFM_FREE_SMCD in ({$keys})
EOT;
                    $this->db->query($sql, $orderSmCds);

                    //扱い易いように加工
                    $result = $this->db->result_data();

                    foreach ($result as $row) {
                        $courses[] = $row['SFM_SMCD'];
                    }
                }
            }
        }

        $setting['allYouCanEatKbn'] = $courseFoodKbns;

        //サブメニュー情報を取得
        $sql = <<<EOT
			select
				SSM_KBN
			,	SSM_CD
			,	SSM_NMJ
			,	SSM_YOBI_KOMOKU1
			,	SSM_L_NMJ01
			,	SSM_L_NMJ02
			,	SSM_L_NMJ03
			from
				SSM
            left join
                SSM_L on(
                SSM_KBN = SSM_L_KBN
                and
                SSM_CD = SSM_L_CD
                and
                SSM_L_DEL_KBN = 0
            )
			order by
				SSM_KBN ASC
			,	SSM_CD ASC
EOT;

        $this->db->query($sql);

        //扱い易いように加工
        $result = $this->db->result_data();
        $taste = array();
        $taste_titles = array();

        foreach ($result as $row) {

            if (intval($row['SSM_CD']) === 0) {
                $taste_titles[$row['SSM_KBN']] = array(
                    'name' => $row['SSM_YOBI_KOMOKU1']
                ,
                    'name_en' => $row['SSM_L_NMJ01']
                ,
                    'name_cn' => $row['SSM_L_NMJ02']
                ,
                    'name_kr' => $row['SSM_L_NMJ03']
                );
                continue;
            }

            $taste_row = array();
            if (isset($taste[$row['SSM_KBN']])) {
                $taste_row = $taste[$row['SSM_KBN']];
            }

            $taste_row[] = array(
                'key' => $row['SSM_CD']
            ,
                'name' => $row['SSM_NMJ']
            ,
                'name_en' => $row['SSM_L_NMJ01']
            ,
                'name_cn' => $row['SSM_L_NMJ02']
            ,
                'name_kr' => $row['SSM_L_NMJ03']
            );
            $taste[$row['SSM_KBN']] = $taste_row;
        }

        $conditions = array($tm_cd);
        if ($menuCategory === false) {
            $sql = <<<EOT
			select
				STM_CTMCD1
			,	STM_CTMCD2
			,	STM_BAIKA
			,	STM_ZEIMAR_KBN
			,	SM_CD
			,	SM_NMJ
			,	SM_SELF_SMNMJ
			,	SM_FOOD_KBN
			,	SM_FREE
			,	SM_SSMKBN
			,	SM_IMAGE
			,	SM_DETAIL
			,	STM_YOBI_KOMOKU1
			,	SM_YOBI_KBN1
			,	SM_YOBI_KBN2
			,	SM_YOBI_KBN4
			,	SM_YOBI_KBN5
			,	SM_YOBI_KBN6
			,	SM_FREE_ADULT_SMCD
			,	SM_FREE_CHILD_SMCD
			,	SM_FREE_BABY_SMCD
			,	SM_FREE_SENIOR_SMCD
			,	SM_FREE_FREE_SMCD
			,	ISNULL(RD_ZAN, - 1) AS ZAN
			,   SM_L_SELF_SMNMJ01
			,   SM_L_SELF_SMNMJ02
			,   SM_L_SELF_SMNMJ03
			,   SM_L_DETAIL01
			,   SM_L_DETAIL02
			,   SM_L_DETAIL03
			from
				STM
			inner join
				SM on(
				STM.STM_SMCD = SM.SM_CD
			)
			left join
				RD on(
				STM.STM_SMCD = RD.RD_SMCD
			)
			left join
			    SM_L on(
			    SM.SM_CD = SM_L.SM_L_CD
			    and
			    SM_L_DEL_KBN = 0
            )
			where
				STM_TMCD = ?
				and
				SM_FREE != '2'
				and
				SM_DEL_KBN <> '1'
				and
				STM_DEL_KBN <> '1'
				and
				SM_SELF_HYOJI_KBN <> '1'
			order by
				STM_HYOJI,
				SM_CD
EOT;
        } else {
            $sql = <<<EOT
			select
				STM_SELF_CTMCD1 as STM_CTMCD1
			,	STM_SELF_CTMCD2 as STM_CTMCD2
			,	STM_BAIKA
			,	STM_ZEIMAR_KBN
			,	SM_CD
			,	SM_NMJ
			,	SM_SELF_SMNMJ
			,	SM_FOOD_KBN
			,	SM_FREE
			,	SM_SSMKBN
			,	SM_IMAGE
			,	SM_DETAIL
			,	STM_YOBI_KOMOKU1
			,	SM_YOBI_KBN1
			,	SM_YOBI_KBN2
			,	SM_YOBI_KBN4
			,	SM_YOBI_KBN5
			,	SM_YOBI_KBN6
			,	SM_FREE_ADULT_SMCD
			,	SM_FREE_CHILD_SMCD
			,	SM_FREE_BABY_SMCD
			,	SM_FREE_SENIOR_SMCD
			,	SM_FREE_FREE_SMCD
			,	ISNULL(RD_ZAN, - 1) AS ZAN
			,   SM_L_SELF_SMNMJ01
			,   SM_L_SELF_SMNMJ02
			,   SM_L_SELF_SMNMJ03
			,   SM_L_DETAIL01
			,   SM_L_DETAIL02
			,   SM_L_DETAIL03
			from
			  STM_SELF
      inner join
				STM on(
				STM_SELF.STM_SELF_TMCD = STM.STM_TMCD
				and
				STM_SELF.STM_SELF_SMCD = STM.STM_SMCD
			)
			inner join
				SM on(
				STM_SELF.STM_SELF_SMCD = SM.SM_CD
			)
			left join
				RD on(
				STM_SELF.STM_SELF_SMCD = RD.RD_SMCD
			)
			left join
			    SM_L on(
			    SM.SM_CD = SM_L.SM_L_CD
			    and
			    SM_L_DEL_KBN = 0
            )
			where
				STM_SELF_TMCD = ?
				and
				STM_SELF_PCD = ?
				and
				SM_FREE != '2'
				and
				SM_DEL_KBN <> '1'
				and
				STM_SELF_DEL_KBN <> '1'
				and
				SM_SELF_HYOJI_KBN <> '1'
			order by
				STM_SELF_HYOJI,
				SM_CD
EOT;
            $conditions[] = $menuCategory;
        }

        $this->db->query($sql, $conditions);

        $result = $this->db->result_data();
        $items = array();
        $sortOrder = 1;
        foreach ($result as $row) {

            $itemCategory = $this->_convertCategoryIdentifier($row['STM_CTMCD1'], $row['STM_CTMCD2']);
            $price = $row['STM_BAIKA'] * (1 + TAX);
            switch (intval($row['STM_ZEIMAR_KBN'])) {
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
            $item = array(
                'identifier' => $row['SM_CD']
            ,
                'sortOrder' => $sortOrder++
            ,
                'name' => !empty($row['SM_SELF_SMNMJ']) ? $row['SM_SELF_SMNMJ'] : $row['SM_NMJ'],
                'name_en' => $row['SM_L_SELF_SMNMJ01'],
                'name_cn' => $row['SM_L_SELF_SMNMJ02'],
                'name_kr' => $row['SM_L_SELF_SMNMJ03'],
                'message' => $row['SM_DETAIL']
            ,
                'message_en' => $row['SM_L_DETAIL01']
            ,
                'message_cn' => $row['SM_L_DETAIL02']
            ,
                'message_kr' => $row['SM_L_DETAIL03']
            ,
                'price' => $price
            ,
                'recommended' => $row['SM_YOBI_KBN2']
            ,
                'subIdentifier' => ''
            ,
                'subName' => ''
            ,
                'thumbnailImageFileName' => ''
            ,
                'imageFileName' => $row['SM_IMAGE']
            ,
                'itemCategory' => $itemCategory
            ,
                'superItem' => ''
            ,
                'ranking' => (!empty($rankings[$row['SM_CD']])) ? $rankings[$row['SM_CD']] : ''
            ,
                'foodKind' => $row['SM_FOOD_KBN']
            ,
                'allYouCanEat' => (count($courses) <= 0 || in_array($row['SM_CD'], $courses) || !in_array($row['SM_FOOD_KBN'], $courseFoodKbns))
            ,
                'soldOut' => (intval($row['ZAN']) === 0)
            ,
                'anotherShowMinuteFrom' => -1
            ,
                'anotherShowMinuteTo' => -1
            ,
                'minimumOrder' => intval($row['SM_YOBI_KBN1'])
            ,
                'orderNumber' => is_numeric($row['STM_YOBI_KOMOKU1']) ? intval($row['STM_YOBI_KOMOKU1']) : -1
            ,
                'setTea' => is_numeric($row['SM_YOBI_KBN5']) ? ( intval($row['SM_YOBI_KBN5']) == 1 ? 1 : -1) : -1
            ,
                'isBlendTea' => is_numeric($row['SM_YOBI_KBN5']) ? ( intval($row['SM_YOBI_KBN5']) == 2 ? 1 : -1) : -1
            ,
                'isHiddenTea' => is_numeric($row['SM_YOBI_KBN5']) ? ( intval($row['SM_YOBI_KBN5']) == 3 ? 1 : -1) : -1
            ,
                'isSetBlendTeaMenu' => is_numeric($row['SM_YOBI_KBN6']) ? ( intval($row['SM_YOBI_KBN6']) ) : -1
            ,
                'isHiddenMenu' => is_numeric($row['SM_YOBI_KBN6']) ? ( intval($row['SM_YOBI_KBN6']) > 0 ? 1 : -1) : -1
            );
            if (!empty($recommends[$item['identifier']])) {
                $item = array_merge($item, $recommends[$item['identifier']]);
            }

            if (isset($taste[$row['SM_SSMKBN']]) && intval($row['SM_YOBI_KBN4']) !== 1) {
                $sub_item_base = $item;
                $item['ranking'] = '';
                $item['anotherShowMinuteFrom'] = -1;
                $item['anotherShowMinuteTo'] = -1;
                if (isset($taste_titles[$row['SM_SSMKBN']])) {
                    $item['subButtonTitle'] = $taste_titles[$row['SM_SSMKBN']]['name'];
                    $item['subButtonTitleEn'] = $taste_titles[$row['SM_SSMKBN']]['name_en'];
                    $item['subButtonTitleCn'] = $taste_titles[$row['SM_SSMKBN']]['name_cn'];
                    $item['subButtonTitleKr'] = $taste_titles[$row['SM_SSMKBN']]['name_kr'];
                }
                $items[] = $item;
                $isFirst = true;
                foreach ($taste[$row['SM_SSMKBN']] as $key => $value) {
                    $sub_item = $sub_item_base;
                    $sub_item['subIdentifier'] = $value['key'];
                    $sub_item['subName'] = $value['name'];
                    $sub_item['subNameEn'] = $value['name_en'];
                    $sub_item['subNameCn'] = $value['name_cn'];
                    $sub_item['subNameKr'] = $value['name_kr'];
                    $sub_item['superItem'] = $item['identifier'];
                    $sub_item['sortOrder'] = $sortOrder++;
                    $sub_item['orderNumber'] = -1;
                    if (!$isFirst) {
                        $sub_item['ranking'] = '';
                        $sub_item['anotherShowMinuteFrom'] = -1;
                        $sub_item['anotherShowMinuteTo'] = -1;
                    }
                    $isFirst = false;
                    $items[] = $sub_item;
                }
            } else {
                $items[] = $item;
            }
        }

        $sql_keys = array(
            'parent_key' => 'SSSM_SMCD',
            'group_key' => 'SSSM_GROUP_CD',
            'group_row' => 'SSSM_GYO',
            'item_key' => 'SSSM_SET_SMCD',
            'possible_count_of_order' => 'SSSM_ORDER_SURYO',
            'button_name' => 'SSSM_YOBI_KOMOKU1',
            'fix_parent_count' => 'SSSM_YOBI_KBN1',
            'button_name_en' => 'SSSM_L_NMJ01',
            'button_name_cn' => 'SSSM_L_NMJ02',
            'button_name_kr' => 'SSSM_L_NMJ03',
        );

        $sql_selects = '';
        foreach ($sql_keys as $key => $value) {
            if (!empty($sql_selects)) {
                $sql_selects .= ' , ';
            }
            $sql_selects .= "{$value} as {$key}";
        }

        $sql = <<<EOT
      select
        {$sql_selects}
      from
        SSSM
        LEFT JOIN
        SSSM_L
        ON (
        SSSM_SMCD = SSSM_L_SMCD
        )
      where
        SSSM_TMCD = {$tm_cd}
        and
        SSSM_DEL_KBN = 0
      order by
        parent_key asc,
        group_key asc,
        group_row asc
EOT;

        $this->db->query($sql);
        $result = $this->db->result_data();

        $set_menus = array();
        $setMenuItemCds = array();

        foreach ($result as $row) {
            $set_menu = array();
            $parent_key = $row['parent_key'];
            if (isset($set_menus[$parent_key])) {
                $set_menu = $set_menus[$parent_key];
            }
            $group_menu = array(
                'item_keys' => array(),
                'possible_count_of_order' => 0
            );
            $group_key = $row['group_key'];
            if (isset($set_menu[$group_key])) {
                $group_menu = $set_menu[$group_key];
            }
            $group_menu['item_keys'][] = $row['item_key'];
            $group_menu['possible_count_of_order'] = $row['possible_count_of_order'];

            if (intval($row['group_row']) === 1 && intval($row['group_key']) === 1) {
                $group_menu['button_name'] = $row['button_name'];
                $group_menu['button_name_en'] = $row['button_name_en'];
                $group_menu['button_name_cn'] = $row['button_name_cn'];
                $group_menu['button_name_kr'] = $row['button_name_kr'];
                $group_menu['fix_parent_count'] = intval($row['fix_parent_count']) === 1;
            }

            $set_menu[$group_key] = $group_menu;
            $set_menus[$parent_key] = $set_menu;
            $setMenuItemCds[$row['item_key']] = $row['item_key'];
        }
        foreach ($set_menus as $parent_key => $set_menu) {
            $set_menus[$parent_key] = array_values($set_menu);
        }

        // ■ セットメニュー用の商品データを取得
        $setMenuIns = join(',', $setMenuItemCds);
        $setMenuItemItems = array();
        if (!empty($setMenuIns)) {
            $sql = <<<EOT
			select
			    STM_BAIKA
			,   STM_ZEIMAR_KBN 
			,	SM_CD
			,	SM_NMJ
			,	SM_SELF_SMNMJ
			,   SM_L_SELF_SMNMJ01
			,   SM_L_SELF_SMNMJ02
			,   SM_L_SELF_SMNMJ03
			from
				STM
			inner join
				SM on(
				STM.STM_SMCD = SM.SM_CD
			)
			left join
			    SM_L on(
			    SM.SM_CD = SM_L.SM_L_CD
			    and
			    SM_L_DEL_KBN = 0
            )
			where
				STM_TMCD = ?
				and
				SM_DEL_KBN <> '1'
				and
				STM_DEL_KBN <> '1'
				and
				SM_SELF_HYOJI_KBN <> '1'
				AND 
				SM_CD in ({$setMenuIns})
			order by
				STM_HYOJI,
				SM_CD
EOT;
            $this->db->query($sql, array($tm_cd));

            $result = $this->db->result_data();
            foreach ($result as $row) {
                $price = $row['STM_BAIKA'] * (1 + TAX);
                switch (intval($row['STM_ZEIMAR_KBN'])) {
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
                $item = array(
                    'name' => !empty($row['SM_SELF_SMNMJ']) ? $row['SM_SELF_SMNMJ'] : $row['SM_NMJ'],
                    'name_en' => $row['SM_L_SELF_SMNMJ01'],
                    'name_cn' => $row['SM_L_SELF_SMNMJ02'],
                    'name_kr' => $row['SM_L_SELF_SMNMJ03'],
                    'price' => $price
                );
                $setMenuItemItems[$row['SM_CD']] = $item;
            }
        }

        $this->data['items'] = $items;
        $this->data['settings'] = $setting;
        $this->data['set_menus'] = $set_menus;
        $this->data['set_menu_items'] = $setMenuItemItems;

        $this->bim();

        $this->valid = true;
    }

    function bim()
    {
        $sql = <<<EOT
			select
			    BIM_KBN
			,	BIM_CD
			,	BIM_NMJ
			,   BIM_NMJ01
			,   BIM_NMJ02
			,   BIM_NMJ03
			,   BIM_NMJ04
			,   BIM_HYOJI
			,   BIM_YOBI_KBN1
			from
				BIM
			where
				BIM_DEL_KBN <> '1'
			order by
				BIM_HYOJI,
				BIM_CD
EOT;
        $this->db->query($sql);

        $result = $this->db->result_data();
        $bims = array();
        foreach ($result as $row) {
            $bim = array(
                'kbn' => $row['BIM_KBN'],
                'identifier' => $row['BIM_CD'],
                'name' => $row['BIM_NMJ'],
                'name_en' => $row['BIM_NMJ01'],
                'name_cn' => $row['BIM_NMJ02'],
                'name_kr' => $row['BIM_NMJ03'],
                'name_cnn' => $row['BIM_NMJ04'],
                'sortOrder' => $row['BIM_HYOJI'],
                'isHiddenKbn' => $row['BIM_YOBI_KBN1']
            );
            $bims[] = $bim;
        }

        $this->data['bims'] = $bims;

        $sql = <<<EOT
			select
			    BISM_KBN
			,	BISM_BIMCD
			,	BISM_CD
			,	BISM_NMJ
			,   BISM_NMJ01
			,   BISM_NMJ02
			,   BISM_NMJ03
			,   BISM_NMJ04
			,   BISM_HYOJI
			,   BISM_YOBI_KBN1
			from
				BISM
			where
				BISM_DEL_KBN <> '1'
			order by
				BISM_HYOJI,
				BISM_CD
EOT;
        $this->db->query($sql);

        $result = $this->db->result_data();
        $bisms = array();
        foreach ($result as $row) {
            if (empty($bisms[$row['BISM_KBN']])) {
                $bisms[$row['BISM_KBN']] = array();
            }
            if (empty($bisms[$row['BISM_KBN']][$row['BISM_BIMCD']])) {
                $bisms[$row['BISM_KBN']][$row['BISM_BIMCD']] = array();
            }
            $bism = array(
                'kbn' => $row['BISM_KBN'],
                'identifier' => $row['BISM_CD'],
                'name' => $row['BISM_NMJ'],
                'name_en' => $row['BISM_NMJ01'],
                'name_cn' => $row['BISM_NMJ02'],
                'name_kr' => $row['BISM_NMJ03'],
                'name_cnn' => $row['BISM_NMJ04'],
                'sortOrder' => $row['BISM_HYOJI'],
                'isHiddenKbn' => $row['BISM_YOBI_KBN1'],
            );
            $bisms[$row['BISM_KBN']][$row['BISM_BIMCD']][] = $bism;
        }

        $this->data['bisms'] = $bisms;
    }

    function calcItem()
    {
        if (!isset($_REQUEST['bisps']) || !is_array($_REQUEST['bisps'])) {
            return;
        }
        if (!isset($_REQUEST['category']) || !is_numeric($_REQUEST['category'])) {
            return;
        }
        $this->valid = true;
        $this->data['cd'] = array();
        $this->data['contents'] = array();
        
        $bisps = $_REQUEST['bisps'];
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

        // 症状マスタより取得
        $sql = <<<EOT
        select
        BISP_SM2CD,
        BISP_POINT
        from
        BISP
        WHERE
          BISP_DEL_KBN = 0
        AND
          BISP_POINT > 0
        AND
EOT;
        $bisp_conditions = array();
        $bisp_results = array();
        foreach ($bisps as $bisp) {
            $conditions = explode('-', $bisp);
            if (count($conditions) !== 3) {
                continue;
            }
            if (!empty($bisp_conditions)) {
                $sql .= ' or ';
            }
            $sql .= " (  BISP_KBN = {$conditions[0]} ";
            $sql .= "and BISP_BIMCD = {$conditions[1]} ";
            $sql .= "and BISP_CD = {$conditions[2]}) ";
            $bisp_conditions += $conditions;
        }
        if (!empty($bisp_conditions)) {
            $this->db->query($sql);
            $result = $this->db->result_data();
            foreach ($result as $row) {
                if (empty($bisp_results[$row['BISP_SM2CD']])) {
                    $bisp_results[$row['BISP_SM2CD']] = 0;
                }
                $bisp_results[$row['BISP_SM2CD']] += $row['BISP_POINT'];
            }
        }
        
        // 商品マスタより取得
        if (!empty($bisp_results)) {
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
            $sm2cds = array_keys($bisp_results);
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
                    $sms[$row['SM_CD']]['point'] += $bisp_results[$row['SM_CD']];
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
                    // カテゴリがブレンド茶の時のみ物販のデータも取得
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
                        //return (intval($a['smcd']) > intval($b['smcd'])) ? 1 : -1;
                        return 1;
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
                $count = 0;
                $smcd0 = 0;
                $smcd1 = 0;
                foreach ($results as $sm_key => &$sm) {
                    $count++;
                    if ($count > 3) {
                        break;
                    }
                    if ($smcd0 == 0) {
                        $smcd0 = $sm[0];
                    } elseif ( $smcd0 == $sm[0] ) {
                        if ( $smcd1 == 0 ) {
                            $tmp = $sm[0];
                            $sm[0] = $sm[1];
                            $sm[1] = $sm[2];
                            $sm[2] = $tmp;
                            $smcd1 = $sm[0];
                         } elseif ($smcd1 == $sm[1]) {
                            $tmp = $sm[0];
                            $sm[0] = $sm[1];
                            $sm[1] = $sm[2];
                            $sm[2] = $tmp;
                            $tmp = $sm[0];
                            $sm[0] = $sm[1];
                            $sm[1] = $sm[2];
                            $sm[2] = $tmp;
                         }
                    }
                }
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

    function _convertCategoryIdentifier($code1, $code2)
    {
        $value = $code1;
        if (!empty($code2)) {
            $value .= sprintf('%010d', $code2);
        }
        return $value;
    }
}

?>
