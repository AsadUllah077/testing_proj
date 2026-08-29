<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Suppliers Export</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .header {
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Logistics Suppliers List</h2>
        <p>Generated on: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Company Name</th>
                <th>Country</th>
                <th>Business Type</th>
                <th>Contact</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->legal_company_name }}</td>
                    <td>{{ $supplier->country_of_registration }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($supplier->business_type)) }}</td>
                    <td>{{ $supplier->primary_contact_name }}</td>
                    <td>{{ $supplier->official_email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
