<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Announcement - {{ $refNo }}</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        /* ============ HEADER ============ */
        .header {
            position: relative;
            height: 160px;
            width: 100%;
            overflow: hidden;
        }

        /* ---- Logo (left) ---- */
        .logo {
            position: absolute;
            top: 45px;
            left: 50px;
        }

        .logo img {
            height: 65px;
        }

        .logo-text-name {
            font-weight: bold;
            font-size: 22px;
            color: #1a56a0;
            line-height: 1.2;
        }

        .logo-text-sub {
            font-size: 10px;
            color: #555;
            letter-spacing: 0.5px;
        }

        /* ---- Scattered small diamonds (top-left) ---- */
        .diamonds-left {
            position: absolute;
            top: 0;
            left: 0;
            width: 200px;
            height: 100px;
        }

        .diamonds-left .d {
            position: absolute;
            transform: rotate(45deg);
        }

        /* ---- Blue geometric pattern (top-right) ---- */
        .pattern-right {
            position: absolute;
            top: 0;
            right: 0;
            width: 320px;
            height: 160px;
            overflow: hidden;
        }

        .pattern-right .sq {
            position: absolute;
            transform: rotate(45deg);
        }

        /* Dark cluster (far right / corner) */
        .sq-dark-1 {
            width: 120px;
            height: 120px;
            background: #0A1628;
            top: -30px;
            right: -30px;
        }

        .sq-dark-2 {
            width: 95px;
            height: 95px;
            background: #0D2B4E;
            top: -15px;
            right: 55px;
        }

        .sq-dark-3 {
            width: 105px;
            height: 105px;
            background: #0E4C8A;
            top: 30px;
            right: -10px;
        }

        /* Medium blue */
        .sq-med-1 {
            width: 85px;
            height: 85px;
            background: #0078C8;
            top: -10px;
            right: 120px;
        }

        .sq-med-2 {
            width: 90px;
            height: 90px;
            background: #0E4C8A;
            top: 55px;
            right: 65px;
        }

        .sq-med-3 {
            width: 75px;
            height: 75px;
            background: #0078C8;
            top: 70px;
            right: 140px;
        }

        /* Light blue */
        .sq-light-1 {
            width: 65px;
            height: 65px;
            background: #4DA6E0;
            top: -5px;
            right: 195px;
        }

        .sq-light-2 {
            width: 68px;
            height: 68px;
            background: #A8D4F0;
            top: 50px;
            right: 210px;
        }

        .sq-light-3 {
            width: 55px;
            height: 55px;
            background: #D6EAF8;
            top: 5px;
            right: 255px;
        }

        .sq-light-4 {
            width: 58px;
            height: 58px;
            background: #C8D6E0;
            top: 90px;
            right: 245px;
        }

        /* Scattered small light diamonds (right fade) */
        .d-right-1 {
            width: 16px;
            height: 16px;
            background: #D6EAF8;
            top: 5px;
            right: 305px;
        }

        .d-right-2 {
            width: 13px;
            height: 13px;
            background: #C8D6E0;
            top: -5px;
            right: 330px;
        }

        .d-right-3 {
            width: 11px;
            height: 11px;
            background: #E8F4FA;
            top: 35px;
            right: 315px;
        }

        .d-right-4 {
            width: 10px;
            height: 10px;
            background: #D6EAF8;
            top: 65px;
            right: 305px;
        }

        /* ============ DIVIDER ============ */
        .divider {
            border: none;
            border-top: 0.5px solid #E5E7EB;
            margin: 0 40px;
        }

        /* ============ CONTENT ============ */
        .content {
            margin: 30px 60px;
            min-height: 580px;
        }

        .title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
            color: #000;
            letter-spacing: 2px;
        }

        .ref-no {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 35px;
            color: #000;
        }

        .body-text {
            font-size: 13px;
            text-align: justify;
            margin-bottom: 40px;
            color: #333;
            line-height: 1.8;
        }

        /* ============ SIGNATURE ============ */
        .signature {
            margin-top: 50px;
            margin-bottom: 5px;
        }

        .signature img {
            width: 150px;
            height: auto;
        }

        .signature-line {
            width: 160px;
            border-top: 1px solid #333;
            margin-bottom: 6px;
        }

        .footer-text {
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>

    <!-- ===== HEADER ===== -->
    <div class="header">

        <!-- Small scattered diamonds top-left -->
        <div class="diamonds-left">
            <div class="d" style="width:10px;height:10px;background:#E5E7EB;top:8px;left:28px;"></div>
            <div class="d" style="width:13px;height:13px;background:#D1D5DB;top:2px;left:55px;"></div>
            <div class="d" style="width:9px;height:9px;background:#F3F4F6;top:18px;left:80px;"></div>
            <div class="d" style="width:11px;height:11px;background:#D1D5DB;top:5px;left:105px;"></div>
            <div class="d" style="width:8px;height:8px;background:#E5E7EB;top:22px;left:130px;"></div>
            <div class="d" style="width:7px;height:7px;background:#F3F4F6;top:10px;left:150px;"></div>
            <div class="d" style="width:9px;height:9px;background:#E5E7EB;top:45px;left:40px;"></div>
            <div class="d" style="width:7px;height:7px;background:#D1D5DB;top:55px;left:18px;"></div>
        </div>

        <!-- Logo -->
        <div class="logo">
            @if ($company && $company->logo)
                <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo">
            @else
                <div class="logo-text-name">Safe Solutions</div>
                <div class="logo-text-sub">Consultants Pvt Ltd</div>
            @endif
        </div>

        <!-- Blue geometric pattern top-right -->
        <div class="pattern-right">
            <!-- Dark cluster -->
            <div class="sq sq-dark-1"></div>
            <div class="sq sq-dark-2"></div>
            <div class="sq sq-dark-3"></div>
            <!-- Medium blue -->
            <div class="sq sq-med-1"></div>
            <div class="sq sq-med-2"></div>
            <div class="sq sq-med-3"></div>
            <!-- Light blue -->
            <div class="sq sq-light-1"></div>
            <div class="sq sq-light-2"></div>
            <div class="sq sq-light-3"></div>
            <div class="sq sq-light-4"></div>
            <!-- Scattered small diamonds (fade left) -->
            <div class="sq d-right-1"></div>
            <div class="sq d-right-2"></div>
            <div class="sq d-right-3"></div>
            <div class="sq d-right-4"></div>
        </div>

    </div>

    <hr class="divider">

    <!-- ===== CONTENT ===== -->
    <div class="content">

        <h1 class="title">Notification</h1>

        <div class="ref-no">{{ $refNo }}</div>

        <div class="body-text">
            {!! nl2br(strip_tags($announcement->content)) !!}
        </div>

        <div class="signature">
            @if (file_exists(public_path('assets/signature/signature.png')))
                <img src="{{ public_path('assets/signature/signature.png') }}" alt="Signature">
            @else
                <div class="signature-line"></div>
            @endif
            <div class="footer-text">ADMIN DEPARTMENT</div>
        </div>

    </div>

</body>

</html>