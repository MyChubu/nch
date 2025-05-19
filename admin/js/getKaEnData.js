window.addEventListener('load', function() {
  getKaEnData()
});

async function getKaEnData() {
  const url = 'https://nch.netmedia.works/api/banquet/ka-en/'; // APIのURLを設定
  let htmlEn = '';
  let htmlKa = '';
  let htmlOther = '';
  let titleLength = 0;
  const titleLengthMax = 15;
  try {
    let response = await fetch(url);
    console.log('Responseオブジェクト:', response); // Responseオブジェクトを出力

    let jsonData = await response.json();
    console.log('オブジェクト形式に変換したJSONデータ:', jsonData); // パースされたJSONデータを出力
    console.log('日付:', jsonData.hizuke); // 日付を出力
    console.log('イベント数:', jsonData.events.length); // イベント数を出力
    document.title = jsonData.hizuke + 'の会議・宴会予定' ; // タイトルに日付を設定
    document.getElementById("schedate").innerHTML = jsonData.hizuke;
    
    if (jsonData.events_en.length == 0) { // イベントが0件の場合
      htmlEn += '<div class="eventbox">';
      htmlEn += '<div class="signage-content">本日の予定はありません</div>';
      htmlEn += '</div>';
    }else{  
      htmlEn += '<table class="event_table"><tr><th>イベント名</th><th>担当</th><th>部屋</th><th>階</th><th>利用時間</th><th>料理</th><th>@</th><th>人数</th><th>目的</th></tr>';
      for (let i = 0; i < jsonData.events_en.length; i++) {
        if(jsonData.events_en[i].status != 5){
          titleLength = jsonData.events_en[i].event_name.length;
          htmlEn += '<tr id="row_"' + i + '">';
          
          htmlEn += '<td class="event_name">' + jsonData.events_en[i].event_name + '</td>';
          
          htmlEn += '<td class="pic">' + jsonData.events_en[i].pic + '</td>';
          htmlEn += '<td class="room_name">' + jsonData.events_en[i].room_name + '</td>';
          htmlEn += '<td class="floor">' + jsonData.events_en[i].floor + '</td>';
          if(jsonData.events_en[i].layout_id == 20){
            htmlEn += '<td class="time">入れ込み</td>';
          }else{
            htmlEn += '<td class="time">' + jsonData.events_en[i].start + ' ～ ' +  '</td>';
          }
          htmlEn += '<td class="cuisine">' + '-' + '</td>';
          htmlEn += '<td class="price">' + '-' + '</td>';
          htmlEn += '<td class="people">' + jsonData.events_en[i].people + '</td>';
          htmlEn += '<td class="purpose">' + jsonData.events_en[i].purpose_short + '</td>';
          htmlEn += '</tr>';
        }
      }
      htmlEn += '</table>';
    }
    
    if (jsonData.events_ka.length == 0) {
      htmlKa += '<div class="eventbox">';
      htmlKa += '<div class="signage-content">本日の予定はありません</div>';
      htmlKa += '</div>';
    }else{
      htmlKa += '<table class="event_table"><tr><th>イベント名</th><th>担当</th><th>部屋</th><th>階</th><th>利用時間</th><th>人数</th><th>目的</th></tr>';
      for (let i = 0; i < jsonData.events_ka.length; i++) {
        if(jsonData.events_ka[i].status != 5){
          titleLength = jsonData.events_ka[i].event_name.length;
          htmlKa += '<tr id="row_"' + i + '">';
          
          htmlKa += '<td class="event_name">' + jsonData.events_ka[i].event_name + '</td>';
        
          htmlKa += '<td class="pic">' + jsonData.events_ka[i].pic + '</td>';
          htmlKa += '<td class="room_name">' + jsonData.events_ka[i].room_name + '</td>';
          htmlKa += '<td class="floor">' + jsonData.events_ka[i].floor + '</td>';
          htmlKa += '<td class="time">' + jsonData.events_ka[i].start + ' ～ ' + jsonData.events_ka[i].end + '</td>';
          htmlKa += '<td class="people">' + jsonData.events_ka[i].people + '</td>';
          htmlKa += '<td class="purpose">' + jsonData.events_ka[i].purpose_short + '</td>';
          htmlKa += '</tr>';
        }
      }
      htmlKa += '</table>';
    }

    document.getElementById("eventsEn").innerHTML = htmlEn;
    document.getElementById("eventsKa").innerHTML = htmlKa;
    if (jsonData.events_other.length > 0) {
      htmlOther += '<h2><i class="fa-solid fa-landmark-flag"></i> その他</h2>';
      htmlOther += '<div id="eventsOther">';
      htmlOther += '<table class="event_table"><tr><th>イベント名</th><th>担当</th><th>部屋</th><th>階</th><th>利用時間</th><th>人数</th><th>目的</th></tr>';
      for (let i = 0; i < jsonData.events_other.length; i++) {
        if(jsonData.events_other[i].status != 5){
          titleLength = jsonData.events_other[i].event_name.length;
          htmlOther += '<tr id="row_"' + i + '">';
          if(titleLength > titleLengthMax){
            htmlOther += '<td class="event_name"><div class="marquee">';
            htmlOther += '<div class="marquee-inner">' + jsonData.events_other[i].event_name + '&nbsp;&nbsp;&nbsp;&nbsp;</div>';
            htmlOther += '<div class="marquee-inner">' + jsonData.events_other[i].event_name+ '&nbsp;&nbsp;&nbsp;&nbsp;</div>';
            htmlOther += '</div></td>';
          }else{
            htmlOther += '<td class="event_name">' + jsonData.events_other[i].event_name + '</td>';
          }
          htmlOther += '<td class="pic">' + jsonData.events_other[i].pic + '</td>';
          htmlOther += '<td class="room_name">' + jsonData.events_other[i].room_name + '</td>';
          htmlOther += '<td class="floor"> - </td>';
          htmlOther += '<td class="time">' + jsonData.events_other[i].start + ' ～ ' + jsonData.events_other[i].end + '</td>';
          htmlOther += '<td class="people">' + jsonData.events_other[i].people + '</td>';
          htmlOther += '<td class="purpose">' + jsonData.events_other[i].purpose_short + '</td>';
          htmlOther += '</tr>';
        }
      }
      htmlOther += '</table></div>';70
      document.getElementById("eventsOther").innerHTML = htmlOther;
    }
  }
  catch (error) {
      console.error('An error occurred:', error);
      htmlEn = '<div class="eventbox">';
      htmlEn += '<div class="signage-content">データ受信に失敗しました <i class="fa-regular fa-face-sad-tear"></i></div>';
      htmlEn += '</div>';
      document.getElementById("eventsEn").innerHTML = htmlEn;
      document.getElementById("eventsKa").innerHTML = htmlEn;
  }
  
}

