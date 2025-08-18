// Utilidades
const $ = (q, el=document) => el.querySelector(q);
const $$ = (q, el=document) => Array.from(el.querySelectorAll(q));
function toast(msg){ alert(msg); }

// -------- Navegación lateral estilo WP --------
function activateTab(tab) {
  $$('.nav-link').forEach(b => b.classList.remove('active'));
  const btn = $(`.nav-link[data-tab="${tab}"]`);
  if (btn) btn.classList.add('active');

  $$('.panel').forEach(p => p.classList.add('hidden'));
  const panel = $(`#panel-${tab}`);
  if (panel) panel.classList.remove('hidden');
}

// Soporte clicks
$$('.nav-link').forEach(btn => {
  btn.addEventListener('click', () => {
    activateTab(btn.dataset.tab);
  });
});

// -------- AJAX helper --------
async function api(action, data={}, method='POST') {
  let body;
  if (data instanceof FormData) {
    body = data;
  } else {
    const fd = new FormData();
    Object.entries(data).forEach(([k,v]) => fd.append(k, v));
    body = fd;
  }
  if (method === 'GET') {
    const qs = new URLSearchParams();
    qs.set('action', action);
    if (!(data instanceof FormData)) {
      for (const [k,v] of Object.entries(data)) qs.set(k, v);
    }
    const r = await fetch('api.php?' + qs.toString());
    return await r.json();
  } else {
    if (!body.has('action')) body.append('action', action);
    const r = await fetch('api.php', { method:'POST', body });
    return await r.json();
  }
}

// -------------------- USUARIOS --------------------
const usersTable = $('#usersTable');
const userForm = $('#userForm');
if (usersTable) {
  async function loadUsers() {
    const res = await api('users.list', {}, 'POST');
    if (!res.ok) return;
    usersTable.innerHTML = `<tr>
      <th>ID</th><th>Nombre</th><th>Email</th><th>Usuario</th><th>Rol</th><th>Creado</th><th>Acciones</th>
    </tr>` + res.rows.map(r => `
      <tr>
        <td>${r.id}</td>
        <td>${r.nombre}</td>
        <td>${r.email}</td>
        <td>${r.usuario}</td>
        <td><span class="badge">${r.rol}</span></td>
        <td>${r.creado_en}</td>
        <td>
          <button data-edit="${r.id}">Editar</button>
          <button class="secondary" data-del="${r.id}">Borrar</button>
        </td>
      </tr>`).join('');
  }
  loadUsers();

  usersTable.addEventListener('click', async (e) => {
    const id = e.target.getAttribute('data-edit');
    if (id) {
      const row = Array.from(e.target.closest('tr').children).map(td => td.textContent);
      userForm.id.value = id;
      userForm.nombre.value = row[1];
      userForm.email.value = row[2];
      userForm.usuario.value = row[3];
      userForm.rol.value = e.target.closest('tr').children[4].innerText.trim();
    }
    const del = e.target.getAttribute('data-del');
    if (del && confirm('¿Borrar usuario?')) {
      const r = await api('users.delete', {id:del});
      if (r.ok) loadUsers();
      else toast(r.error||'Error');
    }
  });

  userForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(userForm);
    const action = userForm.id.value ? 'users.update' : 'users.create';
    data.append('action', action);
    const r = await api(action, data);
    if (r.ok) { userForm.reset(); loadUsers(); }
    else toast(r.error||'Error');
  });
  $('#userReset')?.addEventListener('click', () => userForm.reset());
}

// -------------------- OFERTAS --------------------
const offersTable = $('#offersTable');
const offerForm = $('#offerForm');
const fieldsOffer = $('#fieldsOffer');
const appsOffer = $('#appsOffer');
const interviewsOffer = $('#interviewsOffer');

async function refreshOffersSelects(rows) {
  const opts = rows.map(r => `<option value="${r.id}">${r.titulo} (#${r.id})</option>`).join('');
  if (fieldsOffer) fieldsOffer.innerHTML = `<option value="">— Selecciona —</option>` + opts;
  if (appsOffer) appsOffer.innerHTML = `<option value="">— Selecciona —</option>` + opts;
  if (interviewsOffer) interviewsOffer.innerHTML = `<option value="">— Selecciona —</option>` + opts;
}

