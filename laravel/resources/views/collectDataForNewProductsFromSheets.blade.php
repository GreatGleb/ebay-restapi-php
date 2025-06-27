@extends('layouts.app')

@section('title','Collect data for new products from Sheets')
@section('content')
    <h1>Collect data for new products from Sheets</h1>

    <h3>Logs</h3>
    <div class="logs-container">

    </div>

    <script>
        function generateStringWithDate() {
            const date = new Date();
            const dateStr = date.toISOString().slice(0, 10);

            const randomLength = 16 - dateStr.length;

            let randomStr = '';
            while (randomStr.length < randomLength) {
                randomStr += Math.random().toString(36).slice(2);
            }

            randomStr = randomStr.slice(0, randomLength);

            return dateStr + randomStr;
        }

        async function sendRequestForUpdating(logTrackId) {
            const url = "http://localhost/jobs/update/products/collectData";

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'log-trace-id': logTrackId
                    },
                });

                const result = await response.json();
                console.log(result)
            } catch (error) {
                console.log(error.message)
            }
        }

        function addLogToHTML(logs) {
            logs = logs['logs']
            const container = document.querySelector('.logs-container');

            for(let log of logs) {
                let currentLevels = [container];
                const li = document.createElement('li');
                li.textContent = `[${log.date}] [${log.source}] ${log.message}`;

                while (currentLevels.length <= log.indent) {
                    const newUl = document.createElement('ul');
                    currentLevels[currentLevels.length - 1].appendChild(newUl);
                    currentLevels.push(newUl);
                }

                currentLevels[log.indent].appendChild(li);
            }
        }

        async function sendRequestForLogs(logTrackId) {
            const url = "http://localhost/logs/get?trace_id="+logTrackId;

            try {
                const response = await fetch(url, {
                    method: 'GET',
                });

                const result = await response.json();
                console.log(result)
                addLogToHTML(result)
            } catch (error) {
                console.log(error.message)
            }
        }

        function sendEachSecondRequestForLogs(logTrackId) {
            setInterval(() => {
                sendRequestForLogs(logTrackId)
            }, 1000)
        }

        async function run() {
            let logTrackId = generateStringWithDate()

            sendRequestForUpdating(logTrackId)
            sendEachSecondRequestForLogs(logTrackId)
        }

        run()
    </script>
@endsection
