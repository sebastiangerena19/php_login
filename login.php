<?php
header("Content-Type: application/json");

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'sistema_usuarios';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la conexión a la base de datos"]);
    exit;
}

// Captura el cuerpo de la solicitud y convierte el JSON en un array PHP
$request = json_decode(file_get_contents("php://input"), true);

// Verifica el tipo de operación solicitado (registro o inicio de sesión)
if (isset($request["accion"])) {
    $accion = $request["accion"];

    if ($accion == "registro") {
        registro($request, $pdo);
    } elseif ($accion == "login") {
        login($request, $pdo);
    } else {
        echo json_encode(["error" => "Acción no válida"]);
    }
} else {
    echo json_encode(["error" => "Solicitud no válida"]);
}

// Función para registrar un nuevo usuario
function registro($request, $pdo) {
    if (empty($request["username"]) || empty($request["password"])) {
        echo json_encode(["error" => "Usuario y contraseña son obligatorios"]);
        return;
    }

    $username = $request["username"];
    $password = password_hash($request["password"], PASSWORD_BCRYPT); // Encriptamos la contraseña

    // Inserta el nuevo usuario en la base de datos
    $query = "INSERT INTO usuarios (username, password) VALUES (:username, :password)";
    $stmt = $pdo->prepare($query);

    try {
        $stmt->execute(["username" => $username, "password" => $password]);
        echo json_encode(["mensaje" => "Usuario registrado exitosamente"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error al registrar usuario: " . $e->getMessage()]);
    }
}

// Función para el inicio de sesión
function login($request, $pdo) {
    if (empty($request["username"]) || empty($request["password"])) {
        echo json_encode(["error" => "Usuario y contraseña son obligatorios"]);
        return;
    }

    $username = $request["username"];
    $password = $request["password"];

    // Selecciona el usuario de la base de datos
    $query = "SELECT password FROM usuarios WHERE username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->execute(["username" => $username]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario["password"])) {
        echo json_encode(["mensaje" => "Autenticación satisfactoria"]);
    } else {
        echo json_encode(["error" => "Error en la autenticación"]);
    }
}
?>
