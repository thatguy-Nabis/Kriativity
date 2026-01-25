<?php
// ============================================
// RECOMMENDATION SERVICE
// Content-Based Filtering Algorithm
// File: services/RecommendationService.php
// ============================================

class RecommendationService {
    private $pdo;
    private $cacheEnabled = true;
    private $cacheDuration = 3600; // 1 hour in seconds
    
    /**
     * Constructor
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get personalized recommendations for a user
     * @param int $userId User ID
     * @param int $limit Number of recommendations to return
     * @param bool $useCache Whether to use cached recommendations
     * @return array Array of recommended content with similarity scores
     */
    public function recommend($userId, $limit = 10, $useCache = true) {
        // Check cache first
        if ($useCache && $this->cacheEnabled) {
            $cached = $this->getCachedRecommendations($userId, $limit);
            if (!empty($cached)) {
                return $cached;
            }
        }
        
        // Build or update user profile
        $userProfile = $this->getUserProfile($userId);
        
        if (empty($userProfile) || $userProfile['profile_strength'] < 0.1) {
            // User has insufficient data, return popular content
            return $this->getPopularContent($limit);
        }
        
        // Get all available content (excluding what user already liked/collected)
        $availableContent = $this->getAvailableContent($userId);
        
        if (empty($availableContent)) {
            return [];
        }
        
        // Calculate similarity scores
        $recommendations = [];
        foreach ($availableContent as $content) {
            $similarity = $this->calculateSimilarity(
                $userProfile['feature_vector'],
                $content['feature_vector']
            );
            
            $recommendations[] = [
                'content_id' => $content['content_id'],
                'title' => $content['title'],
                'description' => $content['description'],
                'category' => $content['category'],
                'image_url' => $content['image_url'],
                'username' => $content['username'],
                'views' => $content['views'],
                'likes' => $content['likes'],
                'similarity_score' => $similarity,
                'style' => $content['style'],
                'medium' => $content['medium'],
                'tags' => $content['tags']
            ];
        }
        
        // Sort by similarity score (highest first)
        usort($recommendations, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });
        
        // Limit results
        $recommendations = array_slice($recommendations, 0, $limit);
        
        // Cache the results
        if ($this->cacheEnabled) {
            $this->cacheRecommendations($userId, $recommendations);
        }
        
