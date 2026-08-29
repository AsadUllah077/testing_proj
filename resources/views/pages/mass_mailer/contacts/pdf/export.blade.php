<!DOCTYPE html>
<html>

<head>
    <title>Contact Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            bg-color: #f2f2f2;
            font-weight: bold;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Mass Mailer Contacts Export</h2>
        <p>Generated on: {{ date('M d, Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Company</th>
                <th>City</th>
                <th>Tags</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contacts as $contact)
                <tr>
                    <td>{{ $contact->firstname }} {{ $contact->lastname }}</td>
                    <td>{{ $contact->email }}</td>
                    <td>{{ $contact->phone }}</td>
                    <td>{{ $contact->company }}</td>
                    <td>{{ $contact->city }}</td>
                    <td>{{ $contact->tags->pluck('title')->implode(', ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Page {PAGENO}
    </div>
</body>

</html>
