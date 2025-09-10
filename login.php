<!-- Archivo 1: login.html (El Frontend con JavaScript) -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login con Firebase</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f9; }
        .login-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 350px; }
        form { display: flex; flex-direction: column; gap: 1rem; }
        input { padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 0.8rem; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        #mensaje { margin-top: 1rem; text-align: center; }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form id="loginForm">
            <input type="email" id="email" placeholder="Correo electrónico" required>
            <input type="password" id="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
        <div id="mensaje"></div>
    </div>

    <!-- SDK de Firebase para el cliente -->
    <script src="https://www.gstatic.com/firebasejs/8.6.8/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.6.8/firebase-auth.js"></script>

    <script>
        // --- CONFIGURACIÓN DE FIREBASE PARA EL CLIENTE ---
        // Reemplaza esto con la configuración de tu propio proyecto de Firebase
        // La encuentras en Configuración del proyecto > General > Tus apps > Configuración del SDK
        const firebaseConfig = {
            apiKey: "AIzaSyC_XFqGgRm33hReE_xKojw-jwWhV9oovZ4",
            authDomain: "uaa-clase.firebaseapp.com",
            projectId: "uaa-clase",
            storageBucket: "uaa-clase.firebasestorage.app",
            messagingSenderId: "347082718678",
            appId: "1:347082718678:web:db94d57f1555347dabb877",
            measurementId: "G-MTBJL2NLXS"
        };

        // Inicializar Firebase
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        // --- LÓGICA DEL LOGIN ---
        const loginForm = document.querySelector('#loginForm');
        const mensajeDiv = document.querySelector('#mensaje');

        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const email = loginForm.email.value;
            const password = loginForm.password.value;

            // 1. El Frontend habla con Firebase para hacer el login
            auth.signInWithEmailAndPassword(email, password)
                .then(userCredential => {
                    // Si el login es exitoso, Firebase nos devuelve las credenciales del usuario
                    const user = userCredential.user;
                    mensajeDiv.innerHTML = `<p style="color:green;">Login exitoso! Verificando en el servidor...</p>`;
                    
                    // 2. Le pedimos a Firebase la "tarjeta de acceso" (ID Token)
                    return user.getIdToken();
                })
                .then(idToken => {
                    // 3. Enviamos el token a nuestro backend PHP para que lo verifique
                    return fetch('verificar_token.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ token: idToken })
                    });
                })
                .then(response => response.json())
                .then(data => {
                    // 5. Mostramos la respuesta de nuestro servidor PHP
                    if (data.status === 'success') {
                         mensajeDiv.innerHTML = `<p style="color:green;">Servidor dice: ¡Bienvenido! Tu UID es ${data.uid}</p>`;
                         // Aquí podrías redirigir a una página protegida, etc.
                    } else {
                         mensajeDiv.innerHTML = `<p style="color:red;">Servidor dice: ${data.message}</p>`;
                    }
                })
                .catch(error => {
                    // Manejar errores (ej: contraseña incorrecta)
                    console.error("Error de autenticación:", error);
                    mensajeDiv.innerHTML = `<p style="color:red;">Error: ${error.message}</p>`;
                });
        });
    </script>

</body>
</html>

---
<!-- Archivo 2: verificar_token.php (El Backend con PHP) -->

<?php

// Incluimos el autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

header('Content-Type: application/json');

// El guardia (PHP) espera recibir la "tarjeta de acceso" (token)
$input = json_decode(file_get_contents('php://input'), true);
$idToken = $input['token'] ?? '';

if (empty($idToken)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Token no proporcionado.']);
    exit();
}

// Inicializamos la fábrica de Firebase con el archivo de la cuenta de servicio
$factory = (new Factory)->withServiceAccount('google-service-account.json');
$auth = $factory->createAuth();

try {
    // Le pedimos al SDK que verifique la firma y la expiración del token
    $verifiedIdToken = $auth->verifyIdToken($idToken);

    // Si la verificación es exitosa, el token es válido.
    $uid = $verifiedIdToken->claims()->get('sub');
    $email = $verifiedIdToken->claims()->get('email');

    // Ahora que sabemos quién es el usuario (por su UID), podríamos:
    // - Iniciar una sesión de PHP ($_SESSION['user_uid'] = $uid;)
    // - Consultar nuestra propia base de datos para obtener más datos de este usuario
    // - Permitirle el acceso a una sección protegida

    // Devolvemos una respuesta de éxito al frontend
    echo json_encode([
        'status' => 'success', 
        'message' => 'Token verificado correctamente.',
        'uid' => $uid,
        'email' => $email
    ]);

} catch (\Kreait\Firebase\Exception\Auth\IdTokenVerificationFailed $e) {
    // El token es inválido (expirado, malformado, etc.)
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Token inválido: ' . $e->getMessage()]);
} catch (\Throwable $e) {
    // Cualquier otro error
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor.']);
}

?>
