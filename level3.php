<?php
// =========================================================
// 🚩 KONFIGURASI DATABASE SESUAI phpMyAdmin ANDA
// =========================================================
$servername = "localhost"; 
$username = "root";       
$password = "";           
// ⭐ DIGANTI SESUAI phpMyAdmin: db_mathventure
$dbname = "db_mathventure"; 
$tableName = "user_progress"; // ⭐ DIGANTI SESUAI phpMyAdmin: user_progress
// =========================================================

// INISIALISASI VARIABEL
$user_id = 1; // ID user yang sedang login (sementara)
$level_id = 3;
$current_stars = 0;
$db_error = null;

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    $db_error = "Koneksi DB Gagal: " . $conn->connect_error;
} else {
    // 1. QUERY UNTUK MENGAMBIL BINTANG YANG SUDAH TERSIMPAN
    $query = "SELECT stars FROM $tableName WHERE user_id = ? AND level_id = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ii", $user_id, $level_id);
        $stmt->execute();
        $result = $stmt->get_result();
		// Jika tidak ada data progress level ini → buat baris baru
		if ($result->num_rows === 0) {
			$insert = "INSERT INTO $tableName (user_id, level_id, stars) VALUES (?, ?, 0)";
			$stmtInsert = $conn->prepare($insert);
			$stmtInsert->bind_param("ii", $user_id, $level_id);
			$stmtInsert->execute();
			$stmtInsert->close();

			$current_stars = 0; // Default bintang 0
		}
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $current_stars = $row['stars']; // Ambil nilai bintang
        }
        $stmt->close();
    } else {
        $db_error = "Query Gagal: " . $conn->error;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MathVenture - Level 3 Penjumlahan Warna Warni</title>
    
    <style>
        /* (CSS TIDAK DIUBAH, SAMA DENGAN KODE SEBELUMNYA) */
        body {
            font-family: 'Arial', sans-serif;
            background-image: url('http://localhost/mathventure/imgmathventure/bg.soal.png'); 
            background-size: cover;
            background-position: center; 
            background-attachment: fixed;
            background-repeat: no-repeat;
            
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            transition: background 0.5s ease;
        }

        .container {
            position: relative;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            text-align: center;
            width: 90%;
            max-width: 550px;
            border: 5px solid #ff7e5f;
        }

        /* ... (CSS lainnya) ... */
        .score-board {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #ffd700;
            color: #333;
            padding: 5px 15px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 1.2em;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        h1 {
            color: #ff5f6d;
            margin-bottom: 5px;
            font-size: 2em;
            text-shadow: 1px 1px #fff;
            margin-top: 5px;
        }

        .level-info {
            color: #3b8d99;
            font-size: 1.1em;
            margin-bottom: 25px;
            padding: 5px 15px;
            background-color: #a8e063;
            border-radius: 10px;
            display: inline-block;
            font-weight: bold;
        }

        .question-box {
            background-color: #ffdb3b;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 3px dashed #ff9a9e;
        }

        #question-text {
            font-size: 2em;
            font-weight: 900;
            color: #6a3093;
            margin: 0;
            user-select: none;
        }

        .options-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .option-button {
            background-color: #6dd5fa;
            color: white;
            border: none;
            padding: 18px;
            border-radius: 10px;
            font-size: 1.5em;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-weight: bold;
            box-shadow: 0 4px #4c8a9f;
            transform: translateY(0);
        }

        .option-button:hover:not(:disabled) {
            background-color: #4ac1e8;
        }

        .option-button:active:not(:disabled) {
            box-shadow: 0 0 #4c8a9f;
            transform: translateY(4px);
        }

        .correct { 
            background-color: #1dd1a1 !important; 
            box-shadow: 0 4px #109b7c !important; 
        }
        .wrong { 
            background-color: #ff6b6b !important; 
            box-shadow: 0 4px #cc5555 !important; 
        }

        .feedback-message {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 20px;
            min-height: 30px;
            color: #333;
        }

        /* Gaya Tombol Next/Lanjut Soal */
        #next-button {
            background-color: #ee82ee;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 1.2em;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px #b35cb3;
            transition: all 0.2s ease-in-out;
            display: none; 
            margin-bottom: 10px; 
        }
        
        #next-button:hover {
            background-color: #d164d1;
        }

        #next-button:active {
            box-shadow: 0 0 #b35cb3;
            transform: translateY(4px);
        }

        /* --- Kontainer Tombol Aksi Akhir Level --- */
        #end-level-actions {
            display: none; 
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        /* Gaya Tombol Kembali ke Peta (di akhir Level) */
        .action-button.map {
            background-color: #f39c12; /* Warna kuning/oranye */
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.2em;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 5px #e67e22;
            transition: all 0.2s;
        }

        .action-button.map:hover {
            background-color: #e67e22;
        }
        
        .action-button.map:active {
            box-shadow: 0 0 #e67e22;
            transform: translateY(5px);
        }
        
        /* --- HILANGKAN KOTAK PESAN KHUSUS (Pop-up) --- */
        /* Kita tidak perlu lagi menampilkan #message-overlay */
        #message-overlay {
            display: none !important; 
        }
        
    </style>
