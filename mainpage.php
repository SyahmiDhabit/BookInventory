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

  <div class="page-wrapper">
    <div class="container">
      <h1>PEMILIHAN SEKOLAH</h1>

      <form action="report.php" method="POST" id="schoolForm">
        <!-- ✅ FIX: Hanya satu input negeri di luar semua panel -->
        <input type="hidden" name="negeri" id="negeriInput" value="" />

        <div class="state-buttons">
          <button type="button" onclick="showPanel('melakaPanel')">MELAKA</button>
          <button type="button" onclick="showPanel('n9Panel')">NEGERI SEMBILAN</button>
        </div>

        <!-- Melaka Panel -->
        <div class="zone-panel hidden" id="melakaPanel">
          <div class="school-select" id="melakaSchool">
            <label id="melakaLabel" class="school-label">Sekolah di Melaka:</label>
            <select name="sekolah" id="melakaSchoolSelect" onchange="checkAllSelected();">
              <option value="">-- Pilih Sekolah --</option>
            </select>
          </div>
        </div>

        <!-- Negeri Sembilan Panel -->
        <div class="zone-panel hidden" id="n9Panel">
          <div class="school-select" id="n9School">
            <label id="n9Label" class="school-label">Sekolah di Negeri Sembilan:</label>
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

</body>
</html>
