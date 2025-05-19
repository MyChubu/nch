window.addEventListener('load', function() {
  getSignageData()
});

async function getSignageData() {
  const url = 'api.php'; // APIのURLを設定

  try {
    let response = await fetch(url);
    console.log('Responseオブジェクト:', response); // Responseオブジェクトを出力

    let jsonData = await response.json();
    console.log('オブジェクト形式に変換したJSONデータ:', jsonData); // パースされたJSONデータを出力
    console.log('日付:', jsonData.hizuke); // 日付を出力
    console.log('イベント数:', jsonData.events.length); // イベント数を出力
    document.title = jsonData.hizuke + 'の会議・宴会予定' ; // タイトルに日付を設定
    document.getElementById("schedate").innerHTML = jsonData.hizuke;
    let html = '';
    if (jsonData.events.length == 0) {
      html += '<div class="eventbox">';
      html += '<div class="signage-content">本日の予定はありません</div>';
      html += '</div>';
    }else{
      for (let i = 0; i < jsonData.events.length; i++) {
        html += '<div class="eventbox">';
        html += '<div class="eventbox_left">' + jsonData.events[i].event_name + '</div>';
        html += '<div class="eventbox_right">';
        html += '<div class="eventbox_room">' + jsonData.events[i].room_name + ' <span class="eventbox_floor">【' + jsonData.events[i].floor + '】</span></div>';
        html += '<div class="eventbox_time">' + jsonData.events[i].start + ' - ' + jsonData.events[i].end + '</div>';
        html += '</div>';
        html += '</div>';
        html += '<div class="eventbox_line"><img src="images/line001.png" alt=""></div>';
      }
    }
    document.getElementById("events").innerHTML = html;
  }
  catch (error) {
      console.error('An error occurred:', error);
  }
  
}

