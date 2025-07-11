<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);
?>
<header class="header1">
  <div class="logo"><img src="../images/nch_mark.jpg" alt="名古屋クラウンホテル" class="header_logo"></div>
  <div class="gnavi__wrap">
    <ul class="gnavi__lists">
      <li class="gnavi__list">
        <a href="#"><?=$user_name ?>さん</a>
        <ul class="dropdown__lists">
          <li class="dropdown__list"><a href="../user.php">ユーザー情報</a></li>
          <li class="dropdown__list"><a href="../password.php">パスワード</a></li>
          <li class="dropdown__list"><a href="../logout.php" alt="ログアウト">ログアウト</a></li>
        </ul>
      </li>
    </ul>
</div>
</header>