<?php
system('clear');

// Fungsi untuk melakukan permintaan cURL
function curl($url, $method = 'POST', $headers = [], $body = '')
{
    $ch = curl_init();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
    ];

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $err = curl_error($ch);

    curl_close($ch);

    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        return $response;
    }
}

// Ambil input msisdn dari pengguna
echo "Input MSISDN: ";
$msisdn = trim(fgets(STDIN));

// Validasi msisdn
if (empty($msisdn)) {
    die("MSISDN tidak boleh kosong.");
}

// Ubah nomor jika dimulai dengan '0' menjadi format internasional
if (substr($msisdn, 0, 1) === '0') {
    $msisdn = '62' . substr($msisdn, 1);
}

// URL API
$url = 'http://token.virtualtunneling.tech:8880/req-otp.php';

// Data yang akan dikirimkan
$data = [
    'msisdn' => $msisdn,
];

// Mengatur headers
$headers = [
    'Content-Type: application/json',
];

// Menginisialisasi cURL
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
]);

// Menjalankan cURL dan mendapatkan respons
$response = curl_exec($ch);

// Mengecek kesalahan cURL
if ($response === false) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    $response_data = json_decode($response, true);

    if (isset($response_data['msisdn'])) {
        // Mengambil msisdn dan menyimpan ke file otp.conf
        $msisdn_to_save = $response_data['msisdn'];
        file_put_contents('otp.conf', $msisdn_to_save);
        echo "MSISDN berhasil disimpan di otp.conf\n";

        // Waktu sekarang dalam UNIX timestamp
        $current_time = time();

        // Waktu reset adalah 1 jam setelah waktu sekarang
        $reset_time = $current_time + 3600; // 3600 detik = 1 jam

        // Data untuk disimpan dalam format JSON
        $data_to_save = [
            'msisdn' => $msisdn_to_save,
            'reset_time' => $reset_time
        ];

        // Nama file untuk menyimpan data
        $nama_file = 'nomor.json';
        file_put_contents($nama_file, json_encode($data_to_save));
        echo "Data nomor dan waktu reset berhasil disimpan di $nama_file\n";
    } else {
        echo "MSISDN tidak ditemukan dalam response API.\n";
    }
}

// Menutup cURL
curl_close($ch);

// Baca msisdn dari file
$msisdn_file = 'otp.conf';
if (file_exists($msisdn_file)) {
    $msisdn = trim(file_get_contents($msisdn_file));
} else {
    echo json_encode(['error' => 'File otp.conf tidak ditemukan']);
    exit;
}

// Input OTP secara manual
echo "\n\nINPUT OTP : ";
$otp = trim(fgets(STDIN));

// Kirim permintaan ke API lokal
$api_url = 'http://token.virtualtunneling.tech:8880/input-otp.php'; // Ganti dengan URL API lokal Anda

$response = curl(
    $api_url,
    'POST',
    [
        'Content-Type: application/json',
    ],
    json_encode([
        'msisdn' => $msisdn,
        'otp' => $otp
    ])
);

// Output respons
echo "Response:\n";
echo $response;
file_put_contents('response.json', $response);