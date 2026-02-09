<aside class="sidebar">
  <h2>メニュー</h2>
  <ul>
    <li><i class="fa-solid fa-house"></i><a href="../guestrooms/">宿泊室トップ</a></li>
  </ul>
  <h3>ルームインジ</h3>
    <ul>
      <li><i class="fa-solid fa-qrcode"></i><a href="./roomindiqr.php" target="_blank">ルームインジ</a></li>
      <li><i class="fa-solid fa-arrow-up-from-bracket"></i><a href="jsonupload.php">jsonアップロード</a></li>
    </ul>

  
  <h3>メンテナンス</h3>
  <ul>
    <?php if($admin == 1): ?>
    <li><i class="fa-solid fa-users"></i><a href="../users/">ユーザー管理</a></li>
    <?php endif; ?>
  </ul>
  <h3>マニュアル</h3>
  <ul>
    <li><i class="fa-solid fa-bug"></i><a href="https://forms.gle/wHRUasYLma6sLgXP6" target="_blank">バグ報告・要望フォーム</a></li>
    <li><i class="fa-solid fa-book"></i><a href="../../wiki/doku.php?id=web%E3%82%B7%E3%82%B9%E3%83%86%E3%83%A0" target="_blank">オンラインマニュアル</a></li>

  </ul>
</aside>