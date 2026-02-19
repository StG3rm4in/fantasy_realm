<?php

namespace App\Service;

/**
 * Service de logging vers MongoDB pour les actions de personnalisation.
 * Utilise le driver PHP natif mongodb/mongodb (PAS Doctrine ODM).
 */
class MongoLogService
{
    private ?\MongoDB\Client $client = null;
    private ?string $mongoUrl;
    private ?string $mongoDb;

    public function __construct(string $mongoUrl = 'mongodb://localhost:27017', string $mongoDb = 'fantasyrealm_logs')
    {
        $this->mongoUrl = $mongoUrl;
        $this->mongoDb = $mongoDb;
    }

    private function getCollection(): ?\MongoDB\Collection
    {
        // Si l'extension MongoDB n'est pas installée, on log silencieusement
        if (!class_exists(\MongoDB\Client::class)) {
            return null;
        }

        try {
            if ($this->client === null) {
                $this->client = new \MongoDB\Client($this->mongoUrl);
            }
            return $this->client->selectDatabase($this->mongoDb)->selectCollection('logs_personnage');
        } catch (\Exception $e) {
            // Silently fail - MongoDB might not be available in dev
            return null;
        }
    }

    /**
     * Vérifie si MongoDB est accessible (extension PHP + serveur joignable)
     */
    public function isAvailable(): bool
    {
        if (!class_exists(\MongoDB\Client::class)) {
            return false;
        }
        try {
            if ($this->client === null) {
                $this->client = new \MongoDB\Client($this->mongoUrl);
            }
            // Ping réel pour vérifier la connexion
            $this->client->selectDatabase($this->mongoDb)->command(['ping' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Log une action dans MongoDB
     */
    public function log(
        int $idUtilisateur,
        string $pseudo,
        int $idPersonnage,
        string $nomPersonnage,
        string $action,
        string $details
    ): void {
        $collection = $this->getCollection();
        if ($collection === null) {
            return;
        }

        try {
            $collection->insertOne([
                'id_utilisateur' => $idUtilisateur,
                'pseudo' => $pseudo,
                'id_personnage' => $idPersonnage,
                'nom_personnage' => $nomPersonnage,
                'action' => $action,
                'details' => $details,
                'date' => new \MongoDB\BSON\UTCDateTime(),
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    public function logCreation(int $idUtilisateur, string $pseudo, int $idPersonnage, string $nomPersonnage): void
    {
        $this->log($idUtilisateur, $pseudo, $idPersonnage, $nomPersonnage, 'creation', "Création du personnage '$nomPersonnage'");
    }

    public function logModification(int $idUtilisateur, string $pseudo, int $idPersonnage, string $nomPersonnage, string $details = ''): void
    {
        $this->log($idUtilisateur, $pseudo, $idPersonnage, $nomPersonnage, 'modification_apparence', $details ?: "Modification de l'apparence de '$nomPersonnage'");
    }

    public function logAjoutEquipement(int $idUtilisateur, string $pseudo, int $idPersonnage, string $nomPersonnage, string $nomArticle): void
    {
        $this->log($idUtilisateur, $pseudo, $idPersonnage, $nomPersonnage, 'ajout_equipement', "Ajout de l'équipement '$nomArticle' au personnage '$nomPersonnage'");
    }

    public function logSuppressionEquipement(int $idUtilisateur, string $pseudo, int $idPersonnage, string $nomPersonnage, string $nomArticle): void
    {
        $this->log($idUtilisateur, $pseudo, $idPersonnage, $nomPersonnage, 'suppression_equipement', "Retrait de l'équipement '$nomArticle' du personnage '$nomPersonnage'");
    }

    public function logPartage(int $idUtilisateur, string $pseudo, int $idPersonnage, string $nomPersonnage): void
    {
        $this->log($idUtilisateur, $pseudo, $idPersonnage, $nomPersonnage, 'partage', "Partage du personnage '$nomPersonnage'");
    }

    public function logArretPartage(int $idUtilisateur, string $pseudo, int $idPersonnage, string $nomPersonnage): void
    {
        $this->log($idUtilisateur, $pseudo, $idPersonnage, $nomPersonnage, 'arret_partage', "Arrêt du partage du personnage '$nomPersonnage'");
    }

    public function logSuppression(int $idUtilisateur, string $pseudo, int $idPersonnage, string $nomPersonnage): void
    {
        $this->log($idUtilisateur, $pseudo, $idPersonnage, $nomPersonnage, 'suppression', "Suppression du personnage '$nomPersonnage'");
    }

    public function logDuplication(int $idUtilisateur, string $pseudo, int $idPersonnage, string $nomPersonnage, string $nouveauNom): void
    {
        $this->log($idUtilisateur, $pseudo, $idPersonnage, $nomPersonnage, 'duplication', "Duplication du personnage '$nomPersonnage' vers '$nouveauNom'");
    }

    /**
     * Récupérer les logs avec filtres et pagination (pour l'admin)
     */
    public function findLogs(
        ?string $pseudo = null,
        ?int $idPersonnage = null,
        ?string $action = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $collection = $this->getCollection();
        if ($collection === null) {
            return [];
        }

        try {
            $filter = [];

            if ($pseudo) {
                $filter['pseudo'] = new \MongoDB\BSON\Regex($pseudo, 'i');
            }

            if ($idPersonnage) {
                $filter['id_personnage'] = $idPersonnage;
            }

            if ($action) {
                $filter['action'] = $action;
            }

            if ($dateDebut || $dateFin) {
                $dateFilter = [];
                if ($dateDebut) {
                    $dateFilter['$gte'] = new \MongoDB\BSON\UTCDateTime($dateDebut);
                }
                if ($dateFin) {
                    $dateFilter['$lte'] = new \MongoDB\BSON\UTCDateTime($dateFin);
                }
                $filter['date'] = $dateFilter;
            }

            $options = [
                'sort' => ['date' => -1],
                'limit' => $limit,
                'skip' => $offset,
            ];

            $cursor = $collection->find($filter, $options);
            $results = [];
            foreach ($cursor as $doc) {
                $results[] = [
                    'id' => (string) $doc['_id'],
                    'id_utilisateur' => $doc['id_utilisateur'],
                    'pseudo' => $doc['pseudo'],
                    'id_personnage' => $doc['id_personnage'],
                    'nom_personnage' => $doc['nom_personnage'],
                    'action' => $doc['action'],
                    'details' => $doc['details'],
                    'date' => $doc['date']->toDateTime()->format('d/m/Y H:i:s'),
                ];
            }

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Compter les logs avec filtres
     */
    public function countLogs(
        ?string $pseudo = null,
        ?int $idPersonnage = null,
        ?string $action = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null
    ): int {
        $collection = $this->getCollection();
        if ($collection === null) {
            return 0;
        }

        try {
            $filter = [];
            if ($pseudo) {
                $filter['pseudo'] = new \MongoDB\BSON\Regex($pseudo, 'i');
            }
            if ($idPersonnage) {
                $filter['id_personnage'] = $idPersonnage;
            }
            if ($action) {
                $filter['action'] = $action;
            }
            if ($dateDebut || $dateFin) {
                $dateFilter = [];
                if ($dateDebut) {
                    $dateFilter['$gte'] = new \MongoDB\BSON\UTCDateTime($dateDebut);
                }
                if ($dateFin) {
                    $dateFilter['$lte'] = new \MongoDB\BSON\UTCDateTime($dateFin);
                }
                $filter['date'] = $dateFilter;
            }

            return $collection->countDocuments($filter);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Récupérer les actions distinctes
     */
    public function getDistinctActions(): array
    {
        $collection = $this->getCollection();
        if ($collection === null) {
            return ['creation', 'modification_apparence', 'ajout_equipement', 'suppression_equipement', 'partage', 'arret_partage', 'suppression', 'duplication'];
        }

        try {
            return $collection->distinct('action');
        } catch (\Exception $e) {
            return [];
        }
    }
}
