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
    <h1 class="main-header">ADUAN BUKU </h1>
</div>
    <div class="page-wrapper">
    <div class="container">

        <h1>PEMILIHAN SEKOLAH</h1>

        <div class="state-buttons">
            <button onclick="showPanel('melakaPanel')">MELAKA</button>
            <button onclick="showPanel('n9Panel')">NEGERI SEMBILAN</button>
        </div>

        <!-- Melaka Panel -->
        <div class="zone-panel hidden" id="melakaPanel">
            <label for="melakaZone">Zon:</label>
            <select id="melakaZone" onchange="showSchoolSelect('melakaZone', 'melakaSchool'); checkAllSelected();">
                <option value="">-- Pilih Zon --</option>
                <option value="melakaTengah">Melaka Tengah</option>
                <option value="jasin">Jasin</option>
            </select>

            <div class="school-select hidden" id="melakaSchool">
                <label>Sekolah:</label>
                <select id="melakaSchoolSelect" onchange="checkAllSelected();">
                    <option value="">-- Pilih Sekolah --</option>
                    <option value="SK Taman Melur">SK Taman Melur</option>
                    <option value="SK Taman Mawar">SK Taman Mawar</option>
                </select>
            </div>
        </div>

        <!-- Negeri Sembilan Panel -->
        <div class="zone-panel hidden" id="n9Panel">
            <label for="n9Zone">Zon:</label>
            <select id="n9Zone" onchange="showSchoolSelect('n9Zone', 'n9School'); checkAllSelected();">
                <option value="">-- Pilih Zon --</option>
                <option value="seremban">Seremban</option>
                <option value="rembau">Rembau</option>
            </select>

            <div class="school-select hidden" id="n9School">
                <label>Sekolah:</label>
                <select id="n9SchoolSelect" onchange="checkAllSelected();">
                    <option value="">-- Pilih Sekolah --</option>
                    <option value="SK Seremban">SK Seremban</option>
                    <option value="SK Rembau">SK Rembau</option>
                </select>
            </div>
        </div>
    </div>

        <!-- CONFIRM BUTTON (Hidden initially) -->
        <div class="button-wrapper">
            <button class="confirm-button hidden" id="confirmBtn">Sahkan</button>
        </div>
    </div>
    </div>

    <script>
        function showPanel(panelId) {
            const panels = ['melakaPanel', 'n9Panel'];
            panels.forEach(id => {
                const panel = document.getElementById(id);
                if (id === panelId) {
                    panel.classList.remove('hidden');
                } else {
                    panel.classList.add('hidden');
                }
            });

            document.getElementById("confirmBtn").classList.add("hidden");
        }

        function showSchoolSelect(zoneSelectId, schoolDivId) {
            const zoneSelect = document.getElementById(zoneSelectId);
            const schoolDiv = document.getElementById(schoolDivId);
            if (zoneSelect.value !== "") {
                schoolDiv.classList.remove("hidden");
            } else {
                schoolDiv.classList.add("hidden");
            }
        }

        function checkAllSelected() {
            const melakaZone = document.getElementById("melakaZone");
            const melakaSchool = document.getElementById("melakaSchoolSelect");

            const n9Zone = document.getElementById("n9Zone");
            const n9School = document.getElementById("n9SchoolSelect");

            const confirmBtn = document.getElementById("confirmBtn");

            if ((melakaZone.value !== "" && melakaSchool && melakaSchool.value !== "") ||
                (n9Zone.value !== "" && n9School && n9School.value !== "")) {
                confirmBtn.classList.remove("hidden");
            } else {
                confirmBtn.classList.add("hidden");
            }
        }
    </script>
</body>
</html>
