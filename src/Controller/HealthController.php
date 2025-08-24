<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthController
{
    #[Route('/', name: 'app_health', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response('Habit Tracker backend is running (' . (new \DateTimeImmutable())->format('Y-m-d H:i:s') . ')');
    }
}
