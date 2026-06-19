/* Giao diện viewer heritage. Gọi: HeritageUI.init(cfg, krpano) sau khi krpano ready. */
(function () {
  const ICON = {
    home: '<svg viewBox="0 0 24 24"><path d="M12 3l9 8h-3v9h-4v-6h-4v6H6v-9H3z"/></svg>',
    map: '<svg viewBox="0 0 24 24"><path d="M15 4l-6 2L3 4v16l6-2 6 2 6-2V2l-6 2zm0 14l-6-2V6l6 2v10z"/></svg>',
    play: '<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>',
    pause: '<svg viewBox="0 0 24 24"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>',
    gallery: '<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zm2 2v8l3-3 2 2 4-4 3 3V7z"/></svg>',
    info: '<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-6h2zm0-8h-2V7h2z"/></svg>',
    full: '<svg viewBox="0 0 24 24"><path d="M4 4h6V2H2v8h2zm10-2v2h6v6h2V2zM4 14H2v8h8v-2H4zm16 6h-6v2h8v-8h-2z"/></svg>',
    share: '<svg viewBox="0 0 24 24"><path d="M18 16a3 3 0 00-2 .8l-7-4a3 3 0 000-1.6l7-4A3 3 0 1015 5l-7 4a3 3 0 100 6l7 4a3 3 0 103-3z"/></svg>',
    audio: '<svg viewBox="0 0 24 24"><path d="M3 10v4h4l5 5V5L7 10zm13 2a4 4 0 00-2-3.5v7A4 4 0 0016 12z"/></svg>',
    gear: '<svg viewBox="0 0 24 24"><path d="M12 8a4 4 0 100 8 4 4 0 000-8zm9 4l-2-1.5.3-2.5-2.4-.7-1-2.3-2.4 1L12 3l-1.5 2.5-2.4-1-1 2.3-2.4.7.3 2.5L3 12l2 1.5-.3 2.5 2.4.7 1 2.3 2.4-1L12 21l1.5-2.5 2.4 1 1-2.3 2.4-.7-.3-2.5z"/></svg>',
  };
  // Icon lớn cho panel cài đặt
  const BIG = {
    rotate: '<svg viewBox="0 0 24 24"><path d="M12 6V3L8 7l4 4V8a5 5 0 11-5 5H5a7 7 0 107-7z"/></svg>',
    nav3d: '<svg viewBox="0 0 24 24"><path d="M12 2a6 6 0 00-6 6c0 4 6 12 6 12s6-8 6-12a6 6 0 00-6-6zm0 8a2 2 0 110-4 2 2 0 010 4z"/></svg>',
    sound: '<svg viewBox="0 0 24 24"><path d="M3 10v4h4l5 5V5L7 10z"/></svg>',
    soundoff: '<svg viewBox="0 0 24 24"><path d="M3 10v4h4l5 5V5L7 10zm13.6 2l2.1-2.1-1.4-1.4L15.2 10.6 13.1 8.5l-1.4 1.4 2.1 2.1-2.1 2.1 1.4 1.4 2.1-2.1 2.1 2.1 1.4-1.4z"/></svg>',
    full: '<svg viewBox="0 0 24 24"><path d="M4 4h6V2H2v8h2zm10-2v2h6v6h2V2zM4 14H2v8h8v-2H4zm16 6h-6v2h8v-8h-2z"/></svg>',
    vr: '<svg viewBox="0 0 24 24"><path d="M3 7h18a1 1 0 011 1v8a1 1 0 01-1 1h-5l-2-3h-4l-2 3H3a1 1 0 01-1-1V8a1 1 0 011-1z"/></svg>',
    planet: '<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 4a6 6 0 016 6H6a6 6 0 016-6z"/></svg>',
    wide: '<svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z"/></svg>',
    guide: '<svg viewBox="0 0 24 24"><path d="M4 4h12a3 3 0 013 3v13l-4-2-4 2-4-2-3 1.5V5a1 1 0 011-1z"/></svg>',
    reload: '<svg viewBox="0 0 24 24"><path d="M17.65 6.35A8 8 0 104 12h2a6 6 0 1110.24-4.24L13 8h7V1l-2.35 2.35z"/></svg>',
  };

  let cfg, kr, audioEl = null, lang = 'vn';

  // Tìm hotspot theo uuid trong mọi scene
  function findHotspot(uuid) {
    for (const sn in cfg.allScenes) {
      const hs = cfg.allScenes[sn].hotspots || {};
      if (hs[uuid]) return hs[uuid];
    }
    return null;
  }

  function el(tag, attrs, html) {
    const e = document.createElement(tag);
    if (attrs) for (const k in attrs) e.setAttribute(k, attrs[k]);
    if (html != null) e.innerHTML = html;
    return e;
  }

  function popup(title, bodyNode) {
    let p = document.querySelector('.hr-popup');
    if (!p) { p = el('div', { class: 'hr-popup' }); document.body.appendChild(p); }
    p.innerHTML = '';
    const panel = el('div', { class: 'hr-panel' });
    panel.appendChild(el('button', { class: 'hr-close' }, '✕'));
    panel.appendChild(el('h3', null, title));
    const body = el('div', { class: 'hr-body' });
    body.appendChild(bodyNode);
    panel.appendChild(body);
    p.appendChild(panel);
    p.classList.add('open');
    panel.querySelector('.hr-close').onclick = () => p.classList.remove('open');
    p.onclick = (e) => { if (e.target === p) p.classList.remove('open'); };
  }

  function curScene() { return kr ? kr.get('xml.scene') : null; }

  function closePopup() {
    const p = document.querySelector('.hr-popup');
    if (p) p.classList.remove('open');
  }

  // Thông báo nhẹ (toast) thay cho alert
  let toastTimer = null;
  function toast(msg) {
    let t = document.querySelector('.hr-toast');
    if (!t) { t = el('div', { class: 'hr-toast' }); document.body.appendChild(t); }
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 3000);
  }

  // URL thumbnail của 1 scene (ghép theo quy ước upload/panos/<id>/)
  function thumbUrl(sc) {
    const t = (sc && sc.thumb) || '';
    if (!t) return '';
    if (t.indexOf('http') === 0 || t.indexOf('/upload') === 0) return t;
    return '/upload/panos/' + cfg.tour.id_path + '/' + t;
  }

  // Dải thumbnail kéo được ở đáy (code riêng, không dùng Slick của StarGlobal)
  function buildFilmstrip() {
    const strip = el('div', { class: 'hr-strip' });
    const track = el('div', { class: 'hr-strip-track' });
    const order = Object.keys(cfg.allScenes);
    order.forEach(name => {
      const sc = cfg.allScenes[name];
      const item = el('div', { class: 'hr-thumb', 'data-scene': name, title: sc.title || name });
      const u = thumbUrl(sc);
      // Nếu thumb.jpg lỗi → tự đổi sang preview.jpg
      const fallback = u.replace(/thumb\.jpg(\?.*)?$/, 'preview.jpg');
      item.innerHTML = u
        ? '<img loading="lazy" src="' + u + '" alt="" onerror="this.onerror=null;this.src=\'' + fallback + '\'">'
        : '';
      item.onclick = () => { if (!dragMoved) loadScene(name); };
      track.appendChild(item);
    });
    strip.appendChild(track);
    // Mũi tên cuộn (như mẫu)
    const next = el('button', { class: 'hr-strip-next', title: 'Cuộn' }, '›');
    next.onclick = () => { track.scrollLeft += track.clientWidth * 0.8; };
    const prev = el('button', { class: 'hr-strip-prev', title: 'Cuộn' }, '‹');
    prev.onclick = () => { track.scrollLeft -= track.clientWidth * 0.8; };
    strip.appendChild(prev);
    strip.appendChild(next);
    document.body.appendChild(strip);

    // Kéo để cuộn ngang
    let down = false, startX = 0, startScroll = 0, dragMoved = false;
    track.addEventListener('mousedown', e => { down = true; dragMoved = false; startX = e.pageX; startScroll = track.scrollLeft; });
    window.addEventListener('mouseup', () => { down = false; });
    window.addEventListener('mousemove', e => {
      if (!down) return;
      const dx = e.pageX - startX;
      if (Math.abs(dx) > 4) dragMoved = true;
      track.scrollLeft = startScroll - dx;
    });
    track.addEventListener('wheel', e => { track.scrollLeft += e.deltaY; e.preventDefault(); }, { passive: false });

    window.__hrStrip = { track, highlight: function (name) {
      track.querySelectorAll('.hr-thumb').forEach(t => {
        const on = t.getAttribute('data-scene') === name;
        t.classList.toggle('active', on);
        if (on) t.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
      });
    }};
  }

  function loadScene(name) {
    if (kr) kr.call('loadscene(' + name + ', null, MERGE, BLEND(0.5));');
    const p = document.querySelector('.hr-popup'); if (p) p.classList.remove('open');
  }

  // ---- Các panel ----
  function openMinimap() {
    if (!cfg.minimap || !cfg.minimap.image) { popup('Bản đồ', el('p', null, 'Chưa có bản đồ cho tour này.')); return; }
    const wrap = el('div', { class: 'hr-minimap-wrap' });
    const img = el('img', { src: cfg.minimap.image });
    wrap.appendChild(img);
    const cur = curScene();
    (cfg.minimap.spots || []).forEach(s => {
      const d = el('div', { class: 'hr-spot' + (s.scene === cur ? ' cur' : ''), title: s.scene });
      d.style.left = s.x + '%'; d.style.top = s.y + '%';
      d.onclick = () => loadScene(s.scene);
      wrap.appendChild(d);
    });
    popup('Bản đồ', wrap);
  }

  function openGallery() {
    const sc = cfg.allScenes[curScene()];
    const imgs = (sc && sc.gallery) || [];
    if (!imgs.length) { popup('Hình ảnh', el('p', null, 'Phòng này chưa có hình ảnh.')); return; }
    const grid = el('div', { class: 'hr-gallery' });
    imgs.forEach(g => {
      const im = el('img', { src: g.image, title: g.caption || '' });
      im.onclick = () => window.open(g.image, '_blank');
      grid.appendChild(im);
    });
    popup('Hình ảnh', grid);
  }

  function openInfo() {
    const d = el('div');
    d.appendChild(el('p', null, '<b>' + (cfg.tour.title || '') + '</b>'));
    d.appendChild(el('p', null, 'Số điểm tham quan: ' + Object.keys(cfg.allScenes).length));
    popup('Thông tin', d);
  }

  // Popup thông tin hiện vật khi click hotspot info (đọc hotspot.i18n)
  function showInfo(uuid) {
    const h = findHotspot(uuid);
    if (!h) return;
    const tr = (h.i18n && (h.i18n[lang] || h.i18n[cfg.tour.default_lang])) || {};
    const title = tr.title || h.tooltip || 'Thông tin';
    const d = el('div');
    d.innerHTML = (tr.content || '<i>Chưa có nội dung.</i>');
    d.style.maxWidth = '70vw';
    popup(title, d);
  }

  // ---- Trạng thái cài đặt ----
  const state = { rotate: false, navVisible: true, muted: false, tiny: false, wide: false };

  function applyNavVisible() {
    const sc = cfg.allScenes[curScene()];
    if (!sc || !kr) return;
    Object.keys(sc.hotspots || {}).forEach(uuid => {
      try { kr.set('hotspot[hs_' + uuid + '].visible', state.navVisible); } catch (e) {}
    });
  }

  function setRotate(on) { state.rotate = on; if (kr) kr.set('autorotate.enabled', on); }
  function setMuted(on) { state.muted = on; if (audioEl) audioEl.muted = on; }
  function toggleTiny() {
    state.tiny = !state.tiny; state.wide = false;
    if (!kr) return;
    if (state.tiny) kr.call('tween(view.fov,150,1.0,easeInOutSine); tween(view.vlookat,90,1.0); tween(view.fisheye,1.0,1.0);');
    else kr.call('tween(view.fisheye,0,1.0); tween(view.fov,120,1.0); tween(view.vlookat,0,1.0);');
  }
  function toggleWide() {
    state.wide = !state.wide; state.tiny = false;
    if (!kr) return;
    kr.call('tween(view.fisheye,0,0.5); tween(view.fov,' + (state.wide ? '140' : '110') + ',1.0,easeInOutSine);');
  }
  function enterVR() {
    if (!kr) return;
    // Thử vào VR; sau 1.2s nếu chưa bật được (máy không hỗ trợ) thì báo rõ cho người dùng.
    kr.call("webvr.enterVR(); delayedcall(1.2, if(webvr.isenabled == false, "
      + "js(window.HeritageUI._toast('Chế độ VR chỉ chạy trên điện thoại hoặc kính thực tế ảo — trình duyệt máy tính này không hỗ trợ.')) ) );");
  }
  function clearAndReload() {
    try { sessionStorage.clear(); } catch (e) {}
    location.reload();
  }

  // ---- Bảng Cài đặt (thiết kế theo mẫu) ----
  function openSettings() {
    const wrap = el('div');
    wrap.style.minWidth = 'min(92vw, 620px)';

    // Hàng nút trên (toggle)
    const grid = el('div', { class: 'hr-set-grid' });
    const cells = [
      ['rotate', 'Tự xoay 360', () => state.rotate, () => setRotate(!state.rotate)],
      ['nav3d', 'Điều hướng', () => state.navVisible, () => { state.navVisible = !state.navVisible; applyNavVisible(); }],
      ['sound', 'Âm thanh', () => !state.muted, () => setMuted(!state.muted)],
      ['full', 'Toàn màn hình', () => !!document.fullscreenElement, toggleFull],
    ];
    cells.forEach(([icon, label, isOn, fn]) => {
      const c = el('button', { class: 'hr-set-cell' }, (icon === 'sound' && state.muted ? BIG.soundoff : BIG[icon]) + '<span>' + label + '</span>');
      if (isOn()) c.classList.add('on');
      c.onclick = () => { fn(); c.classList.toggle('on', isOn()); if (icon === 'sound') c.innerHTML = (state.muted ? BIG.soundoff : BIG.sound) + '<span>' + label + '</span>'; };
      grid.appendChild(c);
    });
    wrap.appendChild(grid);

    // Chọn ngôn ngữ
    const langs = cfg.languages || [];
    if (langs.length) {
      const row = el('div', { class: 'hr-set-lang' });
      row.appendChild(el('span', null, 'Chọn ngôn ngữ'));
      const sel = el('div', { class: 'hr-set-langbtns' });
      langs.forEach(l => {
        const b = el('button', { 'data-lang': l.id }, (l.img ? '<img src="' + l.img + '">' : '') + (l.display_name || l.name));
        if (l.id === lang) b.classList.add('on');
        b.onclick = () => { lang = l.id; sel.querySelectorAll('button').forEach(x => x.classList.toggle('on', x.getAttribute('data-lang') === lang)); updateTitle(); };
        sel.appendChild(b);
      });
      row.appendChild(sel);
      wrap.appendChild(row);
    }

    // Các chế độ xem
    const modes = el('div', { class: 'hr-set-modes' });
    [
      [BIG.vr, 'Chế độ VR', enterVR],
      [BIG.planet, 'Tiny Planet', toggleTiny],
      [BIG.wide, 'Góc rộng', toggleWide],
    ].forEach(([ic, label, fn]) => {
      const m = el('button', { class: 'hr-set-mode' }, ic + '<b>' + label + '</b>');
      m.onclick = () => { closePopup(); fn(); };   // đóng popup rồi chạy chế độ để thấy hiệu ứng
      modes.appendChild(m);
    });
    wrap.appendChild(modes);

    // Hướng dẫn + Tải lại
    const guide = el('button', { class: 'hr-set-wide' }, BIG.guide + ' Hướng dẫn sử dụng');
    guide.onclick = () => {
      const g = el('div');
      g.style.maxWidth = '70vw';
      g.innerHTML = '<ul style="line-height:1.9;margin:0;padding-left:18px;">' +
        '<li>Kéo chuột (hoặc vuốt) để <b>xoay</b> toàn cảnh 360°.</li>' +
        '<li>Lăn chuột (hoặc chụm 2 ngón) để <b>phóng to/thu nhỏ</b>.</li>' +
        '<li>Bấm <b>nút trên sàn</b> để di chuyển sang phòng khác.</li>' +
        '<li>Bấm <b>ảnh ở dải dưới</b> để chọn nhanh phòng.</li>' +
        '<li>Dùng <b>thanh công cụ trái</b>: trang đầu, bản đồ, tự động tham quan, hình ảnh, thông tin, cài đặt.</li>' +
        '</ul>';
      popup('Hướng dẫn sử dụng', g);
    };
    wrap.appendChild(guide);
    const reload = el('button', { class: 'hr-set-wide warn' }, BIG.reload + ' Xóa cache & Tải lại');
    reload.onclick = clearAndReload;
    wrap.appendChild(reload);

    popup('Cài đặt', wrap);
  }

  let autotourOn = false;
  function toggleAutotour(btn) {
    autotourOn = !autotourOn;
    if (kr) kr.set('autorotate.enabled', autotourOn);
    btn.classList.toggle('active', autotourOn);
    btn.innerHTML = autotourOn ? ICON.pause : ICON.play;
  }

  function toggleFull() {
    if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
    else document.exitFullscreen?.();
  }

  function share() {
    const url = location.href;
    navigator.clipboard?.writeText(url).then(
      () => toast('Đã sao chép liên kết tham quan!'),
      () => prompt('Sao chép liên kết:', url)
    );
  }

  // ---- Audio theo scene ----
  function updateTitle() {
    if (!window.__hrTitle) return;
    const sc = cfg.allScenes[curScene()];
    window.__hrTitle.textContent = (sc && sc.title) || '';
  }

  function updateAudio() {
    const sc = cfg.allScenes[curScene()];
    const list = (sc && sc.audio) || [];
    const btn = window.__hrAudioBtn;
    if (!list.length) { if (btn) btn.style.display = 'none'; if (audioEl) audioEl.pause(); return; }
    if (btn) btn.style.display = 'flex';
    const a = list[0];
    if (audioEl) audioEl.pause();
    audioEl = new Audio(a.audio);
    audioEl.loop = !!(+a.loop);
    if (+a.autoplay) audioEl.play().catch(() => {});
  }

  function toggleAudioPlay(btn) {
    if (!audioEl) return;
    if (audioEl.paused) { audioEl.play().catch(() => {}); btn.classList.add('active'); }
    else { audioEl.pause(); btn.classList.remove('active'); }
  }

  // ---- Khởi tạo UI ----
  function init(config, krpano) {
    cfg = config; kr = krpano;
    lang = (cfg.tour && cfg.tour.default_lang) || 'vn';
    const ui = el('div', { class: 'hr-ui' });

    if (cfg.tour && cfg.tour.title) ui.appendChild(el('div', { class: 'hr-logo' }, cfg.tour.title));

    // Nút đổi ngôn ngữ (góc trên phải)
    const langs = cfg.languages || [];
    if (langs.length > 1) {
      const lbar = el('div', { class: 'hr-langbar' });
      langs.forEach(l => {
        const b = el('button', { title: l.display_name, 'data-lang': l.id });
        b.innerHTML = l.img ? '<img src="' + l.img + '" alt="">' : l.name;
        if (l.id === lang) b.classList.add('active');
        b.onclick = () => {
          lang = l.id;
          lbar.querySelectorAll('button').forEach(x => x.classList.toggle('active', x.getAttribute('data-lang') === lang));
        };
        lbar.appendChild(b);
      });
      ui.appendChild(lbar);
    }

    // Tiêu đề cảnh ở giữa trên
    const titleBar = el('div', { class: 'hr-title' });
    ui.appendChild(titleBar);
    window.__hrTitle = titleBar;

    const bar = el('div', { class: 'hr-toolbar' });
    const firstScene = Object.keys(cfg.allScenes)[0];
    const buttons = [
      ['home', 'Trang đầu', () => loadScene(firstScene)],
      ['map', 'Bản đồ', openMinimap],
      ['play', 'Tự động tham quan', function () { toggleAutotour(this); }],
      ['gallery', 'Hình ảnh', openGallery],
      ['info', 'Thông tin', openInfo],
      ['full', 'Toàn màn hình', toggleFull],
      ['gear', 'Cài đặt', openSettings],
    ];
    buttons.forEach(([icon, title, fn]) => {
      const b = el('button', { title }, ICON[icon]);
      b.onclick = function () { fn.call(b); };
      bar.appendChild(b);
    });
    ui.appendChild(bar);

    // Nhóm nút góc phải dưới (chia sẻ + âm thanh) — như mẫu
    const corner = el('div', { class: 'hr-corner' });
    const shareBtn = el('button', { title: 'Chia sẻ' }, ICON.share);
    shareBtn.onclick = share;
    const ab = el('button', { title: 'Âm thanh', class: 'hr-audio-btn' }, ICON.audio);
    ab.style.display = 'none';
    ab.onclick = function () { toggleAudioPlay(ab); };
    corner.appendChild(ab);
    corner.appendChild(shareBtn);
    ui.appendChild(corner);
    window.__hrAudioBtn = ab;

    document.body.appendChild(ui);

    // Dải thumbnail dưới
    buildFilmstrip();

    // Ẩn TOÀN BỘ UI của vtourskin (ta dùng giao diện tự viết) — quét mọi layer tên skin_*/webvr_*
    if (kr) {
      window.__hrHideSkin = function () {
        try {
          const n = kr.get('layer.count') || 0;
          for (let i = 0; i < n; i++) {
            const nm = kr.get('layer[' + i + '].name') || '';
            if (nm.indexOf('skin_') === 0 || nm.indexOf('webvr_') === 0) {
              kr.set('layer[' + nm + '].visible', false);
              kr.set('layer[' + nm + '].enabled', false);
            }
          }
        } catch (e) {}
      };
      window.__hrHideSkin();
      setTimeout(window.__hrHideSkin, 500);
      setTimeout(window.__hrHideSkin, 1500);
    }

    // Cập nhật audio + tiêu đề + highlight thumbnail khi đổi scene
    updateAudio();
    updateTitle();
    if (window.__hrStrip) window.__hrStrip.highlight(curScene());
    if (kr) kr.set('events.onnewpano', 'js(window.HeritageUI._onpano());');
  }

  window.HeritageUI = {
    init, showInfo,
    _toast: toast,
    _onpano: function () {
      if (window.__hrHideSkin) window.__hrHideSkin();
      updateAudio();
      updateTitle();
      applyNavVisible();                 // giữ trạng thái ẩn/hiện nút điều hướng
      if (state.muted && audioEl) audioEl.muted = true;
      if (window.__hrStrip) window.__hrStrip.highlight(curScene());
      if (window.trackView) window.trackView();
    }
  };
})();
