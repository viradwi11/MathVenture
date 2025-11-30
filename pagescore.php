<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telur Dinosaurus Interaktif</title>
    <!-- Memuat Tailwind CSS untuk styling dasar dan responsif -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Menggunakan font yang sedikit 'liar' untuk tema prasejarah */
        @import url('https://fonts.googleapis.com/css2?family=Crete+Round&display=swap');
        
        body {
            font-family: 'Crete Round', serif;
            background-image: url('http://localhost/mathventure/imgmathventure/bg.polos.png'); /* Gradien hutan gelap */
            background-size: cover;
			background-position: center;
			min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #4c2f12;
        }


        /* --- Kontainer Telur & Dinosaurus --- */
        #game-area {
            position: relative;
            width: 300px;
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: flex-end; /* Telur diletakkan di bawah */
            perspective: 1000px;
            margin-top: 50px;
        }

        /* --- Gaya Telur --- */
        #egg {
            width: 250px;
            height: 350px;
            background-color: #a3e635; /* Hijau cerah */
            background-image: linear-gradient(to top, #65a30d, #a3e635, #c0f38f);
            border-radius: 50% / 60% 60% 40% 40%;
            border: 8px solid #4d7c0f;
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.5);
            position: absolute;
            bottom: 0;
            cursor: pointer;
            transition: opacity 0.5s, transform 0.1s;
            transform-origin: bottom center;
            /* ** PERBAIKAN: Berikan z-index yang lebih tinggi dari dinosaurus ** */
            z-index: 15; 
        }

        /* --- Efek Retak Awal (CSS Murni) --- */
        #egg::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 80%;
            height: 5px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%) rotate(5deg);
        }

        /* --- Animasi Getar (Shake) --- */
        @keyframes shake {
            0%, 100% { transform: translate(0, 0) rotate(0); }
            10% { transform: translate(-5px, 0) rotate(-1deg); }
            20% { transform: translate(5px, 0) rotate(1deg); }
            30% { transform: translate(-5px, 0) rotate(-1deg); }
            40% { transform: translate(5px, 0) rotate(1deg); }
        }

        .shaking {
            animation: shake 0.3s ease-in-out;
        }

        /* --- Animasi Pecah Telur --- */
        @keyframes crack {
            0% { opacity: 1; }
            100% { opacity: 0; transform: translateY(-50px) scale(0.8); }
        }

        .cracked {
            animation: crack 0.5s forwards;
            pointer-events: none; /* Nonaktifkan klik setelah pecah */
        }

        /* --- Gaya Dinosaurus --- */
        #dino {
            position: absolute;
            bottom: 0;
            width: 600px;
            height: auto;
            opacity: 0;
            transition: opacity 1s, transform 0.5s;
            transform: translateY(50px);
            /* z-index: 10; --> Tidak perlu z-index yang terlalu tinggi saat telur masih ada */
            z-index: 5; /* Pastikan dinosaurus di bawah telur saat belum menetas */
        }
        
        /* Animasi Muncul Dinosaurus */
        @keyframes hatch-reveal {
            from { opacity: 0; transform: translateY(50px) scale(0.5); }
            to { opacity: 1; transform: translateY(0px) scale(1); }
        }

        .revealed {
            animation: hatch-reveal 1s forwards;
        }

        /* --- Gaya Retakan Progresif (diatur oleh JS) --- */
        #cracks {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 5; /* Di atas warna telur, di bawah dinosaurus */
            pointer-events: none;
        }
        
        .crack-line {
            position: absolute;
            background: #4d7c0f;
            box-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
            /* Garis retak akan dibuat dan diposisikan oleh JavaScript */
        }
        
    </style>
