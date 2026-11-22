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
const numbersIn = (value) => (String(value ?? '').normalize('NFKC').match(/\d+/g) || []).filter((group) => group.length <= 3).map(Number);
// Ordinales con los que suele nombrarse un piso («Segundo piso»). «Planta baja» no se interpreta.
const ORDINALS = { primer: 1, primero: 1, primera: 1, segundo: 2, segunda: 2, tercer: 3, tercero: 3, tercera: 3, cuarto: 4, cuarta: 4 };
function floorHintOf(piso) {
  if (Number.isInteger(piso?.numero)) return piso.numero;
  const number = numbersIn(piso?.nombre)[0];
  if (number !== undefined) return number;
  const word = plain(piso?.nombre).split(/[^a-z]+/).find((item) => Object.hasOwn(ORDINALS, item));
  return word ? ORDINALS[word] : null;
}
const floorOfRoom = (number) => BUILDING_FLOORS.find((floor) => floor.left.includes(number) || floor.right.includes(number));

// Texto comparable: sin tildes, en minúsculas, con las variantes tipográficas de una cifra reducidas a su
// forma básica (NFKC convierte «１１» o «¹¹» en «11» y «ⅩⅢ» en «xiii»), guiones y guiones bajos tratados
// como espacios («AULA_11», «LAB-41») y sin espacios en los extremos.
const comparable = (value) => plain(String(value ?? '').replace(/[°º]/g, 'o').normalize('NFKC')).replace(/[_-]+/g, ' ').trim();

// Identificador de ambiente. Debe ser el texto COMPLETO: tipo de ambiente y número, y nada más
// («Aula 11», «Lab. 41», «Aula N° 11», «AULA_11», «LAB-41»). Cualquier añadido impide reconocerlo:
// otro número, un sufijo o una letra («Aula 11B», «Aula 11 bis», «Aula 11 - Bloque B»), un rango o una
// lista («Aula 11–13», «Aula 11 / Aula 11B») o una aclaración («Aula 11 (30 personas)»).
const ROOM_ID = /^(aula|laboratorio|lab)\.?\s*(?:n(?:ro|o)?\.?\s*)?(\d{1,3})$/;
const ROOM_TYPE = /\b(?:aulas?|laboratorios?|labs?)\b/;
// Numeral romano escrito con letras («XIII», «IV»); una letra suelta solo cuenta si es I, V o X.
const ROMAN = /\b(?=[ivxlcdm]{2,}\b|[ivx]\b)m{0,3}(?:cm|cd|d?c{0,3})(?:xc|xl|l?x{0,3})(?:ix|iv|v?i{0,3})\b/;

function roomIdOf(text) {
  const found = ROOM_ID.exec(text);
  return found ? { kind: found[1] === 'aula' ? 'aula' : 'laboratorio', number: Number(found[2]) } : null;
}
// ¿Menciona el texto algún número? Cuenta las cifras de cualquier escritura y los numerales romanos.
const mentionsNumber = (text) => /\p{N}/u.test(text) || ROMAN.test(text);

// Tipo de ambiente según el catálogo del sistema: el código del tipo de espacio del registro («AULA»,
// «LABORATORIO», «AUDITORIO»). Es un dato estable; cuando no llega o no es uno de estos, no se interpreta.
const TYPE_CODES = new Map([['aula', 'aula'], ['aulas', 'aula'], ['laboratorio', 'laboratorio'], ['laboratorios', 'laboratorio'], ['lab', 'laboratorio'], ['auditorio', 'auditorio'], ['aud', 'auditorio']]);
const kindOfType = (tipo) => TYPE_CODES.get(comparable(tipo?.codigo)) ?? null;
// Identificador del auditorio de la maqueta, que es uno solo: «AUD», «AUD-01», «AUD01», «AUDITORIO»,
// «Auditorio N° 1». Con otro número («AUD-02», «Auditorio 02») nombra otro auditorio y no se ubica.
const AUDITORIUM_ID = /^aud(?:itorio)?\.?(?:\s*(?:n(?:ro|o)?\.?\s*)?0?1)?$/;
// Palabra de ambiente dentro de un código que no es exactamente un identificador: suelta o con un sufijo
// («AULA-11B», «LAB-41 B»), pegada a un número («LAB41B», «AUD02») o con su marca de número
// («LABNRO.41B»). Usa la misma gramática que el identificador completo.
const SPACE_WORD = /(?:^|[^a-z])(?:aulas?|laboratorios?|labs?|aud|auditorios?)(?![a-z])/;
const NUMBERED_SPACE = /(?:^|[^a-z])(?:aula|laboratorio|lab|aud|auditorio)s?\.?\s*(?:n(?:ro|o)?\.?\s*)?\d/;
// Nombre que identifica al propio espacio como EL auditorio: empieza por «Auditorio», en singular
// («Auditorio principal»). Mencionarlo no basta («Sala junto al auditorio»), ni una palabra parecida
// («Sala de auditoría»); el plural («Auditorios norte y sur») nombra varios espacios.
const AUDITORIUM_NAME = /^auditorio\b/;
const AUDITORIUMS_NAME = /^auditorios\b/;
// Clases de ambiente que puede nombrar un texto. Un nombre que nombra dos («Aula y auditorio»,
// «Auditorio y laboratorio») no identifica un solo espacio, en el orden que sea.
const KIND_WORDS = [/\baulas?\b/, /\b(?:laboratorios?|labs?)\b/, /\bauditorios?\b/];
const kindsNamed = (text) => KIND_WORDS.filter((word) => word.test(text)).length;