</head>
<body>
    
    <div id="message-overlay" style="display: none;"> 
        <div id="message-box">
            <div id="msg-title"></div>
            <div id="msg-text"></div>
            <button id="msg-button">OK</button>
        </div>
    </div>
    <div class="container">
        <div class="score-board">
            ⭐️ Bintang: <span id="star-count">0</span>
        </div>

        <h1>MathVenture</h1>
        <div class="level-info">Level 3: Penjumlahan</div>

        <div class="question-box">
            <p id="question-text">Loading...</p>
        </div>

        <div class="options-box" id="options-container">
        </div>

        <div id="feedback" class="feedback-message"></div>
        
        <button id="next-button" onclick="nextQuestion()">Soal Selanjutnya</button>
        
        <div id="end-level-actions">
            <button class="action-button map" onclick="returnToMap()">
                Kembali ke Peta
            </button>
        </div>
    </div>

<script>
        // Data Soal Level 2 (Penjumlahan)
        const level3Questions = [
            {question: "Mavin punya 4 permen. Ia menemukan 3 permen lagi di jalan. Berapa jumlah permen Mavin sekarang?",answer: 7,options: [9, 8, 10, 7]},
            {question: "Di perpustakaan hutan ada 23 buku sihir. Lalu penjaga menambahkan 16 buku baru. Berapa jumlah buku sihir sekarang?",answer: 39,options: [40, 38, 37, 39]},
            {question: "Untuk membuka gerbang rahasia, Mavin perlu mengumpulkan 20 kristal cahaya. Dalam perjalanan ia menemukan 36 kristal di hutan dan 27 kristal di gua. Berapa kristal yang ia miliki sekarang?",answer: 83,options: [81, 83, 78, 90]}
        ];

        // 📍 KONFIGURASI
        const MAP_URL = 'http://localhost/mathventure/pagepeta.php'; 
        const USER_ID = <?php echo json_encode($user_id); ?>; 
        const CURRENT_LEVEL_ID = 3;

        // ⭐ INI MEMUAT BINTANG YANG SUDAH TERSIMPAN DARI DB
        let starCount = <?php echo json_encode($current_stars); ?>; 
        const DB_ERROR = <?php echo json_encode($db_error); ?>; 
        
        let currentQuestionIndex = 0;
        
        const questionText = document.getElementById('question-text');
        const optionsContainer = document.getElementById('options-container');
        const feedbackElement = document.getElementById('feedback');
        const nextButton = document.getElementById('next-button');
        const endLevelActions = document.getElementById('end-level-actions');
        const starCountElement = document.getElementById('star-count');

        // 👻 GHOST FUNCTION: Menghilangkan pop-up (hanya log ke konsol)
        function showMessage(title, message, isError = false) {
             console.warn(`Pesan sistem (DISUPRES): ${title} - ${message}`); 
        }

        function hideMessage() {} // Kosong

        // Fungsi untuk mengacak array (tidak diubah)
        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        // Fungsi untuk mengupdate tampilan bintang (tidak diubah)
        function updateStarCount() {
            starCountElement.textContent = starCount;
        }

        // FUNGSI PENTING: Kirim data bintang ke save_progress.php (silent)
        function saveProgressToServer(levelId, stars) {
            const formData = new URLSearchParams();
            formData.append('user_id', USER_ID);
            formData.append('level_id', levelId);
            formData.append('stars', stars);
            
            fetch('save_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(`[SUKSES] Progres Level ${levelId} (${stars} bintang) berhasil disimpan.`);
                    
                    // Sinyal untuk Peta Utama
                    if (window.opener && typeof window.opener.updateMap === 'function') {
                        window.opener.updateMap(levelId, stars); 
                    } 
                } else {
                    // 🚫 TIDAK ADA showMessage() DI SINI
                    console.error("[GAGAL SAVE DB] Progres tidak tersimpan:", data.message);
                }
            })
            .catch(error => {
                // 🚫 TIDAK ADA showMessage() DI SINI
                console.error('[ERROR KONEKSI SERVER] Tidak dapat menghubungi save_progress.php. Cek XAMPP/Localhost.', error);
            });
        }

        // FUNGSI UTAMA DIPANGGIL SAAT LEVEL SELESAI 
        function levelComplete() {
            questionText.textContent = "Level 3 Selesai! 🎉";
            optionsContainer.innerHTML = "";
            feedbackElement.textContent = `Kamu mendapatkan total ${starCount} Bintang.`;
            nextButton.style.display = 'none';
            endLevelActions.style.display = 'flex';
            
            // Panggil fungsi penyimpanan ke server (silent)
            saveProgressToServer(CURRENT_LEVEL_ID, starCount);
			unlockNextLevel();

        }
		// 🔓 Membuka Level Selanjutnya (Level 2)
		function unlockNextLevel() {
			const formData = new URLSearchParams();
			formData.append('user_id', USER_ID);
			formData.append('level_id', 4);
			formData.append('stars', 0);

			fetch('save_progress.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: formData
			})
			.then(r => r.json())
			.then(d => console.log("Unlock Level 4:", d));
		}



        // Fungsi displayQuestion, checkAnswer, nextQuestion (dipertahankan)
        function displayQuestion() {
            if (currentQuestionIndex >= level3Questions.length) {
                levelComplete();
                return;
            }
            // ... (Logika display soal lainnya) ...
            const currentQuestion = level3Questions[currentQuestionIndex];
            questionText.textContent = `${currentQuestion.question}`;
            optionsContainer.innerHTML = "";
            feedbackElement.textContent = "";
            nextButton.style.display = 'none';
            endLevelActions.style.display = 'none'; 
            const shuffledOptions = [...currentQuestion.options];
            shuffleArray(shuffledOptions);
            shuffledOptions.forEach(option => {
                const button = document.createElement('button');
                button.textContent = option;
                button.className = 'option-button';
                button.onclick = () => checkAnswer(parseInt(option), currentQuestion.answer, button); 
                optionsContainer.appendChild(button);
            });
            updateStarCount(); 
        }

        function checkAnswer(selectedOption, correctAnswer, clickedButton) {
            const buttons = optionsContainer.querySelectorAll('.option-button');
            buttons.forEach(btn => btn.disabled = true);
            if (selectedOption === correctAnswer) {
                clickedButton.classList.add('correct');
                feedbackElement.textContent = "HEBAT! Jawaban Benar! 🌟 Kamu mendapat 1 Bintang!";
                starCount++;
                updateStarCount();
            } else {
                clickedButton.classList.add('wrong');
                feedbackElement.textContent = "Ups! Jawaban yang benar adalah " + correctAnswer + ". 😉";
                buttons.forEach(btn => {
                    if (parseInt(btn.textContent) === correctAnswer && btn !== clickedButton) {
                        btn.classList.add('correct');
                    }
                });
            }
            const isLastQuestion = currentQuestionIndex === level3Questions.length - 1;
            if (!isLastQuestion) {
                 nextButton.style.display = 'block';
            } else {
                setTimeout(levelComplete, 1000);
            }
        }

        function nextQuestion() {
            currentQuestionIndex++;
            displayQuestion();
        }

        // REVISI FUNGSI: Kembali ke Peta (Solusi Anti-Blokir Browser)
        function returnToMap() {
            console.log("Mencoba kembali ke Peta...");

            // 1. Coba tutup jendela (jika dibuka oleh window.open dari Peta)
            if (window.opener) {
                window.close();
                console.log("Sinyal window.close() dikirim.");
            }

            // 2. Fallback: Jika penutupan diblokir atau level dibuka langsung,
            // lakukan pengalihan ke Peta (ini adalah jaminan kembali).
            setTimeout(() => {
                if (!window.closed) { 
                    window.location.href = MAP_URL; 
                    console.log("Gagal menutup atau bukan pop-up, mengalihkan ke Peta: " + MAP_URL);
                }
            }, 500); // Beri waktu 0.5 detik untuk window.close() mencoba bekerja
        }

        // Memulai game saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            // Tampilkan error DB di konsol jika ada
            if (DB_ERROR) {
                console.error("DEBUG DB ERROR: " + DB_ERROR);
                console.warn("Pastikan tabel 'user_progress' ada di database 'db_mathventure' Anda!");
            }
            
            // 🔔 PENTING: Panggil updateStarCount() di awal untuk menampilkan skor awal
            updateStarCount(); 
            displayQuestion();
        });
    </script>
</body>
</html>