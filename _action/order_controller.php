<?php

/**
 * Get Order history.
 *
 * @author Katsuhiro Masaki <hiro@digitaljet.co.jp>
 */
class OrderController extends BaseController
{

  /**
   * コンストラクタ
   */
  function OrderController()
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

    $tm_cd = TM_CD;

    //settings for self order
    $sql = 'select ';
    $sql .= 'SETUP_SELF_KBN04_001';
    $sql .= ' FROM SETUP_SELF WHERE SETUP_SELF_KEY = 1 AND SETUP_SELF_TMCD = ?';

    $this->db->query($sql, $parameters);
    $settings = $this->db->result_data();
    $settings = array_shift($settings);
    $staffCallSmCd = false;

    if ($settings !== NULL) {
      $staffCallSmCd = $settings['SETUP_SELF_KBN04_001'];
    }

    //テーブル番号がわたってきているかチェック
    $key = "";
    if (isset($_REQUEST['key']) && is_numeric($_REQUEST['key'])) {
      $parameters[] = $_REQUEST['key'] * 1;
      $key = "AND JD_TBMCD = ?";
    }

    if (!empty($staffCallSmCd)) {
      $parameters[] = $staffCallSmCd;
      $key .= " AND (JD_SMCD <> ? OR JD_SMCD is NULL)";
    }

    //オーダー情報の取得
    $sql = <<<EOT
			select 
				JD_SEQ
			,	JD_SMCD
			,	JD_TBMCD
			,	JD_KBN
			,	JD_SMNMJ
			,	JD_SURYO
			,	JD_SMCD_SUB1
			,	JD_SMCD_SUB5
			,	convert(varchar, JD_INS_DATE, 20) as ORDER_TIME
			,	(JD_TANKA + JD_TANKA_TAX) as JD_TANKA
			from 
				JD
			where
				JD_TMCD = ?
				AND
				JD_GYO <> 0
				AND
				(JD_STSM_GYO = 0 OR JD_STSM_GYO is NULL)
				{$key}
			order by 
				JD_INS_DATE DESC,
				JD_GYO ASC
EOT;

    $this->db->query($sql, $parameters);
    $result = $this->db->result_data();


    //扱いやすいように加工
    $orders = array();
    foreach ($result as $row) {
      $order_row = array();
      if (isset($order[$row['JD_TBMCD']])) {
        $order_row = $order[$row['JD_TBMCD']];
      }
      $val = array();
      $val['identifier'] = $row['JD_SMCD'];
      $val['name'] = $row['JD_SMNMJ'];
      $val['quantity'] = intval($row['JD_SURYO']);
      $val['orderTime'] = $row['ORDER_TIME'];
      $val['subIdentifier'] = $row['JD_SMCD_SUB1'];
      $val['price'] = $row['JD_TANKA'] * 1;
      $val['rowTotalPrice'] = 0;

      $orders[] = $val;
    }

    $sql = <<<EOT
			select 
				(JD_KINGAKU + JD_TAX) as JD_KINGAKU
			from 
				JD
			where
				JD_TMCD = ?
				AND
				JD_GYO = 0
				{$key}
			order by 
				JD_DENPYO_NO ASC,
				JD_DENPYO_NO2 ASC
EOT;

    $this->db->query($sql, $parameters);
    $result = $this->db->result_data();

    if (count($result) > 0) {
      $row = array_shift($result);
      $rowTotalPrice = $row['JD_KINGAKU'] * 1;
      if (count($orders) > 0) {
        $orders[0]['rowTotalPrice'] = $rowTotalPrice;
      }
    }

    $this->data['orders'] = $orders;

    $this->valid = true;
  }
}

?>
