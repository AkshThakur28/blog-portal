(function () {
  'use strict';
  var postsDT = null;

  function getToken() {
    try {
      return localStorage.getItem('jwt_token') || '';
    } catch (e) {
      return '';
    }
  }

  function requireAuth() {
    function parsePayload(t) {
      try {
        var part = t.split('.')[1];
        if (!part) return null;
        part = part.replace(/-/g, '+').replace(/_/g, '/');
        var json = atob(part);
        return JSON.parse(json);
      } catch (_) { return null; }
    }

    const t = getToken();
    const redirectToLogin = function () {
      try {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('user_role');
        localStorage.removeItem('user_name');
      } catch (_) {}
      window.location.href = (window.API && API.login) ? API.login : '/login';
    };

    if (!t) {
      redirectToLogin();
      return false;
    }
    const p = parsePayload(t);
    const now = Math.floor(Date.now() / 1000);
    if (!p || !p.exp || p.exp <= now) {
      redirectToLogin();
      return false;
    }
    return true;
  }

  function getCurrentUser() {
    const t = getToken();
    if (!t) return null;
    try {
      var part = t.split('.')[1];
      if (!part) return null;
      part = part.replace(/-/g, '+').replace(/_/g, '/');
      var json = atob(part);
      var p = JSON.parse(json);
      return { id: p.sub, role: p.role };
    } catch (e) { return null; }
  }

  async function apiFetch(url, options) {
    const token = getToken();
    const opts = Object.assign({ headers: {} }, options || {});
    if (token) {
      opts.headers['Authorization'] = 'Bearer ' + token;
      opts.headers['X-Auth-Token'] = token;
    }
    if (!opts.headers['Content-Type'] && opts.body && !(opts.body instanceof FormData)) {
      opts.headers['Content-Type'] = 'application/json';
    }
    const resp = await fetch(url, opts);
    let data = null;
    try {
      data = await resp.json();
    } catch (_) {}
    if (!resp.ok) {
      const msg = data && data.error ? data.error : ('HTTP ' + resp.status);
      throw new Error(msg);
    }
    return data;
  }

  function initPostsDataTable() {
    const table = document.getElementById('postsTable');
    if (!table) return;
    const pag = document.getElementById('dashPagination');
    if (pag) pag.style.display = 'none';

    function esc(s){ return String(s).replace(/[&<>"']/g, function(c){ return ({'&':'&','<':'<','>':'>','"':'"',"'":'&#39;'}[c]); }); }

    postsDT = new DataTable(table, {
      responsive: true,
      paging: true,
      pageLength: 10,
      searching: true,
      processing: true,
      deferRender: true,
      order: [],
      autoWidth: false,
      ajax: function(dtParams, callback){
        const params = new URLSearchParams();
        params.set('page','1');
        params.set('per_page','1000');
        const qTitleEl = document.getElementById('searchTitle');
        const qAuthorEl = document.getElementById('searchAuthor');
        const qTitle = qTitleEl ? qTitleEl.value.trim() : '';
        const qAuthor = qAuthorEl ? qAuthorEl.value.trim() : '';
        if (qTitle) params.set('title', qTitle);
        if (qAuthor) params.set('author', qAuthor);

        apiFetch(API.posts + '?' + params.toString(), { method:'GET' })
          .then(function(resp){ callback({ data: resp.rows || [] }); })
          .catch(function(err){
            const uiError = document.getElementById('dashError');
            if (uiError) { uiError.textContent = err.message || 'Failed to load posts'; uiError.style.display = 'block'; }
            callback({ data: [] });
          });
      },
      columns: [
        { data: 'title', render: function(data, type, row){ return '<a href="'+API.viewPostBase+encodeURIComponent(row.slug)+'" target="_blank">'+esc(data)+'</a>'; } },
        { data: 'author' },
        { data: 'created_at', render: function(v){ try{ return new Date(v.replace(' ','T')).toLocaleDateString(); }catch(_){ return v; } } },
        { data: 'status', render: function(v){ return '<span class="badge ' + (v === 'deleted' ? 'badge--deleted' : 'badge--active') + '">' + esc(v) + '</span>'; } },
        { data: null, orderable:false, searchable:false, render: function(_d,_t,row){ return '<button class="btn btn-outline" data-action="edit" data-id="'+row.id+'">Edit</button> <button class="btn btn-danger" data-action="delete" data-id="'+row.id+'">Delete</button>'; } }
      ]
    });
  }

  async function loadPosts(page) {
    if (!requireAuth()) return;
    page = page || 1;

    const qTitle = document.getElementById('searchTitle') ? document.getElementById('searchTitle').value.trim() : '';
    const qAuthor = document.getElementById('searchAuthor') ? document.getElementById('searchAuthor').value.trim() : '';
    const perPage = window.API && API.perPage ? API.perPage : 10;

    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('per_page', String(perPage));
    if (qTitle) params.set('title', qTitle);
    if (qAuthor) params.set('author', qAuthor);

    const url = API.posts + '?' + params.toString();

    const uiError = document.getElementById('dashError');
    if (uiError) uiError.style.display = 'none';

    try {
      const data = await apiFetch(url, { method: 'GET' });
      renderPostsTable(data, page);
    } catch (e) {
      if (uiError) {
        uiError.textContent = e.message || 'Failed to load posts';
        uiError.style.display = 'block';
      }
    }
  }

  function renderPostsTable(data, currentPage) {
    const tbody = document.getElementById('postsTbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    (data.rows || []).forEach(function (row) {
      const tr = document.createElement('tr');

      const tdTitle = document.createElement('td');
      const a = document.createElement('a');
      a.href = API.viewPostBase + encodeURIComponent(row.slug);
      a.target = '_blank';
      a.textContent = row.title;
      tdTitle.appendChild(a);

      const tdAuthor = document.createElement('td');
      tdAuthor.textContent = row.author || '';

      const tdDate = document.createElement('td');
      try {
        tdDate.textContent = new Date(row.created_at.replace(' ', 'T')).toLocaleDateString();
      } catch (_) {
        tdDate.textContent = row.created_at;
      }

      const tdStatus = document.createElement('td');
      const badge = document.createElement('span');
      badge.className = 'badge ' + (row.status === 'deleted' ? 'badge--deleted' : 'badge--active');
      badge.textContent = row.status;
      tdStatus.appendChild(badge);

      const tdActions = document.createElement('td');
      tdActions.className = 'actions';
      const me = getCurrentUser();
      const canModify = !!(me && (me.role === 'admin' || (row.user_id && String(row.user_id) === String(me.id))));
      if (canModify) {
        if (row.status === 'deleted') {
          const btnRestore = document.createElement('button');
          btnRestore.className = 'btn btn-outline';
          btnRestore.textContent = 'Restore';
          btnRestore.setAttribute('data-action', 'restore');
          btnRestore.setAttribute('data-id', String(row.id));
          tdActions.appendChild(btnRestore);

          if (me && me.role === 'admin') {
            const btnHard = document.createElement('button');
            btnHard.className = 'btn btn-danger';
            btnHard.textContent = 'Hard Delete';
            btnHard.setAttribute('data-action', 'hard');
            btnHard.setAttribute('data-id', String(row.id));
            tdActions.appendChild(btnHard);
          }
        } else {
          const btnEdit = document.createElement('button');
          btnEdit.className = 'btn btn-outline';
          btnEdit.textContent = 'Edit';
          btnEdit.setAttribute('data-action', 'edit');
          btnEdit.setAttribute('data-id', String(row.id));
          tdActions.appendChild(btnEdit);

          const btnDelete = document.createElement('button');
          btnDelete.className = 'btn btn-danger';
          btnDelete.textContent = 'Soft Delete';
          btnDelete.setAttribute('data-action', 'delete');
          btnDelete.setAttribute('data-id', String(row.id));
          tdActions.appendChild(btnDelete);
        }
      }

      tr.appendChild(tdTitle);
      tr.appendChild(tdAuthor);
      tr.appendChild(tdDate);
      tr.appendChild(tdStatus);
      tr.appendChild(tdActions);

      tbody.appendChild(tr);
    });

    // Initialize/refresh DataTables on the rendered rows (client-side), only if library is present
    try { if (postsDT && typeof postsDT.destroy === 'function') { postsDT.destroy(); } } catch (_) {}
    var tableEl = document.getElementById('postsTable');
    if (tableEl && typeof window.DataTable === 'function') {
      postsDT = new DataTable(tableEl, {
        responsive: true,
        paging: true,
        pageLength: 10,
        searching: true,
        order: [],
        autoWidth: false
      });
    }

    const pag = document.getElementById('dashPagination');
    if (pag) {
      const total = data.total || 0;
      const perPage = window.API && API.perPage ? API.perPage : 10;
      const totalPages = Math.ceil(total / Math.max(1, perPage));
      pag.innerHTML = '';
      if (totalPages > 1) {
        if (currentPage > 1) {
          const prev = document.createElement('button');
          prev.className = 'btn btn-outline';
          prev.textContent = '« Prev';
          prev.addEventListener('click', function () { loadPosts(currentPage - 1); });
          pag.appendChild(prev);
        }
        const span = document.createElement('span');
        span.className = 'muted';
        span.style.margin = '0 10px';
        span.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        pag.appendChild(span);
        if (currentPage < totalPages) {
          const next = document.createElement('button');
          next.className = 'btn btn-outline';
          next.textContent = 'Next »';
          next.addEventListener('click', function () { loadPosts(currentPage + 1); });
          pag.appendChild(next);
        }
      }
    }
  }

  function doLogout() {
    try {
      localStorage.removeItem('jwt_token');
      localStorage.removeItem('user_role');
      localStorage.removeItem('user_name');
    } catch (_) {}
    window.location.href = (window.API && API.login) ? API.login : '/login';
  }

  function initSidebarDropdown() {
    const postsToggle = document.getElementById('postsToggle');
    const postsMenuGroup = postsToggle ? postsToggle.closest('.menu-group') : null;
    if (!postsToggle || !postsMenuGroup) return;

    function setPostsOpen(open) {
      if (open) {
        postsMenuGroup.classList.add('open');
        try { localStorage.setItem('postsMenuOpen', '1'); } catch (_) {}
      } else {
        postsMenuGroup.classList.remove('open');
        try { localStorage.removeItem('postsMenuOpen'); } catch (_) {}
      }
    }

    postsToggle.addEventListener('click', function () {
      const isOpen = postsMenuGroup.classList.contains('open');
      setPostsOpen(!isOpen);
    });

    let shouldOpen = false;
    try { shouldOpen = !!localStorage.getItem('postsMenuOpen'); } catch (_) {}
    if (document.getElementById('pagePosts') || document.getElementById('postsTable')) {
      shouldOpen = true;
    }
    setPostsOpen(shouldOpen);
  }

  function bindDashboardEvents() {
    const btnSearch = document.getElementById('btnSearch');
    if (btnSearch) {
      btnSearch.addEventListener('click', function () {
        if (postsDT && postsDT.ajax) {
          postsDT.ajax.reload();
        } else {
          loadPosts(1);
        }
      });
    }
    const btnCreate = document.getElementById('btnCreate');
    if (btnCreate) {
      btnCreate.addEventListener('click', function () {
        window.location.href = API.createPost;
      });
    }
    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
      btnLogout.addEventListener('click', doLogout);
    }
    const sideLogout = document.getElementById('sidebarLogout');
    if (sideLogout) {
      sideLogout.addEventListener('click', function (e) { e.preventDefault(); doLogout(); });
    }
    const linkCreate = document.getElementById('linkCreate');
    if (linkCreate) {
      linkCreate.addEventListener('click', function (e) { e.preventDefault(); window.location.href = API.createPost; });
    }
    const table = document.getElementById('postsTable');
    if (table) {
      table.addEventListener('click', async function (e) {
        const t = e.target;
        if (!(t instanceof HTMLElement)) return;
        const action = t.getAttribute('data-action');
        const id = t.getAttribute('data-id');
        if (!action || !id) return;

        if (action === 'edit') {
          window.location.href = API.editPostBase + id;
        } else if (action === 'delete') {
          const confirmMsg = 'Soft-delete this post? This will set status to Deleted and hide it from the public list.';
          if (!confirm(confirmMsg)) return;
          try {
            await apiFetch(API.posts + '/' + id, { method: 'DELETE' });
            const rowTr = t.closest('tr');
            if (rowTr) {
              const statusCell = rowTr.children[3];
              if (statusCell) {
                const badgeEl = statusCell.querySelector('.badge');
                if (badgeEl) {
                  badgeEl.textContent = 'deleted';
                  badgeEl.classList.remove('badge--active');
                  badgeEl.classList.add('badge--deleted');
                } else {
                  statusCell.innerHTML = '<span class="badge badge--deleted">deleted</span>';
                }
              }
              const actionsCell = rowTr.querySelector('td.actions') || rowTr.children[4];
              if (actionsCell) {
                const me = getCurrentUser();
                let html = '<button class="btn btn-outline" data-action="restore" data-id="'+id+'">Restore</button>';
                if (me && me.role === 'admin') {
                  html += ' <button class="btn btn-danger" data-action="hard" data-id="'+id+'">Hard Delete</button>';
                }
                actionsCell.innerHTML = html;
              }
            }
            try { if (postsDT && typeof postsDT.draw === 'function') { postsDT.draw(false); } } catch (_) {}
          } catch (err) {
            alert(err.message || 'Delete failed');
          }
        } else if (action === 'restore') {
          if (!confirm('Restore this post to Active?')) return;
          try {
            await apiFetch(API.posts + '/' + id + '/restore', { method: 'POST' });
            const rowTr = t.closest('tr');
            if (rowTr) {
              const statusCell = rowTr.children[3];
              if (statusCell) {
                const badgeEl = statusCell.querySelector('.badge');
                if (badgeEl) {
                  badgeEl.textContent = 'active';
                  badgeEl.classList.remove('badge--deleted');
                  badgeEl.classList.add('badge--active');
                } else {
                  statusCell.innerHTML = '<span class="badge badge--active">active</span>';
                }
              }
              const actionsCell = rowTr.querySelector('td.actions') || rowTr.children[4];
              if (actionsCell) {
                actionsCell.innerHTML = ''
                  + '<button class="btn btn-outline" data-action="edit" data-id="'+id+'">Edit</button> '
                  + '<button class="btn btn-danger" data-action="delete" data-id="'+id+'">Soft Delete</button>';
              }
            }
            try { if (postsDT && typeof postsDT.draw === 'function') { postsDT.draw(false); } } catch (_) {}
          } catch (err) {
            alert(err.message || 'Restore failed');
          }
        } else if (action === 'hard') {
          if (!confirm('PERMANENTLY delete this post? This cannot be undone.')) return;
          try {
            await apiFetch(API.posts + '/' + id + '/hard', { method: 'DELETE' });
            const rowTr = t.closest('tr');
            let removed = false;
            try {
              if (postsDT && typeof postsDT.row === 'function' && rowTr) {
                postsDT.row(rowTr).remove().draw(false);
                removed = true;
              }
            } catch (_) {}
            if (!removed && rowTr && rowTr.parentNode) {
              rowTr.parentNode.removeChild(rowTr);
              removed = true;
            }
            try { if (postsDT && typeof postsDT.draw === 'function') { postsDT.draw(false); } } catch (_) {}
          } catch (err) {
            alert(err.message || 'Hard delete failed');
          }
        }
      });
    }
  }

  async function loadPostIfEditing() {
    if (!requireAuth()) return;
    const formEl = document.getElementById('postForm');
    if (!formEl) return;

    const mode = formEl.getAttribute('data-mode');
    const postId = formEl.getAttribute('data-id');
    if (mode !== 'edit' || !postId) return;

    try {
      const data = await apiFetch(API.posts + '/' + postId, { method: 'GET' });
      document.getElementById('title').value = data.title || '';
      document.getElementById('body').value = data.body || '';
      if (data.cover_media_url) {
        document.getElementById('cover_media_url').value = data.cover_media_url;
        document.getElementById('media_type').value = data.media_type || '';
        renderCoverPreview(data.media_type, data.cover_media_url);
      }
    } catch (e) {
      alert(e.message || 'Failed to load post');
    }
  }

  function renderCoverPreview(type, url) {
    const box = document.getElementById('coverPreview');
    if (!box) return;
    box.innerHTML = '';
    if (!url) return;
    if (type === 'video') {
      const v = document.createElement('video');
      v.src = url;
      v.controls = true;
      v.style.maxWidth = '100%';
      v.style.maxHeight = '240px';
      box.appendChild(v);
    } else {
      const img = document.createElement('img');
      img.src = url;
      img.alt = '';
      img.style.maxWidth = '100%';
      img.style.maxHeight = '240px';
      img.style.objectFit = 'cover';
      box.appendChild(img);
    }
  }

  function bindFormEvents() {
    const formEl = document.getElementById('postForm');
    if (!formEl) return;

    const btnSave = document.getElementById('btnSave');
    const btnPixabay = document.getElementById('btnPixabay');

    if (btnSave) {
      btnSave.addEventListener('click', async function () {
        if (!requireAuth()) return;
        const title = document.getElementById('title').value.trim();
        const body = document.getElementById('body').value;
        const cover = document.getElementById('cover_media_url').value.trim();
        const mtype = document.getElementById('media_type').value.trim();

        if (!title || !body) {
          alert('Title and body are required');
          return;
        }

        const payload = {
          title: title,
          body: body,
          cover_media_url: cover || null,
          media_type: mtype || null
        };

        const mode = formEl.getAttribute('data-mode');
        const postId = formEl.getAttribute('data-id');

        try {
          if (mode === 'edit' && postId) {
            await apiFetch(API.posts + '/' + postId, {
              method: 'PUT',
              body: JSON.stringify(payload)
            });
          } else {
            await apiFetch(API.posts, {
              method: 'POST',
              body: JSON.stringify(payload)
            });
          }
          window.location.href = (API.postsPage || API.dashboard);
        } catch (e) {
          alert(e.message || 'Save failed');
        }
      });
    }

    if (btnPixabay) {
      btnPixabay.addEventListener('click', function () {
        openPixabayModal();
      });
    }

    const coverField = document.getElementById('cover_media_url');
    const mediaTypeField = document.getElementById('media_type');
    if (coverField) {
      coverField.addEventListener('input', function () {
        renderCoverPreview(mediaTypeField.value, coverField.value);
      });
    }
  }

  function ensureModal() {
    let modal = document.getElementById('pixModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'pixModal';
    modal.className = 'modal';

    modal.innerHTML = '' +
      '<div class="sheet">' +
      '  <div class="toolbar mb-2">' +
      '    <input id="pixQuery" class="input search-input" placeholder="Search Pixabay...">' +
      '    <select id="pixType" class="input" style="max-width:120px"><option value="image">Images</option><option value="video">Videos</option></select>' +
      '    <button id="pixSearch" class="btn">Search</button>' +
      '    <button id="pixClose" class="btn btn-outline">Close</button>' +
      '  </div>' +
      '  <div id="pixError" class="notice mb-2" style="display:none;color:#b91c1c"></div>' +
      '  <div id="pixGrid" class="grid"></div>' +
      '</div>';

    document.body.appendChild(modal);

    document.getElementById('pixClose').addEventListener('click', function () {
      modal.style.display = 'none';
    });
    document.getElementById('pixSearch').addEventListener('click', searchPixabay);

    return modal;
  }

  function openPixabayModal() {
    const modal = ensureModal();
    modal.style.display = 'flex';
    document.getElementById('pixQuery').focus();
  }

  async function searchPixabay() {
    const q = (document.getElementById('pixQuery').value || '').trim();
    const type = document.getElementById('pixType').value || 'image';
    const errorBox = document.getElementById('pixError');
    const grid = document.getElementById('pixGrid');

    errorBox.style.display = 'none';
    grid.innerHTML = '';

    if (!q) {
      errorBox.textContent = 'Enter a search term.';
      errorBox.style.display = 'block';
      return;
    }

    try {
      const params = new URLSearchParams();
      params.set('q', q);
      params.set('type', type);
      const data = await apiFetch(API.pixabay + '?' + params.toString(), { method: 'GET' });
      (data.hits || []).forEach(function (h) {
        if (!h) return;
        var thumbUrl = h.url || h.preview || '';
        if (!thumbUrl) return;
        const cell = document.createElement('div');
        cell.className = 'thumb';
        if (type === 'video') {
          const v = document.createElement('video');
          v.src = thumbUrl;
          v.muted = true;
          v.autoplay = true;
          v.loop = true;
          v.playsInline = true;
          cell.appendChild(v);
        } else {
          const img = document.createElement('img');
          img.src = thumbUrl;
          img.alt = h.tags || '';
          cell.appendChild(img);
        }
        cell.addEventListener('click', function () {
          const chosenUrl = thumbUrl;
          const inferredType = h.type || type || 'image';
          document.getElementById('cover_media_url').value = chosenUrl;
          document.getElementById('media_type').value = inferredType;
          renderCoverPreview(inferredType, chosenUrl);
          document.getElementById('pixModal').style.display = 'none';
        });
        grid.appendChild(cell);
      });
    } catch (e) {
      errorBox.textContent = e.message || 'Failed to search Pixabay';
      errorBox.style.display = 'block';
    }
  }

  function bindGlobalEvents() {
    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
      btnLogout.addEventListener('click', function () { doLogout(); });
    }
    const sideLogout = document.getElementById('sidebarLogout');
    if (sideLogout) {
      sideLogout.addEventListener('click', function (e) { e.preventDefault(); doLogout(); });
    }
  }

  function personalizeGreeting() {
    try {
      var name = localStorage.getItem('user_name') || '';
      if (name) {
        var span = document.getElementById('greetName');
        if (span) {
          span.textContent = name;
        }
      }
    } catch (_) {}
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSidebarDropdown();
    bindGlobalEvents();
    personalizeGreeting();

    if (document.getElementById('pagePosts')) {
      if (!requireAuth()) return;
      bindDashboardEvents();
      loadPosts(1);
    }

    if (document.getElementById('postForm')) {
      if (!requireAuth()) return;
      bindFormEvents();
      loadPostIfEditing();
    }
  });
})();
