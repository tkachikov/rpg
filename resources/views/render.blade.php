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
        <div>
            <button onclick="fight">Fight</button>
        </div>
        <img id="frame" width="1000" height="1000" onkeydown="keyDown(event)" tabindex="0">
        <script>
            /*
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
            Echo.channel('test-1')
                .listen('TestEvent', (e) => {
                    if (e.img) {
                        setImg(e.img);
                    }
                });
             */
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
                }
            }
            async function move(position, step) {
                axios.post('window/move?render', {
                    position: position,
                    step: step,
                }).then((response) => setImg(response.data));
            }
            function fight() {
                axios.post('window/fight?render')
                    .then((response) => setImg(response.data));
            }
        </script>
    </body>
</html>
