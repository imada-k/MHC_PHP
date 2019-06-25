<?php

/**
 * Base Action Controller
 *
 * @author Katsuhiro Masaki <hiro@digitaljet.co.jp>
 */
class BaseController
{

  //▼内部変数
  //ビューに引き渡すデータ
  var $data = array();
  //DBアクセスを行うかどうかのフラグ
  var $isDb = false;
  //DB登録を行うかどうかのフラグ
  var $isDbRegister = false;
  //DBアクセスインスタンス
  var $db = false;
  //処理正常終了フラグ
  var $valid = false;
  //処理日時
  var $procTime = 0;
  //▲内部変数

  /**
   * コンストラクタ
   */
  function BaseController()
  {
  }

  /**
   * 初期化処理
   */
  function init()
  {
    $this->procTime = mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y'));
    if ($this->isDb) {
      $this->db = new DbMssql($this->isDbRegister);
    }
  }

  /**
   * 解放
   */
  function release()
  {
    if ($this->db) {
      $this->db->close();
    }
  }

  function keikaJikan($targetTime)
  {
    if ($targetTime == '') {
      $targetTime = 0;
    } else if (strlen($targetTime) == 19) {
      $targetTime = substr($targetTime, 11, 2) . substr($targetTime, 14, 2);
    }
    $targetTime = sprintf("%04d", $targetTime);

    //検索対象の時間を生成
    $targetDate = mktime(substr($targetTime, 0, 2), substr($targetTime, 2, 2), 0, date('n', $this->procTime), date('j', $this->procTime), date('Y', $this->procTime));

    if (date('Hi', $this->procTime) < CLOSE_TIME && $targetTime > CLOSE_TIME) {
      $targetDate -= 86400;
    }

    $keika = floor(($this->procTime - $targetDate) / 60) - 1;
	  if ($keika < 0) {
		  $keika = 0;
	  }

    return $keika;
  }

  function formatJikan($targetTime)
  {
    if ($targetTime == '') {
      $targetTime = 0;
    }
    $targetTime = sprintf("%04d", $targetTime);

    return substr($targetTime, 0, 2) . ":" . substr($targetTime, 2, 2);
  }

  function createGazo($data)
  {
    $size = 512;

    //デコード
    $_gazo = base64_decode(str_replace(' ', '+', $data));

    //ファイル名
    $_filename = md5($_gazo) . '.bmp';

    $_base = imagecreatefromstring($_gazo);

    $w = imagesx($_base);
    $h = imagesy($_base);
    $ratio = $h / $w;

    //新しいイメージ準備
    $imgNew = imagecreate($size, $size * $ratio);

    imagecopyresized($imgNew, $_base, 0, 0, 0, 0, $size, $size * $ratio, 300, 300 * $ratio);

    //保存
    $this->imagebmp($imgNew, CONFIGURE_GAZO_DIR . DS . $_filename);

    return $_filename;
  }

  function imagebmp(&$im, $filename = '', $bit = 8, $compression = 0)
  {
    if (!in_array($bit, array(1, 4, 8, 16, 24))) {
      $bit = 8;
    }
    $bits = pow(2, $bit);

    imagetruecolortopalette($im, true, $bits);
    $width = imagesx($im);
    $height = imagesy($im);
    $colors_num = imagecolorstotal($im);

    if ($bit <= 8) {
      $rgb_quad = '';
      for ($i = 0; $i < $colors_num; $i++) {
        $colors = imagecolorsforindex($im, $i);
        $rgb_quad .= chr($colors['blue']) . chr($colors['green']) . chr($colors['red']) . "\0";
      }

      $bmp_data = '';
      if ($compression == 0 || $bit < 8) {
        if (!in_array($bit, array(1, 4, 8))) {
          $bit = 8;
        }
        $compression = 0;

        $extra = '';
        $padding = 4 - ceil($width / (8 / $bit)) % 4;
        if ($padding % 4 != 0) {
          $extra = str_repeat("\0", $padding);
        }

        for ($j = $height - 1; $j >= 0; $j--) {
          $i = 0;
          while ($i < $width) {
            $bin = 0;
            $limit = $width - $i < 8 / $bit ? (8 / $bit - $width + $i) * $bit : 0;
            for ($k = 8 - $bit; $k >= $limit; $k -= $bit) {
              $index = imagecolorat($im, $i, $j);
              $bin |= $index << $k;
              $i++;
            }
            $bmp_data .= chr($bin);
          }

          $bmp_data .= $extra;
        }
      } else if ($compression == 1 && $bit == 8) {
        for ($j = $height - 1; $j >= 0; $j--) {
          $last_index = "\0";
          $same_num = 0;
          for ($i = 0; $i <= $width; $i++) {
            $index = imagecolorat($im, $i, $j);
            if ($index !== $last_index || $same_num > 255) {
              if ($same_num != 0) {
                $bmp_data .= chr($same_num) . chr($last_index);
              }
              $last_index = $index;
              $same_num = 1;
            } else {
              $same_num++;
            }
          }
          $bmp_data .= "\0\0";
        }

        $bmp_data .= "\0\1";
      }
      $size_quad = strlen($rgb_quad);
      $size_data = strlen($bmp_data);
    } else {
      $extra = '';
      $padding = 4 - ($width * ($bit / 8)) % 4;
      if ($padding % 4 != 0) {
        $extra = str_repeat("\0", $padding);
      }
      $bmp_data = '';
      for ($j = $height - 1; $j >= 0; $j--) {
        for ($i = 0; $i < $width; $i++) {
          $index = imagecolorat($im, $i, $j);
          $colors = imagecolorsforindex($im, $index);

          if ($bit == 16) {
            $bin = 0 << $bit;
            $bin |= ($colors['red'] >> 3) << 10;
            $bin |= ($colors['green'] >> 3) << 5;
            $bin |= $colors['blue'] >> 3;
            $bmp_data .= pack("v", $bin);
          } else {
            $bmp_data .= pack("c*", $colors['blue'], $colors['green'], $colors['red']);
          }

        }
        $bmp_data .= $extra;
      }
      $size_quad = 0;
      $size_data = strlen($bmp_data);
      $colors_num = 0;
    }
    $file_header = "BM" . pack("V3", 54 + $size_quad + $size_data, 0, 54 + $size_quad);
    $info_header = pack("V3v2V*", 0x28, $width, $height, 1, $bit, $compression, $size_data, 0, 0, $colors_num, 0);

    if ($filename != '') {
      $fp = fopen($filename, "wb");
      fwrite($fp, $file_header);
      fwrite($fp, $info_header);
      fwrite($fp, $rgb_quad);
      fwrite($fp, $bmp_data);
      fclose($fp);
      return 1;
    }

    header("Content-Type: image/bmp");
    echo $file_header . $info_header;
    echo $rgb_quad;
    echo $bmp_data;

    return 1;
  }
}

?>