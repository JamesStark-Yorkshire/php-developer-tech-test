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
            ->random()
            ->pick($_ENV['MAX_MATCHED_COMPANIES'])
            ->results();

        $this->render('results.twig', [
            'matchedCompanies'  => $matchedCompanies,
        ]);
    }
}