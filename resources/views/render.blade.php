<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.3.6/axios.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.15.1/echo.iife.min.js"></script>
    </head>
    <body>
        <div style="display: flex;">
            <img id="frame" width="1000" height="1000" onkeydown="keyDown(event)" tabindex="0" onclick="setEvent(event)">
            <div style="display: inline-block; width: 398px; height: 998px; border: 1px solid black; overflow-y: auto;">
                <div id="fps"></div>
                <div id="logs"></div>
            </div>
        </div>
        <script>
            var timeRoute = [];
            var exec = [];
            var userId = {{ $userId }};
            var withRender = '';
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: 'test',
                cluster: 'mt1',
                wsHost: 'localhost',
                wsPort: 6001,
                wssPort: 6001,
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
            });
            Echo.channel(`test-${userId}`)
                .listen('TestEvent', (e) => {
                    const newDiv = document.createElement('div');
                    const content = document.createTextNode(`[${e.time}]: ${e.message}`);
                    newDiv.appendChild(content);
                    document.querySelector('#logs').prepend(newDiv);
                    if (e.img) {
                        setImg(e.img);
                    }
                    timeRoute.push(Math.round(+new Date() - e.microtime));
                    exec.push(e.exec);
                });
            function setImg(img) {
                document.querySelector('#frame').setAttribute('src', img);
            }
            function keyDown(event) {
                console.log(event.code);
                switch (event.code) {
                    case 'ArrowUp':
                        move('y', -1);
                        break;
                    case 'ArrowDown':
                        move('y', 1);
                        break;
                    case 'ArrowLeft':
                        move('x', -1);
                        break;
                    case 'ArrowRight':
                        move('x', 1);
                        break;
                    default:
                        keyEvent(event.code);
                }
            }
            async function move(position, step) {
                axios.post('window/move?'+withRender, {
                    position: position,
                    step: step,
                }).then(function (response) {
                        if (withRender) {
                            setImg(response.data);
                        }
                    });
            }
            function battle() {
                axios.post('window/battle?'+withRender)
                    .then(function (response) {
                        if (withRender) {
                            setImg(response.data);
                        }
                    });
            }
            function setEvent(event) {
                axios.post('render/click?'+withRender, {
                    x: event.clientX - 10,
                    y: event.clientY - 30,
                }).then(function (response) {
                        if (withRender) {
                            setImg(response.data);
                        }
                    });
            }
            function leave() {
                axios.post('window/leave-battle?'+withRender)
                    .then(function (response) {
                        if (withRender) {
                            setImg(response.data);
                        }
                    });
            }
            function keyEvent(code) {
                axios.post('render/event?'+withRender, {code: code})
                    .then(function (response) {
                        if (withRender) {
                            setImg(response.data);
                        }
                    });
            }
            function getAvg(items) {
                if (!items.length) {
                    return 0;
                }
                let avg = 0;
                for (let i = 0; i < items.length; i++) {
                    avg += items[i];
                }
                return Math.round(avg / items.length);
            }
            setInterval(function () {
                document.querySelector('#fps').textContent = 'timeRoute: ' + getAvg(timeRoute) + 'ms, exec: ' + getAvg(exec) + ' ms';
            }, 1000)
        </script>
    </body>
</html>
