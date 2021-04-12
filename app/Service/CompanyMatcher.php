<?php

namespace App\Service;

use PDO;

class CompanyMatcher
{
    private PDO $db;
    private array $matches = [];
    private array $params = [];
    private bool $rand = false;
    private ?int $limit = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function match(array $input = [])
    {
        $params['postcodes'] = $this->getPostCodePrefix($input['postcode']);
        $params['bedrooms'] = (int)$input['bedrooms'] ?? null;
        $params['type'] = $input['type'] ?? null;

        // Set Parameters
        $this->params = $params;

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
     * Build query
     *
     */
    private function buildQuery(): array
    {
        $query = 'SELECT * FROM companies INNER JOIN company_matching_settings ON companies.id = company_matching_settings.company_id';

        // Only fetch active and companies have remaining credit
        $query .= ' WHERE active = 1 AND credits > 0';

        $params = array_filter($this->params);
        $queryParams = [];
        foreach ($params as $key => $value) {
            $query .= " AND $key LIKE :$key";
            $queryParams[":$key"] = "%$value%";
        }

        return [$query, $queryParams];
    }

    public function random(bool $rand = true): self
    {
        $this->rand = $rand;

        return $this;
    }

    public function pick(int $count): self
    {
        $this->limit = $count;

        return $this;
    }

    public function results(): array
    {
        if (!$this->matches) {
            $this->executeQuery();

            $this->deductCredits($this->matches);
        }

        return $this->matches;
    }

    private function executeQuery()
    {
        [$query, $queryParams] = $this->buildQuery();

        if ($this->rand) {
            $query .= ' ORDER BY RAND()';
        }

        if ($this->limit) {
            $query .= ' LIMIT :limit';
        }

        // Setup PDO Statement
        $pdoStatement = $this->db->prepare($query);

        // Blind Value
        foreach($queryParams as $param => $value) {
            $pdoStatement->bindValue($param, $value);
        }

        // Blind limit value
        if ($this->limit) {
            $pdoStatement->bindValue(':limit', $this->limit, PDO::PARAM_INT);
        }

        $pdoStatement->execute();

        $this->matches = $pdoStatement->fetchAll();
    }

    private function deductCredits(array $items)
    {
        $ids = array_column($items, 'company_id');

        if ($ids) {
            $this->db->query('UPDATE `companies` SET credits = credits - 1 WHERE id IN (' . implode(',', $ids) . ')');
        }

        $this->logOutOfCredit();
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
