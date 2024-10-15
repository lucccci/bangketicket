<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php';

$path = 'images/';
$vendor_list = $path . time() . ".png";
$qrimage = time() . ".png";

if (isset($_REQUEST['sbt-btn'])) {
    $fName = $_REQUEST['firstName'];
    $mname = $_REQUEST['MidName'];
    $lName = $_REQUEST['lastName'];
    $suffix = $_REQUEST['suffix'];
    $gender = $_REQUEST['gender'];
    $birthday = $_REQUEST['birthday'];
    $age = $_REQUEST['age'];
    $contactNo = $_REQUEST['contactNo'];
    $province = $_REQUEST['province'];
    $municipality = $_REQUEST['city'];
    $barangay = $_REQUEST['barangay'];
    $houseNo = $_REQUEST['houseNumber'];
    $streetname = $_REQUEST['streetName'];

    // Fetch the current last used ID from the sequence table
    $result = mysqli_query($conn, "SELECT last_used_id FROM vendor_id_sequence LIMIT 1");
    $row = mysqli_fetch_assoc($result);
    
    if ($row) {
        // Increment the last used ID
        $newID = (int)$row['last_used_id'] + 1;

        // Update the sequence table with the new last used ID
        mysqli_query($conn, "UPDATE vendor_id_sequence SET last_used_id = $newID");
    } else {
        // Start with 1 if there are no existing IDs
        $newID = 1;
        mysqli_query($conn, "INSERT INTO vendor_id_sequence (last_used_id) VALUES (1)");
    }

    // Format the new vendorID with leading zeros (e.g., BTV-001, BTV-002)
    $formattedID = 'BTV-' . str_pad($newID, 3, '0', STR_PAD_LEFT);

    // Check if the generated ID already exists in the vendor_list
    $checkIDQuery = mysqli_query($conn, "SELECT vendorID FROM vendor_list WHERE vendorID = '$formattedID'");
    
    // If the ID exists, increment until a unique ID is found
    while (mysqli_num_rows($checkIDQuery) > 0) {
        $newID++;
        $formattedID = 'BTV-' . str_pad($newID, 3, '0', STR_PAD_LEFT);
        $checkIDQuery = mysqli_query($conn, "SELECT vendorID FROM vendor_list WHERE vendorID = '$formattedID'");
    }

    // Insert the new vendor record with the formatted vendorID
    $query = mysqli_query($conn, "INSERT INTO vendor_list SET 
                                  vendorID='$formattedID', 
                                  fName='$fName', 
                                  mname='$mname', 
                                  lName='$lName', 
                                  suffix='$suffix', 
                                  gender='$gender', 
                                  birthday='$birthday', 
                                  age='$age', 
                                  contactNo='$contactNo', 
                                  province='$province', 
                                  municipality='$municipality', 
                                  barangay='$barangay', 
                                  houseNo='$houseNo', 
                                  streetname='$streetname'");

    if ($query) {
        // Update the QR code generation to use the new vendorID
        $data = "Vendor ID: $formattedID\nClick to view transactions: http://localhost/Bangke%20Ticket%207.2/vendortransactions.php?id=$formattedID";
        $updateQuery = mysqli_query($conn, "UPDATE vendor_list SET qrimage='$qrimage' WHERE vendorID='$formattedID'");

        ?>
        <script>
            alert("Data saved successfully with Vendor ID: <?php echo $formattedID; ?>");
        </script>
        <?php
    } else {
        echo "<p>Error saving data.</p>";
    }

    QRcode::png($data, $vendor_list, 'H', 4, 4);

    // Display the QR code inside a modal
    echo '<div id="myModal" class="modal">';
    echo '<div class="modal-content">';
    echo '<span class="close">&times;</span>';
    echo '<img src="' . $vendor_list . '" alt="QR Code">';
    echo '<button id="printButton" onclick="printQR()">Print QR Code</button>'; // Print QR Code button
    echo '</div>';
    echo '</div>';
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="icon" href="pics/logo-bt.png">
  <link rel="stylesheet" href="menuheader.css">
  <link rel="stylesheet" href="vendorform.css">
  <link rel="stylesheet" href="logo.css">
  
  

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Vendor</title>
  <style>
  /* Set a fixed height for the dropdown and enable internal scrolling */
.dropdown-content {
  display: none;
  background-color: #fefcfc;
  position: relative;
  max-height: 150px; /* Set a fixed height for the dropdown */
  overflow-y: auto; /* Enable internal scrolling if content exceeds the height */
  padding-left: 20px; /* Keep padding to make it look nice */
  padding-right: 20px;
  border-left: 3px solid #031F4E;
}

  .logout {
    color: #e74c3c; /* Log Out link color */
    padding: 15px 20px; /* Padding for Log Out link */
    margin-top:120px; /* Add space above Log Out link */
    display: flex; /* Ensure the icon and text align properly */
    align-items: center; /* Center align the icon and text vertically */
    transition: background 0.3s, color 0.3s; /* Transition effects */
}

.logout:hover {
    background-color: #c0392b; /* Hover effect for Log Out link */
    color: #fff; /* Change text color on hover */
}

  .back-button {
    display: flex;
    align-items: center; /* Align icon and text */
    color: black;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none; /* Remove underline from text */
    font-family: 'Arial', sans-serif;
}

.back-button i {
    margin-right: 10px; /* Add space between icon and text */
}

.back-button:hover {
    background-color: #6B8CAE; /* Darker grey on hover */
}

  </style>
</head>
<body>

<div class="header-panel">
  </div>
<div class="overlay"></div>



<!-- Sidebar -->
<div id="sideMenu" class="side-menu">
    <div class="logo">
        <img src="pics/logo.png" alt="Logo">
    </div>
    <a href="dashboard.html"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="product.php"><i class="fas fa-box"></i> Product</a>
    <div class="dropdown active">
    <a href="#" class="active"><i class="fas fa-users"></i> Vendors</a>
    <div class="dropdown-content" style="display: block;">
    <a href="vendorlist.php" class="active"><i class="fas fa-list"></i> Vendor List</a>
        <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a> <!-- Highlighted -->
    </div>
</div>
<a href="#"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>

    <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>



<div class="main-content">



  <div class="panel">
    <br>
    <button class="back-button" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>

    <br>
    
    <h2>Vendor Registration Form</h2>
    <h5 class="personalinfo-heading">Personal Details</h5><br>
    <br>
    <form id="userInfoForm" action="vendorform.php" method="POST">
    
<label for="firstName">First Name:</label>
<input type="text" id="firstName" name="firstName" placeholder="Enter First Name" required>

<label for="MidName">Middle Name:</label>
<input type="text" id="MidName" name="MidName" placeholder="Enter Middle Name" required>

<label for="lastName">Last Name:</label>
<input type="text" id="lastName" name="lastName" placeholder="Enter Last Name" required>

<label for="suffix">Suffix:</label>
<select id="suffix" name="suffix">
  <option value="">Select Suffix</option>
  <option value="Jr.">Jr.</option>
  <option value="Sr.">Sr.</option>
  <option value="II">II</option>
  <option value="III">III</option>
  <option value="IV">IV</option>
  <option value="V">V</option>
</select>

<label for="gender">Gender:</label>
<select id="gender" name="gender">
  <option value="">Select Gender</option>
  <option value="male">Male</option>
  <option value="female">Female</option>
</select>



<label for="birthday">Birthday:</label>
      <input type="date" id="birthday" name="birthday" required>

<label for="age">Age:</label>
<input type="number" id="age" name="age" placeholder="Enter Age" required>

<label for="contactNo">Contact Number:</label>
<div class="phone-input-container">
  <span><img src="philippineflag.webp" alt="Philippine Flag"> +63</span>
  <input type="text" id="contactNo" name="contactNo" pattern="\d{10}" placeholder="XXXXXXXXXX" maxlength="10" required>
</div>


      

<h5 class="address-heading">Address</h5> 

<br>  


      <label for="province">State Province:</label>
        <select id="province" name="province" required onchange="updateCityMunicipality()">
          <option value="">Select Province</option>
          <option value="Aurora">Aurora</option>
          <option value="Bataan">Bataan</option>
          <option value="Bulacan">Bulacan</option>
          <option value="Nueva Ecija">Nueva Ecija</option>
          <option value="Pampanga">Pampanga</option>
          <option value="Tarlac">Tarlac</option>
          <option value="Zambales">Zambales</option>
        </select>

        <label for="city">City/Municipality:</label>
        <select id="city" name="city" required onchange="updateBarangay()">
          <option value="">Select City/Municipality</option>
        </select>

        <label for="barangay">Barangay:</label>
        <select id="barangay" name="barangay" required>
          <option value="">Select Barangay</option>
        </select>

        <label for="houseNumber">House No (Lot/Blk):</label>
        <input type="text" id="houseNumber" name="houseNumber" placeholder="Enter House No (Lot/Blk)" required>

        <label for="streetName">Street Name:</label>
        <input type="text" id="streetName" name="streetName" placeholder="Enter Street Name" required>


    
      <input type="submit" value="Generate QR Code" name="sbt-btn">
    </form>
  </div>
</div>



<div id="myModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <p>No notification yet.</p>
  </div>
</div>

<script>



  //CONTACT

  document.getElementById('contactNo').addEventListener('input', function (e) {
    // Remove any non-digit characters
    this.value = this.value.replace(/\D/g, '');
});

document.addEventListener('DOMContentLoaded', function () {
    // Get all input fields
    var inputs = document.querySelectorAll('input[type="text"]');

    // Add event listener for each input field
    inputs.forEach(function(input) {
      input.addEventListener('input', function() {
        // Capitalize the first letter
        var value = input.value;
        if (value.length > 0) {
          input.value = value.charAt(0).toUpperCase() + value.slice(1);
        }
      });
    });
  });

  //bdayy
  function calculateAge() {
  var birthday = document.getElementById("birthday").value;
  var today = new Date();
  var birthDate = new Date(birthday);
  var age = today.getFullYear() - birthDate.getFullYear();
  var m = today.getMonth() - birthDate.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  document.getElementById("age").value = age;
}

// Attach calculateAge function to the change event of birthday input
document.getElementById("birthday").addEventListener("change", calculateAge);

// Initial call to calculate age based on default value of birthday input
calculateAge();

//address

const barangayData = {
    //Municipality of bulacan and barangays
    "Angat": ["Banaban", "Baybay", "Binagbag", "Donacion", "Encanto", "Laog", "Marungko", "Mercado", "Niugan", "Paltok", "Pulong Yantok", "San Roque", "Santa Cruz", "Sapang Pari", "Taboc", "Binagbag"],
    "Balagtas": ["Borol 1st", "Borol 2nd", "Dalig", "Longos", "Panginay", "Pulong Gubat", "San Juan", "Santol", "Wawa"],
    "Angeles": ["Agapito Del Rosario", "Anunas", "Balibago", "Capaya", "Claro M. Recto", "Cuayan", "Cutcut", "Cutud", "Lourdes Northwest", "Lourdes Sur", "Lourdes Sur East", "Malabanias", "Margot", "Marisol", "Mining", "Pampang", "Pandan", "Pulung Maragul", "Pulung Cacutud", "Pulung Bulu", "Salapungan", "San Jose", "San Nicolas", "Santa Teresita", "Santa Trinidad", "Santo Domingo", "Santo Rosario", "Sapalibutad", "Sapangbato", "Tabun", "Virgen Delos Remedios"],
    "Balagtas": ["Borol 1st", "Borol 2nd", "Dalig", "Longos", "Panginay", "Pulong Gubat", "San Juan", "Santol", "Wawa"],
    "Baliuag": ["Bagong Nayon", "Barangca", "Batia", "Calantipay", "Catulinan", "Concepcion", "Makinabang", "Matangtubig", "Paitan", "Poblacion", "Sabang", "San Jose", "San Roque", "Santa Barbara", "Santa Cruz", "Tangos", "Tiaong", "Tilapayong", "Virgen Delas Flores"],
    "Bocaue": ["Antipona", "Bagumbayan", "Bambang", "Batia", "Biñang 1st", "Biñang 2nd", "Bolacan", "Bundukan", "Bunlo", "Caingin", "Duhat", "Igulot", "Lolomboy", "Poblacion", "Sulucan", "Taal", "Tambubong", "Turo", "Wakas"],
    "Bulakan": ["Bagumbayan", "Balubad", "Bambang", "Matungao", "Maysantol", "Pitpitan", "Perez", "San Francisco", "San Jose", "San Nicolas", "Santa Ana", "Sapang", "Taliptip", "Tibig"],
    "Bustos": ["Bonga Mayor", "Bonga Menor", "Camachile", "Cambaog", "Catacte", "Malamig", "Mina", "Pagala", "Poblacion", "San Pedro", "Santor", "Talampas"],
    "Calumpit": ["Balungao", "Buguion", "Calizon", "Calumpang", "Corazon", "Frances", "Gatbuca", "Gugo", "Iba Este", "Iba Oeste", "Longos", "Lumbreras", "Mabolo", "Maysantol", "Palimbang", "Panginay", "Pio Cruzcosa", "Poblacion", "Pulo", "San Jose", "San Juan", "San Marcos", "Santa Catalina", "Santa Lucia", "Santo Cristo", "Sapang Bayan", "Sapang Putol", "Sucol", "Tabon"],
    "Doña Remedios Trinidad": ["Bagong Barrio", "Bakal I", "Bakal II", "Bayabas", "Camachin", "Camachile", "Kalawakan", "Kabayunan", "Pulong Sampalok", "Sapang Bulak"],
    "Guiguinto": ["Cutcut", "Daungan", "Ilang-ilang", "Malis", "Panginay", "Poblacion", "Pritil", "Pulong Gubat", "Santa Cruz", "Santa Rita","Tabang","Tabe","Tiaong","Tuktukan"],
    "Hagonoy": ["Abulalas", "Carillo", "Iba", "Iba-Iba", "Palapat", "Pugad", "San Agustin", "San Isidro", "San Jose", "San Juan", "San Miguel", "San Nicolas", "San Pablo", "San Pascual", "San Pedro", "San Roque", "Santa Elena", "Santa Monica", "Santo Niño", "Santo Rosario", "Tampok"],
    "Malolos": ["Anilao", "Atlag", "Babatnin", "Bagna", "Bagong Bayan", "Balayong", "Balite", "Bangkal", "Barihan", "Bungahan", "Caingin", "Calero", "Caliligawan", "Canalate", "Caniogan", "Catmon", "Cofradia", "Dakila", "Guinhawa", "Liang", "Ligas", "Longos", "Look 1st", "Look 2nd", "Lugam", "Mabolo", "Mambog", "Masile", "Matimbo", "Mojon", "Namayan", "Niugan", "Pamarawan", "Panasahan", "Pinagbakahan", "San Agustin", "San Gabriel", "San Juan", "San Pablo", "San Vicente", "Santiago", "Santisima Trinidad", "Santo Cristo", "Santo Niño", "Santo Rosario", "Santor", "Sumapang Bata", "Sumapang Matanda", "Taal", "Tikay"],
    "Marilao": ["Abangan Norte", "Abangan Sur", "Ibayo", "Lambakin", "Lias", "Loma de Gato", "Nagbalon", "Patubig", "Poblacion I", "Poblacion II", "Prenza I", "Prenza II", "Santa Rosa I", "Santa Rosa II", "Saog", "Tabing Ilog"],
    "Meycauayan": ["Bahay Pare", "Bancal", "Banga", "Batong Malake", "Bayugo", "Caingin", "Calvario", "Camalig", "Hulo", "Iba", "Langka", "Lawa", "Libtong", "Liputan", "Longos", "Malhacan", "Pajo", "Pandayan", "Pantoc", "Perez", "Poblacion", "Saint Francis", "Saluysoy", "Tugatog", "Ubihan", "Zamora"],  
    "Norzagaray": ["Bangkal", "Baraka", "Bigte", "Bitungol", "Friendship Village Resources", "Matictic", "Minuyan", "Partida", "Pinagtulayan", "Poblacion", "San Lorenzo", "San Mateo", "Santa Maria", "Tigbe"],
    "Obando": ["Binuangan", "Hulo", "Lawa", "Mabolo", "Pag-asa", "Paliwas", "Panghulo", "San Pascual", "Tawiran", "Ubihan", "Paco", "Salambao"],
    "Pandi": ["Bagong Barrio", "Bagong Pag-asa", "Baka-bakahan", "Bunsuran 1st", "Bunsuran 2nd", "Bunsuran 3rd", "Cacarong Bata", "Cacarong Matanda", "Cupang", "Malibong Bata", "Manatal", "Mapulang Lupa", "Masuso", "Masuso East", "Poblacion", "Real de Cacarong", "Santo Niño", "San Roque", "Siling Bata", "Siling Matanda"],
    "Paombong": ["Akle", "Bagong Barrio", "Balagtas", "Binakod", "Kapiti", "Malumot", "Pinalagdan", "Poblacion", "San Isidro I", "San Isidro II", "San Jose", "San Roque", "San Vicente", "Santa Cruz", "Santa Lucia", "Sapang Dalaga"],
    "Plaridel": ["Agnaya", "Bagong Silang", "Banga 1st", "Banga 2nd", "Bintog", "Bulihan", "Caniogan", "Dampol", "Lumang Bayan", "Parulan", "Poblacion", "Pulong Bayabas", "San Jose", "Santa Ines", "Santo Cristo", "Santo Niño", "Sapang Putol"],
    "Pulilan": ["Balatong A", "Balatong B", "Cutcot", "Dampol 1st", "Dampol 2nd", "Dulong Malabon", "Inaon", "Longos", "Lumbac", "Paltao", "Penabatan", "Poblacion", "Santa Peregrina", "San Francisco", "Tibag", "Tabon", "Tibag"],
    "San Ildefonso": ["Akling", "Alagao", "Anyatam", "Bagong Barrio", "Bagong Pag-asa", "Basuit", "Bubulong Malaki", "Calasag", "Calawitan", "Casalat", "Lapnit", "Malipampang", "Masile", "Matimbubong", "Paltao", "Pinaod", "Poblacion", "Pulong Tamo", "San Juan", "Sapang Dayap", "Sumandig", "Telepatio", "Upig", "Ulingao"],
    "San Miguel": ["Bagong Silang", "Balaong", "Bardias", "Baritan", "Biazon", "Bicas", "Buga", "Buliran", "Calumpang", "Cambita", "Camias", "Damas", "Ilog Bulo", "Kabaritan", "King Kabayo", "Lico", "Lomboy", "Magmarale", "Maligaya", "Mandile", "Manggahan", "Matimbubong", "Pacalag", "Paliwasan", "Poblacion", "Pulong Duhat", "Sacdalan", "Salangan", "San Agustin", "San Jose", "San Juan", "San Roque", "San Vicente", "Santa Lucia", "Santa Rita Bata", "Santa Rita Matanda", "Santo Cristo", "Sapang", "Sapang Dayap", "Sapang Putik", "Tandiyong Bakal", "Tibagan", "Tucdoc", "Tumana", "Tungkong Mangga", "Tungkong Munti", "Tungkong Silangan", "Tungkong Upper"],
    "San Rafael": ["Banca-Banca", "Caingin", "Coral na Bato", "Cruz na Daan", "Dagat-Dagatan", "Diliman I", "Diliman II", "Libis", "Lico", "Maasim", "Mabalas-Balas", "Mabini", "Malapad na Parang", "Maronguillo", "Pacalag", "Pagala", "Pantubig", "Pasong Bangkal", "Poblacion", "Pulong Bayabas", "Salapungan", "San Agustin", "San Roque", "Sapang Putik", "Talacsan", "Tambubong", "Tungkong Mangga", "Ulingao"],
    "Santa Maria": ["Bagbaguin", "Balasing", "Buenavista", "Bulac", "Camangyanan", "Catmon", "Cay Pombo", "Caysio", "Dulong Bayan", "Guyong", "Lalakhan", "Mag-asawang Sapa", "Mahabang Parang", "Manggahan", "Parada", "Poblacion", "Pulong Buhangin", "San Gabriel", "San Jose Patag", "San Vicente", "Santa Clara", "Santa Cruz", "Silangan", "Tabing Bakod", "Tumana"],   
    "San Jose del Monte": ["Bagong Buhay I", "Bagong Buhay II", "Bagong Buhay III", "Ciudad Real", "Dulong Bayan", "Fatima I", "Fatima II", "Fatima III", "Fatima IV", "Fatima V", "Francisco Homes-Guijo", "Francisco Homes-Mulawin", "Francisco Homes-Narra", "Francisco Homes-Yakal", "Gaya-Gaya", "Graceville", "Kaybanban", "Kaypian", "Lawang Pare", "Minuyan I", "Minuyan II", "Minuyan III", "Minuyan IV", "Minuyan Proper", "Poblacion", "Poblacion I", "Poblacion II", "Poblacion III", "San Isidro", "San Manuel", "San Martin I", "San Martin II", "San Martin III", "San Martin IV", "San Martin V", "San Pedro", "Santa Cruz", "Sapang Palay Proper", "Santo Cristo", "Tungkong Mangga"],

    //Municipality of aurora and barangays
    "Baler": ["Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", "Barangay IV (Poblacion)", "Buhangin", "Calabuanan", "Obligacion", "Pingit", "Reserva", "Sabang", "Suklayin", "Zabali"],
    "Casiguran": ["Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", "Barangay 4 (Poblacion)", "Barangay 5 (Poblacion)", "Barangay 6 (Poblacion)", "Barangay 7 (Poblacion)", "Barangay 8 (Poblacion)", "Calangcuasan", "Cozo", "Culat", "Dibacong", "Esperanza", "Lual", "San Ildefonso", "Tabas"],
    "Dilasag": ["Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", "Barangay 4 (Poblacion)", "Diniog", "Dicabasan", "Dilaguidi", "Esperanza", "Lawang", "Masagana"],
    "Dinalungan": ["Abuleg", "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", "Dibaraybay", "Dimabuno", "Lipit", "Mapalad"],
    "Dingalan": ["Aplaya", "Butas na Bato", "Caragsacan", "Davildavilan", "Ibona", "Lagsing", "Maligaya", "Matawe", "Paltic", "Poblacion", "Tanawan", "Umiray", "White Beach"],
    "Dipaculao": ["Bacong", "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", "Bani", "Borlongan", "Buenavista", "Calaocan", "Dibutunan", "Dinadiawan", "Diteki", "Gupa", "Lobbot", "Maligaya", "Mucdol"],
    "Maria Aurora": ["Alcala", "Bagtu", "Bayanihan", "Bazal", "Dialatnan", "Diaat", "Dibut", "Diarabasin", "Dimanayat", "Ditumabo", "Kadayacan", "Malasin", "Suguit", "Villa Aurora", "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", "Quirino"],
    "San Luis": ["Bacong", "Balete", "Dibalo", "Dibut", "Dimanayat", "Ditumabo", "L. Pimentel", "Nonong Senior", "Real", "San Isidro", "San Jose", "San Juan", "Zarah"],

    //Municipality of bataan and barangays
  "Abucay": ["Bangkal", "Calaylayan", "Capitangan", "Gabon", "Laon", "Mabatang", "Omboy", "Panibatuhan", "Salamague", "Wawa"],
  "Bagac": ["Atilano L. Ricardo", "Bagumbayan", "Binuangan", "Ibaba", "Ibis", "Parang", "Paysawan", "Quinawan", "Pag-Asa", "Banawang", "Binukawan"],
  "Balanga City": ["Bagong Silang", "Bagumbayan", "Cataning", "Cupang Proper", "Cupang West", "Dangcol", "Ibayo", "Malabia", "Poblacion", "San Jose", "San Juan", "Sibacan", "Talisay", "Tenejero", "Tortugas"],
  "Dinalupihan": ["Alis", "Colo", "Daang Bago", "Del Rosario", "Gen. Luna", "Happy Valley", "Katipunan", "Layac", "Luacan", "Mabini", "Magsaysay", "Maligaya", "Naparing", "New San Jose", "Old San Jose", "Padre Dandan", "Pag-Asa", "Pagalanggang", "Poblacion", "Roxas", "Saguing", "San Benito", "San Isidro", "San Pablo", "San Ramon", "Santa Isabel"],
  "Hermosa": ["A. Rivera", "Almacen", "Bacong", "Balsic", "Burgos", "Cataning", "Del Pilar", "Lamao", "Mabiga", "Mabuco", "Mandama", "Maite", "Palihan", "Pulo", "Saba", "Sawang", "Sumalo"],
  "Limay": ["Alangan", "Duale", "Kitang 1 and 2", "Lamao", "Landing", "Poblacion", "Reformista", "Saint Francis II", "San Francisco de Asis", "San Isidro", "Tuyo", "Wawa", "Kitang I"],
  "Mariveles": ["Alas-asin", "Balon-Anito", "Batangas II", "Baseco", "Camaya", "Iting", "Lamao", "Lucanin", "Malaya", "Maligaya", "Poblacion", "San Carlos", "San Isidro", "Santo Rosario", "Sisiman", "Townsite", "Wawa"],
  "Morong": ["Binaritan", "Mabayo","Nagbalayong","Sabang", "Poblacion"],
  "Orani": ["Apollo", "Bagong Paraiso", "Balut", "Bayan", "Calero", "Centro", "Doña", "Kapinpin", "Kolinlang", "Mulawin", "Pansacala", "Pociano", "Pantalan Luma", "Paraiso", "Santo Domingo", "Santo Rosario", "Wawa"],
  "Orion": ["Arellano", "Bagumbayan", "Balagtas", "Balut", "Bantan", "Calungusan", "Daan Bilolo", "Daang Parola", "Kapunitan", "Lati", "Lucanin", "Pandatung", "Puting Buhangin", "Sabatan", "San Vicente"],
  "Pilar": ["Bagumbayan", "Balut", "Barangay Pantingan", "Liyang", "Nagwaling", "Panilao", "Pita", "Saint Francis I", "Santa Rosa", "Wakas"],


   //Municipality of nueva ecija and barangays
    "Aliaga": ["Aliaga", "Baguio", "Banga", "Barangka", "Bitas", "Bongabong", "Bulan-bulan", "Calabuan", "Dela Paz", "Gapan", "Inbit", "Lubo", "Mabini", "Malinao", "Mambog", "Mandalag", "Mauway", "Minuli", "Nagcatumbalen", "San Vicente", "Santo Domingo"],
    "Bongabon": ["Bagong Sikat", "Basilang", "Bitulok", "Cuyapo", "Lumbang", "Malimba", "Malanday", "Manganay", "Mangalang", "Magsaysay", "Mabini", "Poblacion", "San Vicente", "Santa Maria", "Tagpos"],
    "Cabiao": ["Bañadero", "Biclat", "Bulaon", "Cabitang", "Dila-dila", "Longos", "Malabanan", "Maligaya", "Poblacion", "San Jose", "San Juan", "San Roque", "San Vicente"],
    "Cabanatuan": ["Bagong Sikat", "Banggain", "Buan", "Caalibangbangan", "Capas", "Canawan", "Carmen", "H. del Pilar", "Hulo", "Imelda", "Laurel", "Poblacion", "San Jose", "San Miguel", "San Pablo", "San Roque", "Santa Rita", "Santiago", "Taal", "Tungkong Mangga"],
    "Carranglan": ["Atut, Bataan", "Carmen", "Del Pilar", "Dingalan", "Labrador", "Lemon", "Magsaysay", "Maranon", "Poblacion", "San Felipe", "San Jose", "San Vicente", "Tala", "Urbiztondo"],
    "Cuyapo": ["Bagumbayan", "Bangal", "Bocaue", "Cuyapo", "Dawis", "Dolores", "Hidalgo", "Layog", "Magaspac", "Maligaya", "Mangat", "Mangga", "Mansaraysayan", "San Antonio", "San Felipe", "San Isidro", "San Juan", "San Vicente", "Santa Rosa", "Tondo"],
    "Gapan": ["Alvarez", "Bangued", "Bata", "Bocobo", "Bongabon", "Bunbungan", "Concepcion", "Duhat", "Hulog", "Jaen", "Mabini", "Poblacion", "San Isidro", "San Vicente", "San Jose", "Santo Domingo", "Santa Lucia"],
    "Gabaldon": ["Bayanan", "Bungahan", "Bunga", "Dela Paz", "La Torre", "Magsaysay", "Mangalang", "Maluya", "Mayantoc", "Poblacion", "San Isidro", "San Vicente", "Santa Maria"],
    "General Mamerto Natividad": ["A. Mendoza", "Bagong Sikat", "Bayanan", "Caguioa", "Camuin", "Dila-dila", "Doña Aurora", "Habul", "Labrador", "Maimpis", "Mawaca", "Poblacion", "San Felipe", "San Isidro"],
    "General Tinio": ["Alua", "Cangca", "Guimba", "Maguin", "Malabnang", "Manggahan", "Natividad", "Poblacion", "San Jose", "San Vicente"],
    "Guimba": ["Bagong Sikat", "Barangal", "Buliran", "Cayanga", "Gapan", "Magsaysay", "Malawig", "Mawaca", "Poblacion", "San Felipe", "San Isidro"],
    "Jaen": ["Bagong Sikat", "Baliwag", "Bungahan", "Bunbungan", "Cabaruan", "Cacutud", "Carmen", "Hulo", "Magsaysay", "Maligaya", "Poblacion", "San Antonio", "San Isidro", "San Vicente"],
    "Laur": ["Bagong Silang", "Bamban", "Bunbungan", "Labrador", "Magsaysay", "Malabnang", "Mansaraysayan", "Natividad", "Poblacion", "San Jose", "San Vicente"],
    "Licab": ["Bansalangin", "Cangcawayan", "Malimango", "Poblacion", "San Isidro", "San Vicente", "Santa Maria"],
    "Llanera": ["Bayanan", "Bitas", "Magsaysay", "Manggahan", "Milan", "Poblacion", "San Isidro", "San Jose", "San Vicente"],
    "Lupao": ["Bagong Silang", "Camascan", "Cayapa", "Cayanga", "Cuyapo", "Kangaro", "Langka", "San Jose", "San Vicente"],
    "Muñoz": ["Bagong Silang", "Balinag", "Bulaon", "Bunga", "Cabunian", "Cananay", "Casaloy", "Gabaldon", "Librad", "Luna", "San Jose", "Santa Cruz"],
    "Nampicuan": ["Bagong Sikat", "Bayo", "Bitas", "Calabuan", "Maligaya", "Nampicuan", "Poblacion", "San Antonio", "San Jose"],
    "Pantabangan": ["Bagumbayan", "Buan", "Cansuso", "Dapdap", "Del Pilar", "Imelda", "Poblacion", "San Jose"],
    "Peñaranda": ["Bansalangin", "Biclat", "Bubuyan", "Magtangola", "Magsaysay", "Maligaya", "Masilang", "Poblacion", "San Jose", "San Vicente"],
    "Quezon": ["Baguio", "Bambang", "Bubuy", "Cagayan", "Dapdap", "Imbang", "Poblacion", "San Jose"],
    "Rizal": ["Bucot", "Bulihan", "Canantong", "Dila-dila", "Elias Angeles", "Hampangan", "Hulo", "Kalabangan", "Poblacion", "San Vicente", "San Jose", "Santa Rosa"],
    "San Antonio": ["Bagong Sikat", "Bulaon", "Luneta", "Poblacion", "San Isidro"],
    "San Isidro": ["Bagong Silang", "Bayan", "Bulacan", "Cabaruan", "Magdalena", "Maligaya", "Manggahan", "Poblacion", "San Vicente"],
    "San Jose": ["Bungabon", "Concepcion", "Gapan", "Guimba", "La Paz", "Licab", "Mabini", "Magsaysay", "Poblacion", "San Vicente", "Santa Maria"],
    "San Leonardo": ["Bacala", "Bayan", "Bulong", "Cabuyao", "Cacanauan", "Concepcion", "Magsaysay", "Manggahan", "San Isidro", "San Vicente", "Santa Maria"],
    "Santa Rosa": ["Bagumbayan", "Baguio", "Balingcanaway", "Bongabong", "Dila-dila", "Magsaysay", "Maligaya", "Poblacion", "San Isidro"],
    "Santo Domingo": ["Bamban", "Baru-an", "Cabalintan", "Carmen", "Dela Paz", "Gulod", "Magsaysay", "Poblacion", "San Jose"],
    "Talavera": ["Aliaga", "Alvila", "Bagumbayan", "Baliwag", "Bulaon", "Canarail", "Cangca", "Carmen", "Gulod", "Hulo", "Labrador", "Malabnang", "Poblacion", "San Vicente"],
    "Talugtug": ["Bagumbayan", "Bataan", "Bulasan", "Cabalantian", "Canak", "Dapdap", "Malibay", "Poblacion", "San Vicente", "Santa Rosa"],
    "Zaragoza": ["Banuang", "Biga", "Bagumbayan", "Bongabon", "Poblacion", "San Vicente"],

    //Municipality of  pampanga and barangays
    "Angeles City": ["Agapito Del Rosario", "Anunas", "Balibago", "Bical", "Capaya", "Cutcut", "Del Rosario", "Duquit", "Epifanio", "Pulungbulu", "San Jose", "San Nicolas", "Santo Rosario", "Sapangbato", "Telebastagan"],
    "Apalit": ["Bamboo", "Banal", "Bata", "Bucal", "Cansinala", "Dila-Dila", "Janipaan", "Santo Cristo", "San Vicente", "Santo Tomas"],
    "Arayat": ["Bagong Sikat", "Banga", "Bañadero", "Bulu", "Caduang Tete", "Cutcut", "San Pedro", "San Juan", "Santa Lucia"],
    "Bacolor": ["Bacolor", "Bulaon", "Magsaysay", "San Vicente", "San Pablo", "Santo Niño"],
    "Candaba": ["Bacong", "Bambang", "Cabuyao", "Capalangan", "Dulong Baybay", "Mabilog", "Malusac", "San Francisco", "San Luis", "Santo Rosario"],
    "Floridablanca": ["Bamban", "Bayan", "Bocaue", "Bulaon", "Dela Paz", "Duquit", "Lusong", "San Jose", "San Pedro", "San Vicente"],
    "Guagua": ["Balayong", "Bayan", "Bulaon", "Cameron", "Del Carmen", "Magsaysay", "Malusac", "Poblacion", "San Pedro", "Santo Rosario"],
    "Lubao": ["Bañadero", "Bucal", "Dela Paz", "Mabalacat", "Malusac", "San Felipe", "San Miguel", "San Pablo", "San Pedro", "Santo Niño"],
    "Mabalacat": ["Bamban", "Bayan", "Capas", "Dela Paz", "Laguna", "San Jose", "San Martin", "San Vicente", "Santo Rosario"],
    "Macabebe": ["Bangan", "Burol", "Concepcion", "Dulong Baybay", "Malusac", "Mansilingan", "Poblacion", "San Isidro", "Santa Barbara", "Santa Lucia"],
    "Masantol": ["Bagang", "Bamboo", "Bucal", "Capalangan", "Dela Paz", "Dulong Baybay", "Hapag", "Malusac", "Mansilingan", "Masantol", "San Jose", "San Miguel", "San Pablo", "San Pedro", "San Vicente", "Santo Niño", "Santo Tomas", "Sapangbato", "Sawa", "Tabuyucan", "Talang", "Tinang", "Tuloy", "Wawa", "Bacao"],
    "Mexico": ["Bagong Bataan", "Balibago", "Dela Paz", "Poblacion", "San Antonio", "San Jose", "San Pedro", "Santo Rosario"],
    "Porac": ["Bayan", "Bical", "Camachiles", "Dapdap", "Lambat", "Mabalacat", "Manibaug", "Santo Niño", "San Pedro"],
    "San Fernando": ["Baliti", "Del Pilar", "Guadalupe", "Julius B. Villanueva", "Lourdes Sur", "Poblacion", "San Agustin", "San Isidro", "San Jose", "San Juan"],
    "San Luis": ["Bagumbayan", "Bulaon", "Cansinala", "Dela Paz", "Maligaya", "Marangal", "San Fernando", "San Jose", "Santo Rosario"],
    "San Simon": ["Baleg", "Balucuc", "San Jose", "Santo Tomas", "Santa Monica"],
    "Sasmuan": ["Bamboo", "Buan", "Bulaon", "Guinbalay", "Lambat", "Malusac", "San Jose", "Santa Rosa"],

    //tarlac
    "Anao": ["Anao", "Baguio", "Bagong Bataan", "Cabitang", "Cabaluyan", "Calapacuan", "Camachile", "Dawis", "Dela Paz", "Guimba", "Malibong", "San Antonio", "San Jose", "San Juan", "San Pedro", "Santa Lucia"],
    "Bamban": ["Bagumbayan", "Bamban", "Cabaluan", "Cacabe", "Cayanga", "Malacat", "San Jose", "San Nicolas", "San Pablo", "Santa Rosa"],
    "Capas": ["Bamban", "Capas", "Cutcut", "Maruglu", "Mabalacat", "Manukang Bayan", "San Antonio", "San Jose", "San Juan", "Santa Juliana"],
    "Concepcion": ["Bamban", "Concepcion", "Nambalan", "Poblacion", "San Jose", "San Juan", "San Pedro", "Santa Rita"],
    "La Paz": ["Bacani", "Dela Paz", "Gomez", "La Paz", "Manalang", "San Isidro", "Santa Lucia", "Santo Domingo"],
    "Mayantoc": ["Banga", "Banga", "Bebong", "Bitao", "Cacabe", "Gapan", "Lawang Bato", "Nampicuan", "San Francisco", "San Jose", "San Vicente"],
    "Moncada": ["Bagong Sikat", "Balayong", "Bamban", "Canukang", "Concepcion", "Laoang", "Poblacion", "San Jose", "San Manuel", "San Rafael"],
    "Paniqui": ["Aglipay", "Concepcion", "Lourdes", "Magsaysay", "Manat", "Paniqui", "San Antonio", "San Jose", "San Luis", "San Pedro"],
    "San Jose": ["Banga", "Bucal", "Caniogan", "Dela Paz", "Guadalupe", "Laoang", "Poblacion", "San Jose", "Santa Lucia", "Santo Domingo"],
    "San Manuel": ["Alua", "Bagong Sikat", "Concepcion", "Nambalan", "Poblacion", "San Felipe", "San Jose", "San Juan"],
    "San Rafael": ["Bucao", "Bucal", "Maligaya", "Poblacion", "San Jose", "San Pedro", "Santa Rita"],
    "Santa Ignacia": ["Balaoan", "Bani", "Banua", "Batang", "Bucal", "Dapdap", "Japad", "Maligaya", "Poblacion", "San Jose"],
    "Tarlac City": ["Aguinaldo", "Balayong", "Balingcanaway", "Bamban", "Bata", "Calibutbut", "Labrador", "Lourdes", "Magsaysay", "Poblacion", "San Jose", "San Vicente", "Santo Cristo"],
    "Victoria": ["Abonador", "Bagong Bait", "Balungao", "Buan", "Bulac", "Dapdap", "Laguerta", "Liberty", "Lipa", "Mabalacat", "San Jose", "San Vicente", "Santa Rosa"],

// zambales
    "Botolan": ["Bagalangit", "Balaybay", "Banga", "Bebes", "Biclat", "Bunga", "Capas", "Columban", "Culo", "Dapdap", "Dela Paz", "Gatpuno", "Mabini", "Magsaysay", "Maloma", "Mansalay", "Mansalay", "Masinloc", "Nangalisan", "Owa", "Poblacion", "San Isidro", "San Juan", "San Pedro", "Santo Rosario"],
    "Castillejos": ["Bagong Sikat", "Bago", "Bamban", "Bantay", "Bebes", "Gumain", "Malaki", "Magsaysay", "Maligaya", "Poblacion", "San Antonio", "San Felipe", "San Isidro", "San Marcelino", "San Pedro", "Santa Rosa"],
    "Iba": ["Aguinaldo", "Balayong", "Bamban", "Batasan", "Bulaon", "Burakan", "Cabaritan", "Casilagan", "Cruz", "Del Pilar", "La Paz", "Magsaysay", "Poblacion", "San Antonio", "San Isidro", "Santa Rita"],
    "Masinloc": ["Bacala", "Bacala", "Balayong", "Bato", "Binoclutan", "Bunga", "Dona Cecilia", "Maloma", "Magsaysay", "Poblacion", "San Agustin", "San Andres", "San Marcelino"],
    "Olongapo City": ["Barretto", "East Tapinac", "New Ilalim", "New Cabalan", "Old Cabalan", "Palanan", "San Antonio", "San Isidro", "San Marcelino", "Wawandue"],
    "San Antonio": ["Bagong Silang", "Bani", "Bamban", "Banao", "Bayanan", "Bucal", "Dalayap", "Gapan", "Malaguin", "Poblacion", "San Jose", "San Vicente"],
    "San Felipe": ["Anoling", "Baba", "Baca", "Balayong", "Baro", "Bayanan", "Biclat", "Cayabu", "Gatpuno", "Magsaysay", "Maligaya", "Nangalisan", "Poblacion", "San Vicente", "Santa Rita"],
    "San Marcelino": ["Bagong Silang", "Balaybay", "Bucal", "Cabangaan", "Dalisdis", "Dalit", "Malusac", "Magsaysay", "Poblacion", "San Jose", "San Vicente"],
    "San Narciso": ["Bamban", "Bulaon", "Bulaw", "Caguiat", "Culis", "Magsaysay", "Malaguin", "Poblacion", "San Jose", "San Vicente"],
    "Santa Cruz": ["Bagong Sikat", "Bacala", "Bamban", "Baro", "Cangay", "Del Pilar", "Dela Paz", "San Jose", "San Vicente"],
    "Subic": ["Alibangbang", "Balayong", "Bamban", "Batan", "Cabatangan", "Cruz", "Dela Paz", "Magsaysay", "Poblacion", "San Antonio", "San Isidro", "San Marcelino", "San Vicente"],
    "Zambales": ["Bagumbayan", "Camarine", "Linao", "Mangato", "Manuel A. Roxas", "Maguindanao", "Malayo", "Narciso", "Olongapo City", "Pangasinan", "Poblacion", "San Antonio", "San Felipe"], 

    // pag bubuoin lahat for now central luzon lang meron
  };



const cityMunicipalityData = {
    "Aurora": ["Baler", "Casiguran", "Dilasag", "Dinalungan", "Dingalan", "Dipaculao", "Maria Aurora", "San Luis"],
    "Bataan": ["Balanga", "Abucay", "Bagac", "Dinalupihan", "Hermosa", "Limay", "Mariveles", "Morong", "Orani", "Orion", "Pilar", "Samal"],
    "Bulacan": ["Angat", "Balagtas", "Baliuag", "Bocaue", "Bulakan", "Bustos", "Calumpit", "Doña Remedios Trinidad", "Guiguinto", "Hagonoy", "Malolos", "Marilao", "Meycauayan", "Norzagaray", "Obando", "Pandi", "Paombong", "Plaridel", "Pulilan", "San Ildefonso", "San Jose del Monte", "San Miguel", "San Rafael", "Santa Maria"],
   "Nueva Ecija": ["Aliaga", "Bongabon", "Cabiao", "Cabanatuan", "Carranglan", "Cuyapo", "Gapan", "Gabaldon", "General Mamerto Natividad", "General Tinio", "Guimba", "Jaen", "Laur", "Licab", "Llanera", "Lupao", "Muñoz", "Nampicuan", "Pantabangan", "Peñaranda", "Quezon", "Rizal", "San Antonio", "San Isidro", "San Jose", "San Leonardo", "Santa Rosa", "Santo Domingo", "Talavera", "Talugtug", "Zaragoza"],
   "Pampanga": ["Angeles City", "Apalit", "Arayat", "Bacolor", "Candaba", "Floridablanca", "Guagua", "Lubao", "Mabalacat", "Macabebe","Masantol", "Mexico", "Porac", "San Fernando", "San Luis", "San Simon", "Sasmuan"],
   "Tarlac": ["Anao", "Bamban", "Capas", "Concepcion", "La Paz", "Mayantoc", "Moncada", "Paniqui", "San Jose", "San Manuel", "San Rafael", "Santa Ignacia", "Tarlac City", "Victoria"],
   "Zambales": ["Botolan", "Castillejos", "Iba", "Masinloc", "Olongapo City", "San Antonio", "San Felipe", "San Marcelino", "San Narciso", "Santa Cruz", "Subic", "Zambales"],
   "Abra": ["Bangued", "Boliney", "Bucay", "Bucloc", "Daguioman", "Danglas", "Dolores", "La Paz", "Lacub", "Lagangilang", "Lagayan", "Langiden", "Licuan-Baay", "Luba", "Malibcong", "Manabo", "Penarrubia", "Pidigan", "Pilar", "Sallapadan", "San Isidro", "San Juan", "San Quintin", "Tayum", "Tineg", "Tubo", "Villaviciosa"],
    "Benguet": ["Atok", "Baguio City", "Bakun", "Bokod", "Buguias", "Itogon", "Kabayan", "Kapangan", "Kibungan", "La Trinidad", "Mankayan", "Sablan", "Tuba", "Tublay"],
    "Ifugao": ["Aguinaldo", "Alfonso Lista", "Asipulo", "Banaue", "Hingyon", "Hungduan", "Kiangan", "Lagawe", "Lamut", "Mayoyao", "Tinoc"],
    "Ilocos Norte": ["Adams", "Bacarra", "Badoc", "Bangui", "Banna", "Batac City", "Burgos", "Carasi", "Currimao", "Dingras", "Dumalneg", "Laoag City", "Marcos", "Nueva Era", "Pagudpud", "Paoay", "Pasuquin", "Piddig", "Pinili", "San Nicolas", "Sarrat", "Solsona", "Vintar"],
    "Ilocos Sur": ["Alilem", "Banayoyo", "Bantay", "Burgos", "Cabugao", "Candon City", "Caoayan", "Cervantes", "Galimuyod", "Gregorio del Pilar", "Lidlidda", "Magsingal", "Nagbukel", "Narvacan", "Quirino", "Salcedo", "San Emilio", "San Esteban", "San Ildefonso", "San Juan", "San Vicente", "Santa", "Santa Catalina", "Santa Cruz", "Santa Lucia", "Santa Maria", "Santiago", "Santo Domingo", "Sigay", "Sinait", "Sugpon", "Suyo", "Tagudin", "Vigan City"],
    "Kalinga": ["Balbalan", "Lubuagan", "Pasil", "Pinukpuk", "Rizal", "Tabuk City", "Tanudan", "Tinglayan"],
    "La Union": ["Agoo", "Aringay", "Bacnotan", "Bagulin", "Balaoan", "Bangar", "Bauang", "Burgos", "Caba", "Luna", "Naguilian", "Pugo", "Rosario", "San Fernando City", "San Gabriel", "San Juan", "Santo Tomas", "Santol", "Sudipen", "Tubao"],
    "Mountain Province": ["Barlig", "Bauko", "Besao", "Bontoc", "Natonin", "Paracelis", "Sabangan", "Sadanga", "Sagada", "Tadian"],
    "Quezon": ["Agdangan", "Alabat", "Atimonan", "Buenavista", "Burdeos", "Calauag", "Candelaria", "Catanauan", "Dolores", "General Luna", "General Nakar", "Guinayangan", "Gumaca", "Infanta", "Jomalig", "Lopez", "Lucban", "Lucena City", "Macalelon", "Mauban", "Mulanay", "Padre Burgos", "Pagbilao", "Panukulan", "Patnanungan", "Perez", "Pitogo", "Plaridel", "Polillo", "Quezon", "Real", "Sampaloc", "San Andres", "San Antonio", "San Francisco", "San Narciso", "Sariaya", "Tagkawayan", "Tayabas City", "Tiaong", "Unisan"],
    "Rizal": ["Angono", "Antipolo City", "Baras", "Binangonan", "Cainta", "Cardona", "Jalajala", "Morong", "Pililla", "Rodriguez", "San Mateo", "Tanay", "Taytay", "Teresa"],
    "Camarines Norte": ["Basud", "Capalonga", "Daet", "Jose Panganiban", "Labo", "Mercedes", "Paracale", "San Lorenzo Ruiz", "San Vicente", "Santa Elena", "Talisay", "Vinzons"],
    "Camarines Sur": ["Baao", "Balatan", "Bato", "Bombon", "Buhi", "Bula", "Cabusao", "Calabanga", "Camaligan", "Canaman", "Caramoan", "Del Gallego", "Gainza", "Garchitorena", "Goa", "Iriga City", "Lagonoy", "Libmanan", "Lupi", "Magarao", "Milaor", "Minalabac", "Nabua", "Naga City", "Ocampo", "Pamplona", "Pasacao", "Presentacion", "Ragay", "Sagnay", "San Fernando", "San Jose", "Sipocot", "Siruma", "Tigaon", "Tinambac"],
    "Catanduanes": ["Bagamanoc", "Baras", "Bato", "Caramoran", "Gigmoto", "Pandan", "Panganiban", "San Andres", "San Miguel", "Viga", "Virac"],
    "Sorsogon": ["Barcelona", "Bulan", "Bulusan", "Casiguran", "Castilla", "Donsol", "Gubat", "Irosin", "Juban", "Magallanes", "Matnog", "Pilar", "Prieto Diaz", "Santa Magdalena", "Sorsogon City"],
};


    function updateCityMunicipality() {
      const provinceSelect = document.getElementById("province");
      const citySelect = document.getElementById("city");
      const barangaySelect = document.getElementById("barangay");
      const selectedProvince = provinceSelect.value;

      // Clear existing options in the city dropdown
      citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>'; // Clear barangay dropdown

      if (selectedProvince && cityMunicipalityData[selectedProvince]) {
        cityMunicipalityData[selectedProvince].forEach(city => {
          const option = document.createElement("option");
          option.value = city;
          option.textContent = city;
          citySelect.appendChild(option);
        });
      }
    }

    function updateBarangay() {
      const citySelect = document.getElementById("city");
      const barangaySelect = document.getElementById("barangay");
      const selectedCity = citySelect.value;

      // Clear existing options in the barangay dropdown
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

      if (selectedCity && barangayData[selectedCity]) {
        barangayData[selectedCity].forEach(barangay => {
          const option = document.createElement("option");
          option.value = barangay;
          option.textContent = barangay;
          barangaySelect.appendChild(option);
        });
      }
    }


    
  function toggleMenu() {
    var sideMenu = document.getElementById("sideMenu");
    var overlay = document.querySelector(".overlay");
    var notificationRectangle = document.getElementById("notificationRectangle");

    if (sideMenu.style.width === "250px") {
      sideMenu.style.width = "0";
      overlay.style.zIndex = 0;
      notificationRectangle.style.left = "0";
    } else {
      sideMenu.style.width = "250px";
      overlay.style.zIndex = -1;
      notificationRectangle.style.left = "250px";
    }
  }

  var modal = document.getElementById("myModal");

  var span = document.getElementsByClassName("close")[0];

  document.querySelector('.notification-rectangle .fa-bell').addEventListener('click', function() {
    modal.style.display = "block";
  });

  span.onclick = function() {
    modal.style.display = "none";
  }

  window.onclick = function(event) {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }

  var modal = document.getElementById("myModal");
    var closeButton = document.querySelector(".close");

    // When the page loads, display the modal if QR code is generated
    window.onload = function () {
        <?php if(isset($_REQUEST['sbt-btn'])) { ?>
            modal.style.display = "block";
        <?php } ?>
    };

    // Close the modal when the close button is clicked
    closeButton.onclick = function () {
        modal.style.display = "none";
    };

    // Close the modal when clicking outside the modal
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

     // Function to print QR Code
function printQR() {
    var qrImageSrc = '<?php echo $vendor_list; ?>'; // Get the image source
    var logoSrc = 'pics/bangketicket.png'; // Path to your logo image
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print QR Code</title></head><body style="text-align:center;">');

    // Modify the size of the logo using inline CSS styles
    printWindow.document.write('<img src="' + logoSrc + '" alt="Logo" style="display:block; margin: 20px auto; max-width: 200px; width: 100%;">');

    // Display the QR Code
    printWindow.document.write('<img src="' + qrImageSrc + '" alt="QR Code" onload="window.print();window.close()">');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
}
// Auto capitalize first letter of first and last names
document.getElementById("firstName").addEventListener("input", function() {
  this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
});

document.getElementById("lastName").addEventListener("input", function() {
  this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
});
document.getElementById("address").addEventListener("input", function() {
  var words = this.value.split(" ");
  for (var i = 0; i < words.length; i++) {
    words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1).toLowerCase();
  }
  this.value = words.join(" ");
});

// Validate contact number
document.getElementById("userInfoForm").addEventListener("submit", function(event) {
  var contactNo = document.getElementById("contactNo").value;
  if (contactNo.length !== 11) {
    alert("Contact number must be exactly 11 digits.");
    event.preventDefault();
  }
});
</script>

</body>
</html>
