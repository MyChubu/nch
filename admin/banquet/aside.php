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
  <!--
  <h3>お知らせ</h3>
  <ul>
    <li><i class="fa-solid fa-bell"></i><a href="../banquet/info.php">お知らせ一覧</a></li>
    <li><i class="fa-solid fa-plus"></i><a href="../banquet/info_entry.php">お知らせ登録</a></li>
  </ul>
  -->
  <h3>検索</h3>
  <ul>
    <li><i class="fa-solid fa-magnifying-glass"></i><a href="../banquet/search.php">予約検索(β)</a></li>
  </ul>
  <h3>売上</h3>
  <ul>
    <li><i class="fa-solid fa-chart-area"></i><a href="../banquet/chart.php">グラフ(β)</a></li>
    <li><i class="fa-solid fa-calculator"></i><a href="../banquet/salesyear.php">年間累計</a></li>
    <li><i class="fa-solid fa-hand-point-right"></i><a href="../banquet/salesyear2.php">予算用実績(β)</a></li>
    <li><i class="fa-solid fa-building-circle-arrow-right"></i><a href="../banquet/salesagents.php">エージェント別</a></li>
    <li><i class="fa-solid fa-square-h"></i><a href="../banquet/salesdirect.php">直販</a></li>
  </ul>
  <h3>予約状況</h3>
  <ul>
    <li><i class="fa-solid fa-calendar-check"></i><a href="../banquet/monthly.php" target="_blank">月間予定</a></li>
    <li><i class="fa-solid fa-calendar-days"></i><a href="../banquet/salescal.php" target="_blank">日別・部屋別Cal</a></li>
    <li><i class="fa-solid fa-sun"></i><a href="../banquet/new_reservations.php">月別新規予約リスト</a></li>
    <li><i class="fa-solid fa-list-check"></i><a href="../banquet/reservations.php">受注リスト</a></li>
    <li><i class="fa-solid fa-list-check"></i><a href="../banquet/reservations.php?sts=tentative">仮予約リスト</a></li>
  </ul>
  <h3>更新履歴</h3>
  <ul>
    <li><i class="fa-solid fa-hand-sparkles"></i><a href="../banquet/dailyupdates.php">データ更新リスト</a></li>
    <li><i class="fa-solid fa-square-check"></i><a href="../banquet/reservation_alert.php">登録内容チェック</a></li>
  </ul>
  <h3>発注</h3>
  <ul>
    <li><i class="fa-solid fa-utensils"></i><a href="../banquet/kitchen_order.php">料理発注確認</a></li>
    <li><i class="fa-solid fa-bottle-water"></i><a href="../banquet/softdrink_orders.php">ペットボトルお茶・水</a></li>
  </ul>
  <h3>アップロード</h3>
  <ul>
    <li><i class="fa-solid fa-file-csv"></i><a href="../banquet/csvupload.php">CSVデータアップロード</a></li>
    <li><i class="fa-solid fa-money-bill"></i><a href="../banquet/csv_charge_upload.php">料金CSVアップロード</a></li>
  </ul>
  <h3>メンテナンス</h3>
  <ul>
    <li><i class="fa-solid fa-gear"></i><a href="../banquet/edit_master.php">マスター編集</a></li>
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