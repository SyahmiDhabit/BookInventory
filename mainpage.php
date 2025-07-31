<?php include 'connection.php'; ?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pemilihan Sekolah</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f2f2f2;
    }

    .top-bar {
      background-color: #2b5179ff;
      color: white;
      padding: 15px 0;
      text-align: center;
      position: relative;
    }

    .main-header {
      margin: 0;
      font-size: 24px;
    }

    .profile-icon {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 24px;
      color: white;
      text-decoration: none;
    }

    .profile-icon:hover {
      color: #dcdcdc;
    }

    .page-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      flex-direction: column;
    }

    .container {
      background-color: #fff;
      padding: 30px 20px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-width: 500px;
      width: 100%;
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }

    .state-buttons {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 30px;
    }

    .state-buttons button {
      padding: 12px 20px;
      font-size: 16px;
      border: none;
      background-color: #007bff;
      color: white;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .state-buttons button:hover {
      background-color: #0056b3;
    }

    .zone-panel {
      display: none;
      margin-top: 10px;
    }

    .school-select {
      margin: 20px 0;
    }

    .school-label {
      display: block;
      font-weight: bold;
      margin-bottom: 8px;
    }

    select {
      width: 100%;
      padding: 10px;
      font-size: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    .button-wrapper {
      text-align: center;
      margin-top: 20px;
    }

    .confirm-button {
      padding: 12px 25px;
      background-color: #28a745;
      color: white;
      border: none;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
    }

    .confirm-button:hover {
      background-color: #218838;
    }

    .hidden {
      display: none;
    }

    @media (max-width: 500px) {
      .state-buttons {
        flex-direction: column;
      }

      .state-buttons button {
        width: 100%;
        justify-content: center;
      }


      .main-header {
        font-size: 20px;
      }
    }
  </style>
</head>

<body>
  <div class="top-bar">
    <h1 class="main-header">ADUAN BUKU</h1>
  </div>

  <div class="page-wrapper">
    <div class="container">
      <h1>PEMILIHAN SEKOLAH</h1>

      <form action="report.php" method="POST" id="schoolForm">
        <input type="hidden" name="negeri" id="negeriInput" value="" />

        <div class="state-buttons">
          <button type="button" onclick="showPanel('melakaPanel')">
            <i class="fas fa-map-marker-alt"></i> MELAKA
          </button>
          <button type="button" onclick="showPanel('n9Panel')">
            <i class="fas fa-map-marker-alt"></i> NEGERI SEMBILAN
          </button>
        </div>

        <!-- Melaka Panel -->
        <div class="zone-panel" id="melakaPanel">
          <div class="school-select" id="melakaSchool">
            <label class="school-label">Sekolah di Melaka:</label>
            <select id="melakaSchoolSelect" onchange="checkAllSelected();">
              <option value="">-- Pilih Sekolah --</option>
            </select>
          </div>
        </div>

        <!-- Negeri Sembilan Panel -->
        <div class="zone-panel" id="n9Panel">
          <div class="school-select" id="n9School">
            <label class="school-label">Sekolah di Negeri Sembilan:</label>
            <select id="n9SchoolSelect" onchange="checkAllSelected();">
              <option value="">-- Pilih Sekolah --</option>
            </select>
          </div>
        </div>

        <input type="hidden" name="sekolah" id="schoolCodeInput">

        <div class="button-wrapper">
          <button type="submit" class="confirm-button hidden" id="confirmBtn">
            <i class="fas fa-check-circle"></i> Sahkan
          </button>
        </div>
      </form>
    </div>
  </div>

<script>
function showPanel(panelId) {
  document.getElementById('melakaPanel').style.display = 'none';
  document.getElementById('n9Panel').style.display = 'none';
  document.getElementById('confirmBtn').classList.add('hidden');

  document.getElementById(panelId).style.display = 'block';

  if (panelId === 'melakaPanel') {
    document.getElementById('negeriInput').value = 'Melaka';
    fetchSchools('Melaka', 'melakaSchoolSelect');
  } else if (panelId === 'n9Panel') {
    document.getElementById('negeriInput').value = 'Negeri Sembilan';
    fetchSchools('Negeri Sembilan', 'n9SchoolSelect');
  }
}

function fetchSchools(negeri, selectId) {
  const dropdown = document.getElementById(selectId);
  dropdown.innerHTML = '<option value="">Loading...</option>';

  fetch('get_schools.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'negeri=' + encodeURIComponent(negeri)
  })
  .then(response => response.json())
  .then(data => {
    dropdown.innerHTML = '<option value="">-- Pilih Sekolah --</option>';
    if (data.length > 0) {
      data.forEach(school => {
        dropdown.innerHTML += `<option value="${school.code}">${school.name}</option>`;
      });
    } else {
      dropdown.innerHTML = '<option value="">Tiada sekolah dijumpai</option>';
    }
  })
  .catch(error => {
    console.error('Fetch error:', error);
    dropdown.innerHTML = '<option value="">Ralat ambil data sekolah</option>';
  });
}

function checkAllSelected() {
  let selectedSchool = '';
  const negeri = document.getElementById('negeriInput').value;

  if (negeri === 'Melaka') {
    selectedSchool = document.getElementById('melakaSchoolSelect').value;
  } else if (negeri === 'Negeri Sembilan') {
    selectedSchool = document.getElementById('n9SchoolSelect').value;
  }

  document.getElementById('schoolCodeInput').value = selectedSchool;

  if (selectedSchool !== '') {
    document.getElementById('confirmBtn').classList.remove('hidden');
  } else {
    document.getElementById('confirmBtn').classList.add('hidden');
  }
}
</script>zz
</body>
</html>
