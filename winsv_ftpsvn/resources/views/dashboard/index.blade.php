@extends('layouts.app')

@section('title', 'Hardware Dashboard')

@section('content')
    {{-- DATA TABLE --}}
    <div class="panel">
        <table class="panel-table" id="host-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>host</th>
                    <th>Tag</th>
                    <th>CPU Load</th>
                    <th>CPU Temp</th>
                    <th>RAM Load</th>
                    <th>GPU Temp</th>
                    <th>GPU Load</th>
                    <th>Fan</th>
                    <th>Net (ms)</th>
                    <th>Signal</th>
                    <th>Updated</th>
                    <th>Uptime</th>

                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div id="row-context-menu" class="row-context-menu">
        <button onclick="ctxEditHost()">✏️ Edit host name</button>
        <button onclick="ctxEditTag()">🏷️ Edit tags</button>
        <button onclick="ctxHeartbeat()">❤️ Heartbeat</button>
        <button onclick="ctxPowerOn()">🔌 Power on</button>
        <button onclick="ctxPowerReset()">🔄 Reboot</button>
        <button onclick="ctxPowerOff()">⛔ Power off</button>
    </div>


    <div id="tag-picker-overlay" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Assign tags</h3>
                <div class="modal-sub" id="tag-host-name"></div>
            </div>

            <div class="modal-body">
                <div id="tag-picker"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeTagPicker()">Cancel</button>
                <button class="btn btn-primary" onclick="saveTagAssign()">Save</button>
            </div>
        </div>
    </div>


@endsection


