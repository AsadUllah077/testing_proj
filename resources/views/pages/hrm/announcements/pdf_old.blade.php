<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Announcement - {{ $refNo }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        .header {
            position: relative;
            height: 150px;
            width: 100%;
        }

        .logo {
            position: absolute;
            top: 40px;
            left: 50px;
            height: 60px;
        }

        .logo img {
            height: 60px;
        }

        .graphics {
            position: absolute;
            top: 0;
            right: 0;
        }

        .content {
            margin: 50px 70px;
            min-height: 600px;
        }

        .title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            color: #000;
        }

        .ref-no {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 50px;
            color: #000;
        }

        .body-text {
            font-size: 14px;
            text-align: justify;
            margin-bottom: 40px;
            color: #333;
        }

        .signature {
            margin-top: 50px;
            margin-bottom: 5px;
        }

        .signature img {
            width: 150px;
            height: auto;
        }

        .footer-text {
            font-weight: bold;
            font-size: 15px;
            text-transform: uppercase;
        }

        /* Simulating the geometric pattern from the image */
        .pattern-container {
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
        }

        .square {
            position: absolute;
            transform: rotate(45deg);
        }

        .sq1 {
            width: 40px;
            height: 40px;
            background: #E5E7EB;
            top: 10px;
            right: 100px;
            opacity: 0.6;
        }

        .sq2 {
            width: 50px;
            height: 50px;
            background: #9CA3AF;
            top: 30px;
            right: 50px;
            opacity: 0.8;
        }

        .sq3 {
            width: 60px;
            height: 60px;
            background: #0078C8;
            top: 60px;
            right: 10px;
        }

        .sq4 {
            width: 40px;
            height: 40px;
            background: #F3F4F6;
            top: 80px;
            right: 70px;
        }

        .sq5 {
            width: 30px;
            height: 30px;
            background: #D1D5DB;
            top: 120px;
            right: 40px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            @if ($company && $company->logo)
                <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo">
            @else
                <div style="font-weight: bold; font-size: 24px; color: #0078C8;">
                    {{ $company->name ?? 'Safe Solutions' }}
                </div>
                <div style="font-size: 10px;">Consultants Pvt Ltd</div>
            @endif
        </div>

        <div class="pattern-container">
            <div class="square sq1"></div>
            <div class="square sq2"></div>
            <div class="square sq3"></div>
            <div class="square sq4"></div>
            <div class="square sq5"></div>
        </div>
    </div>

    <div class="content">
        <h1 class="title">Notification</h1>

        <div class="ref-no">
            {{ $refNo }}
        </div>

        <div class="body-text">
            {!! nl2br(strip_tags($announcement->content)) !!}
        </div>

        <div class="signature">
            <img src="{{ public_path('assets/signature/signature.png') }}" alt="Signature">
        </div>

        <div class="footer-text">
            ADMIN DEPARTMENT
        </div>
    </div>
</body>

</html>