if (offersTable) {
  async function loadOffers() {
    const res = await api('offers.list');
    if (!res.ok) return;
    offersTable.innerHTML = `<tr>
      <th>ID</th><th>Título</th><th>Estado</th><th>Creado</th><th>Acciones</th>
    </tr>` + res.rows.map(r => `
      <tr>
        <td>${r.id}</td>
        <td>${r.titulo}</td>
        <td><span class="badge">${r.estado}</span></td>
        <td>${r.creado_en}</td>
        <td>
          <button data-edit="${r.id}">Editar</button>
          <button class="secondary" data-del="${r.id}">Borrar</button>
        </td>
      </tr>`).join('');
    refreshOffersSelects(res.rows);
  }
  loadOffers();

  offersTable.addEventListener('click', async (e) => {
    const tr = e.target.closest('tr');
    const id = e.target.getAttribute('data-edit');
    const del = e.target.getAttribute('data-del');
    if (id) {
      offerForm.id.value = id;
      offerForm.titulo.value = tr.children[1].textContent;
      offerForm.estado.value = tr.children[2].innerText.trim();
    }
    if (del && confirm('¿Borrar oferta y sus datos?')) {
      const r = await api('offers.delete', {id:del});
      if (r.ok) { offerForm.reset(); loadOffers(); }
      else toast(r.error||'Error');
    }
  });

  offerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(offerForm);
    const action = offerForm.id.value ? 'offers.update' : 'offers.create';
    data.append('action', action);
    const r = await api(action, data);
    if (r.ok) { offerForm.reset(); loadOffers(); }
    else toast(r.error||'Error');
  });
  $('#offerReset')?.addEventListener('click', () => offerForm.reset());
}

// -------------------- CAMPOS --------------------
const fieldForm = $('#fieldForm');
const fieldsTable = $('#fieldsTable');
if (fieldsTable) {
  async function loadFields() {
    const offer_id = fieldsOffer.value;
    if (!offer_id) { fieldsTable.innerHTML = ''; return; }
    const res = await api('fields.list', {offer_id}, 'GET');
    if (!res.ok) return;
    fieldsTable.innerHTML = `<tr>
      <th>ID</th><th>Nombre</th><th>Tipo</th><th>Req.</th><th>Orden</th><th>Acciones</th>
    </tr>` + res.rows.map(r => `
      <tr>
        <td>${r.id}</td>
        <td>${r.nombre}</td>
        <td>${r.tipo}</td>
        <td>${r.requerido?'Sí':'No'}</td>
        <td>${r.orden}</td>
        <td>
          <button data-edit="${r.id}">Editar</button>
          <button class="secondary" data-del="${r.id}">Borrar</button>
        </td>
      </tr>`).join('');

    fieldForm.offer_id.value = offer_id;
  }
  fieldsOffer?.addEventListener('change', loadFields);
  loadFields();

  fieldsTable.addEventListener('click', async (e) => {
    const tr = e.target.closest('tr');
    const id = e.target.getAttribute('data-edit');
    const del = e.target.getAttribute('data-del');
    if (id) {
      fieldForm.id.value = id;
      fieldForm.nombre.value = tr.children[1].textContent;
      fieldForm.tipo.value = tr.children[2].textContent;
      fieldForm.requerido.value = tr.children[3].textContent.trim()==='Sí' ? '1':'0';
      fieldForm.orden.value = tr.children[4].textContent;
    }
    if (del && confirm('¿Borrar campo? Esto afecta a candidatos.')) {
      const r = await api('fields.delete', {id:del});
      if (r.ok) loadFields();
      else toast(r.error||'Error');
    }
  });

  fieldForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(fieldForm);
    const action = fieldForm.id.value ? 'fields.update' : 'fields.create';
    data.append('action', action);
    const r = await api(action, data);
    if (r.ok) { fieldForm.reset(); loadFields(); }
    else toast(r.error||'Error');
  });
  $('#fieldReset')?.addEventListener('click', () => fieldForm.reset());
}

// -------------------- CANDIDATOS --------------------
const applicantForm = $('#applicantForm');
const dynamicFields = $('#dynamicFields');
const appsTableWrap = $('#appsTableWrap');

if (appsOffer) {
  appsOffer.addEventListener('change', async () => {
    await buildDynamicForm();
    await loadApplicants();
  });
}

async function buildDynamicForm() {
  dynamicFields.innerHTML = '';
  applicantForm.offer_id.value = appsOffer.value || '';
  if (!appsOffer.value) return;
  const res = await api('fields.list', {offer_id: appsOffer.value}, 'GET');
  if (!res.ok) return;
  res.rows.forEach(f => {
    const wrap = document.createElement('div');
    wrap.className = 'wide';
    let control = '';
    if (f.tipo === 'texto') {
      control = `<input name="field_${f.id}" placeholder="${f.nombre}">`;
    } else if (f.tipo === 'checkbox') {
      control = `<select name="field_${f.id}"><option value="0">No</option><option value="1">Sí</option></select>`;
    } else if (f.tipo === 'datetime') {
      control = `<input type="datetime-local" name="field_${f.id}">`;
    } else if (f.tipo === 'archivo') {
      control = `<input type="file" name="field_${f.id}">`;
    }
    wrap.innerHTML = `<label>${f.nombre}${f.requerido?' *':''}${control}</label>`;
    dynamicFields.appendChild(wrap);
  });
}