</head>
<body class="p-6">

    <div class="text-3xl md:text-5xl mb-4 text-center font-bold tracking-wider">
        Makhluk Legendaris dari Bukit Numeria!!!!
    </div>
    <div id="status" class="text-xl mb-8 text-center bg-orange-200 text-orange-900 p-3 rounded-xl shadow-lg">
        Ketuk Telur untuk Melihat Hadiahmu!
    </div>

    <div id="game-area">
        
        <!-- Telur (Target Klik) -->
        <div id="egg">
            <!-- Tempat garis retak akan ditambahkan -->
            <div id="cracks"></div>
        </div>
        
        <!-- Dinosaurus (Gambar yang akan muncul) -->
        <img id="dino" 
             src="http://localhost/mathventure/imgmathventure/dino3.png" 
             alt="Dinosaurus yang Menetas" 
             onerror="this.src='http://localhost/mathventure/imgmathventure/dino3.png'"
        >
        
        <!-- Catatan: Anda dapat mengganti URL placeholder di atas dengan URL gambar dinosaurus Anda:
             Misalnya: src="URL_GAMBAR_DINOSAURUS_ANDA" -->
    </div>
    
    <div class="mt-8 text-lg font-mono">
        Ketukan: <span id="tap-count" class="font-bold text-yellow-300">0</span> / <span id="tap-required" class="font-bold text-yellow-300">10</span>
    </div>
		<!-- Tombol Kembali ke Peta -->
	<div class="mt-6">
		<a href="pagepeta.php"
		   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl shadow-lg font-bold transition">
			⬅ Kembali ke Peta
		</a>
	</div>


    <script>
        const egg = document.getElementById('egg');
        const dino = document.getElementById('dino');
        const tapCountDisplay = document.getElementById('tap-count');
        const tapRequiredDisplay = document.getElementById('tap-required');
        const statusDisplay = document.getElementById('status');
        const cracksContainer = document.getElementById('cracks');

        let tapCount = 0;
        // Tentukan jumlah ketukan yang diperlukan agar telur pecah
        const TAPS_REQUIRED = 10;
        tapRequiredDisplay.textContent = TAPS_REQUIRED;
        let isHatching = false;

        // Fungsi untuk menambahkan garis retak secara acak
        function addCrack(progress) {
            // Menentukan seberapa banyak retakan yang sudah terbentuk
            const crackAmount = Math.floor(progress * 5) + 1; // 1 sampai 6 retakan
            
            // Tambahkan retakan jika jumlahnya masih kurang
            if (cracksContainer.children.length < crackAmount) {
                // Membuat elemen retakan baru
                const crackLine = document.createElement('div');
                crackLine.className = 'crack-line';
                
                // Posisi dan ukuran retakan acak
                const size = 30 + Math.random() * 50; // Panjang retakan
                const angle = Math.random() * 360;    // Sudut rotasi
                const top = 30 + Math.random() * 40;  // Posisi vertikal
                const left = 30 + Math.random() * 40; // Posisi horizontal
                
                crackLine.style.width = `${size}px`;
                crackLine.style.height = '4px';
                crackLine.style.top = `${top}%`;
                crackLine.style.left = `${left}%`;
                crackLine.style.transform = `translate(-50%, -50%) rotate(${angle}deg)`;
                crackLine.style.borderRadius = '2px';
                
                cracksContainer.appendChild(crackLine);
            }
        }

        // Fungsi yang dipanggil saat telur diketuk
        function handleTap() {
            if (isHatching) return; // Abaikan ketukan jika sudah menetas

            tapCount++;
            tapCountDisplay.textContent = tapCount;

            // 1. Tambahkan efek getar
            egg.classList.add('shaking');
            // Hapus kelas 'shaking' setelah animasi selesai
            setTimeout(() => {
                egg.classList.remove('shaking');
            }, 300); 

            // 2. Perbarui status dan retakan
            const progress = tapCount / TAPS_REQUIRED;
            // Panggil addCrack lebih sering untuk retakan yang lebih progresif
            addCrack(progress); 

            if (tapCount < TAPS_REQUIRED) {
                // Beri umpan balik ke pengguna
                statusDisplay.textContent = 'Telur bergetar! Hampir menetas...';
            } else {
                // 3. Panggil fungsi penetasan
                hatchDinosaur();
            }
        }

        // Fungsi penetasan dinosaurus
        function hatchDinosaur() {
            isHatching = true;
            statusDisplay.textContent = 'Waktunya Menetas!';

            // 1. Animasi telur pecah dan menghilang
            egg.classList.add('cracked');

            // 2. Tampilkan dinosaurus setelah telur pecah
            setTimeout(() => {
                // Pindahkan dinosaurus ke z-index yang lebih tinggi agar terlihat menetas dari puing
                dino.style.zIndex = 20; 
                dino.classList.add('revealed');
                // Hapus telur sepenuhnya dari tampilan
                egg.style.display = 'none'; 
                statusDisplay.textContent = 'SELAMAT! Kamu Mendapat Dinosaurus!!';
            }, 500); // Tunda sedikit agar animasi 'crack' terlihat

            // Nonaktifkan klik pada area permainan
            egg.removeEventListener('click', handleTap);
        }

        // Tambahkan event listener untuk ketukan (klik)
        egg.addEventListener('click', handleTap);

        // Tambahkan dukungan sentuhan untuk perangkat mobile
        egg.addEventListener('touchstart', (e) => {
            // Mencegah zoom atau scroll saat menyentuh telur
            e.preventDefault(); 
            handleTap();
        });

    </script>
</body>
</html>