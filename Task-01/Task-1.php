<?php
$conn = new mysqlite($servername, $username, $password, $dbname); //database connection
if ($conn->connect_error)
{
    die("Connection failed: " . $conn->connect_error);
}

$name = mysqlite_real_escape_string($conn, $_POST['Name']);  
$email = mysqlite_real_escape_string($conn, $_POST['Email_Address']);
$phone = mysqlite_real_escape_string($conn, $_POST['Mobile']);

$sql = "SELECT * FROM submit WHERE 'Email_Address = '$email' AND Mobile = '$phone'";
$result = $conn->query($sql);

if ($result->num_rows > 0)  //check duplication
{
    echo "Duplicate Record Found, Error Code:409.";
}
else
{
    $insertSql = "INSERT INTO submit (Name, Email_Address, Mobile) VALUES ('$name', '$email', '$phone')";

    if ($conn->query($insertSql) === true)
    {
        echo "Thank You. Submitted!";
    }
    else
    {
        echo "Error: " . $insertSql . "<br>" . $conn->error;
    }
}
$conn->close();
?>