@push('scripts')
    <script type="module">

        async function loadTagMaster() {
            const res = await fetch('/api/tags');
            const data = await res.json();

            tagMasterMap.clear();
            tagMaster = [];

            Object.entries(data).forEach(([tag, cfg]) => {
                tagMasterMap.set(tag, cfg);
                tagMaster.push(tag);
            });
        }
        async function loadTags() {
            const res = await fetch('/api/host/tags');
            const data = await res.json();

            tagMap.clear();
            Object.entries(data).forEach(([hostId, tags]) => {
                tagMap.set(hostId, tags);
            });
        }
        async function loadHostNames() {
            const res = await fetch('/api/host/names');
            const data = await res.json();

            Object.entries(data).forEach(([id, name]) => {
                hostNameMap.set(id, name);
            });
        }
        async function loadTagManager(hostId) {
            const res = await fetch('/api/host/tags'); // ✅ CHỈ GET ALL
            const allTags = await res.json();

            editingTags = [...(allTags[hostId] || [])];
            renderTagEditor();
        }
        async function saveTagAssign() {
            await fetch(`/api/host/${editingHostId}/tags`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ tags: editingTags })
            });

            tagMap.set(editingHostId, [...editingTags]);
            refreshAllTags();
            closeTagPicker();
        }
        async function reloadTagMaster() {
            const res = await fetch('/api/tags', {
                cache: 'no-store' // ⛔ tránh cache trình duyệt
            });
            const data = await res.json();

            tagMasterMap.clear();
            tagMaster = [];

            Object.entries(data).forEach(([tag, cfg]) => {
                tagMasterMap.set(tag, cfg);
                tagMaster.push(tag);
            });
        }

        function cell(tr, name) {
            return tr.querySelector(`[data-col="${name}"]`);
        }
        function extracthostId(from) {
            const m = from.match(/(\d+)$/);
            return m ? m[1] : from;
        }
        function createRow(hostId) {
            const tr = document.createElement('tr');
            tr.dataset.hostId = hostId;

            tr.addEventListener('contextmenu', e => {
                e.preventDefault();
                ctxHostId = hostId;

                ctxMenu.style.display = 'block';
                ctxMenu.style.left = e.pageX + 'px';
                ctxMenu.style.top = e.pageY + 'px';
            });

            const displayName = hostNameMap.get(hostId) || hostId;

            const index = rowMap.size + 1;
            tr.innerHTML = `
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="index">${index}</td>

                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="host">
                                                                                                                                                                                                                                                                                                                                                                                                                                        <span class="host-name">${displayName}</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="tag" class="tag-cell" title="Right click → Edit tags"></td>

                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="cpu">-</td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="cpu_temp">-</td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="ram">-</td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="gpu_temp">-</td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="gpu_load">-</td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="fan">-</td>

                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="net">-</td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="signal" class="signal"></td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="updated">-</td>
                                                                                                                                                                                                                                                                                                                                                                                                                                    <td data-col="uptime" class="uptime"></td>
                                                                                                                                                                                                                                                                                                                                                                                                                                `;
            tbody.prepend(tr);
            rowMap.set(hostId, tr);
            return tr;
        }
        function renderHostName(hostId) {
            const tr = rowMap.get(hostId);
            if (!tr) return;

            const td = cell(tr, 'host');
            const name = hostNameMap.get(hostId) || hostId;

            td.innerHTML = `<span class="host-name">${name}</span>`;
        }
        function resetTdColor(td) {
            td.classList.remove('td-green', 'td-yellow', 'td-red');
        }
        function applyTdColor(td, val, levels) {
            resetTdColor(td);
            if (val === null || val === undefined) return;

            const cls = tdClass(val, levels);
            if (cls) td.classList.add(cls);
        }
        function tdClass(val, levels) {
            if (val >= levels.red) return 'td-red';
            if (val >= levels.yellow) return 'td-yellow';
            if (val >= levels.green) return 'td-green';
            return '';
        }
        function renderTags(tags = []) {
            if (!tags.length) return '<span class="tag-empty">+</span>';

            const sorted = [...tags].sort((a, b) =>
                a.localeCompare(b, undefined, { sensitivity: 'base' })
            );

            return sorted.map(tag => {
                const cfg = tagMasterMap.get(tag) || {
                    color: '#e9ecef',
                    text: '#495057'
                };

                return `
                                                                                    <span class="tag-chip"
                                                                                          style="background:${cfg.color};color:${cfg.text}">
                                                                                        ${tag}
                                                                                    </span>
                                                                                `;
            }).join('');
        }


        function updateRow(hostId, m) {
            let tr = rowMap.get(hostId);
            if (!tr) tr = createRow(hostId);

            /* ===== CPU LOAD ===== */
            const cpuLoad = m.cpu_load ?? null;
            const cpuTd = cell(tr, 'cpu');
            cpuTd.innerText = cpuLoad !== null ? cpuLoad.toFixed(1) + '%' : '-';
            applyTdColor(cpuTd, cpuLoad, { green: 50, yellow: 75, red: 90 });

            /* ===== CPU TEMP ===== */
            const cpuTemp = m.cpu_temp ?? null;
            const cpuTempTd = cell(tr, 'cpu_temp');
            cpuTempTd.innerText = cpuTemp !== null ? cpuTemp + '°C' : '-';
            applyTdColor(cpuTempTd, cpuTemp, { green: 55, yellow: 65, red: 75 });

            /* ===== RAM ===== */
            const ramLoad = m.ram_load ?? null;
            const ramTd = cell(tr, 'ram');
            ramTd.innerText = ramLoad !== null ? ramLoad.toFixed(1) + '%' : '-';
            applyTdColor(ramTd, ramLoad, { green: 50, yellow: 70, red: 80 });

            /* ===== GPU ===== */
            cell(tr, 'gpu_temp').innerText =
                m.gpu_temp !== undefined ? m.gpu_temp + '°C' : '-';

            cell(tr, 'gpu_load').innerText =
                m.gpu_load !== undefined ? m.gpu_load.toFixed(1) + '%' : '-';

            /* ===== FAN ===== */
            cell(tr, 'fan').innerText =
                m.fan_load !== undefined ? m.fan_load.toFixed(0) + '%' : '-';

            /* ===== UPDATED ===== */
            cell(tr, 'updated').innerText = new Date().toLocaleTimeString();

            /* ===== TAG ===== */
            cell(tr, 'tag').innerHTML = renderTags(tagMap.get(hostId) || []);

            /* ===== HEARTBEAT ===== */
            lastSeenMap.set(hostId, m.ts ?? Date.now());
            if (!upSinceMap.has(hostId)) {
                upSinceMap.set(hostId, Date.now());
            }
        }
        function uptimeColor(seconds) {
            if (seconds > 120) return 'td-red';
            if (seconds > 60) return 'td-yellow';
            if (seconds > 30) return 'td-green';
            return ''; // <= 30s: KHÔNG MÀU
        }
        function formatUptime(sec) {
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = Math.floor(sec % 60);
            return `${h}h ${m}m ${s}s`;
        }
        function tickUptime() {
            const now = Date.now();

            for (const [hostId, tr] of rowMap.entries()) {
                const lastTs = lastSeenMap.get(hostId);
                if (!lastTs) continue;

                const delaySec = Math.floor((now - lastTs) / 1000);

                /* ===== reset uptime nếu downtime ===== */
                if (delaySec > 120) {
                    upSinceMap.set(hostId, now);
                }

                const upSince = upSinceMap.get(hostId) ?? now;
                const uptimeSec = Math.floor((now - upSince) / 1000);

                /* ===== SIGNAL ===== */
                cell(tr, 'signal').innerHTML =
                    renderSignal(signalLevel(delaySec));

                /* ===== UPTIME ===== */
                const uptimeTd = cell(tr, 'uptime');
                resetTdColor(uptimeTd);

                const color = uptimeColor(delaySec);
                if (color) uptimeTd.classList.add(color);

                uptimeTd.innerText = formatUptime(uptimeSec);
            }
        }
        function renderSignal(level) {
            if (level === 'down')
                return '<span class="signal-x" style="color: #e03131">✖</span>';

            const bars = {
                full: 3,   // <30s
                mid: 2,    // >30s
                low: 1     // >60s
            }[level];

            let html = '<span class="signal-bars">';
            for (let i = 1; i <= 3; i++) {
                html += `<i class="bar ${i <= bars ? 'on' : ''}"></i>`;
            }
            html += '</span>';
            return html;
        }
        function signalLevel(delaySec) {
            if (delaySec > 120) return 'down';
            if (delaySec > 60) return 'low';
            if (delaySec > 30) return 'mid';
            return 'full';
        }
        function refreshAllTags() {
            for (const [hostId, tr] of rowMap.entries()) {
                cell(tr, 'tag').innerHTML =
                    renderTags(tagMap.get(hostId) || []);
            }
        }
        function renderTagPicker() {
            const el = document.getElementById('tag-picker');

            const sorted = [...tagMaster].sort((a, b) =>
                a.localeCompare(b, undefined, { sensitivity: 'base' })
            );

            el.innerHTML = sorted.map(tag => {
                const cfg = tagMasterMap.get(tag);
                const active = editingTags.includes(tag) ? 'active' : '';

                return `
                                            <div class="tag-item ${active}"
                                                 data-tag="${tag}"
                                                 style="background:${cfg.color};color:${cfg.text}">
                                                ${tag}
                                            </div>
                                        `;
            }).join('');

            el.querySelectorAll('.tag-item').forEach(item => {
                item.onclick = () => toggleTagItem(item);
            });
        }

        function toggleTagItem(el) {
            const tag = el.dataset.tag;

            if (editingTags.includes(tag)) {
                editingTags = editingTags.filter(t => t !== tag);
                el.classList.remove('active');
            } else {
                editingTags.push(tag);
                el.classList.add('active');
            }
        }


        function toggleTag(tag) {
            if (editingTags.includes(tag)) {
                editingTags = editingTags.filter(t => t !== tag);
            } else {
                editingTags.push(tag);
            }
        }

        const tbody = document.querySelector('#host-table tbody');
        const rowMap = new Map(); // hostId => tr
        const hostNameMap = new Map(); // hostId -> displayName 
        let ctxHostId = null;
        const ctxMenu = document.getElementById('row-context-menu');
        const lastSeenMap = new Map();   // hostId -> last heartbeat timestamp (ms)
        const upSinceMap = new Map();  // hostId -> uptime start timestamp (ms)
        const tagMap = new Map();
        let editingHostId = null;
        let editingTags = [];
        const tagMasterMap = new Map();
        let tagMaster = [];

        await loadHostNames();
        await loadTags()
        await loadTagMaster();
        refreshAllTags();


        setInterval(tickUptime, 2000);

        document.addEventListener('click', () => {
            ctxMenu.style.display = 'none';
        });
        window.ctxHeartbeat = function () {
            ctxMenu.style.display = 'none';
            sendCmd(ctxHostId, 'health', 'heartbeat');
        };

        window.ctxPowerOn = function () {
            ctxMenu.style.display = 'none';
            sendCmd(ctxHostId, 'power', 'power-on');
        };

        window.ctxPowerOff = function () {
            ctxMenu.style.display = 'none';
            sendCmd(ctxHostId, 'power', 'power-off');
        };
        window.ctxPowerReset = function () {
            ctxMenu.style.display = 'none';
            sendCmd(ctxHostId, 'power', 'power-reset');
        };
        window.closeTagPicker = function () {
            document.getElementById('tag-picker-modal').style.display = 'none';
            editingHostId = null;
            editingTags = [];
        }
        window.ctxEditHost = function () {
            ctxMenu.style.display = 'none';
            editHostName(ctxHostId);
        };
        window.ctxEditTag = async function () {
            ctxMenu.style.display = 'none';

            // 🔄 luôn lấy tag mới nhất
            await reloadTagMaster();

            openTagManager(ctxHostId);
        };

        window.addTag = function () {
            const input = document.getElementById('new-tag');
            const val = input.value.trim();
            if (!val) return;

            editingTags.push(val);
            input.value = '';
            renderTagEditor();
        };
        window.removeTag = function (index) {
            editingTags.splice(index, 1);
            renderTagEditor();
        };
        window.toggleTag = function (tag) {
            if (editingTags.includes(tag)) {
                editingTags = editingTags.filter(t => t !== tag);
            } else {
                editingTags.push(tag);
            }
        };
        window.openTagManager = function (hostId) {
            editingHostId = hostId;
            editingTags = [...(tagMap.get(hostId) || [])];

            renderTagPicker();
            document.getElementById('tag-picker-overlay').style.display = 'flex';
        };
        window.saveTags = async function () {
            if (!editingHostId) return;

            await fetch(`/api/host/${editingHostId}/tags`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ tags: editingTags })
            });

            tagMap.set(editingHostId, [...editingTags]);
            refreshAllTags();
            closeTagManager();
        };
        window.saveTagAssign = async function () {
            if (!editingHostId) return;

            await fetch(`/api/host/${editingHostId}/tags`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ tags: editingTags })
            });

            tagMap.set(editingHostId, [...editingTags]);
            refreshAllTags();

            closeTagPicker(); // ✅ giờ không crash, modal đóng
        };

        window.closeTagPicker = function () {
            const overlay = document.getElementById('tag-picker-overlay');
            if (overlay) {
                overlay.style.display = 'none';
            }

            editingHostId = null;
            editingTags = [];
        };

        window.closeTagManager = function () {
            document.getElementById('tag-manager-modal').style.display = 'none';
            editingHostId = null;
            editingTags = [];
        };

        window.Echo
            .channel('reverb.websocket.redis.signal.chanel')
            .listen('.reverb.websocket.redis.signal.event', e => {
                const msg = e.message;
                const hostId = msg.from;

                let metrics;
                try { metrics = JSON.parse(msg.note); }
                catch { return; }

                updateRow(hostId, metrics);
            });



        window.sendCmd = function (hostId, module, command) {
            fetch(`/api/command/${module}/${hostId}/${command}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
        };
        window.toggleProfile = function (hostId) {
            const menu = document.getElementById('menu-' + hostId);
            menu.classList.toggle('show');
        };
        window.editHostName = function (hostId) {
            const tr = rowMap.get(hostId);
            const td = cell(tr, 'host');

            const current = hostNameMap.get(hostId) || hostId;

            td.innerHTML = `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <input class="host-name-input"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    value="${current}"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    onkeydown="hostNameKey(event,'${hostId}')"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    onblur="saveHostName('${hostId}', this.value)">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            `;

            td.querySelector('input').focus();
        };
        window.saveHostName = function (hostId, newName) {
            if (!newName.trim()) return;

            fetch(`/api/host/${hostId}/rename`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ name: newName })
            });

            hostNameMap.set(hostId, newName);
            renderHostName(hostId);
        };
        window.hostNameKey = function (e, hostId) {
            if (e.key === 'Enter') {
                saveHostName(hostId, e.target.value);
            }
            if (e.key === 'Escape') {
                renderHostName(hostId);
            }
        };
    </script>
@endpush

@push('styles')
    <style>
        /* ===== CONTEXT MENU (RIGHT CLICK) ===== */
        .row-context-menu {
            position: fixed;
            display: none;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
            z-index: 9999;
            min-width: 180px;
        }

        .row-context-menu button {
            width: 100%;
            padding: 8px 12px;
            border: none;
            background: none;
            text-align: left;
            font-size: 13px;
            cursor: pointer;
        }

        .row-context-menu button:hover {
            background: #f1f3f5;
        }

        /* ===== DASHBOARD LAYOUT ===== */
        .dashboard-wrapper {
            background: #f4f6f9;
            min-height: 100vh;
            padding: 24px;
        }

        .dashboard-container {
            max-width: 1440px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        /* ===== LIVE BADGE ===== */
        .live-badge {
            background: #2fb344;
            color: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            animation: pulse 2s infinite;
        }

        /* ===== SUMMARY ===== */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .05);
        }

        .summary-card .label {
            font-size: 12px;
            color: #6c757d;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: 700;
        }

        /* ===== PANEL ===== */
        .panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .05);
            padding: 16px 18px;
        }

        /* ===== TABLE ===== */
        .panel-table {
            width: 100%;
            border-collapse: collapse;
        }

        .panel-table th {
            text-align: left;
            font-size: 12px;
            color: #6c757d;
            padding-bottom: 6px;
            border-bottom: 1px solid #eee;
        }

        .panel-table td {
            padding: 6px 0;
            font-size: 13px;
            line-height: 1.4;
        }

        .panel-table tr:hover {
            background: #f8f9fa;
        }

        /* ===== HOST NAME ===== */
        .host-name {
            font-weight: 500;
        }

        .host-name-input {
            width: 150px;
            padding: 4px 6px;
            font-size: 13px;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }

        /* ===== ANIMATION ===== */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(47, 179, 68, .6);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(47, 179, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(47, 179, 68, 0);
            }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .dashboard-wrapper {
                padding: 14px;
            }
        }

        /* ===== CELL STATUS BACKGROUND ===== */
        .td-green {
            background: #e6fcf5;
        }

        .td-yellow {
            background: #fff9db;
        }

        .td-red {
            background: #ffe3e3;
        }

        /* cho chữ dễ đọc hơn */
        .td-green,
        .td-yellow,
        .td-red {
            font-weight: 600;
        }

        /* ===== SIGNAL ICON ===== */
        .signal-bars {
            display: inline-flex;
            align-items: flex-end;
            gap: 2px;
            height: 14px;
        }

        .signal-bars .bar {
            width: 4px;
            background: #dee2e6;
            border-radius: 1px;
        }

        .signal-bars .bar:nth-child(1) {
            height: 6px;
        }

        .signal-bars .bar:nth-child(2) {
            height: 10px;
        }

        .signal-bars .bar:nth-child(3) {
            height: 14px;
        }

        .signal-bars .bar.on {
            background: #2fb344;
        }

        .signal-x {
            color: #e03131;
            font-weight: 700;
        }

        .tag-cell {
            cursor: pointer;
            min-width: 120px;
        }

        .tag-chip {
            display: inline-block;
            background: #e7f5ff;
            color: #1971c2;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 11px;
            margin-right: 4px;
        }

        .tag-empty {
            color: #adb5bd;
            font-size: 14px;
        }

        .tag-input {
            width: 100%;
            font-size: 12px;
            padding: 4px 6px;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }

        .tag-cell {
            cursor: default;
        }

        /* ===== MODAL OVERLAY ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .35);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        /* ================= MODAL OVERLAY ================= */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .35);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        /* ================= MODAL CARD ================= */
        .modal-card {
            background: #fff;
            border-radius: 14px;
            width: 420px;
            max-width: 92%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, .25);
            overflow: hidden;
            animation: modalIn .15s ease-out;
        }

        @keyframes modalIn {
            from {
                transform: scale(.96);
                opacity: 0
            }

            to {
                transform: scale(1);
                opacity: 1
            }
        }

        /* ================= HEADER ================= */
        .modal-header {
            padding: 14px 18px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .modal-sub {
            font-size: 12px;
            color: #868e96;
        }

        /* ================= BODY ================= */
        .modal-body {
            padding: 16px 18px;
            max-height: 300px;
            overflow-y: auto;
        }

        /* ================= TAG GRID ================= */
        #tag-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* ================= TAG ITEM ================= */
        .tag-item {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            cursor: pointer;
            opacity: .6;
            user-select: none;
            transition: all .15s ease;
        }

        .tag-item:hover {
            opacity: .85;
        }

        .tag-item.active {
            opacity: 1;
            outline: 2px solid rgba(0, 0, 0, .45);
            font-weight: 600;
        }

        /* ================= FOOTER ================= */
        .modal-footer {
            padding: 12px 18px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* ================= BUTTON ================= */
        .btn {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #339af0;
            color: #fff;
        }

        .btn-secondary {
            background: #e9ecef;
        }
    </style>
@endpush