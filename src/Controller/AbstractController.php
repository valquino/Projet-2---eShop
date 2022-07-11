<?php

namespace App\Controller;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Extra\Intl\IntlExtension;
use App\Model\LoginLogoutManager;

/**
 * Initialized some Controller common features (Twig...)
 */
abstract class AbstractController
{
    protected Environment $twig;
    protected array $loginErrors = [];


    public function __construct()
    {
        $loader = new FilesystemLoader(APP_VIEW_PATH);
        $this->twig = new Environment(
            $loader,
            [
                'cache' => false,
                'debug' => (ENV === 'dev'),
            ]
        );
        $this->twig->addExtension(new DebugExtension());
        $this->twig->addExtension(new IntlExtension());
        $this->twig->addGlobal('session', $_SESSION);
        $this->loginErrors = $this->login();
    }

    /**
     ** Login function
     */
    public function login(): string|array|null
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // clean $_POST data
            $post = array_map('trim', $_POST);
            // Erros check
            $this->validateLogin($post);
            if (empty($this->loginErrors)) {
                $email = $post['email'];
                $password = md5($post['password']);
                // So we are checking if the user exist in the database
                $loginLogoutManager = new LoginLogoutManager();
                $user = $loginLogoutManager->selectOneUser($email, $password);
                if ($user !== false) {
                    // Creation of a session variable
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname']
                    ];
                    header('Location: /');
                } else {
                    $this->loginErrors[] = "Vos données de connexion ne correspondent à aucun utilisateur enregistré";
                }
            }
        }
        return $this->loginErrors;
    }

    /**
     * Form check before login the user
     */
    public function validateLogin($post): array
    {
        // is the email format correct?
        if (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
            $this->loginErrors[] = 'Veuillez saisir une adresse email valide';
        }
        // password check
        if (!isset($post['password']) || empty($post['password'])) {
            $this->loginErrors[] = 'Veuillez renseigner votre mot de passe';
        }
        return $this->loginErrors;
    }

    /**
     * Logout function
     */
    public function logout(): void
    {
        // We destroy the session variable
        session_unset();

        // We destroy the session
        session_destroy();

        header('Location: /?logout');
    }
}
