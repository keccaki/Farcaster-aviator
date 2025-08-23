<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Telegram Web App - Aviator Crash Game</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body, html { margin:0; padding:0; height:100%; overflow:hidden; }
        #app { width:100%; height:100%; }
    </style>
</head>
<body>
<script>
    Telegram.WebApp.ready();
    Telegram.WebApp.expand();

    // Send initData to backend for verification
    fetch("{{ route('telegram.initdata') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ initData: Telegram.WebApp.initData })
    })
    .then(res => res.json())
    .then(info => console.log("Telegram user:", info));

    Telegram.WebApp.MainButton.hide();
</script>

<button id="close-button" style="position:absolute; top:10px; right:10px; z-index:1000;">Exit</button>
<script>
    document.getElementById('close-button').addEventListener('click', () => {
        Telegram.WebApp.close();
    });
</script>
<iframe src="{{ url('/game') }}" style="border:none; width:100%; height:100vh;"></iframe>
</body>
</html> 