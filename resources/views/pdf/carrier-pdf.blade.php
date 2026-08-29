<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Carriers Export</title>
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

        .status-satisfactory {
            color: green;
        }

        .status-conditional {
            color: orange;
        }

        .status-unsatisfactory {
            color: red;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Logistics Carriers List</h2>
        <p>Generated on: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Carrier Name</th>
                <th>Mode</th>
                <th>SCAC / IATA</th>
                <th>DOT Number</th>
                <th>MC Authority</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($carriers as $carrier)
                <tr>
                    <td>{{ $carrier->legal_carrier_name }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($carrier->logistics_mode)) }}</td>
                    <td>{{ $carrier->scac_iata_code }}</td>
                    <td>{{ $carrier->dot_number }}</td>
                    <td>{{ $carrier->mc_authority }}</td>
                    <td>
                        <span class="status-{{ strtolower($carrier->safety_rating) }}">
                            {{ $carrier->safety_rating }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
