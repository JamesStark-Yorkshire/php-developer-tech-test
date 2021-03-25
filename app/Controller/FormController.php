<?php

namespace App\Controller;

use App\Service\CompanyMatcher;

class FormController extends Controller
{
    public function index()
    {
        $this->render('form.twig');
    }

    public function submit(array $request)
    {
        $matcher = new CompanyMatcher($this->db());

        $matchedCompanies = $matcher->match($request)
            ->deductCredits()
            ->pick($_ENV['MAX_MATCHED_COMPANIES']);

        $this->render('results.twig', [
            'matchedCompanies'  => $matchedCompanies,
        ]);
    }
}