<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Agence;
use App\Validators\AgenceValidator;
use PDO;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administration: create, rename and delete agencies.
*/
final class AgenceController extends Controller
{
    private const LIST_URL = '/admin/agences';

    private Agence $agences;
    private AgenceValidator $validator;

    public function __construct(?PDO $db = null)
    {
        parent::__construct($db);
        $this->agences = new Agence($this->db);
        $this->validator = new AgenceValidator();
    }

    /**
     * GET /admin/agences
     */
    public function index(): Response
    {
        $this->requireAdmin();

        return $this->render('admin/agences', ['agences' => $this->agences->all()]);
    }

    /**
     * POST /admin/agences : creates an agency.
     */
    public function store(): Response
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $nom = $this->input('nom');
        $error = $this->firstError($nom, null);
        if ($error !== null) {
            return $this->redirectWithFlash(self::LIST_URL, Flash::ERROR, $error);
        }

        $this->agences->create($nom);

        return $this->redirectWithFlash(self::LIST_URL, Flash::SUCCESS, sprintf("L'agence \"%s\" a été créée.", $nom));
    }

    /**
     * POST /admin/agences/{id}/update : renames an agency.
     */
    public function update(int $id): Response
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        if (!$this->agences->exists($id)) {
            return $this->redirectWithFlash(self::LIST_URL, Flash::ERROR, 'Agence introuvable.');
        }

        $nom = $this->input('nom');
        $error = $this->firstError($nom, $id);
        if ($error !== null) {
            return $this->redirectWithFlash(self::LIST_URL, Flash::ERROR, $error);
        }

        $this->agences->rename($id, $nom);

        return $this->redirectWithFlash(self::LIST_URL, Flash::SUCCESS, "L'agence a été renommée.");
    }

    /**
     * POST /admin/agences/{id}/delete : deletes an agency no trip uses.
    */
    public function delete(int $id): Response
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        if (!$this->agences->exists($id)) {
            return $this->redirectWithFlash(self::LIST_URL, Flash::ERROR, 'Agence introuvable.');
        }
        if ($this->agences->isUsed($id)) {
            return $this->redirectWithFlash(
                self::LIST_URL,
                Flash::ERROR,
                'Cette agence est utilisée par au moins un trajet et ne peut pas être supprimée.'
            );
        }

        $this->agences->delete($id);

        return $this->redirectWithFlash(self::LIST_URL, Flash::SUCCESS, "L'agence a été supprimée.");
    }

    /**
     * Returns the first validation error for this name, or null.
     *
     * @param int|null $exceptId Agency being renamed (ignored by the uniqueness check).
     */
    private function firstError(string $nom, ?int $exceptId): ?string
    {
        $errors = $this->validator->validate($nom);
        if ($errors !== []) {
            return $errors[0];
        }
        if ($this->agences->findByNom($nom, $exceptId) !== null) {
            return 'Une agence porte déjà ce nom.';
        }

        return null;
    }
}