async function loadApplicants() {
  appsTableWrap.innerHTML = '';
  if (!appsOffer.value) return;
  const res = await api('applicants.list', {offer_id: appsOffer.value}, 'GET');
  if (!res.ok) return;
  const fields = res.fields;
  const rows = res.rows;

  let html = `<table><tr><th>ID</th>`;
  for (const f of fields) html += `<th>${f.nombre}</th>`;
  html += `<th>Creado</th><th>Acciones</th></tr>`;

  for (const r of rows) {
    html += `<tr><td>${r.id}</td>`;
    for (const f of fields) {
      let v = r.values?.[f.id] ?? '';
      if (f.tipo === 'checkbox') v = v ? 'Sí' : 'No';
      if (f.tipo === 'archivo' && v) v = `<a href="download.php?path=${encodeURIComponent(v)}" target="_blank">Ver archivo</a>`;
      html += `<td>${v ?? ''}</td>`;
    }
    html += `<td>${r.creado_en}</td>
      <td>
        <button data-edit="${r.id}">Editar</button>
        <button class="secondary" data-del="${r.id}">Borrar</button>
      </td></tr>`;
  }
  html += `</table>`;
  appsTableWrap.innerHTML = html;

  // Edición rápida
  appsTableWrap.querySelectorAll('button[data-edit]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-edit');
      const tr = btn.closest('tr');
      applicantForm.applicant_id.value = id;
      const tds = tr.querySelectorAll('td');
      const start = 1;
      fields.forEach((f,i) => {
        const name = `field_${f.id}`;
        const td = tds[start + i];
        if (f.tipo === 'archivo') {
          let hidden = applicantForm.querySelector(`input[type=hidden][name="${name}"]`);
          if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = name;
            applicantForm.appendChild(hidden);
          }
          const a = td.querySelector('a');
          hidden.value = a ? new URL(a.href).searchParams.get('path') : '';
        } else if (f.tipo === 'checkbox') {
          const val = td.textContent.trim() === 'Sí' ? '1':'0';
          applicantForm.querySelector(`[name="${name}"]`).value = val;
        } else {
          applicantForm.querySelector(`[name="${name}"]`).value = td.textContent.trim();
        }
      });
    });
  });

  // Borrado
  appsTableWrap.querySelectorAll('button[data-del]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.getAttribute('data-del');
      if (confirm('¿Borrar candidato?')) {
        const r = await api('applicants.delete', {id});
        if (r.ok) { applicantForm.reset(); await loadApplicants(); }
        else toast(r.error||'Error');
      }
    });
  });
}

applicantForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = new FormData(applicantForm);
  data.append('action','applicants.create_or_update');
  const r = await api('applicants.create_or_update', data);
  if (r.ok) { applicantForm.reset(); await buildDynamicForm(); await loadApplicants(); }
  else toast(r.error||'Error');
});

// -------------------- ENTREVISTAS --------------------
const interviewForm = $('#interviewForm');
const interviewApplicant = $('#interviewApplicant');
const interviewsTable = $('#interviewsTable');

// Caché local de candidatos para entrevistas (label + summary)
let interviewApplicantsCache = {}; // { id: { label, summary } }

if (interviewsOffer) {
  interviewsOffer.addEventListener('change', async () => {
    interviewForm.offer_id.value = interviewsOffer.value || '';
    await loadApplicantsForInterview();
    await loadInterviews(); // se enriquecerá con summaries
  });
}

// Construye etiqueta usando los dos primeros campos de texto no vacíos
function labelFromFirstTwoTextFields(fields, rowValues) {
  const texts = [];
  for (const f of fields) {
    if (f.tipo === 'texto') {
      const val = (rowValues?.[f.id] ?? '').toString().trim();
      if (val) texts.push(val);
      if (texts.length === 2) break;
    }
  }
  return texts.join(' ');
}

// Construye un resumen corto con varios campos de texto (primeros 4 no vacíos)
function summaryFromTextFields(fields, rowValues) {
  const entries = [];
  for (const f of fields) {
    if (f.tipo === 'texto') {
      const val = (rowValues?.[f.id] ?? '').toString().trim();
      if (val) entries.push(`${f.nombre}: ${val}`);
      if (entries.length >= 4) break;
    }
  }
  return entries.join(' · ');
}

