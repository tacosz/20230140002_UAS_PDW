<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panel Mahasiswa - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen font-sans">

    <nav class="bg-blue-700 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <span class="text-white text-2xl font-extrabold tracking-wide flex items-center gap-2">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253z"/>
                        </svg>
                        SIMPRAK
                    </span>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-2">
                            <?php 
                                $activeClass = 'bg-blue-900 text-white shadow font-bold';
                                $inactiveClass = 'text-blue-100 hover:bg-blue-800 hover:text-white transition';
                            ?>
                            <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> px-4 py-2 rounded-lg text-sm">Dashboard</a>
                            <a href="my_courses.php" class="<?php echo ($activePage == 'my_courses') ? $activeClass : $inactiveClass; ?> px-4 py-2 rounded-lg text-sm">Praktikum Saya</a>
                            <a href="courses.php" class="<?php echo ($activePage == 'courses') ? $activeClass : $inactiveClass; ?> px-4 py-2 rounded-lg text-sm">Cari Praktikum</a>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <span class="text-blue-100 mr-4 font-semibold"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                        <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition-all shadow">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 lg:p-8">