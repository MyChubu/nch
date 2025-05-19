document.addEventListener("DOMContentLoaded", function () {
  const target1 = document.getElementsByClassName('event_tr_0'); 
  const btn01 = document.querySelector("#disp_signage");

  btn01.addEventListener("click", () => {
    for(let i = 0; i < target1.length; i++){
      target1[i].classList.toggle("non_disp");
    }
  });
});


document.addEventListener("DOMContentLoaded", function () {
  // チェックボックスとテキストボックスを取得
  const checkboxes = document.querySelectorAll('.event_table input[type="checkbox"]');
  const textInputs = document.querySelectorAll('.event_table input[type="text"]');

  // チェックボックスの変更イベント
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      handlePost(this);
    });
  });

  // テキストボックスの変更イベント
  textInputs.forEach((input) => {
    input.addEventListener("input", function () {
      handlePost(this);
    });
  });

  // POSTリクエストを送信する関数
  function handlePost(element) {
    const row = element.closest("tr");
    const scheid = row.querySelector('input[type="hidden"]').value; // 行のIDを取得
    const eventName = row.querySelector('input[type="text"]').value; // イベント名を取得
    const isChecked = row.querySelector('input[type="checkbox"]').checked; // チェック状態を取得

    // 行のクラスを更新 (チェックボックスが変更された場合のみ)
    if (element.type === "checkbox") {
      row.className = isChecked ? "event_tr_1" : "event_tr_0";
    }

    // POSTするデータを準備
    const data = {
      scheid: scheid,
      event_name: eventName,
      enabled: isChecked,
    };

    // 外部URLへデータをPOST
    fetch("../functions/scheduleupdate.php", {
      method: "POST",
      headers: {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers": "Content-Type, Authorization",
        "Content-Type": "application/json; charset=UTF-8",
      },
      body: JSON.stringify(data),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((responseData) => {
        console.log("成功:", responseData);
      })
      .catch((error) => {
        console.error("エラー:", error);
      });
  }
});

