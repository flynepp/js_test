<?php
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>无标题文档</title>
    <style>
        
    table {
        border: 1px solid #ccc;
    }

    td {
        border: 1px solid #ccc;
        padding: 10px;
        text-align: center;
        width: 20px;
    }

    .tb2 {
        background-color: #888;
    }

    .tb1 {
        background-color: #ccc;
    }

    </style>
</head>

<body>
    <div class="mr-cont">

    </div>
</body>

<script>

    let x = 84; // 行数
    let y = 12; // 列数

    document.write('<table>');
    document.write('<tbody>');

    for (let i = 0; i < x; i++) {

        document.write('<tr class="' + (i % 2 == 0 ? 'tb1' : 'tb2') + '">');

        for (let j = 0; j < y; j++) {
            document.write('<td>' + (i * y + j + 1) + '</td>');
        }

        document.write('</tr>');
    }

    document.write('</tbody>');
    document.write('</table>');

</script>

    <table>
        <tbody>
            <tr class="tb1">
                <td>1</td>
                <td>2</td>
                <td>3</td>
                <td>4</td>
                <td>5</td>
                <td>6</td>
            </tr>
            <tr class="tb2">
                <td>7</td>
                <td>8</td>
                <td>9</td>
                <td>10</td>
                <td>11</td>
                <td>12</td>
            </tr>
            <tr class="tb1">
                <td>13</td>
                <td>14</td>
                <td>15</td>
                <td>16</td>
                <td>17</td>
                <td>18</td>
            </tr>
            <tr class="tb2">
                <td>19</td>
                <td>20</td>
                <td>21</td>
                <td>22</td>
                <td>23</td>
                <td>24</td>
            </tr>
        </tbody>
    </table>

</html>