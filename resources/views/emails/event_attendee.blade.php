<!DOCTYPE html>
<html>
<head>
    <title>Event Invitation</title>
</head>
<body>
    <h1>You have been invited to an event!</h1>
    <p>Event: {{ $details['title'] }}</p>
    <p>Start Time: {{ $details['startTime'] }}</p>
    <p>End Time: {{ $details['endTime'] }}</p>
    @if($details['meetUrl'])
        <p>Join the meeting: <a href="{{ $details['meetUrl'] }}">{{ $details['meetUrl'] }}</a></p>
    @endif
</body>
</html>
