@extends('layouts.app')

@section('title', 'Tags')

@section('content')
    <div class="panel">
        <h3>Tag Manager</h3>

        <table class="panel-table">
            <thead>
                <tr>
                    <th>Tag</th>
                    <th>Preview</th>
                    <th>Color</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tag-table"></tbody>
        </table>

        <hr>

        <input id="new-name" placeholder="tag name">
        <input type="color" id="new-color">
        <button id="btn-add">Add</button>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const tbody = document.getElementById('tag-table');
        const btnAdd = document.getElementById('btn-add');

        async function loadTags() {
            const res = await fetch('/api/tags');
            const tags = await res.json();

            tbody.innerHTML = Object.entries(tags)
                .sort((a, b) => a[0].localeCompare(b[0]))
                .map(([name, cfg]) => `
                        <tr>
                            <td>${name}</td>
                            <td>
                                <span class="tag-chip"
                                        style="background:${cfg.color};color:${cfg.text}">
                                    ${name}
                                </span>
                            </td>
                            <td>
                                <input type="color"
                                        value="${cfg.color}"
                                        onchange="updateTag('${name}', this.value)">
                            </td>
                            <td>
                                <button onclick="deleteTag('${name}')">❌</button>
                            </td>
                        </tr>
                    `).join('');
        }

        btnAdd.addEventListener('click', async () => {
            const name = document.getElementById('new-name').value.trim();
            const color = document.getElementById('new-color').value;

            if (!name) return alert('Tag name required');

            await fetch('/api/tags', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    name,
                    color,
                    text: '#000'
                })
            });

            document.getElementById('new-name').value = '';
            loadTags();
        });

        window.updateTag = async function (name, color) {
            await fetch(`/api/tags/${name}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ color })
            });

            loadTags();
        };

        window.deleteTag = async function (name) {
            if (!confirm(`Delete tag "${name}"?`)) return;

            await fetch(`/api/tags/${name}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]').content
                }
            });

            loadTags();
        };

        loadTags();
    </script>
@endpush