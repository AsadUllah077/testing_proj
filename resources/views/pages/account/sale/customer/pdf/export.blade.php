<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 40px 30px;
        }

        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #333;
        }

        .header {
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #0078C8;
        }

        .company {
            font-size: 18px;
            font-weight: bold;
        }

        .title {
            font-size: 16px;
            color: #0078C8;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #f5f6f7;
            padding: 8px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .footer {
            position: fixed;
            bottom: -15px;
            text-align: center;
            width: 100%;
            font-size: 10px;
            color: #777;
        }

        .page-number:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="company">{{ $companyName }}</div>
        <div class="title">Customer List</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th style="text-align: right;">Due Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $index => $customer)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td>{{ $customer->address ?? '—' }}</td>
                    <td style="text-align: right;">{{ number_format($customer->due_balance ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Page <span class="page-number"></span>
    </div>
</body>

</html>
