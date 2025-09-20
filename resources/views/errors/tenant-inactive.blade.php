<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Inactive - {{ $tenant->name }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            color: white;
            max-width: 500px;
            padding: 2rem;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 300;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .status {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .contact {
            font-size: 0.9rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚧</div>
        <h1>Account Temporarily Unavailable</h1>
        <p>The {{ $tenant->name }} account is currently {{ $tenant->status }}.</p>
        
        <div class="status">
            <strong>Status:</strong> {{ ucfirst($tenant->status) }}
        </div>
        
        @if($tenant->status === 'suspended')
            <p>This account has been temporarily suspended. Please contact support for assistance.</p>
        @elseif($tenant->status === 'inactive')
            <p>This account is currently inactive. Please contact your administrator.</p>
        @else
            <p>We're working to restore access as quickly as possible.</p>
        @endif
        
        <div class="contact">
            If you believe this is an error, please contact support.
        </div>
    </div>
</body>
</html>
