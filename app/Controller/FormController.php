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
            ->pick(3);

        $this->render('results.twig', [
            'matchedCompanies'  => $matchedCompanies,
        ]);
    }
}