@extends('layouts.app')

@section('title', 'Hardware Dashboard')

@section('content')
    <div id="tag-manager-modal" style="display:none">
        <h3>Manage tags</h3>

        <div id="tag-editor"></div>

        <input id="new-tag" placeholder="new tag">
        <button onclick="addTag()">Add</button>

        <button onclick="saveTags()">Save</button>
        <button onclick="closeTagManager()">Close</button>
    </div>
@endsection


@push('scripts')
    <script type="module">
        async function saveTags() {
            const modal = document.getElementById('tag-manager-modal');
            const hostId = modal.dataset.hostId;

            const inputs = modal.querySelectorAll('#tag-editor input');
            const tags = [...inputs].map(i => i.value.trim()).filter(Boolean);

            await fetch(`/api/host/${hostId}/tags`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ tags })
            });

            // reload tagMap realtime
            await loadTags();

            // update row display
            const tr = rowMap.get(hostId);
            cell(tr, 'tag').innerHTML = renderTags(tagMap.get(hostId) || []);

            closeTagManager();
        }

        function renderTagEditor(tags) {
            const el = document.getElementById('tag-editor');
            el.innerHTML = tags.map((t, i) => `
                    <div>
                        <input value="${t}" data-index="${i}">
                        <button onclick="removeTag(${i})">❌</button>
                    </div>
                `).join('');
        }

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
    </style>
@endpush