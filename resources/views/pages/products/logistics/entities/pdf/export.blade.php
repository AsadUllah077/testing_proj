<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Entitys Export</title>
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

        .type-importer {
            color: blue;
        }

        .type-exporter {
            color: green;
        }

        .type-both {
            color: purple;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Logistics Entitys List</h2>
        <p>Generated on: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Business Name</th>
                <th>Type</th>
                <th>Tax ID</th>
                <th>Country</th>
                <th>Contact Name</th>
                <th>Email</th>
                <th>Registered City</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entitys as $entity)
                <tr>
                    <td>{{ $entity->business_legal_name }}</td>
                    <td>
                        <span class="type-{{ strtolower($entity->entity_type) }}">
                            {{ $entity->entity_type }}
                        </span>
                    </td>
                    <td>{{ $entity->tax_id_vat_number }}</td>
                    <td>{{ $entity->registration_country }}</td>
                    <td>{{ $entity->contact_full_name }}</td>
                    <td>{{ $entity->contact_email }}</td>
                    <td>{{ $entity->registered_city }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
