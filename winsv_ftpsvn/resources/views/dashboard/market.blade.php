@extends('layouts.app')

@section('title', 'Hardware Dashboard')

@section('content')
    <div class="market-wrapper">
        <table class="market-table" id="market-table">
            <thead>
                <tr>
                    <th>AGENT</th>
                    <th>CPU °C</th>
                    <th>CPU %</th>
                    <th>RAM %</th>
                    <th>GPU °C</th>
                    <th>GPU %</th>
                    <th>UPDATE</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
@endsection
@push('scripts')
    <script type="module">
        const tbody = document.querySelector('#market-table tbody');
        const rowMap = new Map(); // agent => tr

        function getColor(value, type) {
            if (type === 'temp') {
                if (value >= 80) return 'down';
                if (value >= 65) return 'mid';
                return 'up';
            }
            if (type === 'load') {
                if (value >= 85) return 'down';
                if (value >= 60) return 'mid';
                return 'up';
            }
            return 'up';
        }

        function upsertRow(agent, stats) {
            let tr = rowMap.get(agent);

            if (!tr) {
                tr = document.createElement('tr');
                tr.innerHTML = `
                                    <td class="agent">${agent}</td>
                                    <td></td><td></td><td></td><td></td><td></td>
                                    <td></td>
                                `;
                tbody.appendChild(tr);
                rowMap.set(agent, tr);
            }

            const cells = tr.children;

            cells[1].innerHTML = `<span class="${getColor(stats.cpuTemp, 'temp')}">${stats.cpuTemp}°C</span>`;
            cells[2].innerHTML = `<span class="${getColor(stats.cpuLoad, 'load')}">${stats.cpuLoad}%</span>`;
            cells[3].innerHTML = `<span class="${getColor(stats.ram, 'load')}">${stats.ram}%</span>`;
            cells[4].innerHTML = `<span class="${getColor(stats.gpuTemp, 'temp')}">${stats.gpuTemp}°C</span>`;
            cells[5].innerHTML = `<span class="${getColor(stats.gpuLoad, 'load')}">${stats.gpuLoad}%</span>`;
            cells[6].innerText = new Date().toLocaleTimeString();

            tr.classList.add('flash');
            setTimeout(() => tr.classList.remove('flash'), 300);
        }
        function parseSnapshot(agent, data) {
            let cpuTemp = '-', cpuLoad = '-', ram = '-', gpuTemp = '-', gpuLoad = '-';

            Object.values(data).forEach(d => {
                if (d.type === 2) { // CPU
                    cpuLoad = d.sensors.find(s => s.name === 'CPU Total')?.value?.toFixed(1);
                    cpuTemp = d.sensors.find(s => s.type === 'Temperature' && s.name.includes('Package'))?.value;
                }
                if (d.type === 3) { // RAM
                    ram = d.sensors.find(s => s.type === 'Load')?.value?.toFixed(1);
                }
                if (d.type === 4) { // GPU
                    gpuTemp = d.sensors.find(s => s.type === 'Temperature' && s.name === 'GPU Core')?.value;
                    gpuLoad = d.sensors.find(s => s.type === 'Load' && s.name === 'GPU Core')?.value;
                }
            });

            return { cpuTemp, cpuLoad, ram, gpuTemp, gpuLoad };
        }
        window.Echo
            .channel('reverb.websocket.redis.signal.chanel')
            .listen('.reverb.websocket.redis.signal.event', e => {

                const signal = JSON.parse(e.message);
                const redisKey = signal.redis_key;
                const agent = redisKey.split(':')[2]; // facade:100

                fetch('/api/dashboard/fetch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ redis_key: redisKey })
                })
                    .then(r => r.json())
                    .then(r => {
                        if (!r.ok) return;

                        const stats = parseSnapshot(agent, r.data);
                        upsertRow(agent, stats);
                    });
            });


    </script>
@endpush
@push('styles')
    <style type="text/css">
        .market-wrapper {
            background: #0b1220;
            padding: 16px;
            border-radius: 10px;
        }

        .market-table {
            width: 100%;
            border-collapse: collapse;
            font-family: monospace;
            font-size: 13px;
        }

        .market-table thead th {
            color: #8fa3bf;
            font-weight: 600;
            padding: 10px;
            border-bottom: 1px solid #1e2a44;
            text-align: right;
        }

        .market-table thead th:first-child,
        .market-table tbody td:first-child {
            text-align: left;
        }

        .market-table tbody tr {
            transition: background .15s;
        }

        .market-table tbody tr.flash {
            background: rgba(255, 255, 255, .05);
        }

        .market-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #121b30;
            text-align: right;
            color: #d6e1ff;
        }

        /* COLORS */
        .up {
            color: #2fb344;
        }

        .mid {
            color: #f59f00;
        }

        .down {
            color: #e03131;
        }

        .agent {
            font-weight: 700;
            color: #fff;
        }

@endpush