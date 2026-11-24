// Los huecos de la página (los id del Paso 1)
const lista    = document.getElementById('lista');
const estado   = document.getElementById('estado');
const campoQ   = document.getElementById('q');
const campoCat = document.getElementById('categoria');

// '2026-11-20' → '20/11/2026'
function formatearFecha(fecha) {
    const [a, m, d] = fecha.split('-');
    return `${d}/${m}/${a}`;
}

// Crea UNA tarjeta a partir de UN evento del JSON
function crearTarjeta(ev) {
    const tarjeta = document.createElement('article');
    tarjeta.className = 'card';

    const etiqueta = document.createElement('span');
    etiqueta.className = 'card__badge';
    etiqueta.textContent = ev.categoria;

    const titulo = document.createElement('h2');
    titulo.className = 'card__title';
    titulo.textContent = ev.titulo;

    const cuando = document.createElement('p');
    cuando.className = 'card__meta';
    cuando.textContent = formatearFecha(ev.fecha) + (ev.hora ? ' · ' + ev.hora : '');

    tarjeta.append(etiqueta, titulo, cuando);

    if (ev.descripcion) {                  // null o '' → no se pinta
        const texto = document.createElement('p');
        texto.className = 'card__text';
        texto.textContent = ev.descripcion;
        tarjeta.append(texto);
    }
    return tarjeta;
}

// Pinta la lista completa
function pintar(datos) {
    lista.replaceChildren();               // borra lo que había antes
    estado.textContent = datos.total + ' eventos';
    datos.eventos.forEach(ev => lista.append(crearTarjeta(ev)));
}

async function cargarEventos() {
    const res   = await fetch('api.php');   // 1 · pedir
    const datos = await res.json();         // 3 · convertir
    pintar(datos);                          // 4 · pintar
}

cargarEventos();
