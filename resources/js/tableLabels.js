// Presentación: en pantallas estrechas cada fila de una tabla se muestra como ficha (ver app.css).
// Para que cada dato conserve su rótulo, copia el encabezado de la columna en data-label y fija
// los roles ARIA de tabla, que el navegador puede retirar cuando la tabla deja de usar display: table.

const SELECTOR = '.table-scroll table, .audit-table-wrap table';
const ACTIONS = /^acci(ó|o)n(es)?$/i;

function headerLabel(cell) {
  const clone = cell.cloneNode(true);
  clone.querySelectorAll('.sr-only').forEach(node => node.remove());
  return clone.textContent.trim();
}

// Decide cómo se rotula una celda en la ficha. Una columna «Acción» puede contener datos (la acción
// registrada en la bitácora) o controles (enlaces y botones): solo los controles van sin rótulo.
export function cellLabel(label, hasControls) {
  if (!label) return { actions: false, label: '' };
  if (ACTIONS.test(label) && hasControls) return { actions: true, label: '' };
  return { actions: false, label };
}

function setRole(node, role) {
  if (node && !node.hasAttribute('role')) node.setAttribute('role', role);
}

export function labelTables(root) {
  if (!root) return;
  for (const table of root.querySelectorAll(SELECTOR)) {
    const head = table.tHead?.rows[0];
    if (!head) continue;
    const labels = [...head.cells].map(headerLabel);
    setRole(table, 'table');
    setRole(table.tHead, 'rowgroup');
    setRole(head, 'row');
    [...head.cells].forEach(cell => setRole(cell, 'columnheader'));
    for (const body of table.tBodies) {
      setRole(body, 'rowgroup');
      for (const row of body.rows) {
        setRole(row, 'row');
        const aligned = row.cells.length === labels.length;
        [...row.cells].forEach((cell, index) => {
          setRole(cell, cell.tagName === 'TH' ? 'rowheader' : 'cell');
          const kind = cellLabel(aligned ? labels[index] : '', Boolean(cell.querySelector('a, button')));
          // Los controles no necesitan rótulo: sus enlaces ya dicen qué hacen. Los datos lo conservan.
          if (kind.actions) cell.dataset.actions = '';
          else if (kind.label && cell.dataset.label !== kind.label) cell.dataset.label = kind.label;
        });
      }
    }
  }
}

// Mantiene los rótulos al cambiar de página, filtrar o actualizar un listado.
export function watchTables(root) {
  if (!root || typeof MutationObserver === 'undefined') return () => {};
  let frame = 0;
  const run = () => { frame = 0; labelTables(root); };
  const observer = new MutationObserver(() => { if (!frame) frame = requestAnimationFrame(run); });
  labelTables(root);
  observer.observe(root, { childList: true, subtree: true });
  return () => { observer.disconnect(); if (frame) cancelAnimationFrame(frame); };
}
