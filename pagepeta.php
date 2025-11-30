<?php
// =========================================================
// 🚩 KONFIGURASI DATABASE
// =========================================================
$servername = "localhost"; 
$username = "root";       
$password = "";           
$dbname = "db_mathventure"; // ⭐ Gunakan nama database yang benar
$tableName = "user_progress"; // ⭐ Gunakan nama tabel yang benar
// =========================================================

// INISIALISASI
$user_id = 1; // ID user yang sedang login (sementara)
$level_stars = []; // Array untuk menyimpan bintang setiap level
$db_error = null;

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    $db_error = "Koneksi DB Gagal: " . $conn->connect_error;
} else {
    // 1. QUERY UNTUK MENGAMBIL SEMUA BINTANG USER
    // Ambil level_id dan stars yang sudah dicapai user
    $query = "SELECT level_id, stars FROM $tableName WHERE user_id = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Simpan hasil ke array asosiatif (level_stars[1] = 3, level_stars[2] = 0, dll.)
        while ($row = $result->fetch_assoc()) {
            $level_stars[$row['level_id']] = $row['stars'];
        }
        $stmt->close();
    } else {
        $db_error = "Query Gagal: " . $conn->error;
    }
    $conn->close();
}

// Untuk Level 1, kita akan dapatkan bintangnya:
$stars_level1 = isset($level_stars[1]) ? $level_stars[1] : 0;
// Untuk Level 2, kita akan cek apakah sudah terbuka (unlocked)
$is_level2_unlocked = $stars_level1 > 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MathVenture Peta Level Dinamis (Tanpa Login)</title>

