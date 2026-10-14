<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\HttpException;
use App\Models\Agence;
use App\Models\Trajet;
use App\Models\Utilisateur;
use App\Validators\TrajetValidator;
use DateTimeImmutable;
use PDO;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creation, edition and deletion of trips by their author.
 */
final class TrajetController extends Controller
{
    private const FIELDS = [
        'agence_depart_id',
        'agence_arrivee_id',
        'date_depart',
        'date_arrivee',
        'places_total',
        'places_disponibles',
    ];

    private Trajet $trajets;
    private Agence $agences;
    private Utilisateur $utilisateurs;

    public function __construct(?PDO $db = null)
    {
        parent::__construct($db);
        $this->trajets = new Trajet($this->db);
        $this->agences = new Agence($this->db);
        $this->utilisateurs = new Utilisateur($this->db);
    }

    /**
     * GET /trajets/create : empty form.
     */
    public function create(): Response
    {
        $this->requireLogin();

        return $this->form([
            'agence_depart_id' => '',
            'agence_arrivee_id' => '',
            'date_depart' => '',
            'date_arrivee' => '',
            'places_total' => '1',
            'places_disponibles' => '1',
        ]);
    }

    /**
     * POST /trajets : saves a new trip, then goes back to the list.
     */
    public function store(): Response
    {
        $user = $this->requireLogin();
        $this->verifyCsrf();

        $input = $this->inputs(self::FIELDS);
        $validator = $this->validator();
        if (!$validator->validate($input)) {
            return $this->form($input, null, $validator->errors());
        }

        $this->trajets->create($user['id'], $validator->data());

        return $this->redirectWithFlash('/', Flash::SUCCESS, 'Le trajet a été créé.');
    }

    /**
     * GET /trajets/{id}/edit : form filled with the trip of the author.
     */
    public function edit(int $id): Response
    {
        $trajet = $this->ownedTrajet($id);

        return $this->form([
            'agence_depart_id' => (string) $trajet['agence_depart_id'],
            'agence_arrivee_id' => (string) $trajet['agence_arrivee_id'],
            'date_depart' => $this->view->date($trajet['date_depart'], 'Y-m-d\TH:i'),
            'date_arrivee' => $this->view->date($trajet['date_arrivee'], 'Y-m-d\TH:i'),
            'places_total' => (string) $trajet['places_total'],
            'places_disponibles' => (string) $trajet['places_disponibles'],
        ], $id);
    }

    /**
     * POST /trajets/{id}/update : saves the changes of the author.
     */
    public function update(int $id): Response
    {
        $this->ownedTrajet($id);
        $this->verifyCsrf();

        $input = $this->inputs(self::FIELDS);
        $validator = $this->validator();
        if (!$validator->validate($input)) {
            return $this->form($input, $id, $validator->errors());
        }

        $this->trajets->update($id, $validator->data());

        return $this->redirectWithFlash('/', Flash::SUCCESS, 'Le trajet a été modifié.');
    }

    /**
     * POST /trajets/{id}/delete : deletes a trip of the author.
     */
    public function delete(int $id): Response
    {
        $user = $this->requireLogin();
        $this->verifyCsrf();

        if (!$this->trajets->deleteOwnedBy($id, $user['id'])) {
            return $this->redirectWithFlash('/', Flash::ERROR, 'Trajet introuvable ou vous n\'en êtes pas l\'auteur.');
        }

        return $this->redirectWithFlash('/', Flash::SUCCESS, 'Le trajet a été supprimé.');
    }

    /**
     * Returns the trip if the logged-in user is its author.
     *
     * @return array<string, mixed>
     * @throws HttpException 403 when the trip does not exist or belongs to someone else.
     */
    private function ownedTrajet(int $id): array
    {
        $user = $this->requireLogin();
        $trajet = $this->trajets->findOwnedBy($id, $user['id']);
        if ($trajet === null) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Vous ne pouvez modifier que vos propres trajets.');
        }

        return $trajet;
    }

    /**
     * Builds the validator with the current agencies and time.
     */
    private function validator(): TrajetValidator
    {
        return new TrajetValidator($this->agences->ids(), new DateTimeImmutable());
    }

    /**
     * Renders the create/edit form (HTTP 422 when there are errors).
     *
     * @param array<string, string> $values Values to put back in the fields.
     * @param int|null              $id     Edited trip, null for a creation.
     * @param list<string>          $errors Validation errors to display.
     */
    private function form(array $values, ?int $id = null, array $errors = []): Response
    {
        $user = $this->requireLogin();

        return $this->render('trajets/form', [
            'values' => $values,
            'trajetId' => $id,
            'errors' => $errors,
            'agences' => $this->agences->all(),
            'auteur' => $this->utilisateurs->find($user['id']) ?? [],
            'maxPlaces' => TrajetValidator::MAX_PLACES,
        ], $errors === [] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
