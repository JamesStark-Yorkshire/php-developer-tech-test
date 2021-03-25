<?php

namespace App\Service;

class CompanyMatcher
{
    private $db;
    private $matches = [];

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function match(array $input = [])
    {
        $params['postcodes'] = $this->getPostCodePrefix($input['postcode']);
        $params['bedrooms'] = (int)$input['bedrooms'] ?? null;
        $params['type'] = $input['type'] ?? null;

        $this->matches = $this->db
            ->query($this->buildQuery($params))
            ->fetchAll();

        return $this;
    }

    public function pick(int $count)
    {
        $result = [];

        if ($this->matches) {
            $ids = array_rand($this->matches, $count);

            foreach ($ids as $id) {
                $result[] = $this->matches[$id];
            }
        }

        return $result;
    }

    public function results(): array
    {
        return $this->matches;
    }

    public function deductCredits()
    {
        $ids = array_column($this->matches, 'id');

        if ($ids) {
            $this->db
                ->query('UPDATE `companies` SET credits = credits - 1 WHERE id IN (' . implode(',', $ids) . ')')
                ->execute();
        }

        return $this;
    }

    private function getPostCodePrefix(string $postcode): ?string
    {
        preg_match('/[A-Z]{1,2}(?=\d)/', $postcode, $matches);

        return $matches[0] ?? null;
    }

    private function buildQuery(array $params = []): string
    {
        $query = 'SELECT * FROM companies LEFT JOIN company_matching_settings USING(id)';

        $params = array_filter($params);

        $count = 0;
        foreach ($params as $key => $value) {
            if ($count == 0) {
                $query .= ' WHERE';
            } else {
                $query .= ' AND';
            }
            $query .= " $key LIKE '%$value%'";
            $count++;
        }

        return $query;
    }
}
