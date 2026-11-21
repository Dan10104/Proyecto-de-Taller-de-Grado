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

// Ubica un espacio real en la maqueta. Devuelve { floor, room, kind } o null cuando no hay una
// correspondencia única y coherente; en ese caso el espacio se lista aparte, nunca se descarta.
// El ambiente se toma del número presente en el nombre («Aula 11», «Laboratorio 48») o, si el nombre
// no trae número pero sí indica el tipo, del código («LAB-41»). Si el registro indica su piso con un
// número o un ordinal, debe coincidir con el de la maqueta; si lo contradice, el espacio no se dibuja.
// Un ambiente numerado puede dibujarse sin piso registrado porque su número ya figura en el registro;
// el auditorio no tiene número, así que solo se dibuja cuando el piso registrado lo confirma.
// La posición resultante es solo de dibujo: el piso que se muestra al usuario es siempre el del registro.
export function locateSpace(space) {
  const name = space?.recurso?.nombre;
  const code = space?.recurso?.codigo;
  const text = plain(`${name ?? ''} ${code ?? ''} ${space?.tipo?.nombre ?? ''}`);
  const kind = /\baudit/.test(text) ? 'auditorio' : /\blab/.test(text) ? 'laboratorio' : /\baula/.test(text) ? 'aula' : null;
  const floorHint = floorHintOf(space?.piso);
  const fits = (floor) => floorHint === null || floorHint === floor.number;

  if (kind === 'auditorio') {
    const floor = floorOfRoom('auditorium');
    return floor && floorHint === floor.number ? { floor: floor.number, room: 'auditorium', kind } : null;
  }

  const named = numbersIn(name);
  const candidates = named.length ? named : kind ? numbersIn(code) : [];
  const matches = [...new Set(candidates)]
    .map((number) => ({ number, floor: floorOfRoom(number) }))
    .filter(({ floor }) => floor && (!kind || kind === floor.kind) && fits(floor));

  return matches.length === 1 ? { floor: matches[0].floor.number, room: matches[0].number, kind: matches[0].floor.kind } : null;
}
