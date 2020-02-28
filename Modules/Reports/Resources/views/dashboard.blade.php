<?php

use Carbon\Carbon;

const USER_INDEX = 0;
const TOTAL_INDEX = 1;
const TOTAL_DECIMAL_INDEX = 2;

/**
 * @var $collection \Illuminate\Support\Collection
 */
$dates = [];
$headings = $collection->collapse()->keys();
$collection->each(function ($row) use ($headings, &$dates) {
    foreach ($row as $title => $value) {
        if (in_array($title, [$headings[USER_INDEX], $headings[TOTAL_INDEX], $headings[TOTAL_DECIMAL_INDEX]]) || intval($value) === 0) {
            continue;
        }

        $workedInSeconds = time() + floor($value * 60 * 60);

        $dates[$title][$row[$headings[USER_INDEX]]] = [
            $headings[USER_INDEX] => $row[$headings[USER_INDEX]],
            $headings[TOTAL_INDEX] => Carbon::createFromTimestamp($workedInSeconds)->diffForHumans(null, true, true, 3),
            $headings[TOTAL_DECIMAL_INDEX] => round(Carbon::createFromTimestamp($workedInSeconds)->diffInSeconds() / 60 / 60, 3)
        ];
    }
});

?>

<br>
<br>
<br>
@foreach($dates as $title => $data)
    <h3>{{ $title }}</h3>
    <table style="border: 1px solid black;">
        <thead>
        <tr style="border: 1px solid black;">
            <td style="border: 1px solid black;font-weight: bold">
                {{ $headings[USER_INDEX] }}
            </td>
            <td style="border: 1px solid black;font-weight: bold">
                {{ $headings[TOTAL_INDEX] }}
            </td>
            <td style="border: 1px solid black;font-weight: bold">
                {{ $headings[TOTAL_DECIMAL_INDEX] }}
            </td>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $userName => $values)
            <tr style="border: 1px solid black;">
                <td style="border: 1px solid black;font-style: italic;">
                    {{ $values[$headings[USER_INDEX]] }}
                </td>
                <td style="border: 1px solid black;">
                    {{ $values[$headings[TOTAL_INDEX]] }}
                </td>
                <td style="border: 1px solid black;">
                    {{ $values[$headings[TOTAL_DECIMAL_INDEX]] }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach

