<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php';
$path = 'images/';
$vendorlists = $path.time().".png";
$qrimage = time().".png";

if(isset($_REQUEST['sbt-btn']))
{
$fName = $_REQUEST['firstName'];
$lName = $_REQUEST['lastName'];
$age = $_REQUEST['age'];
$birthday = $_REQUEST['birthday'];
$address = $_REQUEST['address'];
$contactNo = $_REQUEST['contactNo'];

// Concatenate all data into a single string
$data = "First Name: $fName\nLast Name: $lName\nAge: $age\nBirthday: $birthday\nAddress: $address\nContact No: $contactNo";


$query = mysqli_query($conn,"insert into vendorlists set fName='$fName', lName='$lName', age='$age', birthday='$birthday',
        address='$address', contactNo='$contactNo', qrimage='$qrimage'");
	if($query)
	{
		?>
		<script>
			alert("Data save successfully");
		</script>
		<?php
	}
}

QRcode :: png($data, $vendorlists, 'H', 4, 4);
echo "<img src='".$vendorlists."'>";
?>