        return $recommendations;
    }
    
    /**
     * Build or retrieve user feature profile
     * @param int $userId User ID
     * @return array User profile with feature vector
     */
    private function getUserProfile($userId) {
        // Check if profile exists and is up-to-date
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_feature_profiles 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If profile doesn't exist or needs recalculation, build it
        if (!$profile || $profile['needs_recalculation']) {
            $profile = $this->buildUserProfile($userId);
        }
        
        // Decode JSON fields
        if ($profile) {
            $profile['preferred_styles'] = json_decode($profile['preferred_styles'] ?? '{}', true);
            $profile['preferred_mediums'] = json_decode($profile['preferred_mediums'] ?? '{}', true);
            $profile['preferred_subjects'] = json_decode($profile['preferred_subjects'] ?? '{}', true);
            $profile['preferred_moods'] = json_decode($profile['preferred_moods'] ?? '{}', true);
            $profile['preferred_colors'] = json_decode($profile['preferred_colors'] ?? '{}', true);
            $profile['feature_vector'] = json_decode($profile['feature_vector'] ?? '{}', true);
        }
        
        return $profile;
    }
    
    /**
     * Build user profile from likes and collections
     * @param int $userId User ID
     * @return array Built user profile
     */
    private function buildUserProfile($userId) {
        // Get all content user has interacted with (likes + collections)
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT cf.*
            FROM content_features cf
            WHERE cf.content_id IN (
                -- From likes
                SELECT content_id FROM likes WHERE user_id = :user_id
                UNION
                -- From collections
                SELECT ci.content_id 
                FROM collection_items ci
                JOIN collections col ON ci.collection_id = col.id
                WHERE col.user_id = :user_id
            )
        ");
        $stmt->execute(['user_id' => $userId]);
        $userContent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($userContent)) {
            return $this->createEmptyProfile($userId);
        }
        
        // Aggregate preferences
        $styles = [];
        $mediums = [];
        $subjects = [];
        $moods = [];
        $colors = [];
        
        foreach ($userContent as $content) {
            // Count styles
            if (!empty($content['style'])) {
                $styles[$content['style']] = ($styles[$content['style']] ?? 0) + 1;
            }
            
            // Count mediums
            if (!empty($content['medium'])) {
                $mediums[$content['medium']] = ($mediums[$content['medium']] ?? 0) + 1;
            }
            
            // Count subjects
            if (!empty($content['subject_matter'])) {
                $subjects[$content['subject_matter']] = ($subjects[$content['subject_matter']] ?? 0) + 1;
            }
            
            // Count moods
            if (!empty($content['mood'])) {
                $moods[$content['mood']] = ($moods[$content['mood']] ?? 0) + 1;
            }
            
            // Count colors
            $contentColors = json_decode($content['dominant_colors'] ?? '[]', true);
            foreach ($contentColors as $color) {
                $colors[$color] = ($colors[$color] ?? 0) + 1;
            }
        }
        
        // Normalize to probabilities (0-1)
        $totalItems = count($userContent);
        $styles = $this->normalizeArray($styles, $totalItems);
        $mediums = $this->normalizeArray($mediums, $totalItems);
        $subjects = $this->normalizeArray($subjects, $totalItems);
        $moods = $this->normalizeArray($moods, $totalItems);
        $colors = $this->normalizeArray($colors, $totalItems);
        
        // Create feature vector for cosine similarity
        $featureVector = [
            'styles' => $styles,
            'mediums' => $mediums,
            'subjects' => $subjects,
            'moods' => $moods,
            'colors' => $colors
        ];
        
        // Calculate profile strength (0-1)
        $profileStrength = min(1.0, $totalItems / 10); // Full strength at 10+ items
        
        // Save to database
        $stmt = $this->pdo->prepare("
            INSERT INTO user_feature_profiles (
                user_id, preferred_styles, preferred_mediums, 
                preferred_subjects, preferred_moods, preferred_colors,
                feature_vector, total_likes, total_collections,
                profile_strength, needs_recalculation, last_calculated
            ) VALUES (
                :user_id, :styles, :mediums, :subjects, :moods, :colors,
                :feature_vector, :total_likes, :total_collections,
                :profile_strength, FALSE, NOW()
            )
            ON DUPLICATE KEY UPDATE
                preferred_styles = :styles,
                preferred_mediums = :mediums,
                preferred_subjects = :subjects,
                preferred_moods = :moods,
                preferred_colors = :colors,
                feature_vector = :feature_vector,
                total_likes = :total_likes,
                total_collections = :total_collections,
                profile_strength = :profile_strength,
                needs_recalculation = FALSE,
                last_calculated = NOW()
        ");
        
        // Count actual likes and collections
        $countsStmt = $this->pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM likes WHERE user_id = :user_id) as total_likes,
                (SELECT COUNT(DISTINCT ci.id) 
                 FROM collection_items ci
                 JOIN collections col ON ci.collection_id = col.id
                 WHERE col.user_id = :user_id) as total_collections
        ");
        $countsStmt->execute(['user_id' => $userId]);
        $counts = $countsStmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt->execute([
            'user_id' => $userId,
            'styles' => json_encode($styles),
            'mediums' => json_encode($mediums),
            'subjects' => json_encode($subjects),
            'moods' => json_encode($moods),
            'colors' => json_encode($colors),
            'feature_vector' => json_encode($featureVector),
            'total_likes' => $counts['total_likes'],
            'total_collections' => $counts['total_collections'],
            'profile_strength' => $profileStrength
        ]);
        
        return [
            'user_id' => $userId,
            'preferred_styles' => $styles,
            'preferred_mediums' => $mediums,
            'preferred_subjects' => $subjects,
            'preferred_moods' => $moods,
            'preferred_colors' => $colors,
            'feature_vector' => $featureVector,
            'profile_strength' => $profileStrength
        ];
    }
    
    /**
     * Create empty profile for new users
     * @param int $userId User ID
     * @return array Empty profile
     */
    private function createEmptyProfile($userId) {
        return [
            'user_id' => $userId,
            'preferred_styles' => [],
            'preferred_mediums' => [],
            'preferred_subjects' => [],
            'preferred_moods' => [],
            'preferred_colors' => [],
            'feature_vector' => [],
            'profile_strength' => 0.0
        ];
    }
    
    /**
     * Get available content for recommendations (excluding already liked/collected)
     * @param int $userId User ID
     * @return array Available content with features
     */
    private function getAvailableContent($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id as content_id,
                c.title,
                c.description,
                c.category,
                c.image_url,
                c.views,
                c.likes,
                u.username,
                cf.style,
                cf.medium,
                cf.dominant_colors,
                cf.subject_matter,
                cf.mood,
                cf.tags,
                cf.feature_vector
            FROM content c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN content_features cf ON c.id = cf.content_id
            WHERE c.is_published = 1
            AND c.id NOT IN (
                -- Exclude liked content
                SELECT content_id FROM likes WHERE user_id = :user_id
                UNION
                -- Exclude collected content
                SELECT ci.content_id 
                FROM collection_items ci
                JOIN collections col ON ci.collection_id = col.id
                WHERE col.user_id = :user_id
            )
            AND cf.id IS NOT NULL -- Only content with features
            ORDER BY c.created_at DESC
            LIMIT 100 -- Limit for performance
        ");
        $stmt->execute(['user_id' => $userId]);
        $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($content as &$item) {
            $item['dominant_colors'] = json_decode($item['dominant_colors'] ?? '[]', true);
            $item['tags'] = json_decode($item['tags'] ?? '[]', true);
            $item['feature_vector'] = json_decode($item['feature_vector'] ?? '{}', true);
        }
        
        return $content;
    }
    
    /**
     * Calculate cosine similarity between user profile and content
     * @param array $userVector User feature vector
     * @param array $contentVector Content feature vector
     * @return float Similarity score (0-1)
     */
    private function calculateSimilarity($userVector, $contentVector) {
        if (empty($userVector) || empty($contentVector)) {
            return 0.0;
        }
        
        $similarity = 0.0;
        $weights = [
            'styles' => 0.30,   // 30% weight
            'mediums' => 0.20,  // 20% weight
            'subjects' => 0.25, // 25% weight
            'moods' => 0.15,    // 15% weight
            'colors' => 0.10    // 10% weight
        ];
        
        foreach ($weights as $feature => $weight) {
            $userFeature = $userVector[$feature] ?? [];
            $contentFeature = $this->extractContentFeature($contentVector, $feature);
            
            if (!empty($userFeature) && !empty($contentFeature)) {
                $featureSimilarity = $this->cosineSimilarity($userFeature, $contentFeature);
                $similarity += $featureSimilarity * $weight;
            }
        }
        
        return round($similarity, 4);
    }
    
    /**
     * Extract content feature for comparison
     * @param array $contentVector Content feature data
     * @param string $featureType Feature type (styles, mediums, etc.)
     * @return array Feature array
     */
    private function extractContentFeature($contentVector, $featureType) {
        // Map database fields to feature types
        $mapping = [
            'styles' => 'style',
            'mediums' => 'medium',
            'subjects' => 'subject_matter',
            'moods' => 'mood',
            'colors' => 'dominant_colors'
        ];
        
        $field = $mapping[$featureType] ?? null;
        if (!$field || !isset($contentVector[$field])) {
            return [];
        }
        
        $value = $contentVector[$field];
        
        // For colors, return as-is (already an array)
        if ($featureType === 'colors' && is_array($value)) {
            $result = [];
            foreach ($value as $color) {
                $result[$color] = 1.0;
            }
            return $result;
        }
        
        // For other features, create single-item array
        if (!empty($value)) {
            return [$value => 1.0];
        }
        
        return [];
    }
    
    /**
     * Calculate cosine similarity between two vectors
     * @param array $vector1 First vector
     * @param array $vector2 Second vector
     * @return float Similarity score (0-1)
     */
    private function cosineSimilarity($vector1, $vector2) {
        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;
        
        // Get all unique keys
        $allKeys = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));
        
        foreach ($allKeys as $key) {
            $val1 = $vector1[$key] ?? 0.0;
            $val2 = $vector2[$key] ?? 0.0;
            
            $dotProduct += $val1 * $val2;
            $magnitude1 += $val1 * $val1;
            $magnitude2 += $val2 * $val2;
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Normalize array values to sum to 1.0
     * @param array $array Input array
     * @param int $total Total count for normalization
     * @return array Normalized array
     */
    private function normalizeArray($array, $total) {
        if ($total == 0) {
            return [];
        }
        
        $normalized = [];
        foreach ($array as $key => $value) {
            $normalized[$key] = round($value / $total, 4);
        }
        
        // Sort by value descending
        arsort($normalized);
        
        return $normalized;
    }
    
    /**
     * Get cached recommendations
     * @param int $userId User ID
     * @param int $limit Number of recommendations
     * @return array Cached recommendations or empty array
     */
    private function getCachedRecommendations($userId, $limit) {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id as content_id,
                c.title,
                c.description,
                c.category,
                c.image_url,
                c.views,
                c.likes,
                u.username,
                rc.similarity_score,
                cf.style,
                cf.medium,
                cf.tags
            FROM recommendation_cache rc
            JOIN content c ON rc.content_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN content_features cf ON c.id = cf.content_id
            WHERE rc.user_id = :user_id
            AND rc.is_valid = TRUE
            AND (rc.expires_at IS NULL OR rc.expires_at > NOW())
            ORDER BY rc.rank_position ASC
            LIMIT :limit
        ");
        $stmt->execute(['user_id' => $userId, 'limit' => $limit]);
        $cached = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($cached as &$item) {
            $item['tags'] = json_decode($item['tags'] ?? '[]', true);
        }
        
        return $cached;
    }
    
    /**
     * Cache recommendations
     * @param int $userId User ID
     * @param array $recommendations Recommendations to cache
     */
    private function cacheRecommendations($userId, $recommendations) {
        // Clear old cache for this user
        $stmt = $this->pdo->prepare("DELETE FROM recommendation_cache WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        
        // Insert new recommendations
        $stmt = $this->pdo->prepare("
            INSERT INTO recommendation_cache (
                user_id, content_id, similarity_score, rank_position,
                recommendation_type, generated_at, expires_at
            ) VALUES (
                :user_id, :content_id, :similarity_score, :rank_position,
                'content_based', NOW(), DATE_ADD(NOW(), INTERVAL :duration SECOND)
            )
        ");
        
        foreach ($recommendations as $index => $rec) {
            $stmt->execute([
                'user_id' => $userId,
                'content_id' => $rec['content_id'],
                'similarity_score' => $rec['similarity_score'],
                'rank_position' => $index + 1,
                'duration' => $this->cacheDuration
            ]);
        }
    }
    
    /**
     * Get popular content as fallback
     * @param int $limit Number of items
     * @return array Popular content
     */
    private function getPopularContent($limit) {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id as content_id,
                c.title,
                c.description,
                c.category,
                c.image_url,
                c.views,
                c.likes,
                u.username,
                0.5 as similarity_score,
                cf.style,
                cf.medium,
                cf.tags
            FROM content c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN content_features cf ON c.id = cf.content_id
            WHERE c.is_published = 1
            ORDER BY (c.views * 0.3 + c.likes * 0.7) DESC
            LIMIT :limit
        ");
        $stmt->execute(['limit' => $limit]);
        $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($content as &$item) {
            $item['tags'] = json_decode($item['tags'] ?? '[]', true);
        }
        
        return $content;
    }
    
    /**
     * Invalidate cache for a user
     * @param int $userId User ID
     */
    public function invalidateCache($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE recommendation_cache 
            SET is_valid = FALSE 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        
        $stmt = $this->pdo->prepare("
            UPDATE user_feature_profiles 
            SET needs_recalculation = TRUE 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
    }
    
    /**
     * Clear all expired cache entries
     */
    public function clearExpiredCache() {
        $stmt = $this->pdo->prepare("
            DELETE FROM recommendation_cache 
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
        $stmt->execute();
    }
}