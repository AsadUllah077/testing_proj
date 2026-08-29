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
        <div class="title">User List Export</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Designation</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $user->full_name }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone_number ?? '—' }}</td>
                    <td>{{ $user->designation ?? '—' }}</td>
                    <td>{{ $user->role->name ?? '—' }}</td>
                    <td>{{ $user->status == 1 ? 'Active' : 'Inactive' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Page <span class="page-number"></span>
    </div>
</body>

</html>
