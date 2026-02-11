window.addEventListener('load', function() {
  getRoomStatus()
});

async function getRoomStatus() {
  const url = 'https://nch.nagoyacrown.co.jp/api/guestrooms/summary/'; // APIのURLを設定
  // const url = 'https://sign.nagoyacrown.co.jp/rm/json/roomstatus001.json'; // APIのURLを設定 テスト用
  let html = '';
  try {
    let response = await fetch(url);
    console.log('Responseオブジェクト:', response); // Responseオブジェクトを出力
    let jsonData = await response.json();
    const date = jsonData.date;
    const time = jsonData.time;

    console.log('オブジェクト形式に変換したJSONデータ:', jsonData); // パースされたJSONデータを出力

    const floors = jsonData.floor;
    const fLength = floors.length;
    let f_num = '';
    let fn = '';
    let bldg = '';
    let rooms = [];
    let rLength = 0;
    let r_num = '';
    let room_status = '';
    let sc = '';
    let sc2 = '';
    html += `<div class="header"><h1>Room Summary</h1><p class="update-time">Last Update: ${date} ${time}</p></div>`;
    for (let i = 0; i < fLength; i++) {
      fn = '';
      bldg = '';
      f_num = floors[i].number;
      fn = Number(f_num);
      if (fn > 20) {
        fn = fn - 20;
        bldg = 'Annex ';
      }
      rooms = floors[i].rooms;
      rLength = rooms.length;
      html += `<h2 class="floor-title" id="f_${f_num}">${bldg}${fn}F</h2><div class="floor-box">`;
      for (let j = 0; j < rLength; j++) {
        r_num = rooms[j].room;
        room_status = rooms[j].status;
        sc = room_status.charAt(0);
        if (sc === 'V') {
          sc2 = room_status.charAt(1);
          if (sc2 === 'P') {
            sc = 'VP';
          }
        }

        html += `<div class="room ${f_num}${r_num} status_${sc}">${r_num}<br>${room_status}</div>`;

        // 末尾が50の部屋番号の場合、かつ最後の部屋でない場合に改行を挿入
        if (r_num.endsWith("50") && j !== rLength - 1) {
          html += `</div><div class="floor-box">`;
        }
      }
      html += '</div>'; // .floor-boxの閉じタグ
    }
   
    
    document.getElementById('app').innerHTML = html;
  }
  catch (error) {
    console.error('データの取得または処理中にエラーが発生しました:', error);
    document.getElementById('app').innerHTML = '<p class="error-message">Error: Unable to retrieve room status data at this time.</p>';
  }
}