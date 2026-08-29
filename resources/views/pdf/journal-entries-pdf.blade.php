<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Journal Entries Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 3px solid #0078C8;
        }

        .header h1 {
            color: #0078C8;
            margin: 0;
            font-size: 24px;
        }

        .header p {
            color: #666;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #0078C8;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Journal Entries Report</h1>
        <p>Generated on: {{ now()->format('F d, Y - h:i A') }}</p>
        <p>Total Records: {{ $journalEntries->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">S.No</th>
                @php
                    $columnLabels = [
                        'date' => 'Date',
                        'unique_no' => 'Reference No',
                        'narration' => 'Narration',
                        'item' => 'Item',
                        'description' => 'Description',
                        'account' => 'Account',
                        'debit' => 'Debit',
                        'credit' => 'Credit',
                        'created_by_user' => 'Created By',
                    ];
                @endphp
                @if (empty($columns) || count($columns) == 0)
                    <th style="width: 10%;">Date</th>
                    <th style="width: 16%;">Reference No</th>
                    <th style="width: 16%;">Narration</th>
                    <th style="width: 16%;">Item</th>
                    <th style="width: 16%;">Description</th>
                    <th style="width: 16%;">Account</th>
                    <th style="width: 14%;">Debit</th>
                    <th style="width: 8%;">Credit</th>
                @else
                    @foreach ($columns as $col)
                        <th>{{ $columnLabels[$col] ?? ucfirst($col) }}</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($journalEntries as $index => $journalEntry)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (empty($columns) || count($columns) == 0)
                        <td>{{ date('m-d-Y', strtotime($journalEntry->date)) }}</td>
                        <td>{{ $journalEntry->unique_no }}</td>
                        <td>{{ $journalEntry->narration }}</td>
                        <td>{{ $journalEntry->item }}</td>
                        <td>{{ $journalEntry->description }}</td>
                        <td>{{ $journalEntry->account?->name }}</td>
                        <td>{{ $journalEntry->debit }}</td>
                        <td>{{ $journalEntry->credit }}</td>
                    @else
                        @foreach ($columns as $col)
                            @if ($col === 'account')
                                <td>{{ $journalEntry->account?->name }}</td>
                            @elseif($col === 'date')
                                <td>{{ date('m-d-Y', strtotime($journalEntry->date)) }}</td>
                            @elseif($col === 'created_by_user')
                                <td>{{ $journalEntry->createdBy?->name ?? '—' }}</td>
                            @else
                                <td>{{ $journalEntry->$col ?? '—' }}</td>
                            @endif
                        @endforeach
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px; color: #999;">
                        No records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>


    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>

</html>
