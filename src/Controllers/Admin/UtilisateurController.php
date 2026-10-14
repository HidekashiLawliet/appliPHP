<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Utilisateur;
use PDO;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administration: list of the employees.
 */
final class UtilisateurController extends Controller
{
    private Utilisateur $utilisateurs;

    public function __construct(?PDO $db = null)
    {
        parent::__construct($db);
        $this->utilisateurs = new Utilisateur($this->db);
    }

    /**
     * GET /admin/utilisateurs
     */
    public function index(): Response
    {
        $this->requireAdmin();

        return $this->render('admin/utilisateurs', ['utilisateurs' => $this->utilisateurs->all()]);
    }
}
