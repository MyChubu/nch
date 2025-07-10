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
        'Content-Type': 'application/json'
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
