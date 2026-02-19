<!DOCTYPE html>
<html>
<head>
    <title>Test CSRF Token</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        h1 { color: #333; }
        h2 { color: #555; font-size: 1.2em; }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        button:hover { background: #0056b3; }
        .warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
    </style>
</head>
<body>
    <h1>🔍 Test de Diagnostic CSRF Token</h1>

    <?php
    require __DIR__.'/vendor/autoload.php';

    use Symfony\Component\Dotenv\Dotenv;

    $dotenv = new Dotenv();
    $dotenv->load(__DIR__.'/.env');

    $errors = [];
    $warnings = [];
    $successes = [];

    // Test 1: APP_SECRET
    echo '<div class="test-box">';
    echo '<h2>1️⃣ Vérification APP_SECRET</h2>';
    $appSecret = $_ENV['APP_SECRET'] ?? '';
    if (empty($appSecret)) {
        echo '<p class="error">❌ <strong>APP_SECRET est vide !</strong></p>';
        echo '<p>Solution : Ajoutez dans votre fichier <code>.env</code> :</p>';
        echo '<pre>APP_SECRET=' . bin2hex(random_bytes(32)) . '</pre>';
        $errors[] = 'APP_SECRET vide';
    } else {
        echo '<p class="success">✅ APP_SECRET est défini (longueur: ' . strlen($appSecret) . ' caractères)</p>';
        if (strlen($appSecret) < 32) {
            echo '<p class="warning">⚠️ APP_SECRET est trop court (minimum recommandé: 32 caractères)</p>';
            $warnings[] = 'APP_SECRET trop court';
        }
        $successes[] = 'APP_SECRET configuré';
    }
    echo '</div>';

    // Test 2: Sessions PHP
    echo '<div class="test-box">';
    echo '<h2>2️⃣ Test des sessions PHP</h2>';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        echo '<p class="success">✅ Les sessions PHP fonctionnent</p>';
        echo '<p><strong>Session ID:</strong> <code>' . session_id() . '</code></p>';

        $_SESSION['csrf_test'] = 'OK';
        if (isset($_SESSION['csrf_test']) && $_SESSION['csrf_test'] === 'OK') {
            echo '<p class="success">✅ Écriture/Lecture de session fonctionne</p>';
            $successes[] = 'Sessions PHP opérationnelles';
        } else {
            echo '<p class="error">❌ Impossible d\'écrire dans la session</p>';
            $errors[] = 'Sessions non fonctionnelles';
        }

        $sessionPath = session_save_path();
        if (empty($sessionPath)) {
            $sessionPath = sys_get_temp_dir();
        }
        echo '<p><strong>Chemin de stockage:</strong> <code>' . htmlspecialchars($sessionPath) . '</code></p>';

        if (is_writable($sessionPath)) {
            echo '<p class="success">✅ Répertoire accessible en écriture</p>';
        } else {
            echo '<p class="error">❌ Répertoire NON accessible en écriture</p>';
            $errors[] = 'Répertoire sessions non accessible';
        }
    } else {
        echo '<p class="error">❌ Impossible de démarrer une session PHP</p>';
        $errors[] = 'Sessions PHP désactivées';
    }
    echo '</div>';

    // Test 3: Symfony Kernel
    echo '<div class="test-box">';
    echo '<h2>3️⃣ Test du Kernel Symfony</h2>';
    try {
        $kernel = new App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
        $kernel->boot();
        echo '<p class="success">✅ Kernel Symfony chargé avec succès</p>';
        $successes[] = 'Kernel Symfony opérationnel';
    } catch (\Exception $e) {
        echo '<p class="error">❌ Erreur de chargement du Kernel: <code>' . htmlspecialchars($e->getMessage()) . '</code></p>';
        $errors[] = 'Kernel non fonctionnel';
    }
    echo '</div>';

    // Test 4: CSRF Token Manager
    echo '<div class="test-box">';
    echo '<h2>4️⃣ Test du CSRF Token Manager</h2>';
    try {
        $container = $kernel->getContainer();

        if ($container->has('security.csrf.token_manager')) {
            $csrfManager = $container->get('security.csrf.token_manager');
            echo '<p class="success">✅ CSRF Token Manager disponible</p>';

            // Génération de token
            $token = $csrfManager->getToken('authenticate');
            $tokenValue = $token->getValue();

            echo '<p class="success">✅ Token CSRF généré avec succès</p>';
            echo '<p><strong>Token:</strong> <code>' . substr($tokenValue, 0, 30) . '...</code> (longueur: ' . strlen($tokenValue) . ')</p>';

            // Test de validation
            $isValid = $csrfManager->isTokenValid($token);
            if ($isValid) {
                echo '<p class="success">✅ Validation du token réussie</p>';
                $successes[] = 'CSRF fonctionnel';
            } else {
                echo '<p class="error">❌ Validation du token échouée</p>';
                $errors[] = 'CSRF validation failed';
            }

            // Test avec un nouveau token
            $newToken = $csrfManager->getToken('authenticate');
            $areSame = ($token->getValue() === $newToken->getValue());

            if ($areSame) {
                echo '<p class="success">✅ Les tokens sont cohérents (même valeur)</p>';
            } else {
                echo '<p class="warning">⚠️ Les tokens changent à chaque génération</p>';
                echo '<p class="info">Ceci peut être normal selon votre configuration</p>';
            }

        } else {
            echo '<p class="error">❌ CSRF Token Manager NON disponible</p>';
            echo '<p>Vérifiez que <code>csrf_protection</code> est activé dans <code>config/packages/framework.yaml</code></p>';
            $errors[] = 'CSRF Manager indisponible';
        }
    } catch (\Exception $e) {
        echo '<p class="error">❌ Erreur: <code>' . htmlspecialchars($e->getMessage()) . '</code></p>';
        $errors[] = 'Erreur CSRF: ' . $e->getMessage();
    }
    echo '</div>';

    // Test 5: Cookies
    echo '<div class="test-box">';
    echo '<h2>5️⃣ Test des Cookies</h2>';

    // Définir un cookie de test
    setcookie('csrf_test_cookie', 'test_value', time() + 3600, '/');

    if (isset($_COOKIE['csrf_test_cookie'])) {
        echo '<p class="success">✅ Les cookies fonctionnent</p>';
        $successes[] = 'Cookies opérationnels';
    } else {
        echo '<p class="warning">⚠️ Cookie de test non trouvé</p>';
        echo '<p class="info">Note: Le cookie sera disponible au prochain chargement de page</p>';
    }

    // Vérifier si PHPSESSID est présent
    if (isset($_COOKIE['PHPSESSID'])) {
        echo '<p class="success">✅ Cookie PHPSESSID présent: <code>' . htmlspecialchars($_COOKIE['PHPSESSID']) . '</code></p>';
    } else {
        echo '<p class="error">❌ Cookie PHPSESSID absent - Les sessions ne fonctionneront pas !</p>';
        $errors[] = 'PHPSESSID cookie absent';
    }

    echo '</div>';

    // Résumé final
    echo '<div class="test-box">';
    echo '<h2>📊 Résumé</h2>';

    echo '<p><strong>✅ Succès:</strong> ' . count($successes) . '</p>';
    echo '<p><strong>⚠️  Avertissements:</strong> ' . count($warnings) . '</p>';
    echo '<p><strong>❌ Erreurs:</strong> ' . count($errors) . '</p>';

    if (empty($errors)) {
        echo '<div class="success">';
        echo '<h3>🎉 Tous les tests sont passés !</h3>';
        echo '<p><strong>Le système CSRF devrait fonctionner correctement.</strong></p>';
        echo '<p>Si vous avez toujours l\'erreur "Invalid CSRF Token", le problème vient probablement de votre navigateur :</p>';
        echo '<ul>';
        echo '<li>Supprimez TOUS les cookies du site (F12 → Application → Cookies → Delete All)</li>';
        echo '<li>Videz le cache (Ctrl+Shift+Delete)</li>';
        echo '<li>Fermez complètement le navigateur</li>';
        echo '<li>Rouvrez et réessayez en navigation privée (Ctrl+Shift+N)</li>';
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<h3>⚠️ Problèmes détectés</h3>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '<p><strong>Corrigez ces problèmes avant de continuer.</strong></p>';
        echo '</div>';
    }

    echo '</div>';

    // Boutons d'action
    echo '<div class="test-box info">';
    echo '<h2>🔄 Actions rapides</h2>';
    echo '<button onclick="location.reload()">🔄 Recharger cette page</button>';
    echo '<button onclick="location.href=\'/auth/login\'">🔑 Aller à la page de login</button>';
    echo '<button onclick="clearAllData()">🗑️ Nettoyer cookies & cache</button>';
    echo '</div>';
    ?>

    <script>
    function clearAllData() {
        // Supprimer tous les cookies
        document.cookie.split(";").forEach(function(c) {
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });

        // Vider le localStorage
        localStorage.clear();

        // Vider le sessionStorage
        sessionStorage.clear();

        alert('✅ Cookies et stockage local nettoyés !\n\nFermez COMPLÈTEMENT le navigateur et rouvrez-le pour que les changements prennent effet.');
    }
    </script>
</body>
</html>

