<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Trajet;
use DateTimeImmutable;
use PDO;
use Symfony\Component\HttpFoundation\Response;

/**
 * Home page: list of the upcoming trips that still have free seats.
 */
final class HomeController extends Controller
{
    private Trajet $trajets;

    public function __construct(?PDO $db = null)
    {
        parent::__construct($db);
        $this->trajets = new Trajet($this->db);
    }

    /**
     * GET / : displays the available trips.
     */
    public function index(): Response
    {
        return $this->render('home/index', [
            'trajets' => $this->trajets->findUpcomingAvailable(new DateTimeImmutable()),
        ]);
    }
}
