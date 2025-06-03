<aside class="sidebar">
  <h2>メニュー</h2>
  <ul>
    <li><i class="fa-solid fa-house"></i><a href="../banquet/">宴会・会議トップ</a></li>
  </ul>
  <h3>デジサイ</h3>
  <ul>
    <li><i class="fa-solid fa-display"></i><a href="../banquet/signage.php">デジサイ表示</a></li>
    <li>日付を指定
      <form action="../banquet/signage.php" enctype="multipart/form-data">
        <input type="date" name="event_date" id="" value="<?= $date ?>">
        <button type="submit">確認</button>
      </form>
    </li>
    <li><i class="fa-solid fa-clipboard-list"></i><a href="../banquet/ka_en_list.php">本日の予定</a></li>
  </ul>
  <h3>お知らせ</h3>
  <ul>
    <li><i class="fa-solid fa-bell"></i><a href="../banquet/info.php">お知らせ一覧</a></li>
    <li><i class="fa-solid fa-plus"></i><a href="../banquet/info_entry.php">お知らせ登録</a></li>
  <h3>売上</h3>
  <ul>
    <li><i class="fa-solid fa-calculator"></i><a href="../banquet/salesyear.php">年間累計</a></li>
    <li><i class="fa-solid fa-compass"></i><a href="../banquet/salesagents.php">エージェント別</a></li>
    <li><i class="fa-solid fa-square-h"></i><a href="../banquet/salesdirect.php">直販</a></li>
    <li><i class="fa-solid fa-list-check"></i><a href="../banquet/defect_list.php">登録内容チェック</a></li>
  </ul>
  <h3>予約状況</h3>
  <ul>
    <li><i class="fa-solid fa-calendar-check"></i><a href="../banquet/monthly.php" target="_blank">月間予定</a></li>
    <li><i class="fa-solid fa-calendar-days"></i><a href="../banquet/salescal.php" target="_blank">日別・部屋別Cal</a></li>

  </ul>
  <h3>発注</h3>
  <ul>
    <li><i class="fa-solid fa-utensils"></i><a href="../banquet/kitchen_order.php">料理発注確認</a></li>
  </ul>
  <h3>アップロード</h3>
  <ul>
    <li><i class="fa-solid fa-file-csv"></i><a href="../banquet/csvupload.php">CSVデータアップロード</a></li>
    <li><i class="fa-solid fa-money-bill"></i><a href="../banquet/csv_charge_upload.php">料金CSVアップロード</a></li>
  </ul>
</aside>