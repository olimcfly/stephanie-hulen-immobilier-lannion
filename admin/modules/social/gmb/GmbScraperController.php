<?php
/**
 * GmbScraperController.php
 * Contrôleur principal du module GMB Scraper
 * 
 * Fonctionnalités :
 * - Recherche via Google Places API (Text Search + Place Details)
 * - Extraction contacts (téléphone, email depuis site web)
 * - Validation email (MX + SMTP check)
 * - Enrichissement des fiches contacts
 * 
 * @package EcosystemeImmo
 * @author  Olivier / Claude
 */

class GmbScraperController
{
    private $db;
    private $apiKey;
    private $settings = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->loadSettings();
        $this->apiKey = $this->settings['google_places_api_key'] ?? '';
    }

    // ─────────────────────────────────────────────
    // SETTINGS
    // ─────────────────────────────────────────────

    private function loadSettings(): void
    {
        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM gmb_settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log("GMB Settings load error: " . $e->getMessage());
        }
    }

    public function getSetting(string $key, $default = ''): string
    {
        return $this->settings[$key] ?? $default;
    }

    public function updateSetting(string $key, string $value): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO gmb_settings (setting_key, setting_value) 
                 VALUES (:key, :value) 
                 ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = NOW()"
            );
            return $stmt->execute([
                ':key' => $key,
                ':value' => $value,
                ':value2' => $value
            ]);
        } catch (PDOException $e) {
            error_log("GMB Setting update error: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // GOOGLE PLACES API - RECHERCHE
    // ─────────────────────────────────────────────

    /**
     * Recherche de businesses via Google Places Text Search API
     */
    public function searchPlaces(string $query, string $location = '', int $radiusKm = 30): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Clé API Google Places non configurée'];
        }

        // Créer le job de scraping
        $jobId = $this->createScrapeJob($query, $location ?: $this->settings['default_search_location'], $radiusKm);

        try {
            $this->updateScrapeJob($jobId, 'running');

            // Géocoder la location pour obtenir lat/lng
            $coords = $this->geocodeLocation($location ?: $this->settings['default_search_location']);
            if (!$coords) {
                $this->updateScrapeJob($jobId, 'failed', 'Impossible de géocoder la localisation');
                return ['success' => false, 'error' => 'Localisation non trouvée'];
            }

            $results = [];
            $nextPageToken = null;
            $maxPages = 3; // Google retourne max 60 résultats (3 pages x 20)

            for ($page = 0; $page < $maxPages; $page++) {
                $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query([
                    'query' => $query . ' ' . ($location ?: $this->settings['default_search_location']),
                    'location' => $coords['lat'] . ',' . $coords['lng'],
                    'radius' => $radiusKm * 1000,
                    'language' => 'fr',
                    'key' => $this->apiKey
                ]);

                if ($nextPageToken) {
                    $url .= '&pagetoken=' . $nextPageToken;
                    sleep(2); // Google demande un délai entre les pages
                }

                $response = $this->httpGet($url);
                if (!$response || !isset($response['results'])) break;

                foreach ($response['results'] as $place) {
                    $results[] = $this->formatPlaceResult($place);
                }

                $nextPageToken = $response['next_page_token'] ?? null;
                if (!$nextPageToken) break;
            }

            // Sauvegarder les résultats
            $saved = 0;
            foreach ($results as $result) {
                if ($this->saveContact($result)) {
                    $saved++;
                }
            }

            $this->updateScrapeJob($jobId, 'completed', null, count($results), $saved);

            return [
                'success' => true,
                'job_id' => $jobId,
                'total_found' => count($results),
                'total_saved' => $saved,
                'results' => $results
            ];
        } catch (\Exception $e) {
            $this->updateScrapeJob($jobId, 'failed', $e->getMessage());
            error_log("GMB Search error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtenir les détails complets d'un lieu (téléphone, site web, horaires)
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
            'place_id' => $placeId,
            'fields' => 'name,formatted_address,formatted_phone_number,international_phone_number,website,url,rating,user_ratings_total,types,opening_hours,business_status,address_components',
            'language' => 'fr',
            'key' => $this->apiKey
        ]);

        $response = $this->httpGet($url);
        return $response['result'] ?? null;
    }

    /**
     * Enrichir un contact avec les détails Place Details + scraping site web
     */
    public function enrichContact(int $contactId): array
    {
        $contact = $this->getContact($contactId);
        if (!$contact) {
            return ['success' => false, 'error' => 'Contact non trouvé'];
        }

        $updates = [];

        // 1. Récupérer les détails Google Places
        if (!empty($contact['place_id'])) {
            $details = $this->getPlaceDetails($contact['place_id']);
            if ($details) {
                if (!empty($details['formatted_phone_number']) && empty($contact['phone'])) {
                    $updates['phone'] = $details['formatted_phone_number'];
                }
                if (!empty($details['website']) && empty($contact['website'])) {
                    $updates['website'] = $details['website'];
                }
                if (!empty($details['rating'])) {
                    $updates['rating'] = $details['rating'];
                }
                if (!empty($details['user_ratings_total'])) {
                    $updates['reviews_count'] = $details['user_ratings_total'];
                }
            }
        }

        // 2. Scraper le site web pour trouver email et infos
        $website = $updates['website'] ?? $contact['website'];
        if (!empty($website)) {
            $webData = $this->scrapeWebsite($website);
            if (!empty($webData['email']) && empty($contact['email'])) {
                $updates['email'] = $webData['email'];
            }
            if (!empty($webData['secondary_email'])) {
                $updates['secondary_email'] = $webData['secondary_email'];
            }
            if (!empty($webData['phone']) && empty($contact['phone']) && empty($updates['phone'])) {
                $updates['phone'] = $webData['phone'];
            }
            if (!empty($webData['contact_name']) && empty($contact['contact_name'])) {
                $updates['contact_name'] = $webData['contact_name'];
            }
        }

        // 3. Valider l'email si trouvé
        $emailToValidate = $updates['email'] ?? $contact['email'];
        if (!empty($emailToValidate) && $contact['email_status'] === 'unknown') {
            $validation = $this->validateEmail($emailToValidate);
            $updates['email_status'] = $validation['status'];
            $updates['email_validated_at'] = date('Y-m-d H:i:s');
        }

        // 4. Appliquer les mises à jour
        if (!empty($updates)) {
            $updates['last_enriched_at'] = date('Y-m-d H:i:s');
            $this->updateContact($contactId, $updates);
        }

        return [
            'success' => true,
            'contact_id' => $contactId,
            'updates' => $updates,
            'fields_updated' => count($updates)
        ];
    }

    // ─────────────────────────────────────────────
    // SCRAPING SITE WEB
    // ─────────────────────────────────────────────

    /**
     * Scrape un site web pour extraire email, téléphone, nom du contact
     */
    public function scrapeWebsite(string $url): array
    {
        $result = [
            'email' => null,
            'secondary_email' => null,
            'phone' => null,
            'contact_name' => null
        ];

        // Pages à scanner
        $pagesToScan = [
            $url,
            rtrim($url, '/') . '/contact',
            rtrim($url, '/') . '/contact.html',
            rtrim($url, '/') . '/contact.php',
            rtrim($url, '/') . '/a-propos',
            rtrim($url, '/') . '/about',
            rtrim($url, '/') . '/mentions-legales',
        ];

        $allEmails = [];
        $allPhones = [];

        foreach ($pagesToScan as $pageUrl) {
            $html = $this->httpGetHtml($pageUrl);
            if (!$html) continue;

            // Extraire emails
            preg_match_all(
                '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
                $html,
                $emailMatches
            );
            foreach ($emailMatches[0] as $email) {
                $email = strtolower(trim($email));
                // Filtrer les faux positifs
                if (!$this->isValidEmailFormat($email)) continue;
                if (preg_match('/(wixpress|sentry|googleapis|example\.com|wordpress|jquery)/i', $email)) continue;
                $allEmails[] = $email;
            }

            // Extraire téléphones FR
            preg_match_all(
                '/(?:(?:\+33|0033|0)\s*[1-9])(?:[\s.\-]?\d{2}){4}/',
                $html,
                $phoneMatches
            );
            foreach ($phoneMatches[0] as $phone) {
                $cleanPhone = preg_replace('/[\s.\-]/', '', $phone);
                $allPhones[] = $cleanPhone;
            }

            // Tenter d'extraire un nom (heuristique basique)
            if (empty($result['contact_name'])) {
                // Chercher dans les meta tags
                if (preg_match('/<meta[^>]*name=["\']author["\'][^>]*content=["\'](.*?)["\']/i', $html, $authorMatch)) {
                    $result['contact_name'] = html_entity_decode(trim($authorMatch[1]));
                }
            }
        }

        // Dédupliquer et assigner
        $uniqueEmails = array_unique($allEmails);
        if (count($uniqueEmails) >= 1) {
            $result['email'] = array_shift($uniqueEmails);
        }
        if (count($uniqueEmails) >= 1) {
            $result['secondary_email'] = array_shift($uniqueEmails);
        }

        $uniquePhones = array_unique($allPhones);
        if (!empty($uniquePhones)) {
            $result['phone'] = array_shift($uniquePhones);
        }

        return $result;
    }

    // ─────────────────────────────────────────────
    // VALIDATION EMAIL
    // ─────────────────────────────────────────────

    /**
     * Valider un email : format + MX + SMTP check
     */
    public function validateEmail(string $email): array
    {
        $result = [
            'email' => $email,
            'status' => 'unknown',
            'details' => []
        ];

        // 1. Vérification format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['status'] = 'invalid';
            $result['details'][] = 'Format invalide';
            return $result;
        }

        // 2. Vérifier domaine jetable
        $disposableDomains = [
            'guerrillamail.com', 'mailinator.com', 'tempmail.com', 'throwaway.email',
            'yopmail.com', 'trashmail.com', 'dispostable.com', 'maildrop.cc',
            'temp-mail.org', 'fakeinbox.com', 'sharklasers.com', 'guerrillamailblock.com'
        ];
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        if (in_array($domain, $disposableDomains)) {
            $result['status'] = 'disposable';
            $result['details'][] = 'Email jetable détecté';
            return $result;
        }

        // 3. Vérification MX
        $mxHosts = [];
        $mxWeights = [];
        if (!getmxrr($domain, $mxHosts, $mxWeights)) {
            // Pas de MX, vérifier A record
            if (!checkdnsrr($domain, 'A')) {
                $result['status'] = 'invalid';
                $result['details'][] = 'Domaine inexistant (pas de MX ni A record)';
                return $result;
            }
            $mxHosts = [$domain]; // Fallback sur le domaine lui-même
        }
        $result['details'][] = 'MX trouvé: ' . implode(', ', array_slice($mxHosts, 0, 3));

        // 4. Vérification SMTP (connexion sans envoyer)
        $smtpResult = $this->smtpCheck($email, $mxHosts[0]);
        $result['status'] = $smtpResult['status'];
        $result['details'] = array_merge($result['details'], $smtpResult['details']);

        return $result;
    }

    /**
     * Vérification SMTP - se connecte au serveur mail pour vérifier l'adresse
     */
    private function smtpCheck(string $email, string $mxHost): array
    {
        $result = ['status' => 'unknown', 'details' => []];

        try {
            $socket = @fsockopen($mxHost, 25, $errno, $errstr, 10);
            if (!$socket) {
                // Essayer port 587
                $socket = @fsockopen($mxHost, 587, $errno, $errstr, 10);
            }

            if (!$socket) {
                $result['details'][] = "Connexion SMTP impossible: $errstr";
                return $result;
            }

            stream_set_timeout($socket, 10);

            // Lire le banner
            $this->smtpRead($socket);

            // EHLO
            $this->smtpWrite($socket, "EHLO ecosystemeimmo.com\r\n");
            $this->smtpRead($socket);

            // MAIL FROM
            $this->smtpWrite($socket, "MAIL FROM:<verify@ecosystemeimmo.com>\r\n");
            $response = $this->smtpRead($socket);

            if (strpos($response, '250') !== false) {
                // RCPT TO - c'est ici qu'on vérifie si l'email existe
                $this->smtpWrite($socket, "RCPT TO:<{$email}>\r\n");
                $response = $this->smtpRead($socket);

                if (strpos($response, '250') !== false) {
                    $result['status'] = 'valid';
                    $result['details'][] = 'Adresse acceptée par le serveur SMTP';
                } elseif (strpos($response, '550') !== false || strpos($response, '553') !== false) {
                    $result['status'] = 'invalid';
                    $result['details'][] = 'Adresse rejetée par le serveur SMTP';
                } elseif (strpos($response, '452') !== false || strpos($response, '451') !== false) {
                    $result['status'] = 'catch_all';
                    $result['details'][] = 'Serveur catch-all ou temporairement indisponible';
                } else {
                    $result['status'] = 'catch_all';
                    $result['details'][] = 'Réponse SMTP ambiguë: ' . trim($response);
                }
            }

            // QUIT proprement
            $this->smtpWrite($socket, "QUIT\r\n");
            fclose($socket);

        } catch (\Exception $e) {
            $result['details'][] = 'Erreur SMTP: ' . $e->getMessage();
        }

        return $result;
    }

    private function smtpWrite($socket, string $data): void
    {
        fwrite($socket, $data);
    }

    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    }

    // ─────────────────────────────────────────────
    // CONTACTS CRUD
    // ─────────────────────────────────────────────

    public function saveContact(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO gmb_contacts 
                (place_id, business_name, business_category, rating, reviews_count, 
                 address, city, postal_code, latitude, longitude, google_maps_url,
                 website, phone, scrape_source)
                VALUES 
                (:place_id, :business_name, :business_category, :rating, :reviews_count,
                 :address, :city, :postal_code, :latitude, :longitude, :google_maps_url,
                 :website, :phone, :scrape_source)
                ON DUPLICATE KEY UPDATE 
                 rating = VALUES(rating),
                 reviews_count = VALUES(reviews_count),
                 updated_at = NOW()"
            );

            return $stmt->execute([
                ':place_id' => $data['place_id'] ?? null,
                ':business_name' => $data['business_name'] ?? '',
                ':business_category' => $data['business_category'] ?? null,
                ':rating' => $data['rating'] ?? null,
                ':reviews_count' => $data['reviews_count'] ?? 0,
                ':address' => $data['address'] ?? null,
                ':city' => $data['city'] ?? null,
                ':postal_code' => $data['postal_code'] ?? null,
                ':latitude' => $data['latitude'] ?? null,
                ':longitude' => $data['longitude'] ?? null,
                ':google_maps_url' => $data['google_maps_url'] ?? null,
                ':website' => $data['website'] ?? null,
                ':phone' => $data['phone'] ?? null,
                ':scrape_source' => 'google_places'
            ]);
        } catch (PDOException $e) {
            error_log("GMB Save contact error: " . $e->getMessage());
            return false;
        }
    }

    public function getContact(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM gmb_contacts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function updateContact(int $id, array $data): bool
    {
        $allowedFields = [
            'business_name', 'business_category', 'rating', 'reviews_count',
            'address', 'city', 'postal_code', 'website', 'phone', 'email',
            'email_status', 'email_validated_at', 'secondary_email', 'secondary_phone',
            'contact_name', 'contact_type', 'prospect_status', 'partnership_type',
            'partner_reference', 'notes', 'tags', 'last_enriched_at'
        ];

        $sets = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $sets[] = "`{$key}` = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($sets)) return false;

        $sql = "UPDATE gmb_contacts SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteContact(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM gmb_contacts WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Lister les contacts avec filtres et pagination
     */
    public function listContacts(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(business_name LIKE :search OR email LIKE :search2 OR city LIKE :search3)";
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['contact_type'])) {
            $where[] = "contact_type = :contact_type";
            $params[':contact_type'] = $filters['contact_type'];
        }
        if (!empty($filters['prospect_status'])) {
            $where[] = "prospect_status = :prospect_status";
            $params[':prospect_status'] = $filters['prospect_status'];
        }
        if (!empty($filters['email_status'])) {
            $where[] = "email_status = :email_status";
            $params[':email_status'] = $filters['email_status'];
        }
        if (!empty($filters['city'])) {
            $where[] = "city = :city";
            $params[':city'] = $filters['city'];
        }
        if (!empty($filters['list_id'])) {
            $where[] = "id IN (SELECT contact_id FROM gmb_contact_list_members WHERE list_id = :list_id)";
            $params[':list_id'] = $filters['list_id'];
        }
        if (!empty($filters['has_email'])) {
            $where[] = "email IS NOT NULL AND email != ''";
        }
        if (!empty($filters['partnership_type']) && $filters['partnership_type'] !== 'all') {
            $where[] = "partnership_type = :partnership_type";
            $params[':partnership_type'] = $filters['partnership_type'];
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM gmb_contacts WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Fetch page
        $sql = "SELECT * FROM gmb_contacts WHERE {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'contacts' => $contacts,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    // ─────────────────────────────────────────────
    // LISTES DE CONTACTS
    // ─────────────────────────────────────────────

    public function createList(string $name, string $description = '', string $color = '#3B82F6'): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO gmb_contact_lists (name, description, color) VALUES (:name, :desc, :color)"
        );
        $stmt->execute([':name' => $name, ':desc' => $description, ':color' => $color]);
        return (int)$this->db->lastInsertId();
    }

    public function addToList(int $contactId, int $listId): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO gmb_contact_list_members (contact_id, list_id) VALUES (:cid, :lid)"
            );
            $stmt->execute([':cid' => $contactId, ':lid' => $listId]);
            // Update count
            $this->db->exec("UPDATE gmb_contact_lists SET contacts_count = (SELECT COUNT(*) FROM gmb_contact_list_members WHERE list_id = {$listId}) WHERE id = {$listId}");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function removeFromList(int $contactId, int $listId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM gmb_contact_list_members WHERE contact_id = :cid AND list_id = :lid");
        $stmt->execute([':cid' => $contactId, ':lid' => $listId]);
        $this->db->exec("UPDATE gmb_contact_lists SET contacts_count = (SELECT COUNT(*) FROM gmb_contact_list_members WHERE list_id = {$listId}) WHERE id = {$listId}");
        return true;
    }

    public function getLists(): array
    {
        return $this->db->query("SELECT * FROM gmb_contact_lists ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────
    // STATISTIQUES DASHBOARD
    // ─────────────────────────────────────────────

    public function getStats(): array
    {
        $stats = [];

        $stats['total_contacts'] = $this->db->query("SELECT COUNT(*) FROM gmb_contacts")->fetchColumn();
        $stats['valid_emails'] = $this->db->query("SELECT COUNT(*) FROM gmb_contacts WHERE email_status = 'valid'")->fetchColumn();
        $stats['contacts_with_email'] = $this->db->query("SELECT COUNT(*) FROM gmb_contacts WHERE email IS NOT NULL AND email != ''")->fetchColumn();
        $stats['new_prospects'] = $this->db->query("SELECT COUNT(*) FROM gmb_contacts WHERE prospect_status = 'nouveau'")->fetchColumn();
        $stats['partners'] = $this->db->query("SELECT COUNT(*) FROM gmb_contacts WHERE prospect_status = 'partenaire'")->fetchColumn();
        $stats['total_lists'] = $this->db->query("SELECT COUNT(*) FROM gmb_contact_lists")->fetchColumn();
        $stats['emails_sent'] = $this->db->query("SELECT COUNT(*) FROM gmb_email_sends WHERE status != 'queued'")->fetchColumn();
        $stats['emails_opened'] = $this->db->query("SELECT COUNT(*) FROM gmb_email_sends WHERE opened_at IS NOT NULL")->fetchColumn();
        $stats['active_sequences'] = $this->db->query("SELECT COUNT(*) FROM gmb_email_sequences WHERE is_active = 1")->fetchColumn();

        // Par type de contact
        $stmt = $this->db->query("SELECT contact_type, COUNT(*) as count FROM gmb_contacts GROUP BY contact_type ORDER BY count DESC");
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Par statut prospection
        $stmt = $this->db->query("SELECT prospect_status, COUNT(*) as count FROM gmb_contacts GROUP BY prospect_status ORDER BY count DESC");
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Derniers scrapes
        $stmt = $this->db->query("SELECT * FROM gmb_scrape_jobs ORDER BY created_at DESC LIMIT 5");
        $stats['recent_jobs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    // ─────────────────────────────────────────────
    // HELPERS PRIVÉS
    // ─────────────────────────────────────────────

    private function formatPlaceResult(array $place): array
    {
        $city = '';
        $postalCode = '';
        $address = $place['formatted_address'] ?? '';

        // Tenter d'extraire ville et code postal de l'adresse
        if (preg_match('/(\d{5})\s+([^,]+)/', $address, $matches)) {
            $postalCode = $matches[1];
            $city = trim($matches[2]);
        }

        return [
            'place_id' => $place['place_id'] ?? null,
            'business_name' => $place['name'] ?? '',
            'business_category' => $place['types'][0] ?? null,
            'rating' => $place['rating'] ?? null,
            'reviews_count' => $place['user_ratings_total'] ?? 0,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postalCode,
            'latitude' => $place['geometry']['location']['lat'] ?? null,
            'longitude' => $place['geometry']['location']['lng'] ?? null,
            'google_maps_url' => 'https://www.google.com/maps/place/?q=place_id:' . ($place['place_id'] ?? ''),
            'website' => null,
            'phone' => null
        ];
    }

    private function geocodeLocation(string $location): ?array
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $location,
            'key' => $this->apiKey
        ]);

        $response = $this->httpGet($url);
        if (!empty($response['results'][0]['geometry']['location'])) {
            return $response['results'][0]['geometry']['location'];
        }
        return null;
    }

    private function httpGet(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'EcosystemeImmo/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return null;
        return json_decode($response, true);
    }

    private function httpGetHtml(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; EcosystemeImmo/1.0)'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200 && $response) ? $response : null;
    }

    private function isValidEmailFormat(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        if (strlen($email) > 254) return false;
        // Exclure les extensions de fichiers communes
        if (preg_match('/\.(png|jpg|jpeg|gif|svg|css|js|woff|ttf)$/i', $email)) return false;
        return true;
    }

    private function createScrapeJob(string $query, string $location, int $radius): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO gmb_scrape_jobs (search_query, location, radius_km) VALUES (:q, :l, :r)"
        );
        $stmt->execute([':q' => $query, ':l' => $location, ':r' => $radius]);
        return (int)$this->db->lastInsertId();
    }

    private function updateScrapeJob(int $id, string $status, ?string $error = null, int $found = 0, int $saved = 0): void
    {
        $stmt = $this->db->prepare(
            "UPDATE gmb_scrape_jobs SET 
             status = :status, error_message = :error, results_found = :found, results_saved = :saved,
             started_at = IF(:status2 = 'running', NOW(), started_at),
             completed_at = IF(:status3 IN ('completed','failed'), NOW(), completed_at)
             WHERE id = :id"
        );
        $stmt->execute([
            ':status' => $status, ':error' => $error, ':found' => $found, ':saved' => $saved,
            ':status2' => $status, ':status3' => $status, ':id' => $id
        ]);
    }
}