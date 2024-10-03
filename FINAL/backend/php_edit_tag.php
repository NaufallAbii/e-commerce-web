<?php
session_start();
require 'config.php';

// Periksa apakah pengguna sudah login, jika tidak arahkan ke halaman login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Inisialisasi variabel pesan kesalahan
$error_message = "";

// Inisialisasi variabel tag
$tag = ['tag_id' => '', 'name' => ''];

// Periksa apakah ID dari tag yang akan diedit disediakan
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['tag_id'])) {
    $id = $_GET['tag_id'];

    // Ambil data tag dari database
    $sql = "SELECT * FROM tags WHERE tag_id = ?";
    if ($stmt = $conection_db->prepare($sql)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $tag = $result->fetch_assoc();
        } else {
            echo "Tag tidak ditemukan.";
            exit;
        }
    }
}

// Proses pengiriman formulir edit tag
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["tag_id"];
    $name = $_POST["name"];
    $slug = slugify($name); // secara otomatis menghasilkan slug dari nama

    // Periksa apakah ada tag lain dengan nama yang sama
    $sql_check = "SELECT tag_id FROM tags WHERE name = ? AND tag_id != ?";
    if ($stmt_check = $conection_db->prepare($sql_check)) {
        $stmt_check->bind_param("si", $name, $id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_message = "Nama tag ini sudah digunakan.";
        } else {
            // Update tag di database
            $sql_update = "UPDATE tags SET name = ?, slug = ? WHERE tag_id = ?";
            if ($stmt_update = $conection_db->prepare($sql_update)) {
                $stmt_update->bind_param("ssi", $name, $slug, $id);
                if ($stmt_update->execute()) {
                    header("location: tag.php");
                    exit;
                } else {
                    $error_message = "Terjadi kesalahan. Silakan coba lagi.";
                }
            }
        }
    }

    // Pastikan variabel $tag diatur dengan benar ketika terjadi kesalahan
    $tag['tag_id'] = $id;
    $tag['name'] = $name;
}

// Fungsi untuk membuat slug dari string
function slugify($string)
{
    $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
    $slug = trim($slug, '-');
    return strtolower($slug);
}
