<!DOCTYPE html>
<html>
<head>
    <title>PDF Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #333;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .ticket-info {
            display: flex;
            justify-content: space-between;
            width: 100%;
            border: 1px solid #ddd;
            margin-top: 10px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ticket-section {
            flex: 1;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
<div class="container">
        <h2>Ticket :
            <a href="{{ $ticket['ticket_link'] }}">#{{ $ticket['ticket_id'] }}</a>
        </h2>
        <div class="ticket-info">
            <div class="ticket-section">
                <h4>Event Details</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Day</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $eventName ?? '' }}</td>
                            <td>{{ $ticket['start_date'] ?  formatDate($ticket['start_date'])  : ''}}</td>
                            <td>{{ $ticket['end_date'] ? formatDate($ticket['end_date']) : '' }}</td>
                            <td>{{ $ticket['day'] ? formatFullDay($ticket['day']) : '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="ticket-section">
                <h4>Ticket Details</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket Category</th>
                            <th>Ticket Category Type</th>
                            <th>Ticket Status</th>
                            <th>Seller</th>
                            <th>Buyer</th>
                            <th>Verified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $ticketCategory ?? '' }}</td>
                            <td>{{ $categoryType  ?? ''}}</td>
                            <td>{{ $ticket['available_for'] ? ucFirst($ticket['available_for']) : '' }}</td>
                            <td>{{ $createdBy ?? '__' }}</td>
                            <td>{{ $buyer ?? '__' }}</td>
                            <td>{{ $ticket['isverified'] == 0 ? 'No' : 'Yes'}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="ticket-section">
                <h4>Ticket Amount</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Price</th>
                            <th>Service Charge</th>
                            <th>Change Fee</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $ticket['currency_type'] ?? '' }}{{ $ticket['price'] ? format_price($ticket['price']) : '0.00' }}</td>
                            <td>{{ $ticket['currency_type'] ?? '' }}{{ $ticket['service'] ? format_price($ticket['service']) : '0.00' }}</td>
                            <td>{{ $ticket['currency_type'] ?? '' }}{{ $ticket['change_fee'] ? format_price($ticket['change_fee']) : '0.00' }}</td>
                            <td>{{ $ticket['currency_type'] ?? '' }}{{ $ticket['total'] ? format_price($ticket['total']) : '0.00' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="ticket-section">
                <h4>Addons</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Photo Pack</th>
                            <th>Race With Friend</th>
                            <th>Spectator</th>
                            <th>Charity Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $ticket['photo_pack'] ?? '' }}</td>
                            <td>{{ $ticket['race_with_friend'] ?? '' }}</td>
                            <td>{{ $ticket['spectator'] ?? '' }}</td>
                            <td>{{ $ticket['charity_ticket'] == 0 ? 'No' : 'Yes' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
