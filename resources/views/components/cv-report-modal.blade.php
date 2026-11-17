<div id="cvReportModal" style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center;background:rgba(0,0,0,.5);padding:16px;" onclick="if(event.target===this){cvReportClose()}">
    <div style="width:100%;max-width:820px;max-height:90vh;display:flex;flex-direction:column;border-radius:16px;background:#fff;color:#1e293b;box-shadow:0 25px 50px rgba(0,0,0,.25);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;border-bottom:1px solid #e2e8f0;padding:18px 20px;">
            <div>
                <div style="font-size:16px;font-weight:700;">Laporan Verifikasi Kontrak (Format WhatsApp)</div>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">Hanya data On Process (SEND BACK), dipisah per KP.</div>
            </div>
            <button type="button" onclick="cvReportClose()" style="border:0;background:#f1f5f9;border-radius:8px;padding:6px 10px;cursor:pointer;font-size:14px;">✕</button>
        </div>
        <div id="cvReportTabs" style="display:flex;flex-wrap:wrap;gap:8px;border-bottom:1px solid #e2e8f0;padding:12px 20px;"></div>
        <div id="cvReportBody" style="flex:1;overflow-y:auto;padding:16px 20px;"></div>
    </div>
</div>

<script>
(function () {
    var url = @json(route('report.contract-verification'));
    var data = {};
    var step = {};
    var el = function (id) { return document.getElementById(id); };

    function mk(tag, css, text) {
        var n = document.createElement(tag);
        if (css) { n.style.cssText = css; }
        if (text !== undefined) { n.textContent = text; }
        return n;
    }

    function fallback(text) {
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
    }

    function copyRaw(text, cb) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(cb, function () { fallback(text); cb(); });
        } else { fallback(text); cb(); }
    }

    function flash(btn) {
        var old = btn.getAttribute('data-label') || btn.textContent;
        btn.setAttribute('data-label', old);
        btn.textContent = 'Tersalin ✓';
        setTimeout(function () { btn.textContent = old; }, 1500);
    }

    function btnStyle(active) {
        return 'border:1px solid ' + (active ? '#059669' : '#cbd5e1') + ';background:' + (active ? '#059669' : '#fff') +
            ';color:' + (active ? '#fff' : '#334155') + ';border-radius:10px;padding:7px 12px;font-size:13px;font-weight:600;cursor:pointer;';
    }

    function render(activeKey) {
        var tabs = el('cvReportTabs'), body = el('cvReportBody');
        tabs.innerHTML = ''; body.innerHTML = '';

        Object.keys(data).forEach(function (key) {
            var s = data[key];
            var b = mk('button', btnStyle(key === activeKey), s.label + ' (' + s.onProcess + ')');
            b.type = 'button';
            b.onclick = function () { render(key); };
            tabs.appendChild(b);
        });

        var sec = data[activeKey];
        if (!sec) { return; }
        var parts = sec.text || [];
        var areas = [];

        body.appendChild(mk('div', 'font-size:13px;color:#475569;margin-bottom:10px;',
            'KP ' + sec.label + ': On Process ' + sec.onProcess + ' · Selesai ' + sec.selesai + ' · ' + parts.length + ' bagian pesan'));

        var box = mk('div', 'display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:14px;padding:12px;border:1px solid #a7f3d0;background:#ecfdf5;border-radius:12px;');
        var next = mk('button', btnStyle(true), '');
        next.type = 'button';
        var info = mk('div', 'font-size:12px;color:#047857;flex:1;min-width:180px;', '');

        function refresh() {
            var i = step[activeKey] || 0;
            next.textContent = i >= parts.length ? 'Mulai ulang dari bagian 1' : 'Salin bagian ' + (i + 1) + ' dari ' + parts.length;
            info.textContent = i >= parts.length ? 'Semua bagian sudah disalin.' : 'Tempel ke WhatsApp, lalu klik lagi untuk bagian berikutnya.';
            areas.forEach(function (a, k) {
                a.style.borderColor = k < i ? '#059669' : '#cbd5e1';
                a.style.background = k < i ? '#ecfdf5' : '#f8fafc';
            });
        }

        next.onclick = function () {
            var i = step[activeKey] || 0;
            if (i >= parts.length) { step[activeKey] = 0; refresh(); return; }
            copyRaw(parts[i], function () { step[activeKey] = i + 1; refresh(); });
        };

        var dl = mk('button', btnStyle(false), 'Unduh .txt');
        dl.type = 'button';
        dl.onclick = function () {
            var blob = new Blob([parts.join('\n\n')], { type: 'text/plain;charset=utf-8' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'laporan-verifikasi-kontrak-' + sec.label.toLowerCase() + '.txt';
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
        };

        var all = mk('button', btnStyle(false), 'Salin semua bagian');
        all.type = 'button';
        all.onclick = function () { copyRaw(parts.join('\n\n'), function () { flash(all); }); };

        var wa = mk('a', btnStyle(false) + 'text-decoration:none;display:inline-block;', 'Buka WhatsApp Web');
        wa.href = 'https://web.whatsapp.com/'; wa.target = '_blank'; wa.rel = 'noopener';

        box.appendChild(next); box.appendChild(info); box.appendChild(wa); box.appendChild(dl); box.appendChild(all);
        body.appendChild(box);

        parts.forEach(function (txt, i) {
            var wrap = mk('div', 'margin-bottom:14px;');
            var head = mk('div', 'display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;');
            head.appendChild(mk('div', 'font-size:12px;font-weight:700;color:#334155;', 'Bagian ' + (i + 1) + '/' + parts.length));
            var c = mk('button', btnStyle(false), 'Salin');
            c.type = 'button';
            c.onclick = function () { copyRaw(txt, function () { flash(c); }); };
            head.appendChild(c);
            var ta = mk('textarea', 'width:100%;height:200px;border:1px solid #cbd5e1;border-radius:10px;padding:10px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;color:#0f172a;background:#f8fafc;resize:vertical;');
            ta.readOnly = true;
            ta.value = txt;
            areas.push(ta);
            wrap.appendChild(head); wrap.appendChild(ta);
            body.appendChild(wrap);
        });

        refresh();
    }

    window.cvReportOpen = function () {
        step = {};
        el('cvReportModal').style.display = 'flex';
        el('cvReportTabs').innerHTML = '';
        el('cvReportBody').innerHTML = '';
        el('cvReportBody').appendChild(mk('div', 'color:#64748b;font-size:14px;', 'Menyiapkan laporan Verifikasi Kontrak...'));

        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
            .then(function (json) {
                if (!json.success) { throw new Error('Respons tidak valid'); }
                data = json.sections || {};
                var keys = Object.keys(data);
                if (!keys.length) {
                    el('cvReportBody').innerHTML = '';
                    el('cvReportBody').appendChild(mk('div', 'color:#64748b;font-size:14px;', 'Tidak ada data On Process.'));
                    return;
                }
                render(keys[0]);
            })
            .catch(function (e) {
                el('cvReportBody').innerHTML = '';
                el('cvReportBody').appendChild(mk('div', 'color:#dc2626;font-size:14px;', 'Gagal memuat laporan: ' + e.message));
            });
    };

    window.cvReportClose = function () { el('cvReportModal').style.display = 'none'; };
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { cvReportClose(); } });
})();
</script>