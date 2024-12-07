<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Cancelado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #be565e;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .cancel-container {
            text-align: center;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .cancel-container .icon {
            font-size: 4rem;
            color: #dc3545;
        }
        .cancel-container .btn {
            margin-top: 20px;
            color: #fff;
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="icon">
            <i class="fa fa-times-circle"></i>
        </div>
        <h1>Pagamento Cancelado</h1>
        <p>Parece que você cancelou o pagamento. Não perca a oportunidade de garantir seus relatórios personalizados!</p>
        <a href="{{ "https://zodiarium.com" }}" class="btn btn-primary">Tentar Novamente</a>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
