document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('.form_table');

  table.addEventListener('change', (event) => {
    const target = event.target;

    if (!target.classList.contains('master_edit')) return;

    const row = target.closest('tr');
    const inputs = row.querySelectorAll('input, select');

    const data = {};
    inputs.forEach(input => {
      const name = input.getAttribute('name');
      const keyMatch = name.match(/\[([a-z_]+)\]$/);
      if (keyMatch) {
        const key = keyMatch[1];
        data[key] = input.value;
      }
    });

    fetch('../functions/update_purpose.php', {
      method: 'POST',
      headers: {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers": "Content-Type, Authorization",
        "Content-Type": "application/json; charset=UTF-8",
      },
      body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(json => {
      if (json.success) {
        console.log('更新成功:', json.message);

        // ▼ pur_id に新しい ID をセットする（hiddenのinputを更新）
        const purIdInput = row.querySelector('input[type="hidden"][name$="[pur_id]"]');
        if (purIdInput && json.banquet_purpose_id !== undefined) {
          purIdInput.value = json.banquet_purpose_id;
        }

      } else {
        console.warn('更新失敗:', json.message);
      }
    })
    .catch(error => {
      console.error('通信エラー:', error);
    });
  });
});


document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('.form_table');

  // すべての select.purpose_id に change イベントをセット
  const updateSelectOptions = () => {
    const allSelects = table.querySelectorAll('select.purpose_id');

    // 現在選ばれている value を収集
    const selectedValues = Array.from(allSelects)
      .map(select => select.value)
      .filter(val => val !== "");

    // 各 select の option を更新
    allSelects.forEach(select => {
      const currentValue = select.value;

      Array.from(select.options).forEach(option => {
        const val = option.value;

        // 「↓↓↓」など空文字は無効にしない
        if (val === "") {
          option.disabled = false;
          return;
        }

        // 他の select ですでに選ばれている番号なら disabled
        if (val !== currentValue && selectedValues.includes(val)) {
          option.disabled = true;
        } else {
          option.disabled = false;
        }
      });
    });
  };

  // 初期状態で一度実行
  updateSelectOptions();

  // 各 select にイベントリスナー追加
  table.addEventListener('change', (event) => {
    if (event.target.matches('select.purpose_id')) {
      updateSelectOptions();
    }
  });
});
