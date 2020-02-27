<?php
$result = [];
$headings = $collection->collapse()->keys();
?>
<br>
<br>
<div>
    <table>
        <thead>
        <tr>
            @foreach($headings as $title)
                <td style="font-weight: bold;">
                    {{ $title }}
                </td>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($collection as $row)
        <tr>
            @foreach($row as $value)
                <td style="font-style: italic;">
                    {{ $value }}
                </td>
            @endforeach
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

