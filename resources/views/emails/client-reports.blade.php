<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seus Relatórios de Astrologia</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('https://wallpapers.com/images/hd/zodiac-signs-pictures-h5r3nbi0p2rgf4q0.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            color: #444;
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;            
        }
        .email-container {
            padding-top: 50px;
            max-width: 600px;
            margin: 20px auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: #202260;
            color: #fff;
            text-align: center;
            padding: 20px;
        }
        .header img {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
        }
        .content h2 {
            color: #3e206d;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
        }
        .report-list {
            margin: 20px 0;
        }
        .report-item {
            display: flex;
            box-shadow: 2px 2px 10px 2px #a8a8a8;
            border-radius: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 15px;
            height: auto;
            padding: 20px 10px;
        }
        .report-item img {
            max-width: 100px;
            margin-right: 15px;
        }
        .footer {
            background: #f4f4f4;
            text-align: center;
            padding: 15px;
            font-size: 14px;
            color: #888;
        }
        .footer a {
            color: #3e206d;
            text-decoration: none;
        }

        a.btnReport{
            background: #029132;
            color: white !important;
            text-decoration: none;
            padding: 8px;
            border-radius: 12px;
        }
    </style>
</head>
@php  
@endphp
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ $logoUrl ?? '' }}" alt="Astrology Logo">
            <h1>Seus Relatórios</h1>
        </div>
        <div class="content">
            <h2>Olá, {{ $customerName }}!</h2>
            <p>Agradecemos por sua compra! Aqui estão os relatórios que você adquiriu:</p>
            <div class="report-list">
                @foreach ($reports as $report)
                    <div class="report-item">
                        <img src="{{ $report['image'] }}" alt="Ícone do Relatório">
                        <div>
                            <h3>{{ $report['title'] }}</h3>
                            <a class="btnReport" href="{{ asset($report['url']) }}" target="_blank">Acessar relatório</a>
                        </div>
                    </div>
                @endforeach
            </div>
            <p>Esperamos que você aproveite seus relatórios e tenha uma jornada iluminada!</p>
        </div>
        <div class="footer">
            <p>Equipe Zodiarium🌟</p>
        </div>
    </div>
</body>
</html>