const ROOM = (id) => ({ is: 'room', ...id });
const AUDITORIUM = { is: 'auditorium' };
const UNCLEAR = { is: 'unclear' };

// Lo que dice el código del espacio: un ambiente, el auditorio, nada (un correlativo como «ESP-013»)
// o algo que no se puede leer con certeza (nombra un ambiente sin ser exactamente su identificador).
function codeSays(code) {
  const room = roomIdOf(code);
  if (room) return ROOM(room);
  if (AUDITORIUM_ID.test(code)) return AUDITORIUM;
  return SPACE_WORD.test(code) || NUMBERED_SPACE.test(code) ? UNCLEAR : null;
}
// Lo que dice el nombre: un ambiente o el auditorio si es exactamente su identificador («Aula 11»,
// «Auditorio 01»); el auditorio si empieza por «Auditorio» sin números; nada si es descriptivo
// («Laboratorio de robótica», «Sala junto al auditorio»); y no se puede leer con certeza si lleva
// otros números, nombra varios auditorios o nombra más de una clase de ambiente.
function nameSays(name) {
  const room = roomIdOf(name);
  if (room) return ROOM(room);
  if (AUDITORIUM_ID.test(name)) return AUDITORIUM;
  if (mentionsNumber(name) || AUDITORIUMS_NAME.test(name) || kindsNamed(name) > 1) return UNCLEAR;
  return AUDITORIUM_NAME.test(name) ? AUDITORIUM : null;
}

// Ubica un espacio real en la maqueta. Devuelve { floor, room, kind } o null cuando el registro no
// respalda la posición; en ese caso el espacio se lista aparte, nunca se descarta.
// La posición se decide con los datos estables del registro: el código del espacio, el código de su tipo
// y el número de su piso. El nombre es texto de presentación: solo decide cuando el código no dice nada,
// y siempre se comprueba que no lo contradiga.
// Regla conservadora: ante cualquier ambigüedad el espacio NO se dibuja.
// · Se lee por separado lo que dicen el código y el nombre. Si alguno no se puede leer con certeza, o si
//   los dos dicen cosas distintas (dos ambientes, o un ambiente y el auditorio), no se dibuja.
// · Si ninguno dice nada, solo el tipo del catálogo puede identificar al auditorio, que es único; un aula
//   o un laboratorio necesitan su identificador («AULA-11», «Lab. 41», «Aula N° 11»).
// · El tipo del catálogo, cuando el registro lo trae y es reconocible, debe coincidir con lo identificado.
// · Aula o laboratorio: el tipo del identificador debe coincidir con el del ambiente de la maqueta.
// · Auditorio: como no tiene número de ambiente, se dibuja únicamente cuando el piso registrado lo confirma.
// · Si el registro indica su piso con un número o un ordinal, debe coincidir con el de la maqueta.
// La posición resultante es solo de dibujo: el piso que se muestra al usuario es siempre el del registro.
export function locateSpace(space) {
  const fromCode = codeSays(comparable(space?.recurso?.codigo));
  const fromName = nameSays(comparable(space?.recurso?.nombre));
  if (fromCode === UNCLEAR || fromName === UNCLEAR) return null;
  if (fromCode && fromName && (fromCode.is !== fromName.is || fromCode.kind !== fromName.kind || fromCode.number !== fromName.number)) return null;

  const typed = kindOfType(space?.tipo);
  // Sin un código de tipo reconocible, el nombre del tipo también puede decir que es el auditorio.
  const typedAuditorium = typed === 'auditorio' || (typed === null && comparable(space?.tipo?.nombre) === 'auditorio');
  const found = fromCode || fromName || (typedAuditorium ? AUDITORIUM : null);
  if (!found) return null;
  const floorHint = floorHintOf(space?.piso);

  if (found.is === 'auditorium') {
    if (typed !== null && typed !== 'auditorio') return null;
    const floor = floorOfRoom('auditorium');
    return floor && floorHint === floor.number ? { floor: floor.number, room: 'auditorium', kind: 'auditorio' } : null;
  }

  if (typedAuditorium || (typed !== null && typed !== found.kind)) return null;
  const floor = floorOfRoom(found.number);
  if (!floor || floor.kind !== found.kind) return null;
  if (floorHint !== null && floorHint !== floor.number) return null;
  return { floor: floor.number, room: found.number, kind: floor.kind };
}
