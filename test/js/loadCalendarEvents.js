document.addEventListener("DOMContentLoaded", loadCalendarEvents);

async function loadCalendarEvents() {
  const response = await fetch("https://nch.netmedia.works/api/banquet/calendar/");
  const data = await response.json();
  const events = data.events;

  const today = new Date(data.day);  // ← JSONから基準日を取得
  const startOfWeek = new Date(today);
  const dayOfWeek = today.getDay();
  const diff = (dayOfWeek === 0) ? -6 : 1 - dayOfWeek; // 月曜始まり
  startOfWeek.setDate(today.getDate() + diff);

  const eventsByDate = {};
  events.forEach(ev => {
    if (!eventsByDate[ev.date]) {
      eventsByDate[ev.date] = [];
    }
    eventsByDate[ev.date].push(ev);
  });

  // テーブル・thead・tbodyをJSで構築
  const table = document.createElement("table");
  table.className = "cal3w";

  // テーブルヘッダー　（月曜始まり）
  const thead = document.createElement("thead");
  const headRow = document.createElement("tr");
  ["月", "火", "水", "木", "金", "土", "日"].forEach(d => {
    const th = document.createElement("th");
    th.textContent = d;
    headRow.appendChild(th);
  });
  thead.appendChild(headRow);
  table.appendChild(thead);
  // テーブルボディ
  // 3週間分の行を作成
  const tbody = document.createElement("tbody");

  for (let week = 0; week < 3; week++) {
    const tr = document.createElement("tr");
    tr.classList.add("week"+(week+1));
    for (let dow = 0; dow < 7; dow++) {
      const current = new Date(startOfWeek);
      current.setDate(startOfWeek.getDate() + week * 7 + dow);

      const yyyy = current.getFullYear();
      const mm = String(current.getMonth() + 1).padStart(2, '0');
      const dd = String(current.getDate()).padStart(2, '0');
      const dateStr = `${yyyy}-${mm}-${dd}`;

      const td = document.createElement("td");
      if (current.toDateString() === today.toDateString()) {
        td.classList.add("today");
      } else if (current < today) {
        td.classList.add("pastday");
      }

      const dateLabel = document.createElement("div");
      dateLabel.className = "event_date";
      dateLabel.textContent = `【${mm}/${dd}】`;
      td.appendChild(dateLabel);

      const dayEvents = eventsByDate[dateStr];
      if (dayEvents) {
        const seen = new Set();

        dayEvents.forEach(ev => {
          const time = ev.start ? ev.start.slice(11, 16) : "";
          const title = ev.title || "";

          // --- 重複チェック用キー ---
          const key = `${time}_${title}`;
          if (seen.has(key)) return; // 同一イベントはスキップ
          seen.add(key); // 初回だけ通す

          // --- picの整形（名字のみ） ---
          const rawPic = ev.pic || "";
          const normalizedPic = rawPic.replace(/　/g, " ").trim();
          const familyName = normalizedPic.split(" ")[0] || "";
          const pic = familyName ? ` <span class="pic">${familyName}</span>` : "";

          // purpose が "下見" のときだけ追加
          const isPreview = ev.purpose === "下見";
          const purposeTag = isPreview ? `<span class="pur"><i class="fa-regular fa-eye"></i></span>` : "";

          // --- イベント出力 ---
          const div = document.createElement("div");
          div.className = "event";
          div.innerHTML = `<span class="event-time">${time}～</span>${purposeTag}<span class="event-name">${title}</span>${pic}`;

          td.appendChild(div);
        });

      }
      tr.appendChild(td);
    }
    tbody.appendChild(tr);
  }

  table.appendChild(tbody);

  // #enevt_cal に挿入
  const container = document.getElementById("enevt_cal");
  container.innerHTML = ""; // 一旦クリア
  container.appendChild(table);
}