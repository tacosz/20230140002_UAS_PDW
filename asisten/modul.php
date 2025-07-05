<?php
// modul.php - Halaman untuk manajemen modul mata praktikum

require_once '../config.php';

$pageTitle = 'Manajemen Modul';
$activePage = 'modul';
require_once 'templates/header.php';

// Get course_id from URL parameter
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id > 0) {
    // Verify course ownership
    $courseSql = "SELECT * FROM mata_praktikum WHERE id = ? AND asisten_id = ?";
    $courseStmt = $conn->prepare($courseSql);
    $courseStmt->bind_param("ii", $course_id, $_SESSION['user_id']);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();

    if ($courseResult->num_rows === 0) {
        echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>Mata praktikum tidak ditemukan atau Anda tidak memiliki akses</div>";
        exit;
    }

    $courseData = $courseResult->fetch_assoc();
} else {
    // course_id == 0, ambil semua mata praktikum milik asisten
    $courseData = null;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_module'])) {
        $judul = sanitize_input($_POST['judul']);
        $deskripsi = sanitize_input($_POST['deskripsi']);
        $urutan = intval($_POST['urutan']);
        
        $file_materi = null;
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === 0) {
            $uploadResult = uploadFile($_FILES['file_materi'], '../uploads/materi/', ['pdf', 'docx', 'doc', 'pptx', 'ppt']);
            if ($uploadResult['success']) {
                $file_materi = $uploadResult['filename'];
            } else {
                $error_message = "Gagal mengupload file: " . $uploadResult['message'];
            }
        }
        
        if (!isset($error_message)) {
            $sql = "INSERT INTO modul (mata_praktikum_id, judul, deskripsi, file_materi, urutan) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssi", $course_id, $judul, $deskripsi, $file_materi, $urutan);
            
            if ($stmt->execute()) {
                $success_message = "Modul berhasil ditambahkan";
            } else {
                $error_message = "Gagal menambahkan modul";
            }
        }
    }
    
    if (isset($_POST['edit_module'])) {
        $id = intval($_POST['module_id']);
        $judul = sanitize_input($_POST['judul']);
        $deskripsi = sanitize_input($_POST['deskripsi']);
        $urutan = intval($_POST['urutan']);

        $file_materi = $_POST['existing_file'];

        // Cek kepemilikan modul (pastikan modul milik asisten)
        $cekSql = "SELECT m.*, mp.asisten_id FROM modul m JOIN mata_praktikum mp ON m.mata_praktikum_id = mp.id WHERE m.id = ? AND mp.asisten_id = ?";
        $cekStmt = $conn->prepare($cekSql);
        $cekStmt->bind_param("ii", $id, $_SESSION['user_id']);
        $cekStmt->execute();
        $cekResult = $cekStmt->get_result();
        if ($cekResult->num_rows === 0) {
            $error_message = "Anda tidak memiliki akses untuk mengedit modul ini.";
        } else {
            if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === 0) {
                $uploadResult = uploadFile($_FILES['file_materi'], '../uploads/materi/', ['pdf', 'docx', 'doc', 'pptx', 'ppt']);
                if ($uploadResult['success']) {
                    // Delete old file if exists
                    if ($file_materi && file_exists('../uploads/materi/' . $file_materi)) {
                        unlink('../uploads/materi/' . $file_materi);
                    }
                    $file_materi = $uploadResult['filename'];
                } else {
                    $error_message = "Gagal mengupload file: " . $uploadResult['message'];
                }
            }

            if (!isset($error_message)) {
                // Ambil mata_praktikum_id dari modul
                $modulData = $cekResult->fetch_assoc();
                $modul_mata_praktikum_id = $modulData['mata_praktikum_id'];
                $sql = "UPDATE modul SET judul = ?, deskripsi = ?, file_materi = ?, urutan = ? WHERE id = ? AND mata_praktikum_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssiii", $judul, $deskripsi, $file_materi, $urutan, $id, $modul_mata_praktikum_id);

                if ($stmt->execute()) {
                    $success_message = "Modul berhasil diupdate";
                } else {
                    $error_message = "Gagal mengupdate modul";
                }
            }
        }
    }

    if (isset($_POST['delete_module'])) {
        $id = intval($_POST['module_id']);

        // Cek kepemilikan modul (pastikan modul milik asisten)
        $cekSql = "SELECT m.*, mp.asisten_id FROM modul m JOIN mata_praktikum mp ON m.mata_praktikum_id = mp.id WHERE m.id = ? AND mp.asisten_id = ?";
        $cekStmt = $conn->prepare($cekSql);
        $cekStmt->bind_param("ii", $id, $_SESSION['user_id']);
        $cekStmt->execute();
        $cekResult = $cekStmt->get_result();

        if ($cekResult->num_rows === 0) {
            $error_message = "Anda tidak memiliki akses untuk menghapus modul ini.";
        } else {
            $fileData = $cekResult->fetch_assoc();
            $sql = "DELETE FROM modul WHERE id = ? AND mata_praktikum_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $fileData['mata_praktikum_id']);

            if ($stmt->execute()) {
                // Delete file if exists
                if ($fileData['file_materi'] && file_exists('../uploads/materi/' . $fileData['file_materi'])) {
                    unlink('../uploads/materi/' . $fileData['file_materi']);
                }
                $success_message = "Modul berhasil dihapus";
            } else {
                $error_message = "Gagal menghapus modul";
            }
        }
    }
}