<style>
/* ================== CSS ANDA SEBELUMNYA ================== */
body {
    margin: 0;
    padding: 0;
    height: 100vh;
    font-family: Arial, sans-serif;
    background-image: url('http://localhost/mathventure/imgmathventure/peta.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    overflow: hidden;
}

.map-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.map-element {
    position: absolute;
    border: none;
    cursor: pointer;
    z-index: 10;
    transition: transform 0.2s, box-shadow 0.2s;
}
.map-element:hover {
    transform: scale(1.1); 
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
}

.level-node {
    position: absolute;
    width: 65px;
    height: 65px;
    background-color: #ffe641;
    border: 3px solid #4c2f12;
    border-radius: 50%;
    font-size: 1.8em;
    color: #4c2f12;
    font-weight: bold;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    text-decoration: none; 
    transition: background-color 0.3s, opacity 0.3s, transform 0.2s;
}

.level-node.locked {
    opacity: 0.5;
    pointer-events: none; /* Menonaktifkan klik */
    cursor: default;
    background-color: #ccc; /* Warna berbeda untuk level terkunci */
}
.star-box {
    position: absolute;
    bottom: -12px;       /* geser supaya kelihatan */
    left: 50%;
    transform: translateX(-50%);
    font-size: 22px;
    font-weight: bold;
    color: #ffcc00;      /* warna emas terang */
    text-shadow: 1px 1px 3px rgba(0,0,0,0.4);  /* biar muncul */
    pointer-events: none;
    z-index: 9999 !important;  /* pastikan di atas segala icon */
}

.star-display {
    position: absolute;
    top: 70px;
    font-size: 20px;
    color: gold;
    /* Pusatkan bintang di bawah node */
    width: 150px; 
    left: -40px; 
}

/* POSISI LEVEL */
/* Level Node 1-12 */
.level-1 { top: 66%; left: 55%; }
.level-2 { top: 53%; left: 52%; }
.level-3 { top: 54%; left: 45%; }
.level-4 { top: 36%; left: 31%; }
.level-5 { top: 26%; left: 25%; }
.level-6 { top: 20%; left: 33%; }
.level-7 { top: 25%; left: 55%; }
.level-8 { top: 25%; left: 63%; }
.level-9 { top: 31%; left: 70%; }
.level-10 { top: 21%; left: 79%; }
.level-11 { top: 11%; left: 71%; }
.level-12 { top: 8%; left: 79%; }

/* GULUNGAN ICON */
.oval-icon {
    width: 150px;
    height: 45px;
    background-color: #4c2f12;
    border-radius: 50px / 20px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.oval-icon img {
    width: 70%;
}

/* posisi gulungan */
.map-icon-1 { top: 81%; left: 50%; width: 150px; height: 45px; transform: rotate(-15deg); }
.map-icon-2 { top: 46%; left: 36%; width: 140px; height: 45px; transform: rotate(-25deg); }
.map-icon-3 { top: 24%; left: 41%; width: 140px; height: 45px; transform: rotate(34deg); }
.map-icon-4 { top: 33%; left: 79%; width: 140px; height: 45px; transform: rotate(-21deg); }

.lock-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 22px;
    z-index: 20; /* Pastikan kunci di atas nomor level */
}
.level-node:hover {
    transform: scale(1.15);
    box-shadow: 0 0 15px rgba(255, 200, 0, 0.7);
}
.level-node.unlocked {
    animation: pulse 2s infinite;
}

.btn-home {
    position: absolute;
    top: 10px;
    left: 10px;
    background: white;
    border-radius: 50%;
    width: 55px;
    height: 55px;
    border: 3px solid #4c2f12;
    font-size: 26px;
    cursor: pointer;
    z-index: 200;
}

.btn-home:hover {
    background: #e68900;
    transform: scale(1.05);
}


</style>
</head>

<body>

<div class="map-container">

    <!-- Tombol Bekal (Sama seperti sebelumnya) -->
    <button class="map-element oval-icon map-icon-1" onclick="window.location.href='http://localhost/mathventure/penjumlahan.html'">
        <img src="http://localhost/mathventure/imgmathventure/bekal.png" alt="Bekal 1">
    </button>
    <button class="map-element oval-icon map-icon-2" onclick="window.location.href='http://localhost/mathventure/pengurangan.html'">
        <img src="http://localhost/mathventure/imgmathventure/bekal.png" alt="Bekal 2">
    </button>
    <button class="map-element oval-icon map-icon-3" onclick="window.location.href='http://localhost/mathventure/perkalian.html'">
        <img src="http://localhost/mathventure/imgmathventure/bekal.png" alt="Bekal 3">
    </button>
    <button class="map-element oval-icon map-icon-4" onclick="window.location.href='http://localhost/mathventure/pembagian.html'">
        <img src="http://localhost/mathventure/imgmathventure/bekal.png" alt="Bekal 4">
    </button>

    <!-- Loop untuk Level Node -->
    <?php 
    // Karena kita tidak menggunakan PHP untuk data, kita hanya menggunakan PHP 
    // untuk mencetak struktur dasar HTML-nya.
    const TOTAL_LEVELS = 12;
    for ($i = 1; $i <= TOTAL_LEVELS; $i++): 
    ?>
        <a id="level-<?php echo $i; ?>" 
            href="level<?php echo $i; ?>.php" 
            class="level-node level-<?php echo $i; ?>">
            
            <span><?php echo $i; ?></span>
            
            <!-- Kunci dan Bintang akan diisi dan dikendalikan oleh JavaScript -->
            <span class="lock-icon hidden-init">🔒</span> 
            
            <div class="star-display" id="stars-level-<?php echo $i; ?>">
                <!-- Bintang diisi oleh JS -->
            </div>

        </a>
    <?php endfor; ?>
    
    <!-- Tombol untuk Reset Progres (Membantu Debugging) -->
    <button 
        style="position: absolute; top: 10px; right: 10px; background: red; color: white; padding: 5px; border-radius: 5px; cursor: pointer; z-index: 100;"
        onclick="resetProgress()">
        Reset Progres Bintang
    </button>
	
	<button class="btn-home" onclick="window.location.href='page1.html'">🏠</button>




</div>

<script>
const TOTAL_LEVELS = 12;
const MIN_STARS_TO_UNLOCK = 2;

// ===================
// 1. PROGRESS DARI DATABASE (PHP → JS)
// ===================
let phpProgress = <?php echo json_encode($level_stars); ?>;

// Jika PHP kosong → JS butuh objek
if (!phpProgress) phpProgress = {};

// ===================
// 2. FUNGSI AMBIL PROGRES (dari DB, bukan localStorage)
// ===================
function getProgress() {
    return phpProgress;
}

// ===================
// 3. UPDATE MAP SESUAI BINTANG
// ===================
function updateMapDisplay(progress) {

    let isPrevLevelComplete = true; // level 1 selalu UNLOCK

    for (let i = 1; i <= TOTAL_LEVELS; i++) {

        const node = document.getElementById(`level-${i}`);
        const starBox = document.getElementById(`stars-level-${i}`);
        const lockIcon = node.querySelector(".lock-icon");

        const stars = progress[i] ?? 0;

        // --- tampilkan bintang ---
        starBox.innerHTML = "";
        for (let s = 1; s <= 3; s++) {
            starBox.innerHTML += (stars >= s ? "⭐" : "☆");
        }

        // --- logic unlock ---
        let unlocked = (i === 1) || isPrevLevelComplete;

        if (unlocked) {
            node.classList.remove("locked");
            node.href = `level${i}.php`;
            lockIcon.style.display = "none";
        } else {
            node.classList.add("locked");
            node.href = "#";
            lockIcon.style.display = "block";
        }

        // level setelahnya terbuka jika bintang level ini cukup
        isPrevLevelComplete = (stars >= MIN_STARS_TO_UNLOCK);
    }
}

// ===================
// 4. SIMPAN BINTANG KE DATABASE (bukan localStorage)
// ===================
function saveStars(levelId, stars) {

    fetch("save_progress.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `level_id=${levelId}&stars=${stars}`
    })
    .then(res => res.text())
    .then(response => {
        console.log("Saved:", response);

        // update data lokal
        phpProgress[levelId] = stars;

        // refresh map
        updateMapDisplay(phpProgress);
		if (unlocked) {
    node.classList.remove("locked");
    lockIcon.style.display = "none";
} else {
    node.classList.add("locked");
    lockIcon.style.display = "block";
}

    });
}

// ===================
// 5. RESET (hanya tampilan)
// ===================
function resetProgress() {
    phpProgress = {};
    updateMapDisplay(phpProgress);
    alert("Progres reset (database tidak berubah).");
}

// ===================
// 6. JALANKAN SAAT HALAMAN SIAP
// ===================
document.addEventListener("DOMContentLoaded", () => {
    updateMapDisplay(getProgress());
});
</script>
</body>
</html>