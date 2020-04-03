<?php
$result = [];
$headings = $collection->collapse()->keys();
?>
<br>
<table style="border: 1px solid black;">
    <thead>
    <tr style="border: 1px solid black;">
        @foreach($headings as $title)
            <td style="border: 1px solid black;font-weight: bold;">
                {{ $title }}
            </td>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($collection as $row)
        <tr style="border: 1px solid black;">
            @foreach($row as $value)
                <td style="border: 1px solid black;font-style: italic;">
                    {{ $value }}
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>