// Asegura un contenedor para info bajo el select
function ensureApplicantInfoContainer() {
  let info = $('#interviewApplicantInfo');
  if (!info) {
    info = document.createElement('div');
    info.id = 'interviewApplicantInfo';
    info.className = 'muted';
    const label = interviewApplicant.closest('label') || interviewApplicant.parentElement;
    label.parentElement.insertBefore(info, label.nextSibling);
  }
  return info;
}

async function loadApplicantsForInterview() {
  interviewApplicant.innerHTML = '';
  $('#interviewApplicantInfo')?.remove();
  interviewApplicantsCache = {};

  if (!interviewsOffer.value) return;
  const res = await api('applicants.list', {offer_id: interviewsOffer.value}, 'GET');
  if (!res.ok) return;

  const fields = res.fields || [];
  const rows = res.rows || [];

  // Construimos opciones y cache
  interviewApplicant.innerHTML = rows.map(r => {
    const label = labelFromFirstTwoTextFields(fields, r.values) || `#${r.id}`;
    const summary = summaryFromTextFields(fields, r.values);
    interviewApplicantsCache[r.id] = { label, summary };
    return `<option value="${r.id}">${label}</option>`;
  }).join('');

  // Mostrar resumen del seleccionado
  const info = ensureApplicantInfoContainer();
  const curr = interviewApplicant.value;
  info.textContent = interviewApplicantsCache[curr]?.summary || '';

  // Actualizar resumen al cambiar de candidato
  interviewApplicant.addEventListener('change', () => {
    const sel = interviewApplicant.value;
    info.textContent = interviewApplicantsCache[sel]?.summary || '';
  });
}

async function loadInterviews() {
  interviewsTable.innerHTML = '';
  if (!interviewsOffer.value) return;
  const res = await api('interviews.list', {offer_id: interviewsOffer.value}, 'GET');
  if (!res.ok) return;

  // Enriquecer con summaries si existen en caché
  interviewsTable.innerHTML = `<tr>
    <th>ID</th><th>Candidato</th><th>Fecha/Hora</th><th>Nº</th><th>Resultado</th><th>Observaciones</th><th>Acciones</th>
  </tr>` + res.rows.map(r => {
    const extra = interviewApplicantsCache[r.applicant_id]?.summary;
    const extraHtml = extra ? `<div class="muted">${extra}</div>` : '';
    return `
    <tr>
      <td>${r.id}</td>
      <td data-applicant-id="${r.applicant_id}">
        ${r.applicant_label || ('#'+r.applicant_id)}
        ${extraHtml}
      </td>
      <td>${r.fecha_hora}</td>
      <td>${r.numero}</td>
      <td>${r.resultado||''}</td>
      <td>${r.observaciones||''}</td>
      <td>
        <button data-edit="${r.id}">Editar</button>
        <button class="secondary" data-del="${r.id}">Borrar</button>
      </td>
    </tr>`;
  }).join('');
}

interviewsTable?.addEventListener('click', async (e) => {
  const id = e.target.getAttribute('data-edit');
  const del = e.target.getAttribute('data-del');
  const tr = e.target.closest('tr');
  if (id) {
    interviewForm.id.value = id;
    interviewForm.applicant_id.value = tr.children[1].dataset.applicantId || '';
    const raw = tr.children[2].textContent.trim();
    const value = raw.replace(' ', 'T'); // compat input datetime-local
    interviewForm.fecha_hora.value = value;
    interviewForm.numero.value = tr.children[3].textContent.trim();
    interviewForm.resultado.value = tr.children[4].textContent.trim();
    interviewForm.observaciones.value = tr.children[5].textContent.trim();

    // Actualiza panel de info si existe
    const info = $('#interviewApplicantInfo');
    if (info) {
      const sel = interviewForm.applicant_id.value;
      info.textContent = interviewApplicantsCache[sel]?.summary || '';
    }
  }
  if (del && confirm('¿Borrar entrevista?')) {
    const r = await api('interviews.delete', {id:del});
    if (r.ok) loadInterviews();
  }
});

interviewForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = new FormData(interviewForm);
  const action = interviewForm.id.value ? 'interviews.update' : 'interviews.create';
  data.append('action', action);
  const r = await api(action, data);
  if (r.ok) { interviewForm.reset(); await loadInterviews(); }
  else toast(r.error||'Error');
});

$('#interviewReset')?.addEventListener('click', () => interviewForm.reset());

// Inicialización: refrescar selects y mantener pestaña activa inicial
(async () => {
  const res = await api('offers.list');
  if (res.ok) {
    refreshOffersSelects(res.rows);
  }

  const firstActive = $('.nav-link.active')?.dataset.tab || 'ofertas';
  activateTab(firstActive);
})();

