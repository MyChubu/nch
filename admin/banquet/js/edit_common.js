document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('toggleEdit');
  const editableInputs = document.querySelectorAll('.master_edit');

  // 初期状態：すべて disabled（明示しなくてもOKなら省略可）
  editableInputs.forEach(el => el.disabled = true);

  // チェックボックスの切り替えイベント
  toggle.addEventListener('change', () => {
    const isEditable = toggle.checked;
    editableInputs.forEach(el => {
      el.disabled = !isEditable;
    });

  });
});