// Get modules for this course
if ($course_id > 0) {
    $modulesSql = "SELECT * FROM modul WHERE mata_praktikum_id = ? ORDER BY urutan ASC";
    $modulesStmt = $conn->prepare($modulesSql);
    $modulesStmt->bind_param("i", $course_id);
    $modulesStmt->execute();
    $modulesResult = $modulesStmt->get_result();
} else {
    // Ambil semua modul dari semua mata praktikum milik asisten
    $modulesSql = "SELECT m.*, mp.nama_praktikum FROM modul m 
                   JOIN mata_praktikum mp ON m.mata_praktikum_id = mp.id 
                   WHERE mp.asisten_id = ? 
                   ORDER BY mp.nama_praktikum ASC, m.urutan ASC";
    $modulesStmt = $conn->prepare($modulesSql);
    $modulesStmt->bind_param("i", $_SESSION['user_id']);
    $modulesStmt->execute();
    $modulesResult = $modulesStmt->get_result();
}

// Get module data for editing
$editModule = null;
if ($course_id > 0 && isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editSql = "SELECT * FROM modul WHERE id = ? AND mata_praktikum_id = ?";
    $editStmt = $conn->prepare($editSql);
    $editStmt->bind_param("ii", $editId, $course_id);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    if ($editResult->num_rows > 0) {
        $editModule = $editResult->fetch_assoc();
    }
}
?>

<div class="bg-gradient-to-r from-blue-50 to-blue-100 p-6 rounded-lg shadow mb-6 border border-blue-200">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-2xl font-extrabold text-blue-800">Manajemen Modul</h2>
            <?php if ($course_id > 0): ?>
                <p class="text-gray-600">Mata Praktikum: <span class="font-semibold text-blue-700"><?php echo htmlspecialchars($courseData['nama_praktikum']); ?></span></p>
            <?php else: ?>
                <p class="text-gray-600">Menampilkan semua modul dari semua mata praktikum yang Anda ikuti</p>
            <?php endif; ?>
        </div>
        <a href="mata_praktikum.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-all shadow">
            Kembali
        </a>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 shadow">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 shadow">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<?php if ($course_id > 0): ?>
