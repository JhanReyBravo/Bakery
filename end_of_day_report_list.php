<?php
include 'db_connect.php';

// Check if search form is submitted
$search_start_date = '';
$search_end_date = '';
if (isset($_POST['search'])) {
    $search_start_date = $_POST['start_date'];
    $search_end_date = $_POST['end_date'];
}

// Fetch reports based on the search criteria
if (!empty($search_start_date) && !empty($search_end_date)) {
    $sql = "SELECT * FROM end_of_day_reports WHERE Report_Date BETWEEN '$search_start_date' AND '$search_end_date' ORDER BY Report_Date DESC";
} else {
    $sql = "SELECT * FROM end_of_day_reports ORDER BY Report_Date DESC";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>End of Day Report List</title>
    <link rel="stylesheet" href="end_of_day_list.css">
</head>

<body>
    <h2>End of Day Report List</h2>
    <a class="home" href="home.html">Back</a>

    <form method="POST" action="">
        <label for="start_date" class="start">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="<?php echo $search_start_date; ?>" required>
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="<?php echo $search_end_date; ?>" required>
        <button type="submit" name="sea" class="search">Search</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Report ID</th>
                <th>Report Date</th>
                <th>Beginning Balance</th>
                <th>Total Sales</th>
                <th>Total Payments</th>
                <th>Cash</th>
                <th>GCash</th>
                <th>BankTransfer</th>
                <th>Customer Outstanding</th>
                <th>Total Expenses</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['ReportID']}</td>
                            <td>{$row['Report_Date']}</td>
                            <td>{$row['BeginningBalance']}</td>
                            <td>{$row['Total_Sales']}</td>
                            <td>{$row['Total_Payments']}</td>
                            <td>{$row['Cash']}</td>
                            <td>{$row['Gcash']}</td>
                            <td>{$row['BankTransfer']}</td>
                            <td>{$row['Customer_Outstanding']}</td>
                            <td>{$row['Total_Expenses']}</td>
                            <td>
                                <form action='export_report.php' method='GET' style='display:inline-block;'>
                                    <input type='hidden' name='report_id' value='{$row['ReportID']}'>
                                    <button type='submit'>Export</button>
                                </form>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='11'>No reports found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>

</html>

<?php
$conn->close();
?>