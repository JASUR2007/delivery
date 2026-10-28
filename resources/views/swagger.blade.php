<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Davlat Shartnomalari API - Swagger UI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css">
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.11.0/favicon-32x32.png" sizes="32x32" />
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .topbar { background-color: #1b1b1b; padding: 10px 0; }
        .topbar-wrapper { max-width: 1460px; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; }
        .topbar-wrapper a.brand { display: flex; align-items: center; text-decoration: none; color: #fff; font-weight: bold; font-size: 16px; }
        .topbar-wrapper a.brand span { color: #49cc90; margin-left: 6px; }
        .topbar-link { font-size: 13px; color: #49cc90; text-decoration: none; font-weight: 500; }
        .topbar-link:hover { text-decoration: underline; color: #62d8a2; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-wrapper">
            <a href="/" class="brand">
                <svg width="24" height="24" viewBox="0 0 30 30" fill="none" style="margin-right: 8px;">
                    <circle cx="15" cy="15" r="15" fill="#49cc90"/>
                    <path d="M9 15L13 19L21 11" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Davlat Shartnomalari <span>Swagger API</span>
            </a>
            <a href="/" class="topbar-link">&larr; Asosiy tizimga qaytish</a>
        </div>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js" charset="UTF-8"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
    <script>
    window.onload = function() {
        window.ui = SwaggerUIBundle({
            url: "/docs/api.json",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            layout: "BaseLayout",
            docExpansion: "list",
            filter: true,
            persistAuthorization: true
        });
    };
    </script>
</body>
</html>
