<!DOCTYPE html>
<html>

<head>
    <title>Users Export</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
            word-wrap: break-word;
        }

        img {
            max-width: 80px;
            height: auto;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <h1>User List</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>First</th>
                <th>Last</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Username</th>
                <th>Designation</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->first_name }}</td>
                    <td>{{ $user->last_name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone_number }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->designation }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
