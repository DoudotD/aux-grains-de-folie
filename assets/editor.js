/* Éditeur d'actualités : WYSIWYG léger, sans dépendance externe.
 * - contenteditable + document.execCommand pour la mise en forme de base
 * - images et vidéos insérées comme des « figures » avec options de mise en page
 * - le HTML est de toute façon re-nettoyé côté serveur (inc/sanitize.php)
 */
(() => {
  'use strict';

  const form = document.getElementById('post-form');
  const editor = document.getElementById('editor');
  if (!form || !editor) return;

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const base = form.dataset.base || '../';
  const csrf = form.querySelector('input[name="csrf"]').value;
  const field = document.getElementById('content');
  const state = { dirty: false, submitting: false, range: null };
  const enhanced = new WeakSet();

  try {
    document.execCommand('defaultParagraphSeparator', false, 'p');
    document.execCommand('styleWithCSS', false, false);
  } catch (_) { /* navigateur ancien : sans conséquence */ }

  /* ------------------------------------------------------------------ *
   * Utilitaires
   * ------------------------------------------------------------------ */

  const el = (tag, attrs = {}, text) => {
    const node = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) node.setAttribute(k, v);
    if (text !== undefined) node.textContent = text;
    return node;
  };

  const markDirty = () => {
    state.dirty = true;
    updateEmpty();
    scheduleAutosave();
  };

  const updateEmpty = () => {
    const empty = !editor.textContent.trim() && !editor.querySelector('figure, ul, ol, blockquote');
    editor.classList.toggle('is-empty', empty);
  };

  const ensureParagraphs = () => {
    if (!editor.firstChild) editor.innerHTML = '<p><br></p>';
    const last = editor.lastElementChild;
    if (!last || last.tagName === 'FIGURE') editor.append(Object.assign(document.createElement('p'), { innerHTML: '<br>' }));
    if (editor.firstElementChild && editor.firstElementChild.tagName === 'FIGURE') {
      editor.prepend(Object.assign(document.createElement('p'), { innerHTML: '<br>' }));
    }
  };

  const placeCaret = (node, atEnd = false) => {
    const range = document.createRange();
    range.selectNodeContents(node);
    range.collapse(!atEnd);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
    state.range = range.cloneRange();
  };

  const restoreSelection = () => {
    editor.focus({ preventScroll: true });
    const sel = window.getSelection();
    if (state.range && editor.contains(state.range.commonAncestorContainer)) {
      sel.removeAllRanges();
      sel.addRange(state.range);
    } else {
      placeCaret(editor.lastElementChild || editor, true);
    }
  };

  /** Bloc de premier niveau (enfant direct de l'éditeur) contenant la sélection. */
  const topBlock = () => {
    const sel = window.getSelection();
    let node = sel.rangeCount && editor.contains(sel.anchorNode) ? sel.anchorNode : null;
    if (!node && state.range) node = state.range.startContainer;
    while (node && node.parentNode !== editor) node = node.parentNode;
    return node && node.nodeType === 1 ? node : null;
  };

  const exec = (cmd, value) => {
    restoreSelection();
    document.execCommand(cmd, false, value);
    markDirty();
    updateToolbar();
  };

  /* ------------------------------------------------------------------ *
   * Liens : rattrape les saisies de novices (miroir de normalize_link() en PHP)
   * ------------------------------------------------------------------ */

  const normalizeLink = (u) => {
    u = u.trim();
    if (!u || /^[a-z][a-z0-9+.\-]*:/i.test(u) || /^[/#?]/.test(u)) return u;
    if (/^[^\s@/]+@[^\s@/]+\.[a-z]{2,}$/i.test(u)) return 'mailto:' + u;
    if (/^\+?[\d\s.\-()]{8,}$/.test(u)) return 'tel:' + u.replace(/[\s.\-()]/g, '');
    if (!/\.(html?|php)([#?/].*)?$/i.test(u) && /^(?:www\.)?[a-z0-9-]+(?:\.[a-z0-9-]+)*\.[a-z]{2,}(?:[/?#].*)?$/i.test(u)) return 'https://' + u;
    return u;
  };

  const linkOk = (u) => {
    const m = /^([a-z][a-z0-9+.\-]*):/i.exec(u);
    if (m) return ['http', 'https', 'mailto', 'tel'].includes(m[1].toLowerCase());
    return /^[/#]/.test(u) || /\.(html?|php)([#?/].*)?$/i.test(u);
  };

  const parseVideo = (url) => {
    url = url.trim();
    let m;
    if ((m = /^(?:https?:\/\/)?(?:www\.|m\.)?youtu\.be\/([\w-]{11})/i.exec(url))) return { provider: 'youtube', id: m[1] };
    if ((m = /^(?:https?:\/\/)?(?:www\.|m\.)?youtube(?:-nocookie)?\.com\/(?:watch\?(?:[^\s#]*&)?v=|embed\/|shorts\/|live\/)([\w-]{11})/i.exec(url))) return { provider: 'youtube', id: m[1] };
    if ((m = /^(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:[^\s/]+\/)*?(\d{5,12})(?:$|[/?#])/i.exec(url))) return { provider: 'vimeo', id: m[1] };
    if ((m = /^(?:https?:\/\/)?player\.vimeo\.com\/video\/(\d{5,12})/i.exec(url))) return { provider: 'vimeo', id: m[1] };
    return null;
  };

  /* ------------------------------------------------------------------ *
   * Images et vidéos : « figures » avec contrôles intégrés
   * ------------------------------------------------------------------ */

  const LAYOUTS = [
    ['left', 'À gauche'],
    ['center', 'Centrée'],
    ['right', 'À droite'],
    ['full', 'Pleine largeur'],
  ];

  const enhanceFigure = (fig) => {
    if (enhanced.has(fig)) return;
    enhanced.add(fig);
    fig.setAttribute('contenteditable', 'false');
    const isVideo = fig.classList.contains('fig-video');

    let cap = fig.querySelector('figcaption');
    if (!cap) {
      cap = el('figcaption');
      fig.append(cap);
    }
    cap.setAttribute('contenteditable', 'true');
    cap.setAttribute('role', 'textbox');
    cap.setAttribute('aria-label', isVideo ? 'Légende de la vidéo (facultatif)' : 'Légende de l’image (facultatif)');
    cap.setAttribute('data-placeholder', 'Légende (facultatif)');

    if (isVideo) {
      const name = fig.getAttribute('data-provider') === 'vimeo' ? 'Vimeo' : 'YouTube';
      const ph = el('div', { 'data-ui': '', contenteditable: 'false', class: 'video-placeholder' });
      ph.append(el('span', { 'aria-hidden': 'true', class: 'video-ph-icon' }, '▶'));
      ph.append(el('span', {}, `Vidéo ${name} — elle s’affichera ici sur le site (voir « Aperçu avant publication »).`));
      fig.insertBefore(ph, cap);
    }

    const controls = el('div', { 'data-ui': '', contenteditable: 'false', class: 'fig-controls', role: 'group', 'aria-label': isVideo ? 'Options de la vidéo' : 'Options de l’image' });
    if (!isVideo) {
      for (const [value, label] of LAYOUTS) {
        const active = fig.classList.contains('fig-' + value);
        controls.append(el('button', { type: 'button', 'data-fig-layout': value, 'aria-pressed': String(active) }, label));
      }
      controls.append(el('button', { type: 'button', 'data-fig-alt': '' }, 'Description…'));
    }
    controls.append(el('button', { type: 'button', 'data-fig-delete': '', class: 'is-danger' }, isVideo ? 'Supprimer la vidéo' : 'Supprimer l’image'));
    fig.append(controls);
  };

  const enhanceAll = () => $$('figure', editor).forEach(enhanceFigure);

  const makeImageFigure = ({ src, alt, layout, caption }) => {
    const fig = el('figure', { class: 'fig-' + layout });
    fig.append(el('img', { src: base + src, alt }));
    if (caption) fig.append(el('figcaption', {}, caption));
    enhanceFigure(fig);
    return fig;
  };

  const makeVideoFigure = ({ provider, id, caption }) => {
    const fig = el('figure', { class: 'fig-video', 'data-provider': provider, 'data-id': id });
    if (caption) fig.append(el('figcaption', {}, caption));
    enhanceFigure(fig);
    return fig;
  };

  const insertBlock = (node) => {
    restoreSelection();
    const blk = topBlock();
    if (!blk) editor.append(node);
    else if (blk.tagName === 'P' && !blk.textContent.trim() && !blk.querySelector('img')) blk.replaceWith(node);
    else blk.after(node);
    ensureParagraphs();
    const next = node.nextElementSibling;
    if (next) placeCaret(next);
    markDirty();
  };

  editor.addEventListener('click', (event) => {
    const btn = event.target.closest('button');
    const fig = event.target.closest('figure');
    if (!btn || !fig || !editor.contains(fig)) return;

    if (btn.hasAttribute('data-fig-layout')) {
      const value = btn.getAttribute('data-fig-layout');
      fig.className = 'fig-' + value;
      $$('[data-fig-layout]', fig).forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
      markDirty();
    } else if (btn.hasAttribute('data-fig-delete')) {
      const next = fig.nextElementSibling || fig.previousElementSibling;
      fig.remove();
      ensureParagraphs();
      if (next) placeCaret(next);
      markDirty();
    } else if (btn.hasAttribute('data-fig-alt')) {
      openImageDialog(fig);
    }
  });

  /* ------------------------------------------------------------------ *
   * Barre d'outils
   * ------------------------------------------------------------------ */

  const toolbar = $('.toolbar');

  // Garde la sélection dans le texte quand on clique sur un bouton
  toolbar.addEventListener('mousedown', (event) => {
    if (event.target.closest('button')) event.preventDefault();
  });

  const toggleBlock = (tag) => {
    restoreSelection();
    const current = topBlock();
    const active = current && current.tagName.toLowerCase() === tag;
    if (tag === 'blockquote' && active) {
      while (current.firstChild) current.before(current.firstChild);
      current.remove();
      ensureParagraphs();
    } else {
      document.execCommand('formatBlock', false, active ? '<p>' : `<${tag}>`);
    }
    markDirty();
    updateToolbar();
  };

  toolbar.addEventListener('click', (event) => {
    const btn = event.target.closest('button');
    if (!btn) return;
    if (btn.dataset.cmd) exec(btn.dataset.cmd);
    else if (btn.dataset.block) toggleBlock(btn.dataset.block);
    else if (btn.dataset.open === 'link') openLinkDialog();
    else if (btn.dataset.open === 'image') openImageDialog(null);
    else if (btn.dataset.open === 'video') openVideoDialog();
  });

  const updateToolbar = () => {
    const block = topBlock();
    const tag = block ? block.tagName.toLowerCase() : '';
    $$('[data-cmd][aria-pressed]', toolbar).forEach((b) => {
      let on = false;
      try { on = document.queryCommandState(b.dataset.cmd); } catch (_) { /* ignoré */ }
      b.setAttribute('aria-pressed', String(on));
    });
    $$('[data-block]', toolbar).forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.block === tag)));
  };

  document.addEventListener('selectionchange', () => {
    const sel = window.getSelection();
    if (sel.rangeCount && editor.contains(sel.anchorNode)) {
      state.range = sel.getRangeAt(0).cloneRange();
      updateToolbar();
    }
  });

  /* ------------------------------------------------------------------ *
   * Clavier, collage, glisser-déposer
   * ------------------------------------------------------------------ */

  editor.addEventListener('keydown', (event) => {
    const inCaption = event.target.closest && event.target.closest('figcaption');
    if (event.key === 'Enter' && inCaption) {
      event.preventDefault();
      const next = inCaption.closest('figure').nextElementSibling;
      if (next) placeCaret(next);
    }
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      openLinkDialog();
    }
  });

  editor.addEventListener('input', () => {
    if (!editor.firstChild || editor.innerHTML === '<br>') {
      editor.innerHTML = '<p><br></p>';
      placeCaret(editor.firstChild);
    }
    ensureParagraphs();
  });

  const escapeHtml = (s) => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

  /** Nettoie du HTML collé (Word, sites web…) : ne garde que la mise en forme utile. */
  const cleanPasted = (html) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const walk = (node) => {
      if (node.nodeType === Node.TEXT_NODE) return escapeHtml(node.nodeValue.replace(/\s+/g, ' '));
      if (node.nodeType !== Node.ELEMENT_NODE) return '';
      const tag = node.tagName.toLowerCase();
      if (['script', 'style', 'iframe', 'object', 'svg', 'img', 'figure', 'video', 'audio', 'head'].includes(tag)) return '';
      const inner = [...node.childNodes].map(walk).join('');
      switch (tag) {
        case 'strong': case 'b': return inner.trim() ? `<strong>${inner}</strong>` : inner;
        case 'em': case 'i': return inner.trim() ? `<em>${inner}</em>` : inner;
        case 'br': return '<br>';
        case 'p': case 'div': return inner.trim() ? `<p>${inner}</p>` : '';
        case 'h1': case 'h2': return inner.trim() ? `<h2>${inner}</h2>` : '';
        case 'h3': case 'h4': case 'h5': case 'h6': return inner.trim() ? `<h3>${inner}</h3>` : '';
        case 'ul': case 'ol': return `<${tag}>${inner}</${tag}>`;
        case 'li': return `<li>${inner}</li>`;
        case 'blockquote': return `<blockquote>${inner}</blockquote>`;
        case 'a': {
          const href = normalizeLink(node.getAttribute('href') || '');
          return href && linkOk(href) ? `<a href="${escapeHtml(href)}">${inner}</a>` : inner;
        }
        default: return inner;
      }
    };
    return walk(doc.body);
  };

  editor.addEventListener('paste', (event) => {
    const data = event.clipboardData;
    if (!data) return;
    event.preventDefault();
    const inCaption = event.target.closest && event.target.closest('figcaption');
    const text = data.getData('text/plain');
    if (inCaption) {
      document.execCommand('insertText', false, text.replace(/\s+/g, ' '));
      return;
    }
    const html = data.getData('text/html');
    if (html) {
      document.execCommand('insertHTML', false, cleanPasted(html));
    } else {
      const paras = text.split(/\n{2,}/).map((t) => `<p>${escapeHtml(t).replace(/\n/g, '<br>')}</p>`).join('');
      document.execCommand('insertHTML', false, paras);
    }
    markDirty();
  });

  // Empêche le navigateur d'ouvrir une image déposée à côté de l'éditeur
  for (const type of ['dragover', 'drop']) {
    editor.addEventListener(type, (event) => {
      if (event.dataTransfer && [...event.dataTransfer.types].includes('Files')) event.preventDefault();
    });
  }

  /* ------------------------------------------------------------------ *
   * Boîtes de dialogue
   * ------------------------------------------------------------------ */

  const setError = (node, message) => {
    node.textContent = message || '';
    node.hidden = !message;
  };

  $$('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
      if (event.target.closest('[data-close]')) dialog.close();
    });
  });

  /* ---- Lien ---- */
  const linkDialog = $('#link-dialog');
  const linkForm = $('#link-form');
  const linkUrl = $('#link-url');
  const linkText = $('#link-text');
  const linkErr = $('#link-err');
  let linkTarget = null;

  const closestLink = () => {
    const sel = window.getSelection();
    const node = sel.rangeCount ? sel.anchorNode : null;
    const elNode = node && (node.nodeType === 1 ? node : node.parentElement);
    const a = elNode && elNode.closest('a');
    return a && editor.contains(a) ? a : null;
  };

  function openLinkDialog() {
    restoreSelection();
    linkTarget = closestLink();
    const selected = window.getSelection().toString();
    linkUrl.value = linkTarget ? linkTarget.getAttribute('href') : '';
    linkText.value = '';
    $('#link-text-field').hidden = !!selected || !!linkTarget;
    $('#link-remove').hidden = !linkTarget;
    setError(linkErr, '');
    linkDialog.showModal();
  }

  linkForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const url = normalizeLink(linkUrl.value);
    if (!url || !linkOk(url)) {
      setError(linkErr, 'Cette adresse ne semble pas valide. Exemples : https://www.exemple.fr, 02 51 00 00 00 ou nom@exemple.fr');
      linkUrl.focus();
      return;
    }
    const needsText = !$('#link-text-field').hidden;
    if (needsText && !linkText.value.trim()) {
      setError(linkErr, 'Indiquez le texte du lien (ce que les lecteurs verront).');
      linkText.focus();
      return;
    }
    linkDialog.close();
    restoreSelection();
    if (linkTarget) {
      linkTarget.setAttribute('href', url);
    } else if (needsText) {
      document.execCommand('insertHTML', false, `<a href="${escapeHtml(url)}">${escapeHtml(linkText.value.trim())}</a>&nbsp;`);
    } else {
      document.execCommand('createLink', false, url);
    }
    markDirty();
  });

  $('#link-remove').addEventListener('click', () => {
    linkDialog.close();
    restoreSelection();
    if (linkTarget) {
      const range = document.createRange();
      range.selectNodeContents(linkTarget);
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
      document.execCommand('unlink');
      markDirty();
    }
  });

  /* ---- Envoi d'image (éditeur + image à la une) ---- */
  const MAX_BYTES = Number(form.dataset.maxBytes) || 2 * 1024 * 1024; // limite réelle du serveur
  const MAX_LABEL = form.dataset.maxLabel || '2 Mo';
  const MAX_SIDE = 1800; // même plafond que le serveur

  const canvasToBlob = (canvas, type, quality) => new Promise((resolve) => canvas.toBlob(resolve, type, quality));

  /**
   * Réduit la photo dans le navigateur avant l'envoi : une photo de téléphone
   * (5 Mo et plus) passe ainsi sous la limite du serveur sans que l'utilisateur
   * ait quoi que ce soit à faire. En cas d'échec, on renvoie le fichier d'origine.
   */
  async function prepareImage(file) {
    if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type) || !window.createImageBitmap) return file;
    let bitmap;
    try {
      bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    } catch (_) {
      try { bitmap = await createImageBitmap(file); } catch (__) { return file; }
    }
    const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
    const small = file.size <= Math.min(MAX_BYTES, 1.5 * 1024 * 1024);
    if (scale === 1 && small) { bitmap.close && bitmap.close(); return file; }

    const draw = (factor, flatten) => {
      const canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(bitmap.width * scale * factor));
      canvas.height = Math.max(1, Math.round(bitmap.height * scale * factor));
      const ctx = canvas.getContext('2d');
      if (flatten) { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height); }
      ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      return canvas;
    };

    let best = null;
    const attempts = file.type === 'image/png'
      ? [['image/png', 1, 1, false], ['image/jpeg', 0.85, 1, true], ['image/jpeg', 0.7, 0.8, true]]
      : [['image/jpeg', 0.85, 1, true], ['image/jpeg', 0.7, 1, true], ['image/jpeg', 0.6, 0.8, true], ['image/jpeg', 0.55, 0.6, true]];
    for (const [type, quality, factor, flatten] of attempts) {
      const blob = await canvasToBlob(draw(factor, flatten), type, quality);
      if (!blob) continue;
      if (!best || blob.size < best.size) best = blob;
      if (blob.size <= MAX_BYTES * 0.9) { best = blob; break; }
    }
    bitmap.close && bitmap.close();
    if (!best || best.size >= file.size) return file;
    const ext = best.type === 'image/png' ? '.png' : '.jpg';
    return new File([best], (file.name.replace(/\.[^.]+$/, '') || 'image') + ext, { type: best.type });
  }

  async function uploadImage(original) {
    const file = await prepareImage(original);
    if (file.size > MAX_BYTES) {
      throw new Error(`Cette image est trop volumineuse : ce site accepte au maximum ${MAX_LABEL} par image.`);
    }
    const body = new FormData();
    body.append('image', file);
    body.append('csrf', csrf);
    let res;
    try {
      res = await fetch(form.dataset.uploadUrl, { method: 'POST', body, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } });
    } catch (_) {
      throw new Error('Connexion impossible. Vérifiez votre accès internet puis réessayez.');
    }
    let data = null;
    try { data = await res.json(); } catch (_) { /* réponse non JSON */ }
    if (!res.ok || !data || !data.ok) throw new Error((data && data.error) || 'L’envoi de l’image a échoué. Réessayez.');
    return data.src;
  }

  /* ---- Image ---- */
  const imageDialog = $('#image-dialog');
  const imageForm = $('#image-form');
  const imageFile = $('#image-file');
  const imageStatus = $('#image-status');
  const imagePreview = $('#image-preview');
  const imagePreviewImg = $('#image-preview-img');
  const imageAlt = $('#image-alt');
  const imageDecor = $('#image-decor');
  const imageCaption = $('#image-caption');
  const imageErr = $('#image-err');
  const imageSubmit = $('#image-submit');
  const image = { fig: null, src: '' };

  imageDecor.addEventListener('change', () => {
    imageAlt.disabled = imageDecor.checked;
    if (imageDecor.checked) imageAlt.value = '';
  });

  function openImageDialog(fig) {
    if (!fig) restoreSelection();
    image.fig = fig;
    image.src = '';
    imageFile.value = '';
    imageStatus.textContent = '';
    setError(imageErr, '');
    imageDecor.checked = false;
    imageAlt.disabled = false;
    $('#image-title').textContent = fig ? 'Modifier l’image' : 'Ajouter une image';
    imageSubmit.textContent = fig ? 'Enregistrer' : 'Insérer l’image';
    $('#image-file-field').hidden = !!fig;
    if (fig) {
      const img = $('img', fig);
      imagePreviewImg.src = img.getAttribute('src');
      imagePreview.hidden = false;
      imageAlt.value = img.getAttribute('alt') || '';
      imageCaption.value = ($('figcaption', fig) || {}).textContent || '';
      const layout = (LAYOUTS.find(([v]) => fig.classList.contains('fig-' + v)) || ['center'])[0];
      $$('input[name="image-layout"]', imageForm).forEach((r) => { r.checked = r.value === layout; });
    } else {
      imagePreview.hidden = true;
      imageAlt.value = '';
      imageCaption.value = '';
      $$('input[name="image-layout"]', imageForm).forEach((r) => { r.checked = r.value === 'center'; });
    }
    imageDialog.showModal();
  }

  imageFile.addEventListener('change', async () => {
    const file = imageFile.files[0];
    if (!file) return;
    setError(imageErr, '');
    imageStatus.textContent = 'Optimisation et envoi de l’image en cours…';
    imageSubmit.disabled = true;
    try {
      image.src = await uploadImage(file);
      imagePreviewImg.src = base + image.src;
      imagePreview.hidden = false;
      imageStatus.textContent = 'Image prête. Décrivez-la ci-dessous.';
      imageAlt.focus();
    } catch (err) {
      image.src = '';
      imagePreview.hidden = true;
      imageStatus.textContent = '';
      setError(imageErr, err.message);
    } finally {
      imageSubmit.disabled = false;
    }
  });

  imageForm.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!image.fig && !image.src) {
      setError(imageErr, 'Choisissez d’abord une photo sur votre ordinateur ou votre téléphone.');
      imageFile.focus();
      return;
    }
    const alt = imageDecor.checked ? '' : imageAlt.value.trim();
    if (!imageDecor.checked && !alt) {
      setError(imageErr, 'Décrivez l’image en quelques mots, ou cochez « purement décorative ».');
      imageAlt.focus();
      return;
    }
    const layout = $('input[name="image-layout"]:checked', imageForm).value;
    const caption = imageCaption.value.trim();
    imageDialog.close();

    if (image.fig) {
      const fig = image.fig;
      $('img', fig).setAttribute('alt', alt);
      fig.className = 'fig-' + layout;
      $$('[data-fig-layout]', fig).forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.figLayout === layout)));
      $('figcaption', fig).textContent = caption;
      markDirty();
    } else {
      insertBlock(makeImageFigure({ src: image.src, alt, layout, caption }));
    }
  });

  /* ---- Vidéo ---- */
  const videoDialog = $('#video-dialog');
  const videoForm = $('#video-form');
  const videoUrl = $('#video-url');
  const videoCaption = $('#video-caption');
  const videoErr = $('#video-err');

  function openVideoDialog() {
    restoreSelection();
    videoUrl.value = '';
    videoCaption.value = '';
    setError(videoErr, '');
    videoDialog.showModal();
  }

  videoForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const video = parseVideo(videoUrl.value);
    if (!video) {
      setError(videoErr, 'Cette adresse n’est pas reconnue. Collez un lien YouTube (youtube.com ou youtu.be) ou Vimeo.');
      videoUrl.focus();
      return;
    }
    videoDialog.close();
    insertBlock(makeVideoFigure({ ...video, caption: videoCaption.value.trim() }));
  });

  /* ------------------------------------------------------------------ *
   * Image à la une
   * ------------------------------------------------------------------ */

  const coverSrc = $('#cover_src');
  const coverPreview = $('#cover-preview');
  const coverImg = $('#cover-img');
  const coverFile = $('#cover-file');
  const coverChoose = $('#cover-choose');
  const coverRemove = $('#cover-remove');
  const coverAltField = $('#cover-alt-field');
  const coverStatus = $('#cover-status');

  const showCover = (src) => {
    coverSrc.value = src;
    coverPreview.hidden = !src;
    coverRemove.hidden = !src;
    coverAltField.hidden = !src;
    coverChoose.textContent = src ? 'Changer l’image' : 'Choisir une image';
    if (src) coverImg.src = base + src;
  };

  coverChoose.addEventListener('click', () => coverFile.click());
  coverFile.addEventListener('change', async () => {
    const file = coverFile.files[0];
    if (!file) return;
    coverStatus.textContent = 'Optimisation et envoi de l’image en cours…';
    try {
      showCover(await uploadImage(file));
      coverStatus.textContent = 'Image ajoutée. Pensez à la décrire ci-dessous.';
      $('#cover_alt').focus();
      markDirty();
    } catch (err) {
      coverStatus.textContent = err.message;
    }
    coverFile.value = '';
  });
  coverRemove.addEventListener('click', () => {
    showCover('');
    $('#cover_alt').value = '';
    coverStatus.textContent = 'Image retirée.';
    markDirty();
  });

  /* ------------------------------------------------------------------ *
   * Boutons d'action (CTA)
   * ------------------------------------------------------------------ */

  const ctaList = $('#cta-list');
  const ctaAdd = $('#cta-add');
  const ctaRows = () => $$('[data-cta]', ctaList);

  const refreshCtas = () => {
    const visible = ctaRows().filter((r) => !r.hidden);
    visible.forEach((row, i) => { $('legend', row).textContent = 'Bouton ' + (i + 1); });
    ctaAdd.hidden = visible.length >= ctaRows().length;
  };

  const restoreCtas = (list) => {
    ctaRows().forEach((row) => {
      $$('input', row).forEach((i) => { i.value = ''; });
      $('select', row).value = 'primary';
      row.hidden = true;
    });
    list.slice(0, ctaRows().length).forEach((c, i) => {
      const row = ctaRows()[i];
      $('input[name$="[label]"]', row).value = c.label || '';
      $('input[name$="[url]"]', row).value = c.url || '';
      $('select', row).value = c.style === 'secondary' ? 'secondary' : 'primary';
      row.hidden = false;
    });
    refreshCtas();
  };

  ctaAdd.addEventListener('click', () => {
    const row = ctaRows().find((r) => r.hidden);
    if (!row) return;
    row.hidden = false;
    refreshCtas();
    $('input', row).focus();
    markDirty();
  });

  ctaList.addEventListener('click', (event) => {
    if (!event.target.closest('[data-cta-remove]')) return;
    const row = event.target.closest('[data-cta]');
    $$('input', row).forEach((i) => { i.value = ''; });
    $('select', row).value = 'primary';
    row.hidden = true;
    ctaList.append(row); // les lignes masquées passent en fin de liste
    refreshCtas();
    ctaAdd.focus();
    markDirty();
  });

  /* ------------------------------------------------------------------ *
   * Aperçu avant publication
   * ------------------------------------------------------------------ */

  const previewDialog = $('#preview-dialog');
  const previewStage = $('#preview-stage');

  $$('.segmented button', previewDialog).forEach((btn) => {
    btn.addEventListener('click', () => {
      previewStage.dataset.size = btn.dataset.size;
      $$('.segmented button', previewDialog).forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
    });
  });

  /* ------------------------------------------------------------------ *
   * Enregistrement
   * ------------------------------------------------------------------ */

  /** HTML propre à envoyer au serveur (sans les contrôles de l'éditeur). */
  const serialize = () => {
    const clone = editor.cloneNode(true);
    $$('[data-ui]', clone).forEach((n) => n.remove());
    $$('[contenteditable], [role], [aria-label], [data-placeholder]', clone).forEach((n) => {
      ['contenteditable', 'role', 'aria-label', 'data-placeholder'].forEach((a) => n.removeAttribute(a));
    });
    $$('figcaption', clone).forEach((c) => { if (!c.textContent.trim()) c.remove(); });
    $$('img', clone).forEach((img) => img.setAttribute('src', (img.getAttribute('src') || '').replace(/^(\.\.\/)+/, '')));
    return clone.innerHTML;
  };

  form.addEventListener('submit', (event) => {
    field.value = serialize();
    if (event.submitter && event.submitter.hasAttribute('data-preview')) {
      if (!previewDialog.open) previewDialog.showModal();
      return; // la soumission continue dans l'iframe d'aperçu
    }
    state.submitting = true;
    try { localStorage.removeItem(draftKey); } catch (_) { /* ignoré */ }
  });

  window.addEventListener('beforeunload', (event) => {
    if (state.dirty && !state.submitting) {
      event.preventDefault();
      event.returnValue = '';
    }
  });

  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);

  // Bouton « Publier » ↔ « Programmer » selon la date
  const dateInput = $('#published_at');
  const publishBtn = $('#publish-btn');
  const publishLabel = publishBtn.textContent;
  const updatePublishLabel = () => {
    if (publishBtn.value !== 'publish') return;
    const future = dateInput.value && new Date(dateInput.value) > new Date();
    publishBtn.textContent = future ? 'Programmer la publication' : publishLabel;
  };
  dateInput.addEventListener('input', updatePublishLabel);

  /* ------------------------------------------------------------------ *
   * Sauvegarde automatique locale (filet de sécurité)
   * ------------------------------------------------------------------ */

  const draftKey = 'gdf-draft-' + (form.dataset.postId || 'new');
  let autosaveTimer = 0;

  const snapshot = () => ({
    t: Date.now(),
    title: form.elements.title.value,
    content: serialize(),
    cover_src: coverSrc.value,
    cover_alt: form.elements.cover_alt.value,
    ctas: ctaRows().filter((r) => !r.hidden).map((r) => ({
      label: $('input[name$="[label]"]', r).value,
      url: $('input[name$="[url]"]', r).value,
      style: $('select', r).value,
    })),
  });

  function scheduleAutosave() {
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => {
      try { localStorage.setItem(draftKey, JSON.stringify(snapshot())); } catch (_) { /* stockage indisponible */ }
    }, 1500);
  }

  const banner = $('#restore-banner');
  let saved = null;
  try { saved = JSON.parse(localStorage.getItem(draftKey) || 'null'); } catch (_) { saved = null; }

  /* ------------------------------------------------------------------ *
   * Initialisation
   * ------------------------------------------------------------------ */

  editor.innerHTML = field.value;
  ensureParagraphs();
  enhanceAll();
  updateEmpty();
  refreshCtas();
  updatePublishLabel();

  const fingerprint = (title, content, ctas) => JSON.stringify([title, content, (ctas || []).map((c) => [c.label, c.url, c.style])]);
  const initial = fingerprint(form.elements.title.value, serialize(), snapshot().ctas);
  if (saved && saved.t > (Number(form.dataset.updated) || 0) * 1000
      && fingerprint(saved.title, saved.content, saved.ctas) !== initial) {
    $('[data-restore-date]', banner).textContent = new Date(saved.t).toLocaleString('fr-FR', { dateStyle: 'long', timeStyle: 'short' });
    banner.hidden = false;
    banner.addEventListener('click', (event) => {
      const choice = event.target.closest('[data-restore]');
      if (!choice) return;
      if (choice.dataset.restore === 'yes') {
        form.elements.title.value = saved.title;
        editor.innerHTML = saved.content;
        ensureParagraphs();
        enhanceAll();
        showCover(saved.cover_src || '');
        form.elements.cover_alt.value = saved.cover_alt || '';
        restoreCtas(saved.ctas || []);
        markDirty();
      } else {
        try { localStorage.removeItem(draftKey); } catch (_) { /* ignoré */ }
      }
      banner.hidden = true;
    });
  }

  // L'événement « input » déclenché par l'initialisation ne doit pas marquer le formulaire modifié
  state.dirty = false;
})();
