document.addEventListener("DOMContentLoaded", function () {
  let sortOrder = 1;
  let lastSortedColumn = -1;

  function detectDataType(value) {
    const num = parseFloat(value.replace(/,/g, ''));
    const date = Date.parse(value);

    if (!isNaN(num) && /^\d{1,3}(,\d{3})*$/.test(value.trim()) || !isNaN(num) && value.trim().match(/^\d+(\.\d+)?$/)) {
      return "number";
    } else if (!isNaN(date)) {
      return "date";
    } else {
      return "string";
    }
  }

  function compareValues(a, b, type) {
    if (type === "number") {
      return parseFloat(a.replace(/,/g, '')) - parseFloat(b.replace(/,/g, ''));
    } else if (type === "date") {
      return new Date(a) - new Date(b);
    } else {
      return a.localeCompare(b, undefined, { sensitivity: 'base' });
    }
  }

  function sortTable(columnIndex) {
    const table = document.getElementById("data-table");
    const tbody = table.tBodies[0];

    // tfoot の行を取得
    const tfoot = table.tFoot;
    let footerRow = null;
    if (tfoot && tfoot.rows.length > 0) {
      footerRow = tfoot.rows[0];
    }

    // データ行取得（tbodyの中から）
    const rows = Array.from(tbody.rows);

    // データ型の判定
    let sampleCell = rows.find(row => row.cells[columnIndex])?.cells[columnIndex];
    const dataType = sampleCell ? detectDataType(sampleCell.textContent.trim()) : "string";

    // ヘッダースタイル更新
    const ths = table.getElementsByTagName("TH");
    for (let i = 0; i < ths.length; i++) {
      ths[i].classList.remove("asc", "desc");
    }
    ths[columnIndex].classList.add(sortOrder === 1 ? "asc" : "desc");

    // ソート
    rows.sort((rowA, rowB) => {
      const a = rowA.cells[columnIndex]?.textContent.trim() || "";
      const b = rowB.cells[columnIndex]?.textContent.trim() || "";
      const result = compareValues(a, b, dataType);
      return result * sortOrder;
    });

    // tbodyをクリアし、ソート後に再追加
    while (tbody.firstChild) {
      tbody.removeChild(tbody.firstChild);
    }
    rows.forEach(row => tbody.appendChild(row));

    // tfootがあれば再追加して末尾に維持
    if (tfoot && footerRow) {
      tfoot.innerHTML = '';
      tfoot.appendChild(footerRow);
      table.appendChild(tfoot);
    }

    // ソート方向切り替え
    if (lastSortedColumn === columnIndex) {
      sortOrder = -sortOrder;
    } else {
      sortOrder = 1;
      lastSortedColumn = columnIndex;
    }
  }

  // ヘッダーにイベント登録
  const headers = document.querySelectorAll("#data-table th");
  headers.forEach((th, index) => {
    th.addEventListener("click", () => sortTable(index));
  });
});
