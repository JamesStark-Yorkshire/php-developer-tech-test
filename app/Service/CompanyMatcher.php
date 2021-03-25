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
            // Get possible match count
            if(($size = count($this->matches)) <= $count) {
                $count = $size;
            }

            // Get specific number of item and make sure it is in an array
            $ids = (array) array_rand($this->matches, $count);

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
            $this->db->query('UPDATE `companies` SET credits = credits - 1 WHERE id IN (' . implode(',', $ids) . ')');
        }

        $this->logOutOfCredit();

        return $this;
    }

    /**
     * Get prefix of the postcode
     *
     * @param string $postcode
     * @return string|null
     */
    private function getPostCodePrefix(string $postcode): ?string
    {
        preg_match('/[A-Z]{1,2}(?=\d)/', $postcode, $matches);

        return $matches[0] ?? null;
    }

    /**
     * Build filtering query
     *
     * @param array $params
     * @return string
     */
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

    /**
     * Log companies that run out of credits
     */
    private function logOutOfCredit()
    {
        foreach ($this->matches as $match) {
            if ($match['credits'] < 0) {
                Log::alert('Company: ' . $match['id'] . ' - ' . $match['name'] . ' is out of credits.', ['credits' => $match['credits']]);
            }
        }
    }
}
