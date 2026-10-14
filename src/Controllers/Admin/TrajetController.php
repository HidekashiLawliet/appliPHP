<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Trajet;
use PDO;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administration: list and delete any trip.
 */
final class TrajetController extends Controller
{
    private const LIST_URL = '/admin/trajets';

    private Trajet $trajets;

    public function __construct(?PDO $db = null)
    {
        parent::__construct($db);
        $this->trajets = new Trajet($this->db);
    }

    /**
     * GET /admin/trajets
     */
    public function index(): Response
    {
        $this->requireAdmin();

        return $this->render('admin/trajets', ['trajets' => $this->trajets->findAllWithDetails()]);
    }

    /**
     * POST /admin/trajets/{id}/delete
     */
    public function delete(int $id): Response
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        if (!$this->trajets->delete($id)) {
            return $this->redirectWithFlash(self::LIST_URL, Flash::ERROR, 'Trajet introuvable.');
        }

        return $this->redirectWithFlash(self::LIST_URL, Flash::SUCCESS, 'Le trajet a été supprimé.');
    }
}