<div class="bg-white p-8 rounded-xl shadow-lg mb-8 border border-blue-100">
    <h3 class="text-xl font-bold text-blue-800 mb-4 flex items-center gap-2">
        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <?php echo $editModule ? 'Edit Modul' : 'Tambah Modul Baru'; ?>
    </h3>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <?php if ($editModule): ?>
            <input type="hidden" name="module_id" value="<?php echo $editModule['id']; ?>">
            <input type="hidden" name="existing_file" value="<?php echo htmlspecialchars($editModule['file_materi']); ?>">
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-blue-700 mb-2">Judul Modul</label>
                <input type="text" name="judul" required 
                       value="<?php echo $editModule ? htmlspecialchars($editModule['judul']) : ''; ?>"
                       class="w-full px-3 py-2 border border-blue-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 bg-blue-50">
            </div>
            <div>
                <label class="block text-sm font-semibold text-blue-700 mb-2">Urutan</label>
                <input type="number" name="urutan" required min="1" 
                       value="<?php echo $editModule ? $editModule['urutan'] : ''; ?>"
                       class="w-full px-3 py-2 border border-blue-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 bg-blue-50">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-blue-700 mb-2">Deskripsi</label>
            <textarea name="deskripsi" rows="3" required 
                      class="w-full px-3 py-2 border border-blue-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 bg-blue-50"><?php echo $editModule ? htmlspecialchars($editModule['deskripsi']) : ''; ?></textarea>
        </div>
        <div>
            <label class="block text-sm font-semibold text-blue-700 mb-2">File Materi (Optional)</label>
            <input type="file" name="file_materi" accept=".pdf,.doc,.docx,.ppt,.pptx" 
                   class="w-full px-3 py-2 border border-blue-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 bg-blue-50">
            <p class="text-xs text-gray-500 mt-1">Format: PDF, DOC, DOCX, PPT, PPTX</p>
            <?php if ($editModule && $editModule['file_materi']): ?>
                <p class="text-xs text-blue-700 mt-2">
                    File saat ini: <span class="font-semibold"><?php echo htmlspecialchars($editModule['file_materi']); ?></span>
                </p>
            <?php endif; ?>
        </div>
        <div class="flex gap-4">
            <button type="submit" name="<?php echo $editModule ? 'edit_module' : 'add_module'; ?>" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-all shadow font-semibold">
                <?php echo $editModule ? 'Update' : 'Tambah'; ?>
            </button>
            <?php if ($editModule): ?>
                <a href="modul.php?course_id=<?php echo $course_id; ?>" 
                   class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition-all shadow">
                    Batal
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl shadow-lg border border-blue-100">
    <h3 class="text-lg font-bold text-blue-800 mb-4">Daftar Modul</h3>
    <?php if ($modulesResult->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full table-auto border border-blue-200 rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-blue-50">
                        <?php if ($course_id == 0): ?>
                            <th class="px-4 py-2 text-left text-sm font-bold text-blue-700">Mata Praktikum</th>
                        <?php endif; ?>
                        <th class="px-4 py-2 text-left text-sm font-bold text-blue-700">Urutan</th>
                        <th class="px-4 py-2 text-left text-sm font-bold text-blue-700">Judul</th>
                        <th class="px-4 py-2 text-left text-sm font-bold text-blue-700">Deskripsi</th>
                        <th class="px-4 py-2 text-center text-sm font-bold text-blue-700">Materi</th>
                        <th class="px-4 py-2 text-center text-sm font-bold text-blue-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-100">
                    <?php while ($module = $modulesResult->fetch_assoc()): ?>
                        <tr class="hover:bg-blue-50 transition">
                            <?php if ($course_id == 0): ?>
                                <td class="px-4 py-3 text-sm text-blue-700 font-semibold">
                                    <?php echo htmlspecialchars($module['nama_praktikum']); ?>
                                </td>
                            <?php endif; ?>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="bg-blue-200 text-blue-900 px-2 py-1 rounded-full font-bold shadow"><?php echo $module['urutan']; ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-blue-900">
                                <?php echo htmlspecialchars($module['judul']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <?php echo htmlspecialchars(substr($module['deskripsi'], 0, 100)); ?>
                                <?php if (strlen($module['deskripsi']) > 100) echo '...'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <?php if ($module['file_materi']): ?>
                                    <a href="../uploads/materi/<?php echo htmlspecialchars($module['file_materi']); ?>" 
                                       class="text-blue-600 hover:text-blue-800 underline font-semibold" download>
                                        Download
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <div class="flex justify-center space-x-2">
                                    <?php if ($course_id > 0): ?>
                                        <a href="modul.php?course_id=<?php echo $course_id; ?>&edit=<?php echo $module['id']; ?>" 
                                           class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition shadow">
                                            Edit
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus modul ini?')">
                                            <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                            <button type="submit" name="delete_module" 
                                                    class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition shadow">
                                                Hapus
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-8 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253z"></path>
            </svg>
            <p class="font-semibold">Belum ada modul</p>
            <p class="text-sm mt-2">Tambah modul baru menggunakan form di atas</p>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer.php';
?>
