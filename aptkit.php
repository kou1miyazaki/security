<?php
//-------------------------------------------------------------------
// 標的型攻撃メール対応訓練実施キット 開封者情報メール送信プログラム
//
// 本プログラムは、訓練対象者のパソコンから直接、開封者情報を送付
// することができない場合の代替策、また、Webビーコン形式にて訓練を
// 実施する場合の開封者情報取得用として、Webサーバから開封者情報を
// 送付するプログラムとなります。
//  
// 本プログラムを利用するには、以下の条件を満たす必要があります。
// １．Webサーバ側でPHPが使用できること、
// ２．pearのMailモジュールが使用できるようになっていること
// ３．リダイレクト先となるWebページがあること
// ４．GDモジュールが利用可能となっていること
// ５．mbstringモジュールが利用可能となっていること
//-------------------------------------------------------------------

  //---------------------------------------------------------
  // 値の設定
  //---------------------------------------------------------
    $pass = "jE35Tf";   // 不正アクセス防止用のキーワード
    $URL = "http://www.google.com/";  // リダイレクト先のURL

  //--------------------------------------
  // エラー情報を出力しない設定にします
  //--------------------------------------
    ini_set('display_errors',0);

  //--------------------------------------
  // アクセス元情報とキー情報を取得
  //--------------------------------------
    if( !($_SERVER['HTTP_X_FORWARDED_FOR'] == "") ){
      $IP = $_SERVER['HTTP_X_FORWARDED_FOR'];

    } elseif( !($_SERVER['HTTP_CLIENT_IP'] == "") ){
      $IP = $_SERVER['HTTP_CLIENT_IP'];

    } else {
      $IP = $_SERVER['REMOTE_ADDR'];

    }

    //$HOST = $_SERVER['HTTP_HOST'];  // ホスト名(gethostbyaddrかどちらかを選択)
    $HOST = gethostbyaddr($IP);     // ホスト名
    $data = "";

  //--------------------------------------
  // GETのデータを分解
  //--------------------------------------
    $str = $_SERVER["QUERY_STRING"];
    $datas = (strpos($str, '&') === false) ? array($str) : explode('&', $str);
    $name = array();
    $rtn = array();

    $i = 0;
    foreach($datas as $data) {
      if (strpos($data, '=') === false) {
          $name[$i] = "none";
          $array[$i] = urldecode($data);
      } else {
          $_tmp = explode('=', $data, 2);
          $name[$i] = urldecode($_tmp[0]);
          $rtn[$i] = urldecode($_tmp[1]);
      }
      $i = $i + 1;
    }

  //--------------------------------------
  // 秘密の合い言葉を取得
  //--------------------------------------
    if(isset($_GET["kwd"])){
      $kwd  = $_GET["kwd"];
    }

  //----------------------------------------------------------
  // キーワードチェック
  // 不正アクセス防止のため、キーワードが一致しない場合は
  // 画像データのみを返して処理を終了します
  //----------------------------------------------------------
    if( !($kwd == $pass) ){
        header("Content-type: image/png");
        $img = imagecreate(1, 1); // 画像サイズを1pxに設定
        imagecolorallocate($img, 255, 255, 255); // 画像の色をRGB値で設定
        imagepng($img);
        imagedestroy($img);
        exit();
    }

  //--------------------------------------
  // 取得した情報を開封者情報として送信
  //--------------------------------------
    if($name[0] == "apps"){
       $body = "以下の方が添付ファイルを実行しました\nWebビーコン\n" . $IP . "\n\n" . $HOST ."\n" . $rtn[0] . "\n";
 
    } elseif($name[0] == "data"){
       $body = $rtn[0];

    } else {
       $body = "以下の方が添付ファイルを実行しました\nURLクリック\n" . $IP . "\n\n" . $HOST ."\n" . $rtn[0] . "\n";

    }

  //--------------------------------------
  // 形式に応じてデータを返却
  //--------------------------------------

  // テストモードの場合は取得できたIPアドレスを出力する
    if($name[0] == "apps" and $rtn[0] == "test"){
       header("Content-Type: text/html; charset=Shift_JIS");
       echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=shift_jis\"></head><body>";
       echo "<H2>aptkit.aspxの実行結果</H2>";
       echo "<p>取得できたIPアドレス：" . $IP . "</p>";
       echo "<p>取得できたホスト名：" . $HOST . "</p>";
       echo "<p>Body：" . $body . "</p>";
       echo "</body></html>";
       exit();

  // Webビーコン画像を出力
    } elseif($name[0] == "apps"){
       header("Content-type: image/png");
       $img = imagecreate(1, 1); // 画像サイズを1pxに設定
       imagecolorallocate($img, 255, 255, 255); // 画像の色をRGB値で設定
       imagepng($img);
       imagedestroy($img);

  // 空のページを出力
    } elseif($name[0] == "data"){
       header("Content-Type: text/html; charset=Shift_JIS");
       echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=shift_jis\"></head><body>";
       echo "</body></html>";

  // その他はリダイレクト先のページを出力
    } else {
       header("location: " . $URL);
    }

  //--------------------------------------
  // $bodyが空でなければメールを送信
  //--------------------------------------
    if( !($body == "") ){ 
       sendmail($body);
    }
    exit();

function sendmail($body){
  //---------------------------------------------------------
  // 送信情報の設定
  //---------------------------------------------------------
    $host = "smtp.hoge.co.jp";    // SMTPサーバー名
    $port = 587;                  // ポート番号
    $auth = true;                 // SMTP認証を使用するか否か
    $username = "";               // SMTP認証のユーザ名
    $password = "";               // SMTP認証のパスワード
    $to = "";                     // 送信先アドレス
    $from = "";                   // 送信元アドレス
    $subject = "開封者情報送付";  // メールのタイトル

  //---------------------------------------------------------
  // ここから下の記述については変更する必要はありませんが、
  // 文字化けの問題等が生じる場合は、適宜変更してください。
  //---------------------------------------------------------

  // PEAR::Mailのインクルード
    require_once("Mail.php");

  // 日本語メールを送る際に必要
    mb_language("Japanese");
    mb_internal_encoding("SJIS");

  // SMTPサーバーの情報を連想配列にセット
    $params = array(
             "host" => $host,
             "port" => $port,
             "auth" => $auth,
             "username" => $username,
             "password" => $password
    );

  // PEAR::Mailのオブジェクトを作成
  // ※バックエンドとしてSMTPを指定
    $mailObject = Mail::factory("smtp", $params);

  // 送信先のメールアドレス
    $recipients = $to;

  // メールヘッダ情報を連想配列としてセット
    $headers = array(
              "To" => $to, // →ここで指定したアドレスには送信されません
              "From" => $from,
              "Subject" => mb_encode_mimeheader($subject)
    );

  // 日本語なのでエンコード
    $body = mb_convert_encoding($body, "SJIS", "auto");

  // sendメソッドでメールを送信
    $mailObject->send($recipients, $headers, $body);

}

?>
