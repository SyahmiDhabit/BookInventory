<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

include 'connection.php';

// Fetch data dari Melaka
$query = "SELECT codeBook, titleBook, category, MelakaTengah, comment, dateReceive FROM bookmelaka";
$result = $conn->query($query);

// HTML untuk PDF
$html = '<h2>Senarai Buku Melaka</h2>

<style>
    body { font-family: Arial, sans-serif; }
    h2 { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #000; padding: 8px; font-size: 12px; }
    th { background-color: #d0e0f0; text-align: center; }
    td { vertical-align: top; }
</style>';

$html .= '<table border="1" cellspacing="0" cellpadding="5">
<tr>
    <th>Code</th>
    <th>Title</th>
    <th>Category</th>
    <th>Total Receive</th>
    <th>Comment</th>
    <th>Date Received</th>
</tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>';
    $html .= '<td>' . $row['codeBook'] . '</td>';
    $html .= '<td>' . $row['titleBook'] . '</td>';
    $html .= '<td>' . $row['category'] . '</td>';
    $html .= '<td>' . $row['MelakaTengah'] . '</td>'; // ini quantity sebenar

    $html .= '<td>' . $row['comment'] . '</td>';
    $html .= '<td>' . $row['dateReceive'] . '</td>';
    $html .= '</tr>';
}
$html .= '</table>';

// Convert to PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // landscape lebih sesuai untuk banyak column
$dompdf->render();

// Hantar ke browser
$dompdf->stream("senarai_buku_melaka.pdf", ["Attachment" => false]); // false = view in browser
exit;
?>
