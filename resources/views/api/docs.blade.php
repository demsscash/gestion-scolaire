<!-- resources/views/api/docs.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation API - Gestion Scolaire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.1.0/swagger-ui.min.css" integrity="sha512-ujWfZFvoSl8jr7YiERLxbRBXDDlj9/HWB7ZXd1Tx5kLH3EBi4jd1LmRMq33w6YfxALVjQVCD9HgC4t6WEFI0Fg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #333;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 {
            margin: 0;
            font-size: 1.6em;
        }
        .back-link {
            color: white;
            text-decoration: none;
            font-size: 0.9em;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .swagger-ui {
            max-width: 1460px;
            margin: 0 auto;
            padding: 20px;
        }
        .swagger-ui .topbar {
            display: none;
        }
    </style>
</head>
<body>
    <header>
        <h1>Documentation API - Gestion Scolaire</h1>
        <a href="/" class="back-link">Retour à l'accueil</a>
    </header>
    
    <div id="swagger-ui"></div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.1.0/swagger-ui-bundle.min.js" integrity="sha512-v/u+FQckO0hLG7B67l+mZUigdQe+GiwBI0caMB+KV/KFgEwuuGFVJ9XqDgLYxkcadQQMWm/4dlGGpTjlxHVAqA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.1.0/swagger-ui-standalone-preset.min.js" integrity="sha512-5D3yJwAkSBeG3+TQfoxRV3l7BIq4HQSptVJUGJDRRYe2Xk+q/ytIm/81K+a7D19UPrYvXXb6QOSsMbcPjGGZ7g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{{ asset('api-docs.json') }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                tagsSorter: 'alpha',
                operationsSorter: 'alpha',
                docExpansion: 'none'
            });
            
            window.ui = ui;
        };
    </script>
</body>
</html>