<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Report Amazingtime</title>
    <style>
        body {
            width: auto;
            overflow-x: hidden;
            padding: 20px;

            font-family: YTSans,Roboto,Arial,Helvetica,sans-serif;
            color: #282828;
        }

        h2, p {
            margin: 0;
        }

        .row {
            margin: 20px 10px;
        }
        
        .afterwords {
            text-align: center;
            font-size: smaller;
            color: #434254;
        }
    </style>
</head>
<body>

    <div class="row greeting">
        <h1>Hello,</h1>
    </div>

    <div class="row main-text">
        <h2>Please find attached the invoice for the period from {{$fromDate}} to {{$toDate}}</h2>
    </div>

    <div class="row best-wishes">
        <h2>Best wishes,</h2>
        <h2>your Amazingtime.</h2>
    </div>

    <div class="row afterwords">
        <p>Please do not reply this message.</p>
        <p>To cancel this email subscription contact with our manager.</p>
    </div>
</body>
</html>

