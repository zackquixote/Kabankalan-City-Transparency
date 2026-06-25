<?php
// Bootstrap CodeIgniter 4
define('FCPATH', __DIR__ . '/public/');
$systemPath = __DIR__ . '/vendor/codeigniter4/framework/system';
require $systemPath . '/bootstrap.php';

$userModel = new \App\Models\UserModel();
// We need to connect to tests database if we want to see test data, or default db.
// Let's connect to default database first.
$user = $userModel->where('email', 'admin@kabankalan.gov.ph')->first();
echo "--- Default Database User ---\n";
var_dump($user);
if ($user) {
    echo "Password check: ";
    var_dump($userModel->validatePassword('Kabankalan2026!', $user));
}

// Let's also connect to tests database.
echo "\n--- Tests Database User ---\n";
$db = \Config\Database::connect('tests');
// Migrate and seed tests database
$migrate = \Config\Services::migrations();
$migrate->setNamespace('App');
$migrate->latest();
$seeder = \Config\Database::seeder();
$seeder->call('App\Database\Seeds\DatabaseSeeder');

$userModelTest = new \App\Models\UserModel($db);
$userTest = $userModelTest->where('email', 'admin@kabankalan.gov.ph')->first();
var_dump($userTest);
if ($userTest) {
    echo "Password check: ";
    var_dump($userModelTest->validatePassword('Kabankalan2026!', $userTest));
}
