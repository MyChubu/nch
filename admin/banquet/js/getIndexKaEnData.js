window.addEventListener('load', function() {
  getKaEnData(); //初回表示
  //setInterval(getKaEnData, 300000); // 5分ごとに再描画（300,000ミリ秒）
});

async function getKaEnData() {
  const url = 'https://nch.netmedia.works/api/banquet/ka-en/'; // APIのURLを設定
  
  let htmlEn = '';
  let htmlKa = '';
  let htmlOther = '';
  let titleLength = 0;
  let lines = 0;
  const titleLengthMax = 15;
  try {
    let response = await fetch(url);
    console.log('Responseオブジェクト:', response); // Responseオブジェクトを出力

    let jsonData = await response.json();
    document.getElementById("schedate").innerHTML = jsonData.hizuke;
    
    if (jsonData.events_en.length == 0) { // イベントが0件の場合
      htmlEn += '<div class="eventbox">';
      htmlEn += '<div class="signage-content">本日の予定はありません</div>';
      htmlEn += '</div>';
    }else{  
      htmlEn += '<table class="event_table"><tr>';
      htmlEn += '<th>イベント名</th>';
      htmlEn += '<th><i class="fa-solid fa-user"></i></th>'; //担当者アイコン
      htmlEn += '<th><i class="fa-solid fa-location-dot"></i></th>'; //場所アイコン
      htmlEn += '<th><i class="fa-solid fa-stairs"></i></th>'; //階段アイコン
      htmlEn += '<th><i class="fa-solid fa-clock-rotate-left fa-flip-vertical"></i></th>'; //時間アイコン
      htmlEn += '<th><i class="fa-solid fa-utensils"></i></th>'; //料理アイコン
      htmlEn += '<th><i class="fa-solid fa-people-group"></i></th>'; //人数（グループ）アイコン
      htmlEn += '<th><i class="fa-solid fa-flag-checkered"></i></th>'; //目的（チェッカーフラッグ）アイコン
      htmlEn += '<th><i class="fa-solid fa-wine-glass"></i></th>'; //グラスアイコン
      htmlEn += '<th><i class="fa-solid fa-display"></i></th>'; //ディスプレイアイコン
      htmlEn += '</tr>';
      for (let i = 0; i < jsonData.events_en.length; i++) {
        if(jsonData.events_en[i].status != 5){
          
          htmlEn += '<tr id="e_row_"' + i + '">';
          htmlEn += '<td class="event_name">';
          if(jsonData.events_en[i].agent_id > 0){
            let agentName ="";
            if(jsonData.events_en[i].agent_name.length > 0){
              agentName = jsonData.events_en[i].agent_name;
            }else if(jsonData.events_en[i].reserver.length > 0) {
              agentName = jsonData.events_en[i].reserver;
            }else if(jsonData.events_en[i].agent_group.length > 0){
              agentName = jsonData.events_en[i].agent_group;
            }
            if(agentName.length > 0){
              htmlEn +='<div class="agent"><i class="fa-solid fa-crown"></i>'+ agentName +'</div>';
            }
          }

          htmlEn += '<div>' + jsonData.events_en[i].event_name + '</div>';
          htmlEn +="</td>";
          
          htmlEn += '<td class="pic">' + jsonData.events_en[i].pic + '</td>';
          htmlEn += '<td class="room_name">' + jsonData.events_en[i].room_name + '</td>';
          htmlEn += '<td class="floor">' + jsonData.events_en[i].floor + '</td>';
          if(jsonData.events_en[i].layout_id == 20){
            htmlEn += '<td class="time">入れ込み</td>';
          }else{
            htmlEn += '<td class="time">' + jsonData.events_en[i].start + ' ～ ' +  '</td>';
          }
          htmlEn += '<td class="cuisine">'
          if(jsonData.events_en[i].meal.length > 0){
            for (let j = 0; j < jsonData.events_en[i].meal.length; j++) {
              htmlEn += "<div>";
              htmlEn += "<div>" + jsonData.events_en[i].meal[j].short_name + " " + jsonData.events_en[i].meal[j].unit_price.toLocaleString() + "×" + jsonData.events_en[i].meal[j].qty + '</div>';
              htmlEn += "</div>";
            }
          }else{
            htmlEn += '-';
          }
          htmlEn += '</td>';
          htmlEn += '<td class="people">' + jsonData.events_en[i].people + '</td>';
          htmlEn += '<td class="purpose">' + jsonData.events_en[i].purpose_short + '</td>';
          htmlEn += '<td class="drink">';
          if(jsonData.events_en[i].drink1.length > 0){
            for (let j = 0; j < jsonData.events_en[i].drink1.length; j++) {
              let dc = jsonData.events_en[i].drink1[j].short_name;
              let dcp = dc.split('-');
              htmlEn += "<div><span class='dc dc-"+ dcp[1] + "'>" + dcp[1] + "</span></div>";
            }
          }else{
            htmlEn += '-';
          }
          htmlEn += '</td>';
          htmlEn += '<td class="ds">';
          if(jsonData.events_en[i].enable == 1){
            htmlEn += '<i class="fa-solid fa-square-check"></i>';
          }else{
            htmlEn += '';
          }
          htmlEn += '</td>';
          htmlEn += '</tr>';
          lines ++;
        }
      }
      htmlEn += '</table>';
      htmlEn += '<div>';
      htmlEn += '<div class="amount_value">&yen;'+ jsonData.amount_en.toLocaleString() +'</div>';
      htmlEn += '</div>';
    }
    
    if (jsonData.events_ka.length == 0) {
      htmlKa += '<div class="eventbox">';
      htmlKa += '<div class="signage-content">本日の予定はありません</div>';
      htmlKa += '</div>';
    }else{
      htmlKa += '<table class="event_table"><tr>';
      htmlKa += '<th>イベント名</th>';
      htmlKa += '<th><i class="fa-solid fa-user"></i></th>'; //担当者アイコン
      htmlKa += '<th><i class="fa-solid fa-location-dot"></i></th>'; //場所アイコン
      htmlKa += '<th><i class="fa-solid fa-stairs"></i></th>'; //階段アイコン
      htmlKa += '<th><i class="fa-solid fa-clock-rotate-left fa-flip-vertical"></i></th>'; //時間アイコン
      htmlKa += '<th><i class="fa-solid fa-people-group"></i></th>'; //人数（グループ）アイコン
      htmlKa += '<th><i class="fa-solid fa-flag-checkered"></i></th>'; //目的（チェッカーフラッグ）アイコン
      htmlKa += '<th><i class="fa-solid fa-display"></i></th>'; //ディスプレイアイコン
      htmlKa += '</tr>';
      for (let i = 0; i < jsonData.events_ka.length; i++) {
        if(jsonData.events_ka[i].status != 5){
          titleLength = jsonData.events_ka[i].event_name.length;
          htmlKa += '<tr id="k_row_"' + i + '">';
          htmlKa += '<td class="event_name">';
          if(jsonData.events_ka[i].agent_id > 0){
            agentName ="";
            if(jsonData.events_ka[i].agent_name.length > 0){
              agentName = jsonData.events_ka[i].agent_name;
            }else if(jsonData.events_ka[i].reserver.length > 0) {
              agentName = jsonData.events_ka[i].reserver;
            }else if(jsonData.events_ka[i].agent_group.length > 0){
              agentName = jsonData.events_ka[i].agent_group;
            }
            if(agentName.length > 0){
              htmlKa +='<div class="agent"><i class="fa-solid fa-crown"></i>'+ agentName +'</div>';
            }
          }
          htmlKa += '<div>' + jsonData.events_ka[i].event_name + '</div>';
          htmlKa += '</td>';
          htmlKa += '<td class="pic">' + jsonData.events_ka[i].pic + '</td>';
          htmlKa += '<td class="room_name">' + jsonData.events_ka[i].room_name + '</td>';
          htmlKa += '<td class="floor">' + jsonData.events_ka[i].floor + '</td>';
          htmlKa += '<td class="time">' + jsonData.events_ka[i].start + ' ～ ' + jsonData.events_ka[i].end + '</td>';
          htmlKa += '<td class="people">' + jsonData.events_ka[i].people + '</td>';
          htmlKa += '<td class="purpose">' + jsonData.events_ka[i].purpose_short + '</td>';
          htmlKa += '<td class="ds">';
          if(jsonData.events_ka[i].enable == 1){
            htmlKa += '<i class="fa-solid fa-square-check"></i>';
          }else{
            htmlKa += '';
          }
          htmlKa += '</td>';
          htmlKa += '</tr>';
          lines ++;
        }
      }
      htmlKa += '</table>';
      htmlKa += '<div>';
      htmlKa += '<div class="amount_value">&yen;'+ jsonData.amount_ka.toLocaleString() +'</div>';
      htmlKa += '</div>';
    }

    document.getElementById("eventsEn").innerHTML = htmlEn;
    document.getElementById("eventsKa").innerHTML = htmlKa;
    if (jsonData.events_other.length > 0) {
      htmlOther += '<h2><i class="fa-solid fa-landmark-flag"></i> その他</h2>';
      htmlOther += '<div id="eventsOther">';
      htmlOther += '<table class="event_table"><tr>';
      htmlOther += '<th>イベント名</th>';
      htmlOther += '<th><i class="fa-solid fa-user"></i></th>'; //担当者アイコン
      htmlOther += '<th><i class="fa-solid fa-location-dot"></i></th>'; //場所アイコン
      htmlOther += '<th><i class="fa-solid fa-stairs"></i></th>'; //階段アイコン
      htmlOther += '<th><i class="fa-solid fa-clock-rotate-left fa-flip-vertical"></i></th>'; //時間アイコン
      htmlOther += '<th><i class="fa-solid fa-people-group"></i></th>'; //人数（グループ）アイコン
      htmlOther += '<th><i class="fa-solid fa-flag-checkered"></i></th>'; //目的（チェッカーフラッグ）アイコン
      htmlOther += '<th><i class="fa-solid fa-display"></i></th>'; //ディスプレイアイコン
      htmlOther += '</tr>';
      for (let i = 0; i < jsonData.events_other.length; i++) {
     
        if(jsonData.events_other[i].status != 5){
          titleLength = jsonData.events_other[i].event_name.length;
          htmlOther += '<tr id="o_row_"' + i + '">';
          htmlOther += '<td class="event_name">';
          if(jsonData.events_other[i].agent_id > 0){
            agentName ="";
            if(jsonData.events_other[i].agent_name.length > 0){
              agentName = jsonData.events_other[i].agent_name;
            }else if(jsonData.events_other[i].reserver.length > 0) {
              agentName = jsonData.events_other[i].reserver;
            }else if(jsonData.events_other[i].agent_group.length > 0){
              agentName = jsonData.events_other[i].agent_group;
            }
            if(agentName.length > 0){
              htmlOther +='<div class="agent"><i class="fa-solid fa-crown"></i>'+ agentName +'</div>';
            }
          }

          
          htmlOther += '<div>' + jsonData.events_other[i].event_name + '</div></td>';
          
          htmlOther += '</td>';
          htmlOther += '<td class="pic">' + jsonData.events_other[i].pic + '</td>';
          htmlOther += '<td class="room_name">' + jsonData.events_other[i].room_name + '</td>';
          htmlOther += '<td class="floor"> - </td>';
          htmlOther += '<td class="time">' + jsonData.events_other[i].start + ' ～ ' + jsonData.events_other[i].end + '</td>';
          htmlOther += '<td class="people">' + jsonData.events_other[i].people + '</td>';
          htmlOther += '<td class="purpose">' + jsonData.events_other[i].purpose_short + '</td>';
          htmlOther += '<td class="ds">';
          if(jsonData.events_other[i].enable == 1){
            htmlOther += '<i class="fa-solid fa-square-check"></i>';
          }else{
            htmlOther += '';
          }
          htmlOther += '</td>';
          htmlOther += '</tr>';
          lines ++;
        }
      }
      htmlOther += '</table></div>';
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

window.addEventListener('load', function() {
  getKaEnNextData(); //初回表示
  //setInterval(getKaEnData, 300000); // 5分ごとに再描画（300,000ミリ秒）
});

async function getKaEnNextData() {
  const url = 'https://nch.netmedia.works/api/banquet/ka-en/next.php'; // APIのURLを設定
  
  let htmlEn = '';
  let htmlKa = '';
  let htmlOther = '';
  let titleLength = 0;
  let lines = 0;
  const titleLengthMax = 15;
  try {
    let response = await fetch(url);
    console.log('Responseオブジェクト:', response); // Responseオブジェクトを出力

    let jsonData = await response.json();
    document.getElementById("nextSchedate").innerHTML = jsonData.hizuke;
    
    if (jsonData.events_en.length == 0) { // イベントが0件の場合
      htmlEn += '<div class="eventbox">';
      htmlEn += '<div class="signage-content">本日の予定はありません</div>';
      htmlEn += '</div>';
    }else{  
      htmlEn += '<table class="event_table"><tr>';
      htmlEn += '<th>イベント名</th>';
      htmlEn += '<th><i class="fa-solid fa-user"></i></th>'; //担当者アイコン
      htmlEn += '<th><i class="fa-solid fa-location-dot"></i></th>'; //場所アイコン
      htmlEn += '<th><i class="fa-solid fa-stairs"></i></th>'; //階段アイコン
      htmlEn += '<th><i class="fa-solid fa-clock-rotate-left fa-flip-vertical"></i></th>'; //時間アイコン
      htmlEn += '<th><i class="fa-solid fa-utensils"></i></th>'; //料理アイコン
      htmlEn += '<th><i class="fa-solid fa-people-group"></i></th>'; //人数（グループ）アイコン
      htmlEn += '<th><i class="fa-solid fa-flag-checkered"></i></th>'; //目的（チェッカーフラッグ）アイコン
      htmlEn += '<th><i class="fa-solid fa-wine-glass"></i></th>'; //グラスアイコン
      htmlEn += '<th><i class="fa-solid fa-display"></i></th>'; //ディスプレイアイコン
      htmlEn += '</tr>';
      for (let i = 0; i < jsonData.events_en.length; i++) {
        if(jsonData.events_en[i].status != 5){
          
          htmlEn += '<tr id="n_e_row_"' + i + '">';
          htmlEn += '<td class="event_name">';
          if(jsonData.events_en[i].agent_id > 0){
            let agentName ="";
            if(jsonData.events_en[i].agent_name.length > 0){
              agentName = jsonData.events_en[i].agent_name;
            }else if(jsonData.events_en[i].reserver.length > 0) {
              agentName = jsonData.events_en[i].reserver;
            }else if(jsonData.events_en[i].agent_group.length > 0){
              agentName = jsonData.events_en[i].agent_group;
            }
            if(agentName.length > 0){
              htmlEn +='<div class="agent"><i class="fa-solid fa-crown"></i>'+ agentName +'</div>';
            }
          }

          htmlEn += '<div>' + jsonData.events_en[i].event_name + '</div>';
          htmlEn +="</td>";
          
          htmlEn += '<td class="pic">' + jsonData.events_en[i].pic + '</td>';
          htmlEn += '<td class="room_name">' + jsonData.events_en[i].room_name + '</td>';
          htmlEn += '<td class="floor">' + jsonData.events_en[i].floor + '</td>';
          if(jsonData.events_en[i].layout_id == 20){
            htmlEn += '<td class="time">入れ込み</td>';
          }else{
            htmlEn += '<td class="time">' + jsonData.events_en[i].start + ' ～ ' +  '</td>';
          }
          htmlEn += '<td class="cuisine">'
          if(jsonData.events_en[i].meal.length > 0){
            for (let j = 0; j < jsonData.events_en[i].meal.length; j++) {
              htmlEn += "<div>";
              htmlEn += "<div>" + jsonData.events_en[i].meal[j].short_name + " " + jsonData.events_en[i].meal[j].unit_price.toLocaleString() + "×" + jsonData.events_en[i].meal[j].qty + '</div>';
              htmlEn += "</div>";
            }
          }else{
            htmlEn += '-';
          }
          htmlEn += '</td>';
          htmlEn += '<td class="people">' + jsonData.events_en[i].people + '</td>';
          htmlEn += '<td class="purpose">' + jsonData.events_en[i].purpose_short + '</td>';
          htmlEn += '<td class="drink">';
          if(jsonData.events_en[i].drink1.length > 0){
            for (let j = 0; j < jsonData.events_en[i].drink1.length; j++) {
              let dc = jsonData.events_en[i].drink1[j].short_name;
              let dcp = dc.split('-');
              htmlEn += "<div><span class='dc dc-"+ dcp[1] + "'>" + dcp[1] + "</span></div>";
            }
          }else{
            htmlEn += '-';
          }
          htmlEn += '</td>';
          htmlEn += '<td class="ds">';
          if(jsonData.events_en[i].enable == 1){
            htmlEn += '<i class="fa-solid fa-square-check"></i>';
          }else{
            htmlEn += '';
          }
          htmlEn += '</td>';
          htmlEn += '</tr>';
          lines ++;
        }
      }
      htmlEn += '</table>';
      htmlEn += '<div>';
      htmlEn += '<div class="amount_value">&yen;'+ jsonData.amount_en.toLocaleString() +'</div>';
      htmlEn += '</div>';
    }
    
    if (jsonData.events_ka.length == 0) {
      htmlKa += '<div class="eventbox">';
      htmlKa += '<div class="signage-content">本日の予定はありません</div>';
      htmlKa += '</div>';
    }else{
      htmlKa += '<table class="event_table"><tr>';
      htmlKa += '<th>イベント名</th>';
      htmlKa += '<th><i class="fa-solid fa-user"></i></th>'; //担当者アイコン
      htmlKa += '<th><i class="fa-solid fa-location-dot"></i></th>'; //場所アイコン
      htmlKa += '<th><i class="fa-solid fa-stairs"></i></th>'; //階段アイコン
      htmlKa += '<th><i class="fa-solid fa-clock-rotate-left fa-flip-vertical"></i></th>'; //時間アイコン
      htmlKa += '<th><i class="fa-solid fa-people-group"></i></th>'; //人数（グループ）アイコン
      htmlKa += '<th><i class="fa-solid fa-flag-checkered"></i></th>'; //目的（チェッカーフラッグ）アイコン
      htmlKa += '<th><i class="fa-solid fa-display"></i></th>'; //ディスプレイアイコン
      htmlKa += '</tr>';
      for (let i = 0; i < jsonData.events_ka.length; i++) {
        if(jsonData.events_ka[i].status != 5){
          titleLength = jsonData.events_ka[i].event_name.length;
          htmlKa += '<tr id="n_k_row_"' + i + '">';
          htmlKa += '<td class="event_name">';
          if(jsonData.events_ka[i].agent_id > 0){
            agentName ="";
            if(jsonData.events_ka[i].agent_name.length > 0){
              agentName = jsonData.events_ka[i].agent_name;
            }else if(jsonData.events_ka[i].reserver.length > 0) {
              agentName = jsonData.events_ka[i].reserver;
            }else if(jsonData.events_ka[i].agent_group.length > 0){
              agentName = jsonData.events_ka[i].agent_group;
            }
            if(agentName.length > 0){
              htmlKa +='<div class="agent"><i class="fa-solid fa-crown"></i>'+ agentName +'</div>';
            }
          }
          htmlKa += '<div>' + jsonData.events_ka[i].event_name + '</div>';
          htmlKa += '</td>';
          htmlKa += '<td class="pic">' + jsonData.events_ka[i].pic + '</td>';
          htmlKa += '<td class="room_name">' + jsonData.events_ka[i].room_name + '</td>';
          htmlKa += '<td class="floor">' + jsonData.events_ka[i].floor + '</td>';
          htmlKa += '<td class="time">' + jsonData.events_ka[i].start + ' ～ ' + jsonData.events_ka[i].end + '</td>';
          htmlKa += '<td class="people">' + jsonData.events_ka[i].people + '</td>';
          htmlKa += '<td class="purpose">' + jsonData.events_ka[i].purpose_short + '</td>';
          htmlKa += '<td class="ds">';
          if(jsonData.events_ka[i].enable == 1){
            htmlKa += '<i class="fa-solid fa-square-check"></i>';
          }else{
            htmlKa += '';
          }
          htmlKa += '</td>';
          htmlKa += '</tr>';
          lines ++;
        }
      }
      htmlKa += '</table>';
      htmlKa += '<div>';
      htmlKa += '<div class="amount_value">&yen;'+ jsonData.amount_ka.toLocaleString() +'</div>';
      htmlKa += '</div>';
    }

    document.getElementById("nextEventsEn").innerHTML = htmlEn;
    document.getElementById("nextEventsKa").innerHTML = htmlKa;
    if (jsonData.events_other.length > 0) {
      htmlOther += '<h2><i class="fa-solid fa-landmark-flag"></i> その他</h2>';
      htmlOther += '<div id="nextEventsOther">';
      htmlOther += '<table class="event_table"><tr>';
      htmlOther += '<th>イベント名</th>';
      htmlOther += '<th><i class="fa-solid fa-user"></i></th>'; //担当者アイコン
      htmlOther += '<th><i class="fa-solid fa-location-dot"></i></th>'; //場所アイコン
      htmlOther += '<th><i class="fa-solid fa-stairs"></i></th>'; //階段アイコン
      htmlOther += '<th><i class="fa-solid fa-clock-rotate-left fa-flip-vertical"></i></th>'; //時間アイコン
      htmlOther += '<th><i class="fa-solid fa-people-group"></i></th>'; //人数（グループ）アイコン
      htmlOther += '<th><i class="fa-solid fa-flag-checkered"></i></th>'; //目的（チェッカーフラッグ）アイコン
      htmlOther += '<th><i class="fa-solid fa-display"></i></th>'; //ディスプレイアイコン
      htmlOther += '</tr>';
      for (let i = 0; i < jsonData.events_other.length; i++) {
     
        if(jsonData.events_other[i].status != 5){
          titleLength = jsonData.events_other[i].event_name.length;
          htmlOther += '<tr id="n_o_row_"' + i + '">';
          htmlOther += '<td class="event_name">';
          if(jsonData.events_other[i].agent_id > 0){
            agentName ="";
            if(jsonData.events_other[i].agent_name.length > 0){
              agentName = jsonData.events_other[i].agent_name;
            }else if(jsonData.events_other[i].reserver.length > 0) {
              agentName = jsonData.events_other[i].reserver;
            }else if(jsonData.events_other[i].agent_group.length > 0){
              agentName = jsonData.events_other[i].agent_group;
            }
            if(agentName.length > 0){
              htmlOther +='<div class="agent"><i class="fa-solid fa-crown"></i>'+ agentName +'</div>';
            }
          }

          
          htmlOther += '<div>' + jsonData.events_other[i].event_name + '</div></td>';
          
          htmlOther += '</td>';
          htmlOther += '<td class="pic">' + jsonData.events_other[i].pic + '</td>';
          htmlOther += '<td class="room_name">' + jsonData.events_other[i].room_name + '</td>';
          htmlOther += '<td class="floor"> - </td>';
          htmlOther += '<td class="time">' + jsonData.events_other[i].start + ' ～ ' + jsonData.events_other[i].end + '</td>';
          htmlOther += '<td class="people">' + jsonData.events_other[i].people + '</td>';
          htmlOther += '<td class="purpose">' + jsonData.events_other[i].purpose_short + '</td>';
          htmlOther += '<td class="ds">';
          if(jsonData.events_other[i].enable == 1){
            htmlOther += '<i class="fa-solid fa-square-check"></i>';
          }else{
            htmlOther += '';
          }
          htmlOther += '</td>';
          htmlOther += '</tr>';
          lines ++;
        }
      }
      htmlOther += '</table></div>';
      document.getElementById("nextEventsOther").innerHTML = htmlOther;
    }

  }
  catch (error) {
    console.error('An error occurred:', error);
    htmlEn = '<div class="eventbox">';
    htmlEn += '<div class="signage-content">データ受信に失敗しました <i class="fa-regular fa-face-sad-tear"></i></div>';
    htmlEn += '</div>';
    document.getElementById("nextEventsEn").innerHTML = htmlEn;
    document.getElementById("nextEventsKa").innerHTML = htmlEn;
  }
  
}



