<?php include 'connection.php'; ?>
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pemilihan Sekolah</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <div class="top-bar">
    <h1 class="main-header">ADUAN BUKU</h1>
  </div>
<<<<<<< Updated upstream

  <div class="page-wrapper">
    <div class="container">
      <h1>PEMILIHAN SEKOLAH</h1>

      <form action="report.php" method="POST" id="schoolForm">
        <!-- ✅ FIX: Hanya satu input negeri di luar semua panel -->
        <input type="hidden" name="negeri" id="negeriInput" value="" />

=======

  <div class="page-wrapper">
    <div class="container">
      <h1>PEMILIHAN SEKOLAH</h1>

      <form action="report.php" method="POST" id="schoolForm">
>>>>>>> Stashed changes
        <div class="state-buttons">
          <button type="button" onclick="showPanel('melakaPanel')">MELAKA</button>
          <button type="button" onclick="showPanel('n9Panel')">NEGERI SEMBILAN</button>
        </div>

        <!-- Melaka Panel -->
        <div class="zone-panel hidden" id="melakaPanel">
<<<<<<< Updated upstream
          <div class="school-select" id="melakaSchool">
            <label id="melakaLabel" class="school-label">Sekolah di Melaka:</label>
=======
          <input type="hidden" name="negeri" value="Melaka" />
          <label for="melakaZone">Daerah:</label>
          <select name="daerah" id="melakaZone" onchange="loadSchools('Melaka', this.value, 'melakaSchoolSelect')">
            <option value="">-- Pilih Daerah --</option>
            <option value="Melaka Tengah">Melaka Tengah</option>
            <option value="Jasin">Jasin</option>
          </select>

          <div class="school-select hidden" id="melakaSchool">
            <label>Sekolah:</label>
>>>>>>> Stashed changes
            <select name="sekolah" id="melakaSchoolSelect" onchange="checkAllSelected();">
              <option value="">-- Pilih Sekolah --</option>
            </select>
          </div>
        </div>

        <!-- Negeri Sembilan Panel -->
        <div class="zone-panel hidden" id="n9Panel">
<<<<<<< Updated upstream
          <div class="school-select" id="n9School">
            <label id="n9Label" class="school-label">Sekolah di Negeri Sembilan:</label>
=======
          <input type="hidden" name="negeri" value="Negeri Sembilan" />
          <label for="n9Zone">Daerah:</label>
          <select name="daerah" id="n9Zone" onchange="loadSchools('Negeri Sembilan', this.value, 'n9SchoolSelect')">
            <option value="">-- Pilih Daerah --</option>
            <option value="Seremban">Seremban</option>
            <option value="Rembau">Rembau</option>
          </select>

          <div class="school-select hidden" id="n9School">
            <label>Sekolah:</label>
>>>>>>> Stashed changes
            <select name="sekolah" id="n9SchoolSelect" onchange="checkAllSelected();">
              <option value="">-- Pilih Sekolah --</option>
            </select>
          </div>
        </div>

        <div class="button-wrapper">
          <button type="submit" class="confirm-button hidden" id="confirmBtn">Sahkan</button>
        </div>
      </form>
    </div>
  </div>

<script>
function showPanel(panelId) {
<<<<<<< Updated upstream
  document.getElementById('melakaPanel').classList.add('hidden');
  document.getElementById('n9Panel').classList.add('hidden');
  document.getElementById('confirmBtn').classList.add('hidden');

  document.getElementById(panelId).classList.remove('hidden');

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
  document.getElementById('confirmBtn').classList.remove('hidden');
}
</script>

=======
  ['melakaPanel', 'n9Panel'].forEach(id => {
    document.getElementById(id).classList.toggle('hidden', id !== panelId);
  });
  document.getElementById("confirmBtn").classList.add("hidden");
}

function loadSchools(negeri, daerah, selectId) {
  fetch('get_schools.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'negeri=' + encodeURIComponent(negeri) + '&daerah=' + encodeURIComponent(daerah)
  })
  .then(response => response.json())
  .then(data => {
    const select = document.getElementById(selectId);
    select.innerHTML = '<option value="">-- Pilih Sekolah --</option>';
    data.forEach(name => {
      select.innerHTML += `<option value="${name}">${name}</option>`;
    });
    select.parentElement.classList.remove("hidden");
  });
}

function checkAllSelected() {
  const m = document.getElementById("melakaZone")?.value && document.getElementById("melakaSchoolSelect")?.value;
  const n = document.getElementById("n9Zone")?.value && document.getElementById("n9SchoolSelect")?.value;
  document.getElementById("confirmBtn").classList.toggle("hidden", !(m || n));
}
</script>
>>>>>>> Stashed changes
</body>
</html>
