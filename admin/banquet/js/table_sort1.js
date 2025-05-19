document.addEventListener("DOMContentLoaded", function () {
  let sortOrder = 1;
  let lastSortedColumn = -1;

  const table = document.getElementById("data-table");
  const tbody = table.tBodies[0];
  const tfoot = table.tFoot;
  const originalRows = Array.from(tbody.rows); // ソート＆フィルター用に保存

  function detectDataType(value) {
    const num = parseFloat(value.replace(/,/g, ''));
    const date = Date.parse(value);

    if (!isNaN(num) && /^\-?\d+(,\d{3})*(\.\d+)?$/.test(value.trim()) || !isNaN(num) && value.trim().match(/^\-?\d+(\.\d+)?$/)) {
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
    let sampleCell = originalRows.find(row => row.cells[columnIndex])?.cells[columnIndex];
    const dataType = sampleCell ? detectDataType(sampleCell.textContent.trim()) : "string";

    const ths = table.getElementsByTagName("TH");
    for (let i = 0; i < ths.length; i++) {
      ths[i].classList.remove("asc", "desc");
    }
    ths[columnIndex + 1].classList.add(sortOrder === 1 ? "asc" : "desc");

    const filtered = getFilteredRows();
    filtered.sort((rowA, rowB) => {
      const a = rowA.cells[columnIndex]?.textContent.trim() || "";
      const b = rowB.cells[columnIndex]?.textContent.trim() || "";
      const result = compareValues(a, b, dataType);
      return result * sortOrder;
    });

    renderRows(filtered);

    if (lastSortedColumn === columnIndex) {
      sortOrder = -sortOrder;
    } else {
      sortOrder = 1;
      lastSortedColumn = columnIndex;
    }
  }

  function getFilteredRows() {
    const filters = document.querySelectorAll(".filter-row input");
    return originalRows.filter(row => {
      return Array.from(filters).every((input, i) => {
        const val = input.value.trim().toLowerCase();
        const cellText = row.cells[i]?.textContent.toLowerCase() || "";
        return cellText.includes(val);
      });
    });
  }

  function renderRows(rows) {
    tbody.innerHTML = "";
    rows.forEach(row => tbody.appendChild(row));
    if (tfoot) table.appendChild(tfoot);
  }

  // ソートイベント
  const headers = table.querySelectorAll("thead tr:nth-child(2) th");
  headers.forEach((th, index) => {
    th.addEventListener("click", () => sortTable(index));
  });

  // フィルターイベント
  const filters = document.querySelectorAll(".filter-row input");
  filters.forEach(input => {
    input.addEventListener("input", () => {
      const filtered = getFilteredRows();
      renderRows(filtered);
    });
  });
});
