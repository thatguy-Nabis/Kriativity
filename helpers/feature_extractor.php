<?php
// ============================================
// INTEGRATION GUIDE & HELPER SCRIPTS
// ============================================

/*
 * STEP 1: PROJECT STRUCTURE
 * ============================================
 * 
 * your_project/
 * ├── config/
 * │   └── database.php
 * ├── services/
 * │   └── RecommendationService.php  (The main algorithm file)
 * ├── helpers/
 * │   ├── feature_extractor.php      (Helper to extract features from content)
 * │   └── recommendation_helper.php   (Helper functions)
 * ├── cron/
 * │   └── cleanup_cache.php          (Cron job for cache maintenance)
 * ├── recommendations.php             (Page to display recommendations)
 * └── api/
 *     └── get_recommendations.php     (AJAX endpoint)
 */

// ============================================
// FILE 1: helpers/feature_extractor.php
// Helper script to extract features from uploaded content
// ============================================
?>
<?php
/**
 * Feature Extractor Helper
 * Automatically extracts features when content is uploaded
 */

class FeatureExtractor {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Extract and save features for a piece of content
     * @param int $contentId Content ID
     * @param array $metadata Manual metadata (optional)
     * @return bool Success
     */
    public function extractFeatures($contentId, $metadata = []) {
        // Get content details
        $stmt = $this->pdo->prepare("SELECT * FROM content WHERE id = :id");
        $stmt->execute(['id' => $contentId]);
        $content = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$content) {
            return false;
        }
        
        // Extract or use provided metadata
        $style = $metadata['style'] ?? $this->detectStyle($content);
        $medium = $metadata['medium'] ?? $this->detectMedium($content);
        $colors = $metadata['colors'] ?? $this->extractColors($content['image_url']);
        $colorCategory = $metadata['color_category'] ?? $this->categorizeColors($colors);
        $subject = $metadata['subject'] ?? $this->detectSubject($content);
        $mood = $metadata['mood'] ?? $this->detectMood($content);
        $tags = $metadata['tags'] ?? $this->extractTags($content);
        $orientation = $metadata['orientation'] ?? $this->detectOrientation($content['image_url']);
        
        // Create feature vector
        $featureVector = [
            'style' => !empty($style) ? 1.0 : 0.0,
            'medium' => !empty($medium) ? 1.0 : 0.0,
            'color' => !empty($colors) ? 1.0 : 0.0,
            'mood' => !empty($mood) ? 1.0 : 0.0,
            'subject' => !empty($subject) ? 1.0 : 0.0
        ];
        
        // Save to database
        $stmt = $this->pdo->prepare("
            INSERT INTO content_features (
                content_id, style, medium, dominant_colors, color_palette_category,
                subject_matter, mood, tags, orientation, feature_vector
            ) VALUES (
                :content_id, :style, :medium, :colors, :color_category,
                :subject, :mood, :tags, :orientation, :feature_vector
            )
            ON DUPLICATE KEY UPDATE
                style = :style,
                medium = :medium,
                dominant_colors = :colors,
                color_palette_category = :color_category,
                subject_matter = :subject,
                mood = :mood,
                tags = :tags,
                orientation = :orientation,
                feature_vector = :feature_vector
        ");
        
        return $stmt->execute([
            'content_id' => $contentId,
            'style' => $style,
            'medium' => $medium,
            'colors' => json_encode($colors),
            'color_category' => $colorCategory,
            'subject' => $subject,
            'mood' => $mood,
            'tags' => json_encode($tags),
            'orientation' => $orientation,
            'feature_vector' => json_encode($featureVector)
        ]);
    }
    
    // Simple detection methods (you can enhance these)
    
    private function detectStyle($content) {
        // Detect from category or title
        $text = strtolower($content['category'] . ' ' . $content['title']);
        
        $styles = [
            'abstract' => ['abstract', 'modern', 'contemporary'],
            'realism' => ['realistic', 'realism', 'photorealistic'],
            'impressionism' => ['impressionist', 'impressionism'],
            'surrealism' => ['surreal', 'surrealism', 'dreamlike'],
            'minimalism' => ['minimal', 'minimalist', 'simple']
        ];
        
        foreach ($styles as $style => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $style;
                }
            }
        }
        
        return 'abstract'; // Default
    }
    
    private function detectMedium($content) {
        $text = strtolower($content['category'] . ' ' . $content['description']);
        
        $mediums = [
            'digital' => ['digital', 'photoshop', 'illustrator', 'procreate'],
            'oil' => ['oil painting', 'oil on canvas'],
            'watercolor' => ['watercolor', 'watercolour', 'aquarelle'],
            'acrylic' => ['acrylic'],
            'pencil' => ['pencil', 'graphite', 'charcoal'],
            'mixed media' => ['mixed media', 'collage']
        ];
        
        foreach ($mediums as $medium => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $medium;
                }
            }
        }
        
        return 'digital'; // Default
    }
    
    private function extractColors($imageUrl) {
        // Simple color extraction (you can use image processing libraries for better results)
        // For now, return default colors
        return ['#CEA1F5', '#a66fd9', '#6B4FD9'];
    }
    
    private function categorizeColors($colors) {
        // Simple categorization
        return 'cool'; // or 'warm', 'monochrome', 'vibrant'
    }
    
    private function detectSubject($content) {
        $category = strtolower($content['category']);
        
        $subjects = [
            'landscape' => 'landscape',
            'portrait' => 'portrait',
            'nature' => 'landscape',
            'people' => 'portrait',
            'abstract' => 'abstract',
            'urban' => 'urban',
            'architecture' => 'architecture'
        ];
        
        return $subjects[$category] ?? 'abstract';
    }
    
    private function detectMood($content) {
        $text = strtolower($content['description'] ?? '');
        
        $moods = [
            'calm' => ['calm', 'peaceful', 'serene', 'tranquil'],
            'energetic' => ['energetic', 'vibrant', 'dynamic', 'exciting'],
            'melancholic' => ['sad', 'melancholic', 'somber', 'dark'],
            'joyful' => ['happy', 'joyful', 'bright', 'cheerful']
        ];
        
        foreach ($moods as $mood => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $mood;
                }
            }
        }
        
        return 'calm'; // Default
    }
    
    private function extractTags($content) {
        // Extract tags from description and category
        $tags = [$content['category']];
        
        // You can add more sophisticated tag extraction here
        return array_unique($tags);
    }
    
    private function detectOrientation($imageUrl) {
        // You would need to check actual image dimensions
        // For now, return default
        return 'landscape';
    }
}
?>