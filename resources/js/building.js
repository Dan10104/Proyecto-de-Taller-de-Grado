// DISTRIBUCIÓN PROVISIONAL DEL EDIFICIO.
// Estructura visual descrita por el responsable del proyecto y pendiente de verificación en sitio.
// NO es inventario institucional ni el plano real: solo ordena el dibujo del gemelo digital.
// Ningún dato de esta lista se guarda ni se envía; los espacios, sensores y lecturas reales
// llegan siempre desde el servidor y se superponen a esta maqueta cuando pueden ubicarse.

export const BUILDING_NOTE = 'Distribución provisional del edificio: no representa el plano real definitivo.';

// Cada lado se recorre desde la escalera hasta el fondo del pasillo.
// Un número es un ambiente; un texto es un servicio ('stairs', 'elevator', 'restroom-*') o el auditorio.
// Pisos 1 a 3: al llegar por la escalera, el ascensor queda al frente; pasillo recto con aulas a ambos
// lados y baños al fondo (hombres a un lado, mujeres al otro).
// Piso 4: al llegar por la escalera, el auditorio queda a la derecha y comienzan los laboratorios.
// Supuestos sin confirmar: en los pisos 2 y 3 se repite el criterio del piso 1 (impares a la derecha,
// pares a la izquierda) y el lado de cada baño.
export const BUILDING_FLOORS = [
  { number: 1, kind: 'aula', left: ['stairs', 12, 14, 16, 'restroom-women'], right: ['elevator', 11, 13, 15, 17, 'restroom-men'] },
  { number: 2, kind: 'aula', left: ['stairs', 18, 20, 22, 24, 'restroom-women'], right: ['elevator', 19, 21, 23, 25, 'restroom-men'] },
  { number: 3, kind: 'aula', left: ['stairs', 26, 28, 30, 32, 'restroom-women'], right: ['elevator', 27, 29, 31, 'restroom-men'] },
  { number: 4, kind: 'laboratorio', left: ['stairs', 42, 44, 46, 48, 'restroom-women'], right: ['elevator', 'auditorium', 41, 43, 45, 47, 'restroom-men'] },
];

// Ambientes con nombre propio.
export const ROOM_NAMES = { 48: 'Laboratorio de robótica' };

// Tipos de ambiente. «features» es el equipamiento habitual indicado para el tipo, no un dato del espacio.
export const ROOM_KINDS = {
  aula: { label: 'Aula común', features: ['projector', 'air'] },
  laboratorio: { label: 'Laboratorio', features: ['air'] },
  auditorio: { label: 'Auditorio', features: [] },
};

export function roomName(room, kind) {
  if (room === 'auditorium') return 'Auditorio';
  return ROOM_NAMES[room] || `${kind === 'laboratorio' ? 'Laboratorio' : 'Aula'} ${room}`;
}

const plain = (value) => String(value ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
// Grupos de hasta tres cifras: descarta correlativos largos que no son un número de ambiente.
const numbersIn = (value) => (String(value ?? '').match(/\d+/g) || []).filter((group) => group.length <= 3).map(Number);
// Ordinales con los que suele nombrarse un piso («Segundo piso»). «Planta baja» no se interpreta.
const ORDINALS = { primer: 1, primero: 1, primera: 1, segundo: 2, segunda: 2, tercer: 3, tercero: 3, tercera: 3, cuarto: 4, cuarta: 4 };
function floorHintOf(piso) {
  if (Number.isInteger(piso?.numero)) return piso.numero;
  const number = numbersIn(piso?.nombre)[0];
  if (number !== undefined) return number;
  const word = plain(piso?.nombre).split(/[^a-z]+/).find((item) => item in ORDINALS);
  return word ? ORDINALS[word] : null;
}
const floorOfRoom = (number) => BUILDING_FLOORS.find((floor) => floor.left.includes(number) || floor.right.includes(number));

// Identificador de ambiente: el número va unido a la palabra que nombra el tipo («Aula 11», «Lab. 41»,
// «Aula N° 11» en el nombre; «LAB-41», «AULA_11» en el código). Un número suelto no identifica un
// ambiente: «Sala para 20 personas» o un correlativo como «ESP-020» no se interpretan.
const ROOM_ID = /\b(aula|laboratorio|lab)\b\.?\s*(?:n(?:ro|o)?\b\.?\s*)?(\d{1,3})\b/g;
// Todos los identificadores presentes en un texto, como pares «tipo-número» sin repetir.
// Regla estructural, independiente del separador: en un texto que identifica un ambiente, todo número
// debe pertenecer a un identificador. Cualquier otro número puede ser otro ambiente de un rango o de una
// lista («AULA_11-13», «Aula 11–13», «Aula 11 / 13», «Aula 11 o 13», «Aula 11 (13)»), así que el texto
// se considera ambiguo y devuelve null. Un texto sin identificador devuelve una lista vacía.
function roomIdsOf(value) {
  const text = plain(value).replace(/[°º]/g, 'o').replace(/[_-]+/g, ' ');
  const found = [...text.matchAll(ROOM_ID)];
  if (!found.length) return [];
  if ((text.match(/\d+/g) || []).length !== found.length) return null;
  return [...new Set(found.map((match) => `${match[1] === 'aula' ? 'aula' : 'laboratorio'}-${Number(match[2])}`))];
}

// Ubica un espacio real en la maqueta. Devuelve { floor, room, kind } o null cuando el registro no
// respalda la posición; en ese caso el espacio se lista aparte, nunca se descarta.
// · Aula o laboratorio: exige que nombre y código, juntos, identifiquen un único ambiente; si nombran
//   dos distintos («Aula 11 / Aula 13», o nombre y código que no coinciden) o llevan cualquier otro
//   número junto al identificador («AULA_11-13», «Aula 11–13», «Aula 11 o 13») el espacio no se dibuja.
//   Es deliberadamente conservador: «Aula 11 (30 personas)» tampoco se dibuja y queda en la lista aparte.
//   El tipo del identificador debe coincidir con el del ambiente de la maqueta.
// · Auditorio: no tiene número, así que solo se dibuja cuando el piso registrado lo confirma.
// · Si el registro indica su piso con un número o un ordinal, debe coincidir con el de la maqueta.
// La posición resultante es solo de dibujo: el piso que se muestra al usuario es siempre el del registro.
export function locateSpace(space) {
  const name = space?.recurso?.nombre;
  const code = space?.recurso?.codigo;
  const floorHint = floorHintOf(space?.piso);

  if (/\baudit/.test(plain(`${name ?? ''} ${space?.tipo?.nombre ?? ''}`))) {
    const floor = floorOfRoom('auditorium');
    return floor && floorHint === floor.number ? { floor: floor.number, room: 'auditorium', kind: 'auditorio' } : null;
  }

  const inName = roomIdsOf(name), inCode = roomIdsOf(code);
  if (!inName || !inCode) return null;
  const ids = [...new Set([...inName, ...inCode])];
  if (ids.length !== 1) return null;
  const [kind, digits] = ids[0].split('-');
  const number = Number(digits);
  const floor = floorOfRoom(number);
  if (!floor || floor.kind !== kind) return null;
  if (floorHint !== null && floorHint !== floor.number) return null;
  return { floor: floor.number, room: number, kind: floor.kind };
}
